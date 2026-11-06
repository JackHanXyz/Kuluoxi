<?php
$tabs = ['全部' => '', '待支付' => 'pending', '已支付' => 'paid', '已取消' => 'cancelled'];
$total = array_sum($counts);
?>
<div class="page-head">
    <div>
        <span class="eyebrow">ORDERS</span>
        <h1>订单管理</h1>
        <p>查看历史订单，待支付订单可继续付款或取消。</p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('/app/buy')) ?>">新建订单</a>
</div>

<div class="tabs">
    <?php foreach ($tabs as $label => $key): ?>
        <a class="tab <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('/app/orders' . ($key ? '?status=' . $key : ''))) ?>">
            <?= $label ?><?= $key ? ' (' . ($counts[$key] ?? 0) . ')' : ' (' . (int)$total . ')' ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$list): ?>
        <div class="empty"><div class="empty-art">▣</div><strong>暂无订单</strong><p>去选购一台属于你的服务器吧。</p><a class="btn btn--primary btn--sm" href="<?= e(url('/app/buy')) ?>">立即购买</a></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>订单号</th><th>套餐 / 节点</th><th>周期</th><th>金额</th><th>支付方式</th><th>状态</th><th class="right">创建时间</th><th class="right">操作</th></tr></thead>
                <tbody>
                <?php foreach ($list as $o): ?>
                    <tr>
                        <td class="mono"><?= e($o['order_no']) ?></td>
                        <td><?= e($o['plan_name']) ?><br><span class="small muted"><?= e($o['node_name']) ?></span></td>
                        <td><?= (int)$o['months'] ?> 个月</td>
                        <td class="num"><?= e(money((int)$o['amount'])) ?></td>
                        <td class="small muted"><?= $o['payment_method'] ? ['wallet'=>'余额','alipay'=>'支付宝','wechat'=>'微信'][$o['payment_method']] : '—' ?></td>
                        <td><?= status_badge($o['status']) ?></td>
                        <td class="right small muted"><?= e(fmt_datetime($o['created_at'])) ?></td>
                        <td class="right">
                            <?php if ($o['status'] === 'pending'): ?>
                                <div class="cell-actions">
                                    <form method="post" action="<?= e(url('/app/orders/' . $o['id'] . '/pay')) ?>" style="display:inline">
                                        <?= csrf_field() ?><input type="hidden" name="method" value="wallet">
                                        <button class="btn btn--soft btn--sm" type="submit">余额支付</button>
                                    </form>
                                    <form method="post" action="<?= e(url('/app/orders/' . $o['id'] . '/pay')) ?>" style="display:inline">
                                        <?= csrf_field() ?><input type="hidden" name="method" value="alipay">
                                        <button class="btn btn--ghost btn--sm" type="submit">支付宝</button>
                                    </form>
                                    <form method="post" action="<?= e(url('/app/orders/' . $o['id'] . '/pay')) ?>" style="display:inline">
                                        <?= csrf_field() ?><input type="hidden" name="method" value="wechat">
                                        <button class="btn btn--ghost btn--sm" type="submit">微信</button>
                                    </form>
                                    <form method="post" action="<?= e(url('/app/orders/' . $o['id'] . '/cancel')) ?>" style="display:inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn--ghost btn--sm" type="submit">取消</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="small muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
