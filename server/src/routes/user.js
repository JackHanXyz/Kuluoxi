import express from 'express';
import crypto from 'node:crypto';
import { all, get, run } from '../db.js';
import { requireAuth, publicUser } from '../auth.js';
import { instanceOut, orderOut, ticketOut } from '../lib/serialize.js';
import { OS_OPTIONS, provisionInstance, getProvider } from '../providers.js';

const router = express.Router();
router.use(requireAuth);

const now = () => new Date().toISOString();
const nextOrderNo = () => {
  const d = new Date();
  const stamp = `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}`;
  return `KX${stamp}${crypto.randomInt(1000, 9999)}`;
};

const instanceQuery = `
  SELECT i.*, p.name AS plan_name, p.code AS plan_code, n.name AS node_name, n.code AS node_code, n.region
  FROM instances i
  JOIN plans p ON p.id = i.plan_id
  JOIN nodes n ON n.id = i.node_id
`;

const orderQuery = `
  SELECT o.*, p.name AS plan_name, p.code AS plan_code, n.name AS node_name, n.code AS node_code
  FROM orders o
  JOIN plans p ON p.id = o.plan_id
  JOIN nodes n ON n.id = o.node_id
`;

/* ------------------------------- 钱包 ------------------------------- */
router.get('/wallet', (req, res) => {
  const rows = all('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC', req.user.id);
  res.json({
    balance: req.user.balance,
    transactions: rows.map((t) => ({
      id: t.id,
      type: t.type,
      amount: t.amount,
      balanceAfter: t.balance_after,
      remark: t.remark,
      ref: t.ref,
      createdAt: t.created_at
    }))
  });
});

router.post('/wallet/recharge', (req, res) => {
  const amount = Math.round(Number(req.body?.amount));
  if (!Number.isFinite(amount) || amount < 1000 || amount > 5000000) {
    return res.status(400).json({ error: '充值金额需在 ¥10 - ¥50,000 之间' });
  }
  const balance = req.user.balance + amount;
  run('UPDATE users SET balance = ? WHERE id = ?', balance, req.user.id);
  run(
    'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
    req.user.id, 'recharge', amount, balance, '在线充值（模拟支付）', `MOCK-${crypto.randomInt(100000, 999999)}`, now()
  );
  res.json({ balance, user: publicUser(get('SELECT * FROM users WHERE id = ?', req.user.id)) });
});

/* ------------------------------- 订单 ------------------------------- */
router.get('/orders', (req, res) => {
  const rows = all(`${orderQuery} WHERE o.user_id = ? ORDER BY o.created_at DESC, o.id DESC`, req.user.id);
  res.json(rows.map(orderOut));
});

router.post('/orders', (req, res) => {
  const plan = get('SELECT * FROM plans WHERE id = ? AND active = 1', Number(req.body?.planId));
  if (!plan) return res.status(400).json({ error: '套餐不可用' });
  if (plan.stock <= 0) return res.status(400).json({ error: '该套餐已售罄' });

  const node = get('SELECT * FROM nodes WHERE id = ?', Number(req.body?.nodeId));
  if (!node) return res.status(400).json({ error: '请选择可用节点' });
  if (node.status !== 'online') return res.status(400).json({ error: `${node.name} 正在维护中，请选择其它节点` });

  const months = Number(req.body?.months);
  if (![1, 3, 6, 12].includes(months)) return res.status(400).json({ error: '购买周期不合法' });

  const instanceName = String(req.body?.instanceName || '').trim();
  if (!/^[a-zA-Z0-9][a-zA-Z0-9-]{2,31}$/.test(instanceName)) {
    return res.status(400).json({ error: '实例名称需 3-32 位字母、数字或短横线，且以字母数字开头' });
  }
  const os = String(req.body?.os || '');
  if (!OS_OPTIONS.includes(os)) return res.status(400).json({ error: '请选择受支持的操作系统' });

  const amount = plan.price * months + plan.setup_fee;
  const result = run(
    `INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,instance_name,os,created_at)
     VALUES (?,?,?,?,?,?,?,?,?,?)`,
    nextOrderNo(), req.user.id, plan.id, node.id, months, amount, 'pending', instanceName, os, now()
  );
  const order = get(`${orderQuery} WHERE o.id = ?`, result.lastInsertRowid);
  res.json(orderOut(order));
});

router.post('/orders/:id/cancel', (req, res) => {
  const order = get('SELECT * FROM orders WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
  if (!order) return res.status(404).json({ error: '订单不存在' });
  if (order.status !== 'pending') return res.status(400).json({ error: '当前订单状态不可取消' });
  run("UPDATE orders SET status = 'cancelled' WHERE id = ?", order.id);
  res.json({ ok: true });
});

router.post('/orders/:id/pay', (req, res) => {
  const order = get('SELECT * FROM orders WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
  if (!order) return res.status(404).json({ error: '订单不存在' });
  if (order.status !== 'pending') return res.status(400).json({ error: '订单已处理' });

  const method = String(req.body?.method || 'wallet');
  if (!['wallet', 'alipay', 'wechat'].includes(method)) return res.status(400).json({ error: '不支持的支付方式' });

  if (method === 'wallet') {
    if (req.user.balance < order.amount) return res.status(400).json({ error: '余额不足，请先充值' });
    const balance = req.user.balance - order.amount;
    run('UPDATE users SET balance = ? WHERE id = ?', balance, req.user.id);
    run(
      'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
      req.user.id, 'payment', -order.amount, balance, `支付订单 ${order.order_no}`, order.order_no, now()
    );
  }

  run("UPDATE orders SET status = 'paid', payment_method = ?, paid_at = ? WHERE id = ?", method, now(), order.id);
  run('UPDATE plans SET stock = MAX(0, stock - 1) WHERE id = ?', order.plan_id);

  const plan = get('SELECT * FROM plans WHERE id = ?', order.plan_id);
  const expires = new Date(Date.now() + order.months * 30 * 86400000).toISOString();
  const result = run(
    `INSERT INTO instances (user_id,order_id,plan_id,node_id,name,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,expires_at,created_at,updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
    req.user.id, order.id, plan.id, order.node_id, order.instance_name, order.os, 'provisioning',
    plan.cpu, plan.memory_mb, plan.disk_gb, plan.bandwidth_gb, expires, now(), now()
  );
  provisionInstance(result.lastInsertRowid).catch((err) => console.error('[provision]', err));

  const instance = get(`${instanceQuery} WHERE i.id = ?`, result.lastInsertRowid);
  res.json({
    order: orderOut(get(`${orderQuery} WHERE o.id = ?`, order.id)),
    instance: instanceOut(instance),
    user: publicUser(get('SELECT * FROM users WHERE id = ?', req.user.id))
  });
});

/* ------------------------------- 实例 ------------------------------- */
router.get('/instances', (req, res) => {
  const rows = all(`${instanceQuery} WHERE i.user_id = ? AND i.status != 'terminated' ORDER BY i.id DESC`, req.user.id);
  res.json(rows.map((r) => instanceOut(r)));
});

router.get('/instances/:id', (req, res) => {
  const row = get(`${instanceQuery} WHERE i.id = ? AND i.user_id = ?`, Number(req.params.id), req.user.id);
  if (!row) return res.status(404).json({ error: '实例不存在' });
  const events = all(
    'SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
    req.user.id
  );
  res.json({
    ...instanceOut(row),
    events: events.map((e) => ({ id: e.id, type: e.type, remark: e.remark, createdAt: e.created_at }))
  });
});

router.patch('/instances/:id', (req, res) => {
  const inst = get('SELECT * FROM instances WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
  if (!inst) return res.status(404).json({ error: '实例不存在' });
  const name = String(req.body?.name || '').trim();
  if (!/^[a-zA-Z0-9][a-zA-Z0-9-]{2,31}$/.test(name)) return res.status(400).json({ error: '实例名称不合法' });
  run('UPDATE instances SET name = ?, updated_at = ? WHERE id = ?', name, now(), inst.id);
  res.json({ ok: true });
});

router.post('/instances/:id/power', async (req, res, next) => {
  try {
    const inst = get('SELECT * FROM instances WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
    if (!inst) return res.status(404).json({ error: '实例不存在' });
    if (inst.status === 'provisioning') return res.status(400).json({ error: '实例正在开通中，请稍候' });
    if (inst.status === 'suspended') return res.status(400).json({ error: '实例已到期停机，请先续费' });
    if (inst.status === 'terminated') return res.status(400).json({ error: '实例已销毁' });

    const action = String(req.body?.action || '');
    if (!['start', 'stop', 'reboot'].includes(action)) return res.status(400).json({ error: '不支持的操作' });

    const node = get('SELECT * FROM nodes WHERE id = ?', inst.node_id);
    await getProvider(node).power(inst.provider_id, action === 'reboot' ? 'start' : action);
    const status = action === 'stop' ? 'stopped' : 'running';
    run('UPDATE instances SET status = ?, updated_at = ? WHERE id = ?', status, now(), inst.id);
    res.json({ status });
  } catch (err) {
    next(err);
  }
});

router.post('/instances/:id/reinstall', async (req, res, next) => {
  try {
    const inst = get('SELECT * FROM instances WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
    if (!inst) return res.status(404).json({ error: '实例不存在' });
    const os = String(req.body?.os || '');
    if (!OS_OPTIONS.includes(os)) return res.status(400).json({ error: '请选择受支持的操作系统' });
    const node = get('SELECT * FROM nodes WHERE id = ?', inst.node_id);
    await getProvider(node).reinstall(inst.provider_id, os);
    run("UPDATE instances SET status = 'provisioning', os = ?, updated_at = ? WHERE id = ?", os, now(), inst.id);
    setTimeout(() => {
      run("UPDATE instances SET status = 'running', updated_at = ? WHERE id = ?", now(), inst.id);
    }, 6000);
    res.json({ ok: true, os });
  } catch (err) {
    next(err);
  }
});

router.post('/instances/:id/renew', (req, res) => {
  const inst = get('SELECT * FROM instances WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
  if (!inst) return res.status(404).json({ error: '实例不存在' });
  if (inst.status === 'terminated') return res.status(400).json({ error: '实例已销毁，无法续费' });
  const months = Number(req.body?.months);
  if (![1, 3, 6, 12].includes(months)) return res.status(400).json({ error: '续费周期不合法' });
  const plan = get('SELECT * FROM plans WHERE id = ?', inst.plan_id);
  const amount = plan.price * months;
  if (req.user.balance < amount) return res.status(400).json({ error: '余额不足，请先充值' });

  const balance = req.user.balance - amount;
  run('UPDATE users SET balance = ? WHERE id = ?', balance, req.user.id);
  run(
    'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
    req.user.id, 'payment', -amount, balance, `续费实例 ${inst.name} ${months} 个月`, `RENEW-${inst.id}`, now()
  );

  const base = new Date(Math.max(Date.now(), new Date(inst.expires_at).getTime()));
  const expires = new Date(base.getTime() + months * 30 * 86400000).toISOString();
  const status = inst.status === 'suspended' ? 'running' : inst.status;
  run('UPDATE instances SET expires_at = ?, status = ?, updated_at = ? WHERE id = ?', expires, status, now(), inst.id);
  res.json({ expiresAt: expires, status, balance });
});

router.delete('/instances/:id', async (req, res, next) => {
  try {
    const inst = get('SELECT * FROM instances WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
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

/* ------------------------------- 工单 ------------------------------- */
const ticketListSql = `
  SELECT t.*, u.nickname AS user_nickname,
    (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS message_count
  FROM tickets t JOIN users u ON u.id = t.user_id
`;

router.get('/tickets', (req, res) => {
  const rows = all(`${ticketListSql} WHERE t.user_id = ? ORDER BY t.updated_at DESC`, req.user.id);
  res.json(rows.map((t) => ticketOut(t)));
});

router.post('/tickets', (req, res) => {
  const subject = String(req.body?.subject || '').trim();
  const body = String(req.body?.body || '').trim();
  const category = String(req.body?.category || 'technical');
  const priority = String(req.body?.priority || 'normal');
  if (subject.length < 4 || subject.length > 60) return res.status(400).json({ error: '标题需 4-60 个字符' });
  if (body.length < 10 || body.length > 4000) return res.status(400).json({ error: '描述需 10-4000 个字符' });
  const result = run(
    'INSERT INTO tickets (user_id,subject,category,priority,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',
    req.user.id, subject, category, priority, 'open', now(), now()
  );
  run('INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
    result.lastInsertRowid, req.user.id, body, 0, now());
  res.json({ id: result.lastInsertRowid });
});

router.get('/tickets/:id', (req, res) => {
  const ticket = get(`${ticketListSql} WHERE t.id = ? AND t.user_id = ?`, Number(req.params.id), req.user.id);
  if (!ticket) return res.status(404).json({ error: '工单不存在' });
  const messages = all(
    `SELECT m.*, u.nickname FROM ticket_messages m LEFT JOIN users u ON u.id = m.user_id
     WHERE m.ticket_id = ? ORDER BY m.created_at ASC, m.id ASC`,
    ticket.id
  );
  res.json(ticketOut(ticket, messages));
});

router.post('/tickets/:id/reply', (req, res) => {
  const ticket = get('SELECT * FROM tickets WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
  if (!ticket) return res.status(404).json({ error: '工单不存在' });
  if (ticket.status === 'closed') return res.status(400).json({ error: '工单已关闭' });
  const body = String(req.body?.body || '').trim();
  if (body.length < 1 || body.length > 4000) return res.status(400).json({ error: '内容长度不合法' });
  run('INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
    ticket.id, req.user.id, body, 0, now());
  run("UPDATE tickets SET status = 'open', updated_at = ? WHERE id = ?", now(), ticket.id);
  res.json({ ok: true });
});

router.post('/tickets/:id/close', (req, res) => {
  const ticket = get('SELECT * FROM tickets WHERE id = ? AND user_id = ?', Number(req.params.id), req.user.id);
  if (!ticket) return res.status(404).json({ error: '工单不存在' });
  run("UPDATE tickets SET status = 'closed', updated_at = ? WHERE id = ?", now(), ticket.id);
  res.json({ ok: true });
});

/* ------------------------------- 概览 ------------------------------- */
router.get('/overview', (req, res) => {
  const instances = all(`${instanceQuery} WHERE i.user_id = ? AND i.status != 'terminated'`, req.user.id);
  const running = instances.filter((i) => i.status === 'running').length;
  const pendingOrders = get("SELECT COUNT(*) AS n FROM orders WHERE user_id = ? AND status = 'pending'", req.user.id).n;
  const openTickets = get("SELECT COUNT(*) AS n FROM tickets WHERE user_id = ? AND status != 'closed'", req.user.id).n;
  const announcements = all('SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC LIMIT 3');
  const recentOrders = all(`${orderQuery} WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5`, req.user.id);
  res.json({
    balance: req.user.balance,
    instanceCount: instances.length,
    runningCount: running,
    pendingOrders,
    openTickets,
    instances: instances.slice(0, 6).map((i) => instanceOut(i)),
    recentOrders: recentOrders.map(orderOut),
    announcements: announcements.map((a) => ({ id: a.id, title: a.title, body: a.body, level: a.level, createdAt: a.created_at }))
  });
});

export default router;
