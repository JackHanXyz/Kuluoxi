<?php
declare(strict_types=1);

function admin_dashboard()
{
    $user = require_admin();
    $revenue = (int)fetch_one("SELECT COALESCE(SUM(amount),0) AS n FROM orders WHERE status = 'paid'")['n'];
    $monthRevenue = (int)fetch_one(
        "SELECT COALESCE(SUM(amount),0) AS n FROM orders WHERE status = 'paid' AND paid_at >= ?",
        [now_plus_days(-30)]
    )['n'];
    $users = (int)fetch_one("SELECT COUNT(*) AS n FROM users WHERE role = 'user'")['n'];
    $activeInstances = (int)fetch_one("SELECT COUNT(*) AS n FROM instances WHERE status IN ('running','stopped')")['n'];
    $provisioning = (int)fetch_one("SELECT COUNT(*) AS n FROM instances WHERE status = 'provisioning'")['n'];
    $pendingOrders = (int)fetch_one("SELECT COUNT(*) AS n FROM orders WHERE status = 'pending'")['n'];
    $openTickets = (int)fetch_one("SELECT COUNT(*) AS n FROM tickets WHERE status != 'closed'")['n'];
    $nodes = fetch_all('SELECT * FROM nodes ORDER BY id');

    $rows = fetch_all(
        "SELECT DATE(paid_at) AS d, SUM(amount) AS revenue FROM orders
         WHERE status = 'paid' AND paid_at >= ? GROUP BY DATE(paid_at)",
        [date('Y-m-d', strtotime('-13 days'))]
    );
    $map = [];
    foreach ($rows as $row) {
        $map[$row['d']] = (int)$row['revenue'];
    }
    $trend = [];
    for ($i = 13; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $trend[] = ['date' => $day, 'revenue' => $map[$day] ?? 0];
    }

    $planSales = fetch_all(
        'SELECT p.name, p.code, COUNT(o.id) AS count, COALESCE(SUM(o.amount),0) AS revenue
         FROM plans p LEFT JOIN orders o ON o.plan_id = p.id AND o.status = ?
         GROUP BY p.id ORDER BY revenue DESC',
        ['paid']
    );
    $recentOrders = fetch_all(
        'SELECT o.*, p.name AS plan_name, n.name AS node_name, u.nickname
         FROM orders o JOIN plans p ON p.id = o.plan_id JOIN nodes n ON n.id = o.node_id JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC, o.id DESC LIMIT 6'
    );
    $recentTickets = fetch_all(
        'SELECT t.*, u.nickname AS user_nickname,
            (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS message_count
         FROM tickets t JOIN users u ON u.id = t.user_id ORDER BY t.updated_at DESC LIMIT 5'
    );

    render('admin/dashboard', [
        'title' => '运营概览 · 库洛汐',
        'active' => 'admin-dashboard',
        'adminActive' => true,
        'user' => $user,
        'revenue' => $revenue,
        'monthRevenue' => $monthRevenue,
        'users' => $users,
        'activeInstances' => $activeInstances,
        'provisioning' => $provisioning,
        'pendingOrders' => $pendingOrders,
        'openTickets' => $openTickets,
        'nodes' => $nodes,
        'trend' => $trend,
        'planSales' => $planSales,
        'recentOrders' => $recentOrders,
        'recentTickets' => $recentTickets,
    ], 'app');
}

function admin_plans()
{
    $user = require_admin();
    render('admin/plans', [
        'title' => '套餐管理 · 库洛汐',
        'active' => 'admin-plans',
        'adminActive' => true,
        'user' => $user,
        'plans' => fetch_all('SELECT * FROM plans ORDER BY sort ASC, id ASC'),
    ], 'app');
}

function admin_plan_save()
{
    require_admin();
    $id = post_int('id');
    $code = strtolower(post('code'));
    $name = post('name');
    $tagline = post('tagline');
    if ($code === '' || $name === '' || post_int('price_yuan') <= 0) {
        flash('error', '编码、名称与价格必填');
        redirect('/admin/plans');
    }
    $data = [
        'code' => $code,
        'name' => $name,
        'tagline' => $tagline,
        'cpu' => post_int('cpu'),
        'memory_mb' => post_int('memory_gb') * 1024,
        'disk_gb' => post_int('disk_gb'),
        'bandwidth_gb' => post_int('bandwidth_gb'),
        'port_speed' => post('port_speed', '100 Mbps'),
        'price' => post_int('price_yuan') * 100,
        'setup_fee' => post_int('setup_yuan') * 100,
        'stock' => post_int('stock'),
        'sort' => post_int('sort'),
        'active' => isset($_POST['active']) ? 1 : 0,
        'highlight' => isset($_POST['highlight']) ? 1 : 0,
        'description' => post('description'),
    ];

    $duplicate = fetch_one('SELECT id FROM plans WHERE code = ? AND id != ?', [$code, $id]);
    if ($duplicate) {
        flash('error', '套餐编码已存在');
        redirect('/admin/plans');
    }

    if ($id > 0) {
        execute(
            'UPDATE plans SET code=?,name=?,tagline=?,cpu=?,memory_mb=?,disk_gb=?,bandwidth_gb=?,port_speed=?,price=?,setup_fee=?,stock=?,sort=?,active=?,highlight=?,description=? WHERE id=?',
            array_merge(array_values($data), [$id])
        );
        flash('success', '套餐已更新');
    } else {
        execute(
            'INSERT INTO plans (code,name,tagline,cpu,memory_mb,disk_gb,bandwidth_gb,port_speed,price,setup_fee,stock,sort,active,highlight,description)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            array_values($data)
        );
        flash('success', '套餐已创建');
    }
    redirect('/admin/plans');
}

function admin_plan_toggle(int $id)
{
    require_admin();
    $plan = fetch_one('SELECT * FROM plans WHERE id = ?', [$id]);
    if (!$plan) {
        flash('error', '套餐不存在');
        redirect('/admin/plans');
    }
    execute('UPDATE plans SET active = ? WHERE id = ?', [$plan['active'] ? 0 : 1, $id]);
    flash('success', $plan['active'] ? '套餐已下架' : '套餐已上架');
    redirect('/admin/plans');
}

function admin_plan_delete(int $id)
{
    require_admin();
    $plan = fetch_one('SELECT * FROM plans WHERE id = ?', [$id]);
    if (!$plan) {
        flash('error', '套餐不存在');
        redirect('/admin/plans');
    }
    $used = (int)fetch_one("SELECT COUNT(*) AS n FROM orders WHERE plan_id = ? AND status IN ('pending','paid')", [$id])['n'];
    if ($used > 0) {
        execute('UPDATE plans SET active = 0 WHERE id = ?', [$id]);
        flash('success', '该套餐已有订单，已改为下架');
    } else {
        execute('DELETE FROM plans WHERE id = ?', [$id]);
        flash('success', '套餐已删除');
    }
    redirect('/admin/plans');
}

function admin_nodes()
{
    $user = require_admin();
    render('admin/nodes', [
        'title' => '节点管理 · 库洛汐',
        'active' => 'admin-nodes',
        'adminActive' => true,
        'user' => $user,
        'nodes' => fetch_all('SELECT * FROM nodes ORDER BY id'),
    ], 'app');
}

function admin_node_save()
{
    require_admin();
    $id = post_int('id');
    $code = strtolower(post('code'));
    $name = post('name');
    if ($code === '' || $name === '') {
        flash('error', '节点名称与编码必填');
        redirect('/admin/nodes');
    }
    $fields = [
        'name' => $name,
        'region' => post('region', '未知'),
        'country' => post('country', '未知'),
        'flag' => post('flag', '🌐'),
        'provider' => post('provider', 'mock'),
        'api_url' => post('api_url'),
        'api_key' => post('api_key'),
        'status' => post('status') === 'maintenance' ? 'maintenance' : 'online',
        'capacity' => max(1, post_int('capacity', 100)),
    ];

    if ($id > 0) {
        execute(
            'UPDATE nodes SET name=?,region=?,country=?,flag=?,provider=?,api_url=?,api_key=?,status=?,capacity=? WHERE id=?',
            array_merge(array_values($fields), [$id])
        );
        flash('success', '节点已更新');
    } else {
        if (fetch_one('SELECT id FROM nodes WHERE code = ?', [$code])) {
            flash('error', '节点编码已存在');
            redirect('/admin/nodes');
        }
        execute(
            'INSERT INTO nodes (name,code,region,country,flag,provider,api_url,api_key,status,capacity,used,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            array_merge([$name, $code], array_values(array_diff_key($fields, ['name' => ''])), [0, now()])
        );
        flash('success', '节点已创建');
    }
    redirect('/admin/nodes');
}

function admin_node_status(int $id)
{
    require_admin();
    $node = fetch_one('SELECT * FROM nodes WHERE id = ?', [$id]);
    if (!$node) {
        flash('error', '节点不存在');
        redirect('/admin/nodes');
    }
    $next = $node['status'] === 'online' ? 'maintenance' : 'online';
    execute('UPDATE nodes SET status = ? WHERE id = ?', [$next, $id]);
    flash('success', $next === 'online' ? '节点已恢复上线' : '节点已进入维护');
    redirect('/admin/nodes');
}

function admin_node_delete(int $id)
{
    require_admin();
    $node = fetch_one('SELECT * FROM nodes WHERE id = ?', [$id]);
    if (!$node) {
        flash('error', '节点不存在');
        redirect('/admin/nodes');
    }
    $used = (int)fetch_one("SELECT COUNT(*) AS n FROM instances WHERE node_id = ? AND status IN ('running','stopped','provisioning','suspended')", [$id])['n'];
    if ($used > 0) {
        flash('error', '该节点仍有实例，无法删除');
        redirect('/admin/nodes');
    }
    execute('DELETE FROM nodes WHERE id = ?', [$id]);
    flash('success', '节点已删除');
    redirect('/admin/nodes');
}

function admin_orders()
{
    $user = require_admin();
    $status = get('status');
    $sql = 'SELECT o.*, p.name AS plan_name, n.name AS node_name, u.nickname
            FROM orders o JOIN plans p ON p.id = o.plan_id JOIN nodes n ON n.id = o.node_id JOIN users u ON u.id = o.user_id';
    $params = [];
    if ($status && in_array($status, ['pending', 'paid', 'cancelled'], true)) {
        $sql .= ' WHERE o.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';
    render('admin/orders', [
        'title' => '订单管理 · 库洛汐',
        'active' => 'admin-orders',
        'adminActive' => true,
        'user' => $user,
        'list' => fetch_all($sql, $params),
        'filter' => $status,
    ], 'app');
}

function admin_order_close(int $id)
{
    require_admin();
    $order = fetch_one('SELECT * FROM orders WHERE id = ?', [$id]);
    if (!$order || $order['status'] !== 'pending') {
        flash('error', '仅可关闭待支付订单');
        redirect('/admin/orders');
    }
    execute("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$id]);
    flash('success', '订单已关闭');
    redirect('/admin/orders');
}

function admin_users()
{
    $user = require_admin();
    $keyword = get('q');
    if ($keyword !== '') {
        $list = fetch_all(
            'SELECT * FROM users WHERE email LIKE ? OR nickname LIKE ? ORDER BY id DESC',
            ['%' . $keyword . '%', '%' . $keyword . '%']
        );
    } else {
        $list = fetch_all('SELECT * FROM users ORDER BY id DESC');
    }
    foreach ($list as &$row) {
        $row['instance_count'] = (int)fetch_one(
            "SELECT COUNT(*) AS n FROM instances WHERE user_id = ? AND status != 'terminated'",
            [$row['id']]
        )['n'];
        $row['total_spent'] = (int)fetch_one(
            "SELECT COALESCE(SUM(amount),0) AS n FROM orders WHERE user_id = ? AND status = 'paid'",
            [$row['id']]
        )['n'];
    }
    unset($row);

    render('admin/users', [
        'title' => '用户管理 · 库洛汐',
        'active' => 'admin-users',
        'adminActive' => true,
        'user' => $user,
        'list' => $list,
        'keyword' => $keyword,
    ], 'app');
}

function admin_user_balance(int $id)
{
    require_admin();
    $target = fetch_one('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$target) {
        flash('error', '用户不存在');
        redirect('/admin/users');
    }
    $yuan = (float)post('amount');
    $delta = (int)round($yuan * 100);
    if ($delta === 0) {
        flash('error', '金额不合法');
        redirect('/admin/users');
    }
    $balance = (int)$target['balance'] + $delta;
    if ($balance < 0) {
        flash('error', '调整后余额不能为负');
        redirect('/admin/users');
    }
    execute('UPDATE users SET balance = ? WHERE id = ?', [$balance, $id]);
    execute(
        'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
        [$id, $delta > 0 ? 'recharge' : 'payment', $delta, $balance, '管理员余额调整：' . (post('remark') ?: '无备注'), 'ADMIN', now()]
    );
    flash('success', '余额已调整');
    redirect('/admin/users');
}

function admin_user_status(int $id)
{
    require_admin();
    $target = fetch_one('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$target) {
        flash('error', '用户不存在');
        redirect('/admin/users');
    }
    if ($target['role'] === 'admin') {
        flash('error', '不能冻结管理员账户');
        redirect('/admin/users');
    }
    $next = $target['status'] === 'active' ? 'frozen' : 'active';
    execute('UPDATE users SET status = ? WHERE id = ?', [$next, $id]);
    flash('success', $next === 'active' ? '账户已恢复' : '账户已冻结');
    redirect('/admin/users');
}

function admin_instances()
{
    $user = require_admin();
    $status = get('status');
    $sql = 'SELECT i.*, p.name AS plan_name, n.name AS node_name, u.nickname, u.id AS owner_id
            FROM instances i JOIN plans p ON p.id = i.plan_id JOIN nodes n ON n.id = i.node_id JOIN users u ON u.id = i.user_id';
    $params = [];
    if ($status && in_array($status, ['provisioning', 'running', 'stopped', 'suspended', 'terminated'], true)) {
        $sql .= ' WHERE i.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY i.id DESC';
    render('admin/instances', [
        'title' => '实例管理 · 库洛汐',
        'active' => 'admin-instances',
        'adminActive' => true,
        'user' => $user,
        'list' => fetch_all($sql, $params),
        'filter' => $status,
        'osOptions' => OS_OPTIONS,
    ], 'app');
}

function admin_instance_action(int $id)
{
    require_admin();
    $instance = fetch_one('SELECT * FROM instances WHERE id = ?', [$id]);
    if (!$instance) {
        flash('error', '实例不存在');
        redirect('/admin/instances');
    }
    $action = post('action');
    switch ($action) {
        case 'suspend':
            execute("UPDATE instances SET status = 'suspended', updated_at = ? WHERE id = ?", [now(), $id]);
            flash('success', '实例已强制停机');
            break;
        case 'resume':
            execute("UPDATE instances SET status = 'running', updated_at = ? WHERE id = ?", [now(), $id]);
            flash('success', '实例已恢复运行');
            break;
        case 'reinstall':
            $os = post('os');
            if (!valid_os($os)) {
                flash('error', '系统镜像不支持');
                break;
            }
            execute(
                "UPDATE instances SET status = 'provisioning', os = ?, updated_at = ? WHERE id = ?",
                [$os, date('Y-m-d H:i:s', time() - 10), $id]
            );
            flash('success', '重装任务已下发');
            break;
        case 'terminate':
            provider_destroy($instance['provider_id']);
            execute("UPDATE instances SET status = 'terminated', updated_at = ? WHERE id = ?", [now(), $id]);
            execute('UPDATE nodes SET used = GREATEST(0, used - 1) WHERE id = ?', [$instance['node_id']]);
            flash('success', '实例已销毁');
            break;
        default:
            flash('error', '不支持的操作');
    }
    redirect('/admin/instances');
}

function admin_tickets()
{
    $user = require_admin();
    $status = get('status');
    $sql = 'SELECT t.*, u.nickname AS user_nickname,
            (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS message_count
            FROM tickets t JOIN users u ON u.id = t.user_id';
    $params = [];
    if ($status && in_array($status, ['open', 'answered', 'closed'], true)) {
        $sql .= ' WHERE t.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY t.updated_at DESC';
    render('admin/tickets', [
        'title' => '工单处理 · 库洛汐',
        'active' => 'admin-tickets',
        'adminActive' => true,
        'user' => $user,
        'list' => fetch_all($sql, $params),
        'filter' => $status,
    ], 'app');
}

function admin_ticket_show(int $id)
{
    $user = require_admin();
    $ticket = fetch_one(
        'SELECT t.*, u.nickname AS user_nickname FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ?',
        [$id]
    );
    if (!$ticket) {
        flash('error', '工单不存在');
        redirect('/admin/tickets');
    }
    $messages = fetch_all(
        'SELECT m.*, u.nickname FROM ticket_messages m LEFT JOIN users u ON u.id = m.user_id
         WHERE m.ticket_id = ? ORDER BY m.created_at ASC, m.id ASC',
        [$id]
    );
    render('admin/ticket', [
        'title' => '工单 #' . $id . ' · 库洛汐',
        'active' => 'admin-tickets',
        'adminActive' => true,
        'user' => $user,
        'ticket' => $ticket,
        'messages' => $messages,
    ], 'app');
}

function admin_ticket_reply(int $id)
{
    $user = require_admin();
    $ticket = fetch_one('SELECT * FROM tickets WHERE id = ?', [$id]);
    if (!$ticket) {
        flash('error', '工单不存在');
        redirect('/admin/tickets');
    }
    $body = post('body');
    if (mb_strlen($body) < 1) {
        flash('error', '回复内容不能为空');
        redirect('/admin/tickets/' . $id);
    }
    execute('INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)', [$id, $user['id'], $body, 1, now()]);
    execute("UPDATE tickets SET status = 'answered', updated_at = ? WHERE id = ?", [now(), $id]);
    flash('success', '回复已发送');
    redirect('/admin/tickets/' . $id);
}

function admin_ticket_close(int $id)
{
    require_admin();
    execute("UPDATE tickets SET status = 'closed', updated_at = ? WHERE id = ?", [now(), $id]);
    flash('success', '工单已关闭');
    redirect('/admin/tickets/' . $id);
}

/* --------------------------------- 系统设置 --------------------------------- */

function admin_settings()
{
    require_admin();
    render('admin/settings', [
        'title' => '系统设置 · 库洛汐',
        'active' => 'admin-settings',
        'adminActive' => true,
        'user' => current_user(),
        'skinList' => skin_list(),
        'currentSkin' => current_skin(),
        'layoutList' => layout_list(),
        'currentLayout' => current_layout(),
        'payConfig' => payment_config(),
        'payDriverReady' => payment_resolve_driver(),
        'providerSettings' => provider_settings(),
    ], 'app');
}

function admin_settings_save()
{
    require_admin();

    $textKeys = [
        'site_name', 'default_skin', 'ui_layout', 'pay_driver', 'pay_product_name',
        'epay_gateway', 'epay_pid', 'epay_key', 'epay_sitename',
        'codepay_gateway', 'codepay_id', 'codepay_key', 'codepay_token',
        'codepay_pay_id_alipay', 'codepay_pay_id_wechat',
        'provider_driver',
        'mofang_finance_api', 'mofang_finance_key', 'mofang_finance_product',
        'mofang_cloud_api', 'mofang_cloud_key', 'mofang_cloud_product',
    ];
    $boolKeys = ['payment_demo', 'epay_enabled', 'codepay_enabled'];

    foreach ($textKeys as $key) {
        $value = post($key);
        if ($key === 'default_skin' && !array_key_exists($value, skin_list())) {
            $value = 'tencent';
        }
        if ($key === 'ui_layout' && !array_key_exists($value, layout_list())) {
            $value = 'sidebar';
        }
        if ($key === 'pay_driver' && !in_array($value, ['auto', 'epay', 'codepay'], true)) {
            $value = 'auto';
        }
        if ($key === 'provider_driver' && !in_array($value, ['mock', 'mofang_finance', 'mofang_cloud'], true)) {
            $value = 'mock';
        }
        if ($key === 'site_name' && $value === '') {
            $value = '库洛汐 Kuluoxi';
        }
        setting_set($key, $value);
    }

    foreach ($boolKeys as $key) {
        setting_set($key, !empty($_POST[$key]) ? '1' : '0');
    }

    flash('success', '设置已保存');
    redirect('/admin/settings');
}
