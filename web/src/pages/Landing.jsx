import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api, money } from '../lib/api.js';
import { Brand, Button, Badge, Progress, ThemeToggle } from '../components/ui.jsx';
import { useAuth } from '../context/AuthContext.jsx';

const FEATURES = [
  { icon: '◈', title: 'NVMe 全闪存储', desc: '企业级 PCIe 4.0 NVMe 阵列，随机读写比传统 SATA SSD 快 4 倍，建站与数据库启动更快。' },
  { icon: '⚡', title: '秒级自动开通', desc: '支付完成即自动创建实例并下发 root 密码，平均交付时间 6 秒，无需人工等待。' },
  { icon: '◎', title: '多地域低延迟', desc: '香港、东京、新加坡、洛杉矶骨干节点，智能调度线路，东南亚平均延迟 38ms。' },
  { icon: '⟲', title: '一键重装与快照', desc: '支持 Ubuntu / Debian / AlmaLinux / Windows 等镜像，控制台随时重装、回滚。' },
  { icon: '⛨', title: '余额自动续费', desc: '到期前自动从余额续期，提前 3 天提醒，避免业务因为忘记续费而中断。' },
  { icon: '⌘', title: '开放 API 与白标', desc: '完整的 REST API 与分销能力，合作伙伴可自定义品牌、价格与结算方式。' }
];

const FAQS = [
  { q: '开通后多久可以使用？', a: '支付成功后系统会自动创建实例并下发 IP 与 root 密码，通常在 6 秒内完成部署。若状态长时间停留在「开通中」，可在控制台提交工单，支持团队会立即介入。' },
  { q: '如何重装系统或重置密码？', a: '进入实例详情页点击「重装系统」，选择镜像后确认即可。重装会清空磁盘数据并生成新的 root 密码，请提前备份重要文件。' },
  { q: '流量用完了会怎样？', a: '套餐内流量用尽后实例会被限速至 10Mbps，不会停机；可在控制台升级套餐或购买额外流量包。' },
  { q: '支持退款吗？', a: '新购实例 24 小时内且流量使用低于 5GB，可提交工单申请按比例退款。已消耗的资源、初装费与特价机型不支持退款。' },
  { q: '可以分销或白标吗？', a: '可以。库洛汐提供节点级的白标方案与 REST API，合作伙伴可自定义品牌、套餐与结算价，详情请联系商务。' }
];

function ConsolePreview() {
  const rows = [
    { name: 'tide-hk-01', node: '香港 · HK-01', status: 'running' },
    { name: 'tide-tyo-02', node: '东京 · TYO-01', status: 'stopped' },
    { name: 'tide-sg-03', node: '新加坡 · SGP-01', status: 'running' }
  ];
  return (
    <div className="hero-panel">
      <div className="preview">
        <div className="preview-bar">
          <span className="live-dot" />
          <b>实例概览</b>
          <span className="preview-live">节点已同步</span>
        </div>
        <div className="preview-body">
          {rows.map((r) => (
            <div key={r.name} className="preview-row">
              <div>
                <b>{r.name}</b>
                <br />
                <small>{r.node}</small>
              </div>
              <div>
                <small>公网 IP</small>
                <br />
                <span className="mono" style={{ fontSize: 12 }}>10.12.8.31</span>
              </div>
              <Badge status={r.status} />
            </div>
          ))}
        </div>
        <div className="preview-foot">
          <div className="preview-stat"><span>账户余额</span><b>¥1,280.00</b></div>
          <div className="preview-stat"><span>本月流量</span><b>1.5 TB</b></div>
          <div className="preview-stat"><span>可用率</span><b>99.99%</b></div>
        </div>
      </div>
      <div className="float float--1"><b>6s</b> 平均开通</div>
      <div className="float float--2"><b>38ms</b> 平均延迟</div>
      <div className="float float--3">香港 · 东京 · 新加坡</div>
    </div>
  );
}

export default function Landing() {
  const [plans, setPlans] = useState([]);
  const [nodes, setNodes] = useState([]);
  const [stats, setStats] = useState(null);
  const [scrolled, setScrolled] = useState(false);
  const [openFaq, setOpenFaq] = useState(0);
  const { user } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    Promise.all([api('/plans'), api('/nodes'), api('/stats')])
      .then(([p, n, s]) => { setPlans(p); setNodes(n); setStats(s); })
      .catch(() => {});
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const ticker = [...nodes, ...nodes].map((n, i) => (
    <span key={`${n.id}-${i}`}><b>{n.code.toUpperCase()}</b> {n.name} · 延迟 {28 + (n.id * 7) % 40}ms</span>
  ));

  return (
    <div className="landing">
      <header className={`nav ${scrolled ? 'scrolled' : ''}`}>
        <div className="container nav-inner">
          <Link to="/"><Brand /></Link>
          <nav className="nav-links">
            <a href="#plans">产品套餐</a>
            <a href="#features">核心能力</a>
            <a href="#nodes">全球节点</a>
            <a href="#faq">常见问题</a>
          </nav>
          <div className="nav-actions">
            <ThemeToggle />
            {user ? (
              <Link to={user.role === 'admin' ? '/admin' : '/app'} className="btn btn--primary btn--sm">进入控制台</Link>
            ) : (
              <>
                <Link to="/login" className="btn btn--ghost btn--sm">登录</Link>
                <Link to="/register" className="btn btn--primary btn--sm">开始使用</Link>
              </>
            )}
          </div>
        </div>
      </header>

      <section className="hero">
        <div className="container hero-inner">
          <div className="hero-copy">
            <div className="hero-kicker">
              <b><span className="live-dot" />ABYSSAL COMPUTE</b>
              <em>库洛汐 · 深海级算力</em>
            </div>
            <h1>
              让算力像潮汐一样<br />
              <span className="grad">自由、稳定、触手可及</span>
            </h1>
            <p className="hero-lead">
              库洛汐 Kuluoxi 提供高性价比的 VPS 云服务器分销。NVMe 全闪存储、多地域智能调度、秒级自动开通，
              为独立开发者、建站团队与分销伙伴提供可靠的深海级算力底座。
            </p>
            <div className="cta-row">
              <Button size="lg" onClick={() => navigate(user ? '/app/buy' : '/register')}>立即开通 · 最快 6 秒</Button>
              <Button size="lg" variant="ghost" onClick={() => document.getElementById('plans')?.scrollIntoView({ behavior: 'smooth' })}>查看套餐价格</Button>
            </div>
            <div className="trust">
              <div className="trust-item"><strong className="num">{stats?.customers || 0}+</strong><span>活跃客户</span></div>
              <div className="trust-item"><strong className="num">{stats?.instances || 0}</strong><span>在线实例</span></div>
              <div className="trust-item"><strong className="num">{stats?.nodes || 0}</strong><span>全球节点</span></div>
              <div className="trust-item"><strong className="num">{stats?.uptime || '99.99%'}</strong><span>服务可用率</span></div>
            </div>
          </div>
          <ConsolePreview />
        </div>
      </section>

      <div className="region-ticker">
        <div className="ticker-track">{ticker}{ticker}</div>
      </div>

      <section className="section" id="plans">
        <div className="container">
          <div className="section-head">
            <span className="eyebrow">PRICING</span>
            <h2>按需而变，按潮汐计量</h2>
            <p>六档配置覆盖从个人博客到独享整机，所有套餐均含 NVMe 存储、DDoS 防护与工单支持。支持余额自动续费。</p>
          </div>
          <div className="plan-grid">
            {plans.map((p) => (
              <div key={p.id} className={`plan ${p.highlight ? 'plan--hot' : ''}`}>
                {p.highlight ? <span className="plan-tag">MOST POPULAR</span> : null}
                <div className="plan-name">{p.name}</div>
                <div className="plan-tagline">{p.tagline}</div>
                <div className="plan-price">
                  <b>{money(p.price)}</b><em>/ 月</em>
                </div>
                <ul className="spec-list">
                  <li><span>CPU</span><b>{p.cpu} 核</b></li>
                  <li><span>内存</span><b>{p.memoryMb / 1024} GB</b></li>
                  <li><span>NVMe 硬盘</span><b>{p.diskGb} GB</b></li>
                  <li><span>月流量</span><b>{p.bandwidthGb ? `${p.bandwidthGb} GB` : '不限'}</b></li>
                  <li><span>带宽</span><b>{p.portSpeed}</b></li>
                </ul>
                <Button block variant={p.highlight ? 'primary' : 'ghost'} onClick={() => navigate(user ? `/app/buy?plan=${p.id}` : '/register')}>
                  {p.stock > 0 ? '选择该套餐' : '暂时售罄'}
                </Button>
                <p className="small muted" style={{ marginTop: 10, textAlign: 'center' }}>剩余库存 {p.stock} 台</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section" id="features" style={{ paddingTop: 20 }}>
        <div className="container">
          <div className="section-head">
            <span className="eyebrow">CAPABILITIES</span>
            <h2>不是所有 VPS，都叫库洛汐</h2>
            <p>从资源调度到账单清算，我们把复杂的部分留在深海，把简单稳定的体验留给你。</p>
          </div>
          <div className="feature-grid">
            {FEATURES.map((f, i) => (
              <div key={f.title} className="feature">
                <span className="feature-num">0{i + 1}</span>
                <div className="feature-icon">{f.icon}</div>
                <h3>{f.title}</h3>
                <p>{f.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="band">
        <div className="container band-grid">
          <div className="band-item"><b>{(stats?.trafficTb || 0)} TB</b><span>月流量承载</span></div>
          <div className="band-item"><b>{(stats?.responseMs || 38)} ms</b><span>平均响应延迟</span></div>
          <div className="band-item"><b>{stats?.regions || 5}</b><span>覆盖国家与地区</span></div>
          <div className="band-item"><b>7×24</b><span>技术支持响应</span></div>
        </div>
      </section>

      <section className="section" id="nodes">
        <div className="container">
          <div className="section-head">
            <span className="eyebrow">NETWORK</span>
            <h2>全球节点，就近接入</h2>
            <p>所有节点均接入骨干线路与 DDoS 清洗，实时展示机架负载。维护中的节点会在购买时自动禁用。</p>
          </div>
          <div className="node-grid">
            {nodes.map((n) => (
              <div key={n.id} className="node-row">
                <span className="node-flag">{n.flag}</span>
                <div className="node-meta">
                  <strong>{n.name}</strong>
                  <span>{n.code.toUpperCase()} · {n.country}</span>
                </div>
                <div className="node-load">
                  <span>{n.status === 'online' ? `负载 ${n.load}%` : '维护中'}</span>
                  <Progress value={n.load} tone={n.load > 80 ? 'danger' : n.load > 60 ? 'warn' : ''} />
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section" id="faq" style={{ paddingTop: 20 }}>
        <div className="container">
          <div className="section-head">
            <span className="eyebrow">FAQ</span>
            <h2>关于库洛汐的常见问题</h2>
          </div>
          <div className="faq-list">
            {FAQS.map((f, i) => (
              <div key={f.q} className={`faq-item ${openFaq === i ? 'open' : ''}`}>
                <button className="faq-q" onClick={() => setOpenFaq(openFaq === i ? -1 : i)}>
                  {f.q}<i>+</i>
                </button>
                <div className="faq-a"><p>{f.a}</p></div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <div className="container">
        <div className="cta-band">
          <div>
            <span className="eyebrow">GET STARTED</span>
            <h2>准备好让业务随潮汐而动了？</h2>
            <p>注册即送 ¥20 体验金，开通无需备案，支持 24 小时内无理由退款。</p>
          </div>
          <div className="btn-row">
            <Button size="lg" onClick={() => navigate(user ? '/app/buy' : '/register')}>{user ? '去开通实例' : '免费注册'}</Button>
            <Button size="lg" variant="ghost" onClick={() => navigate('/login')}>已有账号登录</Button>
          </div>
        </div>
      </div>

      <footer className="footer">
        <div className="container">
          <div className="footer-grid">
            <div className="footer-brand">
              <Brand />
              <p>库洛汐 Kuluoxi · 深海级 VPS 云服务。以潮汐之名，为每一份业务提供稳定、可负担的算力。</p>
            </div>
            <div>
              <h4>产品</h4>
              <div className="footer-links">
                <a href="#plans">VPS 套餐</a>
                <a href="#nodes">全球节点</a>
                <Link to="/register">白标分销</Link>
                <Link to="/app/buy">控制台</Link>
              </div>
            </div>
            <div>
              <h4>支持</h4>
              <div className="footer-links">
                <Link to="/app/tickets">提交工单</Link>
                <a href="#faq">常见问题</a>
                <a href="mailto:support@kuluoxi.io">support@kuluoxi.io</a>
              </div>
            </div>
            <div>
              <h4>关于</h4>
              <div className="footer-links">
                <span>服务条款</span>
                <span>隐私政策</span>
                <span>退款政策</span>
              </div>
            </div>
          </div>
          <div className="footer-bottom">
            <span>© 2026 库洛汐 Kuluoxi Cloud. 演示环境 · 所有支付与开通均为模拟。</span>
            <span>深海的算力 · 稳定的潮汐</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
