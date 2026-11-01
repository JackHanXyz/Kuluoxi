import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import { Brand, Button, Field, ThemeToggle, useToast } from '../components/ui.jsx';

function AuthShell({ children, title, sub }) {
  return (
    <div className="auth-page">
      <div className="auth-visual">
        <Link to="/" className="auth-back">← 返回官网</Link>
        <Brand />
        <div style={{ position: 'relative', zIndex: 1 }}>
          <span className="eyebrow">KULUOXI CLOUD</span>
          <h2>深海的算力，<br /><span className="grad">只为稳定的你</span></h2>
          <p>登录库洛汐控制台，管理你的实例、账单与工单。所有节点状态实时同步，6 秒自动交付。</p>
        </div>
        <div className="auth-quote">
          <b>「上线三个季度，零停机迁移。」</b>
          我们用它承载了 40 多个客户的站点，控制台和自动开通是真的省心。—— 潮汐工作室
        </div>
      </div>
      <div className="auth-main">
        <div className="auth-card">
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 26 }}>
            <Link to="/"><Brand compact /></Link>
            <ThemeToggle />
          </div>
          <h1>{title}</h1>
          <p className="sub">{sub}</p>
          {children}
        </div>
      </div>
    </div>
  );
}

export function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const toast = useToast();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setError('');
    setBusy(true);
    try {
      const user = await login(email, password);
      toast(`欢迎回来，${user.nickname}`);
      navigate(user.role === 'admin' ? '/admin' : '/app');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  };

  const fill = (type) => {
    if (type === 'admin') { setEmail('admin@kuluoxi.io'); setPassword('Admin@123456'); }
    else { setEmail('demo@kuluoxi.io'); setPassword('Demo@123456'); }
  };

  return (
    <AuthShell title="登录控制台" sub="使用邮箱与密码登录，管理你的深海算力。">
      <form onSubmit={submit}>
        <Field label="邮箱地址">
          <input className="input" type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="you@example.com" autoComplete="email" required />
        </Field>
        <Field label="密码">
          <input className="input" type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="输入登录密码" autoComplete="current-password" required />
        </Field>
        {error && <p className="form-error">{error}</p>}
        <Button block size="lg" type="submit" disabled={busy}>{busy ? '登录中…' : '登录'}</Button>
      </form>
      <p className="small muted" style={{ marginTop: 18 }}>
        还没有账号？<Link to="/register" style={{ color: 'var(--accent-2)' }}>免费注册 →</Link>
      </p>
      <div className="auth-hint">
        演示账号<br />
        用户：<b>demo@kuluoxi.io</b> / Demo@123456<br />
        管理员：<b>admin@kuluoxi.io</b> / Admin@123456
        <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
          <button type="button" className="btn btn--ghost btn--sm" onClick={() => fill('user')}>填入用户</button>
          <button type="button" className="btn btn--ghost btn--sm" onClick={() => fill('admin')}>填入管理员</button>
        </div>
      </div>
    </AuthShell>
  );
}

export function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState({ email: '', nickname: '', password: '', confirm: '' });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setError('');
    if (form.password !== form.confirm) return setError('两次输入的密码不一致');
    setBusy(true);
    try {
      const user = await register(form.email, form.nickname, form.password);
      toast(`注册成功，欢迎加入库洛汐，${user.nickname}`);
      navigate('/app/buy');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  };

  const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });

  return (
    <AuthShell title="创建库洛汐账户" sub="注册即可开通服务器，无需实名与备案，支持 24 小时无理由退款。">
      <form onSubmit={submit}>
        <Field label="邮箱地址">
          <input className="input" type="email" value={form.email} onChange={set('email')} placeholder="you@example.com" required />
        </Field>
        <Field label="昵称" hint="2-20 个字符">
          <input className="input" value={form.nickname} onChange={set('nickname')} placeholder="给自己起个潮汐之名" required />
        </Field>
        <div className="form-grid">
          <Field label="密码" hint="至少 8 位">
            <input className="input" type="password" value={form.password} onChange={set('password')} required />
          </Field>
          <Field label="确认密码">
            <input className="input" type="password" value={form.confirm} onChange={set('confirm')} required />
          </Field>
        </div>
        {error && <p className="form-error">{error}</p>}
        <Button block size="lg" type="submit" disabled={busy}>{busy ? '创建中…' : '注册并开始'}</Button>
      </form>
      <p className="small muted" style={{ marginTop: 18 }}>
        已有账号？<Link to="/login" style={{ color: 'var(--accent-2)' }}>直接登录 →</Link>
      </p>
      <div className="auth-hint">注册即表示同意《服务条款》与《隐私政策》。演示环境不会发送验证邮件。</div>
    </AuthShell>
  );
}
