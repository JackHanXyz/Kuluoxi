<?php
declare(strict_types=1);

/**
 * 安装脚本：建表 + 写入演示数据
 * 用法：php bin/install.php
 * 前提：config.php 已配置，且数据库已创建（utf8mb4）。
 */

date_default_timezone_set('Asia/Shanghai');

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "缺少 config.php，请先复制 config.example.php 并填写数据库信息。\n");
    exit(1);
}

$GLOBALS['config'] = require $configFile;

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    install_schema();
    seed_data();
} catch (Throwable $e) {
    fwrite(STDERR, '安装失败：' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "安装完成：数据表已创建，演示账号已写入。" . PHP_EOL;
echo "用户 demo@kuluoxi.io / Demo@123456" . PHP_EOL;
echo "管理员 admin@kuluoxi.io / Admin@123456" . PHP_EOL;
