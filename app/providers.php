<?php
declare(strict_types=1);

/**
 * 上游 Provider 适配层
 * 支持：mock（演示） / mofang_finance（魔方财务面板） / mofang_cloud（魔方云 VPS 面板）
 *
 * 通用 REST 约定（两个魔方适配器共用，方便对接时只改字段名）：
 *   POST {api_url}
 *   action=create|suspend|unsuspend|reboot|reinstall|destroy
 *   api_key / token 鉴权，sign = md5(sorted(params + key))
 *   返回 JSON：{ "code": 0, "msg": "...", "data": { "hostid": "...", "ip": "...", "password": "..." } }
 */

function mock_ip(): string
{
    return sprintf('10.%d.%d.%d', random_int(1, 59), random_int(1, 254), random_int(1, 254));
}

function mock_password(int $length = 12): string
{
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

function provider_settings(): array
{
    return [
        'driver' => (string)setting('provider_driver', 'mock'),
        'mofang_finance' => [
            'api_url' => (string)setting('mofang_finance_api', ''),
            'key' => (string)setting('mofang_finance_key', ''),
            'product_id' => (string)setting('mofang_finance_product', ''),
        ],
        'mofang_cloud' => [
            'api_url' => (string)setting('mofang_cloud_api', ''),
            'key' => (string)setting('mofang_cloud_key', ''),
            'product_id' => (string)setting('mofang_cloud_product', ''),
        ],
    ];
}

function provider_driver_for_node(array $node): string
{
    $nodeProvider = (string)($node['provider'] ?? 'mock');
    if (in_array($nodeProvider, ['mofang_finance', 'mofang_cloud'], true)) {
        return $nodeProvider;
    }
    $driver = provider_settings()['driver'];
    return in_array($driver, ['mofang_finance', 'mofang_cloud'], true) ? $driver : 'mock';
}

function provider_is_configured(string $driver): bool
{
    $all = provider_settings();
    if (!isset($all[$driver])) {
        return false;
    }
    return $all[$driver]['api_url'] !== '' && $all[$driver]['key'] !== '';
}

/**
 * 发送上游请求；未安装 curl 时退回 file_get_contents。
 * @return array|null 解码后的 JSON
 */
function provider_request(string $url, array $payload, int $timeout = 8): ?array
{
    if ($url === '') {
        return null;
    }
    $body = http_build_query($payload);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno || !is_string($raw)) {
            return null;
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => $timeout,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null;
        }
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return null;
    }
    // 兼容 {code:0,data:{}} 与直接返回对象两种形态
    if (isset($json['code']) && (int)$json['code'] !== 0 && (int)$json['code'] !== 200) {
        return null;
    }
    return isset($json['data']) && is_array($json['data']) ? $json['data'] : $json;
}

/**
 * 向上游下发指令（幂等失败不抛异常，避免阻断演示）。
 */
function provider_dispatch(string $driver, array $node, string $action, array $extra = []): ?array
{
    if ($driver === 'mock' || !provider_is_configured($driver)) {
        return null;
    }
    $cfg = provider_settings()[$driver];
    $payload = array_merge([
        'action' => $action,
        'api_key' => $cfg['key'],
        'product_id' => $cfg['product_id'],
        'hostid' => $extra['hostid'] ?? '',
    ], $extra);
    $payload['sign'] = payment_sign($payload, $cfg['key']);
    return provider_request($cfg['api_url'], $payload);
}

function provider_create(array $node, string $os): array
{
    $driver = provider_driver_for_node($node);
    $result = provider_dispatch($driver, $node, 'create', [
        'os' => $os,
        'hostname' => $node['code'] ?? '',
        'region' => $node['region'] ?? '',
    ]);

    if (is_array($result) && (!empty($result['ip']) || !empty($result['hostid']))) {
        return [
            'provider_id' => (string)($result['hostid'] ?? ('mf-' . bin2hex(random_bytes(3)))),
            'ip' => (string)($result['ip'] ?? mock_ip()),
            'root_password' => (string)($result['password'] ?? mock_password()),
            'os' => $os,
            'driver' => $driver,
        ];
    }

    // 未配置 / 上游失败 → 演示开通
    return [
        'provider_id' => 'mock-' . bin2hex(random_bytes(3)),
        'ip' => mock_ip(),
        'root_password' => mock_password(),
        'os' => $os,
        'driver' => 'mock',
    ];
}

function provider_power(string $providerId, string $action): void
{
    $driver = provider_driver_for_instance($providerId);
    $map = ['start' => 'unsuspend', 'stop' => 'suspend', 'reboot' => 'reboot'];
    provider_dispatch($driver, [], $map[$action] ?? $action, ['hostid' => $providerId]);
}

function provider_reinstall(string $providerId, string $os): void
{
    $driver = provider_driver_for_instance($providerId);
    provider_dispatch($driver, [], 'reinstall', ['hostid' => $providerId, 'os' => $os]);
}

function provider_destroy(string $providerId): void
{
    $driver = provider_driver_for_instance($providerId);
    provider_dispatch($driver, [], 'destroy', ['hostid' => $providerId]);
}

function provider_driver_for_instance(string $providerId): string
{
    $settings = provider_settings();
    if (str_starts_with($providerId, 'mock-') || $providerId === '') {
        return 'mock';
    }
    return in_array($settings['driver'], ['mofang_finance', 'mofang_cloud'], true) ? $settings['driver'] : 'mock';
}

function get_provider_node(int $nodeId): ?array
{
    return fetch_one('SELECT * FROM nodes WHERE id = ?', [$nodeId]);
}

/**
 * 开通实例：写入 Provider 信息，状态置为 provisioning。
 * 稍后由 sweep_instances() 自动转为 running。
 */
function provision_instance(int $instanceId): void
{
    $instance = fetch_one('SELECT * FROM instances WHERE id = ?', [$instanceId]);
    if (!$instance) {
        return;
    }
    $node = get_provider_node((int)$instance['node_id']);
    $result = provider_create($node ?? [], $instance['os']);
    execute(
        'UPDATE instances SET provider_id = ?, ip = ?, root_password = ?, status = ?, updated_at = ? WHERE id = ?',
        [$result['provider_id'], $result['ip'], $result['root_password'], 'provisioning', date('Y-m-d H:i:s', time() - 10), $instanceId]
    );
    // updated_at 故意设为 10 秒前，让下一次请求的巡检直接完成开通（模拟秒开）
}
