<?php
// session_start() placed at the very beginning of the app
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy cấu hình từ biến môi trường (cho Railway/PaaS) hoặc dùng mặc định (cho localhost)
$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$dbname = getenv('MYSQLDATABASE') ?: 'tracnghiem';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // In case of error, you might want to log it or show a friendly message
     die("Connection failed: " . $e->getMessage());
}

// TinyMCE API Key (Lấy tại https://www.tiny.cloud/)
define('TINYMCE_KEY', 'uvgoznxrw0poy6qso8jc8i6istpxb00y97wnc7zsvoog0ept');
?>
