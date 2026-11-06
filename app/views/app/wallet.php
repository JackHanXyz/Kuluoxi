<div class="page-head">
    <div>
        <span class="eyebrow">WALLET</span>
        <h1>充值中心</h1>
        <p>账户余额用于购买服务器与自动续费，支持快捷充值与自定义金额。</p>
    </div>
</div>

<div class="wallet-hero" style="margin-bottom:16px">
    <div>
        <span class="eyebrow" style="color:var(--amber)">账户余额</span>
        <div class="amount num"><?= e(money((int)$user['balance'])) ?></div>
        <p class="small muted" style="margin-top:6px">余额永久有效，可随时用于购买与续费。</p>
    </div>
    <div class="wallet-actions">
        <a class="btn btn--primary" href="<?= e(url('/app/buy')) ?>">去购买</a>
        <a class="btn btn--ghost" href="<?= e(url('/app/orders')) ?>">我的订单</a>
    </div>
</div>

<div class="grid grid--2" style="align-items:start">
    <div class="card">
        <div class="card-head"><h3>在线充值</h3><span class="small muted">模拟支付，即时到账</span></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/app/wallet/recharge')) ?>" id="rechargeForm">
                <?= csrf_field() ?>
                <div class="recharge-options" id="rechargeOptions">
                    <?php foreach ([50, 100, 300, 500] as $amt): ?>
                        <label class="recharge-option" data-amount="<?= $amt ?>">
                            <input type="radio" name="quick" value="<?= $amt ?>" style="display:none">
                            <span class="num">¥<?= $amt ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <label class="field" style="margin-top:16px">
                    <span class="field-label">或输入自定义金额 <em>¥10 - ¥50,000</em></span>
                    <input class="input amount-input num" type="number" name="amount" id="amountInput" value="100" min="10" max="50000" step="0.01">
                </label>
                <button class="btn btn--primary btn--block" type="submit">立即充值</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>最近流水</h3><span class="small muted">最近 30 条</span></div>
        <?php if (!$transactions): ?>
            <div class="empty"><strong>暂无流水记录</strong></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>类型</th><th>金额</th><th>余额</th><th class="right">时间</th></tr></thead>
                    <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td>
                                <span class="pill-tag <?= $t['type'] === 'recharge' ? 'pill-tag--amber' : '' ?>">
                                    <?= $t['type'] === 'recharge' ? '充值' : ($t['type'] === 'payment' ? '消费' : ($t['type'] === 'refund' ? '退款' : '调整')) ?>
                                </span>
                                <div class="small muted" style="margin-top:4px;max-width:220px"><?= e($t['remark']) ?></div>
                            </td>
                            <td class="num" style="color:<?= (int)$t['amount'] >= 0 ? 'var(--green)' : 'var(--danger)' ?>">
                                <?= (int)$t['amount'] >= 0 ? '+' : '' ?><?= e(money((int)$t['amount'])) ?>
                            </td>
                            <td class="num muted"><?= e(money((int)$t['balance_after'])) ?></td>
                            <td class="right small muted"><?= e(fmt_datetime($t['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
  var opts = document.getElementById('rechargeOptions');
  var input = document.getElementById('amountInput');
  opts.addEventListener('click', function(e){
    var opt = e.target.closest('.recharge-option');
    if(!opt) return;
    opts.querySelectorAll('.recharge-option').forEach(function(o){o.classList.remove('active');});
    opt.classList.add('active');
    opt.querySelector('input').checked = true;
    input.value = opt.dataset.amount;
  });
})();
</script>
