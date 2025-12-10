<?php

require_once __DIR__ . '/../models/comment_model.php';
require_once __DIR__ . '/../models/user_model.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_middleware.php';

$commentModel = new CommentModel($pdo);
$userModel = new UserModel($pdo);

if (($_GET['action'] ?? '') === 'get_comments') {

    header('Content-Type: application/json');

    $postId = (int)($_GET['post_id'] ?? 0);
    $page   = (int)($_GET['page'] ?? 1);
    $perPage = 5; 

   
    $result = $commentModel->getCommentsPaginated($postId, $page, $perPage);

    echo json_encode([
        'success'    => true,
        'comments'   => $result['comments'],
        'pagination' => $result['pagination']
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {

    AuthMiddleware::requireLogin();

    $post_id = (int) ($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    $result = $commentModel->createComment($_SESSION['user_id'], $post_id, $content);

    echo json_encode([
        'success' => $result === true,
        'error' => $result === true ? null : $result
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    AuthMiddleware::requireLogin();

    $comment_id = (int) ($_POST['comment_id'] ?? 0);
    $post_id = (int) ($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    $comment = $commentModel->getCommentById($comment_id);
    if (!$comment) {
        echo json_encode([
            'success' => false,
            'error' => 'Comment not found'
        ]);
        exit;
    }

    AuthMiddleware::requireOwnerOrAdmin($comment['user_id']);

    $result = $commentModel->updateComment($comment_id, $content);

    echo json_encode([
        'success' => $result === true,
        'error' => $result === true ? null : $result
    ]);
    exit;
}

if (($_GET['action'] ?? '') === 'delete') {

    AuthMiddleware::requireLogin();

    header('Content-Type: application/json');

    $comment_id = (int)($_GET['id'] ?? 0);
    $comment = $commentModel->getCommentById($comment_id);

    if (!$comment) {
        echo json_encode([
            'success' => false,
            'error' => 'Comment not found.'
        ]);
        exit;
    }

    AuthMiddleware::requireOwnerOrAdmin($comment['user_id']);

    $result = $commentModel->deleteComment($comment_id);

    echo json_encode([
        'success' => $result === true,
        'error'   => $result === true ? null : $result
    ]);
    exit;
}

