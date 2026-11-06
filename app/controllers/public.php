<?php
declare(strict_types=1);

function landing()
{
    $plans = array_map('plan_to_array', fetch_all('SELECT * FROM plans WHERE active = 1 ORDER BY sort ASC, id ASC'));
    $nodes = fetch_all('SELECT * FROM nodes ORDER BY id ASC');
    $stats = [
        'customers' => (int)fetch_one("SELECT COUNT(*) AS n FROM users WHERE role = 'user'")['n'],
        'instances' => (int)fetch_one("SELECT COUNT(*) AS n FROM instances WHERE status IN ('running','stopped')")['n'],
        'nodes' => (int)fetch_one('SELECT COUNT(*) AS n FROM nodes')['n'],
        'regions' => count(fetch_all('SELECT DISTINCT country FROM nodes')),
        'trafficTb' => round((float)(fetch_one('SELECT COALESCE(SUM(traffic_used_gb),0) AS n FROM instances')['n']) / 1024, 1),
        'uptime' => '99.99%',
        'responseMs' => 38,
    ];
    $announcements = fetch_all('SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC LIMIT 3');

    render('landing', [
        'title' => '库洛汐 Kuluoxi · 深海级 VPS 云服务',
        'plans' => $plans,
        'nodes' => $nodes,
        'stats' => $stats,
        'announcements' => $announcements,
        'user' => current_user(),
        'bodyClass' => 'landing',
    ], 'public');
}
