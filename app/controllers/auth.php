<?php
declare(strict_types=1);

function login_page()
{
    if (current_user()) {
        redirect('/app');
    }
    render('auth/login', ['title' => '登录 · 库洛汐', 'old' => []], 'auth');
}

function login_submit()
{
    $email = strtolower(post('email'));
    $password = post('password');
    $user = fetch_one('SELECT * FROM users WHERE email = ?', [$email]);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('error', '邮箱或密码错误');
        render('auth/login', ['title' => '登录 · 库洛汐', 'old' => ['email' => $email]], 'auth');
        return;
    }
    if ($user['status'] !== 'active') {
        flash('error', '账户已被冻结，请联系客服');
        render('auth/login', ['title' => '登录 · 库洛汐', 'old' => ['email' => $email]], 'auth');
        return;
    }

    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    flash('success', '欢迎回来，' . $user['nickname']);
    redirect($user['role'] === 'admin' ? '/admin' : '/app');
}

function register_page()
{
    if (current_user()) {
        redirect('/app');
    }
    render('auth/register', ['title' => '注册 · 库洛汐', 'old' => []], 'auth');
}

function register_submit()
{
    $email = strtolower(post('email'));
    $nickname = post('nickname');
    $password = post('password');
    $confirm = post('confirm');
    $old = ['email' => $email, 'nickname' => $nickname];

    $fail = function (string $message) use ($old) {
        flash('error', $message);
        render('auth/register', ['title' => '注册 · 库洛汐', 'old' => $old], 'auth');
    };

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $fail('邮箱格式不正确');
    }
    if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 20) {
        return $fail('昵称长度需在 2-20 个字符');
    }
    if (mb_strlen($password) < 8) {
        return $fail('密码至少 8 位');
    }
    if ($password !== $confirm) {
        return $fail('两次输入的密码不一致');
    }
    if (fetch_one('SELECT id FROM users WHERE email = ?', [$email])) {
        return $fail('该邮箱已注册');
    }

    execute(
        'INSERT INTO users (email, nickname, password_hash, role, balance, created_at) VALUES (?,?,?,?,?,?)',
        [$email, $nickname, password_hash($password, PASSWORD_DEFAULT), 'user', 0, now()]
    );
    $user = fetch_one('SELECT * FROM users WHERE email = ?', [$email]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    flash('success', '注册成功，欢迎加入库洛汐');
    redirect('/app/buy');
}

function logout()
{
    $_SESSION = [];
    session_destroy();
    redirect('/');
}
