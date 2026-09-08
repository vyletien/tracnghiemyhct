<?php
require_once 'd:/VTProject/TracNghiem/config/database.php';
$stmt = $pdo->query("SHOW COLUMNS FROM exam_attempts");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($columns);
