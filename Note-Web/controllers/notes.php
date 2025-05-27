<?php
    session_start();
    require_once __DIR__ . '/../models/connect.php';

    header('Content-Type: application/json');
    
    function getNOTES(){
        global $pdo;
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để xem todo.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'notes' => $notes]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    function createNOTES() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tạo note.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO notes (title, content, user_id) VALUES (:title, :content, :user_id)");
                $stmt->execute(['title' => $title, 'content' => $content, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Note đã được tạo thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function deleteNOTES() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $noteId = $_POST['id'] ?? null;
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để xóa note.']);
                exit;
            }

            if (!$noteId) {
                echo json_encode(['status' => 'error', 'message' => 'ID note không hợp lệ.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $noteId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Note đã được xóa thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function updateNOTES(){
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            $noteId = $_POST['id'] ?? null;
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để cập nhật note.']);
                exit;
            }

            if (!$noteId) {
                echo json_encode(['status' => 'error', 'message' => 'ID note không hợp lệ.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['title' => $title, 'content' => $content, 'id' => $noteId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Note đã được cập nhật thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function autoSaveNOTES() {
        global $pdo;
        $noteIT = $_POST['id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tự động lưu note.']);
            exit;
        }

        if (!$noteIT) {
            echo json_encode(['status' => 'error', 'message' => 'ID note không hợp lệ.']);
            exit;
        }

        try{
            $stmt = $pdo->prepare(("UPDATE notes SET title = :title, content = :content WHERE id = :id AND user_id = :user_id"));
            $stmt->execute((['title' => $title, 'content' => $content, 'id' => $noteIT, 'user_id' => $userId]));
            echo json_encode(['status' => 'success', 'message' => 'Note đã được tự động lưu thành công.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

?>