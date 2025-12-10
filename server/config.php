<?php

$allowed_origins = ['http://127.0.0.1:5500', 'http://localhost:5500'];

if (in_array($_SERVER['HTTP_ORIGIN'] ?? '', $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',      
    'secure'   => false,   
    'httponly' => true,
    'samesite' => 'Lax',   
]);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DB_HOST', 'localhost');
define('DB_NAME', 'iskaktim');
define('DB_USER', 'iskaktim');
define('DB_PASS', 'webove aplikace');

define('BASE_PATH', dirname(__DIR__));
define('SITE_NAME', 'ZWA Forum');
define('SITE_URL', 'http://localhost:5400/');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}