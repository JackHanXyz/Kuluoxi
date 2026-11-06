<?php
$FEATURES = [
    ['01', '全闪 NVMe', 'PCIe 4.0 企业级阵列，随机读写是 SATA SSD 的 4 倍，建站与数据库冷启动更快。'],
    ['02', '秒级交付', '支付完成即创建实例、下发 IP 与 root 密码，平均 6 秒可用，无需排队等人工。'],
    ['03', '骨干低延迟', '香港、东京、新加坡、洛杉矶等节点，智能线路调度，东南亚平均 38ms。'],
];
$FAQS = [
    ['开通后多久可以使用？', '支付成功后系统自动创建实例并下发 IP 与 root 密码，通常在 6 秒内完成部署。若长时间停留在「开通中」，可在控制台提交工单。'],
    ['如何重装系统或重置密码？', '进入实例详情页点击「重装系统」，选择镜像确认即可。重装会清空磁盘数据并生成新的 root 密码，请提前备份。'],
    ['流量用完了会怎样？', '套餐内流量用尽后实例会被限速至 10Mbps，不会停机；可在控制台升级套餐或购买额外流量。'],
    ['支持退款吗？', '新购实例 24 小时内且流量使用低于 5GB，可提交工单申请按比例退款。已消耗资源、初装费与特价机型不支持退款。'],
];
?>

<section class="lp-hero">
    <div class="lp-hero-inner">
        <div class="lp-hero-copy">
            <span class="lp-kicker"><i class="live-dot"></i> 深海级算力 · KULUOXI CLOUD</span>
            <h1>算力随潮汐<br><span>六秒即达</span></h1>
            <p>NVMe 全闪存储、多地域智能调度、秒级自动开通。为独立开发者、建站团队与分销伙伴提供稳定、可负担的云服务器。</p>
            <div class="lp-actions">
                <a class="btn btn--primary btn--lg" href="<?= e(url(!empty($user) ? '/app/buy' : '/register')) ?>">
                    <?= !empty($user) ? '开通新实例' : '免费注册开通' ?>
                </a>
                <a class="lp-link" href="#plans">查看套餐与价格 <span>→</span></a>
            </div>
            <div class="lp-proof">
                <span><b>6s</b> 平均开通</span>
                <span><b>99.99%</b> 可用率</span>
                <span><b>24h</b> 无理由退款</span>
            </div>
        </div>

        <div class="lp-visual">
            <div class="lp-terminal">
                <div class="lp-terminal-bar">
                    <i></i><i></i><i></i>
                    <span>kuluoxi deploy</span>
                </div>
                <div class="lp-terminal-body">
                    <div class="tl"><span class="tl-p">$</span> kuluoxi deploy --plan light --region hk</div>
                    <div class="tl muted-tl"><span class="tl-ok">▸</span> allocating NVMe volume <span class="tl-dots">······</span> done</div>
                    <div class="tl muted-tl"><span class="tl-ok">▸</span> attaching network 1 Gbps <span class="tl-dots">····</span> done</div>
                    <div class="tl muted-tl"><span class="tl-ok">▸</span> booting Ubuntu 22.04 <span class="tl-dots">······</span> done</div>
                    <div class="tl tl-final"><span class="tl-ok">✓</span> tide-hk-01 online · 10.12.8.31 · <b>6.2s</b><span class="tl-caret"></span></div>
                </div>
            </div>
            <div class="lp-glow"></div>
        </div>
    </div>
</section>

<div class="lp-metrics">
    <div class="lp-metrics-inner">
        <div><b class="num"><?= (int)$stats['customers'] ?>+</b><span>活跃客户</span></div>
        <div><b class="num"><?= (int)$stats['instances'] ?></b><span>在线实例</span></div>
        <div><b class="num"><?= (int)$stats['nodes'] ?></b><span>全球节点</span></div>
        <div><b class="num"><?= e($stats['uptime']) ?></b><span>服务可用率</span></div>
    </div>
</div>

<section class="lp-section" id="plans">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">PRICING</span>
            <h2>按需选择，按月付费</h2>
            <p>六档配置覆盖个人博客到独享整机，全部含 NVMe 存储、DDoS 防护与工单支持。</p>
        </div>

        <div class="lp-plan-list">
            <?php foreach ($plans as $p): ?>
                <div class="lp-plan <?= $p['highlight'] ? 'is-hot' : '' ?>">
                    <div class="lp-plan-main">
                        <div class="lp-plan-title">
                            <h3><?= e($p['name']) ?></h3>
                            <?php if ($p['highlight']): ?><span class="lp-tag">推荐</span><?php endif; ?>
                            <?php if ($p['stock'] <= 0): ?><span class="lp-tag lp-tag--muted">售罄</span><?php endif; ?>
                        </div>
                        <p class="lp-plan-sub"><?= e($p['tagline']) ?></p>
                    </div>
                    <div class="lp-plan-specs">
                        <span><b><?= (int)$p['cpu'] ?></b> 核</span>
                        <span><b><?= $p['memoryMb'] / 1024 ?></b> GB 内存</span>
                        <span><b><?= (int)$p['diskGb'] ?></b> GB NVMe</span>
                        <span><b><?= $p['bandwidthGb'] ? (int)$p['bandwidthGb'] : '∞' ?></b><?= $p['bandwidthGb'] ? ' GB 流量' : ' 流量' ?></span>
                        <span class="lp-plan-speed"><?= e($p['portSpeed']) ?></span>
                    </div>
                    <div class="lp-plan-buy">
                        <div class="lp-plan-price"><b><?= e(money($p['price'])) ?></b><em>/ 月</em></div>
                        <?php if ($p['stock'] > 0): ?>
                            <a class="btn <?= $p['highlight'] ? 'btn--primary' : 'btn--ghost' ?> btn--sm" href="<?= e(url(!empty($user) ? '/app/buy?plan=' . $p['id'] : '/register')) ?>">选择</a>
                        <?php else: ?>
                            <span class="lp-plan-soldout">暂时售罄</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="lp-footnote">支持余额自动续费 · 到期前提醒 · 库存实时同步</p>
    </div>
</section>

<section class="lp-section lp-section--tint" id="features">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">WHY KULUOXI</span>
            <h2>复杂的留在深海，简单的交给你</h2>
        </div>
        <div class="lp-features">
            <?php foreach ($FEATURES as $f): ?>
                <div class="lp-feature">
                    <span class="lp-feature-num"><?= $f[0] ?></span>
                    <h3><?= e($f[1]) ?></h3>
                    <p><?= e($f[2]) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-section" id="nodes">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">NETWORK</span>
            <h2>全球节点，就近接入</h2>
            <p>实时机架负载，维护中的节点在购买时自动禁用。</p>
        </div>
        <div class="lp-nodes">
            <?php foreach ($nodes as $n): ?>
                <?php $pct = (int)round($n['used'] / max(1, (int)$n['capacity']) * 100); ?>
                <div class="lp-node">
                    <span class="lp-node-flag"><?= e($n['flag']) ?></span>
                    <div class="lp-node-meta">
                        <strong><?= e($n['name']) ?></strong>
                        <span><?= e(strtoupper($n['code'])) ?> · <?= e($n['country']) ?></span>
                    </div>
                    <div class="lp-node-load">
                        <span class="lp-node-pct"><?= $n['status'] === 'online' ? $pct . '%' : '维护' ?></span>
                        <span class="progress"><span style="width:<?= $pct ?>%" class="<?= $pct > 80 ? 'danger' : ($pct > 60 ? 'warn' : '') ?>"></span></span>
                    </div>
                    <span class="lp-node-lat"><?= 28 + ((int)$n['id'] * 7) % 40 ?>ms</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-section lp-section--tint" id="faq">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">FAQ</span>
            <h2>常见问题</h2>
        </div>
        <div class="lp-faq">
            <?php foreach ($FAQS as $faq): ?>
                <div class="faq-item">
                    <button class="faq-q" type="button" data-faq><span><?= e($faq[0]) ?></span><i>+</i></button>
                    <div class="faq-a"><p><?= e($faq[1]) ?></p></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-cta">
    <div class="lp-container lp-cta-inner">
        <div>
            <span class="lp-eyebrow lp-eyebrow--light">GET STARTED</span>
            <h2>今天开通，六秒后上线</h2>
            <p>注册即可开通，无需备案，24 小时内可无理由退款。</p>
        </div>
        <div class="lp-actions">
            <a class="btn btn--primary btn--lg" href="<?= e(url(!empty($user) ? '/app/buy' : '/register')) ?>"><?= !empty($user) ? '开通实例' : '免费注册' ?></a>
            <a class="lp-cta-link" href="<?= e(url(!empty($user) ? '/app' : '/login')) ?>"><?= !empty($user) ? '进入控制台' : '已有账号登录' ?></a>
        </div>
    </div>
</section>
