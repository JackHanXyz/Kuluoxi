import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api, money, date, datetime, daysLeft, STATUS_TEXT } from '../../lib/api.js';
import { useAuth } from '../../context/AuthContext.jsx';
import { Badge, Button, Card, Empty, Loader, Progress, Stat } from '../../components/ui.jsx';

export default function Overview() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    api('/overview')
      .then(setData)
      .catch((e) => setError(e.message));
  }, []);

  if (error) return <div className="page"><Card><div className="card-body muted">{error}</div></Card></div>;
  if (!data) return <div className="page"><Loader label="正在载入控制台" /></div>;

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">DASHBOARD</span>
          <h1>晚上好，{user?.nickname}</h1>
          <p>你的深海算力已就绪。这里是最新的账户与实例动态。</p>
        </div>
        <div className="btn-row">
          <Button variant="ghost" onClick={() => navigate('/app/tickets')}>提交工单</Button>
          <Button onClick={() => navigate('/app/buy')}>开通新实例</Button>
        </div>
      </div>

      <div className="grid grid--4" style={{ marginBottom: 20 }}>
        <Stat label="账户余额" value={money(data.balance)} hint="用于自动续费与购买" tone="amber" />
        <Stat label="运行中实例" value={data.runningCount} hint={`共 ${data.instanceCount} 台实例`} tone="accent" />
        <Stat label="待支付订单" value={data.pendingOrders} hint="待完成付款" tone="violet" />
        <Stat label="进行中工单" value={data.openTickets} hint="支持团队 7×24 响应" tone={data.openTickets ? 'danger' : 'default'} />
      </div>

      {data.announcements.length > 0 && (
        <Card style={{ marginBottom: 20 }}>
          <div className="card-head">
            <h3>公告中心</h3>
            <Link to="/app/settings" className="small muted">全部公告 →</Link>
          </div>
          <div className="card-body" style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            {data.announcements.map((a) => (
              <div key={a.id} style={{ display: 'flex', gap: 14 }}>
                <span className={`pill-tag ${a.level === 'warning' ? 'pill-tag--amber' : ''}`}>
                  {a.level === 'warning' ? '维护' : '通知'}
                </span>
                <div>
                  <strong style={{ fontSize: 13.5, fontWeight: 500 }}>{a.title}</strong>
                  <p className="small muted" style={{ marginTop: 3 }}>{a.body}</p>
                </div>
                <span className="small muted" style={{ marginLeft: 'auto', whiteSpace: 'nowrap' }}>{date(a.createdAt)}</span>
              </div>
            ))}
          </div>
        </Card>
      )}

      <div className="grid grid--2">
        <Card>
          <div className="card-head">
            <h3>我的实例</h3>
            <Link to="/app/vps" className="small muted">管理全部 →</Link>
          </div>
          <div className="card-body" style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {data.instances.length === 0 ? (
              <Empty title="还没有实例" desc="选择套餐与节点，6 秒内自动开通。" action={<Button size="sm" onClick={() => navigate('/app/buy')}>立即购买</Button>} />
            ) : (
              data.instances.map((vps) => (
                <Link key={vps.id} to={`/app/vps/${vps.id}`} className="vps-card">
                  <div className="vps-head">
                    <div>
                      <h3>{vps.name}</h3>
                      <span>{vps.planName} · {vps.nodeName}</span>
                    </div>
                    <Badge status={vps.status} />
                  </div>
                  <div className="vps-specs">
                    <div><span>CPU</span><b>{vps.cpu} 核</b></div>
                    <div><span>内存</span><b>{vps.memoryMb / 1024} GB</b></div>
                    <div><span>硬盘</span><b>{vps.diskGb} GB</b></div>
                    <div><span>流量</span><b>{vps.trafficUsedGb} GB</b></div>
                  </div>
                  <div className="vps-foot">
                    <span className="vps-ip">{vps.ip || '分配中…'}</span>
                    <span className="small muted">剩余 {Math.max(0, daysLeft(vps.expiresAt))} 天</span>
                  </div>
                </Link>
              ))
            )}
          </div>
        </Card>

        <Card>
          <div className="card-head">
            <h3>最近订单</h3>
            <Link to="/app/orders" className="small muted">订单列表 →</Link>
          </div>
          {data.recentOrders.length === 0 ? (
            <Empty title="暂无订单" />
          ) : (
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr><th>订单号</th><th>套餐</th><th>金额</th><th>状态</th><th className="right">时间</th></tr>
                </thead>
                <tbody>
                  {data.recentOrders.map((o) => (
                    <tr key={o.id}>
                      <td className="mono">{o.orderNo}</td>
                      <td>{o.planName}<br /><span className="small muted">{o.nodeName}</span></td>
                      <td className="num">{money(o.amount)}</td>
                      <td><Badge status={o.status} /></td>
                      <td className="right small muted">{datetime(o.createdAt)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <div className="card-body" style={{ paddingTop: 16 }}>
            <div className="small muted" style={{ marginBottom: 8 }}>流量用量（本月）</div>
            {data.instances.slice(0, 3).map((vps) => (
              <div key={vps.id} style={{ marginBottom: 12 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, marginBottom: 5 }}>
                  <span>{vps.name}</span>
                  <span className="muted num">{vps.trafficUsedGb} / {vps.bandwidthGb} GB</span>
                </div>
                <Progress value={(vps.trafficUsedGb / Math.max(1, vps.bandwidthGb)) * 100} tone={vps.trafficUsedGb / vps.bandwidthGb > 0.8 ? 'warn' : ''} />
              </div>
            ))}
          </div>
        </Card>
      </div>
      <p className="small muted" style={{ marginTop: 18 }}>提示：实例到期前 3 天会邮件提醒，余额充足可自动续期。</p>
    </div>
  );
}
