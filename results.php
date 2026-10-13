<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkLogin();

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) redirect('/index.php');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT ea.*, e.title, e.show_results, u.fullname FROM exam_attempts ea JOIN exams e ON ea.exam_id = e.id JOIN users u ON ea.user_id = u.id WHERE ea.user_id = ? AND ea.exam_id = ? ORDER BY ea.id DESC LIMIT 1");
$stmt->execute([$user_id, $exam_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    die("Không tìm thấy kết quả bài thi.");
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .congrats-banner { position: relative; overflow: hidden; border: 1px solid rgba(25,135,84,.2); border-radius: 1.25rem; padding: 2rem 1.5rem; background: linear-gradient(135deg, #e8fff3 0%, #f8fffc 52%, #fff8df 100%); box-shadow: 0 .5rem 1.5rem rgba(25,135,84,.12); }
    .congrats-banner::before, .congrats-banner::after { content: ''; position: absolute; width: 90px; height: 90px; border-radius: 50%; background: rgba(255,193,7,.16); }
    .congrats-banner::before { top: -35px; left: -25px; }
    .congrats-banner::after { right: -25px; bottom: -40px; background: rgba(25,135,84,.12); }
    .congrats-icon, .congrats-banner h2, .congrats-banner p { position: relative; z-index: 1; }
    .congrats-icon { font-size: 3.5rem; line-height: 1; }
    .score-card { border-radius: 1rem; padding: 1.25rem; background: linear-gradient(135deg, #fff8df, #fff); border: 1px solid rgba(255,193,7,.35); }
    .score-value { color: #198754; font-size: 3.5rem; font-weight: 800; line-height: 1; }
    .score-label { color: #6c757d; font-size: 1.05rem; margin-top: .5rem; }
</style>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm text-center">
            <div class="card-header bg-success text-white">
                <h4>Kết quả bài thi</h4>
            </div>
            <div class="card-body py-5">
                <h3 class="text-primary mb-4"><?= h($attempt['title']) ?></h3>
                
                <div class="congrats-banner mb-4">
                    <div class="congrats-icon mb-3">🏆</div>
                    <h2 class="text-success fw-bold mb-2">Chúc mừng <?= h($attempt['fullname'] ?: ($_SESSION['fullname'] ?? 'bạn')) ?>!</h2>
                    <p class="fs-5 text-dark mb-0">Bạn đã hoàn thành bài thi thành công.</p>
                    <p class="text-muted small mt-2 mb-0">Kết quả của bạn đã được ghi nhận.</p>
                </div>

                <?php if ($attempt['show_results']): ?>
                    <div class="score-card mb-4">
                        <div class="score-value"><?= number_format($attempt['score'], 2) ?></div>
                        <div class="score-label">Điểm của bạn</div>
                    </div>
                <?php endif; ?>

                <?php /* Legacy score display intentionally disabled. ?>
                    <div class="display-3 text-success fw-bold mb-3"><?= number_format($attempt['score'], 2) ?> <small class="fs-4 text-muted">/ 10</small></div>
                    <p class="fs-5">Biểu điểm trên 10</p>
                <?php else: ?>
                    <h5 class="text-muted">Đề thi này không cấu hình hiển thị điểm số.</h5>
                    <p>Vui lòng liên hệ quản trị viên hoặc xem sau.</p>
                <?php */ ?>
                
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
