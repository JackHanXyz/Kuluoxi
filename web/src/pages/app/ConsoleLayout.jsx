import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext.jsx';
import { Brand, Button, ThemeToggle } from '../../components/ui.jsx';
import { money } from '../../lib/api.js';

const icons = {
  grid: <><rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" /></>,
  server: <><rect x="3" y="4" width="18" height="7" rx="2" /><rect x="3" y="13" width="18" height="7" rx="2" /><path d="M7 7.5h.01M7 16.5h.01" /></>,
  cart: <><path d="M3 4h2l2.4 11h10.2L20 8H6" /><circle cx="9" cy="19" r="1.4" /><circle cx="17" cy="19" r="1.4" /></>,
  receipt: <><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" /><path d="M9 8h6M9 12h6" /></>,
  wallet: <><rect x="3" y="6" width="18" height="13" rx="3" /><path d="M3 10h18" /><circle cx="17" cy="14.5" r="1.2" /></>,
  ticket: <><path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3a2 2 0 000-4V6z" /><path d="M13 5v14" /></>,
  gear: <><circle cx="12" cy="12" r="3.2" /><path d="M12 2.5v2.4M12 19.1v2.4M4.2 4.2l1.7 1.7M18.1 18.1l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.2 19.8l1.7-1.7M18.1 5.9l1.7-1.7" /></>,
  shield: <><path d="M12 3l8 3v6c0 4.4-3.4 7.9-8 9-4.6-1.1-8-4.6-8-9V6l8-3z" /><path d="M9 12l2.2 2.2L15.5 10" /></>,
  logout: <><path d="M9 5H5v14h4" /><path d="M15 8l4 4-4 4M19 12H9" /></>,
  menu: <><path d="M3 6h18M3 12h18M3 18h18" /></>,
  home: <><path d="M4 11l8-7 8 7" /><path d="M6 10v9h12v-9" /></>
};

const Icon = ({ name }) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
    {icons[name]}
  </svg>
);

const navItems = [
  { to: '/app', label: '控制台概览', icon: 'grid', end: true },
  { to: '/app/vps', label: '我的实例', icon: 'server' },
  { to: '/app/buy', label: '购买服务器', icon: 'cart' },
  { to: '/app/orders', label: '订单管理', icon: 'receipt' },
  { to: '/app/wallet', label: '充值中心', icon: 'wallet' },
  { to: '/app/tickets', label: '工单支持', icon: 'ticket' },
  { to: '/app/settings', label: '账户设置', icon: 'gear' }
];

export default function ConsoleLayout() {
  const { user, logout, refresh } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [open, setOpen] = useState(false);

  useEffect(() => {
    setOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    refresh();
    const timer = setInterval(refresh, 30000);
    return () => clearInterval(timer);
  }, [refresh]);

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <div className="shell">
      <header className="topnav">
        <div className="topnav-left">
          <button className="icon-btn mobile-only" onClick={() => setOpen((v) => !v)} aria-label="菜单">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">{icons.menu}</svg>
          </button>
          <Link to="/app"><Brand /></Link>
          <span className="topnav-divider" />
          <span className="topnav-title">用户控制台</span>
        </div>
        <div className="topnav-right">
          <Link to="/app/wallet" className="balance-chip">余额 <b>{money(user?.balance)}</b></Link>
          <Button size="sm" onClick={() => navigate('/app/buy')}>开通服务器</Button>
          <ThemeToggle />
          <Link to="/" className="icon-btn" title="返回官网">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round">{icons.home}</svg>
          </Link>
        </div>
      </header>

      <div className="shell-body">
        {open && <div className="sidebar-mask" onClick={() => setOpen(false)} />}
        <aside className={`sidebar ${open ? 'open' : ''}`}>
          <span className="side-label">控制台</span>
          <nav className="side-nav" style={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
            {navItems.map((item) => (
              <NavLink key={item.to} to={item.to} end={item.end} className={({ isActive }) => `side-link ${isActive ? 'active' : ''}`}>
                <Icon name={item.icon} />
                {item.label}
              </NavLink>
            ))}
            {user?.role === 'admin' && (
              <>
                <span className="side-label">运营</span>
                <NavLink to="/admin" className="side-link">
                  <Icon name="shield" />
                  管理后台
                </NavLink>
              </>
            )}
          </nav>
          <div className="side-foot">
            <div className="avatar">{user?.nickname?.slice(0, 1) || '汐'}</div>
            <div className="user-mini">
              <strong>{user?.nickname}</strong>
              <span>{user?.email}</span>
            </div>
            <button className="icon-btn" title="退出登录" onClick={handleLogout} style={{ marginLeft: 'auto' }}>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round">
                {icons.logout}
              </svg>
            </button>
          </div>
        </aside>

        <main className="main">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
