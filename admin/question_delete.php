<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);
    } catch (\Exception $e) {
        // error handling
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Xác nhận xóa</title></head><body>
    <p>Bạn có chắc chắn muốn xóa câu hỏi này?</p>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit">Xóa</button>
        <a href="questions.php">Hủy</a>
    </form>
    </body></html>
    <?php exit;
}

redirect('questions.php');
