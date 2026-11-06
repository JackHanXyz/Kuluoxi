<?php /** 公共页面布局 */ $skin = current_skin(); ?>
<!doctype html>
<html lang="zh-CN" data-theme="light" data-skin="<?= e($skin) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? '库洛汐 Kuluoxi') ?></title>
    <meta name="description" content="库洛汐 Kuluoxi 提供高性价比的 VPS 云服务器分销：香港、东京、新加坡、洛杉矶节点，NVMe 存储，秒级开通。">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231668ff'/%3E%3Cpath d='M5 20c4-7 9-7 13 0s7 3 9-2' stroke='%23ffffff' stroke-width='2.5' fill='none' stroke-linecap='round'/%3E%3Ccircle cx='24' cy='10' r='2.5' fill='%23ffd08a'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/app.css')) ?>">
    <script>try{var t=localStorage.getItem('kuluoxi.theme');document.documentElement.dataset.theme=t==='dark'?'dark':'light';var s=localStorage.getItem('kuluoxi.skin');if(s)document.documentElement.dataset.skin=s;}catch(e){}</script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="nav">
    <div class="container nav-inner">
        <a href="<?= e(url('/')) ?>" class="brand">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
            <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI</span></span>
        </a>
        <nav class="nav-links">
            <a href="<?= e(url('/#plans')) ?>">产品套餐</a>
            <a href="<?= e(url('/#features')) ?>">核心能力</a>
            <a href="<?= e(url('/#nodes')) ?>">全球节点</a>
            <a href="<?= e(url('/#faq')) ?>">常见问题</a>
        </nav>
        <div class="nav-actions">
            <select class="skin-select" data-skin-select title="切换皮肤"><?= skin_options_html($skin) ?></select>
            <button class="icon-btn" type="button" data-theme-toggle title="切换主题" aria-label="切换主题">
                <span data-theme-icon>☾</span>
            </button>
            <?php if (!empty($user)): ?>
                <a class="btn btn--primary btn--sm" href="<?= e(url($user['role'] === 'admin' ? '/admin' : '/app')) ?>">进入控制台</a>
            <?php else: ?>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('/login')) ?>">登录</a>
                <a class="btn btn--primary btn--sm" href="<?= e(url('/register')) ?>">开始使用</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?= $content ?>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="brand">
                    <svg width="28" height="28" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
                    <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI</span></span>
                </span>
                <p>库洛汐 Kuluoxi · 深海级 VPS 云服务。以潮汐之名，为每一份业务提供稳定、可负担的算力。</p>
            </div>
            <div>
                <h4>产品</h4>
                <div class="footer-links">
                    <a href="<?= e(url('/#plans')) ?>">VPS 套餐</a>
                    <a href="<?= e(url('/#nodes')) ?>">全球节点</a>
                    <a href="<?= e(url('/register')) ?>">白标分销</a>
                    <a href="<?= e(url('/app')) ?>">控制台</a>
                </div>
            </div>
            <div>
                <h4>支持</h4>
                <div class="footer-links">
                    <a href="<?= e(url('/app/tickets')) ?>">提交工单</a>
                    <a href="<?= e(url('/#faq')) ?>">常见问题</a>
                    <a href="mailto:support@kuluoxi.io">support@kuluoxi.io</a>
                </div>
            </div>
            <div>
                <h4>关于</h4>
                <div class="footer-links">
                    <span>服务条款</span><span>隐私政策</span><span>退款政策</span>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> 库洛汐 Kuluoxi Cloud. 演示环境 · 所有支付与开通均为模拟。</span>
            <span>PHP 8 + MySQL 版本</span>
        </div>
    </div>
</footer>
<script src="<?= e(asset_url('/assets/app.js')) ?>"></script>
</body>
</html>
