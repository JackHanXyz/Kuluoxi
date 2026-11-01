import express from 'express';
import { all, get, run } from '../db.js';
import { requireAdmin, publicUser } from '../auth.js';
import { instanceOut, orderOut, ticketOut } from '../lib/serialize.js';
import { provisionInstance, getProvider, OS_OPTIONS } from '../providers.js';

const router = express.Router();
router.use(requireAdmin);

const now = () => new Date().toISOString();

/* ------------------------------- 概览 ------------------------------- */
router.get('/overview', (_req, res) => {
  const revenue = get("SELECT COALESCE(SUM(amount),0) AS n FROM orders WHERE status = 'paid'");
  const monthRevenue = get(
    "SELECT COALESCE(SUM(amount),0) AS n FROM orders WHERE status = 'paid' AND paid_at >= ?",
    new Date(Date.now() - 30 * 86400000).toISOString()
  );
  const users = get("SELECT COUNT(*) AS n FROM users WHERE role = 'user'");
  const activeInstances = get("SELECT COUNT(*) AS n FROM instances WHERE status IN ('running','stopped')");
  const provisioning = get("SELECT COUNT(*) AS n FROM instances WHERE status = 'provisioning'");
  const pendingOrders = get("SELECT COUNT(*) AS n FROM orders WHERE status = 'pending'");
  const openTickets = get("SELECT COUNT(*) AS n FROM tickets WHERE status != 'closed'");

  const nodes = all('SELECT * FROM nodes ORDER BY id');
  const nodeStats = nodes.map((n) => ({
    id: n.id, name: n.name, code: n.code, status: n.status,
    capacity: n.capacity, used: n.used, load: Math.round((n.used / n.capacity) * 100)
  }));

  const recentOrders = all(`
    SELECT o.*, p.name AS plan_name, p.code AS plan_code, n.name AS node_name, n.code AS node_code, u.nickname
    FROM orders o JOIN plans p ON p.id = o.plan_id JOIN nodes n ON n.id = o.node_id JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC, o.id DESC LIMIT 6`);

  // 近 14 天收入趋势
  const since = new Date(Date.now() - 13 * 86400000);
  since.setHours(0, 0, 0, 0);
  const paid = all("SELECT amount, paid_at FROM orders WHERE status = 'paid' AND paid_at >= ?", since.toISOString());
  const trend = Array.from({ length: 14 }, (_, idx) => {
    const d = new Date(since.getTime() + idx * 86400000);
    const key = d.toISOString().slice(0, 10);
    const sum = paid.filter((o) => (o.paid_at || '').slice(0, 10) === key).reduce((a, o) => a + o.amount, 0);
    return { date: key, revenue: sum };
  });

  const planSales = all(`
    SELECT p.name, p.code, COUNT(o.id) AS count, COALESCE(SUM(o.amount),0) AS revenue
    FROM plans p LEFT JOIN orders o ON o.plan_id = p.id AND o.status = 'paid'
    GROUP BY p.id ORDER BY revenue DESC`);

  const recentTickets = all(`
    SELECT t.*, u.nickname AS user_nickname,
      (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS message_count
    FROM tickets t JOIN users u ON u.id = t.user_id ORDER BY t.updated_at DESC LIMIT 5`);

  res.json({
    revenue: revenue.n,
    monthRevenue: monthRevenue.n,
    users: users.n,
    activeInstances: activeInstances.n,
    provisioning: provisioning.n,
    pendingOrders: pendingOrders.n,
    openTickets: openTickets.n,
    nodes: nodeStats,
    recentOrders: recentOrders.map((o) => ({ ...orderOut(o), nickname: o.nickname })),
    recentTickets: recentTickets.map((t) => ticketOut(t)),
    trend,
    planSales
  });
});

/* ------------------------------- 用户 ------------------------------- */
router.get('/users', (req, res) => {
  const keyword = String(req.query.q || '').trim();
  const rows = keyword
    ? all('SELECT * FROM users WHERE email LIKE ? OR nickname LIKE ? ORDER BY id DESC', `%${keyword}%`, `%${keyword}%`)
    : all('SELECT * FROM users ORDER BY id DESC');
  res.json(rows.map((u) => ({
    ...publicUser(u),
    instanceCount: get("SELECT COUNT(*) AS n FROM instances WHERE user_id = ? AND status != 'terminated'", u.id).n,
    totalSpent: get("SELECT COALESCE(SUM(amount),0) AS n FROM orders WHERE user_id = ? AND status = 'paid'", u.id).n
  })));
});

router.post('/users/:id/balance', (req, res) => {
  const user = get('SELECT * FROM users WHERE id = ?', Number(req.params.id));
  if (!user) return res.status(404).json({ error: '用户不存在' });
  const delta = Math.round(Number(req.body?.amount));
  if (!Number.isFinite(delta) || delta === 0) return res.status(400).json({ error: '金额不合法' });
  const balance = user.balance + delta;
  if (balance < 0) return res.status(400).json({ error: '调整后余额不能为负' });
  run('UPDATE users SET balance = ? WHERE id = ?', balance, user.id);
  run(
    'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
    user.id, delta > 0 ? 'recharge' : 'payment', delta, balance,
    `管理员余额调整：${String(req.body?.remark || '无备注')}`, 'ADMIN', now()
  );
  res.json({ ok: true, balance });
});

router.patch('/users/:id', (req, res) => {
  const user = get('SELECT * FROM users WHERE id = ?', Number(req.params.id));
  if (!user) return res.status(404).json({ error: '用户不存在' });
  const status = String(req.body?.status || '');
  if (!['active', 'frozen'].includes(status)) return res.status(400).json({ error: '状态不合法' });
  if (user.role === 'admin') return res.status(400).json({ error: '不能冻结管理员账户' });
  run('UPDATE users SET status = ? WHERE id = ?', status, user.id);
  res.json({ ok: true });
});

/* ------------------------------- 套餐 ------------------------------- */
router.get('/plans', (_req, res) => {
  res.json(all('SELECT * FROM plans ORDER BY sort ASC, id ASC'));
});

const planPayload = (b) => {
  const num = (v, d = 0) => (Number.isFinite(Number(v)) ? Math.round(Number(v)) : d);
  return {
    code: String(b.code || '').trim(),
    name: String(b.name || '').trim(),
    tagline: String(b.tagline || '').trim(),
    cpu: num(b.cpu), memory_mb: num(b.memoryMb), disk_gb: num(b.diskGb),
    bandwidth_gb: num(b.bandwidthGb), port_speed: String(b.portSpeed || '100 Mbps'),
    price: num(b.price), setup_fee: num(b.setupFee), stock: num(b.stock),
    sort: num(b.sort), active: b.active === false ? 0 : 1, highlight: b.highlight ? 1 : 0,
    description: String(b.description || '').trim()
  };
};

router.post('/plans', (req, res) => {
  const p = planPayload(req.body || {});
  if (!p.code || !p.name || p.price <= 0) return res.status(400).json({ error: '编码、名称与价格必填' });
  if (get('SELECT id FROM plans WHERE code = ?', p.code)) return res.status(409).json({ error: '套餐编码已存在' });
  const r = run(
    `INSERT INTO plans (code,name,tagline,cpu,memory_mb,disk_gb,bandwidth_gb,port_speed,price,setup_fee,stock,sort,active,highlight,description)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
    p.code, p.name, p.tagline, p.cpu, p.memory_mb, p.disk_gb, p.bandwidth_gb, p.port_speed,
    p.price, p.setup_fee, p.stock, p.sort, p.active, p.highlight, p.description
  );
  res.json(get('SELECT * FROM plans WHERE id = ?', r.lastInsertRowid));
});

router.put('/plans/:id', (req, res) => {
  const existing = get('SELECT * FROM plans WHERE id = ?', Number(req.params.id));
  if (!existing) return res.status(404).json({ error: '套餐不存在' });
  const p = planPayload({ ...existing, ...req.body, memoryMb: req.body.memoryMb ?? existing.memory_mb, diskGb: req.body.diskGb ?? existing.disk_gb, bandwidthGb: req.body.bandwidthGb ?? existing.bandwidth_gb, portSpeed: req.body.portSpeed ?? existing.port_speed, setupFee: req.body.setupFee ?? existing.setup_fee });
  if (!p.code || !p.name || p.price <= 0) return res.status(400).json({ error: '编码、名称与价格必填' });
  run(
    `UPDATE plans SET code=?,name=?,tagline=?,cpu=?,memory_mb=?,disk_gb=?,bandwidth_gb=?,port_speed=?,price=?,setup_fee=?,stock=?,sort=?,active=?,highlight=?,description=? WHERE id=?`,
    p.code, p.name, p.tagline, p.cpu, p.memory_mb, p.disk_gb, p.bandwidth_gb, p.port_speed,
    p.price, p.setup_fee, p.stock, p.sort, p.active, p.highlight, p.description, existing.id
  );
  res.json(get('SELECT * FROM plans WHERE id = ?', existing.id));
});

router.delete('/plans/:id', (req, res) => {
  const plan = get('SELECT * FROM plans WHERE id = ?', Number(req.params.id));
  if (!plan) return res.status(404).json({ error: '套餐不存在' });
  const used = get("SELECT COUNT(*) AS n FROM orders WHERE plan_id = ? AND status IN ('pending','paid')", plan.id).n;
  if (used > 0) {
    run('UPDATE plans SET active = 0 WHERE id = ?', plan.id);
    return res.json({ ok: true, archived: true });
  }
  run('DELETE FROM plans WHERE id = ?', plan.id);
  res.json({ ok: true });
});

/* ------------------------------- 节点 ------------------------------- */
router.get('/nodes', (_req, res) => res.json(all('SELECT * FROM nodes ORDER BY id')));

router.post('/nodes', (req, res) => {
  const b = req.body || {};
  const code = String(b.code || '').trim().toLowerCase();
  if (!code || !String(b.name || '').trim()) return res.status(400).json({ error: '节点名称与编码必填' });
  if (get('SELECT id FROM nodes WHERE code = ?', code)) return res.status(409).json({ error: '节点编码已存在' });
  const r = run(
    `INSERT INTO nodes (name,code,region,country,flag,provider,api_url,api_key,status,capacity,used,created_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`,
    String(b.name).trim(), code, String(b.region || b.country || '未知').trim(), String(b.country || '未知').trim(),
    String(b.flag || '🌐'), String(b.provider || 'mock'), String(b.apiUrl || ''), String(b.apiKey || ''),
    String(b.status || 'online'), Math.max(1, Math.round(Number(b.capacity) || 100)), 0, now()
  );
  res.json(get('SELECT * FROM nodes WHERE id = ?', r.lastInsertRowid));
});

router.put('/nodes/:id', (req, res) => {
  const node = get('SELECT * FROM nodes WHERE id = ?', Number(req.params.id));
  if (!node) return res.status(404).json({ error: '节点不存在' });
  const b = req.body || {};
  run(
    `UPDATE nodes SET name=?, region=?, country=?, flag=?, provider=?, api_url=?, api_key=?, status=?, capacity=? WHERE id=?`,
    String(b.name ?? node.name), String(b.region ?? node.region), String(b.country ?? node.country),
    String(b.flag ?? node.flag), String(b.provider ?? node.provider), String(b.apiUrl ?? node.api_url),
    String(b.apiKey ?? node.api_key), String(b.status ?? node.status),
    Math.max(1, Math.round(Number(b.capacity ?? node.capacity))), node.id
  );
  res.json(get('SELECT * FROM nodes WHERE id = ?', node.id));
});

router.delete('/nodes/:id', (req, res) => {
  const node = get('SELECT * FROM nodes WHERE id = ?', Number(req.params.id));
  if (!node) return res.status(404).json({ error: '节点不存在' });
  const used = get("SELECT COUNT(*) AS n FROM instances WHERE node_id = ? AND status IN ('running','stopped','provisioning','suspended')", node.id).n;
  if (used > 0) return res.status(400).json({ error: '该节点仍有实例，无法删除' });
  run('DELETE FROM nodes WHERE id = ?', node.id);
  res.json({ ok: true });
});

/* ------------------------------- 订单 ------------------------------- */
router.get('/orders', (req, res) => {
  const status = String(req.query.status || '');
  const base = `
    SELECT o.*, p.name AS plan_name, p.code AS plan_code, n.name AS node_name, n.code AS node_code, u.nickname
    FROM orders o JOIN plans p ON p.id = o.plan_id JOIN nodes n ON n.id = o.node_id JOIN users u ON u.id = o.user_id`;
  const rows = status
    ? all(`${base} WHERE o.status = ? ORDER BY o.created_at DESC, o.id DESC`, status)
    : all(`${base} ORDER BY o.created_at DESC, o.id DESC`);
  res.json(rows.map((o) => ({ ...orderOut(o), nickname: o.nickname, userId: o.user_id })));
});

router.post('/orders/:id/close', (req, res) => {
  const order = get('SELECT * FROM orders WHERE id = ?', Number(req.params.id));
  if (!order) return res.status(404).json({ error: '订单不存在' });
  if (order.status !== 'pending') return res.status(400).json({ error: '仅可关闭待支付订单' });
  run("UPDATE orders SET status = 'cancelled' WHERE id = ?", order.id);
  res.json({ ok: true });
});

/* ------------------------------- 实例 ------------------------------- */
router.get('/instances', (req, res) => {
  const base = `
    SELECT i.*, p.name AS plan_name, p.code AS plan_code, n.name AS node_name, n.code AS node_code, n.region, u.nickname
    FROM instances i JOIN plans p ON p.id = i.plan_id JOIN nodes n ON n.id = i.node_id JOIN users u ON u.id = i.user_id`;
  const rows = all(`${base} ORDER BY i.id DESC`);
  res.json(rows.map((i) => ({ ...instanceOut(i), nickname: i.nickname, userId: i.user_id })));
});

router.post('/instances/:id/power', async (req, res, next) => {
  try {
    const inst = get('SELECT * FROM instances WHERE id = ?', Number(req.params.id));
    if (!inst) return res.status(404).json({ error: '实例不存在' });
    const action = String(req.body?.action || '');
    if (!['start', 'stop', 'reboot', 'suspend', 'resume'].includes(action)) return res.status(400).json({ error: '不支持的操作' });
    if (action === 'suspend') {
      run("UPDATE instances SET status = 'suspended', updated_at = ? WHERE id = ?", now(), inst.id);
      return res.json({ status: 'suspended' });
    }
    if (action === 'resume') {
      run("UPDATE instances SET status = 'running', updated_at = ? WHERE id = ?", now(), inst.id);
      return res.json({ status: 'running' });
    }
    const status = action === 'stop' ? 'stopped' : 'running';
    run('UPDATE instances SET status = ?, updated_at = ? WHERE id = ?', status, now(), inst.id);
    res.json({ status });
  } catch (err) {
    next(err);
  }
});

router.post('/instances/:id/reinstall', (req, res) => {
  const inst = get('SELECT * FROM instances WHERE id = ?', Number(req.params.id));
  if (!inst) return res.status(404).json({ error: '实例不存在' });
  const os = String(req.body?.os || '');
  if (!OS_OPTIONS.includes(os)) return res.status(400).json({ error: '系统镜像不支持' });
  run("UPDATE instances SET status = 'provisioning', os = ?, updated_at = ? WHERE id = ?", os, now(), inst.id);
  setTimeout(() => run("UPDATE instances SET status = 'running', updated_at = ? WHERE id = ?", now(), inst.id), 6000);
  res.json({ ok: true });
});

router.post('/instances/:id/terminate', async (req, res, next) => {
  try {
    const inst = get('SELECT * FROM instances WHERE id = ?', Number(req.params.id));
    if (!inst) return res.status(404).json({ error: '实例不存在' });
    const node = get('SELECT * FROM nodes WHERE id = ?', inst.node_id);
    await getProvider(node).destroy(inst.provider_id);
    run("UPDATE instances SET status = 'terminated', updated_at = ? WHERE id = ?", now(), inst.id);
    if (node) run('UPDATE nodes SET used = MAX(0, used - 1) WHERE id = ?', node.id);
    res.json({ ok: true });
  } catch (err) {
    next(err);
  }
});

router.post('/instances/:id/provision', (req, res) => {
  const inst = get('SELECT * FROM instances WHERE id = ?', Number(req.params.id));
  if (!inst) return res.status(404).json({ error: '实例不存在' });
  provisionInstance(inst.id).catch((e) => console.error(e));
  res.json({ ok: true });
});

/* ------------------------------- 工单 ------------------------------- */
router.get('/tickets', (req, res) => {
  const status = String(req.query.status || '');
  const base = `
    SELECT t.*, u.nickname AS user_nickname,
      (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS message_count
    FROM tickets t JOIN users u ON u.id = t.user_id`;
  const rows = status ? all(`${base} WHERE t.status = ? ORDER BY t.updated_at DESC`, status)
    : all(`${base} ORDER BY t.updated_at DESC`);
  res.json(rows.map((t) => ticketOut(t)));
});

router.get('/tickets/:id', (req, res) => {
  const ticket = get(`
    SELECT t.*, u.nickname AS user_nickname,
      (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS message_count
    FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ?`, Number(req.params.id));
  if (!ticket) return res.status(404).json({ error: '工单不存在' });
  const messages = all(
    `SELECT m.*, u.nickname FROM ticket_messages m LEFT JOIN users u ON u.id = m.user_id
     WHERE m.ticket_id = ? ORDER BY m.created_at ASC, m.id ASC`, ticket.id
  );
  res.json(ticketOut(ticket, messages));
});

router.post('/tickets/:id/reply', (req, res) => {
  const ticket = get('SELECT * FROM tickets WHERE id = ?', Number(req.params.id));
  if (!ticket) return res.status(404).json({ error: '工单不存在' });
  const body = String(req.body?.body || '').trim();
  if (!body) return res.status(400).json({ error: '回复内容不能为空' });
  run('INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
    ticket.id, req.user.id, body, 1, now());
  run("UPDATE tickets SET status = 'answered', updated_at = ? WHERE id = ?", now(), ticket.id);
  res.json({ ok: true });
});

router.post('/tickets/:id/close', (req, res) => {
  const ticket = get('SELECT * FROM tickets WHERE id = ?', Number(req.params.id));
  if (!ticket) return res.status(404).json({ error: '工单不存在' });
  run("UPDATE tickets SET status = 'closed', updated_at = ? WHERE id = ?", now(), ticket.id);
  res.json({ ok: true });
});

/* ------------------------------- 公告 ------------------------------- */
router.post('/announcements', (req, res) => {
  const title = String(req.body?.title || '').trim();
  const body = String(req.body?.body || '').trim();
  if (!title || !body) return res.status(400).json({ error: '标题与内容必填' });
  const r = run(
    'INSERT INTO announcements (title,body,level,active,created_at) VALUES (?,?,?,?,?)',
    title, body, String(req.body?.level || 'info'), 1, now()
  );
  res.json(get('SELECT * FROM announcements WHERE id = ?', r.lastInsertRowid));
});

router.delete('/announcements/:id', (req, res) => {
  run('DELETE FROM announcements WHERE id = ?', Number(req.params.id));
  res.json({ ok: true });
});

export default router;
