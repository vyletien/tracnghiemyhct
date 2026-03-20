<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Đang khởi tạo database...\n";
    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // Split by ; to execute multiple queries if needed, though most PDO can handle multiple in one exec if configured.
    // However, database.sql has CREATE DATABASE then USE, so we just run it.
    $pdo->exec($sql);
    
    echo "Thanh công! Đã tạo CSDL 'tracnghiem' và các bảng cần thiết.\n";
    echo "Tài khoản đăng nhập: admin / 123456\n";

} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
    echo "Vui lòng đảm bảo MySQL (XAMPP/WAMP) đang chạy và user 'root' không có mật khẩu.\n";
}
?>
