<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu không được để trống.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // update password
    $update = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
    $update->execute([
        'password' => $hashedPassword,
        'email' => $email
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Mật khẩu đã được cập nhật thành công.']);
    exit;
}

?>
