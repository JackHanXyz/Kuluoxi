import { get } from './db.js';
import { verifyToken } from './lib/crypto.js';

export function attachUser(req, _res, next) {
  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  const payload = verifyToken(token);
  if (payload) {
    const user = get('SELECT * FROM users WHERE id = ?', payload.uid);
    if (user && user.status === 'active') req.user = user;
  }
  next();
}

export function requireAuth(req, res, next) {
  if (!req.user) return res.status(401).json({ error: '请先登录' });
  next();
}

export function requireAdmin(req, res, next) {
  if (!req.user) return res.status(401).json({ error: '请先登录' });
  if (req.user.role !== 'admin') return res.status(403).json({ error: '需要管理员权限' });
  next();
}

export const publicUser = (u) => ({
  id: u.id,
  email: u.email,
  nickname: u.nickname,
  role: u.role,
  balance: u.balance,
  status: u.status,
  createdAt: u.created_at
});
