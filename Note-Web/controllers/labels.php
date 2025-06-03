<?php
    session_start();
    require_once __DIR__ . '/../models/connect.php';

    header('Content-Type: application/json');

    function getLABELS() {
        global $pdo;
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'You need to login.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM labels WHERE user_id = :user_id ORDER BY name ASC");
            $stmt->execute(['user_id' => $userId]);
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
            $name = trim($_POST['name'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'You need to login to create a label.']);
                exit;
            }

            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Label name is required.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO labels (name, user_id) VALUES (:name, :user_id)");
                $stmt->execute(['name' => $name, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Label created successfully.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function updateLABELS() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $labelId = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'You need to login to update a label.']);
                exit;
            }

            if (empty($labelId) || empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Label ID and name are required.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("UPDATE labels SET name = :name WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['name' => $name, 'id' => $labelId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Label updated successfully.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
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
                echo json_encode(['status' => 'error', 'message' => 'You need to login to delete a label.']);
                exit;
            }

            if (empty($labelId)) {
                echo json_encode(['status' => 'error', 'message' => 'Label ID is required.']);
                exit;
            }

            try {
                // First delete all note-label associations for this label
                $stmt = $pdo->prepare("DELETE FROM note_labels WHERE label_id = :label_id");
                $stmt->execute(['label_id' => $labelId]);

                // Then delete the label itself
                $stmt = $pdo->prepare("DELETE FROM labels WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $labelId, 'user_id' => $userId]);

                echo json_encode(['status' => 'success', 'message' => 'Label deleted successfully.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function assignLABELtoNote() {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $noteId = $_POST['note_id'] ?? null;
            $labelId = $_POST['label_id'] ?? null;
            $userId = $_SESSION['user']['id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'You need to login to assign a label to a note.']);
                exit;
            }

            if (empty($noteId) || empty($labelId)) {
                echo json_encode(['status' => 'error', 'message' => 'Note ID and Label ID are required.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id, user_id) VALUES (:note_id, :label_id, :user_id)");
                $stmt->execute(['note_id' => $noteId, 'label_id' => $labelId, 'user_id' => $userId]);
                echo json_encode(['status' => 'success', 'message' => 'Label assigned to note successfully.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getLABELS();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    createLABELS();
                    break;
                case 'update':
                    updateLABELS();
                    break;
                case 'delete':
                    deleteLABELS();
                    break;
                case 'assign':
                    assignLABELtoNote();
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
                    exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No action specified.']);
            exit;
        }
    }
?>