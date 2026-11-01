import { useEffect, useState } from 'react';
import { api, date, datetime } from '../../lib/api.js';
import { Badge, Button, Card, Empty, Loader, Modal, Select, useToast } from '../../components/ui.jsx';

const OS_LIST = ['Ubuntu 24.04 LTS', 'Ubuntu 22.04 LTS', 'Debian 12', 'AlmaLinux 9', 'Rocky Linux 9', 'CentOS Stream 9', 'Windows Server 2022'];
const FILTERS = [
  { key: '', label: '全部' },
  { key: 'running', label: '运行中' },
  { key: 'stopped', label: '已关机' },
  { key: 'provisioning', label: '开通中' },
  { key: 'suspended', label: '已停机' }
];

export default function AdminInstances() {
  const [list, setList] = useState(null);
  const [filter, setFilter] = useState('');
  const [reinstall, setReinstall] = useState(null);
  const [os, setOs] = useState('Ubuntu 24.04 LTS');
  const toast = useToast();

  const load = () => api('/admin/instances').then(setList).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const power = async (inst, action) => {
    try {
      await api(`/admin/instances/${inst.id}/power`, { method: 'POST', body: { action } });
      toast('操作已执行');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const terminate = async (inst) => {
    if (!confirm(`确认强制销毁实例 ${inst.name}？`)) return;
    try {
      await api(`/admin/instances/${inst.id}/terminate`, { method: 'POST' });
      toast('实例已销毁');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const doReinstall = async () => {
    try {
      await api(`/admin/instances/${reinstall.id}/reinstall`, { method: 'POST', body: { os } });
      toast('重装任务已下发');
      setReinstall(null);
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const shown = (list || []).filter((i) => !filter || i.status === filter);

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">INSTANCES</span>
          <h1>实例管理</h1>
          <p>查看全部实例、强制停机/恢复与销毁，操作会同步到用户侧。</p>
        </div>
        <span className="pill-tag">{list?.length ?? 0} 台实例</span>
      </div>

      <div className="tabs">
        {FILTERS.map((f) => (
          <button key={f.key} className={`tab ${filter === f.key ? 'active' : ''}`} onClick={() => setFilter(f.key)}>
            {f.label}{f.key && ` (${(list || []).filter((i) => i.status === f.key).length})`}
          </button>
        ))}
      </div>

      {!list ? <Loader label="读取实例" /> : shown.length === 0 ? <Card><Empty title="没有实例" /></Card> : (
        <Card>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr><th>实例</th><th>用户</th><th>套餐 / 节点</th><th>规格</th><th>IP</th><th>到期</th><th>状态</th><th className="right">操作</th></tr>
              </thead>
              <tbody>
                {shown.map((i) => (
                  <tr key={i.id}>
                    <td><strong>{i.name}</strong><br /><span className="small muted mono">#{i.id} · {i.providerId || '未分配'}</span></td>
                    <td>{i.nickname}<br /><span className="small muted">UID {i.userId}</span></td>
                    <td>{i.planName}<br /><span className="small muted">{i.nodeName}</span></td>
                    <td className="small">{i.cpu}C / {i.memoryMb / 1024}G / {i.diskGb}G<br /><span className="muted">{i.os}</span></td>
                    <td className="mono small">{i.ip || '—'}</td>
                    <td className="small muted">{date(i.expiresAt)}</td>
                    <td><Badge status={i.status} /></td>
                    <td className="right">
                      <div className="cell-actions">
                        {i.status === 'running' && <Button size="sm" variant="ghost" onClick={() => power(i, 'suspend')}>停机</Button>}
                        {i.status === 'suspended' && <Button size="sm" variant="soft" onClick={() => power(i, 'resume')}>恢复</Button>}
                        {i.status !== 'terminated' && <Button size="sm" variant="ghost" onClick={() => { setReinstall(i); setOs(i.os); }}>重装</Button>}
                        {i.status !== 'terminated' && <Button size="sm" variant="danger" onClick={() => terminate(i)}>销毁</Button>}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      <Modal open={!!reinstall} onClose={() => setReinstall(null)} title={`重装系统 · ${reinstall?.name || ''}`}
        footer={<><Button variant="ghost" onClick={() => setReinstall(null)}>取消</Button><Button onClick={doReinstall}>确认重装</Button></>}>
        <p className="small muted" style={{ marginBottom: 14 }}>重装将清空磁盘并重置 root 密码。当前时间 {datetime(new Date().toISOString())}</p>
        <Select value={os} onChange={(e) => setOs(e.target.value)}>
          {OS_LIST.map((o) => <option key={o} value={o}>{o}</option>)}
        </Select>
      </Modal>
    </div>
  );
}
