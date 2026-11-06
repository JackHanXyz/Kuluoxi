<?php
declare(strict_types=1);

function make_pdo(): PDO
{
    $db = config('database');
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'], $db['port'], $db['name'], $db['charset'] ?? 'utf8mb4'
    );
    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function install_schema(): void
{
    $statements = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(120) NOT NULL UNIQUE,
            nickname VARCHAR(40) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user','admin') NOT NULL DEFAULT 'user',
            balance INT NOT NULL DEFAULT 0,
            status ENUM('active','frozen') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS plans (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(40) NOT NULL UNIQUE,
            name VARCHAR(60) NOT NULL,
            tagline VARCHAR(160) NOT NULL DEFAULT '',
            cpu TINYINT UNSIGNED NOT NULL,
            memory_mb INT UNSIGNED NOT NULL,
            disk_gb INT UNSIGNED NOT NULL,
            bandwidth_gb INT UNSIGNED NOT NULL,
            port_speed VARCHAR(30) NOT NULL,
            price INT NOT NULL,
            setup_fee INT NOT NULL DEFAULT 0,
            stock INT NOT NULL DEFAULT 100,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            highlight TINYINT(1) NOT NULL DEFAULT 0,
            description TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS nodes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(60) NOT NULL,
            code VARCHAR(40) NOT NULL UNIQUE,
            region VARCHAR(60) NOT NULL,
            country VARCHAR(60) NOT NULL,
            flag VARCHAR(8) NOT NULL DEFAULT '🌐',
            provider VARCHAR(30) NOT NULL DEFAULT 'mock',
            api_url VARCHAR(255) NOT NULL DEFAULT '',
            api_key VARCHAR(255) NOT NULL DEFAULT '',
            status ENUM('online','maintenance') NOT NULL DEFAULT 'online',
            capacity INT NOT NULL DEFAULT 100,
            used INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_no VARCHAR(40) NOT NULL UNIQUE,
            user_id INT UNSIGNED NOT NULL,
            plan_id INT UNSIGNED NOT NULL,
            node_id INT UNSIGNED NOT NULL,
            months TINYINT UNSIGNED NOT NULL DEFAULT 1,
            amount INT NOT NULL,
            status ENUM('pending','paid','cancelled','refunded') NOT NULL DEFAULT 'pending',
            payment_method VARCHAR(20) NOT NULL DEFAULT '',
            instance_name VARCHAR(60) NOT NULL DEFAULT '',
            os VARCHAR(60) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            paid_at DATETIME NULL,
            KEY idx_user (user_id),
            KEY idx_plan (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS instances (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NULL,
            plan_id INT UNSIGNED NOT NULL,
            node_id INT UNSIGNED NOT NULL,
            provider_id VARCHAR(60) NOT NULL DEFAULT '',
            name VARCHAR(60) NOT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            root_password VARCHAR(60) NOT NULL DEFAULT '',
            os VARCHAR(60) NOT NULL DEFAULT '',
            status ENUM('provisioning','running','stopped','suspended','terminated') NOT NULL DEFAULT 'provisioning',
            cpu TINYINT UNSIGNED NOT NULL,
            memory_mb INT UNSIGNED NOT NULL,
            disk_gb INT UNSIGNED NOT NULL,
            bandwidth_gb INT UNSIGNED NOT NULL,
            traffic_used_gb DECIMAL(10,2) NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_user (user_id),
            KEY idx_node (node_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type ENUM('recharge','payment','refund') NOT NULL,
            amount INT NOT NULL,
            balance_after INT NOT NULL,
            remark VARCHAR(200) NOT NULL DEFAULT '',
            ref VARCHAR(60) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS tickets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NULL,
            subject VARCHAR(120) NOT NULL,
            category ENUM('technical','network','billing','feature','other') NOT NULL DEFAULT 'technical',
            priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
            status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ticket_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            body TEXT NOT NULL,
            is_staff TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            KEY idx_ticket (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS announcements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            level ENUM('info','warning') NOT NULL DEFAULT 'info',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS settings (
            skey VARCHAR(64) NOT NULL PRIMARY KEY,
            svalue TEXT NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($statements as $sql) {
        db()->exec($sql);
    }
}

function seed_data(): void
{
    if ((int)fetch_one('SELECT COUNT(*) AS n FROM users')['n'] > 0) {
        return;
    }

    execute(
        'INSERT INTO users (email, nickname, password_hash, role, balance, created_at) VALUES (?,?,?,?,?,?)',
        ['admin@kuluoxi.io', '汐主', password_hash('Admin@123456', PASSWORD_DEFAULT), 'admin', 0, now_plus_days(-30)]
    );
    execute(
        'INSERT INTO users (email, nickname, password_hash, role, balance, created_at) VALUES (?,?,?,?,?,?)',
        ['demo@kuluoxi.io', '潮汐旅人', password_hash('Demo@123456', PASSWORD_DEFAULT), 'user', 128000, now_plus_days(-30)]
    );

    $plans = [
        ['light', '轻汐', '个人开发与轻量站点', 1, 1024, 20, 1024, '100 Mbps', 1990, 0, 40, 1, 0, '入门级 NVMe 云服务器，适合个人博客、代理面板与轻量测试环境。'],
        ['wave', '汐涌', '小型应用与中型站点', 2, 2048, 40, 2048, '200 Mbps', 3990, 0, 30, 2, 1, '双核高主频 CPU 搭配企业级 NVMe，兼顾建站与小型数据库负载。'],
        ['flow', '洛汐', '团队项目与生产服务', 4, 4096, 80, 4096, '500 Mbps', 7990, 0, 20, 3, 0, '四核独享算力，适合容器编排、生产环境与中等流量的业务系统。'],
        ['abyss', '深海', '高并发与重载业务', 8, 8192, 160, 8192, '1 Gbps', 15990, 0, 12, 4, 0, '八核大内存机型，面向高并发 API、游戏联机与数据库集群。'],
        ['aurora', '极光', '大规模分布式部署', 16, 16384, 320, 16384, '1 Gbps', 31990, 0, 6, 5, 0, '旗舰计算节点，适合分布式推理前置、集群与大规模容器化业务。'],
        ['dedicated', '独享潮台', '独享资源 · 专属整机', 32, 65536, 1024, 0, '2 Gbps', 89900, 39900, 2, 6, 0, '整机独享物理资源，NVMe 阵列 + 2Gbps 专线，含专属技术支持。'],
    ];
    foreach ($plans as $p) {
        execute(
            'INSERT INTO plans (code,name,tagline,cpu,memory_mb,disk_gb,bandwidth_gb,port_speed,price,setup_fee,stock,sort,highlight,description)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            $p
        );
    }

    $nodes = [
        ['香港 · 将军澳', 'hk-01', '香港', '中国香港', '🇭🇰', 'online', 120, 74],
        ['香港 · 荃湾', 'hk-02', '香港', '中国香港', '🇭🇰', 'online', 80, 31],
        ['东京 · 品川', 'tyo-01', '东京', '日本', '🇯🇵', 'online', 100, 63],
        ['新加坡 · 裕廊', 'sgp-01', '新加坡', '新加坡', '🇸🇬', 'online', 90, 44],
        ['洛杉矶 · 弗里蒙特', 'lax-01', '洛杉矶', '美国', '🇺🇸', 'online', 100, 58],
        ['法兰克福 · 勒斯', 'fra-01', '法兰克福', '德国', '🇩🇪', 'maintenance', 70, 35],
    ];
    foreach ($nodes as $n) {
        execute(
            'INSERT INTO nodes (name,code,region,country,flag,provider,api_url,api_key,status,capacity,used,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$n[0], $n[1], $n[2], $n[3], $n[4], 'mock', 'https://' . $n[1] . '.kuluoxi.io/api', '', $n[5], $n[6], $n[7], now()]
        );
    }

    $demo = fetch_one("SELECT * FROM users WHERE email = 'demo@kuluoxi.io'");
    $light = fetch_one("SELECT * FROM plans WHERE code = 'light'");
    $wave = fetch_one("SELECT * FROM plans WHERE code = 'wave'");

    execute(
        'INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        ['KX20261020001', $demo['id'], $light['id'], 1, 1, $light['price'], 'paid', 'wallet', 'tide-hk-01', 'Ubuntu 24.04 LTS', now_plus_days(-20), now_plus_days(-20)]
    );
    execute(
        'INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        ['KX20261028002', $demo['id'], $wave['id'], 3, 1, $wave['price'], 'paid', 'alipay', 'tide-tyo-02', 'Debian 12', now_plus_days(-8), now_plus_days(-8)]
    );
    execute(
        'INSERT INTO orders (order_no,user_id,plan_id,node_id,months,amount,status,payment_method,instance_name,os,created_at,paid_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        ['KX20261105003', $demo['id'], $light['id'], 1, 3, $light['price'] * 3, 'pending', '', 'tide-hk-03', 'Ubuntu 22.04 LTS', now_plus_days(-1), null]
    );

    execute(
        'INSERT INTO instances (user_id,order_id,plan_id,node_id,provider_id,name,ip,root_password,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,traffic_used_gb,expires_at,created_at,updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$demo['id'], 1, $light['id'], 1, 'kuluoxi-1f7a2c', 'tide-hk-01', '10.12.8.31', 'Kx#2026Demo', 'Ubuntu 24.04 LTS',
            'running', $light['cpu'], $light['memory_mb'], $light['disk_gb'], $light['bandwidth_gb'], 318.4, now_plus_days(10), now_plus_days(-20), now()]
    );
    execute(
        'INSERT INTO instances (user_id,order_id,plan_id,node_id,provider_id,name,ip,root_password,os,status,cpu,memory_mb,disk_gb,bandwidth_gb,traffic_used_gb,expires_at,created_at,updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$demo['id'], 2, $wave['id'], 3, 'kuluoxi-9c31bb', 'tide-tyo-02', '10.41.6.88', 'Kx#2026Wave', 'Debian 12',
            'stopped', $wave['cpu'], $wave['memory_mb'], $wave['disk_gb'], $wave['bandwidth_gb'], 1206.7, now_plus_days(22), now_plus_days(-8), now()]
    );

    execute(
        'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
        [$demo['id'], 'recharge', 200000, 200000, '在线充值（模拟支付）', 'MOCK-1001', now_plus_days(-32)]
    );
    execute(
        'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
        [$demo['id'], 'payment', -($light['price'] + $wave['price']), 200000 - $light['price'] - $wave['price'], '购买「轻汐」「汐涌」实例', 'KX20261020001', now_plus_days(-20)]
    );
    execute(
        'INSERT INTO transactions (user_id,type,amount,balance_after,remark,ref,created_at) VALUES (?,?,?,?,?,?,?)',
        [$demo['id'], 'refund', 12000, 128000, '工单退款 · 网络抖动补偿', 'TK-000001', now_plus_days(-3)]
    );

    execute(
        'INSERT INTO tickets (user_id,subject,category,priority,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',
        [$demo['id'], '东京节点夜间延迟抖动', 'network', 'high', 'answered', now_plus_days(-4), now_plus_days(-3)]
    );
    execute(
        'INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
        [1, $demo['id'], '最近两晚 23:00 后东京节点延迟从 40ms 上升到 120ms，帮忙看下骨干线路。', 0, now_plus_days(-4)]
    );
    execute(
        'INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
        [1, 1, '已确认是国际出口拥塞，我们已切换备用链路并补偿余额 ¥120，请观察今日表现。', 1, now_plus_days(-3)]
    );
    execute(
        'INSERT INTO tickets (user_id,subject,category,priority,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',
        [$demo['id'], '申请增加快照功能', 'feature', 'normal', 'open', now_plus_days(-1), now_plus_days(-1)]
    );
    execute(
        'INSERT INTO ticket_messages (ticket_id,user_id,body,is_staff,created_at) VALUES (?,?,?,?,?)',
        [2, $demo['id'], '希望控制台支持手动创建快照与回滚，付费也可以。', 0, now_plus_days(-1)]
    );

    execute(
        'INSERT INTO announcements (title,body,level,active,created_at) VALUES (?,?,?,?,?)',
        ['法兰克福节点计划维护', '法兰克福 FRA-01 将于本周日 02:00–04:00 (UTC+8) 进行核心交换机升级，期间实例可能短暂断网，请提前保存工作。', 'warning', 1, now_plus_days(-2)]
    );
    execute(
        'INSERT INTO announcements (title,body,level,active,created_at) VALUES (?,?,?,?,?)',
        ['库洛汐控制台上线 2.0', '全新控制台支持一键重装、实时流量统计与余额自动续费，感谢一路同行的你。', 'info', 1, now_plus_days(-10)]
    );
}

function sweep_instances(): void
{
    try {
        $has = db()->query("SHOW TABLES LIKE 'instances'")->fetch();
        if (!$has) {
            return;
        }
        $pending = fetch_all(
            "SELECT id, node_id FROM instances WHERE status = 'provisioning' AND updated_at < DATE_SUB(NOW(), INTERVAL 6 SECOND)"
        );
        foreach ($pending as $row) {
            execute("UPDATE instances SET status = 'running', updated_at = ? WHERE id = ?", [now(), $row['id']]);
            execute('UPDATE nodes SET used = LEAST(capacity, used + 1) WHERE id = ?', [$row['node_id']]);
        }
        execute(
            "UPDATE instances SET status = 'suspended', updated_at = ? WHERE status IN ('running','stopped') AND expires_at < ?",
            [now(), now()]
        );
    } catch (Throwable $e) {
        // 安装前表不存在时静默跳过
    }
}
