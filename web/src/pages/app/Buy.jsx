import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { api, money } from '../../lib/api.js';
import { useAuth } from '../../context/AuthContext.jsx';
import { Badge, Button, Card, Field, Loader, Modal, useToast } from '../../components/ui.jsx';

const CYCLES = [1, 3, 6, 12];
const OS_LIST = ['Ubuntu 24.04 LTS', 'Ubuntu 22.04 LTS', 'Debian 12', 'AlmaLinux 9', 'Rocky Linux 9', 'CentOS Stream 9', 'Windows Server 2022'];
const METHODS = [
  { key: 'wallet', label: '余额支付', desc: '实时到账，立即开通' },
  { key: 'alipay', label: '支付宝（模拟）', desc: '演示环境直接成功' },
  { key: 'wechat', label: '微信支付（模拟）', desc: '演示环境直接成功' }
];

export default function Buy() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const toast = useToast();
  const { user, refresh } = useAuth();
  const [plans, setPlans] = useState(null);
  const [nodes, setNodes] = useState([]);
  const [planId, setPlanId] = useState(Number(params.get('plan')) || null);
  const [nodeId, setNodeId] = useState(null);
  const [months, setMonths] = useState(1);
  const [os, setOs] = useState('Ubuntu 24.04 LTS');
  const [name, setName] = useState(`tide-${Math.random().toString(36).slice(2, 6)}`);
  const [method, setMethod] = useState('wallet');
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(null);

  useEffect(() => {
    Promise.all([api('/plans'), api('/nodes')])
      .then(([p, n]) => {
        setPlans(p);
        setNodes(n);
        const firstPlan = p.find((x) => x.id === Number(params.get('plan'))) || p.find((x) => x.highlight) || p[0];
        setPlanId(firstPlan?.id ?? null);
        const node = n.find((x) => x.status === 'online') || n[0];
        setNodeId(node?.id ?? null);
      })
      .catch((e) => toast(e.message, 'error'));
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const plan = useMemo(() => plans?.find((p) => p.id === planId), [plans, planId]);
  const node = useMemo(() => nodes.find((n) => n.id === nodeId), [nodes, nodeId]);
  const total = plan ? plan.price * months + plan.setupFee : 0;

  const submit = async () => {
    if (!plan) return toast('请选择套餐', 'error');
    if (!node) return toast('请选择节点', 'error');
    if (!/^[a-zA-Z0-9][a-zA-Z0-9-]{2,31}$/.test(name)) return toast('实例名称需 3-32 位字母、数字或短横线', 'error');
    if (method === 'wallet' && user.balance < total) {
      toast('余额不足，请先充值或选择其它支付方式', 'error');
      return;
    }
    setBusy(true);
    try {
      const order = await api('/orders', { method: 'POST', body: { planId: plan.id, nodeId: node.id, months, instanceName: name, os } });
      const res = await api(`/orders/${order.id}/pay`, { method: 'POST', body: { method } });
      await refresh();
      setDone({ instance: res.instance, order: res.order });
      toast('开通成功，正在部署系统');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  if (!plans) return <div className="page"><Loader label="载入售卖配置" /></div>;

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">DEPLOY</span>
          <h1>开通服务器</h1>
          <p>选择配置与节点，支付后 6 秒内自动部署，root 密码即时下发。</p>
        </div>
        <span className="balance-chip">余额 <b>{money(user?.balance)}</b></span>
      </div>

      <div className="buy-grid">
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Card>
            <div className="card-head"><h3>① 选择套餐</h3><span className="small muted">共 {plans.length} 款</span></div>
            <div className="card-body">
              <div className="plan-picker">
                {plans.map((p) => (
                  <button key={p.id} className={`plan-option ${planId === p.id ? 'active' : ''}`} onClick={() => setPlanId(p.id)}>
                    <div className="po-name"><b>{p.name}</b><em>{money(p.price)}/月</em></div>
                    <span className="po-spec">{p.cpu} 核 · {p.memoryMb / 1024}G 内存 · {p.diskGb}G NVMe</span>
                    <span className="po-spec">{p.portSpeed} 带宽 · {p.bandwidthGb ? `${p.bandwidthGb}G 流量` : '不限流量'}</span>
                  </button>
                ))}
              </div>
            </div>
          </Card>

          <Card>
            <div className="card-head"><h3>② 选择节点</h3><span className="small muted">全部为 NVMe 阵列</span></div>
            <div className="card-body">
              <div className="node-options">
                {nodes.map((n) => (
                  <button
                    key={n.id}
                    className={`node-option ${nodeId === n.id ? 'active' : ''} ${n.status !== 'online' ? 'disabled' : ''}`}
                    disabled={n.status !== 'online'}
                    onClick={() => setNodeId(n.id)}
                  >
                    <span style={{ fontSize: 16 }}>{n.flag}</span>
                    <div><b>{n.name}</b><br /><span className="small muted">{n.code.toUpperCase()} · 负载 {n.load}%</span></div>
                    {n.status !== 'online' && <small>维护</small>}
                  </button>
                ))}
              </div>
            </div>
          </Card>

          <Card>
            <div className="card-head"><h3>③ 实例配置</h3></div>
            <div className="card-body">
              <div className="form-grid">
                <Field label="实例名称" hint="3-32 位字母数字或短横线">
                  <input className="input" value={name} onChange={(e) => setName(e.target.value)} maxLength={32} />
                </Field>
                <Field label="操作系统">
                  <select className="input select" value={os} onChange={(e) => setOs(e.target.value)}>
                    {OS_LIST.map((o) => <option key={o} value={o}>{o}</option>)}
                  </select>
                </Field>
                <div className="full">
                  <Field label="购买时长">
                    <div className="cycle-row">
                      {CYCLES.map((m) => (
                        <button key={m} className={`cycle ${months === m ? 'active' : ''}`} onClick={() => setMonths(m)}>
                          {m} 个月{m === 3 ? '（季付）' : m === 6 ? '（半年）' : m === 12 ? '（年付）' : ''}
                        </button>
                      ))}
                    </div>
                  </Field>
                </div>
              </div>
              <Field label="支付方式">
                <div className="grid grid--3" style={{ gap: 10 }}>
                  {METHODS.map((m) => (
                    <button key={m.key} className={`plan-option ${method === m.key ? 'active' : ''}`} onClick={() => setMethod(m.key)} style={{ padding: '12px 14px' }}>
                      <b style={{ fontSize: 13 }}>{m.label}</b>
                      <span className="po-spec">{m.desc}</span>
                    </button>
                  ))}
                </div>
              </Field>
            </div>
          </Card>
        </div>

        <Card className="summary">
          <div className="card-head" style={{ padding: 0, border: 'none', marginBottom: 12 }}>
            <h3>订单摘要</h3>
            <span className="eyebrow" style={{ margin: 0 }}>CHECKOUT</span>
          </div>
          <div className="summary-line"><span>套餐</span><b>{plan?.name || '未选择'}</b></div>
          <div className="summary-line"><span>节点</span><b>{node?.name || '未选择'} {node?.flag}</b></div>
          <div className="summary-line"><span>配置</span><b>{plan ? `${plan.cpu}C / ${plan.memoryMb / 1024}G / ${plan.diskGb}G` : '—'}</b></div>
          <div className="summary-line"><span>镜像</span><b>{os}</b></div>
          <div className="summary-line"><span>周期</span><b>{months} 个月</b></div>
          <div className="summary-line"><span>小计</span><b>{money(plan ? plan.price * months : 0)}</b></div>
          {plan?.setupFee > 0 && <div className="summary-line"><span>初装费</span><b>{money(plan.setupFee)}</b></div>}
          <div className="summary-total">
            <span>应付总额</span>
            <b>{money(total)}</b>
          </div>
          <Button block size="lg" onClick={submit} disabled={busy}>{busy ? '正在处理…' : '确认支付并开通'}</Button>
          <p className="small muted" style={{ marginTop: 12 }}>支付即代表同意《服务条款》与《退款政策》。演示环境不会产生真实扣款。</p>
        </Card>
      </div>

      <Modal open={!!done} onClose={() => { setDone(null); navigate('/app/vps'); }} title="实例开通中"
        footer={<><Button variant="ghost" onClick={() => { setDone(null); navigate('/app/orders'); }}>查看订单</Button><Button onClick={() => { setDone(null); navigate(`/app/vps/${done?.instance?.id}`); }}>进入实例</Button></>}>
        {done && (
          <div>
            <p className="small muted" style={{ marginBottom: 14 }}>支付成功，系统正在部署实例，约 6 秒后可用。</p>
            <div className="kv"><span>实例名称</span><b>{done.instance.name}</b></div>
            <div className="kv"><span>节点</span><b>{done.instance.nodeName}</b></div>
            <div className="kv"><span>IP 地址</span><b className="mono">{done.instance.ip || '分配中'}</b></div>
            <div className="kv"><span>root 密码</span><b className="mono">{done.instance.rootPassword || '生成中'}</b></div>
            <div className="kv"><span>订单号</span><b className="mono">{done.order.orderNo}</b></div>
            <div style={{ marginTop: 10 }}><Badge status={done.instance.status} /></div>
          </div>
        )}
      </Modal>
    </div>
  );
}
