<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if (!$id) {
    redirect('exams.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pdo->prepare("DELETE FROM exams WHERE id = ?")->execute([$id]);
    redirect('exams.php');
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Xác nhận xóa</title></head><body>
<p>Bạn có chắc chắn muốn xóa đề thi này?</p>
<form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit">Xóa</button>
    <a href="exams.php">Hủy</a>
</form>
</body></html>
