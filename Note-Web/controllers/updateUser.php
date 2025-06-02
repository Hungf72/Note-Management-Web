<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user']) || empty($_SESSION['user']['email'])) {
        echo json_encode(["status" => "error", "message" => "Người dùng chưa đăng nhập"]);
        exit;
    }

    $email = $_SESSION['user']['email'];
    $firstName = trim($_POST['firstname'] ?? '');
    $lastName  = trim($_POST['lastname'] ?? '');
    $age       = $_POST['age'] ?? '';
    $phone     = trim($_POST['phone'] ?? '');

    try {
        $update = $pdo->prepare("UPDATE users SET firstName = :firstName, lastName = :lastName, age = :age, phone = :phone WHERE email = :email");
        $success = $update->execute([
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':age' => $age,
            ':phone' => $phone,
            ':email' => $email
        ]);

        if ($success) {
            // Update session user info after successful update
            $_SESSION['user']['firstName'] = $firstName;
            $_SESSION['user']['lastName'] = $lastName;
            $_SESSION['user']['age'] = $age;
            $_SESSION['user']['phone'] = $phone;
            echo json_encode(["status" => "success", "message" => "Cập nhật thành công"]);
        } 
        else {
            echo json_encode(["status" => "error", "message" => "Cập nhật thất bại"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
    }

    exit;
}
?>
