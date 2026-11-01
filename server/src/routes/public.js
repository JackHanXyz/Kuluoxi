import express from 'express';
import { all, get } from '../db.js';
import { OS_OPTIONS } from '../providers.js';

const router = express.Router();

const planOut = (p) => ({
  id: p.id,
  code: p.code,
  name: p.name,
  tagline: p.tagline,
  cpu: p.cpu,
  memoryMb: p.memory_mb,
  diskGb: p.disk_gb,
  bandwidthGb: p.bandwidth_gb,
  portSpeed: p.port_speed,
  price: p.price,
  setupFee: p.setup_fee,
  stock: p.stock,
  highlight: p.highlight,
  description: p.description
});

const nodeOut = (n) => ({
  id: n.id,
  name: n.name,
  code: n.code,
  region: n.region,
  country: n.country,
  flag: n.flag,
  status: n.status,
  capacity: n.capacity,
  used: n.used,
  load: Math.round((n.used / n.capacity) * 100)
});

router.get('/plans', (_req, res) => {
  const plans = all('SELECT * FROM plans WHERE active = 1 ORDER BY sort ASC, id ASC');
  res.json(plans.map(planOut));
});

router.get('/plans/:id', (req, res) => {
  const plan = get('SELECT * FROM plans WHERE id = ?', Number(req.params.id));
  if (!plan) return res.status(404).json({ error: '套餐不存在' });
  res.json(planOut(plan));
});

router.get('/nodes', (_req, res) => {
  const nodes = all('SELECT * FROM nodes ORDER BY id ASC');
  res.json(nodes.map(nodeOut));
});

router.get('/os', (_req, res) => res.json(OS_OPTIONS));

router.get('/announcements', (_req, res) => {
  const rows = all('SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC');
  res.json(rows.map((a) => ({ id: a.id, title: a.title, body: a.body, level: a.level, createdAt: a.created_at })));
});

router.get('/stats', (_req, res) => {
  const users = get("SELECT COUNT(*) AS n FROM users WHERE role = 'user'");
  const instances = get("SELECT COUNT(*) AS n FROM instances WHERE status IN ('running','stopped')");
  const nodes = get('SELECT COUNT(*) AS n FROM nodes');
  const traffic = get('SELECT SUM(traffic_used_gb) AS n FROM instances');
  res.json({
    customers: users.n,
    instances: instances.n,
    nodes: nodes.n,
    regions: all('SELECT DISTINCT country FROM nodes').length,
    trafficTb: Math.round((traffic.n || 0) / 1024 * 10) / 10,
    uptime: '99.99%',
    responseMs: 38
  });
});

export default router;
