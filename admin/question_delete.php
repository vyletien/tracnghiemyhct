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
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="row justify-content-center py-4">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-danger text-white p-4">
                    <h4 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Xác nhận xóa câu hỏi</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">Bạn có chắc chắn muốn xóa câu hỏi này cùng toàn bộ đáp án liên quan?</p>
                    <form method="POST" class="d-flex justify-content-end gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <a href="questions.php" class="btn btn-light border"><i class="bi bi-x-lg me-1"></i>Hủy</a>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Xóa câu hỏi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

redirect('questions.php');
