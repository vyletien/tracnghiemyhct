<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) {
    // List exams with summary
    $stmt = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC");
    $exams = $stmt->fetchAll();
} else {
    // Exam specific report
    $stmtDate = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmtDate->execute([$exam_id]);
    $exam = $stmtDate->fetch();
    
    // Stats
    $stmtCount = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM exam_attempts WHERE exam_id = ? AND is_submitted = 1");
    $stmtCount->execute([$exam_id]);
    $done_attempts_count = $stmtCount->fetchColumn();

    $stmtUnique = $pdo->prepare("SELECT COUNT(DISTINCT u.phone) FROM exam_attempts ea JOIN users u ON ea.user_id = u.id WHERE ea.exam_id = ? AND ea.is_submitted = 1");
    $stmtUnique->execute([$exam_id]);
    $unique_participants = $stmtUnique->fetchColumn();

    $stmtActual = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM exam_attempts WHERE exam_id = ? AND is_submitted = 1");
    $stmtActual->execute([$exam_id]);
    $actual_participants_for_ranking = $stmtActual->fetchColumn();

    // Fetch attempts with refined ranking
    $stmtAtt = $pdo->prepare("
        SELECT ea.*, u.fullname, u.username, u.phone, u.address
        FROM exam_attempts ea 
        JOIN users u ON ea.user_id = u.id 
        WHERE ea.exam_id = ? AND ea.is_submitted = 1
        ORDER BY 
            ea.score DESC, 
            (ea.prediction_value IS NULL) ASC,
            ABS(CAST(COALESCE(ea.prediction_value, 0) AS SIGNED) - ?) ASC, 
            ea.start_time ASC
    ");
    $stmtAtt->execute([$exam_id, $actual_participants_for_ranking]);
    $attempts = $stmtAtt->fetchAll();
    
    // Stats for other cards
    $total = count($attempts);
    $max = 0; $min = 10; $sum = 0;
    foreach ($attempts as $a) {
        if ($a['score'] > $max) $max = $a['score'];
        if ($a['score'] < $min) $min = $a['score'];
        $sum += $a['score'];
    }
    $avg = $total > 0 ? round($sum / $total, 2) : 0;
    if ($total == 0) $min = 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$exam_id): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Báo cáo & Thống kê</h2>
    </div>
    
    <div class="row">
        <?php foreach ($exams as $e): 
            $cStmt = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND is_submitted = 1");
            $cStmt->execute([$e['id']]);
            $doneCount = $cStmt->fetchColumn();
        ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= h($e['title']) ?></h5>
                    <p class="mb-2">Số lượng nộp bài: <strong><?= $doneCount ?></strong></p>
                    <a href="reports.php?exam_id=<?= $e['id'] ?>" class="btn btn-outline-info w-100">Xem chi tiết báo cáo</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Báo cáo: <?= h($exam['title']) ?></h2>
        <div>
            <a href="reports.php" class="btn btn-secondary me-2">Quay lại</a>
            <a href="export_excel.php?exam_id=<?= $exam_id ?>" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Xuất Excel</a>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-info text-white text-center p-3 h-100">
                <h5>Số bài nộp</h5>
                <h2><?= $total ?></h2>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-dark text-white text-center p-3 h-100">
                <h5>Số người dự thi (theo SĐT)</h5>
                <h2><?= $unique_participants ?></h2>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white text-center p-3 h-100">
                <h5>Điểm cao nhất</h5>
                <h2><?= number_format($max, 2) ?></h2>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card bg-warning text-dark text-center p-3 h-100">
                <h5>Điểm thấp nhất</h5>
                <h2><?= number_format($min, 2) ?></h2>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card bg-primary text-white text-center p-3 h-100">
                <h5>Điểm trung bình</h5>
                <h2><?= number_format($avg, 2) ?></h2>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">Danh sách Điểm Thí sinh</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th>Câu dự đoán</th>
                        <th>Thời gian Bắt đầu</th>
                        <th>Giây làm bài</th>
                        <th class="text-center text-danger">Điểm số</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $stt=1; foreach($attempts as $a): 
                        $t1 = strtotime($a['start_time']);
                        $t2 = strtotime($a['end_time']);
                        $secs = ($t2 - $t1);
                    ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><?= h($a['fullname']) ?></td>
                        <td><?= h($a['phone'] ?: 'N/A') ?></td>
                        <td><?= h($a['address'] ?: 'N/A') ?></td>
                        <td><?= h($a['prediction_value'] ?? '-') ?></td>
                        <td><?= date('d/m/Y H:i:s', $t1) ?></td>
                        <td><?= $secs ?> giây</td>
                        <td class="text-center fw-bold fs-5 text-danger"><?= number_format($a['score'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($attempts) === 0): ?>
                    <tr><td colspan="8" class="text-center p-3">Chưa có bài làm nào nộp trên hệ thống.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
