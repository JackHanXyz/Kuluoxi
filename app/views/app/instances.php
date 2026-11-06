<div class="page-head">
    <div>
        <span class="eyebrow">MY SERVERS</span>
        <h1>我的实例</h1>
        <p>支持开机 / 关机 / 重启 / 重装系统，状态实时同步。</p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('/app/buy')) ?>">开通新实例</a>
</div>

<?php
$tabs = [
    '' => '全部',
    'running' => '运行中',
    'stopped' => '已关机',
    'provisioning' => '开通中',
    'suspended' => '已停机',
];
$total = array_sum($counts);
?>
<div class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('/app/vps' . ($key ? '?status=' . $key : ''))) ?>">
            <?= e($label) ?><?= $key ? ' (' . ($counts[$key] ?? 0) . ')' : ' (' . (int)$total . ')' ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!$list): ?>
    <div class="card"><div class="empty"><div class="empty-art">◌</div><strong>没有符合条件的实例</strong><p>换个筛选条件，或开通一台新的服务器。</p><a class="btn btn--primary btn--sm" href="<?= e(url('/app/buy')) ?>">去购买</a></div></div>
<?php else: ?>
    <div class="grid grid--3">
        <?php foreach ($list as $vps): ?>
            <?php $left = max(0, days_left($vps['expires_at'])); ?>
            <div class="vps-card">
                <div class="vps-head">
                    <div>
                        <h3><a href="<?= e(url('/app/vps/' . $vps['id'])) ?>"><?= e($vps['name']) ?></a></h3>
                        <span><?= e($vps['plan_name']) ?> · <?= e($vps['node_name']) ?></span>
                    </div>
                    <?= status_badge($vps['status']) ?>
                </div>
                <div class="vps-specs">
                    <div><span>CPU</span><b><?= (int)$vps['cpu'] ?> 核</b></div>
                    <div><span>内存</span><b><?= $vps['memory_mb'] / 1024 ?>G</b></div>
                    <div><span>硬盘</span><b><?= (int)$vps['disk_gb'] ?>G</b></div>
                    <div><span>带宽</span><b><?= (int)$vps['bandwidth_gb'] ?>G</b></div>
                </div>
                <?php $pct = min(100, (float)$vps['traffic_used_gb'] / max(1, (int)$vps['bandwidth_gb']) * 100); ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px">
                        <span class="muted">流量使用</span>
                        <span class="num"><?= (float)$vps['traffic_used_gb'] ?> / <?= (int)$vps['bandwidth_gb'] ?> GB</span>
                    </div>
                    <span class="progress"><span style="width:<?= round($pct) ?>%" class="<?= $pct > 80 ? 'warn' : '' ?>"></span></span>
                </div>
                <div class="vps-foot">
                    <span class="vps-ip"><?= e($vps['ip'] ?: 'IP 分配中…') ?></span>
                    <span class="small" style="<?= $left < 5 ? 'color:var(--amber)' : 'color:var(--muted)' ?>"><?= $left ?> 天后到期</span>
                </div>
                <div class="btn-row" style="gap:6px">
                    <?php if ($vps['status'] === 'running'): ?>
                        <form method="post" action="<?= e(url('/app/vps/' . $vps['id'] . '/action')) ?>" style="display:inline">
                            <?= csrf_field() ?><input type="hidden" name="action" value="reboot">
                            <button class="btn btn--ghost btn--sm" type="submit">重启</button>
                        </form>
                        <form method="post" action="<?= e(url('/app/vps/' . $vps['id'] . '/action')) ?>" style="display:inline">
                            <?= csrf_field() ?><input type="hidden" name="action" value="stop">
                            <button class="btn btn--ghost btn--sm" type="submit">关机</button>
                        </form>
                    <?php elseif ($vps['status'] === 'stopped'): ?>
                        <form method="post" action="<?= e(url('/app/vps/' . $vps['id'] . '/action')) ?>" style="display:inline">
                            <?= csrf_field() ?><input type="hidden" name="action" value="start">
                            <button class="btn btn--soft btn--sm" type="submit">开机</button>
                        </form>
                    <?php elseif ($vps['status'] === 'suspended'): ?>
                        <a class="btn btn--soft btn--sm" href="<?= e(url('/app/vps/' . $vps['id'])) ?>">立即续费</a>
                    <?php endif; ?>
                    <a class="btn btn--ghost btn--sm" style="margin-left:auto" href="<?= e(url('/app/vps/' . $vps['id'])) ?>">详情</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
