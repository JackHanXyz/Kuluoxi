<?php
$tabs = ['全部' => '', '开通中' => 'provisioning', '运行中' => 'running', '已关机' => 'stopped', '已停机' => 'suspended', '已销毁' => 'terminated'];
?>
<div class="page-head">
    <div>
        <span class="eyebrow">INSTANCES</span>
        <h1>实例管理</h1>
        <p>管理所有用户实例，支持强制停机、恢复、重装与销毁。</p>
    </div>
</div>

<div class="tabs">
    <?php foreach ($tabs as $label => $key): ?>
        <a class="tab <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('/admin/instances' . ($key ? '?status=' . $key : ''))) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$list): ?>
        <div class="empty"><strong>暂无实例</strong></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>ID</th><th>实例</th><th>用户</th><th>套餐</th><th>节点</th><th>IP</th><th>系统</th><th>状态</th><th class="right">到期</th><th class="right">操作</th></tr></thead>
                <tbody>
                <?php foreach ($list as $i): ?>
                    <tr>
                        <td class="num">#<?= (int)$i['id'] ?></td>
                        <td><strong><?= e($i['name']) ?></strong><div class="small muted"><?= (int)$i['cpu'] ?>C · <?= $i['memory_mb']/1024 ?>G · <?= (int)$i['disk_gb'] ?>G</div></td>
                        <td><?= e($i['nickname']) ?></td>
                        <td><?= e($i['plan_name']) ?></td>
                        <td><?= e($i['node_name']) ?></td>
                        <td class="mono small"><?= e($i['ip'] ?: '—') ?></td>
                        <td class="small"><?= e($i['os']) ?></td>
                        <td><?= status_badge($i['status']) ?></td>
                        <td class="right small muted"><?= e(fmt_date($i['expires_at'])) ?></td>
                        <td class="right">
                            <div class="cell-actions">
                                <?php if ($i['status'] === 'running' || $i['status'] === 'stopped'): ?>
                                    <form method="post" action="<?= e(url('/admin/instances/' . $i['id'] . '/action')) ?>" style="display:inline">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="<?= $i['status'] === 'running' ? 'suspend' : 'resume' ?>">
                                        <button class="btn btn--ghost btn--sm" type="submit"><?= $i['status'] === 'running' ? '停机' : '恢复' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($i['status'] !== 'terminated'): ?>
                                    <button class="btn btn--ghost btn--sm" type="button" data-open-modal="reinstall<?= (int)$i['id'] ?>">重装</button>
                                    <form method="post" action="<?= e(url('/admin/instances/' . $i['id'] . '/action')) ?>" style="display:inline" onsubmit="return confirm('确认销毁该实例？数据不可恢复。')">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="terminate">
                                        <button class="btn btn--danger btn--sm" type="submit">销毁</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($list as $i): ?>
    <?php if ($i['status'] !== 'terminated'): ?>
    <div class="modal-mask" data-modal="reinstall<?= (int)$i['id'] ?>" hidden>
        <div class="modal">
            <div class="modal-head"><h3>重装系统 - <?= e($i['name']) ?></h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
            <form method="post" action="<?= e(url('/admin/instances/' . $i['id'] . '/action')) ?>">
                <?= csrf_field() ?><input type="hidden" name="action" value="reinstall">
                <div class="modal-body">
                    <p class="small muted" style="margin-bottom:12px">重装将清空磁盘并重置 root 密码。</p>
                    <?php foreach ($osOptions as $os): ?>
                        <label style="display:flex;gap:10px;align-items:center;padding:7px 0;font-size:13px">
                            <input type="radio" name="os" value="<?= e($os) ?>" <?= $i['os'] === $os ? 'checked' : '' ?>><?= e($os) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="modal-foot">
                    <button class="btn btn--ghost" type="button" data-close-modal>取消</button>
                    <button class="btn btn--primary" type="submit">开始重装</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
