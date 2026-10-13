<?php
$host = '127.0.0.1';
$database = 'tracnghiemyhct';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$database}`");

    $tableCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($database))->fetchColumn();
    if ($tableCount > 0) {
        throw new RuntimeException("Database '{$database}' đã có dữ liệu. Trình cài đặt chỉ dùng cho database mới, trống.");
    }

    $schema = file_get_contents(__DIR__ . '/database.sql');
    if ($schema === false) {
        throw new RuntimeException('Không đọc được file database.sql.');
    }

    $pdo->exec($schema);

    echo "Thành công! Đã tạo database '{$database}' với cấu trúc dữ liệu mới.\n";
    echo "Tài khoản quản trị mặc định: admin / Hoiphunu@789\n";
    echo "Hãy đăng nhập và tạo danh sách đơn vị trước khi mở hệ thống cho thí sinh.\n";
} catch (Throwable $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
    echo "Vui lòng kiểm tra MySQL và cấu hình kết nối trong setup_db.php.\n";
    exit(1);
}
