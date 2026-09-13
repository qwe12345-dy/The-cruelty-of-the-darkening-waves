<?php
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Shanghai');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

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
        echo json_encode(['success' => false, 'message' => '数据库连接失败']);
        exit;
    }
}

function response($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function fixTime($t) {
    if (empty($t)) return '';
    return date('Y-m-d H:i:s', strtotime($t) + 28800);
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

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$token = isset($_POST['token']) ? $_POST['token'] : (isset($_GET['token']) ? $_GET['token'] : '');

if ($action === 'list') {
    $stmt = $pdo->query("SELECT p.*, 
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count
        FROM posts p ORDER BY p.created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($posts as &$p) {
        $p['created_at'] = fixTime($p['created_at']);
    }
    response(true, '获取成功', ['posts' => $posts]);
}

elseif ($action === 'get') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) response(false, '参数错误');
    $stmt = $pdo->prepare("SELECT p.*, 
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count
        FROM posts p WHERE p.id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) response(false, '帖子不存在');
    $post['created_at'] = fixTime($post['created_at']);
    if (!empty($post['updated_at'])) $post['updated_at'] = fixTime($post['updated_at']);
    $stmt2 = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? ORDER BY created_at ASC");
    $stmt2->execute([$id]);
    $comments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comments as &$c) $c['created_at'] = fixTime($c['created_at']);
    response(true, '获取成功', ['post' => $post, 'comments' => $comments]);
}

elseif ($action === 'create') {
    $user = checkAuth($token);
    if (!$user) response(false, '请先登录');
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if (empty($title) || empty($content)) response(false, '标题和内容不能为空');
    if (mb_strlen($title) > 20) response(false, '标题不能超过20个字符');
    if (mb_strlen($content) > 100) response(false, '内容不能超过100个字符');
    if (preg_match('/<[^>]*>/', $title) || preg_match('/<[^>]*>/', $content)) response(false, '不能包含HTML代码');
    if (stripos($title, '<script') !== false || stripos($content, '<script') !== false) response(false, '不能包含JavaScript代码');
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        if (!in_array($ext, $allowed)) response(false, '只允许上传图片');
        if ($file['size'] > 10 * 1024 * 1024) response(false, '图片不能超过10MB');
        $uploadDir = __DIR__ . '/uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $newName = time() . '_' . uniqid() . '.' . $ext;
        $path = $uploadDir . $newName;
        if (move_uploaded_file($file['tmp_name'], $path)) $image = 'uploads/' . $newName;
    }
    $stmt = $pdo->prepare("INSERT INTO posts (title, content, image, author, author_email) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $image, $user['username'], $user['email']]);
    response(true, '发布成功', ['id' => $pdo->lastInsertId()]);
}

elseif ($action === 'delete') {
    $user = checkAuth($token);
    if (!$user) response(false, '请先登录');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) response(false, '参数错误');
    $stmt = $pdo->prepare("SELECT author_email FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) response(false, '帖子不存在');
    if ($post['author_email'] !== $user['email']) response(false, '没有权限删除');
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    response(true, '删除成功');
}

elseif ($action === 'like') {
    $user = checkAuth($token);
    if (!$user) response(false, '请先登录');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) response(false, '参数错误');
    $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND email = ?");
    $stmt->execute([$id, $user['email']]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND email = ?");
        $stmt->execute([$id, $user['email']]);
        response(true, '已取消点赞', ['liked' => false]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, email) VALUES (?, ?)");
        $stmt->execute([$id, $user['email']]);
        response(true, '点赞成功', ['liked' => true]);
    }
}

elseif ($action === 'comment') {
    $user = checkAuth($token);
    if (!$user) response(false, '请先登录');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if (!$id || empty($content)) response(false, '参数错误');
    $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, email, username, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $user['email'], $user['username'], $content]);
    response(true, '评论成功');
}

else {
    response(false, '未知操作');
}
?>