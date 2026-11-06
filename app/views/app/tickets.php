<div class="page-head">
    <div>
        <span class="eyebrow">TICKETS</span>
        <h1>工单支持</h1>
        <p>遇到问题随时提交工单，支持团队 7×24 小时响应。</p>
    </div>
    <button class="btn btn--primary" type="button" data-open-modal="create">+ 提交工单</button>
</div>

<div class="card">
    <?php if (!$list): ?>
        <div class="empty"><div class="empty-art">✉</div><strong>还没有工单</strong><p>有任何问题都可以在这里向我们反馈。</p></div>
    <?php else: ?>
        <div class="ticket-list">
            <?php foreach ($list as $t): ?>
                <a class="ticket-row" href="<?= e(url('/app/tickets/' . $t['id'])) ?>">
                    <span class="pill-tag"><?= e(category_text($t['category'])) ?></span>
                    <div class="tr-main">
                        <strong><?= e($t['subject']) ?></strong>
                        <span><?= e(priority_text($t['priority'])) ?> 优先级 · <?= (int)$t['message_count'] ?> 条消息 · <?= e(ago($t['updated_at'])) ?></span>
                    </div>
                    <?= status_badge($t['status']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="modal-mask" data-modal="create" hidden>
    <div class="modal">
        <div class="modal-head"><h3>提交新工单</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" action="<?= e(url('/app/tickets')) ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <label class="field">
                    <span class="field-label">标题 <em>4-60 个字符</em></span>
                    <input class="input" name="subject" placeholder="简要描述你的问题" maxlength="60">
                </label>
                <div class="form-grid">
                    <label class="field">
                        <span class="field-label">分类</span>
                        <select class="input select" name="category">
                            <?php foreach (['technical'=>'技术支持','network'=>'网络问题','billing'=>'账单支付','feature'=>'功能建议','other'=>'其他'] as $k=>$v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span class="field-label">优先级</span>
                        <select class="input select" name="priority">
                            <?php foreach (['low'=>'低','normal'=>'普通','high'=>'高'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $k==='normal'?'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <label class="field">
                    <span class="field-label">问题描述 <em>10-4000 个字符</em></span>
                    <textarea class="input textarea" name="body" placeholder="请详细描述你遇到的问题，包括实例 ID、复现步骤等"></textarea>
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn--ghost" type="button" data-close-modal>取消</button>
                <button class="btn btn--primary" type="submit">提交工单</button>
            </div>
        </form>
    </div>
</div>
