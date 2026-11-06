<?php
declare(strict_types=1);

/**
 * 支付网关：易支付（彩虹易支付 / EPAY）与码支付（CodePay）。
 * 全部通过后台「系统设置」配置；未配置时回落到演示支付（订单直接标记已支付）。
 */

function payment_config(): array
{
    return [
        'demo' => setting_bool('payment_demo', true),
        'driver' => (string)setting('pay_driver', 'auto'),
        'product_name' => (string)setting('pay_product_name', '云服务器'),
        'epay' => [
            'enabled' => setting_bool('epay_enabled'),
            'gateway' => rtrim((string)setting('epay_gateway', ''), '/'),
            'pid' => (string)setting('epay_pid', ''),
            'key' => (string)setting('epay_key', ''),
            'sitename' => (string)setting('epay_sitename', setting('site_name', '库洛汐')),
        ],
        'codepay' => [
            'enabled' => setting_bool('codepay_enabled'),
            'gateway' => rtrim((string)setting('codepay_gateway', ''), '/'),
            'id' => (string)setting('codepay_id', ''),
            'key' => (string)setting('codepay_key', ''),
            'token' => (string)setting('codepay_token', ''),
            'pay_id_alipay' => (string)setting('codepay_pay_id_alipay', '1'),
            'pay_id_wechat' => (string)setting('codepay_pay_id_wechat', '3'),
        ],
    ];
}

function payment_driver_ready(string $driver): bool
{
    $cfg = payment_config();
    if ($driver === 'epay') {
        return $cfg['epay']['enabled'] && $cfg['epay']['gateway'] !== '' && $cfg['epay']['pid'] !== '' && $cfg['epay']['key'] !== '';
    }
    if ($driver === 'codepay') {
        return $cfg['codepay']['enabled'] && $cfg['codepay']['gateway'] !== '' && $cfg['codepay']['id'] !== '' && $cfg['codepay']['key'] !== '';
    }
    return false;
}

/**
 * 解析当前应使用的驱动：auto → 优先易支付，其次码支付，最后演示。
 */
function payment_resolve_driver(): string
{
    $cfg = payment_config();
    $want = $cfg['driver'];
    if ($want !== 'auto' && $want !== '') {
        return payment_driver_ready($want) ? $want : 'demo';
    }
    if (payment_driver_ready('epay')) {
        return 'epay';
    }
    if (payment_driver_ready('codepay')) {
        return 'codepay';
    }
    return 'demo';
}

function payment_available_methods(): array
{
    $driver = payment_resolve_driver();
    return [
        'wallet' => ['label' => '余额支付', 'online' => false, 'driver' => null],
        'alipay' => ['label' => '支付宝', 'online' => true, 'driver' => $driver],
        'wechat' => ['label' => '微信支付', 'online' => true, 'driver' => $driver],
    ];
}

function payment_notify_url(string $driver): string
{
    return absolute_url('/pay/notify/' . $driver);
}

function payment_return_url(string $method): string
{
    return absolute_url('/pay/return?method=' . urlencode($method));
}

function payment_money(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

/* --------------------------------- 签名 --------------------------------- */

/**
 * 通用签名：按键名排序后拼接 k=v&...，末尾追加 key 做 md5。兼容彩虹易支付。
 */
function payment_sign(array $params, string $key, bool $withKey = true): string
{
    unset($params['sign'], $params['sign_type'], $params['_token']);
    $params = array_filter($params, static function ($v) {
        return $v !== '' && $v !== null;
    });
    ksort($params);
    $str = urldecode(http_build_query($params));
    return md5($withKey ? $str . $key : $str);
}

/**
 * 码支付签名：固定字段顺序拼接。
 */
function codepay_sign(array $p, string $key): string
{
    $raw = 'id=' . ($p['id'] ?? '')
        . '&price=' . ($p['price'] ?? '')
        . '&out_trade_no=' . ($p['out_trade_no'] ?? '')
        . '&notify_url=' . ($p['notify_url'] ?? '')
        . '&return_url=' . ($p['return_url'] ?? '')
        . '&key=' . $key;
    return md5($raw);
}

/**
 * 码支付异步通知签名（字段更全，含 pay_id / type / param）。
 */
function codepay_sign_full(array $p, string $key): string
{
    $raw = 'id=' . ($p['id'] ?? '')
        . '&pay_id=' . ($p['pay_id'] ?? '')
        . '&type=' . ($p['type'] ?? '')
        . '&price=' . ($p['price'] ?? '')
        . '&out_trade_no=' . ($p['out_trade_no'] ?? '')
        . '&notify_url=' . ($p['notify_url'] ?? '')
        . '&return_url=' . ($p['return_url'] ?? '')
        . '&param=' . ($p['param'] ?? '')
        . '&key=' . $key;
    return md5($raw);
}

/**
 * 校验异步通知签名。为提高兼容性，同时尝试多种常见拼接方式。
 */
function payment_verify_notify(string $driver, array $params): bool
{
    $cfg = payment_config();
    if ($driver === 'epay') {
        $key = $cfg['epay']['key'];
        $sign = (string)($params['sign'] ?? '');
        if ($key === '' || $sign === '') {
            return false;
        }
        return hash_equals(payment_sign($params, $key), $sign)
            || hash_equals(payment_sign($params, $key, false), $sign);
    }
    if ($driver === 'codepay') {
        $key = $cfg['codepay']['key'];
        $sign = (string)($params['sign'] ?? '');
        if ($key === '' || $sign === '') {
            return false;
        }
        return hash_equals(codepay_sign($params, $key), $sign)
            || hash_equals(codepay_sign_full($params, $key), $sign)
            || hash_equals(payment_sign($params, $key), $sign);
    }
    return false;
}

/* --------------------------------- 发起支付 --------------------------------- */

/**
 * 发起在线支付。
 * @return array{type:string,url?:string}
 *   type = redirect（跳转收银台）| demo（演示模式，直接完成支付）
 */
function payment_start(array $order, string $channel): array
{
    $driver = payment_resolve_driver();
    if ($driver === 'demo') {
        return ['type' => 'demo', 'driver' => 'demo'];
    }

    $cfg = payment_config();
    $name = $cfg['product_name'] . ' · ' . ($order['plan_name'] ?? $order['order_no']);
    $money = payment_money((int)$order['amount']);

    if ($driver === 'epay') {
        $params = [
            'pid' => $cfg['epay']['pid'],
            'type' => $channel === 'wechat' ? 'wxpay' : 'alipay',
            'out_trade_no' => $order['order_no'],
            'notify_url' => payment_notify_url('epay'),
            'return_url' => payment_return_url($channel),
            'name' => $name,
            'money' => $money,
            'sitename' => $cfg['epay']['sitename'],
        ];
        $params['sign'] = payment_sign($params, $cfg['epay']['key']);
        $params['sign_type'] = 'MD5';
        $gateway = $cfg['epay']['gateway'];
        $endpoint = preg_match('/\.php$/', $gateway) ? $gateway : $gateway . '/submit.php';
        return ['type' => 'redirect', 'driver' => 'epay', 'url' => $endpoint . '?' . http_build_query($params)];
    }

    // 码支付
    $cd = $cfg['codepay'];
    $params = [
        'id' => $cd['id'],
        'pay_id' => $channel === 'wechat' ? $cd['pay_id_wechat'] : $cd['pay_id_alipay'],
        'type' => $channel === 'wechat' ? '3' : '1',
        'price' => $money,
        'out_trade_no' => $order['order_no'],
        'notify_url' => payment_notify_url('codepay'),
        'return_url' => payment_return_url($channel),
        'param' => '',
    ];
    $params['sign'] = codepay_sign($params, $cd['key']);
    if ($cd['token'] !== '') {
        $params['token'] = $cd['token'];
    }
    $gateway = $cd['gateway'];
    $endpoint = preg_match('/\.php$/', $gateway) ? $gateway : $gateway . '/mapi.php';
    return ['type' => 'redirect', 'driver' => 'codepay', 'url' => $endpoint . '?' . http_build_query($params)];
}

/* --------------------------------- 结算 --------------------------------- */

/**
 * 将待支付订单结算为已支付并开通实例（幂等）。
 * @return array{ok:bool, instance_id?:int, order_no?:string}
 */
function settle_order(int $orderId, string $paymentMethod = 'online'): array
{
    $order = fetch_one('SELECT * FROM orders WHERE id = ?', [$orderId]);
    if (!$order) {
        return ['ok' => false, 'message' => '订单不存在'];
    }
    if ($order['status'] === 'paid') {
        return ['ok' => true, 'already' => true, 'order_no' => $order['order_no']];
    }
    if ($order['status'] !== 'pending') {
        return ['ok' => false, 'message' => '订单状态不可支付'];
    }

    $plan = fetch_one('SELECT * FROM plans WHERE id = ?', [$order['plan_id']]);
    if (!$plan) {
        return ['ok' => false, 'message' => '套餐不存在'];
    }

    db()->beginTransaction();
    try {
        execute("UPDATE orders SET status = 'paid', payment_method = ?, paid_at = ? WHERE id = ?", [$paymentMethod, now(), $orderId]);
        execute('UPDATE plans SET stock = GREATEST(0, stock - 1) WHERE id = ?', [$order['plan_id']]);
        execute(
            'INSERT INTO instances (user_id,order_id,plan_id,node_id,name,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,expires_at,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$order['user_id'], $orderId, $order['plan_id'], $order['node_id'], $order['instance_name'], $order['os'], 'provisioning',
                (int)$plan['cpu'], (int)$plan['memory_mb'], (int)$plan['disk_gb'], (int)$plan['bandwidth_gb'],
                now_plus_days((int)$order['months'] * 30), now(), now()]
        );
        $instanceId = (int)db()->lastInsertId();
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        return ['ok' => false, 'message' => '结算失败'];
    }

    provision_instance($instanceId);
    return ['ok' => true, 'instance_id' => $instanceId, 'order_no' => $order['order_no']];
}

function settle_order_by_no(string $orderNo, string $paymentMethod = 'online'): array
{
    $order = fetch_one('SELECT * FROM orders WHERE order_no = ?', [$orderNo]);
    if (!$order) {
        return ['ok' => false, 'message' => '订单不存在'];
    }
    return settle_order((int)$order['id'], $paymentMethod);
}
