<?php
// ============================================================
// ★★★ 设置时区为中国时区 ★★★
// ============================================================
date_default_timezone_set('Asia/Shanghai');

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

define('DB_HOST', 'sql308.infinityfree.com');
define('DB_NAME', 'if0_42664773_gameport');
define('DB_USER', 'if0_42664773');
define('DB_PASS', 'weIfUGkedz2QX');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('BASE_MAX_SIZE', 250 * 1024 * 1024);
define('EXPANDED_MAX_SIZE', 375 * 1024 * 1024);

if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);

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
$action = isset($_POST['action']) ? $_POST['action'] : '';

function checkAuth($token) {
    global $pdo;
    if (empty($token) || !isset($_SESSION['user_token']) || $_SESSION['user_token'] !== $token) return false;
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserData($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT points, storage_expanded FROM user_data WHERE email = ?");
    $stmt->execute([$email]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        $stmt = $pdo->prepare("INSERT INTO user_data (email, points, storage_expanded) VALUES (?, 0, 0)");
        $stmt->execute([$email]);
        return ['points' => 0, 'storage_expanded' => 0];
    }
    return $data;
}

function getUserMaxSize($email) {
    $data = getUserData($email);
    if ($data && (int)$data['storage_expanded'] == 1) {
        return EXPANDED_MAX_SIZE;
    }
    return BASE_MAX_SIZE;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS files (
    id INT(11) NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    uploader VARCHAR(50) NOT NULL,
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ============================================================
// 获取积分和扩容状态
// ============================================================
if ($action === 'getPoints') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $user = checkAuth($token);
    if (!$user) response(false, '未登录或会话已过期');
    
    $data = getUserData($user['email']);
    response(true, '获取成功', [
        'points' => (int)$data['points'],
        'storage_expanded' => (int)$data['storage_expanded']
    ]);
}

// ============================================================
// 获取文件列表（★ 修复时区问题 ★）
// ============================================================
elseif ($action === 'list') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $user = checkAuth($token);
    if (!$user) response(false, '未登录或会话已过期');

    $stmt = $pdo->query("SELECT filename, filepath, uploader, upload_time FROM files ORDER BY upload_time DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $files = [];
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    foreach ($rows as $row) {
        // ★★★ 将 UTC 时间转为北京时间显示 ★★★
        $uploadTime = new DateTime($row['upload_time'], new DateTimeZone('UTC'));
        $uploadTime->setTimezone(new DateTimeZone('Asia/Shanghai'));
        $files[] = [
            'name' => $row['filename'],
            'url' => $baseUrl . '/' . $row['filepath'],
            'uploader' => $row['uploader'],
            'upload_time' => $uploadTime->format('Y-m-d H:i:s')
        ];
    }
    response(true, '获取成功', ['files' => $files]);
}

// ============================================================
// 上传文件
// ============================================================
elseif ($action === 'upload') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $user = checkAuth($token);
    if (!$user) response(false, '未登录或会话已过期');

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        response(false, '文件上传失败');
    }

    $file = $_FILES['file'];
    $filename = basename($file['name']);

    $maxSize = getUserMaxSize($user['username']);
    $maxSizeMB = $maxSize / 1024 / 1024;

    if ($file['size'] > $maxSize) {
        response(false, '文件超过 ' . $maxSizeMB . 'MB 限制');
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $danger = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'cgi', 'pl', 'asp', 'aspx', 'jsp', 'htaccess'];
    if (in_array($ext, $danger)) {
        response(false, '该文件类型不允许上传');
    }

    $targetPath = UPLOAD_DIR . $filename;
    $counter = 1;
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    while (file_exists($targetPath)) {
        $newName = $name . '_' . $counter . '.' . $ext;
        $targetPath = UPLOAD_DIR . $newName;
        $counter++;
        $filename = $newName;
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $stmt = $pdo->prepare("INSERT INTO files (filename, filepath, uploader) VALUES (?, ?, ?)");
        $filepath = 'uploads/' . $filename;
        $stmt->execute([$filename, $filepath, $user['username']]);
        response(true, '上传成功');
    } else {
        response(false, '文件保存失败');
    }
}

// ============================================================
// 删除文件
// ============================================================
elseif ($action === 'delete') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $user = checkAuth($token);
    if (!$user) response(false, '未登录或会话已过期');

    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    if (empty($filename)) response(false, '请指定文件');

    $stmt = $pdo->prepare("SELECT filename, uploader, filepath FROM files WHERE filename = ?");
    $stmt->execute([$filename]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file) response(false, '文件不存在');

    if ($file['uploader'] !== $user['username']) response(false, '你没有权限删除此文件');

    $filePath = UPLOAD_DIR . $file['filename'];
    if (file_exists($filePath)) unlink($filePath);

    $stmt = $pdo->prepare("DELETE FROM files WHERE filename = ?");
    $stmt->execute([$filename]);
    response(true, '删除成功');
}

else {
    response(false, '未知操作');
}
?>