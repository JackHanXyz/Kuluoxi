<?php
declare(strict_types=1);

const OS_OPTIONS = [
    'Ubuntu 24.04 LTS',
    'Ubuntu 22.04 LTS',
    'Debian 12',
    'AlmaLinux 9',
    'Rocky Linux 9',
    'CentOS Stream 9',
    'Windows Server 2022',
];

function post(string $key, $default = '')
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) && is_numeric($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function get(string $key, $default = '')
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

function valid_os(string $os): bool
{
    return in_array($os, OS_OPTIONS, true);
}

function valid_instance_name(string $name): bool
{
    return (bool)preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{2,31}$/', $name);
}

function order_no(): string
{
    return 'KX' . date('Ymd') . random_int(1000, 9999);
}

function category_text(string $category): string
{
    return [
        'technical' => '技术支持',
        'network' => '网络问题',
        'billing' => '账单支付',
        'feature' => '功能建议',
        'other' => '其他',
    ][$category] ?? $category;
}

function priority_text(string $priority): string
{
    return ['low' => '低', 'normal' => '普通', 'high' => '高'][$priority] ?? $priority;
}

function plan_to_array(array $plan): array
{
    return [
        'id' => (int)$plan['id'],
        'code' => $plan['code'],
        'name' => $plan['name'],
        'tagline' => $plan['tagline'],
        'cpu' => (int)$plan['cpu'],
        'memoryMb' => (int)$plan['memory_mb'],
        'diskGb' => (int)$plan['disk_gb'],
        'bandwidthGb' => (int)$plan['bandwidth_gb'],
        'portSpeed' => $plan['port_speed'],
        'price' => (int)$plan['price'],
        'setupFee' => (int)$plan['setup_fee'],
        'stock' => (int)$plan['stock'],
        'highlight' => (int)$plan['highlight'],
        'description' => $plan['description'],
    ];
}
