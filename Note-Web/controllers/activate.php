<?php
require_once __DIR__ . '/../models/connect.php'; 
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $email = trim($_GET['email'] ?? '');

    // Tìm user chưa được kích hoạt
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND is_active = 0");
    $stmt->execute([
        ':email' => $email,
    ]);
    $user = $stmt->fetch();

    if ($user) {
        // Cập nhật trạng thái kích hoạt
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE email = :email");
        $stmt->execute([':email' => $email]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Activation successful! Your account is now active.',
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Reset code is invalid.',
        ]);
    }
}
?>
