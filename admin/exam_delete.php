<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$id = $_GET['id'] ?? null;
if ($id) {
    $pdo->prepare("DELETE FROM exams WHERE id = ?")->execute([$id]);
}
redirect('exams.php');
