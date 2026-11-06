<?php
$tabs = ['全部' => '', '待支付' => 'pending', '已支付' => 'paid', '已取消' => 'cancelled'];
?>
<div class="page-head">
    <div>
        <span class="eyebrow">ORDERS</span>
        <h1>订单管理</h1>
        <p>查看与管理全部用户订单。</p>
    </div>
</div>

<div class="tabs">
    <?php foreach ($tabs as $label => $key): ?>
        <a class="tab <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('/admin/orders' . ($key ? '?status=' . $key : ''))) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$list): ?>
        <div class="empty"><strong>暂无订单</strong></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>订单号</th><th>用户</th><th>套餐</th><th>节点</th><th>周期</th><th>金额</th><th>支付</th><th>状态</th><th class="right">时间</th><th class="right">操作</th></tr></thead>
                <tbody>
                <?php foreach ($list as $o): ?>
                    <tr>
                        <td class="mono"><?= e($o['order_no']) ?></td>
                        <td><?= e($o['nickname']) ?></td>
                        <td><?= e($o['plan_name']) ?></td>
                        <td><?= e($o['node_name']) ?></td>
                        <td><?= (int)$o['months'] ?> 月</td>
                        <td class="num"><?= e(money((int)$o['amount'])) ?></td>
                        <td class="small muted"><?= $o['payment_method'] ? ['wallet'=>'余额','alipay'=>'支付宝','wechat'=>'微信'][$o['payment_method']] : '—' ?></td>
                        <td><?= status_badge($o['status']) ?></td>
                        <td class="right small muted"><?= e(fmt_datetime($o['created_at'])) ?></td>
                        <td class="right">
                            <?php if ($o['status'] === 'pending'): ?>
                                <form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/close')) ?>" style="display:inline" onsubmit="return confirm('确认关闭该订单？')">
                                    <?= csrf_field() ?><button class="btn btn--danger btn--sm" type="submit">关闭</button>
                                </form>
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
