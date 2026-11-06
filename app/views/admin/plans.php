<div class="page-head">
    <div>
        <span class="eyebrow">PLANS</span>
        <h1>套餐管理</h1>
        <p>管理所有可售套餐，支持上下架、库存与推荐位。</p>
    </div>
    <button class="btn btn--primary" type="button" data-open-modal="planForm" onclick="fillPlan(null)">+ 新建套餐</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>编码</th><th>名称</th><th>CPU</th><th>内存</th><th>硬盘</th><th>流量</th><th>月费</th><th>安装费</th><th>库存</th><th>状态</th><th class="right">操作</th></tr></thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td class="mono"><?= e($p['code']) ?></td>
                    <td>
                        <strong><?= e($p['name']) ?></strong>
                        <?php if ((int)$p['highlight']): ?><span class="pill-tag pill-tag--amber" style="margin-left:6px">推荐</span><?php endif; ?>
                        <div class="small muted"><?= e($p['tagline']) ?></div>
                    </td>
                    <td class="num"><?= (int)$p['cpu'] ?> 核</td>
                    <td class="num"><?= $p['memory_mb'] / 1024 ?>G</td>
                    <td class="num"><?= (int)$p['disk_gb'] ?>G</td>
                    <td class="num"><?= (int)$p['bandwidth_gb'] ?>G</td>
                    <td class="num"><?= e(money((int)$p['price'])) ?></td>
                    <td class="num"><?= e(money((int)$p['setup_fee'])) ?></td>
                    <td class="num"><?= (int)$p['stock'] ?></td>
                    <td><?= (int)$p['active'] ? status_badge('active') : status_badge('stopped') ?></td>
                    <td class="right">
                        <div class="cell-actions">
                            <button class="btn btn--ghost btn--sm" type="button" data-open-modal="planForm" onclick='fillPlan(<?= json_encode([
                                "id"=>(int)$p['id'],"code"=>$p['code'],"name"=>$p['name'],"tagline"=>$p['tagline'],
                                "cpu"=>(int)$p['cpu'],"memory_gb"=>$p['memory_mb']/1024,"disk_gb"=>(int)$p['disk_gb'],
                                "bandwidth_gb"=>(int)$p['bandwidth_gb'],"port_speed"=>$p['port_speed'],
                                "price_yuan"=>(int)$p['price']/100,"setup_yuan"=>(int)$p['setup_fee']/100,
                                "stock"=>(int)$p['stock'],"sort"=>(int)$p['sort'],"active"=>(int)$p['active'],
                                "highlight"=>(int)$p['highlight'],"description"=>$p['description']
                            ], JSON_UNESCAPED_UNICODE) ?>)'>编辑</button>
                            <form method="post" action="<?= e(url('/admin/plans/' . $p['id'] . '/toggle')) ?>" style="display:inline">
                                <?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= (int)$p['active'] ? '下架' : '上架' ?></button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/plans/' . $p['id'] . '/delete')) ?>" style="display:inline" onsubmit="return confirm('确认删除该套餐？已有订单将改为下架。')">
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

<div class="modal-mask" data-modal="planForm" hidden>
    <div class="modal" style="max-width:640px">
        <div class="modal-head"><h3 id="planFormTitle">新建套餐</h3><button class="icon-btn" type="button" data-close-modal>✕</button></div>
        <form method="post" action="<?= e(url('/admin/plans/save')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="pf_id" value="0">
            <div class="modal-body">
                <div class="form-grid">
                    <label class="field"><span class="field-label">编码 *</span><input class="input" name="code" id="pf_code" required></label>
                    <label class="field"><span class="field-label">名称 *</span><input class="input" name="name" id="pf_name" required></label>
                </div>
                <label class="field"><span class="field-label">副标题</span><input class="input" name="tagline" id="pf_tagline"></label>
                <div class="form-grid">
                    <label class="field"><span class="field-label">CPU 核数</span><input class="input num" type="number" name="cpu" id="pf_cpu" value="1" min="1"></label>
                    <label class="field"><span class="field-label">内存 (GB)</span><input class="input num" type="number" name="memory_gb" id="pf_memory_gb" value="1" min="0.5" step="0.5"></label>
                    <label class="field"><span class="field-label">硬盘 (GB)</span><input class="input num" type="number" name="disk_gb" id="pf_disk_gb" value="20" min="1"></label>
                    <label class="field"><span class="field-label">月流量 (GB)</span><input class="input num" type="number" name="bandwidth_gb" id="pf_bandwidth_gb" value="1000" min="0"></label>
                    <label class="field"><span class="field-label">端口速率</span><input class="input" name="port_speed" id="pf_port_speed" value="100 Mbps"></label>
                    <label class="field"><span class="field-label">月费 (元) *</span><input class="input num" type="number" name="price_yuan" id="pf_price_yuan" value="0" min="0" step="0.01" required></label>
                    <label class="field"><span class="field-label">安装费 (元)</span><input class="input num" type="number" name="setup_yuan" id="pf_setup_yuan" value="0" min="0" step="0.01"></label>
                    <label class="field"><span class="field-label">库存</span><input class="input num" type="number" name="stock" id="pf_stock" value="999" min="0"></label>
                    <label class="field"><span class="field-label">排序</span><input class="input num" type="number" name="sort" id="pf_sort" value="100"></label>
                </div>
                <label class="field"><span class="field-label">描述</span><textarea class="input textarea" name="description" id="pf_description"></textarea></label>
                <div style="display:flex;gap:20px;font-size:13px">
                    <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="active" id="pf_active" checked> 上架销售</label>
                    <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="highlight" id="pf_highlight"> 推荐套餐</label>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn--ghost" type="button" data-close-modal>取消</button>
                <button class="btn btn--primary" type="submit">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function fillPlan(p){
  var t = document.getElementById('planFormTitle');
  var ids = ['id','code','name','tagline','cpu','memory_gb','disk_gb','bandwidth_gb','port_speed','price_yuan','setup_yuan','stock','sort','description'];
  if(!p){
    t.textContent = '新建套餐';
    ids.forEach(function(k){ var el = document.getElementById('pf_'+k); if(el) el.value = k==='id'?'0':(k==='cpu'||k==='disk_gb'||k==='stock'||k==='sort'?'':k==='price_yuan'||k==='setup_yuan'?'0':''); });
    document.getElementById('pf_cpu').value = 1;
    document.getElementById('pf_memory_gb').value = 1;
    document.getElementById('pf_disk_gb').value = 20;
    document.getElementById('pf_bandwidth_gb').value = 1000;
    document.getElementById('pf_stock').value = 999;
    document.getElementById('pf_sort').value = 100;
    document.getElementById('pf_active').checked = true;
    document.getElementById('pf_highlight').checked = false;
  } else {
    t.textContent = '编辑套餐';
    Object.keys(p).forEach(function(k){
      var el = document.getElementById('pf_'+k);
      if(el){ el.value = p[k]; }
    });
    document.getElementById('pf_active').checked = !!p.active;
    document.getElementById('pf_highlight').checked = !!p.highlight;
  }
}
</script>
