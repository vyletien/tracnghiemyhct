<?php
// session_start() placed at the very beginning of the app
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// --- CẤU HÌNH DATABASE CHO CPANEL / HOSTING ---
$host = '127.0.0.1';
$dbname = 'tracnghiem';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     error_log('Database connection failed: ' . $e->getMessage());
     http_response_code(500);
     exit('Database unavailable.');
     die("Lỗi kết nối database: " . $e->getMessage());
}

// TinyMCE API Key (Lấy tại https://www.tiny.cloud/)
define('TINYMCE_KEY', 'uvgoznxrw0poy6qso8jc8i6istpxb00y97wnc7zsvoog0ept');
?>
