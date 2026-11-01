import { useEffect, useState } from 'react';
import { api, money } from '../../lib/api.js';
import { Button, Card, Field, Loader, Modal, useToast } from '../../components/ui.jsx';

const emptyForm = {
  code: '', name: '', tagline: '', cpu: 1, memoryGb: 1, diskGb: 20, bandwidthGb: 1024,
  portSpeed: '100 Mbps', priceYuan: 19.9, setupYuan: 0, stock: 50, sort: 1, active: true, highlight: false, description: ''
};

const toForm = (p) => ({
  code: p.code, name: p.name, tagline: p.tagline, cpu: p.cpu, memoryGb: p.memory_mb / 1024,
  diskGb: p.disk_gb, bandwidthGb: p.bandwidth_gb, portSpeed: p.port_speed,
  priceYuan: p.price / 100, setupYuan: p.setup_fee / 100, stock: p.stock, sort: p.sort,
  active: !!p.active, highlight: !!p.highlight, description: p.description
});

export default function AdminPlans() {
  const [plans, setPlans] = useState(null);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [busy, setBusy] = useState(false);
  const toast = useToast();

  const load = () => api('/admin/plans').then(setPlans).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const openCreate = () => { setEditing({ id: null }); setForm(emptyForm); };
  const openEdit = (p) => { setEditing(p); setForm(toForm(p)); };

  const save = async () => {
    if (!form.code || !form.name) return toast('编码与名称必填', 'error');
    setBusy(true);
    const body = {
      code: form.code, name: form.name, tagline: form.tagline,
      cpu: Number(form.cpu), memoryMb: Math.round(Number(form.memoryGb) * 1024),
      diskGb: Number(form.diskGb), bandwidthGb: Number(form.bandwidthGb), portSpeed: form.portSpeed,
      price: Math.round(Number(form.priceYuan) * 100), setupFee: Math.round(Number(form.setupYuan || 0) * 100),
      stock: Number(form.stock), sort: Number(form.sort), active: form.active, highlight: form.highlight,
      description: form.description
    };
    try {
      if (editing?.id) await api(`/admin/plans/${editing.id}`, { method: 'PUT', body });
      else await api('/admin/plans', { method: 'POST', body });
      toast(editing?.id ? '套餐已更新' : '套餐已创建');
      setEditing(null);
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  const toggle = async (p) => {
    const body = { ...p, memoryMb: p.memory_mb, diskGb: p.disk_gb, bandwidthGb: p.bandwidth_gb, portSpeed: p.port_speed, setupFee: p.setup_fee, active: !p.active };
    try {
      await api(`/admin/plans/${p.id}`, { method: 'PUT', body });
      toast(p.active ? '套餐已下架' : '套餐已上架');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const remove = async (p) => {
    if (!confirm(`确认删除套餐「${p.name}」？如已产生订单将自动下架。`)) return;
    try {
      const res = await api(`/admin/plans/${p.id}`, { method: 'DELETE' });
      toast(res.archived ? '该套餐已有订单，已改为下架' : '套餐已删除');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.type === 'checkbox' ? e.target.checked : e.target.value });

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">CATALOG</span>
          <h1>套餐管理</h1>
          <p>配置售卖套餐的规格、价格与库存，下架后前台立即隐藏。</p>
        </div>
        <Button onClick={openCreate}>新建套餐</Button>
      </div>

      {!plans ? <Loader label="读取套餐" /> : (
        <Card>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr><th>套餐</th><th>配置</th><th>价格</th><th>初装费</th><th>库存</th><th>排序</th><th>状态</th><th className="right">操作</th></tr>
              </thead>
              <tbody>
                {plans.map((p) => (
                  <tr key={p.id}>
                    <td>
                      <strong>{p.name}</strong> {p.highlight ? <span className="pill-tag pill-tag--amber">推荐</span> : null}
                      <br /><span className="small muted mono">{p.code}</span>
                    </td>
                    <td className="small">{p.cpu}C / {p.memory_mb / 1024}G / {p.disk_gb}G<br /><span className="muted">{p.bandwidth_gb ? `${p.bandwidth_gb}G 流量` : '不限流量'} · {p.port_speed}</span></td>
                    <td className="num">{money(p.price)}<span className="small muted">/月</span></td>
                    <td className="num">{money(p.setup_fee)}</td>
                    <td className="num">{p.stock}</td>
                    <td className="num">{p.sort}</td>
                    <td>{p.active ? <span className="badge badge--running"><i />在售</span> : <span className="badge">已下架</span>}</td>
                    <td className="right">
                      <div className="cell-actions">
                        <Button size="sm" variant="ghost" onClick={() => toggle(p)}>{p.active ? '下架' : '上架'}</Button>
                        <Button size="sm" variant="soft" onClick={() => openEdit(p)}>编辑</Button>
                        <Button size="sm" variant="danger" onClick={() => remove(p)}>删除</Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      <Modal open={!!editing} onClose={() => setEditing(null)} title={editing?.id ? '编辑套餐' : '新建套餐'} width={680}
        footer={<><Button variant="ghost" onClick={() => setEditing(null)}>取消</Button><Button onClick={save} disabled={busy}>保存</Button></>}>
        <div className="form-grid">
          <Field label="套餐名称"><input className="input" value={form.name} onChange={set('name')} /></Field>
          <Field label="套餐编码" hint="唯一，英文小写"><input className="input" value={form.code} onChange={set('code')} /></Field>
          <Field label="一句话卖点" className="full"><input className="input" value={form.tagline} onChange={set('tagline')} /></Field>
          <Field label="CPU 核数"><input className="input" type="number" value={form.cpu} onChange={set('cpu')} /></Field>
          <Field label="内存（GB）"><input className="input" type="number" value={form.memoryGb} onChange={set('memoryGb')} /></Field>
          <Field label="硬盘（GB）"><input className="input" type="number" value={form.diskGb} onChange={set('diskGb')} /></Field>
          <Field label="月流量（GB，0 为不限）"><input className="input" type="number" value={form.bandwidthGb} onChange={set('bandwidthGb')} /></Field>
          <Field label="端口带宽"><input className="input" value={form.portSpeed} onChange={set('portSpeed')} /></Field>
          <Field label="月价（元）"><input className="input" type="number" step="0.01" value={form.priceYuan} onChange={set('priceYuan')} /></Field>
          <Field label="初装费（元）"><input className="input" type="number" step="0.01" value={form.setupYuan} onChange={set('setupYuan')} /></Field>
          <Field label="库存"><input className="input" type="number" value={form.stock} onChange={set('stock')} /></Field>
          <Field label="排序权重"><input className="input" type="number" value={form.sort} onChange={set('sort')} /></Field>
          <Field label="套餐描述" className="full"><textarea className="input textarea" style={{ minHeight: 90 }} value={form.description} onChange={set('description')} /></Field>
        </div>
        <div className="btn-row">
          <label className="small muted" style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <input type="checkbox" checked={form.active} onChange={set('active')} /> 上架销售
          </label>
          <label className="small muted" style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <input type="checkbox" checked={form.highlight} onChange={set('highlight')} /> 标记为推荐
          </label>
        </div>
      </Modal>
    </div>
  );
}
