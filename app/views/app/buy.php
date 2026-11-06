<?php
$old = $old ?? [];
$selPlan = null;
foreach ($plans as $p) {
    if ($p['id'] === $selectedPlan) { $selPlan = $p; break; }
}
if (!$selPlan && $plans) $selPlan = $plans[0];
$selMonths = isset($old['months']) ? (int)$old['months'] : 1;
$selNode = isset($old['node_id']) ? (int)$old['node_id'] : 0;
$selOs = $old['os'] ?? ($osOptions[0] ?? '');
$selMethod = $old['method'] ?? 'wallet';
$selName = $old['instance_name'] ?? '';

$setup = $selPlan ? (int)$selPlan['setupFee'] : 0;
$monthly = $selPlan ? (int)$selPlan['price'] : 0;
$total = $monthly * $selMonths + $setup;
?>
<div class="page-head">
    <div>
        <span class="eyebrow">NEW SERVER</span>
        <h1>购买服务器</h1>
        <p>选择套餐、节点与周期，支付后系统将自动为你开通实例。</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/app/vps')) ?>">我的实例</a>
</div>

<div class="buy-steps">
    <span class="on">1 · 选套餐</span>
    <span>2 · 选节点</span>
    <span>3 · 周期系统</span>
    <span>4 · 确认支付</span>
</div>

<form method="post" action="<?= e(url('/app/buy')) ?>" id="buyForm">
    <?= csrf_field() ?>
    <input type="hidden" name="plan_id" id="planId" value="<?= (int)($selPlan['id'] ?? 0) ?>">
    <input type="hidden" name="months" id="months" value="<?= (int)$selMonths ?>">

    <div class="buy-grid">
        <div style="display:flex;flex-direction:column;gap:14px">
            <!-- 套餐 -->
            <div class="card">
                <div class="card-head"><h3>选择套餐</h3><span class="small muted"><?= count($plans) ?> 个可用套餐</span></div>
                <div class="card-body">
                    <div class="plan-picker" id="planPicker">
                        <?php foreach ($plans as $p): ?>
                            <label class="plan-option <?= ($selPlan && $selPlan['id'] === $p['id']) ? 'active' : '' ?>"
                                   data-id="<?= (int)$p['id'] ?>" data-price="<?= (int)$p['price'] ?>" data-setup="<?= (int)$p['setupFee'] ?>">
                                <input type="radio" name="plan_radio" value="<?= (int)$p['id'] ?>"
                                    <?= ($selPlan && $selPlan['id'] === $p['id']) ? 'checked' : '' ?> style="display:none">
                                <div class="po-name"><b><?= e($p['name']) ?></b><em><?= e(money((int)$p['price'])) ?></em></div>
                                <div class="po-spec"><?= (int)$p['cpu'] ?> 核 · <?= $p['memoryMb'] / 1024 ?>G 内存 · <?= (int)$p['diskGb'] ?>G NVMe · <?= (int)$p['bandwidthGb'] ?>G 流量</div>
                                <?php if ((int)$p['setupFee'] > 0): ?><div class="po-spec small muted">一次性安装费 <?= e(money((int)$p['setupFee'])) ?></div><?php endif; ?>
                                <?php if ((int)$p['stock'] <= 0): ?><div class="po-spec" style="color:var(--danger)">已售罄</div><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 节点 -->
            <div class="card">
                <div class="card-head"><h3>选择节点</h3></div>
                <div class="card-body">
                    <div class="node-options" id="nodeOptions">
                        <?php foreach ($nodes as $n): ?>
                            <?php $online = $n['status'] === 'online'; $pct = $n['capacity'] > 0 ? min(100, round((int)$n['used'] / (int)$n['capacity'] * 100)) : 0; ?>
                            <label class="node-option <?= (!$selNode && $online) ? 'active' : ($selNode === (int)$n['id'] ? 'active' : '') ?> <?= $online ? '' : 'disabled' ?>">
                                <input type="radio" name="node_id" value="<?= (int)$n['id'] ?>"
                                    <?= $online ? '' : 'disabled' ?>
                                    <?= ($selNode === (int)$n['id']) ? 'checked' : ((!$selNode && $online) ? 'checked' : '') ?> style="display:none">
                                <span class="node-flag" style="font-size:18px"><?= e($n['flag']) ?></span>
                                <div style="flex:1;min-width:0">
                                    <b><?= e($n['name']) ?></b>
                                    <div class="small muted"><?= e($n['region']) ?> · <?= e($n['country']) ?></div>
                                </div>
                                <small><?= $online ? $pct . '%' : '维护' ?></small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 周期 & 系统 -->
            <div class="card">
                <div class="card-head"><h3>周期与系统</h3></div>
                <div class="card-body">
                    <label class="field">
                        <span class="field-label">实例名称 <em>3-32 位字母、数字或短横线，开头为字母或数字</em></span>
                        <input class="input" name="instance_name" value="<?= e($selName) ?>" placeholder="如 web-prod-01" maxlength="32">
                    </label>
                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">购买周期</span>
                            <div class="cycle-row" id="cycleRow">
                                <?php foreach ([1, 3, 6, 12] as $m): ?>
                                    <label class="cycle <?= $selMonths === $m ? 'active' : '' ?>" data-months="<?= $m ?>">
                                        <input type="radio" name="cycle_radio" value="<?= $m ?>" <?= $selMonths === $m ? 'checked' : '' ?> style="display:none">
                                        <?= $m ?> 个月<?php if ($m === 12): ?><em>省 2 个月</em><?php elseif ($m === 6): ?><em>优惠</em><?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </label>
                        <label class="field">
                            <span class="field-label">操作系统</span>
                            <select class="input select" name="os">
                                <?php foreach ($osOptions as $os): ?>
                                    <option value="<?= e($os) ?>" <?= $selOs === $os ? 'selected' : '' ?>><?= e($os) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <label class="field">
                        <span class="field-label">支付方式</span>
                        <div class="cycle-row">
                            <?php foreach (['wallet' => '余额支付', 'alipay' => '支付宝', 'wechat' => '微信支付'] as $k => $v): ?>
                                <label class="cycle <?= $selMethod === $k ? 'active' : '' ?>">
                                    <input type="radio" name="method" value="<?= $k ?>" <?= $selMethod === $k ? 'checked' : '' ?> style="display:none"><?= $v ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($selMethod === 'wallet'): ?>
                            <p class="small muted" style="margin-top:8px">当前余额 <?= e(money((int)$user['balance'])) ?>，下单后自动扣费。</p>
                        <?php elseif (($payDriver ?? 'demo') === 'demo'): ?>
                            <p class="small muted" style="margin-top:8px">当前为演示支付：提交后订单直接完成并开通实例。</p>
                        <?php else: ?>
                            <p class="small muted" style="margin-top:8px">提交后将跳转到<?= ($payDriver === 'epay') ? '易支付' : '码支付' ?>收银台完成付款。</p>
                        <?php endif; ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- 订单摘要 -->
        <div class="card summary">
            <div class="card-head"><h3>订单摘要</h3></div>
            <div class="card-body">
                <div class="summary-line"><span>套餐</span><b id="sumPlan"><?= e($selPlan['name'] ?? '—') ?></b></div>
                <div class="summary-line"><span>周期</span><b id="sumMonths"><?= (int)$selMonths ?> 个月</b></div>
                <div class="summary-line"><span>月费</span><b id="sumMonthly"><?= e(money($monthly)) ?></b></div>
                <div class="summary-line"><span>安装费</span><b id="sumSetup"><?= e(money($setup)) ?></b></div>
                <div class="summary-total"><span>应付金额</span><b id="sumTotal"><?= e(money($total)) ?></b></div>
                <button class="btn btn--primary btn--block" type="submit">确认下单</button>
                <p class="small muted" style="margin-top:10px;text-align:center">支付成功后实例将自动开通，约 6 秒后可使用。</p>
            </div>
        </div>
    </div>
</form>

<script>
(function(){
  var picker = document.getElementById('planPicker');
  var cycleRow = document.getElementById('cycleRow');
  var planId = document.getElementById('planId');
  var months = document.getElementById('months');
  var sumPlan = document.getElementById('sumPlan');
  var sumMonthly = document.getElementById('sumMonthly');
  var sumSetup = document.getElementById('sumSetup');
  var sumMonths = document.getElementById('sumMonths');
  var sumTotal = document.getElementById('sumTotal');

  function fmt(c){ return '¥' + (c/100).toFixed(c%100===0?0:2).replace(/\B(?=(\d{3})+(?!\d))/g,','); }

  function updateSummary(){
    var active = picker.querySelector('.plan-option.active');
    var m = parseInt(months.value,10) || 1;
    if(!active) return;
    var price = parseInt(active.dataset.price,10);
    var setup = parseInt(active.dataset.setup,10);
    planId.value = active.dataset.id;
    sumPlan.textContent = active.querySelector('.po-name b').textContent;
    sumMonthly.textContent = fmt(price);
    sumSetup.textContent = fmt(setup);
    sumMonths.textContent = m + ' 个月';
    sumTotal.textContent = fmt(price*m+setup);
  }

  picker.addEventListener('click', function(e){
    var opt = e.target.closest('.plan-option');
    if(!opt) return;
    picker.querySelectorAll('.plan-option').forEach(function(o){o.classList.remove('active');});
    opt.classList.add('active');
    opt.querySelector('input').checked = true;
    updateSummary();
  });

  cycleRow.addEventListener('click', function(e){
    var opt = e.target.closest('.cycle');
    if(!opt) return;
    cycleRow.querySelectorAll('.cycle').forEach(function(o){o.classList.remove('active');});
    opt.classList.add('active');
    opt.querySelector('input').checked = true;
    months.value = opt.dataset.months;
    updateSummary();
  });

  // 支付方式点击激活
  document.querySelectorAll('input[name="method"]').forEach(function(r){
    r.addEventListener('change', function(){
      r.closest('.cycle-row').querySelectorAll('.cycle').forEach(function(o){o.classList.remove('active');});
      r.closest('.cycle').classList.add('active');
    });
  });
})();
</script>
