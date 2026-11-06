<?php
declare(strict_types=1);

/**
 * 傻瓜式安装向导 /install
 * 仅在 config.php 或 install.lock 缺失时由 index.php 载入；也可直接访问查看安装状态。
 */

$root = dirname(__DIR__);
$configFile = $root . '/config.php';
$lockFile = $root . '/install.lock';

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$installed = is_file($lockFile);
$step = (int)($_GET['step'] ?? 1);
if ($installed && $step < 4) {
    $step = 4;
}

$errors = [];
$notice = '';

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wz_csrf(): string
{
    if (empty($_SESSION['wz_csrf'])) {
        $_SESSION['wz_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['wz_csrf'];
}

function wz_check_csrf(): bool
{
    return isset($_POST['_token']) && hash_equals(wz_csrf(), (string)$_POST['_token']);
}

function wz_env_checks(): array
{
    return [
        ['PHP 版本 ≥ 8.0', PHP_VERSION_ID >= 80000, PHP_VERSION],
        ['PDO MySQL 扩展', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? '已启用' : '未启用'],
        ['mbstring 扩展', extension_loaded('mbstring'), extension_loaded('mbstring') ? '已启用' : '未启用'],
        ['curl 扩展（对接上游/支付）', extension_loaded('curl'), extension_loaded('curl') ? '已启用' : '未启用（可用兼容模式）'],
        ['项目根目录可写', is_writable(dirname(__DIR__)), is_writable(dirname(__DIR__)) ? '可写' : '不可写，无法生成 config.php'],
        ['public 目录存在', is_dir(__DIR__), is_dir(__DIR__) ? '存在' : '缺失'],
    ];
}

function wz_env_ok(): bool
{
    foreach (wz_env_checks() as [$label, $ok]) {
        if (!$ok) {
            return false;
        }
    }
    return true;
}

/* --------------------------------- 步骤处理 --------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    if (!wz_check_csrf()) {
        $errors[] = '会话已过期，请刷新页面重试。';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'database') {
            $db = [
                'host' => trim((string)($_POST['host'] ?? '127.0.0.1')),
                'port' => (int)($_POST['port'] ?? 3306),
                'name' => trim((string)($_POST['name'] ?? 'kuluoxi')),
                'user' => trim((string)($_POST['user'] ?? '')),
                'pass' => (string)($_POST['pass'] ?? ''),
                'charset' => 'utf8mb4',
            ];
            try {
                $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], $db['port']);
                $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $db['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $pdo->exec('USE `' . str_replace('`', '', $db['name']) . '`');
                $_SESSION['wz_db'] = $db;
                header('Location: ?step=3');
                exit;
            } catch (Throwable $e) {
                $errors[] = '数据库连接失败：' . $e->getMessage();
                $step = 2;
            }
        }

        if ($action === 'finish') {
            $db = $_SESSION['wz_db'] ?? null;
            $siteName = trim((string)($_POST['site_name'] ?? '库洛汐 Kuluoxi')) ?: '库洛汐 Kuluoxi';
            $email = trim((string)($_POST['admin_email'] ?? ''));
            $nickname = trim((string)($_POST['admin_nickname'] ?? '汐主')) ?: '汐主';
            $password = (string)($_POST['admin_password'] ?? '');
            $demo = !empty($_POST['demo']);

            if (!$db) {
                $errors[] = '数据库配置已丢失，请重新填写。';
                $step = 2;
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = '管理员邮箱格式不正确。';
                $step = 3;
            } elseif (strlen($password) < 8) {
                $errors[] = '管理员密码至少 8 位。';
                $step = 3;
            } else {
                // 写入 config.php
                $content = "<?php\n"
                    . "/**\n * 库洛汐 Kuluoxi · 自动生成的配置\n * 由安装向导写入：" . date('Y-m-d H:i:s') . "\n */\n"
                    . "return [\n"
                    . "    'database' => [\n"
                    . "        'host' => " . var_export($db['host'], true) . ",\n"
                    . "        'port' => " . (int)$db['port'] . ",\n"
                    . "        'name' => " . var_export($db['name'], true) . ",\n"
                    . "        'user' => " . var_export($db['user'], true) . ",\n"
                    . "        'pass' => " . var_export($db['pass'], true) . ",\n"
                    . "        'charset' => 'utf8mb4',\n"
                    . "    ],\n"
                    . "    'app' => [\n"
                    . "        'name' => " . var_export($siteName, true) . ",\n"
                    . "        'base_url' => '',\n"
                    . "        'debug' => false,\n"
                    . "    ],\n"
                    . "];\n";

                if (@file_put_contents($configFile, $content) === false) {
                    $errors[] = '无法写入 config.php，请检查项目根目录权限。';
                    $step = 3;
                } else {
                    try {
                        $GLOBALS['config'] = [
                            'database' => $db,
                            'app' => ['name' => $siteName, 'base_url' => '', 'debug' => false],
                        ];
                        require $root . '/app/database.php';

                        // 安装环境下的最小数据库函数（与 bootstrap 中的实现等价）
                        if (!function_exists('db')) {
                            function db(): PDO
                            {
                                static $pdo = null;
                                return $pdo ??= make_pdo();
                            }
                        }
                        if (!function_exists('execute')) {
                            function execute(string $sql, array $params = []): int
                            {
                                $stmt = db()->prepare($sql);
                                $stmt->execute($params);
                                return $stmt->rowCount();
                            }
                        }
                        if (!function_exists('fetch_one')) {
                            function fetch_one(string $sql, array $params = []): ?array
                            {
                                $stmt = db()->prepare($sql);
                                $stmt->execute($params);
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                return $row === false ? null : $row;
                            }
                        }
                        if (!function_exists('now')) {
                            function now(): string
                            {
                                return date('Y-m-d H:i:s');
                            }
                        }
                        if (!function_exists('now_plus_days')) {
                            function now_plus_days(int $days): string
                            {
                                return date('Y-m-d H:i:s', time() + $days * 86400);
                            }
                        }

                        install_schema();
                        if ($demo) {
                            seed_data();
                        }

                        // 管理员账号（若演示数据已创建同邮箱账号，则覆盖密码与昵称）
                        $exists = fetch_one('SELECT id FROM users WHERE email = ?', [$email]);
                        if ($exists) {
                            execute(
                                'UPDATE users SET nickname = ?, password_hash = ?, role = ? WHERE email = ?',
                                [$nickname, password_hash($password, PASSWORD_DEFAULT), 'admin', $email]
                            );
                        } else {
                            execute(
                                'INSERT INTO users (email, nickname, password_hash, role, balance, status, created_at) VALUES (?,?,?,?,?,?,?)',
                                [$email, $nickname, password_hash($password, PASSWORD_DEFAULT), 'admin', 0, 'active', now()]
                            );
                        }

                        // 默认设置
                        $defaults = [
                            'site_name' => $siteName,
                            'default_skin' => 'tencent',
                            'ui_layout' => 'sidebar',
                            'payment_demo' => '1',
                            'pay_driver' => 'auto',
                            'pay_product_name' => '云服务器',
                            'provider_driver' => 'mock',
                        ];
                        foreach ($defaults as $k => $v) {
                            execute(
                                'INSERT INTO settings (skey, svalue, updated_at) VALUES (?,?,?)
                                 ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = VALUES(updated_at)',
                                [$k, (string)$v, now()]
                            );
                        }

                        @file_put_contents($lockFile, 'installed at ' . now() . PHP_EOL);
                        unset($_SESSION['wz_db']);
                        header('Location: ?step=4');
                        exit;
                    } catch (Throwable $e) {
                        $errors[] = '初始化数据表失败：' . $e->getMessage();
                        $step = 3;
                    }
                }
            }
        }
    }
}

/* --------------------------------- 渲染 --------------------------------- */
$envChecks = wz_env_checks();
$oldDb = $_SESSION['wz_db'] ?? ['host' => '127.0.0.1', 'port' => 3306, 'name' => 'kuluoxi', 'user' => 'root', 'pass' => ''];
?>
<!doctype html>
<html lang="zh-CN" data-theme="light" data-skin="tencent">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 · 库洛汐 Kuluoxi</title>
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 26px 16px; background: var(--bg); }
        .wizard { width: min(760px, 100%); }
        .wizard-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 18px; }
        .steps { display: flex; gap: 6px; margin-bottom: 18px; }
        .steps div { flex: 1; text-align: center; font-size: 12px; color: var(--muted); padding: 7px 4px; border-radius: 6px; background: var(--surface-3); }
        .steps div.active { background: var(--primary); color: #fff; font-weight: 600; }
        .steps div.done { background: var(--primary-soft); color: var(--primary); }
        .check-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--line); font-size: 13px; }
        .check-row:last-child { border-bottom: none; }
        .check-row .ok { color: var(--green); }
        .check-row .bad { color: var(--danger); }
        .hint { font-size: 12px; color: var(--muted); line-height: 1.8; margin-top: 8px; }
        .done-icon { width: 54px; height: 54px; border-radius: 14px; display: grid; place-items: center; background: var(--green-soft); color: var(--green); font-size: 26px; margin: 0 auto 14px; }
    </style>
</head>
<body>
<div class="wizard">
    <div class="wizard-head">
        <span class="brand">
            <svg width="30" height="30" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="#0052d9"/><path d="M5 20.5c3.8-6.6 8.4-6.6 12.2 0 3.8 6.6 6.8 2.4 9-2.6" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><circle cx="24.5" cy="9.5" r="2.2" fill="#ffd08a"/></svg>
            <span class="brand-text"><span class="brand-cn">库洛汐</span><span class="brand-en">KULUOXI INSTALLER</span></span>
        </span>
        <span class="badge badge--pending"><i></i>安装向导</span>
    </div>

    <div class="steps">
        <div class="<?= $step === 1 ? 'active' : ($step > 1 ? 'done' : '') ?>">1 环境检测</div>
        <div class="<?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>">2 数据库</div>
        <div class="<?= $step === 3 ? 'active' : ($step > 3 ? 'done' : '') ?>">3 管理员</div>
        <div class="<?= $step === 4 ? 'active' : '' ?>">4 完成</div>
    </div>

    <?php if ($errors): ?>
        <div class="flash-stack">
            <?php foreach ($errors as $err): ?>
                <div class="flash flash--error"><span><?= h($err) ?></span></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <?php if ($step === 1): ?>
            <div class="card-head"><h3>环境检测</h3><span class="small muted">PHP <?= h(PHP_VERSION) ?></span></div>
            <div class="card-body">
                <?php foreach ($envChecks as [$label, $ok, $detail]): ?>
                    <div class="check-row">
                        <span><?= h($label) ?></span>
                        <span class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? '✓' : '✕' ?> <?= h($detail) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="btn-row" style="margin-top:16px;justify-content:flex-end">
                    <?php if (wz_env_ok()): ?>
                        <a class="btn btn--primary" href="?step=2">下一步</a>
                    <?php else: ?>
                        <span class="small" style="color:var(--danger)">请先解决标记为 ✕ 的项</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($step === 2): ?>
            <div class="card-head"><h3>数据库连接</h3><span class="small muted">MySQL 5.6+ / 8.0</span></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="_token" value="<?= h(wz_csrf()) ?>">
                    <input type="hidden" name="action" value="database">
                    <div class="form-grid">
                        <label class="field"><span class="field-label">主机</span><input class="input" name="host" value="<?= h($oldDb['host']) ?>"></label>
                        <label class="field"><span class="field-label">端口</span><input class="input" name="port" value="<?= h($oldDb['port']) ?>"></label>
                        <label class="field"><span class="field-label">数据库名</span><input class="input" name="name" value="<?= h($oldDb['name']) ?>" placeholder="kuluoxi"></label>
                        <label class="field"><span class="field-label">用户名</span><input class="input" name="user" value="<?= h($oldDb['user']) ?>"></label>
                        <label class="field full"><span class="field-label">密码</span><input class="input" type="password" name="pass" value="<?= h($oldDb['pass']) ?>"></label>
                    </div>
                    <p class="hint">数据库不存在时会尝试自动创建；账号需具备 CREATE / INSERT / ALTER 权限。</p>
                    <div class="btn-row" style="margin-top:16px;justify-content:space-between">
                        <a class="btn btn--ghost" href="?step=1">上一步</a>
                        <button class="btn btn--primary" type="submit">测试并继续</button>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 3): ?>
            <div class="card-head"><h3>站点与管理员</h3><span class="small muted">最后一步</span></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="_token" value="<?= h(wz_csrf()) ?>">
                    <input type="hidden" name="action" value="finish">
                    <label class="field"><span class="field-label">站点名称</span><input class="input" name="site_name" value="库洛汐 Kuluoxi"></label>
                    <div class="form-grid">
                        <label class="field"><span class="field-label">管理员邮箱</span><input class="input" type="email" name="admin_email" placeholder="admin@example.com" required></label>
                        <label class="field"><span class="field-label">昵称</span><input class="input" name="admin_nickname" value="汐主"></label>
                        <label class="field full"><span class="field-label">管理员密码 <em>至少 8 位</em></span><input class="input" type="password" name="admin_password" required></label>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:4px 0 8px">
                        <input type="checkbox" name="demo" value="1" checked> 同时安装演示数据（套餐 / 节点 / 示例订单）
                    </label>
                    <p class="hint">安装完成后会自动生成 <b>install.lock</b> 锁定安装程序。</p>
                    <div class="btn-row" style="margin-top:16px;justify-content:space-between">
                        <a class="btn btn--ghost" href="?step=2">上一步</a>
                        <button class="btn btn--primary" type="submit">开始安装</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="card-body" style="text-align:center;padding:36px 20px">
                <div class="done-icon">✓</div>
                <h2 style="font-size:20px">安装完成</h2>
                <p class="muted" style="font-size:13px;margin-top:6px">
                    <?= $installed ? '系统已安装。如需重装，请删除根目录 install.lock 后刷新。' : '数据表与管理员账号已创建，安装程序已锁定。' ?>
                </p>
                <?php if ($installed): ?>
                    <p class="hint" style="font-family:Consolas,monospace"><?= h(@file_get_contents($lockFile)) ?></p>
                <?php endif; ?>
                <div class="btn-row" style="justify-content:center;margin-top:20px">
                    <a class="btn btn--primary" href="<?= is_file($configFile) ? '/' : '?step=1' ?>">进入站点</a>
                    <a class="btn btn--ghost" href="/admin">进入后台</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <p class="small muted" style="text-align:center;margin-top:14px">库洛汐 Kuluoxi · PHP 全栈版安装程序</p>
</div>
</body>
</html>
