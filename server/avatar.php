<?php

$filename = $_GET['filename'] ?? '';

$uploadDir = __DIR__ . '/../client/uploads/avatars/';
$defaultAvatar = __DIR__ . '/../client/images/default-avatar.jpg';

if (empty($filename)) {
    if (file_exists($defaultAvatar)) {
        header('Content-Type: image/jpeg');
        readfile($defaultAvatar);
    }
    exit;
}

$avatarPath = $uploadDir . basename($filename);

if (file_exists($avatarPath)) {

    $ext = strtolower(pathinfo($avatarPath, PATHINFO_EXTENSION));

    switch ($ext) {
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        default:
            header('Content-Type: image/jpeg');
            break;
    }

    readfile($avatarPath);
    exit;
}

if (file_exists($defaultAvatar)) {
    header('Content-Type: image/jpeg');
    readfile($defaultAvatar);
}
exit;
