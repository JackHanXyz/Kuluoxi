import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, ago, datetime, money } from '../../lib/api.js';
import { Badge, Card, Loader, Progress, Stat } from '../../components/ui.jsx';

export default function AdminOverview() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    api('/admin/overview').then(setData).catch((e) => setError(e.message));
  }, []);

  if (error) return <div className="page"><Card><div className="card-body muted">{error}</div></Card></div>;
  if (!data) return <div className="page"><Loader label="汇总运营数据" /></div>;

  const maxRevenue = Math.max(1, ...data.trend.map((t) => t.revenue));
  const maxPlan = Math.max(1, ...data.planSales.map((p) => p.revenue));

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">OPERATIONS</span>
          <h1>运营概览</h1>
          <p>营收、活跃实例与节点负载概览，数据实时聚合自订单与实例表。</p>
        </div>
        <div className="btn-row">
          <Link to="/admin/orders" className="btn btn--ghost btn--sm">订单中心</Link>
          <Link to="/admin/tickets" className="btn btn--primary btn--sm">处理工单 ({data.openTickets})</Link>
        </div>
      </div>

      <div className="grid grid--4" style={{ marginBottom: 12 }}>
        <Stat label="累计营收" value={money(data.revenue)} hint="含全部已支付订单" tone="amber" />
        <Stat label="近 30 天营收" value={money(data.monthRevenue)} hint="环比持续增长" tone="accent" />
        <Stat label="注册用户" value={data.users} hint="不含管理员" tone="default" />
        <Stat label="活跃实例" value={data.activeInstances} hint={`${data.provisioning} 台部署中`} tone="violet" />
      </div>
      <div className="grid grid--4" style={{ marginBottom: 20 }}>
        <Stat label="待支付订单" value={data.pendingOrders} hint="可手动关闭" tone={data.pendingOrders ? 'amber' : 'default'} />
        <Stat label="待处理工单" value={data.openTickets} hint="7×24 响应承诺" tone={data.openTickets ? 'danger' : 'default'} />
        <Stat label="部署中实例" value={data.provisioning} hint="自动完成" tone="default" />
        <Stat label="节点在线率" value={`${Math.round((data.nodes.filter((n) => n.status === 'online').length / Math.max(1, data.nodes.length)) * 100)}%`} hint={`${data.nodes.length} 个节点`} tone="accent" />
      </div>

      <div className="grid grid--2" style={{ marginBottom: 16, alignItems: 'stretch' }}>
        <Card>
          <div className="card-head"><h3>近 14 天营收</h3><span className="small muted">单位：元</span></div>
          <div className="card-body">
            <div className="chart">
              {data.trend.map((t) => (
                <div key={t.date} className="chart-bar">
                  <em>{money(t.revenue)}</em>
                  <i style={{ height: `${Math.max(3, (t.revenue / maxRevenue) * 100)}%` }} />
                  <span>{t.date.slice(5)}</span>
                </div>
              ))}
            </div>
          </div>
        </Card>

        <Card>
          <div className="card-head"><h3>套餐销售分布</h3><span className="small muted">按营收</span></div>
          <div className="card-body bar-list">
            {data.planSales.map((p) => (
              <div key={p.code} className="bar-row">
                <span>{p.name}</span>
                <Progress value={(p.revenue / maxPlan) * 100} />
                <b>{p.count} 单</b>
              </div>
            ))}
          </div>
        </Card>
      </div>

      <div className="grid grid--2" style={{ alignItems: 'start' }}>
        <Card>
          <div className="card-head"><h3>节点负载</h3><Link to="/admin/nodes" className="small muted">管理 →</Link></div>
          <div className="card-body bar-list">
            {data.nodes.map((n) => (
              <div key={n.id} className="bar-row" style={{ gridTemplateColumns: '130px 1fr 90px' }}>
                <span>{n.name}</span>
                <Progress value={n.load} tone={n.load > 80 ? 'danger' : n.load > 60 ? 'warn' : ''} />
                <b style={{ color: n.status === 'online' ? undefined : 'var(--amber)' }}>{n.status === 'online' ? `${n.used}/${n.capacity}` : '维护'}</b>
              </div>
            ))}
          </div>
        </Card>

        <Card>
          <div className="card-head"><h3>最新工单</h3><Link to="/admin/tickets" className="small muted">全部 →</Link></div>
          <div className="ticket-list">
            {data.recentTickets.length === 0 ? <div className="card-body small muted">暂无工单</div> : data.recentTickets.map((t) => (
              <Link key={t.id} to="/admin/tickets" className="ticket-row">
                <div className="tr-main">
                  <strong>#{String(t.id).padStart(5, '0')} · {t.subject}</strong>
                  <span>{t.userNickname} · {ago(t.updatedAt)}</span>
                </div>
                <Badge status={t.status} />
              </Link>
            ))}
          </div>
        </Card>
      </div>

      <Card style={{ marginTop: 16 }}>
        <div className="card-head"><h3>最新订单</h3><Link to="/admin/orders" className="small muted">全部订单 →</Link></div>
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr><th>订单号</th><th>用户</th><th>套餐 / 节点</th><th>周期</th><th>金额</th><th>状态</th><th className="right">时间</th></tr>
            </thead>
            <tbody>
              {data.recentOrders.map((o) => (
                <tr key={o.id}>
                  <td className="mono">{o.orderNo}</td>
                  <td>{o.nickname}</td>
                  <td>{o.planName}<br /><span className="small muted">{o.nodeName}</span></td>
                  <td className="num">{o.months} 个月</td>
                  <td className="num">{money(o.amount)}</td>
                  <td><Badge status={o.status} /></td>
                  <td className="right small muted">{datetime(o.createdAt)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
