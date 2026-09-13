<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

date_default_timezone_set('Asia/Shanghai');

define('DB_HOST', 'sql308.infinityfree.com');
define('DB_NAME', 'if0_42664773_gameport');
define('DB_USER', 'if0_42664773');
define('DB_PASS', 'weIfUGkedz2QX');

function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '数据库连接失败：' . $e->getMessage()]);
        exit;
    }
}

function response($success, $message, $extra = []) {
    $data = array_merge(['success' => $success, 'message' => $message], $extra);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

session_start();
$pdo = getDB();

function checkAuth($token) {
    global $pdo;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) return false;
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserData($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM user_data WHERE email = ?");
    $stmt->execute([$email]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        $stmt = $pdo->prepare("INSERT INTO user_data (email, points, storage_expanded) VALUES (?, 0, 0)");
        $stmt->execute([$email]);
        return ['email' => $email, 'points' => 0, 'storage_expanded' => 0];
    }
    return $data;
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$user = checkAuth($token);
if (!$user) response(false, '未登录或会话已过期');

$userData = getUserData($user['email']);
$action = isset($_POST['action']) ? $_POST['action'] : '';
$today = date('Y-m-d');

// ========== 获取积分 ==========
if ($action === 'getPoints') {
    $signedToday = false;
    $stmt = $pdo->prepare("SELECT sign_date FROM sign_log WHERE email = ? AND sign_date = ?");
    $stmt->execute([$user['email'], $today]);
    if ($stmt->fetch()) {
        $signedToday = true;
    }
    response(true, '获取成功', [
        'points' => (int)$userData['points'],
        'storage_expanded' => (int)$userData['storage_expanded'],
        'signed_today' => $signedToday
    ]);
}

// ========== 签到 ==========
elseif ($action === 'signIn') {
    $stmt = $pdo->prepare("SELECT id FROM sign_log WHERE email = ? AND sign_date = ?");
    $stmt->execute([$user['email'], $today]);
    if ($stmt->fetch()) {
        response(false, '今日已签到，明天再来吧！');
    }
    $pointsEarned = rand(1, 25);
    $stmt = $pdo->prepare("INSERT INTO sign_log (email, sign_date, points_earned) VALUES (?, ?, ?)");
    $stmt->execute([$user['email'], $today, $pointsEarned]);
    $newPoints = (int)$userData['points'] + $pointsEarned;
    $stmt = $pdo->prepare("UPDATE user_data SET points = ? WHERE email = ?");
    $stmt->execute([$newPoints, $user['email']]);
    response(true, '签到成功', ['points_earned' => $pointsEarned, 'total_points' => $newPoints]);
}

// ========== 购买扩容 ==========
elseif ($action === 'buyStorage') {
    if ((int)$userData['storage_expanded'] == 1) {
        response(false, '你已经购买过扩容了！');
    }
    if ((int)$userData['points'] < 125) {
        response(false, '积分不足，需要 125 积分');
    }
    $newPoints = (int)$userData['points'] - 125;
    $stmt = $pdo->prepare("UPDATE user_data SET points = ?, storage_expanded = 1 WHERE email = ?");
    $stmt->execute([$newPoints, $user['email']]);
    $stmt = $pdo->prepare("INSERT INTO orders (email, order_name, order_type, cost_points) VALUES (?, '添加内存订单', 'storage', ?)");
    $stmt->execute([$user['email'], 125]);
    response(true, '扩容成功！上传上限已提升至 375MB');
}

// ========== 购买引流 ==========
elseif ($action === 'buyTraffic') {
    if ((int)$userData['points'] < 2500) {
        response(false, '积分不足，需要 2500 积分');
    }
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE email = ? AND order_type = 'traffic'");
    $stmt->execute([$user['email']]);
    if ($stmt->fetch()) {
        response(false, '你已经购买过引流服务了！');
    }
    $newPoints = (int)$userData['points'] - 2500;
    $stmt = $pdo->prepare("UPDATE user_data SET points = ? WHERE email = ?");
    $stmt->execute([$newPoints, $user['email']]);
    $orderData = json_encode([
        'message' => 'You have successfully purchased the traffic driving service. Please enter "xurifu_2025@qq.com" in any email address. Then please send an email with the subject "I have paid for the lead generation service". The email content reads: "Hello, I have purchased the traffic attraction service. My WeChat ID is xx, and I agree to the precautions for using the traffic attraction service. 1. The service will only last for one hour and is non-refundable. 2. The number of participants may vary, and I should adapt accordingly."'
    ]);
    $stmt = $pdo->prepare("INSERT INTO orders (email, order_name, order_type, cost_points, order_data) VALUES (?, '引流订单', 'traffic', ?, ?)");
    $stmt->execute([$user['email'], 2500, $orderData]);
    response(true, '引流服务购买成功！');
}

// ========== 兑换码 ==========
elseif ($action === 'redeem') {
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    if (empty($code)) {
        response(false, '请输入兑换码');
    }
    $stmt = $pdo->prepare("SELECT code, points FROM redeem_codes WHERE code = ?");
    $stmt->execute([$code]);
    $codeData = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$codeData) {
        response(false, '兑换码不存在或已失效');
    }
    $stmt = $pdo->prepare("SELECT id FROM redeem_log WHERE email = ? AND code = ?");
    $stmt->execute([$user['email'], $code]);
    if ($stmt->fetch()) {
        response(false, '你已经兑换过这个兑换码了！');
    }
    $pointsEarned = (int)$codeData['points'];
    $newPoints = (int)$userData['points'] + $pointsEarned;
    $stmt = $pdo->prepare("UPDATE user_data SET points = ? WHERE email = ?");
    $stmt->execute([$newPoints, $user['email']]);
    $stmt = $pdo->prepare("INSERT INTO redeem_log (email, code, points_earned) VALUES (?, ?, ?)");
    $stmt->execute([$user['email'], $code, $pointsEarned]);
    response(true, '兑换成功', ['points_earned' => $pointsEarned]);
}

// ========== 获取订单列表 ==========
elseif ($action === 'getOrders') {
    $stmt = $pdo->prepare("SELECT order_name, order_type, cost_points, order_data, created_at FROM orders WHERE email = ? ORDER BY created_at DESC");
    $stmt->execute([$user['email']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    response(true, '获取成功', ['orders' => $orders]);
}

else {
    response(false, '未知操作');
}
?>