<?php
    session_start();
    require_once __DIR__ . '/../models/connect.php';

    header('Content-Type: application/json');

    function getTasks() {
        global $pdo;
        $userId = $_SESSION['user']['id'] ?? null;
        $noteId = $_POST['note_id'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để xem nhãn.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id AND note_id = :note_id");
            $stmt->execute(['user_id' => $userId, 'note_id' => $noteId]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'tasks' => $tasks]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    function createTASKS() {
        global $pdo;        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $noteId = $_POST['note_id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tạo task.']);
                exit;
            }

            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Tên task không được để trống.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO tasks (name, note_id, user_id) VALUES (:name, :note_id, :user_id)");
                $stmt->execute(['name' => $name, 'note_id' => $noteId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Nhãn đã được tạo thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function deleteTASKS() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskId = $_POST['id'] ?? null;
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để xóa task.']);
                exit;
            }

            if (empty($taskId)) {
                echo json_encode(['status' => 'error', 'message' => 'ID task không được để trống.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE label_id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $taskId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Nhãn đã được xóa thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function editTASKS() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskId = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để cập nhật nhãn.']);
                exit;
            }

            if (empty($taskId) || empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'ID nhãn và tên nhãn không được để trống.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("UPDATE tasks SET name = :name WHERE tasks_id = :id AND user_id = :user_id");
                $stmt->execute(['name' => $name, 'id' => $taskId, 'user_id' => $userId]);
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
                getTAsks();
                break;
            case 'create':
                createTASKS();
                break;
            case 'delete':
                deleteTASKS();
                break;
            case 'edit':
                editTASKS();
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