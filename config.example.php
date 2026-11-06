<?php
/**
 * 库洛汐 Kuluoxi · PHP 全栈版配置
 * 复制本文件为 config.php 后填写数据库信息。
 */
return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'kuluoxi',
        'user' => 'kuluoxi',
        'pass' => 'change-me',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => '库洛汐 Kuluoxi',
        'base_url' => '',           // 部署在子目录时填写，例如 /kuluoxi
        'debug' => true,
    ],
];
