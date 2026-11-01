import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ago, api } from '../../lib/api.js';
import { Badge, Button, Card, Empty, Field, Loader, Modal, useToast } from '../../components/ui.jsx';

const CATEGORY = { technical: '技术支持', network: '网络问题', billing: '账单支付', feature: '功能建议', other: '其他' };
const PRIORITY = { low: '低', normal: '普通', high: '高' };

export default function Tickets() {
  const [tickets, setTickets] = useState(null);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ subject: '', category: 'technical', priority: 'normal', body: '' });
  const [busy, setBusy] = useState(false);
  const toast = useToast();
  const navigate = useNavigate();

  const load = () => api('/tickets').then(setTickets).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const create = async () => {
    if (form.subject.trim().length < 4) return toast('标题至少 4 个字符', 'error');
    if (form.body.trim().length < 10) return toast('描述至少 10 个字符', 'error');
    setBusy(true);
    try {
      const res = await api('/tickets', { method: 'POST', body: form });
      toast('工单已提交，支持团队将尽快响应');
      setOpen(false);
      navigate(`/app/tickets/${res.id}`);
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">SUPPORT</span>
          <h1>工单支持</h1>
          <p>技术、网络与账单问题都可以提交工单，平均响应 12 分钟。</p>
        </div>
        <Button onClick={() => setOpen(true)}>提交工单</Button>
      </div>

      {!tickets ? (
        <Loader label="加载工单" />
      ) : tickets.length === 0 ? (
        <Card><Empty title="还没有工单" desc="遇到问题？提交工单，7×24 小时支持团队在线。" action={<Button size="sm" onClick={() => setOpen(true)}>提交工单</Button>} /></Card>
      ) : (
        <Card>
          <div className="ticket-list">
            {tickets.map((t) => (
              <div key={t.id} className="ticket-row" style={{ cursor: 'pointer' }} onClick={() => navigate(`/app/tickets/${t.id}`)}>
                <div className="tr-main">
                  <strong>#{String(t.id).padStart(5, '0')} · {t.subject}</strong>
                  <span>{CATEGORY[t.category] || t.category} · {PRIORITY[t.priority]}优先级 · {t.messageCount} 条对话 · 更新于 {ago(t.updatedAt)}</span>
                </div>
                <Badge status={t.status} />
              </div>
            ))}
          </div>
        </Card>
      )}

      <Modal open={open} onClose={() => setOpen(false)} title="提交新工单"
        footer={<><Button variant="ghost" onClick={() => setOpen(false)}>取消</Button><Button onClick={create} disabled={busy}>提交</Button></>}>
        <Field label="工单标题" hint="4-60 字符"><input className="input" value={form.subject} maxLength={60} onChange={(e) => setForm({ ...form, subject: e.target.value })} placeholder="例如：东京节点夜间丢包" /></Field>
        <div className="form-grid">
          <Field label="问题分类">
            <select className="input select" value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })}>
              {Object.entries(CATEGORY).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </Field>
          <Field label="优先级">
            <select className="input select" value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })}>
              {Object.entries(PRIORITY).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </Field>
        </div>
        <Field label="问题描述" hint="请附上实例名称与复现步骤">
          <textarea className="input textarea" value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} placeholder="详细描述遇到的现象、时间与影响范围…" />
        </Field>
      </Modal>
    </div>
  );
}
