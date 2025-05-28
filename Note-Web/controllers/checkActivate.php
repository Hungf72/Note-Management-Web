<?php
require_once __DIR__ . '/../models/connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $email = trim($_GET['email'] ?? '');

    if (empty($email)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Thiếu email.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode([
            'status' => 'success',
            'is_active' => (bool)$user['is_active']
        ]);
    } 
    else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Không tìm thấy tài khoản.'
        ]);
    }
}
?>
