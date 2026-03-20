<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkLogin();

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) redirect('/index.php');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT ea.*, e.title, e.show_results FROM exam_attempts ea JOIN exams e ON ea.exam_id = e.id WHERE ea.user_id = ? AND ea.exam_id = ? ORDER BY ea.id DESC LIMIT 1");
$stmt->execute([$user_id, $exam_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    die("Không tìm thấy kết quả bài thi.");
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm text-center">
            <div class="card-header bg-success text-white">
                <h4>Kết quả bài thi</h4>
            </div>
            <div class="card-body py-5">
                <h3 class="text-primary mb-4"><?= h($attempt['title']) ?></h3>
                
                <?php if ($attempt['show_results']): ?>
                    <div class="display-3 text-success fw-bold mb-3"><?= number_format($attempt['score'], 2) ?> <small class="fs-4 text-muted">/ 10</small></div>
                    <p class="fs-5">Biểu điểm trên 10</p>
                <?php else: ?>
                    <h5 class="text-muted">Đề thi này không cấu hình hiển thị điểm số.</h5>
                    <p>Vui lòng liên hệ quản trị viên hoặc xem sau.</p>
                <?php endif; ?>
                
                <hr class="my-4">
                <div class="d-flex justify-content-between text-muted">
                    <div><i class="bi bi-clock"></i> Bắt đầu: <?= date('H:i - d/m/Y', strtotime($attempt['start_time'])) ?></div>
                    <div><i class="bi bi-check-circle"></i> Nộp bài: <?= date('H:i - d/m/Y', strtotime($attempt['end_time'])) ?></div>
                </div>
                <div class="mt-3 text-muted small italic">
                    <i class="bi bi-info-circle"></i> Đây là kết quả của lần thi gần nhất. Bạn có thể xem lại toàn bộ lịch sử thi <a href="/history.php">tại đây</a>.
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="/index.php" class="btn btn-primary"><i class="bi bi-house"></i> Về trang chủ</a>
                <a href="/history.php" class="btn btn-outline-secondary"><i class="bi bi-journals"></i> Lịch sử thi của tôi</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
