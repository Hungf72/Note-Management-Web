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
            $password = trim($_POST['note_password'] ?? '');

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tạo note.']);
                exit;
            }      
                  
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($ext, $allowedTypes)) {
                    echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.']);
                    exit;
                }
                
                $filename = 'note_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/' . $filename;
                }
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO notes (title, content, user_id, image_path, note_password) VALUES (:title, :content, :user_id, :image_path, :note_password)");
                $stmt->execute([
                    'title' => $title,
                    'content' => $content,
                    'user_id' => $userId,
                    'image_path' => $imagePath,
                    'note_password' => $password
                ]);
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
                $stmt = $pdo->prepare("SELECT image_path FROM notes WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $noteId, 'user_id' => $userId]);
                $note = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($note && $note['image_path']) {
                    $imagePath = __DIR__ . '/../' . $note['image_path'];
                    if (file_exists($imagePath) && !unlink($imagePath)) {
                        unlink($imagePath);
                        echo json_encode(['status' => 'error', 'message' => 'Cannot delete the image file.']);
                        exit;
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM note_labels WHERE note_id = :note_id");
                $stmt->execute(['note_id' => $noteId]);

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
                $labelStmt = $pdo->prepare("SELECT label_id FROM note_labels WHERE note_id = :note_id");
                $labelStmt->execute(['note_id' => $noteId]);
                $note['labels'] = array_map('intval', array_column($labelStmt->fetchAll(PDO::FETCH_ASSOC), 'label_id'));

                // Xử lý upload hình ảnh
                $imagePath = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (!in_array($ext, $allowedTypes)) {
                        echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.']);
                        exit;
                    }
                    
                    $filename = 'note_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                        $imagePath = 'uploads/' . $filename;
                    }
                }                
                if ($imagePath) {
                    if ($note['image_path']) {
                        $oldImagePath = __DIR__ . '/../' . $note['image_path'];
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $updateStmt = $pdo->prepare("UPDATE notes SET image_path = :image_path WHERE id = :id AND user_id = :user_id");
                    $updateStmt->execute(['image_path' => $imagePath, 'id' => $noteId, 'user_id' => $userId]);
                    $note['image_path'] = $imagePath; 
                }

                if (isset($_POST['remove_password']) && $_POST['remove_password'] == '1') {
                    $updateStmt = $pdo->prepare("UPDATE notes SET note_password = NULL WHERE id = :id AND user_id = :user_id");
                    $updateStmt->execute(['id' => $noteId, 'user_id' => $userId]);
                    $note['note_password'] = null;
                } else if (isset($_POST['note_password']) && !empty($_POST['note_password'])) {
                    $password = trim($_POST['note_password']);
                    $updateStmt = $pdo->prepare("UPDATE notes SET note_password = :note_password WHERE id = :id AND user_id = :user_id");
                    $updateStmt->execute(['note_password' => $password, 'id' => $noteId, 'user_id' => $userId]);
                    $note['note_password'] = $password;
                }

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

        try {
            $stmt = $pdo->prepare("SELECT image_path FROM notes WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $noteIT, 'user_id' => $userId]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);

            // Xử lý upload hình ảnh
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($ext, $allowedTypes)) {
                    echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.']);
                    exit;
                }
                
                if ($note && $note['image_path']) {
                    $oldImagePath = __DIR__ . '/../' . $note['image_path'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                $filename = 'note_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/' . $filename;
                }
            }

            if ($imagePath) {
                $stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content, image_path = :image_path WHERE id = :id AND user_id = :user_id");
                $stmt->execute([
                    'title' => $title,
                    'content' => $content,
                    'image_path' => $imagePath,
                    'id' => $noteIT,
                    'user_id' => $userId
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content WHERE id = :id AND user_id = :user_id");
                $stmt->execute([
                    'title' => $title,
                    'content' => $content,
                    'id' => $noteIT,
                    'user_id' => $userId
                ]);
            }

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
            // Kiểm tra và cập nhật mật khẩu nếu có
            if (isset($_POST['remove_password']) && $_POST['remove_password'] == '1') {
                $updateStmt = $pdo->prepare("UPDATE notes SET note_password = NULL WHERE id = :id AND user_id = :user_id");
                $updateStmt->execute(['id' => $noteIT, 'user_id' => $userId]);
            } else if (isset($_POST['note_password']) && !empty($_POST['note_password'])) {
                $password = trim($_POST['note_password']);
                $updateStmt = $pdo->prepare("UPDATE notes SET note_password = :note_password WHERE id = :id AND user_id = :user_id");
                $updateStmt->execute(['note_password' => $password, 'id' => $noteIT, 'user_id' => $userId]);
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

    function checkPassword(){
        global $pdo;
        $noteId = $_POST['id'] ?? null;
        $password = $_POST['password'] ?? '';
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId || !$noteId) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT content, note_password, image_path FROM notes WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $noteId, 'user_id' => $userId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$note) {
            echo json_encode(['status' => 'error', 'message' => 'Note not found']);
            exit;
        }
        if ($note['note_password'] === $password) {
            echo json_encode(['status' => 'success', 'content' => $note['content'], 'image_path' => $note['image_path']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
        }
        exit;
    }

    // Xử lý action markSharedRead để cập nhật is_share=0 cho các note được chia sẻ
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['action']) && $input['action'] === 'markSharedRead' && !empty($input['note_ids'])) {
            $userId = $_SESSION['user']['id'] ?? null;
            $noteIds = array_map('intval', $input['note_ids']);

            if ($userId && count($noteIds) > 0) {
                $in = str_repeat('?,', count($noteIds) - 1) . '?';
                $sql = "UPDATE notes SET is_share=0 WHERE user_id=? AND id IN ($in)";
                $params = array_merge([$userId], $noteIds);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['status' => 'success', 'message' => 'Đã đánh dấu đã đọc ghi chú chia sẻ.']);
                exit;
            }
            echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin user hoặc note.']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? '';

        if ($id && $action === 'edit') {
            editNOTES($id);
        } else {
            getNOTES();
        }
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            case 'view_protected':
                checkPassword();
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
                exit;
        }
    } 
    else {
        echo json_encode(['status' => 'error', 'message' => 'Unsupported request method']);
        exit;
    }
?>
