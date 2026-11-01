import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ago, api, date, money } from '../../lib/api.js';
import { useAuth } from '../../context/AuthContext.jsx';
import { Badge, Button, Card, Field, Loader, useToast } from '../../components/ui.jsx';

export default function Settings() {
  const { user, setUser, logout } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();
  const [nickname, setNickname] = useState(user?.nickname || '');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [busy, setBusy] = useState('');
  const [announcements, setAnnouncements] = useState(null);

  useEffect(() => {
    api('/announcements').then(setAnnouncements).catch(() => setAnnouncements([]));
  }, []);

  const saveProfile = async () => {
    if (nickname.trim().length < 2) return toast('昵称至少 2 个字符', 'error');
    setBusy('profile');
    try {
      const res = await api('/auth/me', { method: 'PATCH', body: { nickname } });
      setUser(res);
      toast('昵称已更新');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy('');
    }
  };

  const savePassword = async () => {
    if (password.length < 8) return toast('新密码至少 8 位', 'error');
    if (password !== confirm) return toast('两次输入的密码不一致', 'error');
    setBusy('password');
    try {
      await api('/auth/me', { method: 'PATCH', body: { password } });
      setPassword('');
      setConfirm('');
      toast('密码已更新，下次登录生效');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      setBusy('');
    }
  };

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <span className="eyebrow">ACCOUNT</span>
          <h1>账户设置</h1>
          <p>管理个人资料、登录密码与账户安全。</p>
        </div>
        <div className="balance-chip">余额 <b>{money(user?.balance)}</b></div>
      </div>

      <div className="grid grid--2" style={{ alignItems: 'start' }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Card>
            <div className="card-head"><h3>个人资料</h3></div>
            <div className="card-body">
              <Field label="邮箱"><input className="input" value={user?.email || ''} disabled /></Field>
              <Field label="昵称" hint="2-20 字">
                <input className="input" value={nickname} onChange={(e) => setNickname(e.target.value)} maxLength={20} />
              </Field>
              <Button size="sm" onClick={saveProfile} disabled={busy === 'profile'}>保存资料</Button>
            </div>
          </Card>

          <Card>
            <div className="card-head"><h3>修改密码</h3></div>
            <div className="card-body">
              <Field label="新密码" hint="至少 8 位"><input className="input" type="password" value={password} onChange={(e) => setPassword(e.target.value)} /></Field>
              <Field label="确认新密码"><input className="input" type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} /></Field>
              <Button size="sm" variant="ghost" onClick={savePassword} disabled={busy === 'password'}>更新密码</Button>
            </div>
          </Card>
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Card>
            <div className="card-head"><h3>账户信息</h3><Badge status={user?.status === 'active' ? 'running' : 'frozen'}>{user?.status === 'active' ? '正常' : '冻结'}</Badge></div>
            <div className="card-body">
              <div className="kv"><span>用户 ID</span><b className="mono">#{String(user?.id).padStart(6, '0')}</b></div>
              <div className="kv"><span>账户角色</span><b>{user?.role === 'admin' ? '管理员' : '普通用户'}</b></div>
              <div className="kv"><span>注册时间</span><b>{date(user?.createdAt)}</b></div>
              <div className="kv"><span>实名认证</span><b className="muted">未认证</b></div>
              <div className="kv"><span>双因素验证</span><b className="muted">未开启</b></div>
            </div>
            <div className="card-body" style={{ borderTop: '1px solid var(--line)', paddingTop: 16 }}>
              <div className="btn-row">
                {user?.role === 'admin' && <Button size="sm" variant="soft" onClick={() => navigate('/admin')}>进入管理后台</Button>}
                <Button size="sm" variant="danger" onClick={() => { logout(); navigate('/'); }}>退出登录</Button>
              </div>
            </div>
          </Card>

          <Card>
            <div className="card-head"><h3>平台公告</h3></div>
            {!announcements ? <Loader label="加载公告" /> : announcements.length === 0 ? <div className="card-body small muted">暂无公告</div> : (
              <div className="card-body" style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                {announcements.map((a) => (
                  <div key={a.id}>
                    <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
                      <span className={`pill-tag ${a.level === 'warning' ? 'pill-tag--amber' : ''}`}>{a.level === 'warning' ? '维护' : '通知'}</span>
                      <strong style={{ fontSize: 13.5, fontWeight: 500 }}>{a.title}</strong>
                      <span className="small muted" style={{ marginLeft: 'auto' }}>{ago(a.createdAt)}</span>
                    </div>
                    <p className="small muted" style={{ marginTop: 6 }}>{a.body}</p>
                  </div>
                ))}
              </div>
            )}
          </Card>
        </div>
      </div>
    </div>
  );
}
