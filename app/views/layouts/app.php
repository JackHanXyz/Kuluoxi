<?php
/** 控制台 / 后台布局 */
$isAdmin = !empty($adminActive);
$navUser = user_nav();
$navAdmin = admin_nav();
$active = $active ?? '';
$flashes = flash_messages();
$skin = current_skin();
?>
<!doctype html>
<html lang="zh-CN" data-theme="light" data-skin="<?= e($skin) ?>" data-layout="sidebar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? '库洛汐控制台') ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231668ff'/%3E%3Cpath d='M5 20c4-7 9-7 13 0s7 3 9-2' stroke='%23ffffff' stroke-width='2.5' fill='none' stroke-linecap='round'/%3E%3Ccircle cx='24' cy='10' r='2.5' fill='%23ffd08a'/%3E%3C/svg%3E">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/app.css')) ?>">
    <script>try{var t=localStorage.getItem('kuluoxi.theme');document.documentElement.dataset.theme=t==='dark'?'dark':'light';var s=localStorage.getItem('kuluoxi.skin');if(s)document.documentElement.dataset.skin=s;}catch(e){}</script>
</head>
<body>
<div class="shell">
    <header class="topnav">
        <div class="topnav-left">
            <button class="icon-btn mobile-only" type="button" data-sidebar-toggle aria-label="菜单">☰</button>
            <a href="<?= e(url($isAdmin ? '/admin' : '/app')) ?>" class="brand">
                <svg width="26" height="26" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
                <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI</span></span>
            </a>
            <span class="topnav-divider"></span>
            <span class="topnav-title"><?= $isAdmin ? '运营后台' : '用户控制台' ?></span>
            <?php if ($isAdmin): ?><span class="pill-tag pill-tag--amber">ADMIN</span><?php endif; ?>
        </div>
        <div class="topnav-right">
            <?php if ($isAdmin): ?>
                <a href="<?= e(url('/app')) ?>" class="btn btn--ghost btn--sm">用户视角</a>
                <a href="<?= e(url('/admin/tickets')) ?>" class="btn btn--primary btn--sm">处理工单</a>
            <?php else: ?>
                <a href="<?= e(url('/app/wallet')) ?>" class="balance-chip">余额 <b><?= e(money((int)$user['balance'])) ?></b></a>
                <a href="<?= e(url('/app/buy')) ?>" class="btn btn--primary btn--sm">开通服务器</a>
            <?php endif; ?>
            <button class="icon-btn" type="button" data-theme-toggle title="切换主题" aria-label="切换主题"><span data-theme-icon>☾</span></button>
            <select class="skin-select" data-skin-select title="切换皮肤"><?= skin_options_html($skin) ?></select>
            <a href="<?= e(url('/')) ?>" class="icon-btn" title="返回官网">⌂</a>
        </div>
    </header>

    <div class="shell-body">
        <div class="sidebar-mask" data-sidebar-mask></div>
        <aside class="sidebar" data-sidebar>
            <?php if ($isAdmin): ?>
                <span class="side-label">运营中心</span>
                <?php foreach ($navAdmin as [$key, $path, $icon, $label]): ?>
                    <a class="side-link <?= $active === $key ? 'active' : '' ?>" href="<?= e(url($path)) ?>"><span style="width:16px;text-align:center"><?= $icon ?></span><?= e($label) ?></a>
                <?php endforeach; ?>
                <span class="side-label">客户侧</span>
                <a class="side-link" href="<?= e(url('/app')) ?>"><span style="width:16px;text-align:center">↗</span>用户控制台</a>
                <a class="side-link" href="<?= e(url('/')) ?>"><span style="width:16px;text-align:center">⌂</span>返回官网</a>
            <?php else: ?>
                <span class="side-label">控制台</span>
                <?php foreach ($navUser as [$key, $path, $icon, $label]): ?>
                    <a class="side-link <?= $active === $key ? 'active' : '' ?>" href="<?= e(url($path)) ?>"><span style="width:16px;text-align:center"><?= $icon ?></span><?= e($label) ?></a>
                <?php endforeach; ?>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <span class="side-label">运营</span>
                    <a class="side-link" href="<?= e(url('/admin')) ?>"><span style="width:16px;text-align:center">◈</span>管理后台</a>
                <?php endif; ?>
            <?php endif; ?>
            <div class="side-foot">
                <div class="avatar"><?= e(mb_substr($user['nickname'], 0, 1)) ?></div>
                <div class="user-mini">
                    <strong><?= e($user['nickname']) ?></strong>
                    <span><?= e($user['email']) ?></span>
                </div>
                <form method="post" action="<?= e(url('/logout')) ?>" style="margin:0">
                    <?= csrf_field() ?>
                    <button class="icon-btn" title="退出登录" type="submit">⏻</button>
                </form>
            </div>
        </aside>

        <main class="main">
            <div class="page">
                <?php if ($flashes): ?>
                    <div class="flash-stack">
                        <?php foreach ($flashes as $f): ?>
                            <div class="flash flash--<?= e($f['type']) ?>"><span><?= e($f['message']) ?></span><button type="button" data-dismiss>✕</button></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </main>
    </div>
</div>
<script src="<?= e(asset_url('/assets/app.js')) ?>"></script>
</body>
</html>
