<?php
require_once __DIR__ . '/../models/connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['firstname'] ?? '');
    $lastName  = trim($_POST['lastname'] ?? '');
    $age       = $_POST['age'] ?? null;
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email đã tồn tại']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Mật khẩu và xác nhận mật khẩu không khớp!'
        ]);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users 
            (firstName, lastName, age, phone, email, password, is_active)
            VALUES 
            (:firstName, :lastName, :age, :phone, :email, :password, 0)");

        $stmt->execute([
            ':firstName' => $firstName,
            ':lastName'  => $lastName,
            ':age'       => $age,
            ':phone'     => $phone,
            ':email'     => $email,
            ':password'  => $hashed_password,
        ]);

        // Gửi email kích hoạt
        $activation_link = "activate.html?email=" . urlencode($email);

        echo json_encode([
            'status' => 'success',
            'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt tài khoản.',
            'activation_link' => $activation_link,
            'email' => $email,
        ]);
        exit;

    } catch (PDOException $e){
        echo json_encode([
            'status' => 'error',
            'message' => 'Lỗi khi đăng ký: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ.'
    ]);
}
?>
