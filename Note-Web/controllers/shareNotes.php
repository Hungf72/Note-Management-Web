<?php
session_start();
require_once __DIR__ . '/../models/connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientEmail = trim($_POST['email'] ?? '');
    $noteIds = isset($_POST['note_ids']) ? $_POST['note_ids'] : [];

    if (is_string($noteIds)) {
        $noteIds = explode(',', $noteIds);
    }
    $noteIds = array_filter(array_map('intval', $noteIds));

    if (empty($recipientEmail) || empty($noteIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Thiếu email hoặc danh sách ghi chú.']);
        exit;
    }

    // Lấy user_id của người nhận
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $recipientEmail]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        echo json_encode(['status' => 'error', 'message' => 'Recipient not found.']);
        exit;
    }

    $recipientId = $recipient['id'];

    // Lặp qua từng note, tạo bản sao cho người nhận với is_share=1
    $successCount = 0;
    foreach ($noteIds as $noteId) {
        // Lấy thông tin note gốc
        $stmt = $pdo->prepare('SELECT title, content, image_path FROM notes WHERE id = :id');
        $stmt->execute(['id' => $noteId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$note) continue;

        // Tạo note mới cho người nhận
        $stmt = $pdo->prepare('INSERT INTO notes (user_id, title, content, image_path, is_share) VALUES (:user_id, :title, :content, :image_path, 1)');
        $stmt->execute([
            'user_id' => $recipientId,
            'title' => $note['title'],
            'content' => $note['content'],
            'image_path' => $note['image_path']
        ]);
        $successCount++;
    }

    if ($successCount > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Đã chia sẻ ' . $successCount . ' ghi chú.']);
    } 
    else {
        echo json_encode(['status' => 'error', 'message' => 'Không có ghi chú nào được chia sẻ.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
exit;
?>
