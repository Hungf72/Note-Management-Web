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
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = :user_id ORDER BY is_pinned ASC, CASE WHEN is_pinned = 1 THEN pinned_order END DESC, last_modified ASC");
            $stmt->execute(['user_id' => $userId]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch labels for each note
            foreach ($notes as &$note) {
                $labelStmt = $pdo->prepare("SELECT label_id FROM note_labels WHERE note_id = :note_id");
                $labelStmt->execute(['note_id' => $note['id']]);
                $note['labels'] = array_map('intval', array_column($labelStmt->fetchAll(PDO::FETCH_ASSOC), 'label_id'));
            }

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
            $labels = !empty($_POST['labels']) ? explode(',', $_POST['labels']) : [];

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tạo note.']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO notes (title, content, user_id) VALUES (:title, :content, :user_id)");
                $stmt->execute(['title' => $title, 'content' => $content, 'user_id' => $userId]);
                $noteId = $pdo->lastInsertId();

                // Save label associations
                if (!empty($labels)) {
                    $labelStmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (:note_id, :label_id)");
                    foreach ($labels as $labelId) {
                        if ($labelId !== '') {
                            $labelStmt->execute(['note_id' => $noteId, 'label_id' => $labelId]);
                        }
                    }
                }

                echo json_encode(['status' => 'success', 'message' => 'Note đã được tạo thành công.']);
            } catch (PDOException $e) {
                $pdo->rollBack();
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
                // First delete the note-label associations
                $stmt = $pdo->prepare("DELETE FROM note_labels WHERE note_id = :note_id");
                $stmt->execute(['note_id' => $noteId]);

                // Then delete the note
                $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $noteId, 'user_id' => $userId]);

                echo json_encode(['status' => 'success', 'message' => 'Note đã được xóa thành công.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    function editNOTES($noteId){
        global $pdo;
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để xem note.']);
            exit;
        }

        if (!$noteId) {
            echo json_encode(['status' => 'error', 'message' => 'ID note không hợp lệ.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $noteId, 'user_id' => $userId]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($note) {
                // Fetch label associations
                $labelStmt = $pdo->prepare("SELECT label_id FROM note_labels WHERE note_id = :note_id");
                $labelStmt->execute(['note_id' => $noteId]);
                $note['labels'] = array_map('intval', array_column($labelStmt->fetchAll(PDO::FETCH_ASSOC), 'label_id'));
                echo json_encode(['status' => 'success', 'note' => $note]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy note.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    function autoSaveNOTES() {
        global $pdo;
        $noteIT = $_POST['id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $userId = $_SESSION['user']['id'] ?? null;
        $labels = !empty($_POST['labels']) ? explode(',', $_POST['labels']) : [];

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

            // Remove old label associations
            $delStmt = $pdo->prepare("DELETE FROM note_labels WHERE note_id = :note_id");
            $delStmt->execute(['note_id' => $noteIT]);

            // Add new label associations
            if (!empty($labels)) {
                $labelStmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (:note_id, :label_id)");
                foreach ($labels as $labelId) {
                    if ($labelId !== '') {
                        $labelStmt->execute(['note_id' => $noteIT, 'label_id' => $labelId]);
                    }
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Note đã được tự động lưu thành công.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    function togglePin() {
        global $pdo;
        $noteId = $_POST['id'] ?? null;
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId || !$noteId) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT is_pinned FROM notes WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $noteId, 'user_id' => $userId]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$note) {
                echo json_encode(['status' => 'error', 'message' => 'Note not found']);
                exit;
            }
            $newPinStatus = !$note['is_pinned'];
            
            if ($newPinStatus) {
                $stmt = $pdo->prepare("SELECT COALESCE(MAX(pinned_order), 0) + 1 as next_order FROM notes WHERE user_id = :user_id AND is_pinned = 1");
                $stmt->execute(['user_id' => $userId]);
                $nextOrder = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
                
                $stmt = $pdo->prepare("UPDATE notes SET is_pinned = 1, pinned_order = :order WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['order' => $nextOrder, 'id' => $noteId, 'user_id' => $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE notes SET is_pinned = 0, pinned_order = NULL WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $noteId, 'user_id' => $userId]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Note pin status updated']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? '';

        if ($id && $action === 'edit') {
            editNOTES($id);
        } else {
            getNOTES();
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'create':
                createNOTES();
                break;
            case 'delete':
                deleteNOTES();
                break;
            case 'autosave':
                autoSaveNOTES();
                break;
            case 'togglePin':
                togglePin();
                break;
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
                exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unsupported request method']);
        exit;
    }
?>
