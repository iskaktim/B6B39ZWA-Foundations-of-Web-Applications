<?php

require_once __DIR__ . '/../models/user_model.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_middleware.php';

header('Content-Type: application/json');

$userModel = new UserModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['confirm_password'] ?? '');

    if (strlen($password) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'The password must contain at least 6 characters.'
        ]);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email format is invalid.'
        ]);
        exit;
    }
    if ($passwordConfirm !== $password) {
        echo json_encode([
            'success' => false,
            'message' => 'Passwords do not match.'
        ]);
        exit;
    }
    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username cannot be empty.'
        ]);
        exit;
    }
    if (empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email cannot be empty.'
        ]);
        exit;
    }
    if (empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Password cannot be empty.'
        ]);
        exit;
    }
    if (empty($passwordConfirm)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please confirm your password.'
        ]);
        exit;
    }

    $result = $userModel->register($username, $email, $password);

    if ($result === true) {
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful. You can now log in.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result
        ]);
    }
    exit;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username cannot be empty.'
        ]);
        exit;
    }

    if (empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Password cannot be empty.'
        ]);
        exit;
    }

    $result = $userModel->login($username, $password);

    if ($result === true) {

        session_regenerate_id(true);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'] ?? 'user'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result
        ]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    AuthMiddleware::requireLogin();

    $userModel->logout();

    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully.'
    ]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'check_session') {

    if (!empty($_SESSION['user_id'])) {
        echo json_encode([
            'loggedIn' => true,
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'] ?? 'user'
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['loggedIn' => false]);
    }
    exit;
}

if (($_GET['action'] ?? '') === 'get_users') {

    AuthMiddleware::requireAdmin();

    $stmt = $pdo->query("
        SELECT 
            u.id, u.username, u.email, u.role, u.created_at,
            (SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id) AS post_count
        FROM users u
        ORDER BY u.id ASC
    ");

    echo json_encode([
        'success' => true,
        'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    exit;
}

if (($_POST['action'] ?? '') === 'promote') {
    AuthMiddleware::requireAdmin();

    $currentRole = $_SESSION['role'];
    $user_id = (int)$_POST['user_id'];

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $targetRole = $stmt->fetchColumn();

    if ($currentRole === 'admin' && $targetRole !== 'user') {
        echo json_encode(['success' => false, 'error' => 'You cannot modify this user.']);
        exit;
    }

    if ($targetRole === 'owner') {
        echo json_encode(['success' => false, 'error' => 'Cannot modify the owner.']);
        exit;
    }

    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$user_id]);

    echo json_encode(['success' => true]);
    exit;
}


if (($_POST['action'] ?? '') === 'delete_user') {

    AuthMiddleware::requireAdmin();

    $currentRole = $_SESSION['role'];
    $user_id = (int)$_POST['user_id'];

    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'You cannot delete yourself.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $targetRole = $stmt->fetchColumn();

    if ($currentRole === 'admin' && $targetRole !== 'user') {
        echo json_encode(['success' => false, 'error' => 'Admins can only delete regular users.']);
        exit;
    }

    if ($targetRole === 'owner') {
        echo json_encode(['success' => false, 'error' => 'Cannot delete the owner.']);
        exit;
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

    echo json_encode(['success' => true]);
    exit;
}

if (($_POST['action'] ?? '') === 'demote') {

    AuthMiddleware::requireOwner();

    $currentRole = $_SESSION['role'];
    $user_id = (int)$_POST['user_id'];

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $targetRole = $stmt->fetchColumn();

    if ($targetRole === 'owner') {
        echo json_encode(['success' => false, 'error' => 'Cannot modify owner.']);
        exit;
    }

    if ($currentRole === 'admin' && $targetRole !== 'user') {
        echo json_encode(['success' => false, 'error' => 'No permission.']);
        exit;
    }

    $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$user_id]);

    echo json_encode(['success' => true]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_profile') {

    AuthMiddleware::requireLogin();

    $user = $userModel->getUserProfile($_SESSION['user_id']);

    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {

    AuthMiddleware::requireLogin();

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username cannot be empty.'
        ]);
        exit;
    }

    if (empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email cannot be empty.'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email format is invalid.'
        ]);
        exit;
    }

    $result = $userModel->updateProfile($_SESSION['user_id'], $username, $email);

    if ($result === true) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'username' => $username
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {

    AuthMiddleware::requireLogin();

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Current password cannot be empty.'
        ]);
        exit;
    }

    if (empty($new_password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New password cannot be empty.'
        ]);
        exit;
    }

    if (empty($confirm_password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Please confirm your new password.'
        ]);
        exit;
    }

    if (strlen($new_password) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New password must contain at least 6 characters.'
        ]);
        exit;
    }

    if ($new_password === $current_password) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New password must be different from the current password.'
        ]);
        exit;
    }



    if ($new_password !== $confirm_password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'New passwords do not match.']);
        exit;
    }

    $result = $userModel->updatePassword($_SESSION['user_id'], $current_password, $new_password);

    if ($result === true) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_avatar') {

    AuthMiddleware::requireLogin();

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Upload failed or no file.']);
        exit;
    }

    $upload_dir = __DIR__ . '/../../client/uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif'];

    $info = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($info, $_FILES['avatar']['tmp_name']);
    finfo_close($info);

    if (!in_array($mime, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF allowed.']);
        exit;
    }

    if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Max file size is 2MB.']);
        exit;
    }

    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {

        $result = $userModel->updateAvatar($_SESSION['user_id'], $filename);

        if ($result === true) {
            echo json_encode(['success' => true, 'message' => 'Avatar uploaded.', 'filename' => $filename]);
        } else {
            unlink($upload_dir . $filename);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database update failed.']);
        }

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Upload failed.']);
    }

    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'remove_avatar') {

    AuthMiddleware::requireLogin();

    $user = $userModel->getUserById($_SESSION['user_id']);

    if ($user && $user['avatar']) {
        $file = __DIR__ . '/../../client/uploads/avatars/' . $user['avatar'];
        if (file_exists($file)) {
            unlink($file);
        }
    }

    $result = $userModel->removeAvatar($_SESSION['user_id']);

    if ($result === true) {
        echo json_encode(['success' => true, 'message' => 'Avatar removed.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to remove avatar.']);
    }
    exit;
}

