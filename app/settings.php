<?php
declare(strict_types=1);

/**
 * 系统设置（键值表）
 */

function settings_all(): array
{
    if (isset($GLOBALS['__settings_cache'])) {
        return $GLOBALS['__settings_cache'];
    }
    $cache = [];
    try {
        foreach (fetch_all('SELECT skey, svalue FROM settings') as $row) {
            $cache[$row['skey']] = $row['svalue'];
        }
    } catch (Throwable $e) {
        // 表尚未创建（例如未跑迁移）时返回空配置
        $cache = [];
    }
    $GLOBALS['__settings_cache'] = $cache;
    return $cache;
}

function setting(string $key, $default = null)
{
    $all = settings_all();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function setting_bool(string $key, bool $default = false): bool
{
    $value = setting($key, $default ? '1' : '0');
    return $value === '1' || $value === 'true';
}

function setting_set(string $key, string $value): void
{
    execute(
        'INSERT INTO settings (skey, svalue, updated_at) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = VALUES(updated_at)',
        [$key, $value, date('Y-m-d H:i:s')]
    );
    unset($GLOBALS['__settings_cache']);
}

function site_name(): string
{
    return (string)setting('site_name', '库洛汐 Kuluoxi');
}

/* ---------------------------------- 皮肤 ---------------------------------- */

function skin_list(): array
{
    return [
        'tencent' => ['name' => '腾讯云蓝', 'vendor' => '腾讯云', 'primary' => '#0052d9'],
        'aliyun' => ['name' => '阿里云橙', 'vendor' => '阿里云', 'primary' => '#ff6a00'],
        'huawei' => ['name' => '华为云红', 'vendor' => '华为云', 'primary' => '#cf0a2c'],
        'volcano' => ['name' => '火山引擎', 'vendor' => '火山引擎', 'primary' => '#1664ff'],
        'lime' => ['name' => '青柠绿', 'vendor' => '轻量云', 'primary' => '#16a34a'],
        'deepsea' => ['name' => '深海青', 'vendor' => '深海', 'primary' => '#0e7490'],
    ];
}

function current_skin(): string
{
    $skin = (string)setting('default_skin', 'tencent');
    return array_key_exists($skin, skin_list()) ? $skin : 'tencent';
}

function skin_options_html(string $current): string
{
    $html = '';
    foreach (skin_list() as $key => $skin) {
        $html .= '<option value="' . e($key) . '"' . ($key === $current ? ' selected' : '') . '>'
            . e($skin['name']) . '</option>';
    }
    return $html;
}

/* --------------------------------- 布局 ---------------------------------- */

function layout_list(): array
{
    return [
        'sidebar' => ['name' => '经典侧边栏', 'desc' => '左侧导航 + 内容区，SaaS 控制台常见形态'],
        'topbar' => ['name' => '顶部导航', 'desc' => '横向导航条 + 全宽内容，宽屏更舒展'],
        'rail' => ['name' => '图标窄栏', 'desc' => '极窄图标侧栏 + 大面积内容，沉浸式操作'],
        'console' => ['name' => '云控制台', 'desc' => '深色顶栏 + 分组菜单 + 面包屑，大厂云风格'],
    ];
}

function current_layout(): string
{
    $layout = (string)setting('ui_layout', 'sidebar');
    return array_key_exists($layout, layout_list()) ? $layout : 'sidebar';
}

function layout_options_html(string $current): string
{
    $html = '';
    foreach (layout_list() as $key => $layout) {
        $html .= '<option value="' . e($key) . '"' . ($key === $current ? ' selected' : '') . '>'
            . e($layout['name']) . '</option>';
    }
    return $html;
}

/**
 * 控制台 / 后台侧边导航条目。
 */
function user_nav(): array
{
    return [
        ['dashboard', '/app', '▦', '控制台概览'],
        ['instances', '/app/vps', '▤', '我的实例'],
        ['buy', '/app/buy', '＋', '购买服务器'],
        ['orders', '/app/orders', '▣', '订单管理'],
        ['wallet', '/app/wallet', '◎', '充值中心'],
        ['tickets', '/app/tickets', '✉', '工单支持'],
        ['settings', '/app/settings', '⚙', '账户设置'],
    ];
}

function admin_nav(): array
{
    return [
        ['admin-dashboard', '/admin', '◈', '运营概览'],
        ['admin-plans', '/admin/plans', '▤', '套餐管理'],
        ['admin-nodes', '/admin/nodes', '◉', '节点管理'],
        ['admin-orders', '/admin/orders', '▣', '订单管理'],
        ['admin-users', '/admin/users', '◍', '用户管理'],
        ['admin-instances', '/admin/instances', '❖', '实例管理'],
        ['admin-tickets', '/admin/tickets', '✉', '工单处理'],
        ['admin-settings', '/admin/settings', '⚙', '系统设置'],
    ];
}

/**
 * 云控制台布局的分组菜单。
 */
function console_nav_groups(bool $isAdmin): array
{
    if ($isAdmin) {
        return [
            '概览' => [['admin-dashboard', '/admin', '◈', '运营概览']],
            '资源' => [
                ['admin-plans', '/admin/plans', '▤', '套餐管理'],
                ['admin-nodes', '/admin/nodes', '◉', '节点管理'],
                ['admin-instances', '/admin/instances', '❖', '实例管理'],
            ],
            '交易' => [
                ['admin-orders', '/admin/orders', '▣', '订单管理'],
                ['admin-users', '/admin/users', '◍', '用户管理'],
            ],
            '服务' => [
                ['admin-tickets', '/admin/tickets', '✉', '工单处理'],
                ['admin-settings', '/admin/settings', '⚙', '系统设置'],
            ],
        ];
    }
    return [
        '概览' => [['dashboard', '/app', '▦', '控制台概览']],
        '资源' => [
            ['instances', '/app/vps', '▤', '我的实例'],
            ['buy', '/app/buy', '＋', '购买服务器'],
        ],
        '交易' => [
            ['orders', '/app/orders', '▣', '订单管理'],
            ['wallet', '/app/wallet', '◎', '充值中心'],
        ],
        '服务' => [
            ['tickets', '/app/tickets', '✉', '工单支持'],
            ['settings', '/app/settings', '⚙', '账户设置'],
        ],
    ];
}

/* --------------------------------- 资源地址 --------------------------------- */

function asset_url(string $path): string
{
    $file = dirname(__DIR__) . '/public/' . ltrim($path, '/');
    $version = is_file($file) ? (string)filemtime($file) : (string)time();
    return url('/' . ltrim($path, '/')) . '?v=' . $version;
}
