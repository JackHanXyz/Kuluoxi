import express from 'express';
import cors from 'cors';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import { db, all, run } from './db.js';
import { attachUser } from './auth.js';
import publicRoutes from './routes/public.js';
import authRoutes from './routes/auth.js';
import userRoutes from './routes/user.js';
import adminRoutes from './routes/admin.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();

app.use(cors());
app.use(express.json({ limit: '1mb' }));
app.use(attachUser);

app.get('/api/health', (_req, res) => res.json({ ok: true, service: 'kuluoxi', time: new Date().toISOString() }));
app.use('/api', publicRoutes);
app.use('/api/auth', authRoutes);
app.use('/api', userRoutes);
app.use('/api/admin', adminRoutes);

// 生产模式：存在构建产物时直接托管前端
const dist = path.join(__dirname, '..', '..', 'web', 'dist');
if (fs.existsSync(dist)) {
  app.use(express.static(dist));
  app.get(/^\/(?!api).*/, (_req, res) => res.sendFile(path.join(dist, 'index.html')));
}

app.use((err, _req, res, _next) => {
  console.error('[error]', err);
  res.status(500).json({ error: '服务器内部错误' });
});

// 到期实例自动停机
setInterval(() => {
  const expired = all(
    "SELECT * FROM instances WHERE status IN ('running','stopped') AND expires_at < ?",
    new Date().toISOString()
  );
  for (const inst of expired) {
    run("UPDATE instances SET status = 'suspended', updated_at = ? WHERE id = ?", new Date().toISOString(), inst.id);
  }
}, 60 * 1000).unref();

const PORT = Number(process.env.PORT || 3001);
app.listen(PORT, () => {
  console.log(`库洛汐 Kuluoxi API 已启动： http://localhost:${PORT}`);
});

process.on('SIGINT', () => {
  db.close();
  process.exit(0);
});
