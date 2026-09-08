<?php
require 'config/database.php';
try {
    $attemptModeColumn = $pdo->query("SHOW COLUMNS FROM exams LIKE 'attempt_mode'")->fetch();
    if (!$attemptModeColumn) {
        $pdo->exec("ALTER TABLE exams ADD COLUMN attempt_mode TINYINT NOT NULL DEFAULT 0 COMMENT '0: một lần, 1: nhiều lần, 2: tối đa một lần mỗi ngày'");
    }
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL, ADD COLUMN address VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE users MODIFY password VARCHAR(255) NULL");
    echo "Migration Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
