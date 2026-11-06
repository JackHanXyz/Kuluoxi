<?php $flashes = flash_messages(); ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:26px">
    <a href="<?= e(url('/')) ?>" class="brand">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
        <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI</span></span>
    </a>
</div>
<h1>登录控制台</h1>
<p class="sub">使用邮箱与密码登录，管理你的深海算力。</p>
<?php foreach ($flashes as $f): ?>
    <div class="flash flash--<?= e($f['type']) ?>"><span><?= e($f['message']) ?></span></div>
<?php endforeach; ?>
<form method="post" action="<?= e(url('/login')) ?>">
    <?= csrf_field() ?>
    <label class="field">
        <span class="field-label">邮箱地址</span>
        <input class="input" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com" required autofocus>
    </label>
    <label class="field">
        <span class="field-label">密码</span>
        <input class="input" type="password" name="password" placeholder="输入登录密码" required>
    </label>
    <button class="btn btn--primary btn--block btn--lg" type="submit">登录</button>
</form>
<p class="small muted" style="margin-top:18px">还没有账号？<a href="<?= e(url('/register')) ?>" style="color:var(--primary)">免费注册 →</a></p>
<div class="auth-hint">
    演示账号<br>
    用户：<b>demo@kuluoxi.io</b> / Demo@123456<br>
    管理员：<b>admin@kuluoxi.io</b> / Admin@123456
    <div style="display:flex;gap:8px;margin-top:8px">
        <button type="button" class="btn btn--ghost btn--sm" data-fill="user">填入用户</button>
        <button type="button" class="btn btn--ghost btn--sm" data-fill="admin">填入管理员</button>
    </div>
</div>
