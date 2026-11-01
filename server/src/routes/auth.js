import express from 'express';
import { get, run } from '../db.js';
import { hashPassword, signToken, verifyPassword } from '../lib/crypto.js';
import { publicUser, requireAuth } from '../auth.js';

const router = express.Router();

router.post('/register', (req, res) => {
  const email = String(req.body?.email || '').trim().toLowerCase();
  const nickname = String(req.body?.nickname || '').trim();
  const password = String(req.body?.password || '');

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return res.status(400).json({ error: '邮箱格式不正确' });
  if (nickname.length < 2 || nickname.length > 20) return res.status(400).json({ error: '昵称长度需在 2-20 个字符' });
  if (password.length < 8) return res.status(400).json({ error: '密码至少 8 位' });
  if (get('SELECT id FROM users WHERE email = ?', email)) return res.status(409).json({ error: '该邮箱已注册' });

  const result = run(
    'INSERT INTO users (email, nickname, password_hash, role, balance, created_at) VALUES (?,?,?,?,?,?)',
    email, nickname, hashPassword(password), 'user', 0, new Date().toISOString()
  );
  const user = get('SELECT * FROM users WHERE id = ?', result.lastInsertRowid);
  res.json({ token: signToken({ uid: user.id, role: user.role }), user: publicUser(user) });
});

router.post('/login', (req, res) => {
  const email = String(req.body?.email || '').trim().toLowerCase();
  const password = String(req.body?.password || '');
  const user = get('SELECT * FROM users WHERE email = ?', email);
  if (!user || !verifyPassword(password, user.password_hash)) {
    return res.status(401).json({ error: '邮箱或密码错误' });
  }
  if (user.status !== 'active') return res.status(403).json({ error: '账户已被冻结，请联系客服' });
  res.json({ token: signToken({ uid: user.id, role: user.role }), user: publicUser(user) });
});

router.get('/me', requireAuth, (req, res) => res.json(publicUser(req.user)));

router.patch('/me', requireAuth, (req, res) => {
  const nickname = req.body?.nickname;
  const password = req.body?.password;
  if (nickname !== undefined) {
    const name = String(nickname).trim();
    if (name.length < 2 || name.length > 20) return res.status(400).json({ error: '昵称长度需在 2-20 个字符' });
    run('UPDATE users SET nickname = ? WHERE id = ?', name, req.user.id);
  }
  if (password !== undefined) {
    if (String(password).length < 8) return res.status(400).json({ error: '密码至少 8 位' });
    run('UPDATE users SET password_hash = ? WHERE id = ?', hashPassword(String(password)), req.user.id);
  }
  res.json(publicUser(get('SELECT * FROM users WHERE id = ?', req.user.id)));
});

export default router;
