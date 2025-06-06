<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/connect.php';

// Nếu có email truyền vào, kiểm tra user theo email
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    $stmt = $pdo->prepare('SELECT email, firstName, lastName, age, phone, avatar FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode([
            'status' => 'success',
            'email' => $row['email'],
            'firstName' => $row['firstName'],
            'lastName' => $row['lastName'],
            'age' => $row['age'],
            'phone' => $row['phone'],
            'avatar' => $row['avatar'] ?? null
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
    }
    exit;
}

// Nếu không có email, trả về user trong session như cũ
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Chưa đăng nhập.'
    ]);
    exit;
}

$user = $_SESSION['user'];
echo json_encode([
    'status' => 'success',
    'email' => $user['email'],
    'firstName' => $user['firstName'],
    'lastName' => $user['lastName'],
    'age' => $user['age'],
    'phone' => $user['phone'],
    'avatar' => isset($user['avatar']) ? $user['avatar'] : null
]);
?>
