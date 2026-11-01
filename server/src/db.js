import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { DatabaseSync } from 'node:sqlite';
import { hashPassword } from './lib/crypto.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const dataDir = path.join(__dirname, '..', 'data');
fs.mkdirSync(dataDir, { recursive: true });

export const db = new DatabaseSync(path.join(dataDir, 'kuluoxi.db'));

db.exec(`
  PRAGMA journal_mode = WAL;

  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    nickname TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    balance INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    tagline TEXT NOT NULL DEFAULT '',
    cpu INTEGER NOT NULL,
    memory_mb INTEGER NOT NULL,
    disk_gb INTEGER NOT NULL,
    bandwidth_gb INTEGER NOT NULL,
    port_speed TEXT NOT NULL,
    price INTEGER NOT NULL,
    setup_fee INTEGER NOT NULL DEFAULT 0,
    stock INTEGER NOT NULL DEFAULT 100,
    sort INTEGER NOT NULL DEFAULT 0,
    active INTEGER NOT NULL DEFAULT 1,
    highlight INTEGER NOT NULL DEFAULT 0,
    description TEXT NOT NULL DEFAULT ''
  );

  CREATE TABLE IF NOT EXISTS nodes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT UNIQUE NOT NULL,
    region TEXT NOT NULL,
    country TEXT NOT NULL,
    flag TEXT NOT NULL DEFAULT '🌐',
    provider TEXT NOT NULL DEFAULT 'mock',
    api_url TEXT NOT NULL DEFAULT '',
    api_key TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'online',
    capacity INTEGER NOT NULL DEFAULT 100,
    used INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_no TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    node_id INTEGER NOT NULL,
    months INTEGER NOT NULL DEFAULT 1,
    amount INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    payment_method TEXT NOT NULL DEFAULT '',
    instance_name TEXT NOT NULL DEFAULT '',
    os TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    paid_at TEXT
  );

  CREATE TABLE IF NOT EXISTS instances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_id INTEGER,
    plan_id INTEGER NOT NULL,
    node_id INTEGER NOT NULL,
    provider_id TEXT NOT NULL DEFAULT '',
    name TEXT NOT NULL,
    ip TEXT NOT NULL DEFAULT '',
    root_password TEXT NOT NULL DEFAULT '',
    os TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'provisioning',
    cpu INTEGER NOT NULL,
    memory_mb INTEGER NOT NULL,
    disk_gb INTEGER NOT NULL,
    bandwidth_gb INTEGER NOT NULL,
    traffic_used_gb REAL NOT NULL DEFAULT 0,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    amount INTEGER NOT NULL,
    balance_after INTEGER NOT NULL,
    remark TEXT NOT NULL DEFAULT '',
    ref TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_id INTEGER,
    subject TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'technical',
    priority TEXT NOT NULL DEFAULT 'normal',
    status TEXT NOT NULL DEFAULT 'open',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    body TEXT NOT NULL,
    is_staff INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    level TEXT NOT NULL DEFAULT 'info',
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL
  );
`);

export const all = (sql, ...params) => db.prepare(sql).all(...params);
export const get = (sql, ...params) => db.prepare(sql).get(...params);
export const run = (sql, ...params) => db.prepare(sql).run(...params);

const now = () => new Date().toISOString();
const iso = (offsetDays = 0) => new Date(Date.now() + offsetDays * 86400000).toISOString();

function seed() {
  const existing = get('SELECT COUNT(*) AS n FROM users');
  if (existing.n > 0) return;

  const adminPass = hashPassword('Admin@123456');
  const demoPass = hashPassword('Demo@123456');

  run(
    'INSERT INTO users (email, nickname, password_hash, role, balance, created_at) VALUES (?,?,?,?,?,?)',
    'admin@kuluoxi.io', '汐主', adminPass, 'admin', 0, now()
  );
  run(
    'INSERT INTO users (email, nickname, password_hash, role, balance, created_at) VALUES (?,?,?,?,?,?)',
    'demo@kuluoxi.io', '潮汐旅人', demoPass, 'user', 128000, iso(-30)
  );

  const plans = [
    ['light', '轻汐', '个人开发与轻量站点', 1, 1024, 20, 1024, '100 Mbps', 1990, 0, 40, 1, 0,
      '入门级 NVMe 云服务器，适合个人博客、代理面板与轻量测试环境。'],
    ['wave', '汐涌', '小型应用与中型站点', 2, 2048, 40, 2048, '200 Mbps', 3990, 0, 30, 2, 1,
      '双核高主频 CPU 搭配企业级 NVMe，兼顾建站与小型数据库负载。'],
    ['flow', '洛汐', '团队项目与生产服务', 4, 4096, 80, 4096, '500 Mbps', 7990, 0, 20, 3, 0,
      '四核独享算力，适合容器编排、生产环境与中等流量的业务系统。'],
    ['abyss', '深海', '高并发与重载业务', 8, 8192, 160, 8192, '1 Gbps', 15990, 0, 12, 4, 0,
      '八核大内存机型，面向高并发 API、游戏联机与数据库集群。'],
    ['aurora', '极光', '大规模分布式部署', 16, 16384, 320, 16384, '1 Gbps', 31990, 0, 6, 5, 0,
      '旗舰计算节点，适合分布式训练推理前置、集群与大规模容器化业务。'],
    ['dedicated', '独享潮台', '独享资源 · 专属整机', 32, 65536, 1024, 0, '2 Gbps', 89900, 39900, 2, 6, 0,
      '整机独享物理资源，NVMe 阵列 + 2Gbps 专线，含专属技术支持。']
  ];
  for (const p of plans) {
    run(
      `INSERT INTO plans (code,name,tagline,cpu,memory_mb,disk_gb,bandwidth_gb,port_speed,price,setup_fee,stock,sort,highlight,description)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)`, ...p
    );
  }

  const nodes = [
    ['香港 · 将军澳', 'hk-01', '香港', '中国香港', '🇭🇰', 'mock', 'https://hk01.kuluoxi.io/api', '', 'online', 120, 74],
    ['香港 · 荃湾', 'hk-02', '香港', '中国香港', '🇭🇰', 'mock', 'https://hk02.kuluoxi.io/api', '', 'online', 80, 31],
    ['东京 · 品川', 'tyo-01', '东京', '日本', '🇯🇵', 'mock', 'https://tyo01.kuluoxi.io/api', '', 'online', 100, 63],
    ['新加坡 · 裕廊', 'sgp-01', '新加坡', '新加坡', '🇸🇬', 'mock', 'https://sgp01.kuluoxi.io/api', '', 'online', 90, 44],
    ['洛杉矶 · 弗里蒙特', 'lax-01', '洛杉矶', '美国', '🇺🇸', 'mock', 'https://lax01.kuluoxi.io/api', '', 'online', 100, 58],
    ['法兰克福 · 勒斯', 'fra-01', '法兰克福', '德国', '🇩🇪', 'mock', 'https://fra01.kuluoxi.io/api', '', 'maintenance', 70, 35]
  ];
  for (const n of nodes) {
    run(
      `INSERT INTO nodes (name,code,region,country,flag,provider,api_url,api_key,status,capacity,used,created_at)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`, ...n, now()
    );
  }

  // 演示实例
  const planLight = get("SELECT * FROM plans WHERE code='light'");
  const planWave = get("SELECT * FROM plans WHERE code='wave'");
  const demo = get("SELECT * FROM users WHERE email='demo@kuluoxi.io'");
  const orderNo = (n) => `KX${String(n).padStart(8, '0')}`;
  run(
    `INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`,
    orderNo(1001), demo.id, planLight.id, 1, 1, planLight.price, 'paid', 'wallet', 'tide-hk-01', 'Ubuntu 24.04 LTS', iso(-20), iso(-20)
  );
  run(
    `INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`,
    orderNo(1002), demo.id, planWave.id, 3, 1, planWave.price, 'paid', 'alipay', 'tide-tyo-02', 'Debian 12', iso(-8), iso(-8)
  );
  run(
    `INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`,
    orderNo(1003), demo.id, planLight.id, 1, 3, planLight.price * 3, 'pending', '', 'tide-hk-03', 'Ubuntu 22.04 LTS', iso(-1), null
  );

  run(
    `INSERT INTO instances (user_id,order_id,plan_id,node_id,provider_id,name,ip,root_password,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,traffic_used_gb,expires_at,created_at,updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
    demo.id, 1, planLight.id, 1, 'kuluoxi-1f7a2c', 'tide-hk-01', '10.12.8.31', 'Kx#2026Demo', 'Ubuntu 24.04 LTS',
    'running', planLight.cpu, planLight.memory_mb, planLight.disk_gb, planLight.bandwidth_gb, 318.4, iso(10), iso(-20), now()
  );
  run(
    `INSERT INTO instances (user_id,order_id,plan_id,node_id,provider_id,name,ip,root_password,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,traffic_used_gb,expires_at,created_at,updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
    demo.id, 2, planWave.id, 3, 'kuluoxi-9c31bb', 'tide-tyo-02', '10.41.6.88', 'Kx#2026Wave', 'Debian 12',
    'stopped', planWave.cpu, planWave.memory_mb, planWave.disk_gb, planWave.bandwidth_gb, 1206.7, iso(22), iso(-8), now()
  );

  run(
    `INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at)
     VALUES (?,?,?,?,?,?,?)`, demo.id, 'recharge', 200000, 200000, '在线充值（模拟支付）', 'MOCK-1001', iso(-32)
  );
  run(
    `INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at)
     VALUES (?,?,?,?,?,?,?)`, demo.id, 'payment', -planLight.price - planWave.price, 200000 - planLight.price - planWave.price,
    '购买「轻汐」「汐涌」实例', 'KX00001001', iso(-20)
  );
  run(
    `INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at)
     VALUES (?,?,?,?,?,?,?)`, demo.id, 'refund', 12000, 128000, '工单退款 · 网络抖动补偿', 'TK-000001', iso(-3)
  );

  run(
    `INSERT INTO tickets (user_id,order_id,subject,category,priority,status,created_at,updated_at)
     VALUES (?,?,?,?,?,?,?,?)`, demo.id, null, '东京节点夜间延迟抖动', 'network', 'high', 'answered', iso(-4), iso(-3)
  );
  run(
    'INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
    1, demo.id, '最近两晚 23:00 后东京节点延迟从 40ms 上升到 120ms，帮忙看下骨干线路。', 0, iso(-4)
  );
  run(
    'INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
    1, 1, '已确认是国际出口拥塞，我们已切换备用链路并补偿余额 ¥120，请观察今日表现。', 1, iso(-3)
  );
  run(
    `INSERT INTO tickets (user_id,order_id,subject,category,priority,status,created_at,updated_at)
     VALUES (?,?,?,?,?,?,?,?)`, demo.id, null, '申请增加快照功能', 'feature', 'normal', 'open', iso(-1), iso(-1)
  );
  run(
    'INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
    2, demo.id, '希望控制台支持手动创建快照与回滚，付费也可以。', 0, iso(-1)
  );

  run(
    `INSERT INTO announcements (title,body,level,active,created_at) VALUES (?,?,?,?,?)`,
    '法兰克福节点计划维护', '法兰克福 FRA-01 将于本周日 02:00–04:00 (UTC+8) 进行核心交换机升级，期间实例可能短暂断网，请提前保存工作。', 'warning', 1, iso(-2)
  );
  run(
    `INSERT INTO announcements (title,body,level,active,created_at) VALUES (?,?,?,?,?)`,
    '库洛汐控制台上线 2.0', '全新控制台支持一键重装、实时流量统计与余额自动续费，感谢一路同行的你。', 'info', 1, iso(-10)
  );
}

seed();
