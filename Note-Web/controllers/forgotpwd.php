<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email không được để trống.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // tao ma sac nhan 
        $ecode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        echo json_encode(['status' => 'success', 'message' => 'Mã xác nhận của bạn là:', 'ecode' => $ecode]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy tài khoản với email này.']);
    }
}

?>