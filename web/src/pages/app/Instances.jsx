import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api, daysLeft } from '../../lib/api.js';
import { Badge, Button, Card, Empty, Loader, Progress, useToast } from '../../components/ui.jsx';

const FILTERS = [
  { key: 'all', label: '全部' },
  { key: 'running', label: '运行中' },
  { key: 'stopped', label: '已关机' },
  { key: 'provisioning', label: '开通中' },
  { key: 'suspended', label: '已停机' }
];

export default function Instances() {
  const [list, setList] = useState(null);
  const [filter, setFilter] = useState('all');
  const [busy, setBusy] = useState(null);
  const toast = useToast();
  const navigate = useNavigate();

  const load = () => api('/instances').then(setList).catch((e) => toast(e.message, 'error'));

  useEffect(() => {
    load();
    const timer = setInterval(load, 8000);
    return () => clearInterval(timer);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const power = async (e, vps, action) => {
    e.preventDefault();
    e.stopPropagation();
    setBusy(vps.id);
    try {
      await api(`/instances/${vps.id}/power`, { method: 'POST', body: { action } });
      toast(action === 'stop' ? `${vps.name} 已关机` : `${vps.name} 已${action === 'reboot' ? '重启' : '开机'}`);
      load();
    } catch (err) {
      toast(err.message, 'error');
    } finally {
      setBusy(null);
    }
  };

  const shown = (list || []).filter((v) => filter === 'all' || v.status === filter);

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">MY SERVERS</span>
          <h1>我的实例</h1>
          <p>共 {list?.length ?? 0} 台实例 · 支持开机 / 关机 / 重启 / 重装系统</p>
        </div>
        <Button onClick={() => navigate('/app/buy')}>开通新实例</Button>
      </div>

      <div className="tabs">
        {FILTERS.map((f) => (
          <button key={f.key} className={`tab ${filter === f.key ? 'active' : ''}`} onClick={() => setFilter(f.key)}>
            {f.label}{f.key !== 'all' && ` (${(list || []).filter((v) => v.status === f.key).length})`}
          </button>
        ))}
      </div>

      {!list ? (
        <Loader label="正在同步实例状态" />
      ) : shown.length === 0 ? (
        <Card><Empty title="没有符合条件的实例" desc="换个筛选条件，或开通一台新的服务器。" action={<Button size="sm" onClick={() => navigate('/app/buy')}>去购买</Button>} /></Card>
      ) : (
        <div className="grid grid--3">
          {shown.map((vps) => {
            const left = Math.max(0, daysLeft(vps.expiresAt));
            return (
              <div key={vps.id} className="vps-card" onClick={() => navigate(`/app/vps/${vps.id}`)} style={{ cursor: 'pointer' }}>
                <div className="vps-head">
                  <div>
                    <h3>{vps.name}</h3>
                    <span>{vps.planName} · {vps.nodeName}</span>
                  </div>
                  <Badge status={vps.status} />
                </div>
                <div className="vps-specs">
                  <div><span>CPU</span><b>{vps.cpu} 核</b></div>
                  <div><span>内存</span><b>{vps.memoryMb / 1024}G</b></div>
                  <div><span>硬盘</span><b>{vps.diskGb}G</b></div>
                  <div><span>带宽</span><b>{vps.bandwidthGb}G</b></div>
                </div>
                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11.5, marginBottom: 5 }}>
                    <span className="muted">流量使用</span>
                    <span className="num">{vps.trafficUsedGb} / {vps.bandwidthGb} GB</span>
                  </div>
                  <Progress value={(vps.trafficUsedGb / Math.max(1, vps.bandwidthGb)) * 100} tone={vps.trafficUsedGb / vps.bandwidthGb > 0.8 ? 'warn' : ''} />
                </div>
                <div className="vps-foot">
                  <span className="vps-ip">{vps.ip || 'IP 分配中…'}</span>
                  <span className={`small ${left < 5 ? '' : 'muted'}`} style={{ color: left < 5 ? 'var(--amber)' : undefined }}>
                    {left} 天后到期
                  </span>
                </div>
                <div className="btn-row" style={{ gap: 8 }} onClick={(e) => e.stopPropagation()}>
                  {vps.status === 'running' && (
                    <>
                      <Button size="sm" variant="ghost" disabled={busy === vps.id} onClick={(e) => power(e, vps, 'reboot')}>重启</Button>
                      <Button size="sm" variant="ghost" disabled={busy === vps.id} onClick={(e) => power(e, vps, 'stop')}>关机</Button>
                    </>
                  )}
                  {vps.status === 'stopped' && (
                    <Button size="sm" variant="soft" disabled={busy === vps.id} onClick={(e) => power(e, vps, 'start')}>开机</Button>
                  )}
                  {vps.status === 'suspended' && (
                    <Link to={`/app/vps/${vps.id}`} className="btn btn--soft btn--sm" onClick={(e) => e.stopPropagation()}>立即续费</Link>
                  )}
                  <span className="small muted" style={{ marginLeft: 'auto' }}>
                    {vps.status === 'provisioning' ? '自动部署中…' : ''}
                  </span>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
