import { useEffect, useState } from 'react';
import { api, datetime, money } from '../../lib/api.js';
import { Badge, Button, Card, Empty, Loader, useToast } from '../../components/ui.jsx';

const FILTERS = [
  { key: '', label: '全部' },
  { key: 'pending', label: '待支付' },
  { key: 'paid', label: '已支付' },
  { key: 'cancelled', label: '已取消' }
];

export default function AdminOrders() {
  const [orders, setOrders] = useState(null);
  const [status, setStatus] = useState('');
  const toast = useToast();

  const load = () => api(`/admin/orders${status ? `?status=${status}` : ''}`).then(setOrders).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(); }, [status]); // eslint-disable-line react-hooks/exhaustive-deps

  const close = async (o) => {
    if (!confirm(`确认关闭订单 ${o.orderNo}？`)) return;
    try {
      await api(`/admin/orders/${o.id}/close`, { method: 'POST' });
      toast('订单已关闭');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">ORDERS</span>
          <h1>订单管理</h1>
          <p>全量订单流水，可关闭长期未支付的挂单。</p>
        </div>
        <span className="pill-tag">{orders?.length ?? 0} 条记录</span>
      </div>

      <div className="tabs">
        {FILTERS.map((f) => (
          <button key={f.key} className={`tab ${status === f.key ? 'active' : ''}`} onClick={() => setStatus(f.key)}>{f.label}</button>
        ))}
      </div>

      {!orders ? <Loader label="读取订单" /> : orders.length === 0 ? <Card><Empty title="没有订单" /></Card> : (
        <Card>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr><th>订单号</th><th>用户</th><th>套餐 / 节点</th><th>实例</th><th>金额</th><th>支付方式</th><th>状态</th><th>创建时间</th><th className="right">操作</th></tr>
              </thead>
              <tbody>
                {orders.map((o) => (
                  <tr key={o.id}>
                    <td className="mono">{o.orderNo}</td>
                    <td>{o.nickname}<br /><span className="small muted">UID {o.userId}</span></td>
                    <td>{o.planName}<br /><span className="small muted">{o.nodeName} · {o.months} 个月</span></td>
                    <td className="small muted">{o.instanceName}<br />{o.os}</td>
                    <td className="num">{money(o.amount)}</td>
                    <td><span className="pill-tag">{o.paymentMethod || '未支付'}</span></td>
                    <td><Badge status={o.status} /></td>
                    <td className="small muted">{datetime(o.createdAt)}</td>
                    <td className="right">
                      {o.status === 'pending' ? <Button size="sm" variant="ghost" onClick={() => close(o)}>关闭订单</Button> : <span className="small muted">—</span>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}
    </div>
  );
}
