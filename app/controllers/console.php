<?php
declare(strict_types=1);

function dashboard()
{
    $user = require_login();
    $instances = fetch_all(
        'SELECT i.*, p.name AS plan_name, n.name AS node_name
         FROM instances i JOIN plans p ON p.id = i.plan_id JOIN nodes n ON n.id = i.node_id
         WHERE i.user_id = ? AND i.status != ? ORDER BY i.id DESC LIMIT 6',
        [$user['id'], 'terminated']
    );
    $instanceCount = (int)fetch_one(
        "SELECT COUNT(*) AS n FROM instances WHERE user_id = ? AND status != 'terminated'",
        [$user['id']]
    )['n'];
    $runningCount = (int)fetch_one(
        "SELECT COUNT(*) AS n FROM instances WHERE user_id = ? AND status = 'running'",
        [$user['id']]
    )['n'];
    $pendingOrders = (int)fetch_one(
        "SELECT COUNT(*) AS n FROM orders WHERE user_id = ? AND status = 'pending'",
        [$user['id']]
    )['n'];
    $openTickets = (int)fetch_one(
        "SELECT COUNT(*) AS n FROM tickets WHERE user_id = ? AND status != 'closed'",
        [$user['id']]
    )['n'];
    $recentOrders = fetch_all(
        'SELECT o.*, p.name AS plan_name, n.name AS node_name
         FROM orders o JOIN plans p ON p.id = o.plan_id JOIN nodes n ON n.id = o.node_id
         WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5',
        [$user['id']]
    );
    $announcements = fetch_all('SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC LIMIT 3');
    $flow = fetch_all(
        'SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
        [$user['id']]
    );

    render('app/dashboard', [
        'title' => '控制台概览 · 库洛汐',
        'active' => 'dashboard',
        'user' => $user,
        'instances' => $instances,
        'instanceCount' => $instanceCount,
        'runningCount' => $runningCount,
        'pendingOrders' => $pendingOrders,
        'openTickets' => $openTickets,
        'recentOrders' => $recentOrders,
        'announcements' => $announcements,
        'flow' => $flow,
    ], 'app');
}

function instances()
{
    $user = require_login();
    $status = get('status');
    $sql = 'SELECT i.*, p.name AS plan_name, n.name AS node_name
            FROM instances i JOIN plans p ON p.id = i.plan_id JOIN nodes n ON n.id = i.node_id
            WHERE i.user_id = ? AND i.status != ?';
    $params = [$user['id'], 'terminated'];
    if ($status && in_array($status, ['running', 'stopped', 'provisioning', 'suspended'], true)) {
        $sql .= ' AND i.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY i.id DESC';
    $list = fetch_all($sql, $params);
    $all = fetch_all(
        "SELECT status, COUNT(*) AS n FROM instances WHERE user_id = ? AND status != 'terminated' GROUP BY status",
        [$user['id']]
    );
    $counts = [];
    foreach ($all as $row) {
        $counts[$row['status']] = (int)$row['n'];
    }

    render('app/instances', [
        'title' => '我的实例 · 库洛汐',
        'active' => 'instances',
        'user' => $user,
        'list' => $list,
        'filter' => $status,
        'counts' => $counts,
    ], 'app');
}

function own_instance(int $id, array $user): array
{
    $row = fetch_one(
        'SELECT i.*, p.name AS plan_name, p.price AS plan_price, n.name AS node_name, n.code AS node_code, n.region
         FROM instances i JOIN plans p ON p.id = i.plan_id JOIN nodes n ON n.id = i.node_id
         WHERE i.id = ? AND i.user_id = ?',
        [$id, $user['id']]
    );
    if (!$row) {
        flash('error', '实例不存在');
        redirect('/app/vps');
    }
    return $row;
}

function instance_show(int $id)
{
    $user = require_login();
    $instance = own_instance($id, $user);
    $events = fetch_all('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5', [$user['id']]);

    render('app/instance', [
        'title' => $instance['name'] . ' · 库洛汐',
        'active' => 'instances',
        'user' => $user,
        'instance' => $instance,
        'events' => $events,
        'osOptions' => OS_OPTIONS,
    ], 'app');
}

function instance_action(int $id)
{
    $user = require_login();
    $instance = own_instance($id, $user);
    $action = post('action');
    $back = '/app/vps/' . $id;

    if ($instance['status'] === 'terminated') {
        flash('error', '实例已销毁');
        redirect('/app/vps');
    }

    switch ($action) {
        case 'start':
        case 'stop':
        case 'reboot':
            if ($instance['status'] === 'provisioning') {
                flash('error', '实例正在开通中，请稍候');
                break;
            }
            if ($instance['status'] === 'suspended') {
                flash('error', '实例已到期停机，请先续费');
                break;
            }
            $status = $action === 'stop' ? 'stopped' : 'running';
            provider_power($instance['provider_id'], $action);
            execute('UPDATE instances SET status = ?, updated_at = ? WHERE id = ?', [$status, now(), $id]);
            flash('success', '实例已' . ($action === 'stop' ? '关机' : ($action === 'reboot' ? '重启' : '开机')));
            break;

        case 'reinstall':
            $os = post('os');
            if (!valid_os($os)) {
                flash('error', '请选择受支持的操作系统');
                break;
            }
            provider_reinstall($instance['provider_id'], $os);
            execute(
                "UPDATE instances SET status = 'provisioning', os = ?, updated_at = ? WHERE id = ?",
                [$os, date('Y-m-d H:i:s', time() - 10), $id]
            );
            flash('success', '重装任务已下发，约 6 秒后完成');
            break;

        case 'renew':
            $months = post_int('months');
            if (!in_array($months, [1, 3, 6, 12], true)) {
                flash('error', '续费周期不合法');
                break;
            }
            $amount = (int)$instance['plan_price'] * $months;
            if ((int)$user['balance'] < $amount) {
                flash('error', '余额不足，请先充值');
                break;
            }
            db()->beginTransaction();
            try {
                $balance = (int)$user['balance'] - $amount;
                execute('UPDATE users SET balance = ? WHERE id = ?', [$balance, $user['id']]);
                execute(
                    'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
                    [$user['id'], 'payment', -$amount, $balance, '续费实例 ' . $instance['name'] . ' ' . $months . ' 个月', 'RENEW-' . $id, now()]
                );
                $base = max(time(), strtotime($instance['expires_at']));
                $expires = date('Y-m-d H:i:s', $base + $months * 30 * 86400);
                $status = $instance['status'] === 'suspended' ? 'running' : $instance['status'];
                execute('UPDATE instances SET expires_at = ?, status = ?, updated_at = ? WHERE id = ?', [$expires, $status, now(), $id]);
                db()->commit();
                flash('success', '续费成功，到期时间已顺延');
            } catch (Throwable $e) {
                db()->rollBack();
                flash('error', '续费失败，请稍后重试');
            }
            break;

        case 'rename':
            $name = post('name');
            if (!valid_instance_name($name)) {
                flash('error', '实例名称需 3-32 位字母、数字或短横线');
                break;
            }
            execute('UPDATE instances SET name = ?, updated_at = ? WHERE id = ?', [$name, now(), $id]);
            flash('success', '实例名称已更新');
            $back = '/app/vps/' . $id;
            break;

        case 'destroy':
            provider_destroy($instance['provider_id']);
            execute("UPDATE instances SET status = 'terminated', updated_at = ? WHERE id = ?", [now(), $id]);
            execute('UPDATE nodes SET used = GREATEST(0, used - 1) WHERE id = ?', [$instance['node_id']]);
            flash('success', '实例已销毁');
            redirect('/app/vps');
    }

    redirect($back);
}

function buy_page()
{
    $user = require_login();
    $plans = array_map('plan_to_array', fetch_all('SELECT * FROM plans WHERE active = 1 ORDER BY sort ASC, id ASC'));
    $nodes = fetch_all('SELECT * FROM nodes ORDER BY id ASC');
    $selectedPlan = (int)get('plan');
    if (!$selectedPlan) {
        foreach ($plans as $plan) {
            if ($plan['highlight']) {
                $selectedPlan = $plan['id'];
                break;
            }
        }
    }
    if (!$selectedPlan && $plans) {
        $selectedPlan = $plans[0]['id'];
    }

    render('app/buy', [
        'title' => '购买服务器 · 库洛汐',
        'active' => 'buy',
        'user' => $user,
        'plans' => $plans,
        'nodes' => $nodes,
        'selectedPlan' => $selectedPlan,
        'osOptions' => OS_OPTIONS,
        'payDriver' => payment_resolve_driver(),
        'old' => $_SESSION['buy_old'] ?? [],
    ], 'app');
    unset($_SESSION['buy_old']);
}

function buy_submit()
{
    $user = require_login();
    $planId = post_int('plan_id');
    $nodeId = post_int('node_id');
    $months = post_int('months');
    $instanceName = post('instance_name');
    $os = post('os');
    $method = post('method', 'wallet');

    $plan = fetch_one('SELECT * FROM plans WHERE id = ? AND active = 1', [$planId]);
    $node = fetch_one('SELECT * FROM nodes WHERE id = ?', [$nodeId]);

    $fail = function (string $message) use ($planId) {
        flash('error', $message);
        redirect('/app/buy?plan=' . $planId);
    };

    if (!$plan) return $fail('套餐不可用');
    if ((int)$plan['stock'] <= 0) return $fail('该套餐已售罄');
    if (!$node) return $fail('请选择可用节点');
    if ($node['status'] !== 'online') return $fail($node['name'] . ' 正在维护中，请选择其它节点');
    if (!in_array($months, [1, 3, 6, 12], true)) return $fail('购买周期不合法');
    if (!valid_instance_name($instanceName)) return $fail('实例名称需 3-32 位字母、数字或短横线，且以字母数字开头');
    if (!valid_os($os)) return $fail('请选择受支持的操作系统');
    if (!in_array($method, ['wallet', 'alipay', 'wechat'], true)) return $fail('不支持的支付方式');

    $amount = (int)$plan['price'] * $months + (int)$plan['setup_fee'];
    if ($method === 'wallet' && (int)$user['balance'] < $amount) {
        return $fail('余额不足，请先充值或选择其它支付方式');
    }

    // 在线支付：先创建待支付订单，再跳转收银台
    if ($method !== 'wallet') {
        $no = order_no();
        execute(
            'INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$no, $user['id'], $planId, $nodeId, $months, $amount, 'pending', $method, $instanceName, $os, now(), null]
        );
        $orderId = (int)db()->lastInsertId();
        $order = fetch_one(
            'SELECT o.*, p.name AS plan_name FROM orders o JOIN plans p ON p.id = o.plan_id WHERE o.id = ?',
            [$orderId]
        );
        $pay = payment_start($order, $method);
        if ($pay['type'] === 'redirect') {
            redirect_external($pay['url']);
        }
        // 演示模式：直接结算
        $result = settle_order($orderId, $method . '-demo');
        if (!empty($result['ok'])) {
            flash('success', '支付成功，实例正在开通');
            redirect('/app/vps/' . ($result['instance_id'] ?? ''));
        }
        flash('error', '下单失败，请稍后重试');
        redirect('/app/buy?plan=' . $planId);
    }

    db()->beginTransaction();
    try {
        $no = order_no();
        execute(
            'INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$no, $user['id'], $planId, $nodeId, $months, $amount, 'paid', $method, $instanceName, $os, now(), now()]
        );
        $orderId = (int)db()->lastInsertId();

        if ($method === 'wallet') {
            $balance = (int)$user['balance'] - $amount;
            execute('UPDATE users SET balance = ? WHERE id = ?', [$balance, $user['id']]);
            execute(
                'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
                [$user['id'], 'payment', -$amount, $balance, '支付订单 ' . $no, $no, now()]
            );
        }
        execute('UPDATE plans SET stock = GREATEST(0, stock - 1) WHERE id = ?', [$planId]);
        execute(
            'INSERT INTO instances (user_id,order_id,plan_id,node_id,name,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,expires_at,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$user['id'], $orderId, $planId, $nodeId, $instanceName, $os, 'provisioning',
                (int)$plan['cpu'], (int)$plan['memory_mb'], (int)$plan['disk_gb'], (int)$plan['bandwidth_gb'],
                now_plus_days($months * 30), now(), now()]
        );
        $instanceId = (int)db()->lastInsertId();
        db()->commit();

        provision_instance($instanceId);
        flash('success', '开通成功，系统正在部署实例');
        redirect('/app/vps/' . $instanceId);
    } catch (Throwable $e) {
        db()->rollBack();
        flash('error', '下单失败，请稍后重试');
        redirect('/app/buy?plan=' . $planId);
    }
}

function orders()
{
    $user = require_login();
    $status = get('status');
    $sql = 'SELECT o.*, p.name AS plan_name, n.name AS node_name
            FROM orders o JOIN plans p ON p.id = o.plan_id JOIN nodes n ON n.id = o.node_id
            WHERE o.user_id = ?';
    $params = [$user['id']];
    if ($status && in_array($status, ['pending', 'paid', 'cancelled'], true)) {
        $sql .= ' AND o.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';
    $list = fetch_all($sql, $params);
    $counts = [];
    foreach (fetch_all('SELECT status, COUNT(*) AS n FROM orders WHERE user_id = ? GROUP BY status', [$user['id']]) as $row) {
        $counts[$row['status']] = (int)$row['n'];
    }

    render('app/orders', [
        'title' => '订单管理 · 库洛汐',
        'active' => 'orders',
        'user' => $user,
        'list' => $list,
        'filter' => $status,
        'counts' => $counts,
    ], 'app');
}

function order_pay(int $id)
{
    $user = require_login();
    $order = fetch_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$id, $user['id']]);
    if (!$order) {
        flash('error', '订单不存在');
        redirect('/app/orders');
    }
    if ($order['status'] !== 'pending') {
        flash('error', '订单已处理');
        redirect('/app/orders');
    }
    $method = post('method', 'wallet');
    if (!in_array($method, ['wallet', 'alipay', 'wechat'], true)) {
        flash('error', '不支持的支付方式');
        redirect('/app/orders');
    }
    if ($method === 'wallet' && (int)$user['balance'] < (int)$order['amount']) {
        flash('error', '余额不足，请先充值');
        redirect('/app/orders');
    }

    if ($method !== 'wallet') {
        $full = fetch_one(
            'SELECT o.*, p.name AS plan_name FROM orders o JOIN plans p ON p.id = o.plan_id WHERE o.id = ?',
            [$id]
        );
        execute('UPDATE orders SET payment_method = ? WHERE id = ?', [$method, $id]);
        $pay = payment_start($full, $method);
        if ($pay['type'] === 'redirect') {
            redirect_external($pay['url']);
        }
        $result = settle_order($id, $method . '-demo');
        if (!empty($result['ok'])) {
            flash('success', '支付成功，实例正在开通');
            redirect('/app/vps/' . ($result['instance_id'] ?? ''));
        }
        flash('error', '支付失败，请稍后重试');
        redirect('/app/orders');
    }

    db()->beginTransaction();
    try {
        if ($method === 'wallet') {
            $balance = (int)$user['balance'] - (int)$order['amount'];
            execute('UPDATE users SET balance = ? WHERE id = ?', [$balance, $user['id']]);
            execute(
                'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
                [$user['id'], 'payment', -(int)$order['amount'], $balance, '支付订单 ' . $order['order_no'], $order['order_no'], now()]
            );
        }
        execute("UPDATE orders SET status = 'paid', payment_method = ?, paid_at = ? WHERE id = ?", [$method, now(), $id]);
        execute('UPDATE plans SET stock = GREATEST(0, stock - 1) WHERE id = ?', [$order['plan_id']]);
        $plan = fetch_one('SELECT * FROM plans WHERE id = ?', [$order['plan_id']]);
        execute(
            'INSERT INTO instances (user_id,order_id,plan_id,node_id,name,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,expires_at,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$user['id'], $id, $order['plan_id'], $order['node_id'], $order['instance_name'], $order['os'], 'provisioning',
                (int)$plan['cpu'], (int)$plan['memory_mb'], (int)$plan['disk_gb'], (int)$plan['bandwidth_gb'],
                now_plus_days((int)$order['months'] * 30), now(), now()]
        );
        $instanceId = (int)db()->lastInsertId();
        db()->commit();

        provision_instance($instanceId);
        flash('success', '支付成功，实例正在开通');
        redirect('/app/vps/' . $instanceId);
    } catch (Throwable $e) {
        db()->rollBack();
        flash('error', '支付失败，请稍后重试');
        redirect('/app/orders');
    }
}

function order_cancel(int $id)
{
    $user = require_login();
    $order = fetch_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$id, $user['id']]);
    if (!$order) {
        flash('error', '订单不存在');
        redirect('/app/orders');
    }
    if ($order['status'] !== 'pending') {
        flash('error', '当前订单状态不可取消');
        redirect('/app/orders');
    }
    execute("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$id]);
    flash('success', '订单已取消');
    redirect('/app/orders');
}

function wallet()
{
    $user = require_login();
    $transactions = fetch_all('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 30', [$user['id']]);
    render('app/wallet', [
        'title' => '充值中心 · 库洛汐',
        'active' => 'wallet',
        'user' => $user,
        'transactions' => $transactions,
    ], 'app');
}

function wallet_recharge()
{
    $user = require_login();
    $yuan = (float)post('amount');
    $amount = (int)round($yuan * 100);
    if ($amount < 1000 || $amount > 5000000) {
        flash('error', '充值金额需在 ¥10 - ¥50,000 之间');
        redirect('/app/wallet');
    }
    $balance = (int)$user['balance'] + $amount;
    execute('UPDATE users SET balance = ? WHERE id = ?', [$balance, $user['id']]);
    execute(
        'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
        [$user['id'], 'recharge', $amount, $balance, '在线充值（模拟支付）', 'MOCK-' . random_int(100000, 999999), now()]
    );
    flash('success', '充值成功，当前余额 ' . money($balance));
    redirect('/app/wallet');
}

function tickets()
{
    $user = require_login();
    $list = fetch_all(
        'SELECT t.*, (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS message_count
         FROM tickets t WHERE t.user_id = ? ORDER BY t.updated_at DESC',
        [$user['id']]
    );
    render('app/tickets', [
        'title' => '工单支持 · 库洛汐',
        'active' => 'tickets',
        'user' => $user,
        'list' => $list,
    ], 'app');
}

function ticket_create()
{
    $user = require_login();
    $subject = post('subject');
    $body = post('body');
    $category = post('category', 'technical');
    $priority = post('priority', 'normal');

    if (mb_strlen($subject) < 4 || mb_strlen($subject) > 60) {
        flash('error', '标题需 4-60 个字符');
        redirect('/app/tickets');
    }
    if (mb_strlen($body) < 10 || mb_strlen($body) > 4000) {
        flash('error', '描述需 10-4000 个字符');
        redirect('/app/tickets');
    }
    if (!in_array($category, ['technical', 'network', 'billing', 'feature', 'other'], true)) {
        $category = 'technical';
    }
    if (!in_array($priority, ['low', 'normal', 'high'], true)) {
        $priority = 'normal';
    }

    execute(
        'INSERT INTO tickets (user_id,subject,category,priority,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',
        [$user['id'], $subject, $category, $priority, 'open', now(), now()]
    );
    $ticketId = (int)db()->lastInsertId();
    execute(
        'INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
        [$ticketId, $user['id'], $body, 0, now()]
    );
    flash('success', '工单已提交，支持团队将尽快响应');
    redirect('/app/tickets/' . $ticketId);
}

function ticket_show(int $id)
{
    $user = require_login();
    $ticket = fetch_one('SELECT * FROM tickets WHERE id = ? AND user_id = ?', [$id, $user['id']]);
    if (!$ticket) {
        flash('error', '工单不存在');
        redirect('/app/tickets');
    }
    $messages = fetch_all(
        'SELECT m.*, u.nickname FROM ticket_messages m LEFT JOIN users u ON u.id = m.user_id
         WHERE m.ticket_id = ? ORDER BY m.created_at ASC, m.id ASC',
        [$id]
    );
    render('app/ticket', [
        'title' => '工单 #' . $id . ' · 库洛汐',
        'active' => 'tickets',
        'user' => $user,
        'ticket' => $ticket,
        'messages' => $messages,
    ], 'app');
}

function ticket_reply(int $id)
{
    $user = require_login();
    $ticket = fetch_one('SELECT * FROM tickets WHERE id = ? AND user_id = ?', [$id, $user['id']]);
    if (!$ticket) {
        flash('error', '工单不存在');
        redirect('/app/tickets');
    }
    if ($ticket['status'] === 'closed') {
        flash('error', '工单已关闭');
        redirect('/app/tickets/' . $id);
    }
    $body = post('body');
    if (mb_strlen($body) < 1) {
        flash('error', '回复内容不能为空');
        redirect('/app/tickets/' . $id);
    }
    execute('INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)', [$id, $user['id'], $body, 0, now()]);
    execute("UPDATE tickets SET status = 'open', updated_at = ? WHERE id = ?", [now(), $id]);
    flash('success', '回复已发送');
    redirect('/app/tickets/' . $id);
}

function ticket_close(int $id)
{
    $user = require_login();
    $ticket = fetch_one('SELECT * FROM tickets WHERE id = ? AND user_id = ?', [$id, $user['id']]);
    if (!$ticket) {
        flash('error', '工单不存在');
        redirect('/app/tickets');
    }
    execute("UPDATE tickets SET status = 'closed', updated_at = ? WHERE id = ?", [now(), $id]);
    flash('success', '工单已关闭');
    redirect('/app/tickets/' . $id);
}

function settings()
{
    $user = require_login();
    $announcements = fetch_all('SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC');
    render('app/settings', [
        'title' => '账户设置 · 库洛汐',
        'active' => 'settings',
        'user' => $user,
        'announcements' => $announcements,
    ], 'app');
}

function settings_save()
{
    $user = require_login();
    $nickname = post('nickname');
    $password = post('password');
    $confirm = post('confirm');

    if ($nickname !== '') {
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 20) {
            flash('error', '昵称长度需在 2-20 个字符');
            redirect('/app/settings');
        }
        execute('UPDATE users SET nickname = ? WHERE id = ?', [$nickname, $user['id']]);
        flash('success', '昵称已更新');
    }

    if ($password !== '') {
        if (mb_strlen($password) < 8 || $password !== $confirm) {
            flash('error', '密码至少 8 位且需与确认密码一致');
            redirect('/app/settings');
        }
        execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        flash('success', '密码已更新');
    }

    redirect('/app/settings');
}
