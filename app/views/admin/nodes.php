<div class="page-head">
    <div>
        <span class="eyebrow">NODES</span>
        <h1>节点管理</h1>
        <p>管理物理节点与可用区域，控制节点上下线与容量。</p>
    </div>
    <button class="btn btn--primary" type="button" data-open-modal="nodeForm" onclick="fillNode(null)">+ 新建节点</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>编码</th><th>名称</th><th>区域</th><th>提供商</th><th>容量</th><th>已用</th><th>负载</th><th>状态</th><th class="right">操作</th></tr></thead>
            <tbody>
            <?php foreach ($nodes as $n): ?>
                <?php $pct = $n['capacity'] > 0 ? min(100, round((int)$n['used'] / (int)$n['capacity'] * 100)) : 0; ?>
                <tr>
                    <td class="mono"><?= e($n['code']) ?></td>
                    <td><strong><?= e($n['flag']) ?> <?= e($n['name']) ?></strong><div class="small muted"><?= e($n['region']) ?> · <?= e($n['country']) ?></div></td>
                    <td><?= e($n['region']) ?></td>
                    <td><?= e($n['provider']) ?></td>
                    <td class="num"><?= (int)$n['capacity'] ?></td>
                    <td class="num"><?= (int)$n['used'] ?></td>
                    <td style="min-width:120px"><span class="progress"><span style="width:<?= $pct ?>%" class="<?= $pct > 80 ? 'warn' : '' ?>"></span></span><div class="small muted num" style="margin-top:3px"><?= $pct ?>%</div></td>
                    <td><?= status_badge($n['status']) ?></td>
                    <td class="right">
                        <div class="cell-actions">
                            <button class="btn btn--ghost btn--sm" type="button" data-open-modal="nodeForm" onclick='fillNode(<?= json_encode([
                                "id"=>(int)$n['id'],"code"=>$n['code'],"name"=>$n['name'],"region"=>$n['region'],
                                "country"=>$n['country'],"flag"=>$n['flag'],"provider"=>$n['provider'],
                                "api_url"=>$n['api_url'],"api_key"=>$n['api_key'],"status"=>$n['status'],
                                "capacity"=>(int)$n['capacity']
                            ], JSON_UNESCAPED_UNICODE) ?>)'>编辑</button>
                            <form method="post" action="<?= e(url('/admin/nodes/' . $n['id'] . '/status')) ?>" style="display:inline">
                                <?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= $n['status'] === 'online' ? '维护' : '上线' ?></button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/nodes/' . $n['id'] . '/delete')) ?>" style="display:inline" onsubmit="return confirm('确认删除该节点？仅无实例节点可删除。')">
                                <?= csrf_field() ?><button class="btn btn--danger btn--sm" type="submit">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-mask" data-modal="nodeForm" hidden>
    <div class="modal" style="max-width:560px">
        <div class="modal-head"><h3 id="nodeFormTitle">新建节点</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" action="<?= e(url('/admin/nodes/save')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="nf_id" value="0">
            <div class="modal-body">
                <div class="form-grid">
                    <label class="field"><span class="field-label">编码 *</span><input class="input" name="code" id="nf_code" required></label>
                    <label class="field"><span class="field-label">名称 *</span><input class="input" name="name" id="nf_name" required></label>
                    <label class="field"><span class="field-label">区域</span><input class="input" name="region" id="nf_region" value="亚太"></label>
                    <label class="field"><span class="field-label">国家/地区</span><input class="input" name="country" id="nf_country" value="中国"></label>
                    <label class="field"><span class="field-label">旗帜 emoji</span><input class="input" name="flag" id="nf_flag" value="🌐"></label>
                    <label class="field"><span class="field-label">提供商</span><input class="input" name="provider" id="nf_provider" value="mock"></label>
                    <label class="field"><span class="field-label">容量</span><input class="input num" type="number" name="capacity" id="nf_capacity" value="100" min="1"></label>
                    <label class="field"><span class="field-label">状态</span>
                        <select class="input select" name="status" id="nf_status">
                            <option value="online">在线</option>
                            <option value="maintenance">维护</option>
                        </select>
                    </label>
                </div>
                <label class="field"><span class="field-label">API 地址</span><input class="input" name="api_url" id="nf_api_url" placeholder="可选"></label>
                <label class="field"><span class="field-label">API Key</span><input class="input" name="api_key" id="nf_api_key" placeholder="可选"></label>
            </div>
            <div class="modal-foot">
                <button class="btn btn--ghost" type="button" data-close-modal>取消</button>
                <button class="btn btn--primary" type="submit">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function fillNode(n){
  var t = document.getElementById('nodeFormTitle');
  var ids = ['id','code','name','region','country','flag','provider','api_url','api_key','status','capacity'];
  if(!n){
    t.textContent = '新建节点';
    ids.forEach(function(k){ var el = document.getElementById('nf_'+k); if(el) el.value=''; });
    document.getElementById('nf_region').value = '亚太';
    document.getElementById('nf_country').value = '中国';
    document.getElementById('nf_flag').value = '🌐';
    document.getElementById('nf_provider').value = 'mock';
    document.getElementById('nf_status').value = 'online';
    document.getElementById('nf_capacity').value = 100;
  } else {
    t.textContent = '编辑节点';
    Object.keys(n).forEach(function(k){ var el = document.getElementById('nf_'+k); if(el) el.value = n[k]; });
  }
}
</script>
