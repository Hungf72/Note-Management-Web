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

    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../images/avatars/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        
        // Use only user's sanitized firstname for filename
        $safeFirstName = preg_replace('/[^a-zA-Z0-9]/', '', $firstName);
        $fileName = 'avatar_' . $safeFirstName . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
            $avatarPath = 'images/avatars/' . $fileName;
        }
    }

    try {
        $sql = "UPDATE users SET firstName = :firstName, lastName = :lastName, age = :age, phone = :phone";
        $params = [
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':age' => $age,
            ':phone' => $phone,
            ':email' => $email
        ];
        if ($avatarPath) {
            $sql .= ", avatar = :avatar";
            $params[':avatar'] = $avatarPath;
        }
        $sql .= " WHERE email = :email";
        $update = $pdo->prepare($sql);
        $success = $update->execute($params);

        if ($success) {
            // Update session user info after successful update
            $_SESSION['user']['firstName'] = $firstName;
            $_SESSION['user']['lastName'] = $lastName;
            $_SESSION['user']['age'] = $age;
            $_SESSION['user']['phone'] = $phone;
            if ($avatarPath) {
                $_SESSION['user']['avatar'] = $avatarPath;
            }
            echo json_encode(["status" => "success", "message" => "Cập nhật thành công", "avatar" => $avatarPath]);
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
