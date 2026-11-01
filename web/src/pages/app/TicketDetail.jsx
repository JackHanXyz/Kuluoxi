import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, datetime } from '../../lib/api.js';
import { Badge, Button, Card, Loader, Textarea, useToast } from '../../components/ui.jsx';

const CATEGORY = { technical: '技术支持', network: '网络问题', billing: '账单支付', feature: '功能建议', other: '其他' };

export default function TicketDetail() {
  const { id } = useParams();
  const [ticket, setTicket] = useState(null);
  const [reply, setReply] = useState('');
  const [busy, setBusy] = useState(false);
  const toast = useToast();

  const load = () => api(`/tickets/${id}`).then(setTicket).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(); }, [id]); // eslint-disable-line react-hooks/exhaustive-deps

  const send = async () => {
    if (reply.trim().length < 1) return toast('请输入回复内容', 'error');
    setBusy(true);
    try {
      await api(`/tickets/${id}/reply`, { method: 'POST', body: { body: reply } });
      setReply('');
      toast('回复已发送');
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  const close = async () => {
    try {
      await api(`/tickets/${id}/close`, { method: 'POST' });
      toast('工单已关闭');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  if (!ticket) return <div className="page"><Loader label="读取工单" /></div>;

  return (
    <div className="page">
      <div className="page-head">
        <div style={{ maxWidth: 760 }}>
          <span className="eyebrow">TICKET #{String(ticket.id).padStart(5, '0')}</span>
          <h1 style={{ fontSize: 24 }}>{ticket.subject}</h1>
          <p>{CATEGORY[ticket.category] || ticket.category} · 创建于 {datetime(ticket.createdAt)} · 更新于 {datetime(ticket.updatedAt)}</p>
        </div>
        <div className="btn-row">
          <Link to="/app/tickets" className="btn btn--ghost btn--sm">返回列表</Link>
          {ticket.status !== 'closed' && <Button size="sm" variant="ghost" onClick={close}>关闭工单</Button>}
        </div>
      </div>

      <Card>
        <div className="card-head">
          <h3>对话记录</h3>
          <Badge status={ticket.status} />
        </div>
        <div className="thread">
          {ticket.messages.map((m) => (
            <div key={m.id} className={`msg ${m.isStaff ? 'msg--staff' : ''}`}>
              <div className="avatar" style={{ width: 32, height: 32, fontSize: 12 }}>{m.isStaff ? '汐' : m.nickname?.slice(0, 1)}</div>
              <div>
                <div className="msg-body">{m.body}</div>
                <div className="msg-meta">{m.nickname}{m.isStaff ? ' · 官方支持' : ''} · {datetime(m.createdAt)}</div>
              </div>
            </div>
          ))}
        </div>
        {ticket.status === 'closed' ? (
          <div className="composer"><p className="small muted">该工单已关闭，如需继续咨询请新建工单。</p></div>
        ) : (
          <div className="composer">
            <Textarea placeholder="输入回复内容…" value={reply} onChange={(e) => setReply(e.target.value)} style={{ minHeight: 96 }} />
            <div className="composer-actions">
              <Button onClick={send} disabled={busy}>{busy ? '发送中…' : '发送回复'}</Button>
            </div>
          </div>
        )}
      </Card>
    </div>
  );
}
