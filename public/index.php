<?php
declare(strict_types=1);

$root = dirname(__DIR__);

// 未安装（或缺 install.lock）时进入傻瓜式安装向导；/install 始终可访问查看安装状态
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (!is_file($root . '/config.php') || !is_file($root . '/install.lock') || $requestPath === '/install') {
    require __DIR__ . '/install.php';
    exit;
}

require $root . '/app/bootstrap.php';

$routes = [
    // 公开
    ['GET', '/', 'public.php', 'landing'],
    ['GET', '/login', 'auth.php', 'login_page'],
    ['POST', '/login', 'auth.php', 'login_submit'],
    ['GET', '/register', 'auth.php', 'register_page'],
    ['POST', '/register', 'auth.php', 'register_submit'],
    ['POST', '/logout', 'auth.php', 'logout'],

    // 用户控制台
    ['GET', '/app', 'console.php', 'dashboard'],
    ['GET', '/app/vps', 'console.php', 'instances'],
    ['GET', '/app/vps/(\d+)', 'console.php', 'instance_show'],
    ['POST', '/app/vps/(\d+)/action', 'console.php', 'instance_action'],
    ['GET', '/app/buy', 'console.php', 'buy_page'],
    ['POST', '/app/buy', 'console.php', 'buy_submit'],
    ['GET', '/app/orders', 'console.php', 'orders'],
    ['POST', '/app/orders/(\d+)/pay', 'console.php', 'order_pay'],
    ['POST', '/app/orders/(\d+)/cancel', 'console.php', 'order_cancel'],
    ['GET', '/app/wallet', 'console.php', 'wallet'],
    ['POST', '/app/wallet/recharge', 'console.php', 'wallet_recharge'],
    ['GET', '/app/tickets', 'console.php', 'tickets'],
    ['POST', '/app/tickets', 'console.php', 'ticket_create'],
    ['GET', '/app/tickets/(\d+)', 'console.php', 'ticket_show'],
    ['POST', '/app/tickets/(\d+)/reply', 'console.php', 'ticket_reply'],
    ['POST', '/app/tickets/(\d+)/close', 'console.php', 'ticket_close'],
    ['GET', '/app/settings', 'console.php', 'settings'],
    ['POST', '/app/settings', 'console.php', 'settings_save'],

    // 支付回调
    ['GET', '/pay/notify/([a-z]+)', 'payments.php', 'pay_notify'],
    ['POST', '/pay/notify/([a-z]+)', 'payments.php', 'pay_notify'],
    ['GET', '/pay/return', 'payments.php', 'pay_return'],
    ['POST', '/pay/return', 'payments.php', 'pay_return'],

    // 管理后台
    ['GET', '/admin', 'admin.php', 'admin_dashboard'],
    ['GET', '/admin/plans', 'admin.php', 'admin_plans'],
    ['POST', '/admin/plans/save', 'admin.php', 'admin_plan_save'],
    ['POST', '/admin/plans/(\d+)/toggle', 'admin.php', 'admin_plan_toggle'],
    ['POST', '/admin/plans/(\d+)/delete', 'admin.php', 'admin_plan_delete'],
    ['GET', '/admin/nodes', 'admin.php', 'admin_nodes'],
    ['POST', '/admin/nodes/save', 'admin.php', 'admin_node_save'],
    ['POST', '/admin/nodes/(\d+)/status', 'admin.php', 'admin_node_status'],
    ['POST', '/admin/nodes/(\d+)/delete', 'admin.php', 'admin_node_delete'],
    ['GET', '/admin/orders', 'admin.php', 'admin_orders'],
    ['POST', '/admin/orders/(\d+)/close', 'admin.php', 'admin_order_close'],
    ['GET', '/admin/users', 'admin.php', 'admin_users'],
    ['POST', '/admin/users/(\d+)/balance', 'admin.php', 'admin_user_balance'],
    ['POST', '/admin/users/(\d+)/status', 'admin.php', 'admin_user_status'],
    ['GET', '/admin/instances', 'admin.php', 'admin_instances'],
    ['POST', '/admin/instances/(\d+)/action', 'admin.php', 'admin_instance_action'],
    ['GET', '/admin/tickets', 'admin.php', 'admin_tickets'],
    ['GET', '/admin/tickets/(\d+)', 'admin.php', 'admin_ticket_show'],
    ['POST', '/admin/tickets/(\d+)/reply', 'admin.php', 'admin_ticket_reply'],
    ['POST', '/admin/tickets/(\d+)/close', 'admin.php', 'admin_ticket_close'],
    ['GET', '/admin/settings', 'admin.php', 'admin_settings'],
    ['POST', '/admin/settings', 'admin.php', 'admin_settings_save'],
];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = current_path();

foreach ($routes as [$routeMethod, $pattern, $file, $function]) {
    if ($routeMethod !== $method) {
        continue;
    }
    if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
        if ($method === 'POST') {
            verify_csrf();
        }
        require_once dirname(__DIR__) . '/app/controllers/' . $file;
        array_shift($matches);
        // 路由捕获均为字符串，将纯数字参数转为 int 以匹配控制器类型声明
        $args = array_map(function ($m) {
            return is_string($m) && ctype_digit($m) ? (int)$m : $m;
        }, $matches);
        $function(...$args);
        exit;
    }
}

http_response_code(404);
render('404', ['title' => '页面不存在'], 'public');
