<?php
$tabs = ['全部' => '', '待处理' => 'open', '已回复' => 'answered', '已关闭' => 'closed'];
?>
<div class="page-head">
    <div>
        <span class="eyebrow">TICKETS</span>
        <h1>工单处理</h1>
        <p>处理用户提交的工单，及时回复与关闭。</p>
    </div>
</div>

<div class="tabs">
    <?php foreach ($tabs as $label => $key): ?>
        <a class="tab <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('/admin/tickets' . ($key ? '?status=' . $key : ''))) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$list): ?>
        <div class="empty"><strong>暂无工单</strong></div>
    <?php else: ?>
        <div class="ticket-list">
            <?php foreach ($list as $t): ?>
                <a class="ticket-row" href="<?= e(url('/admin/tickets/' . $t['id'])) ?>">
                    <?= status_badge($t['status']) ?>
                    <div class="tr-main">
                        <strong><?= e($t['subject']) ?></strong>
                        <span><?= e($t['user_nickname']) ?> · <?= e(category_text($t['category'])) ?> · <?= e(priority_text($t['priority'])) ?> · <?= (int)$t['message_count'] ?> 条 · <?= e(ago($t['updated_at'])) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
