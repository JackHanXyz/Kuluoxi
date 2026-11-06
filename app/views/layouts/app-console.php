<?php
/** 控制台布局：大厂云控制台（深色顶栏 + 分组菜单 + 面包屑） */
$isAdmin = !empty($adminActive);
$groups = console_nav_groups($isAdmin);
$active = $active ?? '';
$flashes = flash_messages();
$skin = current_skin();
$activeLabel = '控制台';
$activePath = $isAdmin ? '/admin' : '/app';
foreach ($groups as $items) {
    foreach ($items as [$key, $path, $icon, $label]) {
        if ($active === $key) {
            $activeLabel = $label;
            $activePath = $path;
        }
    }
}
require __DIR__ . '/_head.php';
?>
<body>
<div class="shell shell--console">
    <header class="console-top">
        <button class="icon-btn on-dark mobile-only" type="button" data-sidebar-toggle aria-label="菜单">☰</button>
        <a href="<?= e(url($isAdmin ? '/admin' : '/app')) ?>" class="brand brand--light">
            <svg width="24" height="24" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
            <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI CLOUD</span></span>
        </a>
        <?php if ($isAdmin): ?><span class="console-tag">运营后台</span><?php else: ?><span class="console-tag">用户控制台</span><?php endif; ?>

        <div class="console-right">
            <?php if (!$isAdmin): ?>
                <a href="<?= e(url('/app/wallet')) ?>" class="console-balance">余额 <b><?= e(money((int)$user['balance'])) ?></b></a>
            <?php endif; ?>
            <a class="console-icon" href="<?= e(url('/')) ?>" title="返回官网">⌂</a>
            <button class="console-icon" type="button" data-theme-toggle title="切换主题" style="border:none;background:none"><span data-theme-icon>☾</span></button>
            <select class="skin-select skin-select--dark" data-skin-select title="切换皮肤"><?= skin_options_html($skin) ?></select>
            <div class="console-user">
                <div class="avatar" style="width:28px;height:28px;font-size:12px"><?= e(mb_substr($user['nickname'], 0, 1)) ?></div>
                <div class="user-mini"><strong style="color:#fff"><?= e($user['nickname']) ?></strong><span style="color:rgba(255,255,255,.6)"><?= e($user['email']) ?></span></div>
            </div>
            <form method="post" action="<?= e(url('/logout')) ?>" style="margin:0">
                <?= csrf_field() ?>
                <button class="console-icon" type="submit" title="退出登录" style="border:none;background:none">⏻</button>
            </form>
        </div>
    </header>

    <div class="console-body">
        <div class="sidebar-mask console-mask" data-sidebar-mask></div>
        <aside class="console-side" data-sidebar>
            <?php foreach ($groups as $group => $items): ?>
                <span class="side-label"><?= e($group) ?></span>
                <?php foreach ($items as [$key, $path, $icon, $label]): ?>
                    <a class="side-link <?= $active === $key ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                        <span style="width:16px;text-align:center"><?= $icon ?></span><?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <div class="side-foot">
                <div class="avatar"><?= e(mb_substr($user['nickname'], 0, 1)) ?></div>
                <div class="user-mini">
                    <strong><?= e($user['nickname']) ?></strong>
                    <span><?= $isAdmin ? '管理员' : '普通用户' ?></span>
                </div>
            </div>
        </aside>

        <main class="main console-main">
            <div class="console-crumb">
                <a href="<?= e(url('/')) ?>">首页</a>
                <span>/</span>
                <a href="<?= e(url($isAdmin ? '/admin' : '/app')) ?>"><?= $isAdmin ? '运营后台' : '控制台' ?></a>
                <span>/</span>
                <b><?= e($activeLabel) ?></b>
            </div>
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
