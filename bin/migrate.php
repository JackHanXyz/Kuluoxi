<?php
declare(strict_types=1);

/**
 * 数据库迁移：补齐后续新增的数据表 / 默认设置。
 * 用法：php bin/migrate.php
 */

date_default_timezone_set('Asia/Shanghai');

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "缺少 config.php，请先完成安装向导。\n");
    exit(1);
}

$GLOBALS['config'] = require $configFile;
require dirname(__DIR__) . '/app/bootstrap.php';

try {
    install_schema();
    echo "数据表已同步（含 settings）。\n";

    $defaults = [
        'site_name' => '库洛汐 Kuluoxi',
        'default_skin' => 'tencent',
        'ui_layout' => 'sidebar',
        'payment_demo' => '1',
        'pay_driver' => 'auto',
        'pay_product_name' => '云服务器',
        'provider_driver' => 'mock',
    ];
    foreach ($defaults as $key => $value) {
        $exists = fetch_one('SELECT skey FROM settings WHERE skey = ?', [$key]);
        if (!$exists) {
            execute(
                'INSERT INTO settings (skey, svalue, updated_at) VALUES (?,?,?)',
                [$key, $value, now()]
            );
            echo "写入默认设置：{$key} = {$value}\n";
        }
    }
    echo "迁移完成。\n";
} catch (Throwable $e) {
    fwrite(STDERR, '迁移失败：' . $e->getMessage() . PHP_EOL);
    exit(1);
}
