<?php

require_once __DIR__ . '/../models/post_model.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_middleware.php';

$postModel = new PostModel($pdo);

header('Content-Type: application/json');

if (($_GET['action'] ?? '') === 'get_posts') {


    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 5;

    $mode = $_GET['mode'] ?? 'all';

    if ($mode === 'my') {
        AuthMiddleware::requireLogin();
        $result = $postModel->getPostsPaginated($page, $perPage, $_SESSION['user_id']);
    } else {
        $result = $postModel->getPostsPaginated($page, $perPage, null);
    }

    echo json_encode([
        'success' => true,
        'posts' => $result['posts'],
        'pagination' => $result['pagination']
    ]);
    exit;
}

if (($_GET['action'] ?? '') === 'get_post') {
    $post_id = (int) ($_GET['id'] ?? 0);

    try {
        $post = $postModel->getPostById($post_id);

        if ($post) {
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Post not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to load post']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {

    AuthMiddleware::requireLogin();

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        echo json_encode(['success' => false, 'error' => 'Title and content cannot be empty.']);
        exit;
    }

    $imageName = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {


        $uploadDir = __DIR__ . '/../../client/uploads/posts/';

        $allowed = ['image/jpeg', 'image/png', 'image/gif'];

        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $_FILES['image']['tmp_name']);
        finfo_close($info);

        if (!in_array($mime, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF allowed.']);
            exit;
        }


        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Max file size is 2MB.']);
            exit;
        }


        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'post_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;


        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload image.']);
            exit;
        }
    }

    $result = $postModel->createPost($_SESSION['user_id'], $title, $content, $imageName);

    if ($result === true) {
        echo json_encode(['success' => true, 'message' => 'Post successfully created.']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result]);
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    AuthMiddleware::requireLogin();

    $post_id = (int) ($_POST['post_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    $post = $postModel->getPostById($post_id);
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found.']);
        exit;
    }

    AuthMiddleware::requireOwnerOrAdmin($post['user_id']);

    if ($title === '' || $content === '') {
        echo json_encode(['success' => false, 'error' => 'Title and content cannot be empty.']);
        exit;
    }

    $currentImage = $post['image']; 
    $uploadDir = __DIR__ . '/../../client/uploads/posts/';

    if (isset($_POST['delete_image']) && $_POST['delete_image'] === "1") {
        if ($currentImage && file_exists($uploadDir . $currentImage)) {
            unlink($uploadDir . $currentImage);
        }
        $currentImage = null;
    }

    if (!empty($_FILES['new_image']['name']) &&
        $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {

        $allowed = ['image/jpeg', 'image/png', 'image/gif'];

        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $_FILES['new_image']['tmp_name']);
        finfo_close($info);

        if (!in_array($mime, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF allowed.']);
            exit;
        }

        if ($_FILES['new_image']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Max file size is 2MB.']);
            exit;
        }

        $ext = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
        $newName = 'post_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;

        if ($currentImage && file_exists($uploadDir . $currentImage)) {
            unlink($uploadDir . $currentImage);
        }

        if (!move_uploaded_file($_FILES['new_image']['tmp_name'], $uploadDir . $newName)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload image.']);
            exit;
        }

        $currentImage = $newName;
    }

    $result = $postModel->updatePost($post_id, $title, $content, $currentImage);

    echo json_encode([
        'success' => (bool)$result,
        'message' => 'Post successfully updated.'
    ]);

    exit;
}

if (($_GET['action'] ?? '') === 'delete') {

    AuthMiddleware::requireLogin();

    $post_id = (int) ($_GET['id'] ?? 0);

    $post = $postModel->getPostById($post_id);
    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found.']);
        exit;
    }

    AuthMiddleware::requireOwnerOrAdmin($post['user_id']);

    $result = $postModel->deletePost($post_id);

    if ($result === true) {
        echo json_encode(['success' => true, 'message' => 'Post successfully deleted.']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result]);
    }

    exit;
}
