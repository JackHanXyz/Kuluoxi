<div class="page-head">
    <div class="detail-hero">
        <div class="avatar">✉</div>
        <div>
            <h1 style="font-size:20px"><?= e($ticket['subject']) ?></h1>
            <div style="display:flex;gap:10px;align-items:center;margin-top:6px;flex-wrap:wrap">
                <?= status_badge($ticket['status']) ?>
                <span class="pill-tag"><?= e(category_text($ticket['category'])) ?></span>
                <span class="pill-tag pill-tag--amber"><?= e(priority_text($ticket['priority'])) ?></span>
                <span class="small muted">用户：<?= e($ticket['user_nickname']) ?> · 工单 #<?= (int)$ticket['id'] ?></span>
            </div>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/tickets')) ?>">返回列表</a>
        <?php if ($ticket['status'] !== 'closed'): ?>
            <form method="post" action="<?= e(url('/admin/tickets/' . $ticket['id'] . '/close')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="btn btn--ghost btn--sm" type="submit">关闭工单</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="thread">
        <?php foreach ($messages as $m): ?>
            <div class="msg <?= (int)$m['is_staff'] ? 'msg--staff' : '' ?>">
                <div class="avatar" style="width:30px;height:30px;font-size:12px"><?= e(mb_substr($m['nickname'] ?: 'U', 0, 1)) ?></div>
                <div style="flex:1;min-width:0">
                    <div class="msg-body"><?= e($m['body']) ?></div>
                    <div class="msg-meta">
                        <b style="color:var(--text)"><?= e($m['nickname'] ?: '用户') ?></b>
                        <?php if ((int)$m['is_staff']): ?><span class="pill-tag" style="padding:0 6px">客服</span><?php endif; ?>
                        <span><?= e(fmt_datetime($m['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
        <form method="post" action="<?= e(url('/admin/tickets/' . $ticket['id'] . '/reply')) ?>" class="composer">
            <?= csrf_field() ?>
            <textarea class="input textarea" name="body" placeholder="输入回复内容..." required></textarea>
            <div class="composer-actions">
                <button class="btn btn--primary" type="submit">发送回复</button>
            </div>
        </form>
    <?php else: ?>
        <div class="card-body" style="text-align:center;color:var(--muted);border-top:1px solid var(--line)">
            工单已关闭。
        </div>
    <?php endif; ?>
</div>
