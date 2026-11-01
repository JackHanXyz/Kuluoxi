import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { api, date, datetime, daysLeft, money } from '../../lib/api.js';
import { Badge, Button, Card, Copy, Empty, Field, Loader, Modal, Progress, Select, useToast } from '../../components/ui.jsx';
import { useAuth } from '../../context/AuthContext.jsx';

const OS_LIST = ['Ubuntu 24.04 LTS', 'Ubuntu 22.04 LTS', 'Debian 12', 'AlmaLinux 9', 'Rocky Linux 9', 'CentOS Stream 9', 'Windows Server 2022'];
const CYCLE = [1, 3, 6, 12];

export default function InstanceDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const { refresh, user } = useAuth();
  const [vps, setVps] = useState(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState('');
  const [reinstall, setReinstall] = useState({ open: false, os: '' });
  const [renew, setRenew] = useState({ open: false, months: 1 });
  const [rename, setRename] = useState({ open: false, name: '' });
  const [confirmDestroy, setConfirmDestroy] = useState(false);

  const load = () =>
    api(`/instances/${id}`)
      .then((d) => {
        setVps(d);
        setRename((r) => ({ ...r, name: d.name }));
        return d;
      })
      .catch((e) => setError(e.message));

  useEffect(() => {
    load();
    const timer = setInterval(load, 5000);
    return () => clearInterval(timer);
  }, [id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (error) return <div className="page"><Card><div className="card-body"><Empty title="找不到该实例" desc={error} action={<Button size="sm" onClick={() => navigate('/app/vps')}>返回列表</Button>} /></div></Card></div>;
  if (!vps) return <div className="page"><Loader label="读取实例信息" /></div>;

  const power = async (action) => {
    setBusy(action);
    try {
      await api(`/instances/${id}/power`, { method: 'POST', body: { action } });
      toast(`实例已${action === 'stop' ? '关机' : action === 'reboot' ? '重启' : '开机'}`);
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy('');
    }
  };

  const doReinstall = async () => {
    if (!reinstall.os) return toast('请选择系统镜像', 'error');
    setBusy('reinstall');
    try {
      await api(`/instances/${id}/reinstall`, { method: 'POST', body: { os: reinstall.os } });
      toast('重装任务已下发，将重置 root 密码');
      setReinstall({ open: false, os: '' });
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy('');
    }
  };

  const doRenew = async () => {
    setBusy('renew');
    try {
      const res = await api(`/instances/${id}/renew`, { method: 'POST', body: { months: renew.months } });
      toast('续费成功');
      setRenew({ open: false, months: 1 });
      await refresh();
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy('');
    }
  };

  const doRename = async () => {
    setBusy('rename');
    try {
      await api(`/instances/${id}`, { method: 'PATCH', body: { name: rename.name } });
      toast('实例名称已更新');
      setRename((r) => ({ ...r, open: false }));
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy('');
    }
  };

  const doDestroy = async () => {
    setBusy('destroy');
    try {
      await api(`/instances/${id}`, { method: 'DELETE' });
      toast('实例已销毁');
      navigate('/app/vps');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy('');
    }
  };

  const left = Math.max(0, daysLeft(vps.expiresAt));

  return (
    <div className="page">
      <div className="page-head">
        <div className="detail-hero">
          <div className="avatar">{vps.name.slice(0, 1).toUpperCase()}</div>
          <div>
            <h1 style={{ fontSize: 24 }}>{vps.name}</h1>
            <div style={{ display: 'flex', gap: 10, alignItems: 'center', marginTop: 6, flexWrap: 'wrap' }}>
              <Badge status={vps.status} />
              <span className="small muted">{vps.planName} · {vps.nodeName}（{vps.nodeCode}）</span>
            </div>
          </div>
        </div>
        <div className="btn-row">
          <Link to="/app/vps" className="btn btn--ghost btn--sm">返回列表</Link>
          <Button variant="ghost" size="sm" onClick={() => setRename((r) => ({ ...r, open: true }))}>重命名</Button>
        </div>
      </div>

      <div className="metric-strip">
        <div><span>CPU</span><b>{vps.cpu} 核</b></div>
        <div><span>内存</span><b>{vps.memoryMb / 1024} GB</b></div>
        <div><span>NVMe 硬盘</span><b>{vps.diskGb} GB</b></div>
        <div><span>月流量</span><b>{vps.bandwidthGb} GB</b></div>
      </div>

      <div className="grid grid--2" style={{ marginTop: 20, alignItems: 'start' }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Card>
            <div className="card-head"><h3>连接信息</h3><span className="small muted">仅本人可见</span></div>
            <div className="card-body">
              <div className="kv"><span>公网 IP</span><b className="mono">{vps.ip || '分配中'}{vps.ip && <Copy text={vps.ip} />}</b></div>
              <div className="kv"><span>SSH 端口</span><b className="mono">22</b></div>
              <div className="kv"><span>root 密码</span><b className="mono">{vps.rootPassword || '等待部署'}{vps.rootPassword && <Copy text={vps.rootPassword} />}</b></div>
              <div className="kv"><span>操作系统</span><b>{vps.os}</b></div>
              <div className="kv"><span>开通时间</span><b>{date(vps.createdAt)}</b></div>
              <div className="kv"><span>到期时间</span><b>{date(vps.expiresAt)}（剩余 {left} 天）</b></div>
            </div>
            <div className="card-body" style={{ borderTop: '1px solid var(--line)', paddingTop: 16 }}>
              <div className="small muted" style={{ marginBottom: 8 }}>本月流量</div>
              <Progress value={(vps.trafficUsedGb / Math.max(1, vps.bandwidthGb)) * 100} tone={vps.trafficUsedGb / vps.bandwidthGb > 0.8 ? 'warn' : ''} />
              <div className="small muted" style={{ marginTop: 6 }}>{vps.trafficUsedGb} GB / {vps.bandwidthGb} GB</div>
            </div>
          </Card>

          <Card>
            <div className="card-head"><h3>价格与续费</h3></div>
            <div className="card-body">
              <p className="small muted">续费将在到期时间基础上顺延，余额充足即可即时生效。</p>
              <div className="btn-row" style={{ marginTop: 16 }}>
                <Button size="sm" onClick={() => setRenew({ open: true, months: 1 })}>续费实例</Button>
                <Button size="sm" variant="ghost" onClick={() => { setRenew({ open: true, months: 1 }); }}>查看账单</Button>
              </div>
              <p className="small muted" style={{ marginTop: 14 }}>当前余额 {money(user?.balance)}</p>
            </div>
          </Card>
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Card>
            <div className="card-head"><h3>电源与系统</h3></div>
            <div className="card-body">
              <div className="btn-row">
                <Button size="sm" variant="soft" disabled={vps.status !== 'stopped' || busy} onClick={() => power('start')}>开机</Button>
                <Button size="sm" variant="ghost" disabled={vps.status !== 'running' || busy} onClick={() => power('reboot')}>重启</Button>
                <Button size="sm" variant="ghost" disabled={vps.status !== 'running' || busy} onClick={() => power('stop')}>关机</Button>
                <Button size="sm" variant="ghost" onClick={() => setReinstall({ open: true, os: vps.os })}>重装系统</Button>
              </div>
              <p className="small muted" style={{ marginTop: 14 }}>关机/重启为模拟操作；重装将重置实例系统与 root 密码。</p>
            </div>
          </Card>

          <Card>
            <div className="card-head"><h3>操作记录</h3></div>
            <div className="card-body">
              <ul className="timeline">
                <li><span className="t-dot" /><div className="t-body"><b>实例创建</b>{datetime(vps.createdAt)} · 由订单自动开通</div></li>
                {vps.events?.map((ev) => (
                  <li key={ev.id}><span className="t-dot" /><div className="t-body"><b>{ev.type === 'payment' ? '扣费' : ev.type === 'recharge' ? '充值' : '退款'}</b>{ev.remark} · {datetime(ev.createdAt)}</div></li>
                ))}
              </ul>
            </div>
          </Card>

          <Card>
            <div className="card-head"><h3>危险操作</h3></div>
            <div className="card-body">
              <p className="small muted">销毁后实例数据不可恢复，且未到期费用不予退还。</p>
              <div className="btn-row" style={{ marginTop: 14 }}>
                <Button size="sm" variant="danger" onClick={() => setConfirmDestroy(true)}>销毁实例</Button>
              </div>
            </div>
          </Card>
        </div>
      </div>

      <Modal open={reinstall.open} onClose={() => setReinstall({ open: false, os: '' })} title="重装操作系统"
        footer={<><Button variant="ghost" onClick={() => setReinstall({ open: false, os: '' })}>取消</Button><Button onClick={doReinstall} disabled={busy === 'reinstall'}>开始重装</Button></>}>
        <p className="small muted" style={{ marginBottom: 16 }}>重装会清空磁盘数据并重新生成 root 密码，请提前备份。</p>
        <Field label="选择镜像">{OS_LIST.map((os) => (
          <label key={os} style={{ display: 'flex', gap: 10, alignItems: 'center', padding: '9px 0', fontSize: 13 }}>
            <input type="radio" name="os" checked={reinstall.os === os} onChange={() => setReinstall((r) => ({ ...r, os }))} />
            {os}
          </label>
        ))}</Field>
      </Modal>

      <Modal open={renew.open} onClose={() => setRenew({ open: false, months: 1 })} title="续费实例"
        footer={<><Button variant="ghost" onClick={() => setRenew({ open: false, months: 1 })}>取消</Button><Button onClick={doRenew} disabled={busy === 'renew'}>确认续费</Button></>}>
        <Field label="续费周期">
          <div className="cycle-row">
            {CYCLE.map((m) => (
              <button key={m} className={`cycle ${renew.months === m ? 'active' : ''}`} onClick={() => setRenew({ open: true, months: m })}>{m} 个月</button>
            ))}
          </div>
        </Field>
        <p className="small muted">从 {date(vps.expiresAt)} 起顺延 {renew.months * 30} 天，余额 {money(user?.balance)}。</p>
      </Modal>

      <Modal open={rename.open} onClose={() => setRename((r) => ({ ...r, open: false }))} title="重命名实例"
        footer={<><Button variant="ghost" onClick={() => setRename((r) => ({ ...r, open: false }))}>取消</Button><Button onClick={doRename} disabled={busy === 'rename'}>保存</Button></>}>
        <Field label="实例名称" hint="3-32 位字母、数字或短横线"><input className="input" value={rename.name} onChange={(e) => setRename({ ...rename, name: e.target.value })} /></Field>
      </Modal>

      <Modal open={confirmDestroy} onClose={() => setConfirmDestroy(false)} title="确认销毁实例"
        footer={<><Button variant="ghost" onClick={() => setConfirmDestroy(false)}>再想想</Button><Button variant="danger" onClick={doDestroy} disabled={busy === 'destroy'}>确认销毁</Button></>}>
        <p className="small">即将销毁 <b>{vps.name}</b>，此操作不可撤销。请确认已备份全部数据。</p>
      </Modal>
    </div>
  );
}
