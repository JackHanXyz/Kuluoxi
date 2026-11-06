<?php /** 登录 / 注册布局 */ ?>
<!doctype html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? '库洛汐') ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231668ff'/%3E%3Cpath d='M5 20c4-7 9-7 13 0s7 3 9-2' stroke='%23ffffff' stroke-width='2.5' fill='none' stroke-linecap='round'/%3E%3Ccircle cx='24' cy='10' r='2.5' fill='%23ffd08a'/%3E%3C/svg%3E">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>">
    <script>try{var t=localStorage.getItem('kuluoxi.theme');document.documentElement.dataset.theme=t==='dark'?'dark':'light';}catch(e){}</script>
</head>
<body>
<div class="auth-page">
    <div class="auth-visual">
        <a href="<?= e(url('/')) ?>" class="auth-back">← 返回官网</a>
        <a href="<?= e(url('/')) ?>" class="brand">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
            <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI</span></span>
        </a>
        <div style="position:relative;z-index:1">
            <span class="eyebrow">KULUOXI CLOUD</span>
            <h2>深海的算力，<br><span class="grad">只为稳定的你</span></h2>
            <p>登录库洛汐控制台，管理你的实例、账单与工单。所有节点状态实时同步，自动开通。</p>
        </div>
        <div class="auth-quote">
            <b>「上线三个季度，零停机迁移。」</b>
            我们用它承载了 40 多个客户的站点，控制台和自动开通是真的省心。—— 潮汐工作室
        </div>
    </div>
    <div class="auth-main">
        <div class="auth-card">
            <?= $content ?>
        </div>
    </div>
    <button class="icon-btn" type="button" data-theme-toggle title="切换主题" style="position:fixed;top:18px;right:18px;z-index:5">
        <span data-theme-icon>☾</span>
    </button>
</div>
<script src="<?= e(url('/assets/app.js')) ?>"></script>
</body>
</html>
