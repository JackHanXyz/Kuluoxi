import { createContext, useContext, useEffect, useRef, useState, useCallback } from 'react';
import { STATUS_TEXT } from '../lib/api.js';
import { useTheme } from '../context/ThemeContext.jsx';

/* ---------------------------------- 品牌 ---------------------------------- */
export function Brand({ compact = false, to }) {
  return (
    <span className={`brand ${compact ? 'brand--compact' : ''}`}>
      <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true" className="brand-mark">
        <rect width="32" height="32" rx="8" fill="var(--primary)" />
        <path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#ffffff" strokeWidth="2.4" strokeLinecap="round" opacity="0.92" />
        <circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a" />
      </svg>
      <span className="brand-text">
        <span className="brand-cn">库洛汐</span>
        {!compact && <span className="brand-en">KULUOXI</span>}
      </span>
    </span>
  );
}

/* ---------------------------------- 主题切换 ---------------------------------- */
export function ThemeToggle({ className = '' }) {
  const { theme, toggle } = useTheme();
  return (
    <button
      type="button"
      className={`icon-btn ${className}`}
      onClick={toggle}
      title={theme === 'light' ? '切换到深色模式' : '切换到浅色模式'}
      aria-label="切换主题"
    >
      {theme === 'light' ? (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round">
          <path d="M21 13.2A8.6 8.6 0 1111 3.1a6.9 6.9 0 0010 10.1z" />
        </svg>
      ) : (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round">
          <circle cx="12" cy="12" r="4" />
          <path d="M12 2.5v2.2M12 19.3v2.2M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6" />
        </svg>
      )}
    </button>
  );
}

/* ---------------------------------- 按钮 ---------------------------------- */
export function Button({ variant = 'primary', size, className = '', children, ...rest }) {
  return (
    <button className={`btn btn--${variant} ${size ? `btn--${size}` : ''} ${className}`} {...rest}>
      {children}
    </button>
  );
}

export function Card({ className = '', children, ...rest }) {
  return (
    <div className={`card ${className}`} {...rest}>
      {children}
    </div>
  );
}

export function Badge({ status, children }) {
  return <span className={`badge badge--${status || 'default'}`}><i />{children || STATUS_TEXT[status] || status}</span>;
}

export function Field({ label, hint, children, className = '' }) {
  return (
    <label className={`field ${className}`}>
      {label && <span className="field-label">{label}{hint && <em>{hint}</em>}</span>}
      {children}
    </label>
  );
}

export function Input({ className = '', ...rest }) {
  return <input className={`input ${className}`} {...rest} />;
}

export function Select({ className = '', children, ...rest }) {
  return <select className={`input select ${className}`} {...rest}>{children}</select>;
}

export function Textarea({ className = '', ...rest }) {
  return <textarea className={`input textarea ${className}`} {...rest} />;
}

export function Modal({ open, onClose, title, children, footer, width = 520 }) {
  useEffect(() => {
    if (!open) return;
    const onKey = (e) => e.key === 'Escape' && onClose?.();
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) return null;
  return (
    <div className="modal-mask" onMouseDown={(e) => e.target === e.currentTarget && onClose?.()}>
      <div className="modal" style={{ maxWidth: width }}>
        <div className="modal-head">
          <h3>{title}</h3>
          <button className="icon-btn" onClick={onClose} aria-label="关闭">✕</button>
        </div>
        <div className="modal-body">{children}</div>
        {footer && <div className="modal-foot">{footer}</div>}
      </div>
    </div>
  );
}

export function Empty({ title = '暂无数据', desc, action }) {
  return (
    <div className="empty">
      <div className="empty-art">◌</div>
      <strong>{title}</strong>
      {desc && <p>{desc}</p>}
      {action}
    </div>
  );
}

export function Loader({ label = '加载中' }) {
  return <div className="loader"><span className="loader-ring" />{label}…</div>;
}

export function Stat({ label, value, hint, tone = 'default' }) {
  return (
    <div className={`stat stat--${tone}`}>
      <span className="stat-label">{label}</span>
      <strong className="stat-value num">{value}</strong>
      {hint && <span className="stat-hint">{hint}</span>}
    </div>
  );
}

export function Progress({ value, tone }) {
  return (
    <span className="progress">
      <span style={{ width: `${Math.min(100, Math.max(0, value))}%` }} className={tone || ''} />
    </span>
  );
}

export function Copy({ text, label = '复制' }) {
  const [done, setDone] = useState(false);
  const timer = useRef(null);
  const copy = async () => {
    try {
      await navigator.clipboard.writeText(text);
    } catch {
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
    }
    setDone(true);
    clearTimeout(timer.current);
    timer.current = setTimeout(() => setDone(false), 1600);
  };
  return (
    <button type="button" className="copy-btn" onClick={copy}>{done ? '已复制' : label}</button>
  );
}

/* ---------------------------------- Toast ---------------------------------- */
const ToastContext = createContext(null);

export function ToastProvider({ children }) {
  const [items, setItems] = useState([]);
  const push = useCallback((message, type = 'success') => {
    const id = Math.random().toString(36).slice(2);
    setItems((prev) => [...prev, { id, message, type }]);
    setTimeout(() => setItems((prev) => prev.filter((i) => i.id !== id)), 3200);
  }, []);
  return (
    <ToastContext.Provider value={push}>
      {children}
      <div className="toasts">
        {items.map((t) => (
          <div key={t.id} className={`toast toast--${t.type}`}>
            <span className="toast-dot" />{t.message}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export const useToast = () => useContext(ToastContext);
