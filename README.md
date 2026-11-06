# 库洛汐 Kuluoxi · PHP 全栈版

深海级 VPS 分销与云服务器运营系统。原生 PHP + MySQL 实现，无框架依赖，内置傻瓜式安装向导、多套大厂风格皮肤与四种控制台布局，支持易支付 / 码支付，并提供魔方财务 / 魔方云上游适配层。

> 本仓库 `php` 分支为 PHP 重写版；`main` 分支为 Node.js + React 原版。

## 功能

**官网**
- 深色全幅 Hero + 终端动效演示
- 套餐列表、节点负载与延迟、FAQ、多套皮肤切换

**用户控制台**
- 概览：余额、实例状态、最近订单、公告
- 实例：开机 / 关机 / 重启 / 重装系统 / 重命名 / 续费 / 销毁
- 购买：套餐 → 节点 → 周期系统 → 支付，金额实时联动
- 订单、钱包充值、工单对话、账户设置

**运营后台**
- 运营概览：营收趋势、套餐销售分布、节点负载、最新订单与工单
- 套餐 / 节点 / 订单 / 用户 / 实例 / 工单管理
- 系统设置：站点信息、默认皮肤、控制台布局、支付网关、上游驱动

**基础设施**
- 傻瓜式安装向导 `/install`（环境检测 → 数据库 → 管理员 → 完成，自动生成 `config.php` 与 `install.lock`）
- 多皮肤（6 套）× 多布局（4 套）
- 易支付（彩虹易支付）与码支付：MD5 签名、跳转收银台、异步回调结算
- 上游 Provider 适配层：`mock` / `mofang_finance`（魔方财务）/ `mofang_cloud`（魔方云）
- CSRF 防护、`password_hash` 密码、PDO 预处理、事务化余额与开通

## 技术栈

- PHP 8.0+（原生，无框架）
- MySQL 5.6+ / 8.0（InnoDB、utf8mb4）
- 原生 HTML/CSS/JS 服务端渲染，无构建步骤

## 目录结构

```
kuluoxi-php/
├── config.example.php      # 配置模板
├── bin/
│   ├── install.php         # 命令行建表 + 演示数据
│   └── migrate.php         # 增量迁移（新增表 / 默认设置）
├── public/
│   ├── index.php           # 入口与路由
│   ├── install.php         # 傻瓜式安装向导
│   ├── router.php          # PHP 内置服务器路由
│   └── assets/             # app.css / app.js
└── app/
    ├── bootstrap.php       # 配置、会话、DB、路由辅助函数
    ├── database.php        # 表结构与种子数据
    ├── helpers.php         # 通用工具
    ├── settings.php        # 设置、皮肤、布局、导航
    ├── payments.php        # 易支付 / 码支付
    ├── providers.php       # 上游适配层
    ├── controllers/        # public / auth / console / admin / payments
    └── views/              # 布局、页面、后台
```

## 快速开始

### 方式一：安装向导（推荐）

1. 将项目部署到 Web 根目录（PHP 8.0+，启用 `pdo_mysql`、`mbstring`，建议 `curl`）
2. 浏览器访问 `/install`
3. 按提示填写数据库（不存在会自动建库）、站点名与管理员账号
4. 完成后自动生成 `config.php` 与 `install.lock`，直接进入站点

重装：删除根目录 `install.lock` 后刷新 `/install`。

### 方式二：命令行

```bash
cp config.example.php config.php   # 填写数据库信息
php bin/install.php                # 建表 + 演示数据
php bin/migrate.php                # 后续新增表时执行
```

### 本地启动

```bash
php -S 127.0.0.1:8080 -t public public/router.php
```

### 演示账号

| 角色 | 邮箱 | 密码 |
| --- | --- | --- |
| 管理员 | `admin@kuluoxi.io` | `Admin@123456` |
| 用户 | `demo@kuluoxi.io` | `Demo@123456` |

> 演示数据仅在执行 `seed_data()` / 向导勾选「演示数据」时写入。

## 配置说明

全部配置位于后台 **系统设置**：

- **站点与外观**：站点名、默认皮肤（6 套）、控制台布局（侧边栏 / 顶部导航 / 图标窄栏 / 云控制台）
- **支付网关**：策略（自动 / 强制易支付 / 强制码支付）、易支付 `网关 + PID + KEY`、码支付 `网关 + 商户 ID + 密钥 + pay_id`
  - 异步回调：`/pay/notify/epay`、`/pay/notify/codepay`
  - 未配置网关时自动回落演示支付
- **上游开通**：`mock`（本地模拟）/ 魔方财务 / 魔方云，填 API 地址、API Key 与默认产品 ID
  - 下发动作：`create / suspend / unsuspend / reboot / reinstall / destroy`
  - 未配置或调用失败会自动回落演示开通，不阻断下单

## 安全提示

- `config.php` 与 `install.lock` 已在 `.gitignore` 中排除，请勿提交真实数据库密码
- 生产环境建议关闭 `app.debug`、启用 HTTPS，并将站点根目录指向 `public/`
- 支付回调务必使用可公网访问的域名（系统按当前 Host 生成绝对回调地址）

## 许可证

[MIT](LICENSE)
