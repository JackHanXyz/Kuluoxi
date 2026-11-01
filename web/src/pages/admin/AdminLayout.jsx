import { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext.jsx';
import { Brand, ThemeToggle } from '../../components/ui.jsx';

const items = [
  { to: '/admin', label: '运营概览', icon: '◈', end: true },
  { to: '/admin/plans', label: '套餐管理', icon: '▤' },
  { to: '/admin/nodes', label: '节点管理', icon: '◎' },
  { to: '/admin/orders', label: '订单管理', icon: '▣' },
  { to: '/admin/users', label: '用户管理', icon: '◍' },
  { to: '/admin/instances', label: '实例管理', icon: '❖' },
  { to: '/admin/tickets', label: '工单处理', icon: '✉' }
];

export default function AdminLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);

  return (
    <div className="shell">
      <header className="topnav">
        <div className="topnav-left">
          <button className="icon-btn mobile-only" onClick={() => setOpen((v) => !v)} aria-label="菜单">☰</button>
          <Link to="/admin"><Brand /></Link>
          <span className="topnav-divider" />
          <span className="topnav-title">运营后台</span>
          <span className="pill-tag pill-tag--amber">ADMIN</span>
        </div>
        <div className="topnav-right">
          <Link to="/app" className="btn btn--ghost btn--sm">用户视角</Link>
          <Link to="/admin/tickets" className="btn btn--primary btn--sm">处理工单</Link>
          <ThemeToggle />
          <Link to="/" className="icon-btn" title="返回官网">⌂</Link>
        </div>
      </header>

      <div className="shell-body">
        {open && <div className="sidebar-mask" onClick={() => setOpen(false)} />}
        <aside className={`sidebar ${open ? 'open' : ''}`}>
          <span className="side-label">运营中心</span>
          <nav className="side-nav" style={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
            {items.map((i) => (
              <NavLink key={i.to} to={i.to} end={i.end} className={({ isActive }) => `side-link ${isActive ? 'active' : ''}`} onClick={() => setOpen(false)}>
                <span style={{ width: 16, textAlign: 'center', fontSize: 13 }}>{i.icon}</span>
                {i.label}
              </NavLink>
            ))}
            <span className="side-label">客户侧</span>
            <NavLink to="/app" className="side-link"><span style={{ width: 16, textAlign: 'center' }}>↗</span>用户控制台</NavLink>
            <NavLink to="/" className="side-link"><span style={{ width: 16, textAlign: 'center' }}>⌂</span>返回官网</NavLink>
          </nav>
          <div className="side-foot">
            <div className="avatar">{user?.nickname?.slice(0, 1) || '管'}</div>
            <div className="user-mini">
              <strong>{user?.nickname}</strong>
              <span>{user?.email}</span>
            </div>
            <button className="icon-btn" title="退出登录" style={{ marginLeft: 'auto' }} onClick={() => { logout(); navigate('/'); }}>⏻</button>
          </div>
        </aside>

        <main className="main">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
