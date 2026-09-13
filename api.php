<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('UTC');
ob_start();
header('Content-Type: application/json');
session_start();

define('DB_HOST', 'sql307.ezyro.com');
define('DB_NAME', 'ezyro_42734402_123');
define('DB_USER', 'ezyro_42734402');
define('DB_PASS', '7added00e6ef');

function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]);
        exit;
    }
}

function response($success, $message, $extra = []) {
    ob_end_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function generateCode() {
    return sprintf("%06d", rand(100000, 999999));
}

function sendVerificationEmail($email, $code) {
    $serviceID = 'service_pj60jer';
    $templateID = 'template_19nbgn9';
    $userID = 'JyM2FaCOfdCyNkunC';
    $data = [
        'service_id' => $serviceID,
        'template_id' => $templateID,
        'user_id' => $userID,
        'template_params' => [
            'to_email' => $email,
            'to_name' => '用户',
            'code' => $code,
            'subject' => '【龙黑化】注册验证码'
        ]
    ];
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }
    return false;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'sendCode') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        response(false, '请输入有效的邮箱地址');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            response(false, '该邮箱已被注册');
        }
    } catch (PDOException $e) {
        response(false, '数据库错误');
    }
    $code = generateCode();
    $_SESSION['verify_code'] = $code;
    $_SESSION['verify_email'] = $email;
    $_SESSION['verify_time'] = time();
    if (sendVerificationEmail($email, $code)) {
        response(true, '验证码已发送到 ' . $email . '，请查收', ['code' => $code]);
    } else {
        response(false, '邮件发送失败，请检查邮箱地址是否正确，或稍后重试');
    }
}

elseif ($action === 'register') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        response(false, '请输入有效的邮箱地址');
    }
    if (empty($username) || strlen($username) < 2 || strlen($username) > 20) {
        response(false, '用户名必须为2-20个字符');
    }
    if (strlen($password) < 6) {
        response(false, '密码至少6位');
    }
    if (empty($code) || strlen($code) !== 6) {
        response(false, '请输入6位验证码');
    }
    if (!isset($_SESSION['verify_code']) || !isset($_SESSION['verify_email'])) {
        response(false, '请先获取验证码');
    }
    if ($_SESSION['verify_email'] !== $email) {
        response(false, '验证码与邮箱不匹配');
    }
    if ($_SESSION['verify_code'] !== $code) {
        response(false, '验证码错误');
    }
    if (time() - $_SESSION['verify_time'] > 300) {
        response(false, '验证码已过期，请重新获取');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            response(false, '该邮箱已被注册');
        }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            response(false, '该用户名已被使用');
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, rcoins, created_at) VALUES (?, ?, ?, 100, NOW())");
        $stmt->execute([$email, $username, $hashed]);
        unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_time']);
        response(true, '注册成功！欢迎加入龙黑化社区！');
    } catch (PDOException $e) {
        response(false, '注册失败: ' . $e->getMessage());
    }
}

elseif ($action === 'login') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (empty($email) || empty($password)) {
        response(false, '请填写邮箱和密码');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, password, rcoins, gender, bio, bio_status, bio_error, avatar, cover FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            response(false, '邮箱未注册');
        }
        if (!password_verify($password, $user['password'])) {
            response(false, '密码错误');
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_token'] = $token;
        $_SESSION['email'] = $email;
        setcookie('user_id', $user['id'], time() + 86400 * 30, '/');
        setcookie('user_token', $token, time() + 86400 * 30, '/');
        response(true, '登录成功！欢迎回来 ' . $user['username'], [
            'token' => $token,
            'username' => $user['username'],
            'email' => $email,
            'rcoins' => $user['rcoins'],
            'gender' => $user['gender'] ?? '',
            'bio' => $user['bio'] ?? '',
            'bio_status' => $user['bio_status'] ?? 'pending',
            'bio_error' => $user['bio_error'] ?? '',
            'avatar' => $user['avatar'] ?? '',
            'cover' => $user['cover'] ?? '',
            'id' => $user['id']
        ]);
    } catch (PDOException $e) {
        response(false, '登录失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getUserInfo') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, email, rcoins, credit_score, gender, bio, bio_status, bio_error, avatar, cover FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) response(false, '用户不存在');
        $_SESSION['email'] = $user['email'];
        $creditScore = isset($user['credit_score']) ? (int)$user['credit_score'] : 100;
        response(true, '获取成功', [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'rcoins' => (int)$user['rcoins'],
            'credit_score' => $creditScore,
            'gender' => $user['gender'] ?? '',
            'bio' => $user['bio'] ?? '',
            'bio_status' => $user['bio_status'] ?? 'pending',
            'bio_error' => $user['bio_error'] ?? '',
            'avatar' => $user['avatar'] ?? '',
            'cover' => $user['cover'] ?? ''
        ]);
    } catch (PDOException $e) {
        response(false, '数据库查询失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getUserProfile') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $targetId = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$targetId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, email, rcoins, bio, avatar FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) response(false, '用户不存在');
        $stmt = $pdo->prepare("SELECT COUNT(*) as games FROM user_games WHERE user_id = ? AND status = 'approved'");
        $stmt->execute([$targetId]);
        $games = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT SUM(likes) as likes FROM user_games WHERE user_id = ?");
        $stmt->execute([$targetId]);
        $likes = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT SUM(collects) as collects FROM user_games WHERE user_id = ?");
        $stmt->execute([$targetId]);
        $collects = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT COUNT(*) as followers FROM user_follows WHERE following_id = ?");
        $stmt->execute([$targetId]);
        $followers = $stmt->fetch(PDO::FETCH_ASSOC);
        $is_following = false;
        if ($_SESSION['user_id'] != $targetId) {
            $stmt = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$_SESSION['user_id'], $targetId]);
            $is_following = $stmt->fetch() ? true : false;
        }
        $stmt = $pdo->prepare("SELECT id, user_id, username as author, title, file_path, icon, likes, collects, views, created_at FROM user_games WHERE user_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$targetId]);
        $userGames = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, '获取成功', [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'rcoins' => (int)$user['rcoins'],
                'bio' => $user['bio'] ?? '',
                'avatar' => $user['avatar'] ?? ''
            ],
            'stats' => [
                'games' => (int)$games['games'],
                'likes' => (int)$likes['likes'],
                'collects' => (int)$collects['collects'],
                'followers' => (int)$followers['followers']
            ],
            'is_following' => $is_following,
            'games' => $userGames
        ]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'updateProfile') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (strlen($username) < 2 || strlen($username) > 20) {
        response(false, '用户名必须为2-20个字符');
    }
    // 信用分检查：低于70不能修改介绍
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT credit_score FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $cs = isset($u['credit_score']) ? (int)$u['credit_score'] : 100;
        if ($cs < 70 && !empty($bio)) response(false, '你的信用分低于70，无法修改个人介绍');
    } catch (Exception $e) {}
    $avatar = '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (!in_array($ext, $allowed)) response(false, '只允许上传图片');
        if ($file['size'] > 10 * 1024 * 1024) response(false, '图片不能超过10MB');
        $uploadDir = __DIR__ . '/uploads/avatars/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $newName = time() . '_' . uniqid() . '.' . $ext;
        $path = $uploadDir . $newName;
        if (move_uploaded_file($file['tmp_name'], $path)) {
            $avatar = 'uploads/avatars/' . $newName;
        }
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT bio, bio_status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($bio) && $bio !== ($current['bio'] ?? '')) {
            $bioStatus = 'pending';
            $bioError = NULL;
        } else {
            $bioStatus = $current['bio_status'] ?? 'pending';
            $bioError = NULL;
        }
        if (!empty($avatar)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ?, bio_status = ?, bio_error = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$username, $bio, $bioStatus, $bioError, $avatar, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ?, bio_status = ?, bio_error = ? WHERE id = ?");
            $stmt->execute([$username, $bio, $bioStatus, $bioError, $_SESSION['user_id']]);
        }
        response(true, '个人资料已更新', ['bio_status' => $bioStatus]);
    } catch (PDOException $e) {
        response(false, '更新失败: ' . $e->getMessage());
    }
}

elseif ($action === 'updateCover') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
        response(false, '请选择一张图片');
    }
    $file = $_FILES['cover'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    if (!in_array($ext, $allowed)) response(false, '只允许上传图片格式');
    if ($file['size'] > 10 * 1024 * 1024) response(false, '图片不能超过10MB');
    $uploadDir = __DIR__ . '/uploads/covers/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    $newName = time() . '_cover_' . uniqid() . '.' . $ext;
    $path = $uploadDir . $newName;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        response(false, '图片保存失败，请检查目录权限');
    }
    $coverPath = 'uploads/covers/' . $newName;
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET cover = ? WHERE id = ?");
        $stmt->execute([$coverPath, $_SESSION['user_id']]);
        response(true, '封面更新成功', ['cover' => $coverPath]);
    } catch (PDOException $e) {
        response(false, '更新失败: ' . $e->getMessage());
    }
}

elseif ($action === 'checkBioStatus') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT email, bio, bio_status, bio_error FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) response(false, '用户不存在');
        if ($user['bio_status'] !== 'pending') {
            response(true, '获取成功', ['bio' => $user['bio'] ?? '', 'bio_status' => $user['bio_status'] ?? 'pending', 'bio_error' => $user['bio_error'] ?? '']);
            return;
        }
        $badWords = ['屠杀', '血腥', '碎尸', '斩首', '赌博', '赌场', '博彩'];
        $hasBadWord = false;
        $foundWord = '';
        $bioLower = strtolower($user['bio'] ?? '');
        foreach ($badWords as $word) {
            if (strpos($bioLower, strtolower($word)) !== false) {
                $hasBadWord = true;
                $foundWord = $word;
                break;
            }
        }
        if (empty(trim($user['bio'] ?? ''))) {
            $status = 'failed';
            $error = '介绍为空';
            $mailTitle = '介绍审核失败';
            $mailContent = "你的介绍审核失败\n\n原因：介绍为空，请填写后重新提交";
        } else if ($hasBadWord) {
            $status = 'failed';
            $error = '含有违规内容';
            $mailTitle = '介绍审核失败';
            $mailContent = "你的介绍审核失败\n\n原因：含有敏感内容\n请修改后重新提交";
        } else {
            $status = 'approved';
            $error = NULL;
            $mailTitle = '介绍审核成功';
            $mailContent = "恭喜你的介绍审核通过！\n\n你的介绍：" . $user['bio'];
        }
        $stmt = $pdo->prepare("UPDATE users SET bio_status = ?, bio_error = ? WHERE id = ?");
        $stmt->execute([$status, $error, $_SESSION['user_id']]);
        $stmt2 = $pdo->prepare("INSERT INTO user_mails (email, title, content) VALUES (?, ?, ?)");
        $stmt2->execute([$user['email'], $mailTitle, $mailContent]);
        response(true, '审核完成', ['bio' => $user['bio'] ?? '', 'bio_status' => $status, 'bio_error' => $error ?? '']);
    } catch (PDOException $e) {
        response(false, '审核失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getMails') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (empty($_SESSION['email'])) response(false, '无法获取用户邮箱, email=' . (isset($_SESSION['email']) ? $_SESSION['email'] : '空'));
    try {
        $pdo = getDB();
        // 尝试多个可能的表名
        $tableNames = ['user_mails', 'mails', 'user_mail', 'mail', 'user_notifications', 'notifications'];
        $mails = null;
        $lastError = '';
        foreach ($tableNames as $tname) {
            try {
                $stmt = $pdo->prepare("SELECT id, title, content, is_read, created_at FROM {$tname} WHERE email = ? ORDER BY created_at DESC");
                $stmt->execute([$_SESSION['email']]);
                $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // 如果成功，记录实际使用的表名
                $GLOBALS['actual_mail_table'] = $tname;
                break;
            } catch (PDOException $e2) {
                $lastError = $tname . ': ' . $e2->getMessage();
                continue;
            }
        }
        if ($mails !== null) {
            response(true, '获取成功', ['mails' => $mails, 'table_used' => $GLOBALS['actual_mail_table'] ?? 'unknown']);
        } else {
            response(false, '所有表名都失败，最后错误: ' . $lastError);
        }
    } catch (PDOException $e) {
        response(false, '邮件获取失败: ' . $e->getMessage() . ' | SQLSTATE: ' . $e->getCode());
    }
}

elseif ($action === 'readMail') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $mailId = isset($_POST['mail_id']) ? intval($_POST['mail_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$mailId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $tableNames = ['user_mails', 'mails', 'user_mail', 'mail', 'user_notifications', 'notifications'];
        $success = false;
        foreach ($tableNames as $tname) {
            try {
                $stmt = $pdo->prepare("UPDATE {$tname} SET is_read = 1 WHERE id = ? AND email = ?");
                $stmt->execute([$mailId, $_SESSION['email']]);
                $success = true;
                break;
            } catch (PDOException $e2) {
                continue;
            }
        }
        if ($success) {
            response(true, '已读');
        } else {
            response(false, '所有表名都失败');
        }
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

// 临时调试接口：查看数据库所有表名
elseif ($action === 'debugTables') {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        response(true, '表列表', ['tables' => $tables]);
    } catch (PDOException $e) {
        response(false, '获取表列表失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getRcoins') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT rcoins FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) response(false, '用户不存在');
        response(true, '获取成功', ['rcoins' => (int)$user['rcoins']]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getGames') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, user_id, username as author, title, file_path, icon, description, likes, collects, views, unique_views, created_at FROM user_games WHERE status = 'approved' ORDER BY created_at DESC LIMIT 50");
        $stmt->execute();
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($games as &$g) {
            $stmt = $pdo->prepare("SELECT id FROM game_likes WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_liked'] = $stmt->fetch() ? true : false;
            $stmt = $pdo->prepare("SELECT id FROM game_collects WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_collected'] = $stmt->fetch() ? true : false;
        }
        response(true, '获取成功', ['games' => $games]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getMyGames') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, user_id, username as author, title, file_path, icon, description, likes, collects, views, unique_views, status, error_msg, created_at FROM user_games WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['games' => $games]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'searchGames') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (empty($keyword)) response(false, '请输入搜索关键词');
    try {
        $pdo = getDB();
        $keyword = '%' . $keyword . '%';
        // 只搜索作品标题，不搜索作者名字
        $stmt = $pdo->prepare("SELECT id, user_id, username as author, title, file_path, icon, description, likes, collects, views, unique_views, created_at FROM user_games WHERE status = 'approved' AND title LIKE ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$keyword]);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($games as &$g) {
            $stmt = $pdo->prepare("SELECT id FROM game_likes WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_liked'] = $stmt->fetch() ? true : false;
            $stmt = $pdo->prepare("SELECT id FROM game_collects WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_collected'] = $stmt->fetch() ? true : false;
        }
        response(true, '获取成功', ['games' => $games]);
    } catch (PDOException $e) {
        response(false, '搜索失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getGameDetail') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE user_games SET views = views + 1 WHERE id = ?");
        $stmt->execute([$gameId]);
        $stmt = $pdo->prepare("SELECT id, user_id, username as author, title, file_path, icon, description, likes, collects, views, unique_views, created_at, status FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) response(false, '作品不存在');
        if ($game['status'] !== 'approved') {
            response(false, '作品正在审核中，请稍后再试');
        }
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$game['user_id']]);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);
        $game['author_avatar'] = $author['avatar'] ?? '';
        $game['author_id'] = $game['user_id'];
        $is_following = false;
        if ($_SESSION['user_id'] != $game['user_id']) {
            $stmt = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$_SESSION['user_id'], $game['user_id']]);
            $is_following = $stmt->fetch() ? true : false;
        }
        $game['is_following'] = $is_following;
        $stmt = $pdo->prepare("SELECT id FROM game_likes WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $game['user_liked'] = $stmt->fetch() ? true : false;
        $stmt = $pdo->prepare("SELECT id FROM game_collects WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $game['user_collected'] = $stmt->fetch() ? true : false;
        $stmt = $pdo->prepare("SELECT COUNT(*) as online FROM game_online WHERE game_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
        $stmt->execute([$gameId]);
        $online = $stmt->fetch(PDO::FETCH_ASSOC);
        $game['online_count'] = (int)$online['online'];
        $stmt = $pdo->prepare("SELECT id, user_id, username, content, created_at FROM game_comments WHERE game_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$gameId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($comments as &$c) {
            $c['time'] = strtotime($c['created_at']);
        }
        $game['comments'] = $comments;
        $game['tags'] = 'Scratch,休闲';
        response(true, '获取成功', ['game' => $game]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'like') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM game_likes WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        if ($stmt->fetch()) response(false, '已经点赞过了');
        $stmt = $pdo->prepare("INSERT INTO game_likes (game_id, user_id) VALUES (?, ?)");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $stmt = $pdo->prepare("UPDATE user_games SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$gameId]);
        $stmt = $pdo->prepare("SELECT likes FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $likes = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '点赞成功', ['likes' => $likes['likes']]);
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'unlike') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM game_likes WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $stmt = $pdo->prepare("UPDATE user_games SET likes = likes - 1 WHERE id = ? AND likes > 0");
        $stmt->execute([$gameId]);
        $stmt = $pdo->prepare("SELECT likes FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $likes = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '取消点赞成功', ['likes' => $likes['likes']]);
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'collect') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM game_collects WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        if ($stmt->fetch()) response(false, '已经收藏过了');
        $stmt = $pdo->prepare("INSERT INTO game_collects (game_id, user_id) VALUES (?, ?)");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $stmt = $pdo->prepare("UPDATE user_games SET collects = collects + 1 WHERE id = ?");
        $stmt->execute([$gameId]);
        $stmt = $pdo->prepare("SELECT collects FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $collects = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '收藏成功', ['collects' => $collects['collects']]);
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'uncollect') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM game_collects WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $stmt = $pdo->prepare("UPDATE user_games SET collects = collects - 1 WHERE id = ? AND collects > 0");
        $stmt->execute([$gameId]);
        $stmt = $pdo->prepare("SELECT collects FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $collects = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '取消收藏成功', ['collects' => $collects['collects']]);
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'comment') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    if (empty($content) || strlen($content) < 1) response(false, '评论内容不能为空');
    if (strlen($content) > 500) response(false, '评论不能超过500字');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) response(false, '用户不存在');
        $stmt = $pdo->prepare("INSERT INTO game_comments (game_id, user_id, username, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$gameId, $_SESSION['user_id'], $user['username'], $content]);
        response(true, '评论成功');
    } catch (PDOException $e) {
        response(false, '评论失败: ' . $e->getMessage());
    }
}

elseif ($action === 'deleteComment') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$commentId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT user_id FROM game_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$comment) response(false, '评论不存在');
        if ($comment['user_id'] != $_SESSION['user_id']) response(false, '无权删除此评论');
        $stmt = $pdo->prepare("DELETE FROM game_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        response(true, '删除成功');
    } catch (PDOException $e) {
        response(false, '删除失败: ' . $e->getMessage());
    }
}

elseif ($action === 'follow') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $targetId = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$targetId) response(false, '参数错误');
    if ($targetId == $_SESSION['user_id']) response(false, '不能关注自己');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$_SESSION['user_id'], $targetId]);
        if ($stmt->fetch()) response(false, '已经关注过了');
        $stmt = $pdo->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $targetId]);
        response(true, '关注成功');
    } catch (PDOException $e) {
        response(false, '关注失败: ' . $e->getMessage());
    }
}

elseif ($action === 'unfollow') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $targetId = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$targetId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$_SESSION['user_id'], $targetId]);
        response(true, '取消关注成功');
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getUserStats') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT COUNT(*) as games FROM user_games WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $games = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT SUM(likes) as likes FROM user_games WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $likes = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT SUM(collects) as collects FROM user_games WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $collects = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT COUNT(*) as following FROM user_follows WHERE follower_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $following = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT COUNT(*) as followers FROM user_follows WHERE following_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $followers = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '获取成功', [
            'games' => (int)$games['games'],
            'likes' => (int)$likes['likes'],
            'collects' => (int)$collects['collects'],
            'following' => (int)$following['following'],
            'followers' => (int)$followers['followers']
        ]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'playGame') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT status FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game || $game['status'] !== 'approved') {
            response(false, '作品正在审核中，请稍后再试');
        }
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT IGNORE INTO game_visits (game_id, user_id) VALUES (?, ?)");
        $stmt->execute([$gameId, $userId]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare("UPDATE user_games SET unique_views = unique_views + 1 WHERE id = ?")->execute([$gameId]);
        }
        $stmt = $pdo->prepare("INSERT INTO game_online (game_id, user_id, last_activity) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE last_activity = NOW()");
        $stmt->execute([$gameId, $userId]);
        $stmt = $pdo->prepare("SELECT COUNT(*) as online FROM game_online WHERE game_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
        $stmt->execute([$gameId]);
        $online = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '进入游戏成功', ['online_count' => (int)$online['online']]);
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'keepAlive') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE game_online SET last_activity = NOW() WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $stmt = $pdo->prepare("SELECT COUNT(*) as online FROM game_online WHERE game_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
        $stmt->execute([$gameId]);
        $online = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '心跳成功', ['online_count' => (int)$online['online']]);
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'leaveGame') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM game_online WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $stmt = $pdo->prepare("SELECT COUNT(*) as online FROM game_online WHERE game_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
        $stmt->execute([$gameId]);
        $online = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '退出成功', ['online_count' => (int)$online['online']]);
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getNonce') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '请先登录');
    }
    try {
        $pdo = getDB();
        $nonce = bin2hex(random_bytes(32));
        $pdo->prepare("DELETE FROM used_nonces WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->execute();
        response(true, '获取成功', ['nonce' => $nonce]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'transferRcoin') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
    $nonce = isset($_POST['nonce']) ? trim($_POST['nonce']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $fromUserId = $_SESSION['user_id'];
    if (!$gameId) response(false, '游戏ID无效');
    if ($amount <= 0) response(false, '金额必须大于0');
    if ($amount > 10000) response(false, '单次最多10000R币');
    if (empty($nonce)) response(false, '无效请求');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM used_nonces WHERE nonce = ?");
        $stmt->execute([$nonce]);
        if ($stmt->fetch()) response(false, '请求已过期，请重试');
        $stmt = $pdo->prepare("INSERT INTO used_nonces (nonce) VALUES (?)");
        $stmt->execute([$nonce]);
        $stmt = $pdo->prepare("SELECT user_id FROM user_games WHERE id = ? AND status = 'approved'");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) response(false, '游戏不存在');
        $toUserId = $game['user_id'];
        if ($toUserId == $fromUserId) response(false, '不能给自己打赏');
        $stmt = $pdo->prepare("SELECT rcoins FROM users WHERE id = ?");
        $stmt->execute([$fromUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['rcoins'] < $amount) response(false, 'R币余额不足');
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE users SET rcoins = rcoins - ? WHERE id = ? AND rcoins >= ?");
        $stmt->execute([$amount, $fromUserId, $amount]);
        if ($stmt->rowCount() == 0) throw new Exception('扣款失败');
        $stmt = $pdo->prepare("UPDATE users SET rcoins = rcoins + ? WHERE id = ?");
        $stmt->execute([$amount, $toUserId]);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO rcoin_transfers (from_user_id, to_user_id, game_id, amount, from_ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fromUserId, $toUserId, $gameId, $amount, $ip]);
        $pdo->commit();
        $stmt = $pdo->prepare("SELECT rcoins FROM users WHERE id = ?");
        $stmt->execute([$fromUserId]);
        $newBalance = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '打赏成功！', ['new_balance' => (int)$newBalance['rcoins']]);
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        response(false, '打赏失败：' . $e->getMessage());
    }
}

elseif ($action === 'getTransferHistory') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT t.id, t.amount, t.created_at, 
                   u.username as from_username, u2.username as to_username
            FROM rcoin_transfers t
            LEFT JOIN users u ON t.from_user_id = u.id
            LEFT JOIN users u2 ON t.to_user_id = u2.id
            WHERE t.game_id = ? AND t.status = 1
            ORDER BY t.created_at DESC LIMIT 10
        ");
        $stmt->execute([$gameId]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['records' => $records]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getRcoinRecords') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.amount as raw_amount,
                t.game_id,
                t.created_at,
                t.from_user_id,
                t.to_user_id,
                u.username as from_username,
                u2.username as to_username,
                g.title as game_name,
                g.icon as game_icon,
                g.file_path as game_file
            FROM rcoin_transfers t
            LEFT JOIN users u ON t.from_user_id = u.id
            LEFT JOIN users u2 ON t.to_user_id = u2.id
            LEFT JOIN user_games g ON t.game_id = g.id
            WHERE t.from_user_id = ? OR t.to_user_id = ?
            ORDER BY t.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$userId, $userId]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($records as $r) {
            if ($r['to_user_id'] == $userId && $r['from_user_id'] != $userId) {
                $amount = (int)$r['raw_amount'];
                $type = 'income';
                $title = ($r['game_id'] > 0) ? '收到打赏' : '充值入账';
            } else if ($r['from_user_id'] == $userId && $r['to_user_id'] != $userId) {
                $amount = -(int)$r['raw_amount'];
                $type = 'expense';
                $title = ($r['game_id'] > 0) ? '打赏支出' : '消费支出';
            } else {
                $amount = 0;
                $type = 'unknown';
                $title = 'R币变动';
            }
            if ($r['game_id'] == 0 || $r['game_id'] === null) {
                $gameName = '系统充值';
                $gameIcon = null;
            } else {
                $gameName = $r['game_name'] ?? '未知游戏';
                $gameIcon = $r['game_icon'] ?? null;
            }
            $result[] = [
                'id' => $r['id'],
                'amount' => $amount,
                'game_id' => $r['game_id'],
                'created_at' => $r['created_at'],
                'type' => $type,
                'title' => $title,
                'game_name' => $gameName,
                'game_icon' => $gameIcon,
                'from_username' => $r['from_username'],
                'to_username' => $r['to_username']
            ];
        }
        response(true, '获取成功', ['records' => $result]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'createGame') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT credit_score FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $cs = isset($u['credit_score']) ? (int)$u['credit_score'] : 100;
        if ($cs < 1) response(false, '你的账号已被封禁，请更换账号');
        if ($cs < 90) response(false, '你的信用分低于90，无法上传作品');
    } catch (Exception $e) {}
    if (strlen($title) < 2 || strlen($title) > 20) {
        response(false, '作品名称必须为2-20个字符');
    }
    if (empty($code) || strlen($code) < 10) {
        response(false, '代码太短');
    }
    if (!isset($_FILES['icon']) || $_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
        response(false, '请上传作品图标');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) response(false, '用户不存在');
        $iconFile = $_FILES['icon'];
        $ext = strtolower(pathinfo($iconFile['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (!in_array($ext, $allowed)) response(false, '只允许上传图片格式');
        if ($iconFile['size'] > 5 * 1024 * 1024) response(false, '图标不能超过5MB');
        $uploadDir = __DIR__ . '/uploads/icons/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $iconName = time() . '_icon_' . uniqid() . '.' . $ext;
        $iconPath = $uploadDir . $iconName;
        if (!move_uploaded_file($iconFile['tmp_name'], $iconPath)) {
            response(false, '图标保存失败');
        }
        $iconUrl = 'uploads/icons/' . $iconName;
        $stmt = $pdo->prepare("INSERT INTO user_games (user_id, username, title, icon, description, status, created_at) VALUES (?, ?, ?, ?, ?, 'approved', NOW())");
        $stmt->execute([$_SESSION['user_id'], $user['username'], $title, $iconUrl, $description]);
        $gameId = $pdo->lastInsertId();
        $htmlDir = __DIR__ . '/uploads/html/';
        if (!file_exists($htmlDir)) mkdir($htmlDir, 0777, true);
        $filename = 'play' . $gameId . '.html';
        $filePath = 'uploads/html/' . $filename;
        $fullPath = $htmlDir . $filename;
        if (strpos($code, '<!DOCTYPE') === false && strpos($code, '<html') === false) {
            $code = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($title) . '</title></head><body>' . $code . '</body></html>';
        }
        if (file_put_contents($fullPath, $code) === false) {
            response(false, '保存游戏文件失败');
        }
        $stmt = $pdo->prepare("UPDATE user_games SET file_path = ? WHERE id = ?");
        $stmt->execute([$filePath, $gameId]);
        response(true, '作品发布成功，已秒上线', ['game_id' => $gameId, 'file_path' => $filePath]);
    } catch (PDOException $e) {
        response(false, '发布失败: ' . $e->getMessage());
    }
}

elseif ($action === 'deleteGame') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT file_path, icon FROM user_games WHERE id = ? AND user_id = ?");
        $stmt->execute([$gameId, $_SESSION['user_id']]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) {
            response(false, '游戏不存在或无权限删除');
            return;
        }
        $fullPath = __DIR__ . '/' . $game['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);
        if ($game['icon'] && file_exists(__DIR__ . '/' . $game['icon'])) {
            unlink(__DIR__ . '/' . $game['icon']);
        }
        $editorDir = __DIR__ . '/uploads/2.0html/' . $gameId . '/';
        if (file_exists($editorDir)) {
            array_map('unlink', glob($editorDir . '*.*'));
            rmdir($editorDir);
        }
        $pdo->prepare("DELETE FROM game_likes WHERE game_id = ?")->execute([$gameId]);
        $pdo->prepare("DELETE FROM game_collects WHERE game_id = ?")->execute([$gameId]);
        $pdo->prepare("DELETE FROM game_comments WHERE game_id = ?")->execute([$gameId]);
        $pdo->prepare("DELETE FROM game_visits WHERE game_id = ?")->execute([$gameId]);
        $pdo->prepare("DELETE FROM game_online WHERE game_id = ?")->execute([$gameId]);
        $pdo->prepare("DELETE FROM user_games WHERE id = ? AND user_id = ?")->execute([$gameId, $_SESSION['user_id']]);
        response(true, '游戏删除成功');
    } catch (PDOException $e) {
        response(false, '删除失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getDonationTotal') {
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM rcoin_transfers WHERE game_id = ? AND status = 1");
        $stmt->execute([$gameId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['total' => (int)$result['total']]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getUserBadges') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $targetUserId = $userId ? $userId : $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT b.id, b.badge_key, b.name, b.icon, b.color, b.description, ub.earned_at 
            FROM badges b
            JOIN user_badges ub ON b.id = ub.badge_id
            WHERE ub.user_id = ?
            ORDER BY ub.earned_at DESC
        ");
        $stmt->execute([$targetUserId]);
        $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['badges' => $badges]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'checkBadges') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    $newBadges = [];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT username, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) response(false, '用户不存在');
        $stmt = $pdo->prepare("SELECT badge_id FROM user_badges WHERE user_id = ?");
        $stmt->execute([$userId]);
        $hasBadges = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as game_count,
                SUM(likes) as total_likes,
                SUM(collects) as total_collects,
                SUM(views) as total_views
            FROM user_games WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $gameCount = (int)$stats['game_count'];
        $totalLikes = (int)$stats['total_likes'];
        $totalCollects = (int)$stats['total_collects'];
        $totalViews = (int)$stats['total_views'];
        $totalInteract = $totalLikes + $totalCollects;
        if ($gameCount >= 20 && $totalInteract >= 90 && $totalViews >= 10000) {
            $stmt = $pdo->prepare("SELECT id FROM badges WHERE badge_key = 'blue_v'");
            $stmt->execute();
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($badge && !in_array($badge['id'], $hasBadges)) {
                $stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
                $stmt->execute([$userId, $badge['id']]);
                $newBadges[] = 'blue_v';
                $hasBadges[] = $badge['id'];
            }
        }
        $regYear = date('Y', strtotime($user['created_at']));
        if ($regYear == '2026' || $regYear == '2027') {
            $stmt = $pdo->prepare("SELECT id FROM badges WHERE badge_key = 'colorful_v'");
            $stmt->execute();
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($badge && !in_array($badge['id'], $hasBadges)) {
                $stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
                $stmt->execute([$userId, $badge['id']]);
                $newBadges[] = 'colorful_v';
            }
        }
        response(true, '检查完成', ['new_badges' => $newBadges]);
    } catch (PDOException $e) {
        response(false, '检查失败: ' . $e->getMessage());
    }
}

elseif ($action === 'logout') {
    session_destroy();
    setcookie('user_id', '', time() - 3600, '/');
    setcookie('user_token', '', time() - 3600, '/');
    response(true, '已退出登录');
}

elseif ($action === 'getHotGames') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, user_id, username as author, title, file_path, icon, description, likes, collects, views, unique_views, created_at FROM user_games WHERE status = 'approved' ORDER BY (likes + views) DESC LIMIT 15");
        $stmt->execute();
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($games as &$g) {
            $stmt = $pdo->prepare("SELECT id FROM game_likes WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_liked'] = $stmt->fetch() ? true : false;
            $stmt = $pdo->prepare("SELECT id FROM game_collects WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_collected'] = $stmt->fetch() ? true : false;
        }
        response(true, '获取成功', ['games' => $games]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getNewGames') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, user_id, username as author, title, file_path, icon, description, likes, collects, views, unique_views, created_at FROM user_games WHERE status = 'approved' AND created_at > DATE_SUB(NOW(), INTERVAL 35 MINUTE) ORDER BY created_at DESC");
        $stmt->execute();
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($games as &$g) {
            $stmt = $pdo->prepare("SELECT id FROM game_likes WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_liked'] = $stmt->fetch() ? true : false;
            $stmt = $pdo->prepare("SELECT id FROM game_collects WHERE game_id = ? AND user_id = ?");
            $stmt->execute([$g['id'], $_SESSION['user_id']]);
            $g['user_collected'] = $stmt->fetch() ? true : false;
        }
        response(true, '获取成功', ['games' => $games]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getNewGamesCount') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_games WHERE status = 'approved' AND created_at > DATE_SUB(NOW(), INTERVAL 35 MINUTE)");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['count' => (int)$result['count']]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'checkGameStatus') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT status, created_at, user_id, title, file_path FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) response(false, '游戏不存在');
        if ($game['status'] !== 'pending') {
            response(true, '获取成功', [
                'status' => $game['status'],
                'is_approved' => $game['status'] === 'approved'
            ]);
            return;
        }
        // 创建/发布后直接审核通过，秒上线（不再等待30秒）
        $pdo->prepare("UPDATE user_games SET status = 'approved' WHERE id = ?")->execute([$gameId]);
        $stmt = $pdo->prepare("SELECT status, error_msg FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '审核完成', [
            'status' => $result['status'],
            'is_approved' => $result['status'] === 'approved',
            'error_msg' => $result['error_msg'] ?? ''
        ]);
    } catch (PDOException $e) {
        response(false, '检查失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getFriends') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.avatar, u.rcoins, u.bio, u.email,
                   uf.created_at as friend_since
            FROM user_friends uf
            JOIN users u ON (uf.friend_id = u.id)
            WHERE uf.user_id = ? AND uf.status = 'accepted'
            UNION
            SELECT u.id, u.username, u.avatar, u.rcoins, u.bio, u.email,
                   uf.created_at as friend_since
            FROM user_friends uf
            JOIN users u ON (uf.user_id = u.id)
            WHERE uf.friend_id = ? AND uf.status = 'accepted'
        ");
        $stmt->execute([$userId, $userId]);
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['friends' => $friends]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'searchUsers') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
    $searchType = isset($_POST['search_type']) ? trim($_POST['search_type']) : 'all'; // all=全部, name=按名字, id=按ID
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (empty($keyword) || strlen($keyword) < 1) {
        response(false, '请输入搜索关键词');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $searchTerm = $keyword;
        
        if ($searchType === 'id') {
            // 按ID精确搜索：只显示完全等于这个ID的结果
            $stmt = $pdo->prepare("
                SELECT id, username, avatar, rcoins, bio 
                FROM users 
                WHERE id != ? AND id = ?
                LIMIT 15
            ");
            $stmt->execute([$userId, intval($keyword)]);
        } elseif ($searchType === 'name') {
            // 按名字模糊搜索：包括这个名字的都显示
            $keyword = '%' . $keyword . '%';
            $stmt = $pdo->prepare("
                SELECT id, username, avatar, rcoins, bio 
                FROM users 
                WHERE id != ? AND username LIKE ?
                ORDER BY 
                    CASE 
                        WHEN username = ? THEN 1
                        WHEN username LIKE ? THEN 2
                        ELSE 3
                    END,
                    username ASC
                LIMIT 15
            ");
            $stmt->execute([$userId, $keyword, $searchTerm, $keyword]);
        } else {
            // 默认：同时搜索用户名和ID（模糊匹配）
            $keyword = '%' . $keyword . '%';
            $stmt = $pdo->prepare("
                SELECT id, username, avatar, rcoins, bio 
                FROM users 
                WHERE id != ? AND (username LIKE ? OR id LIKE ?)
                ORDER BY 
                    CASE 
                        WHEN username = ? THEN 1
                        WHEN username LIKE ? THEN 2
                        ELSE 3
                    END,
                    username ASC
                LIMIT 15
            ");
            $stmt->execute([$userId, $keyword, $keyword, $searchTerm, $keyword]);
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$u) {
            $stmt2 = $pdo->prepare("SELECT id FROM user_friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'accepted'");
            $stmt2->execute([$userId, $u['id'], $u['id'], $userId]);
            $u['is_friend'] = $stmt2->fetch() ? true : false;
            $stmt2 = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $stmt2->execute([$userId, $u['id']]);
            $u['is_following'] = $stmt2->fetch() ? true : false;
            $stmt2 = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $stmt2->execute([$u['id'], $userId]);
            $u['follows_me'] = $stmt2->fetch() ? true : false;
            $stmt2 = $pdo->prepare("SELECT id FROM friend_invites WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending'");
            $stmt2->execute([$userId, $u['id']]);
            $u['invite_sent'] = $stmt2->fetch() ? true : false;
            $stmt2 = $pdo->prepare("SELECT id FROM friend_invites WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending'");
            $stmt2->execute([$u['id'], $userId]);
            $u['invite_received'] = $stmt2->fetch() ? true : false;
        }
        response(true, '获取成功', ['users' => $users]);
    } catch (PDOException $e) {
        response(false, '搜索失败: ' . $e->getMessage());
    }
}

elseif ($action === 'sendFriendRequest' || $action === 'sendFriendRequestDirect') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $targetId = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    if ($targetId == $userId) response(false, '不能添加自己为好友');
    if (!$targetId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT id FROM user_friends 
            WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
            AND status = 'accepted'
        ");
        $stmt->execute([$userId, $targetId, $targetId, $userId]);
        if ($stmt->fetch()) response(false, '你们已经是好友了');
        $stmt = $pdo->prepare("
            SELECT id FROM friend_invites 
            WHERE ((from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?))
            AND status = 'pending'
        ");
        $stmt->execute([$userId, $targetId, $targetId, $userId]);
        if ($stmt->fetch()) response(false, '已存在待处理的好友请求');
        $stmt = $pdo->prepare("
            SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?
        ");
        $stmt->execute([$userId, $targetId]);
        $follow1 = $stmt->fetch();
        $stmt->execute([$targetId, $userId]);
        $follow2 = $stmt->fetch();
        if (!$follow1 || !$follow2) {
            response(false, '双方需要互相关注才能发起好友邀请');
        }
        $stmt = $pdo->prepare("INSERT INTO friend_invites (from_user_id, to_user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$userId, $targetId]);
        $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($target) {
            $stmt2 = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt2->execute([$userId]);
            $from = $stmt2->fetch(PDO::FETCH_ASSOC);
            $mailTitle = '好友请求';
            $mailContent = "用户 " . ($from['username'] ?? '未知') . " 想添加你为好友！\n\n请前往好友页面确认。";
            $stmt3 = $pdo->prepare("INSERT INTO user_mails (email, title, content) VALUES (?, ?, ?)");
            $stmt3->execute([$target['email'], $mailTitle, $mailContent]);
        }
        response(true, '好友请求已发送！');
    } catch (PDOException $e) {
        response(false, '发送失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getPendingFriendRequests') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT fi.id, fi.from_user_id, fi.to_user_id, fi.status, fi.created_at,
                   u.username as from_username, u.avatar as from_avatar
            FROM friend_invites fi
            JOIN users u ON fi.from_user_id = u.id
            WHERE fi.to_user_id = ? AND fi.status = 'pending'
            ORDER BY fi.created_at DESC
        ");
        $stmt->execute([$userId]);
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['invites' => $invites]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'acceptFriendRequest') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $inviteId = isset($_POST['invite_id']) ? intval($_POST['invite_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    if (!$inviteId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT from_user_id, to_user_id, status FROM friend_invites WHERE id = ?");
        $stmt->execute([$inviteId]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invite) response(false, '邀请不存在');
        if ($invite['to_user_id'] != $userId) response(false, '无权操作');
        if ($invite['status'] != 'pending') response(false, '该邀请已处理');
        $stmt = $pdo->prepare("UPDATE friend_invites SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$inviteId]);
        $stmt = $pdo->prepare("INSERT INTO user_friends (user_id, friend_id, status) VALUES (?, ?, 'accepted'), (?, ?, 'accepted')");
        $stmt->execute([$invite['from_user_id'], $invite['to_user_id'], $invite['to_user_id'], $invite['from_user_id']]);
        $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt->execute([$invite['from_user_id']]);
        $from = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($from) {
            $mailTitle = '好友请求已通过';
            $mailContent = "用户 " . ($from['username'] ?? '') . " 已接受你的好友请求！\n\n现在你们可以开始聊天了！";
            $stmt2 = $pdo->prepare("INSERT INTO user_mails (email, title, content) VALUES (?, ?, ?)");
            $stmt2->execute([$from['email'], $mailTitle, $mailContent]);
        }
        $pdo->commit();
        response(true, '已添加好友！');
    } catch (PDOException $e) {
        if (isset($pdo)) $pdo->rollBack();
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'rejectFriendRequest') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $inviteId = isset($_POST['invite_id']) ? intval($_POST['invite_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    if (!$inviteId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT to_user_id, from_user_id FROM friend_invites WHERE id = ? AND status = 'pending'");
        $stmt->execute([$inviteId]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invite) response(false, '邀请不存在');
        if ($invite['to_user_id'] != $userId) response(false, '无权操作');
        $stmt = $pdo->prepare("UPDATE friend_invites SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$inviteId]);
        response(true, '已拒绝');
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getFriendMessages') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $friendId = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
    $beforeId = isset($_POST['before_id']) ? intval($_POST['before_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    if (!$friendId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT id FROM user_friends 
            WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
            AND status = 'accepted'
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        if (!$stmt->fetch()) response(false, '不是好友关系');
        $stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$friendId, $userId]);
        $sql = "SELECT pm.id, pm.sender_id, pm.receiver_id, pm.content, pm.is_read, pm.is_recalled, pm.created_at, u.username, u.avatar
                FROM private_messages pm
                LEFT JOIN users u ON pm.sender_id = u.id
                WHERE ((pm.sender_id = ? AND pm.receiver_id = ?) OR (pm.sender_id = ? AND pm.receiver_id = ?))
                AND (pm.is_blocked IS NULL OR pm.is_blocked = 0)";
        $params = [$userId, $friendId, $friendId, $userId];
        if ($beforeId > 0) {
            $sql .= " AND pm.id < ?";
            $params[] = $beforeId;
        }
        $sql .= " ORDER BY pm.id DESC LIMIT " . intval($limit);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $messages = array_reverse($messages);
        response(true, '获取成功', ['messages' => $messages]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'sendFriendMessage') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $friendId = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    if (!$friendId) response(false, '参数错误');
    if (empty($content) || strlen($content) < 1) response(false, '消息不能为空');
    if (mb_strlen($content) > 500) response(false, '消息不能超过500字');
    // 信用分检查：低于70禁言
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT credit_score FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $cs = isset($u['credit_score']) ? (int)$u['credit_score'] : 100;
        if ($cs < 1) response(false, '你的账号已被封禁，请更换账号');
        if ($cs < 70) response(false, '你的信用分低于70，已被禁言，无法发送消息');
    } catch (Exception $e) {}
    $badWords = ['赌博','赌钱','下注','博彩','彩票','六合彩','老虎机','赌球','网赌','棋牌','炸金花','斗牛','麻将馆','抽烟','吸烟','卷烟','雪茄','电子烟','烟瘾','卖淫','嫖娼','色情','黄片','毒品','海洛因','冰毒','大麻','枪支','军火','恐怖','爆炸','诈骗','骗钱','刷单','传销'];
    $found = '';
    foreach ($badWords as $w) {
        if (mb_strpos($content, $w) !== false) {
            $found = $w;
            break;
        }
    }
    if ($found !== '') {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, content, is_read, is_blocked, block_reason) VALUES (?, ?, ?, 0, 1, ?)");
            $stmt->execute([$userId, $friendId, $content, $found]);
            // 违规消息扣信用分（1-5分随机）
            $deduct = rand(1, 5);
            $stmt = $pdo->prepare("UPDATE users SET credit_score = GREATEST(0, credit_score - ?) WHERE id = ?");
            $stmt->execute([$deduct, $userId]);
            // 发邮件通知
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $uinfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($uinfo) {
                $stmt = $pdo->prepare("INSERT INTO user_mails (email, title, content) VALUES (?, ?, ?)");
                $stmt->execute([$uinfo['email'], '违规消息警告', '亲爱的冒险家，你发送的消息包含违规内容（' . $found . '），已被拦截，并扣除信用分' . $deduct . '分。请遵守社区规范，文明交流。']);
            }
        } catch (Exception $e) {}
        response(false, '消息包含违规内容（' . $found . '），已被拦截，并扣除信用分');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT id FROM user_friends 
            WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
            AND status = 'accepted'
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        if (!$stmt->fetch()) response(false, '不是好友关系');
        $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, content, is_read) VALUES (?, ?, ?, 0)");
        $stmt->execute([$userId, $friendId, $content]);
        $msgId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT id, sender_id, receiver_id, content, is_read, created_at FROM private_messages WHERE id = ?");
        $stmt->execute([$msgId]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '发送成功', ['message' => $msg]);
    } catch (PDOException $e) {
        response(false, '发送失败: ' . $e->getMessage());
    }
}

elseif ($action === 'recallMessage') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $msgId = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    if (!$msgId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM private_messages WHERE id = ? AND sender_id = ?");
        $stmt->execute([$msgId, $userId]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$msg) response(false, '消息不存在或无权操作');
        if (!empty($msg['is_recalled'])) response(false, '消息已撤回');
        // 检查是否超过15分钟
        $sendTime = strtotime($msg['created_at']);
        if (time() - $sendTime > 900) response(false, '消息发送超过15分钟，无法撤回');
        // 执行撤回
        $stmt = $pdo->prepare("UPDATE private_messages SET is_recalled = 1, recalled_at = NOW() WHERE id = ?");
        $stmt->execute([$msgId]);
        response(true, '消息已撤回');
    } catch (PDOException $e) {
        response(false, '撤回失败: ' . $e->getMessage());
    }
}

elseif ($action === 'submitReport') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $reportedUserId = isset($_POST['reported_user_id']) ? intval($_POST['reported_user_id']) : 0;
    $reportType = isset($_POST['report_type']) ? trim($_POST['report_type']) : 'message';
    $targetId = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $reportContent = isset($_POST['content']) ? trim($_POST['content']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $reporterId = $_SESSION['user_id'];
    if (!$reportedUserId) response(false, '参数错误');
    if (empty($title)) response(false, '请填写举报标题');
    if (empty($reportContent)) response(false, '请填写举报内容');
    if (mb_strlen($title) > 50) response(false, '举报标题不能超过50字');
    if (mb_strlen($reportContent) > 500) response(false, '举报内容不能超过500字');
    try {
        $pdo = getDB();
        // 插入举报记录
        $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, reported_user_id, report_type, target_id, title, content, contact) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$reporterId, $reportedUserId, $reportType, $targetId, $title, $reportContent, $contact]);
        // 检查被举报用户被举报次数（pending+processed状态都算）
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM reports WHERE reported_user_id = ? AND status IN ('pending', 'processed')");
        $stmt->execute([$reportedUserId]);
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC);
        $reportCount = (int)$cnt['cnt'];
        // 被举报2次及以上，自动处理：扣信用分+发邮件
        if ($reportCount >= 2) {
            $deduct = rand(2, 5);
            $stmt = $pdo->prepare("UPDATE users SET credit_score = GREATEST(0, credit_score - ?) WHERE id = ?");
            $stmt->execute([$deduct, $reportedUserId]);
            // 标记所有该用户的举报为已处理
            $stmt = $pdo->prepare("UPDATE reports SET status = 'processed' WHERE reported_user_id = ? AND status = 'pending'");
            $stmt->execute([$reportedUserId]);
            // 给被举报者发邮件
            $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
            $stmt->execute([$reportedUserId]);
            $ru = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ru) {
                $stmt = $pdo->prepare("INSERT INTO user_mails (email, title, content) VALUES (?, ?, ?)");
                $stmt->execute([$ru['email'], '违规警告', '亲爱的冒险家，由于你因被太多次举报或者违规游戏内容，请你正常游玩，并将扣除信用分-' . $deduct . '。请遵守社区规范，文明交流。']);
            }
            // 给举报人发邮件
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$reporterId]);
            $re = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($re) {
                $stmt = $pdo->prepare("INSERT INTO user_mails (email, title, content) VALUES (?, ?, ?)");
                $stmt->execute([$re['email'], '举报处理结果', '你当前的举报已经处理，已对被举报用户进行处罚。感谢你的友心，共同维护社区环境。']);
            }
        }
        response(true, '举报已提交，我们会尽快处理');
    } catch (PDOException $e) {
        response(false, '举报提交失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getCreditStatus') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT credit_score FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $cs = isset($u['credit_score']) ? (int)$u['credit_score'] : 100;
        $status = 'normal';
        $msg = '';
        if ($cs < 1) { $status = 'banned'; $msg = '你的账号已经被封禁，请你更换账号进行游玩哦'; }
        elseif ($cs < 40) { $status = 'restricted'; $msg = '你当前信用分低于40，部分功能受限'; }
        elseif ($cs < 70) { $status = 'muted'; $msg = '你当前信用分低于70，已被禁言'; }
        elseif ($cs < 90) { $status = 'limited'; $msg = '你当前信用分低于90，无法上传作品'; }
        response(true, '获取成功', ['credit_score' => $cs, 'status' => $status, 'msg' => $msg]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getUnreadMessageCount') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread 
            FROM private_messages 
            WHERE receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['unread' => (int)$result['unread']]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getEditorFiles') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId) response(false, '参数错误');
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT user_id FROM user_games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game || $game['user_id'] != $_SESSION['user_id']) {
        response(false, '无权操作');
    }
    $dir = __DIR__ . '/uploads/2.0html/' . $gameId . '/';
    $files = [];
    if (file_exists($dir)) {
        $scan = scandir($dir);
        foreach ($scan as $f) {
            if ($f !== '.' && $f !== '..') {
                $files[] = $f;
            }
        }
        if (count($files) === 1 && $files[0] === 'index.html') {
            $indexContent = @file_get_contents($dir . 'index.html');
            if ($indexContent !== false && strpos($indexContent, '编辑器 2.0') !== false && strpos($indexContent, '龙<span>黑</span>化') !== false) {
                $files = [];
            }
        }
    }
    response(true, '获取成功', ['files' => $files]);
}

elseif ($action === 'getEditorFile') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId || empty($filename)) response(false, '参数错误');
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT user_id FROM user_games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game || $game['user_id'] != $_SESSION['user_id']) {
        response(false, '无权操作');
    }
    $filename = basename($filename);
    if (strpos($filename, '..') !== false) response(false, '非法文件名');
    $filePath = __DIR__ . '/uploads/2.0html/' . $gameId . '/' . $filename;
    if (!file_exists($filePath)) {
        response(false, '文件不存在');
    }
    $content = file_get_contents($filePath);
    response(true, '获取成功', ['content' => $content]);
}

elseif ($action === 'saveEditorFile') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId || empty($filename)) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT user_id FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game || $game['user_id'] != $_SESSION['user_id']) {
            response(false, '无权操作');
        }
        $filename = basename($filename);
        if (strpos($filename, '..') !== false) response(false, '非法文件名');
        $dir = __DIR__ . '/uploads/2.0html/' . $gameId . '/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $filePath = $dir . $filename;
        if (file_put_contents($filePath, $content) === false) {
            response(false, '保存失败');
        }
        response(true, '保存成功');
    } catch (Exception $e) {
        response(false, '保存失败: ' . $e->getMessage());
    }
}

elseif ($action === 'deleteEditorFile') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId || empty($filename)) response(false, '参数错误');
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT user_id FROM user_games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game || $game['user_id'] != $_SESSION['user_id']) {
        response(false, '无权操作');
    }
    $filename = basename($filename);
    if (strpos($filename, '..') !== false) response(false, '非法文件名');
    if ($filename === 'index.html') {
        response(false, '不能删除主文件');
    }
    $filePath = __DIR__ . '/uploads/2.0html/' . $gameId . '/' . $filename;
    if (!file_exists($filePath)) response(false, '文件不存在');
    unlink($filePath);
    response(true, '删除成功');
}

elseif ($action === 'publishEditorGame') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $code = isset($_POST['code']) ? $_POST['code'] : '';
    $iconData = isset($_POST['icon_data']) ? $_POST['icon_data'] : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT credit_score FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $cs = isset($u['credit_score']) ? (int)$u['credit_score'] : 100;
        if ($cs < 1) response(false, '你的账号已被封禁，请更换账号');
        if ($cs < 90) response(false, '你的信用分低于90，无法上传作品');
    } catch (Exception $e) {}
    if (!$gameId || empty($title) || empty($code)) response(false, '参数错误');
    if (strlen($title) < 2 || strlen($title) > 20) response(false, '名称必须为2-20个字符');
    if (empty($iconData)) response(false, '请上传图标');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT user_id, username FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game || $game['user_id'] != $_SESSION['user_id']) {
            response(false, '无权操作');
        }
        if (strpos($iconData, 'data:image/') !== 0) response(false, '图标格式错误');
        $iconParts = explode(';base64,', $iconData, 2);
        if (count($iconParts) !== 2) response(false, '图标格式错误');
        $ext = strtolower(substr($iconParts[0], 11));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (!in_array($ext, $allowed)) response(false, '不支持的图片格式');
        $iconBinary = base64_decode($iconParts[1], true);
        if ($iconBinary === false) response(false, '图标解码失败');
        $uploadDir = __DIR__ . '/uploads/icons/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $iconName = time() . '_' . uniqid() . '.' . $ext;
        if (file_put_contents($uploadDir . $iconName, $iconBinary) === false) {
            response(false, '图标保存失败');
        }
        $iconUrl = 'uploads/icons/' . $iconName;
        $htmlDir = __DIR__ . '/uploads/html/';
        if (!file_exists($htmlDir)) mkdir($htmlDir, 0777, true);
        $filename = 'play' . $gameId . '.html';
        if (strpos($code, '<!DOCTYPE') === false && strpos($code, '<html') === false) {
            $code = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($title) . '</title></head><body>' . $code . '</body></html>';
        }
        if (file_put_contents($htmlDir . $filename, $code) === false) {
            response(false, '游戏文件保存失败');
        }
        $filePath = 'uploads/html/' . $filename;
        $stmt = $pdo->prepare("UPDATE user_games SET title = ?, icon = ?, description = ?, file_path = ?, status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $iconUrl, $description, $filePath, $gameId]);
        response(true, '发布成功，已秒上线', ['game_id' => $gameId]);
    } catch (Exception $e) {
        response(false, '发布失败: ' . $e->getMessage());
    }
}

elseif ($action === 'updateEditorGame') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $code = isset($_POST['code']) ? $_POST['code'] : '';
    $iconData = isset($_POST['icon_data']) ? $_POST['icon_data'] : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$gameId || empty($title) || empty($code)) response(false, '参数错误');
    if (strlen($title) < 2 || strlen($title) > 20) response(false, '名称必须为2-20个字符');
    if (empty($iconData)) response(false, '请上传图标');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT user_id, username FROM user_games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game || $game['user_id'] != $_SESSION['user_id']) {
            response(false, '无权操作');
        }
        if (strpos($iconData, 'data:image/') !== 0) response(false, '图标格式错误');
        $iconParts = explode(';base64,', $iconData, 2);
        if (count($iconParts) !== 2) response(false, '图标格式错误');
        $ext = strtolower(substr($iconParts[0], 11));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (!in_array($ext, $allowed)) response(false, '不支持的图片格式');
        $iconBinary = base64_decode($iconParts[1], true);
        if ($iconBinary === false) response(false, '图标解码失败');
        $uploadDir = __DIR__ . '/uploads/icons/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $iconName = time() . '_' . uniqid() . '.' . $ext;
        if (file_put_contents($uploadDir . $iconName, $iconBinary) === false) {
            response(false, '图标保存失败');
        }
        $iconUrl = 'uploads/icons/' . $iconName;
        $htmlDir = __DIR__ . '/uploads/html/';
        if (!file_exists($htmlDir)) mkdir($htmlDir, 0777, true);
        $filename = 'play' . $gameId . '.html';
        if (strpos($code, '<!DOCTYPE') === false && strpos($code, '<html') === false) {
            $code = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($title) . '</title></head><body>' . $code . '</body></html>';
        }
        if (file_put_contents($htmlDir . $filename, $code) === false) {
            response(false, '游戏文件保存失败');
        }
        $filePath = 'uploads/html/' . $filename;
        $stmt = $pdo->prepare("UPDATE user_games SET title = ?, icon = ?, description = ?, file_path = ?, status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $iconUrl, $description, $filePath, $gameId]);
        response(true, '更新成功，已秒上线', ['game_id' => $gameId]);
    } catch (Exception $e) {
        response(false, '更新失败: ' . $e->getMessage());
    }
}

elseif ($action === 'redeemCode') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '请先登录');
    }
    if (empty($code)) response(false, '请输入兑换码');
    $userId = $_SESSION['user_id'];
    $code = strtoupper(trim($code));
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, code_name, code_key, reward_rcoins, max_use_per_user, single_account_only, total_uses, max_total_uses, expire_time, is_active FROM exchange_codes WHERE code_key = ? AND is_active = 1");
        $stmt->execute([$code]);
        $codeData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$codeData) {
            response(false, '兑换码不存在或已失效');
        }
        if ($codeData['expire_time'] && strtotime($codeData['expire_time']) < time()) {
            response(false, '兑换码已过期');
        }
        if ($codeData['max_total_uses'] > 0 && $codeData['total_uses'] >= $codeData['max_total_uses']) {
            response(false, '兑换码已用完');
        }
        if ($codeData['single_account_only'] == 1) {
            $stmt = $pdo->prepare("SELECT user_id FROM exchange_usage WHERE code_id = ? LIMIT 1");
            $stmt->execute([$codeData['id']]);
            $used = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($used && $used['user_id'] != $userId) {
                response(false, '此兑换码已被其他账号使用');
            }
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) as used_count FROM exchange_usage WHERE code_id = ? AND user_id = ?");
        $stmt->execute([$codeData['id'], $userId]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usage['used_count'] >= $codeData['max_use_per_user']) {
            response(false, '您已达到此兑换码的最大使用次数');
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO exchange_usage (code_id, user_id) VALUES (?, ?)");
        $stmt->execute([$codeData['id'], $userId]);
        $stmt = $pdo->prepare("UPDATE exchange_codes SET total_uses = total_uses + 1 WHERE id = ?");
        $stmt->execute([$codeData['id']]);
        $stmt = $pdo->prepare("UPDATE users SET rcoins = rcoins + ? WHERE id = ?");
        $stmt->execute([$codeData['reward_rcoins'], $userId]);
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $title = '兑换码: ' . $codeData['code_name'];
            $content = "您成功兑换了【" . $codeData['code_name'] . "】兑换码，获得 " . $codeData['reward_rcoins'] . " R币！";
            $stmt = $pdo->prepare("INSERT INTO user_mails (email, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$user['email'], $title, $content]);
        }
        $pdo->commit();
        $stmt = $pdo->prepare("SELECT rcoins FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $newBalance = $stmt->fetch(PDO::FETCH_ASSOC);
        response(true, '兑换成功！获得 ' . $codeData['reward_rcoins'] . ' R币', ['new_balance' => $newBalance['rcoins']]);
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        response(false, '兑换失败：' . $e->getMessage());
    }
}

elseif ($action === 'adminCreateCode') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $codeName = isset($_POST['code_name']) ? trim($_POST['code_name']) : '';
    $codeKey = isset($_POST['code_key']) ? trim($_POST['code_key']) : '';
    $rewardRcoins = isset($_POST['reward_rcoins']) ? intval($_POST['reward_rcoins']) : 0;
    $maxUsePerUser = isset($_POST['max_use_per_user']) ? intval($_POST['max_use_per_user']) : 1;
    $maxTotalUses = isset($_POST['max_total_uses']) ? intval($_POST['max_total_uses']) : 0;
    $expireTime = isset($_POST['expire_time']) ? trim($_POST['expire_time']) : '';
    $singleAccountOnly = isset($_POST['single_account_only']) ? intval($_POST['single_account_only']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (empty($codeName)) response(false, '请输入兑换码名字');
    if (empty($codeKey) || strlen($codeKey) < 4) response(false, '请输入有效的兑换码');
    if ($rewardRcoins <= 0) response(false, 'R币数量必须大于0');
    if ($maxUsePerUser <= 0) response(false, '每人兑换次数必须大于0');
    if ($maxTotalUses < 0) response(false, '总使用次数不能为负数');
    try {
        $pdo = getDB();
        $codeKey = strtoupper(trim($codeKey));
        $stmt = $pdo->prepare("SELECT id FROM exchange_codes WHERE code_key = ?");
        $stmt->execute([$codeKey]);
        if ($stmt->fetch()) {
            response(false, '兑换码已存在，请重新生成');
        }
        $expireTime = !empty($expireTime) ? $expireTime : null;
        $stmt = $pdo->prepare("INSERT INTO exchange_codes (code_name, code_key, reward_rcoins, max_use_per_user, max_total_uses, expire_time, single_account_only, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$codeName, $codeKey, $rewardRcoins, $maxUsePerUser, $maxTotalUses, $expireTime, $singleAccountOnly]);
        response(true, '兑换码创建成功！', ['code_id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        response(false, '创建失败：' . $e->getMessage());
    }
}

elseif ($action === 'adminGetCodes') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, code_name, code_key, reward_rcoins, max_use_per_user, max_total_uses, total_uses, expire_time, single_account_only, is_active, created_at FROM exchange_codes ORDER BY id DESC");
        $stmt->execute();
        $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        response(true, '获取成功', ['codes' => $codes]);
    } catch (PDOException $e) {
        response(false, '获取失败：' . $e->getMessage());
    }
}

elseif ($action === 'adminToggleCode') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $codeId = isset($_POST['code_id']) ? intval($_POST['code_id']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$codeId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE exchange_codes SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $codeId]);
        response(true, $status == 1 ? '已启用' : '已停用');
    } catch (PDOException $e) {
        response(false, '操作失败：' . $e->getMessage());
    }
}

elseif ($action === 'adminDeleteCode') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $codeId = isset($_POST['code_id']) ? intval($_POST['code_id']) : 0;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!$codeId) response(false, '参数错误');
    try {
        $pdo = getDB();
        $pdo->prepare("DELETE FROM exchange_usage WHERE code_id = ?")->execute([$codeId]);
        $pdo->prepare("DELETE FROM exchange_codes WHERE id = ?")->execute([$codeId]);
        response(true, '删除成功');
    } catch (PDOException $e) {
        response(false, '删除失败：' . $e->getMessage());
    }
}

elseif ($action === 'checkOnboarding') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    session_write_close();
    try {
        $pdo = getDB();
        $done = 0;
        try {
            $stmt = $pdo->prepare("SELECT onboarding_done FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if ($row && isset($row['onboarding_done'])) {
                $done = intval($row['onboarding_done']);
            }
        } catch (PDOException $e2) {
            $done = 0;
        }
        response(true, '', ['done' => $done]);
    } catch (PDOException $e) {
        response(true, '', ['done' => 0]);
    }
}

elseif ($action === 'completeOnboarding') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    session_write_close();
    try {
        $pdo = getDB();
        try {
            $stmt = $pdo->prepare("UPDATE users SET onboarding_done = 1 WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e2) {
            // 字段不存在时忽略
        }
        response(true, '引导完成');
    } catch (PDOException $e) {
        response(false, '操作失败: ' . $e->getMessage());
    }
}

elseif ($action === 'getUserEmail') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    session_write_close();
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['email'])) {
            response(true, '', ['email' => $row['email']]);
        } else {
            response(false, '未找到邮箱');
        }
    } catch (PDOException $e) {
        response(false, '数据库错误: ' . $e->getMessage());
    }
}

elseif ($action === 'sendChangePasswordCode') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row || empty($row['email'])) {
            response(false, '未找到邮箱');
        }
        $email = $row['email'];
        $code = generateCode();
        $_SESSION['verify_code'] = $code;
        $_SESSION['verify_email'] = $email;
        $_SESSION['verify_time'] = time();
        if (sendVerificationEmail($email, $code)) {
            response(true, '验证码已发送到 ' . $email . '，请查收');
        } else {
            response(false, '邮件发送失败，请稍后重试');
        }
    } catch (PDOException $e) {
        response(false, '数据库错误: ' . $e->getMessage());
    }
}

elseif ($action === 'changePassword') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        response(false, '邮箱无效');
    }
    if (empty($code) || strlen($code) !== 6) {
        response(false, '请输入6位验证码');
    }
    if (empty($newPassword) || strlen($newPassword) < 6 || strlen($newPassword) > 20) {
        response(false, '新密码长度应为6-20位');
    }
    if (!isset($_SESSION['verify_code']) || !isset($_SESSION['verify_email'])) {
        response(false, '请先获取验证码');
    }
    if ($_SESSION['verify_email'] !== $email) {
        response(false, '验证码与邮箱不匹配');
    }
    if ($_SESSION['verify_code'] !== $code) {
        response(false, '验证码错误');
    }
    if (time() - $_SESSION['verify_time'] > 300) {
        response(false, '验证码已过期，请重新获取');
    }
    
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    try {
        $pdo = getDB();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND email = ?");
        $stmt->execute([$hashedPassword, $userId, $email]);
        // 清除验证码
        unset($_SESSION['verify_code']);
        unset($_SESSION['verify_email']);
        unset($_SESSION['verify_time']);
        response(true, '密码修改成功');
    } catch (PDOException $e) {
        response(false, '修改失败: ' . $e->getMessage());
    }
}

elseif ($action === 'githubLogin') {
    // GitHub OAuth 登录
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    if (empty($code)) {
        response(false, '缺少授权码');
    }
    
    $clientId = 'Ov23liASYrS51uIyeC9m';
    $clientSecret = 'afb61385c2c2b35638d2dcec91877d4776f74faa';
    
    // 第一步：用授权码换 access_token
    $tokenUrl = 'https://github.com/login/oauth/access_token';
    $tokenData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $code
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    $tokenResponse = curl_exec($ch);
    $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tokenCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($tokenCurlError) {
        response(false, '获取GitHub token网络错误: ' . $tokenCurlError);
    }
    
    if ($tokenHttpCode !== 200 || !$tokenResponse) {
        response(false, '获取GitHub token失败 [HTTP ' . $tokenHttpCode . ']: ' . substr($tokenResponse, 0, 200));
    }
    
    $tokenResult = json_decode($tokenResponse, true);
    if (!$tokenResult || empty($tokenResult['access_token'])) {
        response(false, 'GitHub token解析失败: ' . substr($tokenResponse, 0, 200));
    }
    
    $accessToken = $tokenResult['access_token'];
    
    // 第二步：用 access_token 获取用户信息
    $userUrl = 'https://api.github.com/user';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $accessToken,
        'User-Agent: LongHeiHua-App',
        'Accept: application/vnd.github.v3+json'
    ]);
    $userResponse = curl_exec($ch);
    $userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $userCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($userCurlError) {
        response(false, '获取GitHub用户信息网络错误: ' . $userCurlError);
    }
    
    if ($userHttpCode !== 200 || !$userResponse) {
        response(false, '获取GitHub用户信息失败 [HTTP ' . $userHttpCode . ']: ' . substr($userResponse, 0, 200));
    }
    
    $githubUser = json_decode($userResponse, true);
    if (!$githubUser) {
        response(false, 'GitHub用户信息解析失败: ' . substr($userResponse, 0, 200));
    }
    
    $githubId = $githubUser['id'] ?? '';
    $githubUsername = $githubUser['login'] ?? '';
    $githubName = $githubUser['name'] ?? ($githubUsername ?: 'GitHub用户');
    $githubAvatar = $githubUser['avatar_url'] ?? '';
    $githubEmail = $githubUser['email'] ?? '';
    
    if (empty($githubId)) {
        response(false, '未获取到GitHub用户ID');
    }
    
    // 检查是否是绑定模式（已登录用户绑定第三方账号）
    $bindMode = isset($_POST['bind_mode']) && $_POST['bind_mode'] === '1';
    $bindToken = isset($_POST['token']) ? trim($_POST['token']) : '';
    if ($bindMode) {
        if (empty($bindToken) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $bindToken) {
            response(false, '未登录，无法绑定');
        }
        $currentUserId = $_SESSION['user_id'];
        try {
            $pdo = getDB();
            // 检查该GitHub账号是否已经绑定到其他账号
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE github_id = ? AND id != ? LIMIT 1");
            $stmt->execute([$githubId, $currentUserId]);
            $otherUser = $stmt->fetch();
            if ($otherUser) {
                response(false, '你的GitHub账号（' . $githubUsername . '）已绑定到账号「' . $otherUser['username'] . '」，请先在该账号取消绑定后再试');
            }
            // 绑定到当前账号
            try {
                $pdo->prepare("UPDATE users SET github_id = ?, github_access_token = ?, github_username = ? WHERE id = ?")
                    ->execute([$githubId, $accessToken, $githubUsername, $currentUserId]);
            } catch (Exception $e) {
                // 如果github_id字段不存在，用taptap_openid字段兼容
                $pdo->prepare("UPDATE users SET taptap_openid = ?, taptap_access_token = ? WHERE id = ?")
                    ->execute(['github_' . $githubId, $accessToken, $currentUserId]);
            }
            response(true, 'GitHub账号绑定成功', ['platform' => 'github', 'username' => $githubUsername]);
        } catch (PDOException $e) {
            response(false, '绑定失败: ' . $e->getMessage());
        }
    }
    
    // 第三步：在数据库中查找或创建用户（正常登录模式）
    try {
        $pdo = getDB();
        
        // 查找是否已有绑定的账号（用github_id字段，如果没有则用taptap_openid字段兼容）
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE github_id = ? LIMIT 1");
        $stmt->execute([$githubId]);
        $existingUser = $stmt->fetch();
        
        // 如果github_id字段不存在，尝试用taptap_openid字段（兼容旧数据库）
        if (!$existingUser) {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE taptap_openid = ? LIMIT 1");
                $stmt->execute(['github_' . $githubId]);
                $existingUser = $stmt->fetch();
            } catch (Exception $e) {
                // 忽略字段不存在的错误
            }
        }
        
        if ($existingUser) {
            // 已有账号，直接登录
            $userId = $existingUser['id'];
            $username = $existingUser['username'];
            $email = $existingUser['email'];
            
            // 更新github_id和access_token
            try {
                $pdo->prepare("UPDATE users SET github_id = ?, github_access_token = ?, avatar = ? WHERE id = ?")
                    ->execute([$githubId, $accessToken, $githubAvatar, $userId]);
            } catch (Exception $e) {
                // 如果字段不存在，忽略
            }
        } else {
            // 新账号，创建用户
            $username = $githubName;
            // 检查用户名是否重复，重复则加随机后缀
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $username = $githubName . '_' . substr(md5(time()), 0, 4);
            }
            
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            $email = $githubEmail ?: ($githubUsername . '@github.local');
            
            // 尝试用github_id字段插入，如果字段不存在则用taptap_openid字段兼容
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, github_id, github_access_token, github_username, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashedPassword, $githubId, $accessToken, $githubUsername, $githubAvatar]);
            } catch (Exception $e) {
                // 如果github_id字段不存在，用taptap_openid字段兼容
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, taptap_openid, taptap_access_token, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashedPassword, 'github_' . $githubId, $accessToken, $githubAvatar]);
            }
            $userId = $pdo->lastInsertId();
        }
        
        // 生成系统 token
        $token = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['user_token'] = $token;
        $_SESSION['login_time'] = time();
        
        // 更新用户最后登录时间（加容错，字段不存在也不报错）
        try {
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);
        } catch (Exception $e) {
            // 忽略字段不存在的错误
        }
        
        response(true, '登录成功', [
            'token' => $token,
            'user_id' => $userId,
            'username' => $username,
            'email' => $email
        ]);
        
    } catch (PDOException $e) {
        response(false, '数据库错误: ' . $e->getMessage());
    }
}

elseif ($action === 'discordLogin') {
    // Discord OAuth 登录
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    if (empty($code)) {
        response(false, '缺少授权码');
    }
    
    $clientId = '1547608690750656552';
    $clientSecret = '1ZsyOWE25yia8G-fYcFwF75ojkezUcfP';
    $redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/callback_discord.html';
    
    // 第一步：用授权码换 access_token
    $tokenUrl = 'https://discord.com/api/oauth2/token';
    $tokenData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $tokenResponse = curl_exec($ch);
    $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tokenCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($tokenCurlError) {
        response(false, '获取Discord token网络错误: ' . $tokenCurlError);
    }
    
    if ($tokenHttpCode !== 200 || !$tokenResponse) {
        response(false, '获取Discord token失败 [HTTP ' . $tokenHttpCode . ']: ' . substr($tokenResponse, 0, 200));
    }
    
    $tokenResult = json_decode($tokenResponse, true);
    if (!$tokenResult || empty($tokenResult['access_token'])) {
        response(false, 'Discord token解析失败: ' . substr($tokenResponse, 0, 200));
    }
    
    $accessToken = $tokenResult['access_token'];
    
    // 第二步：用 access_token 获取用户信息
    $userUrl = 'https://discord.com/api/users/@me';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    $userResponse = curl_exec($ch);
    $userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $userCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($userCurlError) {
        response(false, '获取Discord用户信息网络错误: ' . $userCurlError);
    }
    
    if ($userHttpCode !== 200 || !$userResponse) {
        response(false, '获取Discord用户信息失败 [HTTP ' . $userHttpCode . ']: ' . substr($userResponse, 0, 200));
    }
    
    $discordUser = json_decode($userResponse, true);
    if (!$discordUser) {
        response(false, 'Discord用户信息解析失败: ' . substr($userResponse, 0, 200));
    }
    
    $discordId = $discordUser['id'] ?? '';
    $discordUsername = $discordUser['username'] ?? '';
    $discordDiscriminator = $discordUser['discriminator'] ?? '0';
    $discordAvatarHash = $discordUser['avatar'] ?? '';
    $discordEmail = $discordUser['email'] ?? '';
    
    if (empty($discordId)) {
        response(false, '未获取到Discord用户ID');
    }
    
    // 拼接头像URL
    if (!empty($discordAvatarHash)) {
        $discordAvatar = 'https://cdn.discordapp.com/avatars/' . $discordId . '/' . $discordAvatarHash . '.png';
    } else {
        // 默认头像
        $defaultAvatarIndex = intval($discordDiscriminator) % 5;
        $discordAvatar = 'https://cdn.discordapp.com/embed/avatars/' . $defaultAvatarIndex . '.png';
    }
    
    // 显示名称（用户名#区分符，新用户名系统可能没有区分符）
    if ($discordDiscriminator !== '0' && !empty($discordDiscriminator)) {
        $displayName = $discordUsername . '#' . $discordDiscriminator;
    } else {
        $displayName = $discordUsername;
    }
    
    // 第三步：在数据库中查找或创建用户
    try {
        $pdo = getDB();
        
        // 查找是否已有绑定的账号
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE discord_id = ? LIMIT 1");
        $stmt->execute([$discordId]);
        $existingUser = $stmt->fetch();
        
        // 如果discord_id字段不存在，尝试用taptap_openid字段兼容
        if (!$existingUser) {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE taptap_openid = ? LIMIT 1");
                $stmt->execute(['discord_' . $discordId]);
                $existingUser = $stmt->fetch();
            } catch (Exception $e) {
                // 忽略字段不存在的错误
            }
        }
        
        if ($existingUser) {
            // 已有账号，直接登录
            $userId = $existingUser['id'];
            $username = $existingUser['username'];
            $email = $existingUser['email'];
            
            // 更新discord_id和access_token
            try {
                $pdo->prepare("UPDATE users SET discord_id = ?, discord_access_token = ?, avatar = ? WHERE id = ?")
                    ->execute([$discordId, $accessToken, $discordAvatar, $userId]);
            } catch (Exception $e) {
                // 如果字段不存在，忽略
            }
        } else {
            // 新账号，创建用户
            $username = $displayName;
            // 检查用户名是否重复，重复则加随机后缀
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $username = $discordUsername . '_' . substr(md5(time()), 0, 4);
            }
            
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            $email = $discordEmail ?: ($discordUsername . '@discord.local');
            
            // 尝试用discord_id字段插入，如果字段不存在则用taptap_openid字段兼容
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, discord_id, discord_access_token, discord_username, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashedPassword, $discordId, $accessToken, $discordUsername, $discordAvatar]);
            } catch (Exception $e) {
                // 如果discord_id字段不存在，用taptap_openid字段兼容
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, taptap_openid, taptap_access_token, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashedPassword, 'discord_' . $discordId, $accessToken, $discordAvatar]);
            }
            $userId = $pdo->lastInsertId();
        }
        
        // 生成系统 token
        $token = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['user_token'] = $token;
        $_SESSION['login_time'] = time();
        
        // 更新用户最后登录时间（加容错）
        try {
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);
        } catch (Exception $e) {
            // 忽略字段不存在的错误
        }
        
        response(true, '登录成功', [
            'token' => $token,
            'user_id' => $userId,
            'username' => $username,
            'email' => $email
        ]);
        
    } catch (PDOException $e) {
        response(false, '数据库错误: ' . $e->getMessage());
    }
}

elseif ($action === 'giteeLogin') {
    // Gitee OAuth 登录
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    if (empty($code)) {
        response(false, '缺少授权码');
    }
    
    $clientId = 'b889443affb4a406a4535a032d2d3ae35c4846a0158892894b01dc9b68aee19e';
    $clientSecret = 'f3e8951f63936902c7d4007d93f5cf3b50ccdfd746799cfc12aadd018278b515';
    $redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/callback_gitee.html';
    
    // 第一步：用授权码换 access_token
    $tokenUrl = 'https://gitee.com/oauth/token';
    $tokenData = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $tokenResponse = curl_exec($ch);
    $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tokenCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($tokenCurlError) {
        response(false, '获取Gitee token网络错误: ' . $tokenCurlError);
    }
    
    if ($tokenHttpCode !== 200 || !$tokenResponse) {
        response(false, '获取Gitee token失败 [HTTP ' . $tokenHttpCode . ']: ' . substr($tokenResponse, 0, 200));
    }
    
    $tokenResult = json_decode($tokenResponse, true);
    if (!$tokenResult || empty($tokenResult['access_token'])) {
        response(false, 'Gitee token解析失败: ' . substr($tokenResponse, 0, 200));
    }
    
    $accessToken = $tokenResult['access_token'];
    
    // 第二步：用 access_token 获取用户信息
    $userUrl = 'https://gitee.com/api/v5/user?access_token=' . urlencode($accessToken);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: LongHeiHua-App'
    ]);
    $userResponse = curl_exec($ch);
    $userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $userCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($userCurlError) {
        response(false, '获取Gitee用户信息网络错误: ' . $userCurlError);
    }
    
    if ($userHttpCode !== 200 || !$userResponse) {
        response(false, '获取Gitee用户信息失败 [HTTP ' . $userHttpCode . ']: ' . substr($userResponse, 0, 200));
    }
    
    $giteeUser = json_decode($userResponse, true);
    if (!$giteeUser) {
        response(false, 'Gitee用户信息解析失败: ' . substr($userResponse, 0, 200));
    }
    
    $giteeId = $giteeUser['id'] ?? '';
    $giteeLogin = $giteeUser['login'] ?? '';
    $giteeName = $giteeUser['name'] ?? ($giteeLogin ?: 'Gitee用户');
    $giteeAvatar = $giteeUser['avatar_url'] ?? '';
    $giteeEmail = $giteeUser['email'] ?? '';
    
    if (empty($giteeId)) {
        response(false, '未获取到Gitee用户ID');
    }
    
    // 检查是否是绑定模式（已登录用户绑定第三方账号）
    $bindMode = isset($_POST['bind_mode']) && $_POST['bind_mode'] === '1';
    $bindToken = isset($_POST['token']) ? trim($_POST['token']) : '';
    if ($bindMode) {
        if (empty($bindToken) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $bindToken) {
            response(false, '未登录，无法绑定');
        }
        $currentUserId = $_SESSION['user_id'];
        try {
            $pdo = getDB();
            // 检查该Gitee账号是否已经绑定到其他账号
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE gitee_id = ? AND id != ? LIMIT 1");
            $stmt->execute([$giteeId, $currentUserId]);
            $otherUser = $stmt->fetch();
            if ($otherUser) {
                response(false, '你的Gitee账号（' . $giteeLogin . '）已绑定到账号「' . $otherUser['username'] . '」，请先在该账号取消绑定后再试');
            }
            // 绑定到当前账号
            try {
                $pdo->prepare("UPDATE users SET gitee_id = ?, gitee_access_token = ?, gitee_username = ? WHERE id = ?")
                    ->execute([$giteeId, $accessToken, $giteeLogin, $currentUserId]);
            } catch (Exception $e) {
                // 如果gitee_id字段不存在，用taptap_openid字段兼容
                $pdo->prepare("UPDATE users SET taptap_openid = ?, taptap_access_token = ? WHERE id = ?")
                    ->execute(['gitee_' . $giteeId, $accessToken, $currentUserId]);
            }
            response(true, 'Gitee账号绑定成功', ['platform' => 'gitee', 'username' => $giteeLogin]);
        } catch (PDOException $e) {
            response(false, '绑定失败: ' . $e->getMessage());
        }
    }
    
    // 第三步：在数据库中查找或创建用户
    try {
        $pdo = getDB();
        
        // 查找是否已有绑定的账号
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE gitee_id = ? LIMIT 1");
        $stmt->execute([$giteeId]);
        $existingUser = $stmt->fetch();
        
        // 如果gitee_id字段不存在，尝试用taptap_openid字段兼容
        if (!$existingUser) {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE taptap_openid = ? LIMIT 1");
                $stmt->execute(['gitee_' . $giteeId]);
                $existingUser = $stmt->fetch();
            } catch (Exception $e) {
                // 忽略字段不存在的错误
            }
        }
        
        if ($existingUser) {
            // 已有账号，直接登录
            $userId = $existingUser['id'];
            $username = $existingUser['username'];
            $email = $existingUser['email'];
            
            // 更新gitee_id和access_token
            try {
                $pdo->prepare("UPDATE users SET gitee_id = ?, gitee_access_token = ?, avatar = ? WHERE id = ?")
                    ->execute([$giteeId, $accessToken, $giteeAvatar, $userId]);
            } catch (Exception $e) {
                // 如果字段不存在，忽略
            }
        } else {
            // 新账号，创建用户
            $username = $giteeName;
            // 检查用户名是否重复，重复则加随机后缀
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $username = $giteeLogin . '_' . substr(md5(time()), 0, 4);
            }
            
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            $email = $giteeEmail ?: ($giteeLogin . '@gitee.local');
            
            // 尝试用gitee_id字段插入，如果字段不存在则用taptap_openid字段兼容
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, gitee_id, gitee_access_token, gitee_username, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashedPassword, $giteeId, $accessToken, $giteeLogin, $giteeAvatar]);
            } catch (Exception $e) {
                // 如果gitee_id字段不存在，用taptap_openid字段兼容
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, taptap_openid, taptap_access_token, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashedPassword, 'gitee_' . $giteeId, $accessToken, $giteeAvatar]);
            }
            $userId = $pdo->lastInsertId();
        }
        
        // 生成系统 token
        $token = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['user_token'] = $token;
        $_SESSION['login_time'] = time();
        
        // 更新用户最后登录时间（加容错）
        try {
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);
        } catch (Exception $e) {
            // 忽略字段不存在的错误
        }
        
        response(true, '登录成功', [
            'token' => $token,
            'user_id' => $userId,
            'username' => $username,
            'email' => $email
        ]);
        
    } catch (PDOException $e) {
        response(false, '数据库错误: ' . $e->getMessage());
    }
}

elseif ($action === 'getBindings') {
    // 获取当前账号的第三方登录绑定状态
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        
        // 初始化绑定状态
        $bindings = [
            'github' => ['bound' => false, 'id' => '', 'username' => ''],
            'gitee' => ['bound' => false, 'id' => '', 'username' => ''],
            'discord' => ['bound' => false, 'id' => '', 'username' => '']
        ];
        
        // 尝试查询所有绑定字段（加容错，字段不存在也不报错）
        $user = null;
        try {
            $stmt = $pdo->prepare("SELECT github_id, github_username, gitee_id, gitee_username, discord_id, discord_username FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // 字段不存在，忽略，继续用兼容方式查询
            $user = null;
        }
        
        if ($user) {
            $bindings['github']['bound'] = !empty($user['github_id']);
            $bindings['github']['id'] = $user['github_id'] ?? '';
            $bindings['github']['username'] = $user['github_username'] ?? '';
            $bindings['gitee']['bound'] = !empty($user['gitee_id']);
            $bindings['gitee']['id'] = $user['gitee_id'] ?? '';
            $bindings['gitee']['username'] = $user['gitee_username'] ?? '';
            $bindings['discord']['bound'] = !empty($user['discord_id']);
            $bindings['discord']['id'] = $user['discord_id'] ?? '';
            $bindings['discord']['username'] = $user['discord_username'] ?? '';
        }
        
        // 如果github_id/gitee_id字段不存在或为空，尝试用taptap_openid字段兼容
        if (!$bindings['github']['bound'] && !$bindings['gitee']['bound']) {
            try {
                $stmt2 = $pdo->prepare("SELECT taptap_openid FROM users WHERE id = ? LIMIT 1");
                $stmt2->execute([$userId]);
                $compat = $stmt2->fetch();
                if ($compat && !empty($compat['taptap_openid'])) {
                    if (strpos($compat['taptap_openid'], 'github_') === 0) {
                        $bindings['github']['bound'] = true;
                        $bindings['github']['id'] = substr($compat['taptap_openid'], 7);
                    } elseif (strpos($compat['taptap_openid'], 'gitee_') === 0) {
                        $bindings['gitee']['bound'] = true;
                        $bindings['gitee']['id'] = substr($compat['taptap_openid'], 6);
                    }
                }
            } catch (Exception $e) {
                // 忽略字段不存在的错误
            }
        }
        
        response(true, '获取成功', ['bindings' => $bindings]);
    } catch (PDOException $e) {
        response(false, '获取失败: ' . $e->getMessage());
    }
}

elseif ($action === 'unbindAccount') {
    // 取消第三方账号绑定
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $platform = isset($_POST['platform']) ? trim($_POST['platform']) : '';
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) {
        response(false, '未登录');
    }
    if (!in_array($platform, ['github', 'gitee', 'discord'])) {
        response(false, '不支持的平台');
    }
    $userId = $_SESSION['user_id'];
    try {
        $pdo = getDB();
        
        // 检查用户是否设置了密码，如果没有密码且只有这一个登录方式，不允许取消绑定
        $stmt = $pdo->prepare("SELECT password, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 统计已绑定的第三方账号数量
        $boundCount = 0;
        try {
            $stmt2 = $pdo->prepare("SELECT github_id, gitee_id, discord_id FROM users WHERE id = ? LIMIT 1");
            $stmt2->execute([$userId]);
            $bindInfo = $stmt2->fetch();
            if (!empty($bindInfo['github_id'])) $boundCount++;
            if (!empty($bindInfo['gitee_id'])) $boundCount++;
            if (!empty($bindInfo['discord_id'])) $boundCount++;
        } catch (Exception $e) {}
        
        // 如果没有密码且只有这一个绑定方式，不允许取消
        if (empty($user['password']) && $boundCount <= 1) {
            response(false, '您的账号没有设置密码，且这是唯一的登录方式，取消绑定后将无法登录。请先设置密码后再取消绑定。');
        }
        
        // 取消绑定
        $fieldMap = [
            'github' => ['github_id', 'github_access_token', 'github_username'],
            'gitee' => ['gitee_id', 'gitee_access_token', 'gitee_username'],
            'discord' => ['discord_id', 'discord_access_token', 'discord_username']
        ];
        $fields = $fieldMap[$platform];
        
        try {
            $sql = "UPDATE users SET " . implode(" = NULL, ", $fields) . " = NULL WHERE id = ?";
            $pdo->prepare($sql)->execute([$userId]);
        } catch (Exception $e) {
            // 如果字段不存在，尝试用taptap_openid字段兼容
            if ($platform === 'github' || $platform === 'gitee') {
                try {
                    $pdo->prepare("UPDATE users SET taptap_openid = NULL, taptap_access_token = NULL WHERE id = ?")->execute([$userId]);
                } catch (Exception $e2) {}
            }
        }
        
        response(true, '已取消' . strtoupper($platform) . '账号绑定');
    } catch (PDOException $e) {
        response(false, '取消绑定失败: ' . $e->getMessage());
    }
}

else {
    response(false, '未知操作');
}
?>
