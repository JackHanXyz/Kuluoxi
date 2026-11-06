<?php
$cfg = $payConfig;
$mf = $providerSettings;
?>
<div class="page-head">
    <div>
        <span class="eyebrow">SYSTEM</span>
        <h1>系统设置</h1>
        <p>站点外观、支付网关与上游对接，全部在这里配置。</p>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/settings')) ?>">
    <?= csrf_field() ?>

    <div class="grid grid--2" style="align-items:start;margin-bottom:14px">
        <div class="card">
            <div class="card-head"><h3>站点信息</h3></div>
            <div class="card-body">
                <label class="field">
                    <span class="field-label">站点名称</span>
                    <input class="input" name="site_name" value="<?= e(setting('site_name', '库洛汐 Kuluoxi')) ?>">
                </label>
                <div class="field">
                    <span class="field-label">默认皮肤 <em>访客首次访问时使用；已切换过的用户以本地记忆为准</em></span>
                    <input type="hidden" name="default_skin" id="defaultSkinInput" value="<?= e($currentSkin) ?>">
                    <div class="skin-grid" id="skinGrid">
                        <?php foreach ($skinList as $key => $skin): ?>
                            <button type="button" class="skin-card <?= $key === $currentSkin ? 'active' : '' ?>" data-skin-pick="<?= e($key) ?>" data-value="<?= e($key) ?>">
                                <span class="skin-dot" style="background:<?= e($skin['primary']) ?>"></span>
                                <span>
                                    <b><?= e($skin['name']) ?></b>
                                    <span><?= e($skin['vendor']) ?>风格 · <?= e($skin['primary']) ?></span>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <p class="small muted" style="margin-top:8px">点击卡片可即时预览；保存后成为新访客的默认皮肤。</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>上游开通（Provider）</h3><span class="pill-tag <?= $mf['driver'] === 'mock' ? '' : 'pill-tag--amber' ?>"><?= e(['mock'=>'演示模式','mofang_finance'=>'魔方财务','mofang_cloud'=>'魔方云'][$mf['driver']] ?? $mf['driver']) ?></span></div>
            <div class="card-body">
                <label class="field">
                    <span class="field-label">开通驱动</span>
                    <select class="input select" name="provider_driver">
                        <option value="mock" <?= $mf['driver'] === 'mock' ? 'selected' : '' ?>>演示模式（本地模拟开通）</option>
                        <option value="mofang_finance" <?= $mf['driver'] === 'mofang_finance' ? 'selected' : '' ?>>魔方财务面板 API</option>
                        <option value="mofang_cloud" <?= $mf['driver'] === 'mofang_cloud' ? 'selected' : '' ?>>魔方云 VPS 面板 API</option>
                    </select>
                </label>

                <hr class="hr">
                <span class="field-label" style="display:block;margin-bottom:10px">魔方财务</span>
                <div class="form-grid">
                    <label class="field full"><span class="field-label">API 地址</span><input class="input" name="mofang_finance_api" value="<?= e($mf['mofang_finance']['api_url']) ?>" placeholder="https://panel.example.com/api.php"></label>
                    <label class="field"><span class="field-label">API Key</span><input class="input" name="mofang_finance_key" value="<?= e($mf['mofang_finance']['key']) ?>"></label>
                    <label class="field"><span class="field-label">默认产品 ID</span><input class="input" name="mofang_finance_product" value="<?= e($mf['mofang_finance']['product_id']) ?>"></label>
                </div>

                <hr class="hr">
                <span class="field-label" style="display:block;margin-bottom:10px">魔方云</span>
                <div class="form-grid">
                    <label class="field full"><span class="field-label">API 地址</span><input class="input" name="mofang_cloud_api" value="<?= e($mf['mofang_cloud']['api_url']) ?>" placeholder="https://cloud.example.com/api/v1"></label>
                    <label class="field"><span class="field-label">API Key</span><input class="input" name="mofang_cloud_key" value="<?= e($mf['mofang_cloud']['key']) ?>"></label>
                    <label class="field"><span class="field-label">默认产品 ID</span><input class="input" name="mofang_cloud_product" value="<?= e($mf['mofang_cloud']['product_id']) ?>"></label>
                </div>
                <p class="small muted" style="margin-top:10px">适配层按 <span class="mono">action=create/suspend/unsuspend/reboot/reinstall/destroy</span> + 签名下发；未配置或调用失败会自动回落演示开通，不阻断下单。</p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h3>控制台布局</h3><span class="small muted">保存后整站控制台按新排版渲染</span></div>
        <div class="card-body">
            <input type="hidden" name="ui_layout" id="uiLayoutInput" value="<?= e($currentLayout) ?>">
            <div class="layout-grid" id="layoutGrid">
                <?php
                $thumbs = [
                    'sidebar' => '<rect x="0" y="0" width="18" height="36" fill="#0052d9" opacity=".85"/><rect x="22" y="5" width="26" height="3" rx="1" fill="#c9d4e6"/><rect x="22" y="12" width="22" height="3" rx="1" fill="#c9d4e6"/><rect x="22" y="19" width="26" height="10" rx="2" fill="#e8eef8"/>',
                    'topbar' => '<rect x="0" y="0" width="52" height="8" fill="#0052d9" opacity=".85"/><rect x="4" y="14" width="20" height="16" rx="2" fill="#e8eef8"/><rect x="28" y="14" width="20" height="16" rx="2" fill="#e8eef8"/>',
                    'rail' => '<rect x="0" y="0" width="10" height="36" fill="#0052d9" opacity=".85"/><rect x="14" y="5" width="34" height="3" rx="1" fill="#c9d4e6"/><rect x="14" y="12" width="34" height="18" rx="2" fill="#e8eef8"/>',
                    'console' => '<rect x="0" y="0" width="52" height="7" fill="#0f1a2b"/><rect x="0" y="7" width="16" height="29" fill="#0052d9" opacity=".75"/><rect x="20" y="11" width="28" height="3" rx="1" fill="#c9d4e6"/><rect x="20" y="18" width="28" height="14" rx="2" fill="#e8eef8"/>',
                ];
                ?>
                <?php foreach ($layoutList as $layoutKey => $layoutItem): ?>
                    <button type="button" class="layout-card <?= $layoutKey === $currentLayout ? 'active' : '' ?>" data-layout-pick="<?= e($layoutKey) ?>" data-value="<?= e($layoutKey) ?>">
                        <span class="layout-thumb">
                            <svg width="52" height="36" viewBox="0 0 52 36" xmlns="http://www.w3.org/2000/svg"><?= $thumbs[$layoutKey] ?? '' ?></svg>
                        </span>
                        <span>
                            <b><?= e($layoutItem['name']) ?></b>
                            <span><?= e($layoutItem['desc']) ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h3>支付网关</h3><span class="small muted">当前生效：<?= e(['epay'=>'易支付','codepay'=>'码支付','demo'=>'演示支付'][$payDriverReady] ?? $payDriverReady) ?></span></div>
        <div class="card-body">
            <div class="form-grid">
                <label class="field">
                    <span class="field-label">网关策略</span>
                    <select class="input select" name="pay_driver">
                        <option value="auto" <?= $cfg['driver'] === 'auto' ? 'selected' : '' ?>>自动（易支付 → 码支付 → 演示）</option>
                        <option value="epay" <?= $cfg['driver'] === 'epay' ? 'selected' : '' ?>>强制易支付</option>
                        <option value="codepay" <?= $cfg['driver'] === 'codepay' ? 'selected' : '' ?>>强制码支付</option>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">商品名称</span>
                    <input class="input" name="pay_product_name" value="<?= e($cfg['product_name']) ?>">
                </label>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:6px">
                <input type="checkbox" name="payment_demo" value="1" <?= $cfg['demo'] ? 'checked' : '' ?>> 未配置支付网关时使用演示支付（订单直接完成）
            </label>

            <hr class="hr">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                <span class="field-label" style="margin:0">易支付（彩虹易支付 / EPAY）</span>
                <label style="display:flex;align-items:center;gap:6px;font-size:12.5px"><input type="checkbox" name="epay_enabled" value="1" <?= $cfg['epay']['enabled'] ? 'checked' : '' ?>> 启用</label>
            </div>
            <div class="form-grid" style="margin-top:10px">
                <label class="field full"><span class="field-label">网关地址 <em>如 https://pay.example.com（自动补 /submit.php）</em></span><input class="input" name="epay_gateway" value="<?= e($cfg['epay']['gateway']) ?>"></label>
                <label class="field"><span class="field-label">商户 PID</span><input class="input" name="epay_pid" value="<?= e($cfg['epay']['pid']) ?>"></label>
                <label class="field"><span class="field-label">商户密钥 KEY</span><input class="input" name="epay_key" value="<?= e($cfg['epay']['key']) ?>"></label>
                <label class="field full"><span class="field-label">站点名称（sitename）</span><input class="input" name="epay_sitename" value="<?= e($cfg['epay']['sitename']) ?>"></label>
            </div>
            <p class="small muted" style="margin-top:8px">异步通知地址：
                <span class="mono"><?= e(absolute_url('/pay/notify/epay')) ?></span>
                <button type="button" class="copy-btn" data-copy="<?= e(absolute_url('/pay/notify/epay')) ?>">复制</button>
            </p>

            <hr class="hr">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                <span class="field-label" style="margin:0">码支付（CodePay）</span>
                <label style="display:flex;align-items:center;gap:6px;font-size:12.5px"><input type="checkbox" name="codepay_enabled" value="1" <?= $cfg['codepay']['enabled'] ? 'checked' : '' ?>> 启用</label>
            </div>
            <div class="form-grid" style="margin-top:10px">
                <label class="field full"><span class="field-label">网关地址 <em>如 https://pay.example.com（自动补 /mapi.php）</em></span><input class="input" name="codepay_gateway" value="<?= e($cfg['codepay']['gateway']) ?>"></label>
                <label class="field"><span class="field-label">商户 ID</span><input class="input" name="codepay_id" value="<?= e($cfg['codepay']['id']) ?>"></label>
                <label class="field"><span class="field-label">商户密钥</span><input class="input" name="codepay_key" value="<?= e($cfg['codepay']['key']) ?>"></label>
                <label class="field"><span class="field-label">支付宝 pay_id</span><input class="input" name="codepay_pay_id_alipay" value="<?= e($cfg['codepay']['pay_id_alipay']) ?>"></label>
                <label class="field"><span class="field-label">微信 pay_id</span><input class="input" name="codepay_pay_id_wechat" value="<?= e($cfg['codepay']['pay_id_wechat']) ?>"></label>
                <label class="field full"><span class="field-label">Token（可选）</span><input class="input" name="codepay_token" value="<?= e($cfg['codepay']['token']) ?>"></label>
            </div>
            <p class="small muted" style="margin-top:8px">异步通知地址：
                <span class="mono"><?= e(absolute_url('/pay/notify/codepay')) ?></span>
                <button type="button" class="copy-btn" data-copy="<?= e(absolute_url('/pay/notify/codepay')) ?>">复制</button>
            </p>
        </div>
    </div>

    <div class="btn-row" style="justify-content:flex-end">
        <button class="btn btn--primary" type="submit">保存全部设置</button>
    </div>
</form>

<script>
(function () {
  var grid = document.getElementById('skinGrid');
  var input = document.getElementById('defaultSkinInput');
  if (grid && input) {
    grid.addEventListener('click', function (e) {
      var card = e.target.closest('[data-skin-pick]');
      if (!card) return;
      input.value = card.getAttribute('data-value');
    });
  }

  var layoutGrid = document.getElementById('layoutGrid');
  var layoutInput = document.getElementById('uiLayoutInput');
  if (layoutGrid && layoutInput) {
    layoutGrid.addEventListener('click', function (e) {
      var card = e.target.closest('[data-layout-pick]');
      if (!card) return;
      layoutInput.value = card.getAttribute('data-value');
      layoutGrid.querySelectorAll('[data-layout-pick]').forEach(function (el) {
        el.classList.toggle('active', el === card);
      });
    });
  }
})();
</script>
