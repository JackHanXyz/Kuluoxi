import { useEffect, useState } from 'react';
import { api, date, money } from '../../lib/api.js';
import { Badge, Button, Card, Field, Loader, Modal, useToast } from '../../components/ui.jsx';

export default function AdminUsers() {
  const [users, setUsers] = useState(null);
  const [q, setQ] = useState('');
  const [adjust, setAdjust] = useState(null);
  const [form, setForm] = useState({ amount: '', remark: '' });
  const [busy, setBusy] = useState(false);
  const toast = useToast();

  const load = (keyword = q) => api(`/admin/users${keyword ? `?q=${encodeURIComponent(keyword)}` : ''}`).then(setUsers).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(''); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const submitAdjust = async () => {
    const yuan = Number(form.amount);
    if (!Number.isFinite(yuan) || yuan === 0) return toast('请输入非零金额（正数增加，负数扣减）', 'error');
    setBusy(true);
    try {
      await api(`/admin/users/${adjust.id}/balance`, { method: 'POST', body: { amount: Math.round(yuan * 100), remark: form.remark } });
      toast('余额已调整');
      setAdjust(null);
      setForm({ amount: '', remark: '' });
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  const toggle = async (u) => {
    try {
      await api(`/admin/users/${u.id}`, { method: 'PATCH', body: { status: u.status === 'active' ? 'frozen' : 'active' } });
      toast(u.status === 'active' ? '账户已冻结' : '账户已恢复');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">CUSTOMERS</span>
          <h1>用户管理</h1>
          <p>查询用户、调整余额、冻结异常账户。</p>
        </div>
        <span className="pill-tag">{users?.length ?? 0} 位用户</span>
      </div>

      <div className="toolbar">
        <input className="input" placeholder="搜索邮箱或昵称…" value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load()} />
        <Button variant="ghost" onClick={() => load()}>搜索</Button>
        {q && <Button variant="link" onClick={() => { setQ(''); load(''); }}>清空</Button>}
      </div>

      {!users ? <Loader label="读取用户" /> : (
        <Card>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr><th>用户</th><th>UID</th><th>角色</th><th>余额</th><th>实例</th><th>累计消费</th><th>状态</th><th>注册时间</th><th className="right">操作</th></tr>
              </thead>
              <tbody>
                {users.map((u) => (
                  <tr key={u.id}>
                    <td><strong>{u.nickname}</strong><br /><span className="small muted">{u.email}</span></td>
                    <td className="mono">#{String(u.id).padStart(6, '0')}</td>
                    <td><span className={`pill-tag ${u.role === 'admin' ? 'pill-tag--amber' : ''}`}>{u.role === 'admin' ? '管理员' : '用户'}</span></td>
                    <td className="num">{money(u.balance)}</td>
                    <td className="num">{u.instanceCount}</td>
                    <td className="num">{money(u.totalSpent)}</td>
                    <td>{u.status === 'active' ? <span className="badge badge--running"><i />正常</span> : <span className="badge badge--frozen"><i />冻结</span>}</td>
                    <td className="small muted">{date(u.createdAt)}</td>
                    <td className="right">
                      <div className="cell-actions">
                        <Button size="sm" variant="ghost" onClick={() => { setAdjust(u); setForm({ amount: '', remark: '' }); }}>调整余额</Button>
                        {u.role !== 'admin' && <Button size="sm" variant={u.status === 'active' ? 'danger' : 'soft'} onClick={() => toggle(u)}>{u.status === 'active' ? '冻结' : '解冻'}</Button>}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      <Modal open={!!adjust} onClose={() => setAdjust(null)} title={`调整余额 · ${adjust?.nickname || ''}`}
        footer={<><Button variant="ghost" onClick={() => setAdjust(null)}>取消</Button><Button onClick={submitAdjust} disabled={busy}>确认调整</Button></>}>
        {adjust && (
          <>
            <div className="kv"><span>当前余额</span><b className="num">{money(adjust.balance)}</b></div>
            <Field label="调整金额（元）" hint="正数增加，负数扣减">
              <input className="input" type="number" step="0.01" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} placeholder="例如 100 或 -50" />
            </Field>
            <Field label="备注">
              <input className="input" value={form.remark} onChange={(e) => setForm({ ...form, remark: e.target.value })} placeholder="补偿 / 退款 / 手工扣费" />
            </Field>
            <p className="small muted">调整会生成一条资金流水，可在用户钱包中查看。</p>
          </>
        )}
      </Modal>
    </div>
  );
}
