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
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center py-4">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-danger text-white p-4">
                <h4 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Xác nhận xóa đề thi</h4>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Bạn có chắc chắn muốn xóa đề thi này? Các câu hỏi liên kết và lịch sử liên quan có thể bị xóa theo.</p>
                <form method="POST" class="d-flex justify-content-end gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <a href="exams.php" class="btn btn-light border"><i class="bi bi-x-lg me-1"></i>Hủy</a>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Xóa đề thi</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
