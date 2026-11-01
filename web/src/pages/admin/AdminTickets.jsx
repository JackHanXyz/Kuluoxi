import { useEffect, useState } from 'react';
import { ago, api, datetime } from '../../lib/api.js';
import { Badge, Button, Card, Empty, Loader, Modal, Textarea, useToast } from '../../components/ui.jsx';

const CATEGORY = { technical: '技术支持', network: '网络问题', billing: '账单支付', feature: '功能建议', other: '其他' };
const FILTERS = [
  { key: '', label: '全部' },
  { key: 'open', label: '待处理' },
  { key: 'answered', label: '已回复' },
  { key: 'closed', label: '已关闭' }
];

export default function AdminTickets() {
  const [tickets, setTickets] = useState(null);
  const [filter, setFilter] = useState('');
  const [detail, setDetail] = useState(null);
  const [reply, setReply] = useState('');
  const [busy, setBusy] = useState(false);
  const toast = useToast();

  const load = () => api(`/admin/tickets${filter ? `?status=${filter}` : ''}`).then(setTickets).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(); }, [filter]); // eslint-disable-line react-hooks/exhaustive-deps

  const openDetail = async (t) => {
    try {
      const data = await api(`/admin/tickets/${t.id}`);
      setDetail(data);
      setReply('');
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const send = async () => {
    if (reply.trim().length < 1) return toast('请输入回复内容', 'error');
    setBusy(true);
    try {
      await api(`/admin/tickets/${detail.id}/reply`, { method: 'POST', body: { body: reply } });
      const data = await api(`/admin/tickets/${detail.id}`);
      setDetail(data);
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
      await api(`/admin/tickets/${detail.id}/close`, { method: 'POST' });
      setDetail(null);
      toast('工单已关闭');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">SUPPORT DESK</span>
          <h1>工单处理</h1>
          <p>回复客户工单，回复后状态自动变为「已回复」。</p>
        </div>
        <span className="pill-tag">{tickets?.length ?? 0} 条工单</span>
      </div>

      <div className="tabs">
        {FILTERS.map((f) => (
          <button key={f.key} className={`tab ${filter === f.key ? 'active' : ''}`} onClick={() => setFilter(f.key)}>{f.label}</button>
        ))}
      </div>

      {!tickets ? <Loader label="读取工单" /> : tickets.length === 0 ? <Card><Empty title="没有工单" /></Card> : (
        <Card>
          <div className="ticket-list">
            {tickets.map((t) => (
              <div key={t.id} className="ticket-row" style={{ cursor: 'pointer' }} onClick={() => openDetail(t)}>
                <div className="tr-main">
                  <strong>#{String(t.id).padStart(5, '0')} · {t.subject}</strong>
                  <span>{t.userNickname} · {CATEGORY[t.category] || t.category} · {t.messageCount} 条对话 · {ago(t.updatedAt)}</span>
                </div>
                <Badge status={t.status} />
              </div>
            ))}
          </div>
        </Card>
      )}

      <Modal open={!!detail} onClose={() => setDetail(null)} title={detail ? `工单 #${String(detail.id).padStart(5, '0')}` : ''} width={720}>
        {detail && (
          <>
            <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, alignItems: 'center', marginBottom: 16 }}>
              <div>
                <strong style={{ fontSize: 15 }}>{detail.subject}</strong>
                <p className="small muted" style={{ marginTop: 4 }}>{detail.userNickname} · {CATEGORY[detail.category]} · 创建于 {datetime(detail.createdAt)}</p>
              </div>
              <Badge status={detail.status} />
            </div>
            <div className="thread" style={{ padding: 0, maxHeight: 380, overflowY: 'auto' }}>
              {detail.messages.map((m) => (
                <div key={m.id} className={`msg ${m.isStaff ? 'msg--staff' : ''}`}>
                  <div className="avatar" style={{ width: 30, height: 30, fontSize: 11 }}>{m.isStaff ? '汐' : m.nickname?.slice(0, 1)}</div>
                  <div>
                    <div className="msg-body">{m.body}</div>
                    <div className="msg-meta">{m.nickname}{m.isStaff ? ' · 官方' : ''} · {datetime(m.createdAt)}</div>
                  </div>
                </div>
              ))}
            </div>
            {detail.status !== 'closed' ? (
              <>
                <Textarea style={{ marginTop: 18, minHeight: 90 }} placeholder="以官方身份回复…" value={reply} onChange={(e) => setReply(e.target.value)} />
                <div className="btn-row" style={{ marginTop: 12, justifyContent: 'flex-end' }}>
                  <Button variant="ghost" size="sm" onClick={close}>关闭工单</Button>
                  <Button size="sm" onClick={send} disabled={busy}>{busy ? '发送中…' : '发送回复'}</Button>
                </div>
              </>
            ) : (
              <p className="small muted" style={{ marginTop: 16 }}>该工单已关闭。</p>
            )}
          </>
        )}
      </Modal>
    </div>
  );
}
