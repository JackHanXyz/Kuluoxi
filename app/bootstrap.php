<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Shanghai');

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo '<h1>缺少配置文件</h1><p>请复制 <code>config.example.php</code> 为 <code>config.php</code>，并填写 MySQL 连接信息后运行 <code>php bin/install.php</code>。</p>';
    exit;
}

$GLOBALS['config'] = require $configFile;
if (!empty($GLOBALS['config']['app']['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/database.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/payments.php';
require __DIR__ . '/providers.php';

/* 模拟开通完成 & 到期停机（PHP 无定时任务，请求时顺带巡检） */
sweep_instances();

/* ---------------------------------- 通用 ---------------------------------- */
function config(string $key, $default = null)
{
    $parts = explode('.', $key);
    $value = $GLOBALS['config'];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = make_pdo();
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetch_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function execute(string $sql, array $params = []): int
{
    return q($sql, $params)->rowCount();
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function now_plus_days(int $days): string
{
    return date('Y-m-d H:i:s', time() + $days * 86400);
}

/* ---------------------------------- 输出 ---------------------------------- */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int $cents): string
{
    $value = $cents / 100;
    $text = number_format($value, 2, '.', ',');
    if (substr($text, -3) === '.00') {
        $text = substr($text, 0, -3);
    }
    return '¥' . $text;
}

function fmt_date(?string $datetime): string
{
    return $datetime ? date('Y-m-d', strtotime($datetime)) : '—';
}

function fmt_datetime(?string $datetime): string
{
    return $datetime ? date('m-d H:i', strtotime($datetime)) : '—';
}

function ago(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . ' 分钟前';
    if ($diff < 86400) return floor($diff / 3600) . ' 小时前';
    if ($diff < 2592000) return floor($diff / 86400) . ' 天前';
    return fmt_date($datetime);
}

function days_left(?string $datetime): int
{
    if (!$datetime) {
        return 0;
    }
    return (int)ceil((strtotime($datetime) - time()) / 86400);
}

function status_text(string $status): string
{
    return [
        'pending' => '待支付',
        'paid' => '已支付',
        'cancelled' => '已取消',
        'refunded' => '已退款',
        'provisioning' => '开通中',
        'running' => '运行中',
        'stopped' => '已关机',
        'suspended' => '已停机',
        'terminated' => '已销毁',
        'open' => '待处理',
        'answered' => '已回复',
        'closed' => '已关闭',
        'online' => '在线',
        'maintenance' => '维护',
        'active' => '正常',
        'frozen' => '冻结',
    ][$status] ?? $status;
}

function status_badge(string $status): string
{
    $cls = [
        'paid' => 'paid', 'running' => 'running', 'answered' => 'answered',
        'online' => 'online', 'active' => 'active', 'provisioning' => 'provisioning',
        'suspended' => 'suspended', 'frozen' => 'frozen', 'pending' => 'pending',
        'open' => 'pending', 'maintenance' => 'maintenance',
    ][$status] ?? 'stopped';
    return '<span class="badge badge--' . $cls . '"><i></i>' . e(status_text($status)) . '</span>';
}

/* ---------------------------------- URL ---------------------------------- */
function url(string $path = ''): string
{
    $base = rtrim((string)config('app.base_url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function redirect_external(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * 生成绝对地址（支付回调必须公网可访问的完整 URL）。
 */
function absolute_url(string $path = ''): string
{
    $base = rtrim((string)config('app.base_url', ''), '/');
    if (preg_match('#^https?://#i', $base)) {
        return $base . '/' . ltrim($path, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host . url($path);
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim((string)config('app.base_url', ''), '/');
    if ($base && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . trim($path, '/');
}

/* ---------------------------------- 会话 ---------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!hash_equals(csrf_token(), (string)$token)) {
        http_response_code(419);
        exit('页面已过期，请刷新后重试。');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_messages(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ---------------------------------- 鉴权 ---------------------------------- */
function current_user(bool $fresh = false): ?array
{
    static $user = null;
    static $loaded = false;
    if ($fresh) {
        $loaded = false;
        $user = null;
    }
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $row = fetch_one('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']]);
    if (!$row || $row['status'] !== 'active') {
        unset($_SESSION['uid']);
        return null;
    }
    $user = $row;
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', '请先登录');
        redirect('/login');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        flash('error', '需要管理员权限');
        redirect('/app');
    }
    return $user;
}

/* ---------------------------------- 模板 ---------------------------------- */
function render(string $view, array $data = [], string $layout = 'public'): void
{
    extract($data, EXTR_SKIP);
    $viewFile = dirname(__DIR__) . '/app/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        http_response_code(500);
        exit('模板不存在: ' . e($view));
    }
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    // 控制台/后台布局支持多套排版：sidebar / topbar / rail / console
    if ($layout === 'app') {
        $layoutMap = [
            'sidebar' => 'app',
            'topbar' => 'app-topbar',
            'rail' => 'app-rail',
            'console' => 'app-console',
        ];
        $layout = $layoutMap[current_layout()] ?? 'app';
    }
    $layoutFile = dirname(__DIR__) . '/app/views/layouts/' . $layout . '.php';
    require $layoutFile;
}

function view_partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/app/views/partials/' . $name . '.php';
}
