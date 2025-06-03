<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/connect.php';
session_start();

$email = null;
$currentPassword = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $currentPassword = $input['currentPassword'] ?? '';
    $email = $input['email'] ?? ($_SESSION['email'] ?? null);
}

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập!']);
    exit;
}

$stmt = $pdo->prepare('SELECT password FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && password_verify($currentPassword, $row['password'])) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'fail', 'message' => 'Mật khẩu hiện tại không đúng!']);
}
exit;
