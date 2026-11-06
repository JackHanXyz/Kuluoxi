<?php
$maxRev = 0;
foreach ($trend as $t) { $maxRev = max($maxRev, (int)$t['revenue']); }
$maxPlanRev = 0;
foreach ($planSales as $p) { $maxPlanRev = max($maxPlanRev, (int)$p['revenue']); }
?>
<div class="page-head">
    <div>
        <span class="eyebrow">ADMIN OVERVIEW</span>
        <h1>运营概览</h1>
        <p>实时掌握营收、实例、订单与工单动态。</p>
    </div>
    <div class="btn-row">
        <a class="btn btn--ghost" href="<?= e(url('/admin/tickets')) ?>">工单 (<?= (int)$openTickets ?>)</a>
        <a class="btn btn--primary" href="<?= e(url('/admin/plans')) ?>">套餐管理</a>
    </div>
</div>

<div class="grid grid--4" style="margin-bottom:14px">
    <div class="stat stat--amber"><span class="stat-label">累计营收</span><strong class="stat-value num"><?= e(money((int)$revenue)) ?></strong><span class="stat-hint">全部已支付订单</span></div>
    <div class="stat stat--accent"><span class="stat-label">近 30 天营收</span><strong class="stat-value num"><?= e(money((int)$monthRevenue)) ?></strong><span class="stat-hint">较上月趋势</span></div>
    <div class="stat stat--violet"><span class="stat-label">注册用户</span><strong class="stat-value num"><?= (int)$users ?></strong><span class="stat-hint">普通用户</span></div>
    <div class="stat"><span class="stat-label">活跃实例</span><strong class="stat-value num"><?= (int)$activeInstances ?></strong><span class="stat-hint">开通中 <?= (int)$provisioning ?> · 待支付 <?= (int)$pendingOrders ?></span></div>
</div>

<div class="grid grid--2" style="align-items:start;margin-bottom:14px">
    <div class="card">
        <div class="card-head"><h3>近 14 天营收</h3><span class="small muted num"><?= e(money((int)$monthRevenue)) ?></span></div>
        <div class="card-body">
            <div class="chart">
                <?php foreach ($trend as $t): ?>
                    <?php $h = $maxRev > 0 ? round((int)$t['revenue'] / $maxRev * 100) : 0; ?>
                    <div class="chart-bar" title="<?= e($t['date']) ?>">
                        <em><?= e(money((int)$t['revenue'])) ?></em>
                        <i style="height:<?= $h ?>%"></i>
                        <span><?= e(substr($t['date'], 5)) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>套餐销售分布</h3></div>
        <div class="card-body">
            <?php if (!$planSales): ?>
                <div class="empty"><strong>暂无销售数据</strong></div>
            <?php else: ?>
                <div class="bar-list">
                    <?php foreach ($planSales as $p): ?>
                        <?php $w = $maxPlanRev > 0 ? round((int)$p['revenue'] / $maxPlanRev * 100) : 0; ?>
                        <div class="bar-row">
                            <span><?= e($p['name']) ?></span>
                            <span class="progress"><span style="width:<?= $w ?>%"></span></span>
                            <b class="num"><?= e(money((int)$p['revenue'])) ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid--2" style="align-items:start">
    <div class="card">
        <div class="card-head"><h3>节点负载</h3><a href="<?= e(url('/admin/nodes')) ?>" class="small muted">管理 →</a></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <?php foreach ($nodes as $n): ?>
                <?php $pct = $n['capacity'] > 0 ? min(100, round((int)$n['used'] / (int)$n['capacity'] * 100)) : 0; ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px">
                        <span><?= e($n['flag']) ?> <?= e($n['name']) ?> <span class="muted">· <?= e($n['region']) ?></span></span>
                        <span class="muted num"><?= (int)$n['used'] ?> / <?= (int)$n['capacity'] ?></span>
                    </div>
                    <span class="progress"><span style="width:<?= $pct ?>%" class="<?= $pct > 80 ? 'warn' : '' ?>"></span></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>最新工单</h3><a href="<?= e(url('/admin/tickets')) ?>" class="small muted">全部 →</a></div>
        <?php if (!$recentTickets): ?>
            <div class="empty"><strong>暂无工单</strong></div>
        <?php else: ?>
            <div class="ticket-list">
                <?php foreach ($recentTickets as $t): ?>
                    <a class="ticket-row" href="<?= e(url('/admin/tickets/' . $t['id'])) ?>">
                        <?= status_badge($t['status']) ?>
                        <div class="tr-main">
                            <strong><?= e($t['subject']) ?></strong>
                            <span><?= e($t['user_nickname']) ?> · <?= e(ago($t['updated_at'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <div class="card-head"><h3>最新订单</h3><a href="<?= e(url('/admin/orders')) ?>" class="small muted">全部订单 →</a></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>订单号</th><th>用户</th><th>套餐</th><th>节点</th><th>金额</th><th>状态</th><th class="right">时间</th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td class="mono"><?= e($o['order_no']) ?></td>
                    <td><?= e($o['nickname']) ?></td>
                    <td><?= e($o['plan_name']) ?></td>
                    <td><?= e($o['node_name']) ?></td>
                    <td class="num"><?= e(money((int)$o['amount'])) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td class="right small muted"><?= e(fmt_datetime($o['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
