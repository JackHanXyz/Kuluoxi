import { useEffect, useState } from 'react';
import { api, datetime, money } from '../../lib/api.js';
import { useAuth } from '../../context/AuthContext.jsx';
import { Button, Card, Empty, Field, Loader, Modal, useToast } from '../../components/ui.jsx';

const PRESETS = [5000, 10000, 30000, 50000];
const TYPE_TEXT = { recharge: '充值', payment: '消费', refund: '退款' };

export default function Wallet() {
  const [data, setData] = useState(null);
  const [open, setOpen] = useState(false);
  const [amount, setAmount] = useState(5000);
  const [custom, setCustom] = useState('');
  const [busy, setBusy] = useState(false);
  const toast = useToast();
  const { user, refresh, setUser } = useAuth();

  const load = () => api('/wallet').then(setData).catch((e) => toast(e.message, 'error'));

  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const finalAmount = custom ? Math.round(Number(custom) * 100) : amount;

  const recharge = async () => {
    if (!Number.isFinite(finalAmount) || finalAmount < 1000) return toast('充值金额至少 ¥10', 'error');
    setBusy(true);
    try {
      const res = await api('/wallet/recharge', { method: 'POST', body: { amount: finalAmount } });
      setUser(res.user);
      await refresh();
      setOpen(false);
      setCustom('');
      toast(`充值成功，当前余额 ${money(res.balance)}`);
      load();
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
          <span className="eyebrow">WALLET</span>
          <h1>充值中心</h1>
          <p>余额可用于购买与续费，充值为模拟支付，即时到账。</p>
        </div>
        <Button onClick={() => setOpen(true)}>立即充值</Button>
      </div>

      <div className="wallet-hero" style={{ marginBottom: 20 }}>
        <div>
          <span className="small muted">可用余额（CNY）</span>
          <div className="amount">{money(user?.balance)}</div>
          <p className="small muted" style={{ marginTop: 6 }}>到期实例将从余额自动续费，余额不足会提前 3 天提醒。</p>
        </div>
        <div className="wallet-actions">
          <Button onClick={() => setOpen(true)}>充值</Button>
          <Button variant="ghost">自动续费：已开启</Button>
        </div>
      </div>

      <Card>
        <div className="card-head">
          <h3>资金流水</h3>
          <span className="small muted">{data?.transactions?.length ?? 0} 条记录</span>
        </div>
        {!data ? (
          <Loader label="读取账单" />
        ) : data.transactions.length === 0 ? (
          <Empty title="暂无流水" />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr><th>时间</th><th>类型</th><th>说明</th><th>关联单号</th><th className="right">金额</th><th className="right">余额</th></tr>
              </thead>
              <tbody>
                {data.transactions.map((t) => (
                  <tr key={t.id}>
                    <td className="small muted">{datetime(t.createdAt)}</td>
                    <td><span className={`pill-tag ${t.type === 'recharge' ? 'pill-tag--amber' : ''}`}>{TYPE_TEXT[t.type] || t.type}</span></td>
                    <td>{t.remark}</td>
                    <td className="mono small muted">{t.ref || '—'}</td>
                    <td className="right num" style={{ color: t.amount > 0 ? 'var(--accent)' : 'var(--muted)' }}>
                      {t.amount > 0 ? '+' : ''}{money(t.amount)}
                    </td>
                    <td className="right num">{money(t.balanceAfter)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title="账户充值"
        footer={<><Button variant="ghost" onClick={() => setOpen(false)}>取消</Button><Button onClick={recharge} disabled={busy}>确认充值 {money(finalAmount || 0)}</Button></>}>
        <Field label="快捷金额">
          <div className="recharge-options">
            {PRESETS.map((v) => (
              <button key={v} className={`recharge-option ${!custom && amount === v ? 'active' : ''}`}
                onClick={() => { setAmount(v); setCustom(''); }}>{money(v)}</button>
            ))}
          </div>
        </Field>
        <Field label="自定义金额" hint="¥10 - ¥50,000">
          <input className="input amount-input" type="number" placeholder="输入金额（元）" value={custom}
            onChange={(e) => setCustom(e.target.value)} min="10" />
        </Field>
        <div className="kv"><span>支付方式</span><b>模拟支付网关（即时到账）</b></div>
        <p className="small muted">演示环境不会产生真实扣款。</p>
      </Modal>
    </div>
  );
}
