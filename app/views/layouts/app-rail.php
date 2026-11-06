<?php
/** 控制台布局：图标窄栏 + 大面积内容 */
$isAdmin = !empty($adminActive);
$nav = $isAdmin ? admin_nav() : user_nav();
$active = $active ?? '';
$flashes = flash_messages();
$skin = current_skin();
$activeLabel = '控制台';
foreach ($nav as [$key, $path, $icon, $label]) {
    if ($active === $key) {
        $activeLabel = $label;
        break;
    }
}
$brandHref = url($isAdmin ? '/admin' : '/app');
require __DIR__ . '/_head.php';
?>
<body>
<div class="shell shell--rail">
    <aside class="rail">
        <a class="rail-brand" href="<?= e($brandHref) ?>" title="库洛汐">
            <svg width="26" height="26" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
        </a>
        <?php foreach ($nav as [$key, $path, $icon, $label]): ?>
            <a class="rail-link <?= $active === $key ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                <span><?= $icon ?></span><em><?= e($label) ?></em>
            </a>
        <?php endforeach; ?>
        <div class="rail-spacer"></div>
        <button class="rail-btn" type="button" data-theme-toggle title="切换主题"><span data-theme-icon>☾</span></button>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a class="rail-btn" href="<?= e(url($isAdmin ? '/app' : '/admin')) ?>" title="<?= $isAdmin ? '用户视角' : '管理后台' ?>">⇄</a>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/logout')) ?>" style="margin:0">
            <?= csrf_field() ?>
            <button class="rail-btn" type="submit" title="退出登录">⏻</button>
        </form>
    </aside>

    <div class="rail-main">
        <header class="rail-top">
            <div style="min-width:0">
                <div class="eyebrow" style="margin-bottom:2px"><?= $isAdmin ? 'ADMIN' : 'CONSOLE' ?></div>
                <h1 class="rail-title"><?= e($activeLabel) ?></h1>
            </div>
            <div class="topbar2-right">
                <?php if (!$isAdmin): ?>
                    <a href="<?= e(url('/app/wallet')) ?>" class="balance-chip">余额 <b><?= e(money((int)$user['balance'])) ?></b></a>
                <?php else: ?>
                    <span class="pill-tag pill-tag--amber">ADMIN</span>
                <?php endif; ?>
                <select class="skin-select" data-skin-select title="切换皮肤"><?= skin_options_html($skin) ?></select>
                <a class="avatar" href="<?= e(url('/app/settings')) ?>" title="<?= e($user['nickname']) ?>"><?= e(mb_substr($user['nickname'], 0, 1)) ?></a>
            </div>
        </header>

        <main class="main">
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
</div>
<script src="<?= e(asset_url('/assets/app.js')) ?>"></script>
</body>
</html>
