<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkLogin();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT ea.*, e.title, e.show_results 
    FROM exam_attempts ea 
    JOIN exams e ON ea.exam_id = e.id 
    WHERE ea.user_id = ? 
    ORDER BY ea.id DESC
");
$stmt->execute([$user_id]);
$attempts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Lịch sử thi của tôi</h2>
    <a href="/index.php" class="btn btn-secondary">Danh sách đề thi</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>STT</th>
                    <th>Tên đề thi</th>
                    <th>Thời gian thi</th>
                    <th class="text-center">Dự đoán</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-center">Điểm số</th>
                </tr>
            </thead>
            <tbody>
                <?php $stt = 1; foreach ($attempts as $att): ?>
                <tr>
                    <td><?= $stt++ ?></td>
                    <td><?= h($att['title']) ?></td>
                    <td>
                        <?= date('d/m/Y H:i', strtotime($att['start_time'])) ?> 
                        <i class="bi bi-arrow-right mx-1"></i> 
                        <?= $att['end_time'] ? date('d/m/Y H:i', strtotime($att['end_time'])) : '-' ?>
                    </td>
                    <td class="text-center">
                        <?= h($att['prediction_value'] ?? '-') ?>
                    </td>
                    <td class="text-center">
                        <?php if($att['is_submitted']): ?>
                            <span class="badge bg-success">Đã nộp bài</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Chưa nộp / Đang thi</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center fw-bold">
                        <?php 
                        if ($att['show_results'] && $att['is_submitted']) {
                            echo '<span class="text-primary">' . number_format($att['score'], 2) . '</span>';
                        } elseif (!$att['show_results'] && $att['is_submitted']) {
                            echo '<span class="text-muted">Ẩn điểm</span>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($attempts) === 0): ?>
                <tr><td colspan="6" class="text-center py-4">Bạn chưa tham gia bài thi nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
