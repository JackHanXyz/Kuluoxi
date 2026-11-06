<?php $flashes = flash_messages(); ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:26px">
    <a href="<?= e(url('/')) ?>" class="brand">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--primary)"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
        <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI</span></span>
    </a>
</div>
<h1>创建库洛汐账户</h1>
<p class="sub">注册即可开通服务器，无需实名与备案，支持 24 小时无理由退款。</p>
<?php foreach ($flashes as $f): ?>
    <div class="flash flash--<?= e($f['type']) ?>"><span><?= e($f['message']) ?></span></div>
<?php endforeach; ?>
<form method="post" action="<?= e(url('/register')) ?>">
    <?= csrf_field() ?>
    <label class="field">
        <span class="field-label">邮箱地址</span>
        <input class="input" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com" required autofocus>
    </label>
    <label class="field">
        <span class="field-label">昵称 <em>2-20 个字符</em></span>
        <input class="input" name="nickname" value="<?= e($old['nickname'] ?? '') ?>" placeholder="给自己起个潮汐之名" required>
    </label>
    <div class="form-grid">
        <label class="field">
            <span class="field-label">密码 <em>至少 8 位</em></span>
            <input class="input" type="password" name="password" required>
        </label>
        <label class="field">
            <span class="field-label">确认密码</span>
            <input class="input" type="password" name="confirm" required>
        </label>
    </div>
    <button class="btn btn--primary btn--block btn--lg" type="submit">注册并开始</button>
</form>
<p class="small muted" style="margin-top:18px">已有账号？<a href="<?= e(url('/login')) ?>" style="color:var(--primary)">直接登录 →</a></p>
<div class="auth-hint">注册即表示同意《服务条款》与《隐私政策》。演示环境不会发送验证邮件。</div>
