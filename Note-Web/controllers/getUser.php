<?php
session_start();
header('Content-Type: application/json');

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
