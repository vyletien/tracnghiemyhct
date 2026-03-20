<?php
require 'config/database.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL, ADD COLUMN address VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE users MODIFY password VARCHAR(255) NULL");
    echo "Migration Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
