import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { useAuth } from './context/AuthContext.jsx';
import { Loader } from './components/ui.jsx';
import Landing from './pages/Landing.jsx';
import { Login, Register } from './pages/Auth.jsx';
import ConsoleLayout from './pages/app/ConsoleLayout.jsx';
import Overview from './pages/app/Overview.jsx';
import Instances from './pages/app/Instances.jsx';
import InstanceDetail from './pages/app/InstanceDetail.jsx';
import Buy from './pages/app/Buy.jsx';
import Orders from './pages/app/Orders.jsx';
import Wallet from './pages/app/Wallet.jsx';
import Tickets from './pages/app/Tickets.jsx';
import TicketDetail from './pages/app/TicketDetail.jsx';
import Settings from './pages/app/Settings.jsx';
import AdminLayout from './pages/admin/AdminLayout.jsx';
import AdminOverview from './pages/admin/AdminOverview.jsx';
import AdminPlans from './pages/admin/AdminPlans.jsx';
import AdminNodes from './pages/admin/AdminNodes.jsx';
import AdminOrders from './pages/admin/AdminOrders.jsx';
import AdminUsers from './pages/admin/AdminUsers.jsx';
import AdminInstances from './pages/admin/AdminInstances.jsx';
import AdminTickets from './pages/admin/AdminTickets.jsx';

function Guard({ admin = false }) {
  const { user, loading } = useAuth();
  const location = useLocation();
  if (loading) {
    return <div style={{ minHeight: '100vh', display: 'grid', placeItems: 'center' }}><Loader label="验证登录状态" /></div>;
  }
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  if (admin && user.role !== 'admin') return <Navigate to="/app" replace />;
  return <Outlet />;
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Landing />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />

      <Route element={<Guard />}>
        <Route path="/app" element={<ConsoleLayout />}>
          <Route index element={<Overview />} />
          <Route path="vps" element={<Instances />} />
          <Route path="vps/:id" element={<InstanceDetail />} />
          <Route path="buy" element={<Buy />} />
          <Route path="orders" element={<Orders />} />
          <Route path="wallet" element={<Wallet />} />
          <Route path="tickets" element={<Tickets />} />
          <Route path="tickets/:id" element={<TicketDetail />} />
          <Route path="settings" element={<Settings />} />
        </Route>
      </Route>

      <Route element={<Guard admin />}>
        <Route path="/admin" element={<AdminLayout />}>
          <Route index element={<AdminOverview />} />
          <Route path="plans" element={<AdminPlans />} />
          <Route path="nodes" element={<AdminNodes />} />
          <Route path="orders" element={<AdminOrders />} />
          <Route path="users" element={<AdminUsers />} />
          <Route path="instances" element={<AdminInstances />} />
          <Route path="tickets" element={<AdminTickets />} />
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
