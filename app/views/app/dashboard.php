<div class="page-head">
    <div>
        <span class="eyebrow">OVERVIEW</span>
        <h1>你好，<?= e($user['nickname']) ?></h1>
        <p>账户与实例的实时状态。</p>
    </div>
    <div class="btn-row">
        <a class="btn btn--ghost" href="<?= e(url('/app/tickets')) ?>">提交工单</a>
        <a class="btn btn--primary" href="<?= e(url('/app/buy')) ?>">开通新实例</a>
    </div>
</div>

<div class="ov-strip">
    <div class="ov-cell ov-cell--amber">
        <span>账户余额</span>
        <b><?= e(money((int)$user['balance'])) ?></b>
    </div>
    <div class="ov-cell ov-cell--accent">
        <span>运行中实例</span>
        <b><?= (int)$runningCount ?><em>/ <?= (int)$instanceCount ?> 台</em></b>
    </div>
    <div class="ov-cell ov-cell--violet">
        <span>待支付订单</span>
        <b><?= (int)$pendingOrders ?></b>
    </div>
    <div class="ov-cell <?= $openTickets ? 'ov-cell--danger' : '' ?>">
        <span>进行中工单</span>
        <b><?= (int)$openTickets ?></b>
    </div>
</div>

<div class="dash-grid">
    <div>
        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head">
                <h3>我的实例</h3>
                <a href="<?= e(url('/app/vps')) ?>" class="small muted">管理全部 →</a>
            </div>
            <div class="panel-body panel-body--flush">
                <?php if (!$instances): ?>
                    <div class="empty">
                        <div class="empty-art">◌</div>
                        <strong>还没有实例</strong>
                        <p>选择套餐与节点，几秒内自动开通。</p>
                        <a class="btn btn--primary btn--sm" href="<?= e(url('/app/buy')) ?>">立即购买</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($instances as $vps): ?>
                        <a class="inst-row" href="<?= e(url('/app/vps/' . $vps['id'])) ?>">
                            <div class="inst-row-main">
                                <strong><?= e($vps['name']) ?></strong>
                                <span><?= e($vps['plan_name']) ?> · <?= e($vps['node_name']) ?></span>
                            </div>
                            <div class="inst-row-specs">
                                <span><b><?= (int)$vps['cpu'] ?></b> 核</span>
                                <span><b><?= $vps['memory_mb'] / 1024 ?></b> GB</span>
                                <span><b><?= (int)$vps['disk_gb'] ?></b> GB</span>
                            </div>
                            <span class="inst-row-ip"><?= e($vps['ip'] ?: '分配中…') ?></span>
                            <span class="inst-row-days">剩 <?= max(0, days_left($vps['expires_at'])) ?> 天</span>
                            <?= status_badge($vps['status']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h3>最近订单</h3>
                <a href="<?= e(url('/app/orders')) ?>" class="small muted">订单列表 →</a>
            </div>
            <div class="panel-body panel-body--flush">
                <?php if (!$recentOrders): ?>
                    <div class="empty"><strong>暂无订单</strong></div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>订单号</th><th>套餐</th><th>金额</th><th>状态</th><th class="right">时间</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td class="mono"><?= e($o['order_no']) ?></td>
                                    <td><?= e($o['plan_name']) ?><br><span class="small muted"><?= e($o['node_name']) ?></span></td>
                                    <td class="num"><?= e(money((int)$o['amount'])) ?></td>
                                    <td><?= status_badge($o['status']) ?></td>
                                    <td class="right small muted"><?= e(fmt_datetime($o['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside>
        <section class="panel">
            <div class="panel-head"><h3>账户</h3></div>
            <div class="side-block">
                <div class="label">可用余额</div>
                <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px">
                    <b class="num" style="font-size:28px;font-weight:600;color:#d97706;line-height:1.2"><?= e(money((int)$user['balance'])) ?></b>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/app/wallet')) ?>">充值</a>
                </div>
                <p class="small muted" style="margin-top:6px">余额用于购买与自动续费</p>
            </div>
            <div class="side-block">
                <div class="label">快捷操作</div>
                <div class="side-actions">
                    <a class="quick-action" href="<?= e(url('/app/buy')) ?>">＋ 开通实例</a>
                    <a class="quick-action" href="<?= e(url('/app/wallet')) ?>">◎ 充值</a>
                    <a class="quick-action" href="<?= e(url('/app/orders')) ?>">▣ 订单</a>
                    <a class="quick-action" href="<?= e(url('/app/tickets')) ?>">✉ 工单</a>
                </div>
            </div>
            <?php if ($announcements): ?>
                <div class="side-block">
                    <div class="label">公告</div>
                    <?php foreach ($announcements as $a): ?>
                        <div class="ann-item">
                            <strong><?= e($a['title']) ?></strong>
                            <p><?= e($a['body']) ?></p>
                            <span class="small muted"><?= e(fmt_date($a['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </aside>
</div>
