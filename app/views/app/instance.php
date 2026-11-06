<div class="page-head">
    <div class="detail-hero">
        <div class="avatar"><?= e(mb_strtoupper(mb_substr($instance['name'], 0, 1))) ?></div>
        <div>
            <h1 style="font-size:22px"><?= e($instance['name']) ?></h1>
            <div style="display:flex;gap:10px;align-items:center;margin-top:6px;flex-wrap:wrap">
                <?= status_badge($instance['status']) ?>
                <span class="small muted"><?= e($instance['plan_name']) ?> · <?= e($instance['node_name']) ?>（<?= e(strtoupper($instance['node_code'])) ?>）</span>
            </div>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/app/vps')) ?>">返回列表</a>
        <button class="btn btn--ghost btn--sm" type="button" data-open-modal="rename">重命名</button>
    </div>
</div>

<div class="metric-strip">
    <div><span>CPU</span><b><?= (int)$instance['cpu'] ?> 核</b></div>
    <div><span>内存</span><b><?= $instance['memory_mb'] / 1024 ?> GB</b></div>
    <div><span>NVMe 硬盘</span><b><?= (int)$instance['disk_gb'] ?> GB</b></div>
    <div><span>月流量</span><b><?= (int)$instance['bandwidth_gb'] ?> GB</b></div>
</div>

<div class="grid grid--2" style="margin-top:16px;align-items:start">
    <div style="display:flex;flex-direction:column;gap:14px">
        <div class="card">
            <div class="card-head"><h3>连接信息</h3><span class="small muted">仅本人可见</span></div>
            <div class="card-body">
                <div class="kv"><span>公网 IP</span><b class="mono"><?= e($instance['ip'] ?: '分配中') ?><?php if ($instance['ip']): ?><button type="button" class="copy-btn" data-copy="<?= e($instance['ip']) ?>">复制</button><?php endif; ?></b></div>
                <div class="kv"><span>SSH 端口</span><b class="mono">22</b></div>
                <div class="kv"><span>root 密码</span><b class="mono"><?= e($instance['root_password'] ?: '等待部署') ?><?php if ($instance['root_password']): ?><button type="button" class="copy-btn" data-copy="<?= e($instance['root_password']) ?>">复制</button><?php endif; ?></b></div>
                <div class="kv"><span>操作系统</span><b><?= e($instance['os']) ?></b></div>
                <div class="kv"><span>开通时间</span><b><?= e(fmt_date($instance['created_at'])) ?></b></div>
                <div class="kv"><span>到期时间</span><b><?= e(fmt_date($instance['expires_at'])) ?>（剩余 <?= max(0, days_left($instance['expires_at'])) ?> 天）</b></div>
            </div>
            <?php $pct = min(100, (float)$instance['traffic_used_gb'] / max(1, (int)$instance['bandwidth_gb']) * 100); ?>
            <div class="card-body" style="border-top:1px solid var(--line);padding-top:14px">
                <div class="small muted" style="margin-bottom:6px">本月流量</div>
                <span class="progress"><span style="width:<?= round($pct) ?>%" class="<?= $pct > 80 ? 'warn' : '' ?>"></span></span>
                <div class="small muted" style="margin-top:5px"><?= (float)$instance['traffic_used_gb'] ?> GB / <?= (int)$instance['bandwidth_gb'] ?> GB</div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>价格与续费</h3></div>
            <div class="card-body">
                <p class="small muted">续费将在到期时间基础上顺延，余额充足即可即时生效。</p>
                <div class="btn-row" style="margin-top:12px">
                    <button class="btn btn--primary btn--sm" type="button" data-open-modal="renew">续费实例</button>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/app/wallet')) ?>">充值余额</a>
                </div>
                <p class="small muted" style="margin-top:12px">当前余额 <?= e(money((int)$user['balance'])) ?></p>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px">
        <div class="card">
            <div class="card-head"><h3>电源与系统</h3></div>
            <div class="card-body">
                <div class="btn-row">
                    <form method="post" action="<?= e(url('/app/vps/' . $instance['id'] . '/action')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="start"><button class="btn btn--soft btn--sm" type="submit" <?= $instance['status'] === 'stopped' ? '' : 'disabled' ?>>开机</button></form>
                    <form method="post" action="<?= e(url('/app/vps/' . $instance['id'] . '/action')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="reboot"><button class="btn btn--ghost btn--sm" type="submit" <?= $instance['status'] === 'running' ? '' : 'disabled' ?>>重启</button></form>
                    <form method="post" action="<?= e(url('/app/vps/' . $instance['id'] . '/action')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="stop"><button class="btn btn--ghost btn--sm" type="submit" <?= $instance['status'] === 'running' ? '' : 'disabled' ?>>关机</button></form>
                    <button class="btn btn--ghost btn--sm" type="button" data-open-modal="reinstall">重装系统</button>
                </div>
                <p class="small muted" style="margin-top:12px">关机 / 重启为模拟操作；重装将重置系统与 root 密码。</p>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>操作记录</h3></div>
            <div class="card-body">
                <ul class="timeline">
                    <li><span class="t-dot"></span><div class="t-body"><b>实例创建</b><?= e(fmt_datetime($instance['created_at'])) ?> · 由订单自动开通</div></li>
                    <?php foreach ($events as $ev): ?>
                        <li><span class="t-dot"></span><div class="t-body"><b><?= $ev['type'] === 'payment' ? '扣费' : ($ev['type'] === 'recharge' ? '充值' : '退款') ?></b><?= e($ev['remark']) ?> · <?= e(fmt_datetime($ev['created_at'])) ?></div></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>危险操作</h3></div>
            <div class="card-body">
                <p class="small muted">销毁后实例数据不可恢复，且未到期费用不予退还。</p>
                <button class="btn btn--danger btn--sm" style="margin-top:12px" type="button" data-open-modal="destroy">销毁实例</button>
            </div>
        </div>
    </div>
</div>

<!-- 重装系统 -->
<div class="modal-mask" data-modal="reinstall" hidden>
    <div class="modal">
        <div class="modal-head"><h3>重装操作系统</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" action="<?= e(url('/app/vps/' . $instance['id'] . '/action')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reinstall">
            <div class="modal-body">
                <p class="small muted" style="margin-bottom:12px">重装会清空磁盘数据并重新生成 root 密码，请提前备份。</p>
                <?php foreach ($osOptions as $os): ?>
                    <label style="display:flex;gap:10px;align-items:center;padding:7px 0;font-size:13px">
                        <input type="radio" name="os" value="<?= e($os) ?>" <?= $instance['os'] === $os ? 'checked' : '' ?>>
                        <?= e($os) ?>
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

<!-- 续费 -->
<div class="modal-mask" data-modal="renew" hidden>
    <div class="modal">
        <div class="modal-head"><h3>续费实例</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" action="<?= e(url('/app/vps/' . $instance['id'] . '/action')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="renew">
            <div class="modal-body">
                <span class="field-label">续费周期</span>
                <div class="cycle-row" style="margin:8px 0 16px">
                    <?php foreach ([1, 3, 6, 12] as $m): ?>
                        <label class="cycle" style="cursor:pointer">
                            <input type="radio" name="months" value="<?= $m ?>" <?= $m === 1 ? 'checked' : '' ?> style="margin-right:5px"><?= $m ?> 个月
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="small muted">从 <?= e(fmt_date($instance['expires_at'])) ?> 起顺延，余额 <?= e(money((int)$user['balance'])) ?>。</p>
            </div>
            <div class="modal-foot">
                <button class="btn btn--ghost" type="button" data-close-modal>取消</button>
                <button class="btn btn--primary" type="submit">确认续费</button>
            </div>
        </form>
    </div>
</div>

<!-- 重命名 -->
<div class="modal-mask" data-modal="rename" hidden>
    <div class="modal">
        <div class="modal-head"><h3>重命名实例</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" action="<?= e(url('/app/vps/' . $instance['id'] . '/action')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="rename">
            <div class="modal-body">
                <label class="field">
                    <span class="field-label">实例名称 <em>3-32 位字母、数字或短横线</em></span>
                    <input class="input" name="name" value="<?= e($instance['name']) ?>" maxlength="32">
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn--ghost" type="button" data-close-modal>取消</button>
                <button class="btn btn--primary" type="submit">保存</button>
            </div>
        </form>
    </div>
</div>

<!-- 销毁 -->
<div class="modal-mask" data-modal="destroy" hidden>
    <div class="modal">
        <div class="modal-head"><h3>确认销毁实例</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" action="<?= e(url('/app/vps/' . $instance['id'] . '/action')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="destroy">
            <div class="modal-body">
                <p class="small">即将销毁 <b><?= e($instance['name']) ?></b>，此操作不可撤销，请确认已备份全部数据。</p>
            </div>
            <div class="modal-foot">
                <button class="btn btn--ghost" type="button" data-close-modal>再想想</button>
                <button class="btn btn--danger" type="submit">确认销毁</button>
            </div>
        </form>
    </div>
</div>
