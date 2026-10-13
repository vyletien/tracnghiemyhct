<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

// Handling filter
$typeFilter = $_GET['type'] ?? '';
$levelFilter = $_GET['level'] ?? '';

$query = "SELECT q.*, c.name as category_name,
                 COALESCE(a.answer_count, 0) AS answer_count,
                 COALESCE(a.correct_count, 0) AS correct_count
          FROM questions q
          LEFT JOIN categories c ON q.category_id = c.id
          LEFT JOIN (
              SELECT question_id,
                     COUNT(*) AS answer_count,
                     SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS correct_count
              FROM answers
              GROUP BY question_id
          ) a ON a.question_id = q.id
          WHERE 1=1";
$params = [];

if ($typeFilter) {
    $query .= " AND q.type = ?";
    $params[] = $typeFilter;
}
if ($levelFilter) {
    $query .= " AND q.level = ?";
    $params[] = $levelFilter;
}
$query .= " ORDER BY q.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$questions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Quản lý Câu hỏi</h2>
    <a href="question_edit.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Thêm câu hỏi mới</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="type" class="form-select">
                    <option value="">Tất cả loại câu hỏi</option>
                    <option value="single" <?= $typeFilter === 'single' ? 'selected' : '' ?>>Một đáp án (Single Choice)</option>
                    <option value="multiple" <?= $typeFilter === 'multiple' ? 'selected' : '' ?>>Nhiều đáp án (Multiple Choice)</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="level" class="form-select">
                    <option value="">Tất cả mức độ</option>
                    <option value="1" <?= $levelFilter === '1' ? 'selected' : '' ?>>Nhận biết</option>
                    <option value="2" <?= $levelFilter === '2' ? 'selected' : '' ?>>Thông thường</option>
                    <option value="3" <?= $levelFilter === '3' ? 'selected' : '' ?>>Thông hiểu</option>
                    <option value="4" <?= $levelFilter === '4' ? 'selected' : '' ?>>Vận dụng</option>
                    <option value="5" <?= $levelFilter === '5' ? 'selected' : '' ?>>Vận dụng cao</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary">Lọc</button>
                <a href="questions.php" class="btn btn-outline-secondary">Xóa lọc</a>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th width="5%">STT</th>
                <th width="40%">Nội dung câu hỏi</th>
                <th width="13%">Loại</th>
                <th width="12%">Mức độ</th>
                <th width="15%" class="text-center">Đáp án</th>
                <th width="20%" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php $stt = count($questions); foreach ($questions as $q): ?>
            <tr>
                <td><?= $stt-- ?></td>
                <td>
                    <!-- Dùng strip_tags để hiển thị text preview ngắn gọn -->
                    <?= mb_strimwidth(strip_tags($q['content']), 0, 100, '...') ?>
                </td>
                <td>
                    <?php if($q['type'] == 'single'): ?>
                        <span class="badge bg-info text-dark">Một đáp án</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Nhiều đáp án</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                        $levels = ['1' => 'Nhận biết', '2' => 'Thông thường', '3' => 'Thông hiểu', '4' => 'Vận dụng', '5' => 'Vận dụng cao'];
                        echo $levels[$q['level']] ?? '';
                    ?>
                </td>
                <td class="text-center">
                    <span class="badge <?= (int)$q['answer_count'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                        <?= (int)$q['answer_count'] ?> lựa chọn
                    </span>
                    <div class="small text-muted mt-1"><?= (int)$q['correct_count'] ?> đáp án đúng</div>
                </td>
                <td class="text-center">
                    <a href="question_edit.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-primary" title="Sửa"><i class="bi bi-pencil"></i></a>
                    <a href="question_delete.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-danger" title="Xóa"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($questions) === 0): ?>
            <tr>
                <td colspan="6" class="text-center">Chưa có câu hỏi nào.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
