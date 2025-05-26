<?php
require_once 'connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['firstname'] ?? '');
    $lastNname  = trim($_POST['lastname'] ?? '');
    $age        = $_POST['age'] ?? null;
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        echo "Mật khẩu và xác nhận mật khẩu không khớp!";
        exit;
    }

    // Băm mật khẩu
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO Users (firstName, lastNname, age, phone, email, password)
                               VALUES (:firstName, :lastNname, :age, :phone, :email, :password)");

        $stmt->execute([
            ':firstName' => $firstName,
            ':lastNname'  => $lastNname,
            ':age'        => $age,
            ':phone'      => $phone,
            ':email'      => $email,
            ':password'   => $hashed_password
        ]);

        // Nếu đăng ký thành công, hiển thị alert và chuyển hướng
        echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công!']);
        exit;

    } catch (PDOException $e){
        if ($e->errorInfo[1] == 1062) {
            echo json_encode(['status' => 'error', 'message' => 'Email đã tồn tại']);
        } else{
            echo "Lỗi khi đăng ký: " . addslashes($e->getMessage()) . " ";
        }
    }
} 
else{
    echo "Phương thức không hợp lệ.";
}
?>
