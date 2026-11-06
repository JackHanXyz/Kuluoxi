<div class="page-head">
    <div>
        <span class="eyebrow">SETTINGS</span>
        <h1>账户设置</h1>
        <p>修改昵称与登录密码，查看系统公告。</p>
    </div>
</div>

<div class="grid grid--2" style="align-items:start">
    <div style="display:flex;flex-direction:column;gap:14px">
        <div class="card">
            <div class="card-head"><h3>基本信息</h3></div>
            <div class="card-body">
                <form method="post" action="<?= e(url('/app/settings')) ?>">
                    <?= csrf_field() ?>
                    <label class="field">
                        <span class="field-label">昵称</span>
                        <input class="input" name="nickname" value="<?= e($user['nickname']) ?>" maxlength="20">
                    </label>
                    <label class="field">
                        <span class="field-label">邮箱</span>
                        <input class="input" value="<?= e($user['email']) ?>" disabled>
                    </label>
                    <button class="btn btn--primary" type="submit">保存昵称</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>修改密码</h3></div>
            <div class="card-body">
                <form method="post" action="<?= e(url('/app/settings')) ?>">
                    <?= csrf_field() ?>
                    <label class="field">
                        <span class="field-label">新密码 <em>至少 8 位</em></span>
                        <input class="input" type="password" name="password" placeholder="留空则不修改">
                    </label>
                    <label class="field">
                        <span class="field-label">确认新密码</span>
                        <input class="input" type="password" name="confirm" placeholder="再次输入新密码">
                    </label>
                    <button class="btn btn--primary" type="submit">更新密码</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>系统公告</h3><span class="small muted"><?= count($announcements) ?> 条</span></div>
        <?php if (!$announcements): ?>
            <div class="empty"><strong>暂无公告</strong></div>
        <?php else: ?>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                <?php foreach ($announcements as $a): ?>
                    <div style="border-bottom:1px solid var(--line);padding-bottom:14px">
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px">
                            <span class="pill-tag <?= $a['level'] === 'warning' ? 'pill-tag--amber' : '' ?>"><?= $a['level'] === 'warning' ? '维护' : '通知' ?></span>
                            <strong style="font-size:13.5px;font-weight:600"><?= e($a['title']) ?></strong>
                            <span class="small muted" style="margin-left:auto"><?= e(fmt_date($a['created_at'])) ?></span>
                        </div>
                        <p class="small muted" style="line-height:1.8"><?= e($a['body']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
