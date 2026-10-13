<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$stmt = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC");
$exams = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Quản lý Đề thi</h2>
    <a href="exam_edit.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tạo đề thi mới</a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Tên đề thi</th>
                <th>Thời gian (Phút)</th>
                <th>Mở - Đóng</th>
                <th class="text-center">Số câu hỏi</th>
                <th class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exams as $e): 
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
                $countStmt->execute([$e['id']]);
                $qCount = $countStmt->fetchColumn();
            ?>
            <tr>
                <td><?= $e['id'] ?></td>
                <td><?= h($e['title']) ?></td>
                <td><?= $e['duration_minutes'] == 0 ? 'Không giới hạn' : $e['duration_minutes'] ?></td>
                <td>
                    Mở: <?= $e['start_time'] ? date('d/m/Y H:i', strtotime($e['start_time'])) : 'Không' ?><br>
                    Đóng: <?= $e['end_time'] ? date('d/m/Y H:i', strtotime($e['end_time'])) : 'Không' ?>
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary"><?= $qCount ?></span>
                </td>
                <td class="text-center">
                    <a href="exam_questions.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" title="Danh sách câu hỏi"><i class="bi bi-list-task"></i> Câu hỏi</a>
                    <a href="exam_edit.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary" title="Cấu hình"><i class="bi bi-gear"></i></a>
                    <a href="exam_delete.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" title="Xóa"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($exams) === 0): ?>
            <tr>
                <td colspan="6" class="text-center">Chưa có đề thi nào.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
