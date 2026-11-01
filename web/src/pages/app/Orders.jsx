import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api, datetime, money } from '../../lib/api.js';
import { useAuth } from '../../context/AuthContext.jsx';
import { Badge, Button, Card, Empty, Loader, Modal, useToast } from '../../components/ui.jsx';

const FILTERS = [
  { key: 'all', label: '全部' },
  { key: 'pending', label: '待支付' },
  { key: 'paid', label: '已支付' },
  { key: 'cancelled', label: '已取消' }
];

export default function Orders() {
  const [orders, setOrders] = useState(null);
  const [filter, setFilter] = useState('all');
  const [paying, setPaying] = useState(null);
  const [method, setMethod] = useState('wallet');
  const [busy, setBusy] = useState(false);
  const toast = useToast();
  const navigate = useNavigate();
  const { user, refresh } = useAuth();

  const load = () => api('/orders').then(setOrders).catch((e) => toast(e.message, 'error'));

  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const pay = async () => {
    setBusy(true);
    try {
      const res = await api(`/orders/${paying.id}/pay`, { method: 'POST', body: { method } });
      await refresh();
      setPaying(null);
      toast('支付成功，实例正在开通');
      load();
      if (res.instance) navigate(`/app/vps/${res.instance.id}`);
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  const cancel = async (order) => {
    try {
      await api(`/orders/${order.id}/cancel`, { method: 'POST' });
      toast('订单已取消');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const shown = (orders || []).filter((o) => filter === 'all' || o.status === filter);

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">ORDERS</span>
          <h1>订单管理</h1>
          <p>查看支付状态、补付待支付订单，或重新下单。</p>
        </div>
        <Button onClick={() => navigate('/app/buy')}>新建订单</Button>
      </div>

      <div className="tabs">
        {FILTERS.map((f) => (
          <button key={f.key} className={`tab ${filter === f.key ? 'active' : ''}`} onClick={() => setFilter(f.key)}>
            {f.label}{f.key !== 'all' && ` (${(orders || []).filter((o) => o.status === f.key).length})`}
          </button>
        ))}
      </div>

      {!orders ? (
        <Loader label="读取订单" />
      ) : shown.length === 0 ? (
        <Card><Empty title="暂无订单" desc="开通一台服务器，订单会自动出现在这里。" action={<Button size="sm" onClick={() => navigate('/app/buy')}>去开通</Button>} /></Card>
      ) : (
        <Card>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>订单号</th><th>套餐 / 节点</th><th>配置</th><th>周期</th><th>金额</th><th>状态</th><th>创建时间</th><th className="right">操作</th>
                </tr>
              </thead>
              <tbody>
                {shown.map((o) => (
                  <tr key={o.id}>
                    <td className="mono">{o.orderNo}</td>
                    <td>{o.planName}<br /><span className="small muted">{o.nodeName}</span></td>
                    <td className="small muted">{o.instanceName}<br />{o.os}</td>
                    <td className="num">{o.months} 个月</td>
                    <td className="num">{money(o.amount)}</td>
                    <td><Badge status={o.status} /></td>
                    <td className="small muted">{datetime(o.createdAt)}</td>
                    <td className="right">
                      {o.status === 'pending' ? (
                        <div className="cell-actions">
                          <Button size="sm" variant="ghost" onClick={() => cancel(o)}>取消</Button>
                          <Button size="sm" onClick={() => { setPaying(o); setMethod('wallet'); }}>去支付</Button>
                        </div>
                      ) : o.status === 'paid' ? (
                        <Link to="/app/vps" className="small">实例 →</Link>
                      ) : (
                        <span className="small muted">—</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      <Modal open={!!paying} onClose={() => setPaying(null)} title="支付订单"
        footer={<><Button variant="ghost" onClick={() => setPaying(null)}>取消</Button><Button onClick={pay} disabled={busy}>{busy ? '处理中…' : `支付 ${money(paying?.amount)}`}</Button></>}>
        {paying && (
          <>
            <div className="kv"><span>订单号</span><b className="mono">{paying.orderNo}</b></div>
            <div className="kv"><span>套餐</span><b>{paying.planName} · {paying.nodeName}</b></div>
            <div className="kv"><span>应付金额</span><b className="num">{money(paying.amount)}</b></div>
            <div className="kv"><span>当前余额</span><b className="num">{money(user?.balance)}</b></div>
            <Field label="支付方式" className="field" >
              <select className="input select" value={method} onChange={(e) => setMethod(e.target.value)}>
                <option value="wallet">余额支付（实时开通）</option>
                <option value="alipay">支付宝（模拟）</option>
                <option value="wechat">微信支付（模拟）</option>
              </select>
            </Field>
            {method === 'wallet' && user && user.balance < paying.amount && (
              <p className="form-error">余额不足，可先充值或改用模拟支付。</p>
            )}
          </>
        )}
      </Modal>
    </div>
  );
}
