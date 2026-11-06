<div class="page-head">
    <div>
        <span class="eyebrow">USERS</span>
        <h1>用户管理</h1>
        <p>查看用户信息、调整余额、冻结或解冻账户。</p>
    </div>
</div>

<div class="toolbar">
    <form method="get" action="<?= e(url('/admin/users')) ?>" style="display:flex;gap:8px">
        <input class="input" name="q" value="<?= e($keyword) ?>" placeholder="搜索邮箱或昵称">
        <button class="btn btn--primary" type="submit">搜索</button>
    </form>
</div>

<div class="card">
    <?php if (!$list): ?>
        <div class="empty"><strong>没有匹配的用户</strong></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>ID</th><th>用户</th><th>邮箱</th><th>角色</th><th>余额</th><th>实例数</th><th>累计消费</th><th>状态</th><th>注册时间</th><th class="right">操作</th></tr></thead>
                <tbody>
                <?php foreach ($list as $u): ?>
                    <tr>
                        <td class="num">#<?= (int)$u['id'] ?></td>
                        <td><strong><?= e($u['nickname']) ?></strong></td>
                        <td class="small muted"><?= e($u['email']) ?></td>
                        <td><span class="pill-tag <?= $u['role'] === 'admin' ? 'pill-tag--amber' : '' ?>"><?= $u['role'] === 'admin' ? '管理员' : '用户' ?></span></td>
                        <td class="num" style="color:var(--amber)"><?= e(money((int)$u['balance'])) ?></td>
                        <td class="num"><?= (int)$u['instance_count'] ?></td>
                        <td class="num"><?= e(money((int)$u['total_spent'])) ?></td>
                        <td><?= status_badge($u['status']) ?></td>
                        <td class="small muted"><?= e(fmt_date($u['created_at'])) ?></td>
                        <td class="right">
                            <div class="cell-actions">
                                <button class="btn btn--ghost btn--sm" type="button" data-open-modal="balanceForm" onclick="fillBalance(<?= (int)$u['id'] ?>, '<?= e(addslashes($u['nickname'])) ?>')">余额</button>
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <form method="post" action="<?= e(url('/admin/users/' . $u['id'] . '/status')) ?>" style="display:inline">
                                        <?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= $u['status'] === 'active' ? '冻结' : '解冻' ?></button>
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

<div class="modal-mask" data-modal="balanceForm" hidden>
    <div class="modal">
        <div class="modal-head"><h3>调整用户余额</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" id="balanceFormAction" action="">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p class="small muted" style="margin-bottom:12px">用户：<b id="balUserName"></b></p>
                <label class="field">
                    <span class="field-label">调整金额 <em>正数充值，负数扣减，单位：元</em></span>
                    <input class="input num" type="number" name="amount" step="0.01" placeholder="如 100 或 -50" required>
                </label>
                <label class="field">
                    <span class="field-label">备注</span>
                    <input class="input" name="remark" placeholder="可选">
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn--ghost" type="button" data-close-modal>取消</button>
                <button class="btn btn--primary" type="submit">确认</button>
            </div>
        </form>
    </div>
</div>

<script>
function fillBalance(id, name){
  document.getElementById('balanceFormAction').action = '/admin/users/' + id + '/balance';
  document.getElementById('balUserName').textContent = name;
}
</script>
