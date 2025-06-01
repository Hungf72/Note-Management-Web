<?php
    session_start();
    require_once __DIR__ . '/../models/connect.php';

    header('Content-Type: application/json');

    function getLABELS() {
        global $pdo;
        $userId = $_SESSION['user']['id'] ?? null;
        $noteId = $_POST['note_id'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để xem nhãn.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM labels WHERE user_id = :user_id AND note_id = :note_id");
            $stmt->execute(['user_id' => $userId, 'note_id' => $noteId]);
            $labels = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'labels' => $labels]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    function createLABELS() {
        global $pdo;        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $noteId = $_POST['note_id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tạo nhãn.']);
                exit;
            }

            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Tên nhãn không được để trống.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO labels (name, note_id, user_id) VALUES (:name, :note_id, :user_id)");
                $stmt->execute(['name' => $name, 'note_id' => $noteId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Nhãn đã được tạo thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function deleteLABELS() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $labelId = $_POST['id'] ?? null;
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để xóa nhãn.']);
                exit;
            }

            if (empty($labelId)) {
                echo json_encode(['status' => 'error', 'message' => 'ID nhãn không được để trống.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM labels WHERE label_id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $labelId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Nhãn đã được xóa thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function editLABELS() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $labelId = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để cập nhật nhãn.']);
                exit;
            }

            if (empty($labelId) || empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'ID nhãn và tên nhãn không được để trống.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("UPDATE labels SET name = :name WHERE label_id = :id AND user_id = :user_id");
                $stmt->execute(['name' => $name, 'id' => $labelId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Nhãn đã được cập nhật thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'get':
                getLABELS();
                break;
            case 'create':
                createLABELS();
                break;
            case 'delete':
                deleteLABELS();
                break;
            case 'edit':
                editLABELS();
                break;
            default:
                echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ.']);
                exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
        exit;
    }
?>