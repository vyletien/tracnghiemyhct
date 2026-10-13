<?php
require 'config/database.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $attemptModeColumn = $pdo->query("SHOW COLUMNS FROM exams LIKE 'attempt_mode'")->fetch();
    if (!$attemptModeColumn) {
        $pdo->exec("ALTER TABLE exams ADD COLUMN attempt_mode TINYINT NOT NULL DEFAULT 0 COMMENT '0: một lần, 1: nhiều lần, 2: tối đa một lần mỗi ngày'");
    }
    $predictionColumn = $pdo->query("SHOW COLUMNS FROM exam_attempts LIKE 'prediction_value'")->fetch();
    if (!$predictionColumn) {
        $pdo->exec("ALTER TABLE exam_attempts ADD COLUMN prediction_value INT DEFAULT NULL");
    }
    $phoneColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
    if (!$phoneColumn) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL");
    }
    $addressColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'address'")->fetch();
    if (!$addressColumn) {
        $pdo->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL");
    }
    $unitColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'unit_id'")->fetch();
    if (!$unitColumn) {
        $pdo->exec("ALTER TABLE users ADD COLUMN unit_id INT NULL AFTER address");
        $pdo->exec("INSERT IGNORE INTO units (name)
            SELECT DISTINCT TRIM(SUBSTRING_INDEX(address, ' - ', -1))
            FROM users
            WHERE role = 'student' AND address LIKE '% - %'
              AND TRIM(SUBSTRING_INDEX(address, ' - ', -1)) <> ''");
        $pdo->exec("UPDATE users u
            JOIN units un ON un.name = TRIM(SUBSTRING_INDEX(u.address, ' - ', -1))
            SET u.unit_id = un.id
            WHERE u.role = 'student' AND u.address LIKE '% - %'");
        $pdo->exec("ALTER TABLE users
            ADD CONSTRAINT fk_users_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE RESTRICT");
    }
    $pdo->exec("INSERT IGNORE INTO units (name) VALUES
        ('Ban Phụ nữ Công an tỉnh'),
        ('Hội Phụ nữ cơ quan BCH Quân sự tỉnh')");
    $pdo->exec("ALTER TABLE users MODIFY password VARCHAR(255) NULL");
    echo "Migration Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
