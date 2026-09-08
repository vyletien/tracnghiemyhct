<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkLogin();

// Lấy danh sách toàn bộ đề thi
$stmt = $pdo->query("
    SELECT * FROM exams 
    ORDER BY created_at DESC
");
$exams = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<h2 class="mb-4">Danh sách Đề thi</h2>

<div class="row">
    <?php if (count($exams) > 0): 
        $user_id = $_SESSION['user_id'];
    ?>
        <?php foreach ($exams as $exam): 
            // Check for user's attempts on this exam
            $stmtAtt = $pdo->prepare("SELECT * FROM exam_attempts WHERE user_id = ? AND exam_id = ? ORDER BY id DESC LIMIT 1");
            $stmtAtt->execute([$user_id, $exam['id']]);
            $latest_attempt = $stmtAtt->fetch();
            $attempt_mode = isset($exam['attempt_mode']) ? (int)$exam['attempt_mode'] : (($exam['allow_multiple'] ?? 0) ? 1 : 0);
            $has_attempt_today = false;
            if ($attempt_mode === 2) {
                $stmtToday = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts WHERE user_id = ? AND exam_id = ? AND is_submitted = 1 AND end_time >= ? AND end_time < ?");
                $stmtToday->execute([$user_id, $exam['id'], date('Y-m-d 00:00:00'), date('Y-m-d 00:00:00', strtotime('+1 day'))]);
                $has_attempt_today = (bool)$stmtToday->fetchColumn();
            }
            
            $has_finished = $latest_attempt && $latest_attempt['is_submitted'];
            $is_taking = $latest_attempt && !$latest_attempt['is_submitted'];
            
            $now = time();
            $is_open_yet = true;
            $has_ended = false;
            
            if ($exam['start_time'] && strtotime($exam['start_time']) > $now) {
                $is_open_yet = false;
            }
            if ($exam['end_time'] && strtotime($exam['end_time']) < $now) {
                $has_ended = true;
            }
        ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0 border-top border-primary border-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title text-primary"><?= h($exam['title']) ?></h5>
                            <?php if ($has_finished): ?>
                                <span class="badge bg-success">Đã hoàn thành</span>
                            <?php elseif ($is_taking): ?>
                                <span class="badge bg-warning text-dark">Đang thi</span>
                            <?php endif; ?>
                        </div>
                        <p class="card-text text-muted"><?= h($exam['description']) ?></p>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-clock"></i> Thời gian: <strong><?= $exam['duration_minutes'] == 0 ? 'Không giới hạn' : $exam['duration_minutes'] . ' phút' ?></strong></li>
                            <?php if ($exam['start_time']): ?>
                            <li><i class="bi bi-calendar-event"></i> Mở: <?= date('d/m/Y H:i', strtotime($exam['start_time'])) ?></li>
                            <?php endif; ?>
                            <?php if ($exam['end_time']): ?>
                            <li><i class="bi bi-calendar-x"></i> Đóng: <?= date('d/m/Y H:i', strtotime($exam['end_time'])) ?></li>
                            <?php endif; ?>
                            <li><i class="bi bi-arrow-repeat"></i> Chế độ: <?= $attempt_mode === 2 ? '<span class="text-warning">Tối đa 1 lần/ngày</span>' : ($attempt_mode === 1 ? '<span class="text-success">Thi nhiều lần</span>' : '<span class="text-secondary">Thi 1 lần duy nhất</span>') ?></li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-grid gap-2">
                        <?php if (!$is_open_yet): ?>
                            <button class="btn btn-secondary w-100 opacity-75" disabled><i class="bi bi-clock-history"></i> Chưa tới thời điểm thi</button>
                        <?php elseif ($has_ended): ?>
                            <button class="btn btn-secondary w-100 opacity-75" disabled><i class="bi bi-x-circle"></i> Đã kết thúc</button>
                            <?php if ($has_finished): ?>
                                <a href="/history.php" class="btn btn-light w-100 border text-muted small mt-2">Xem lịch sử thi</a>
                            <?php endif; ?>
                        <?php elseif ($is_taking): ?>
                            <a href="/take_exam.php?id=<?= $exam['id'] ?>" class="btn btn-warning w-100">Tiếp tục thi</a>
                        <?php elseif ($has_finished): ?>
                            <?php if ($attempt_mode > 0 && !$has_attempt_today): ?>
                                <a href="/take_exam.php?id=<?= $exam['id'] ?>" class="btn btn-outline-primary w-100">Thi lại</a>
                            <?php elseif ($attempt_mode === 2 && $has_attempt_today): ?>
                                <button class="btn btn-outline-secondary w-100" disabled>Đã thi hôm nay</button>
                            <?php endif; ?>
                            <a href="/history.php" class="btn btn-light w-100 border text-muted small">Xem lịch sử thi</a>
                        <?php else: ?>
                            <a href="/take_exam.php?id=<?= $exam['id'] ?>" class="btn btn-primary w-100">Vào thi</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">Hiện tại không có đề thi nào sắp diễn ra.</div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
