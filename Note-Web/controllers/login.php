<?php
session_start();
// duong dan tuyet doi
require_once __DIR__ . '/../models/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try{
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;

            // neu bat remember me thi luu cookie trong 1 ngay
            if (!empty($_POST['rememberMe'])) {
                setcookie('user_email', $email, time() + 86400, "/");
            } 
            else{
                // xoa cookie
                if (isset($_COOKIE['user_email'])) {
                    setcookie('user_email', '', time() - 3600, "/");
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Đăng nhập thành công', 'email' => $email]);
        } 
        else{
            echo json_encode(['status' => 'error', 'message' => 'Email hoặc mật khẩu không đúng.']);
        }
    } 
    catch (PDOException $e){
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    exit;
} 
?> 



