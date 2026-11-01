# 库洛汐 Kuluoxi

> 深海级 VPS 分销与云服务器运营系统 · 全栈可运行 MVP

库洛汐是一套开箱即用的 VPS 分销平台，包含官网、用户控制台与运营后台。支付与虚拟化默认使用模拟实现，不依赖任何第三方服务即可本地完整跑通「选购 → 支付 → 自动开通 → 管理」全流程。

## 功能

**官网**
- 套餐展示、全球节点负载、常见问题、公告
- 浅色 / 深色主题切换（持久化到 localStorage）

**用户控制台**
- 账户概览：余额、实例、待支付订单、工单、流量用量
- 实例管理：开机 / 关机 / 重启 / 重装系统 / 重命名 / 续费 / 销毁，展示 IP 与 root 密码
- 购买流程：套餐 → 节点 → 周期（1/3/6/12 个月）→ 系统镜像 → 支付
- 订单管理：补付、取消、查看状态
- 钱包：模拟充值、资金流水
- 工单：提交、对话、关闭
- 账户设置：昵称、密码、公告

**运营后台**
- 营收概览：累计 / 近 30 天营收、14 天趋势图、套餐销售分布、节点负载
- 套餐 CRUD（上下架、库存、价格、推荐标记）
- 节点 CRUD（容量、负载、维护状态、Provider 配置）
- 订单、用户（余额调整、冻结）、实例（强制停机/恢复/重装/销毁）、工单回复

**后端**
- Node.js + Express + Node 内置 SQLite（零原生依赖，无需编译）
- HMAC-SHA256 签名令牌、scrypt 密码哈希
- 钱包流水、库存扣减、到期自动停机
- Mock Provider：模拟 6 秒自动开通，生成 IP 与 root 密码；预留 Proxmox / SolusVM 适配位

## 技术栈

| 层 | 技术 |
| --- | --- |
| 前端 | React 18 + Vite 6 + React Router 6 |
| 后端 | Node.js (>= 22.5) + Express 4 + `node:sqlite` |
| 样式 | 纯 CSS 变量主题（浅色 / 深色），无 UI 框架 |
| 数据 | SQLite（`server/data/kuluoxi.db`，首次启动自动建表与写入演示数据） |

## 快速开始

```bash
npm run setup   # 安装前后端依赖
npm run dev     # 同时启动 API (3001) 与前端 (5173)
```

打开 http://localhost:5173 即可。

生产模式（单端口托管构建产物）：

```bash
npm run build
npm start       # http://localhost:3001
```

### 演示账号

| 角色 | 邮箱 | 密码 |
| --- | --- | --- |
| 用户 | `demo@kuluoxi.io` | `Demo@123456` |
| 管理员 | `admin@kuluoxi.io` | `Admin@123456` |

演示数据包含 6 个套餐、6 个节点、3 台实例与历史订单/工单。删除 `server/data/kuluoxi.db` 重启即可重置。

## 目录结构

```
kuluoxi/
├── server/
│   ├── src/
│   │   ├── index.js          # Express 入口、静态托管、到期巡检
│   │   ├── db.js             # 表结构 + 种子数据
│   │   ├── auth.js           # 鉴权中间件
│   │   ├── providers.js      # 虚拟化适配层（Mock）
│   │   ├── lib/              # 加密、序列化
│   │   └── routes/           # public / auth / user / admin
│   └── data/                 # SQLite 数据文件（已 gitignore）
├── web/
│   └── src/
│       ├── pages/            # 官网、控制台、管理后台
│       ├── components/       # UI 组件库
│       ├── context/          # 登录态、主题
│       ├── lib/              # API 客户端与格式化
│       └── styles/global.css # 设计系统
├── dev.mjs                   # 并行启动脚本
└── package.json
```

## 环境变量

| 变量 | 默认值 | 说明 |
| --- | --- | --- |
| `PORT` | `3001` | API 端口 |
| `KULUOXI_SECRET` | 内置开发密钥 | 令牌签名密钥，**生产必须替换** |

## 接入真实服务

1. **支付**：在 `server/src/routes/user.js` 的 `POST /orders/:id/pay` 中替换模拟分支，接入支付宝 / 微信 / Stripe 并校验异步回调签名。
2. **虚拟化**：在 `server/src/providers.js` 实现 `createServer / power / reinstall / destroy`，按 `node.provider` 分发到 Proxmox VE 或 SolusVM。
3. **生产部署**：建议使用 Nginx 反向代理 3001，开启 HTTPS，设置 `KULUOXI_SECRET` 并定期备份 SQLite 文件。

## 免责声明

本项目为演示性质的全栈模板，支付、实名、发票等均为模拟逻辑，请勿直接用于生产收款。上线前请自行完成安全审计、合规备案与密钥管理。

## License

[MIT](./LICENSE) © 2026 JackHanXyz
