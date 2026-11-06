<?php
/** 控制台布局：顶部横向导航 */
$isAdmin = !empty($adminActive);
$nav = $isAdmin ? admin_nav() : user_nav();
$active = $active ?? '';
$flashes = flash_messages();
$skin = current_skin();
$brandHref = url($isAdmin ? '/admin' : '/app');
require __DIR__ . '/_head.php';
?>
<body>
<div class="shell shell--top">
    <header class="topbar2">
        <div class="topbar2-inner">
            <a href="<?= e($brandHref) ?>" class="brand">
                <svg width="26" height="26" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
                <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI</span></span>
            </a>
            <nav class="topbar2-nav">
                <?php foreach ($nav as [$key, $path, $icon, $label]): ?>
                    <a class="topbar2-link <?= $active === $key ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                        <span><?= $icon ?></span><?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="topbar2-right">
                <?php if ($isAdmin): ?>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/app')) ?>">用户视角</a>
                <?php else: ?>
                    <a href="<?= e(url('/app/wallet')) ?>" class="balance-chip">余额 <b><?= e(money((int)$user['balance'])) ?></b></a>
                <?php endif; ?>
                <button class="icon-btn" type="button" data-theme-toggle title="切换主题"><span data-theme-icon>☾</span></button>
                <select class="skin-select" data-skin-select title="切换皮肤"><?= skin_options_html($skin) ?></select>
                <form method="post" action="<?= e(url('/logout')) ?>" style="margin:0">
                    <?= csrf_field() ?>
                    <button class="icon-btn" type="submit" title="退出登录">⏻</button>
                </form>
            </div>
        </div>
    </header>

    <main class="main main--wide">
        <div class="page page--wide">
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
<script src="<?= e(asset_url('/assets/app.js')) ?>"></script>
</body>
</html>
