import { useEffect, useState } from 'react';
import { api } from '../../lib/api.js';
import { Button, Card, Field, Loader, Modal, Progress, useToast } from '../../components/ui.jsx';

const emptyForm = { name: '', code: '', region: '', country: '', flag: '🌐', provider: 'mock', apiUrl: '', apiKey: '', status: 'online', capacity: 100 };

export default function AdminNodes() {
  const [nodes, setNodes] = useState(null);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [busy, setBusy] = useState(false);
  const toast = useToast();

  const load = () => api('/admin/nodes').then(setNodes).catch((e) => toast(e.message, 'error'));
  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const openEdit = (n) => {
    setEditing(n || { id: null });
    setForm(n ? { name: n.name, code: n.code, region: n.region, country: n.country, flag: n.flag, provider: n.provider, apiUrl: n.api_url, apiKey: n.api_key, status: n.status, capacity: n.capacity } : emptyForm);
  };

  const save = async () => {
    if (!form.name || !form.code) return toast('名称与编码必填', 'error');
    setBusy(true);
    try {
      if (editing?.id) await api(`/admin/nodes/${editing.id}`, { method: 'PUT', body: form });
      else await api('/admin/nodes', { method: 'POST', body: form });
      toast(editing?.id ? '节点已更新' : '节点已创建');
      setEditing(null);
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  const toggleStatus = async (n) => {
    try {
      await api(`/admin/nodes/${n.id}`, { method: 'PUT', body: { ...n, apiUrl: n.api_url, apiKey: n.api_key, status: n.status === 'online' ? 'maintenance' : 'online' } });
      toast(n.status === 'online' ? '节点已进入维护' : '节点已恢复上线');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const remove = async (n) => {
    if (!confirm(`确认删除节点「${n.name}」？`)) return;
    try {
      await api(`/admin/nodes/${n.id}`, { method: 'DELETE' });
      toast('节点已删除');
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  };

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">INFRASTRUCTURE</span>
          <h1>节点管理</h1>
          <p>维护可用地域与容量，维护中的节点会在购买流程中自动禁用。</p>
        </div>
        <Button onClick={() => openEdit(null)}>新增节点</Button>
      </div>

      {!nodes ? <Loader label="读取节点" /> : (
        <Card>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr><th>节点</th><th>地区</th><th>Provider</th><th>负载</th><th>状态</th><th className="right">操作</th></tr>
              </thead>
              <tbody>
                {nodes.map((n) => (
                  <tr key={n.id}>
                    <td><span style={{ fontSize: 18, marginRight: 8 }}>{n.flag}</span><strong>{n.name}</strong><br /><span className="small muted mono">{n.code.toUpperCase()}</span></td>
                    <td>{n.region}<br /><span className="small muted">{n.country}</span></td>
                    <td><span className="pill-tag">{n.provider}</span></td>
                    <td style={{ minWidth: 150 }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, marginBottom: 4 }}>
                        <span className="muted">{n.used} / {n.capacity}</span>
                        <span className="num">{Math.round((n.used / n.capacity) * 100)}%</span>
                      </div>
                      <Progress value={(n.used / n.capacity) * 100} tone={n.used / n.capacity > 0.8 ? 'danger' : n.used / n.capacity > 0.6 ? 'warn' : ''} />
                    </td>
                    <td>{n.status === 'online' ? <span className="badge badge--running"><i />在线</span> : <span className="badge badge--maintenance"><i />维护</span>}</td>
                    <td className="right">
                      <div className="cell-actions">
                        <Button size="sm" variant="ghost" onClick={() => toggleStatus(n)}>{n.status === 'online' ? '维护' : '恢复'}</Button>
                        <Button size="sm" variant="soft" onClick={() => openEdit(n)}>编辑</Button>
                        <Button size="sm" variant="danger" onClick={() => remove(n)}>删除</Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      <Modal open={!!editing} onClose={() => setEditing(null)} title={editing?.id ? '编辑节点' : '新增节点'} width={620}
        footer={<><Button variant="ghost" onClick={() => setEditing(null)}>取消</Button><Button onClick={save} disabled={busy}>保存</Button></>}>
        <div className="form-grid">
          <Field label="节点名称"><input className="input" value={form.name} onChange={set('name')} placeholder="香港 · 将军澳" /></Field>
          <Field label="节点编码" hint="唯一，英文"><input className="input" value={form.code} onChange={set('code')} placeholder="hk-03" disabled={!!editing?.id} /></Field>
          <Field label="地域"><input className="input" value={form.region} onChange={set('region')} placeholder="香港" /></Field>
          <Field label="国家 / 地区"><input className="input" value={form.country} onChange={set('country')} placeholder="中国香港" /></Field>
          <Field label="旗帜 Emoji"><input className="input" value={form.flag} onChange={set('flag')} /></Field>
          <Field label="Provider">
            <select className="input select" value={form.provider} onChange={set('provider')}>
              <option value="mock">mock（模拟）</option>
              <option value="proxmox">proxmox（预留）</option>
              <option value="solusvm">solusvm（预留）</option>
            </select>
          </Field>
          <Field label="API 地址"><input className="input" value={form.apiUrl} onChange={set('apiUrl')} placeholder="https://node/api" /></Field>
          <Field label="API Key"><input className="input" value={form.apiKey} onChange={set('apiKey')} type="password" /></Field>
          <Field label="容量（可开通实例数）"><input className="input" type="number" value={form.capacity} onChange={set('capacity')} /></Field>
          <Field label="状态">
            <select className="input select" value={form.status} onChange={set('status')}>
              <option value="online">在线</option>
              <option value="maintenance">维护</option>
            </select>
          </Field>
        </div>
      </Modal>
    </div>
  );
}
