<?php
declare(strict_types=1);

/**
 * 支付回调控制器：异步通知 + 同步跳回
 */

function pay_notify(string $driver)
{
    if (!in_array($driver, ['epay', 'codepay'], true)) {
        http_response_code(404);
        echo 'fail';
        return;
    }

    $params = array_merge($_GET, $_POST);
    if (!payment_verify_notify($driver, $params)) {
        http_response_code(400);
        echo 'fail';
        return;
    }

    $orderNo = (string)($params['out_trade_no'] ?? '');
    if ($orderNo === '') {
        echo 'fail';
        return;
    }

    // 易支付 TRADE_SUCCESS / 码支付 SUCCESS 或 1
    $status = (string)($params['trade_status'] ?? $params['status'] ?? 'TRADE_SUCCESS');
    if (!in_array($status, ['TRADE_SUCCESS', 'SUCCESS', 'success', '1'], true)) {
        echo 'fail';
        return;
    }

    $result = settle_order_by_no($orderNo, $driver);
    echo !empty($result['ok']) ? 'success' : 'fail';
}

function pay_return()
{
    $params = array_merge($_GET, $_POST);
    $driver = '';
    if (payment_verify_notify('epay', $params)) {
        $driver = 'epay';
    } elseif (payment_verify_notify('codepay', $params)) {
        $driver = 'codepay';
    }

    $orderNo = (string)($params['out_trade_no'] ?? '');
    if ($driver !== '' && $orderNo !== '') {
        $result = settle_order_by_no($orderNo, $driver);
        if (!empty($result['ok'])) {
            flash('success', '支付成功，实例正在开通');
            if (!empty($result['instance_id'])) {
                redirect('/app/vps/' . $result['instance_id']);
            }
            redirect('/app/orders');
        }
    }

    flash('error', '未能确认支付结果，请在订单页刷新查看');
    redirect('/app/orders');
}
