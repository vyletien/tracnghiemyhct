<?php
// session_start() placed at the very beginning of the app
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy cấu hình từ biến môi trường (Ưu tiên $_ENV hoặc $_SERVER vì getenv() có thể bị disable)
$host = $_ENV['MYSQLHOST'] ?? $_SERVER['MYSQLHOST'] ?? getenv('MYSQLHOST') ?: '127.0.0.1';
$dbname = $_ENV['MYSQLDATABASE'] ?? $_SERVER['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') ?: 'tracnghiem';
$user = $_ENV['MYSQLUSER'] ?? $_SERVER['MYSQLUSER'] ?? getenv('MYSQLUSER') ?: 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? $_SERVER['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?: '';
$port = $_ENV['MYSQLPORT'] ?? $_SERVER['MYSQLPORT'] ?? getenv('MYSQLPORT') ?: '3306';
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
     // Hiển thị lỗi kèm thông tin Host để debug (Xóa khi chạy thực tế nếu cần bảo mật)
     die("Connection failed: " . $e->getMessage() . " (Target: $host:$port, Database: $dbname)");
}

// TinyMCE API Key (Lấy tại https://www.tiny.cloud/)
define('TINYMCE_KEY', 'uvgoznxrw0poy6qso8jc8i6istpxb00y97wnc7zsvoog0ept');
?>
