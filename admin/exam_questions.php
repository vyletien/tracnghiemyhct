<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$id = $_GET['id'] ?? null;
if (!$id) redirect('exams.php');

$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$id]);
$exam = $stmt->fetch();
if (!$exam) redirect('exams.php');

// Handle Generative POST
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    verify_csrf();
    $cat_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $l1 = (int)$_POST['l1'];
    $l2 = (int)$_POST['l2'];
    $l3 = (int)$_POST['l3'];
    $l4 = (int)$_POST['l4'];
    $l5 = (int)$_POST['l5'];

    $added = 0;
    $levels = [1 => $l1, 2 => $l2, 3 => $l3, 4 => $l4, 5 => $l5];
    
    foreach ($levels as $level => $limit) {
        if ($limit > 0) {
            // Select random questions not already in exam
            $qQuery = "SELECT id FROM questions WHERE level = ? AND id NOT IN (SELECT question_id FROM exam_questions WHERE exam_id = ?)";
            $qParams = [(string)$level, $id];
            if ($cat_id) {
                $qQuery .= " AND category_id = ?";
                $qParams[] = $cat_id;
            }
            // ORDER BY RAND() is okay for small-medium sets. $limit is already cast to (int) at lines 19-22.
            $qQuery .= " ORDER BY RAND() LIMIT " . (int)$limit;
            
            $stmtQ = $pdo->prepare($qQuery);
            $stmtQ->execute($qParams);
            $randomQs = $stmtQ->fetchAll();
            
            // Insert
            $insertStmt = $pdo->prepare("INSERT IGNORE INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
            foreach ($randomQs as $rq) {
                $insertStmt->execute([$id, $rq['id']]);
                $added++;
            }
        }
    }
    $msg = "Đã lấy ngẫu nhiên và thêm $added câu hỏi vào đề thi.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    verify_csrf();
    $qId = $_POST['question_id'];
    $pdo->prepare("DELETE FROM exam_questions WHERE exam_id=? AND question_id=?")->execute([$id, $qId]);
}


// Fetch current questions
$stmtEq = $pdo->prepare("SELECT q.*, c.name as category_name FROM questions q JOIN exam_questions eq ON q.id = eq.question_id LEFT JOIN categories c ON q.category_id = c.id WHERE eq.exam_id = ?");
$stmtEq->execute([$id]);
$questions = $stmtEq->fetchAll();

// Categories
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Đề thi: <?= h($exam['title']) ?></h2>
    <a href="exams.php" class="btn btn-secondary">Quay lại danh sách</a>
</div>

<?php if ($msg): ?>
<div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Trình tạo câu hỏi ngẫu nhiên</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="generate">
                    <div class="mb-3">
                        <label class="form-label">Chọn Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach($cats as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <label class="form-label fw-bold">Số lượng theo mức độ:</label>
                    <div class="row mb-2">
                        <div class="col-8">Nhận biết</div>
                        <div class="col-4"><input type="number" name="l1" class="form-control form-control-sm" min="0" value="0"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-8">Thông hiểu</div>
                        <div class="col-4"><input type="number" name="l2" class="form-control form-control-sm" min="0" value="0"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-8">Vận dụng</div>
                        <div class="col-4"><input type="number" name="l3" class="form-control form-control-sm" min="0" value="0"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-8">Vận dụng cao</div>
                        <div class="col-4"><input type="number" name="l4" class="form-control form-control-sm" min="0" value="0"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-8">Thông thường</div>
                        <div class="col-4"><input type="number" name="l5" class="form-control form-control-sm" min="0" value="0"></div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mt-2"><i class="bi bi-shuffle"></i> Tự động bốc câu hỏi</button>
                    <small class="text-muted d-block mt-2 text-center">Các câu hỏi đã có trong đề sẽ không bị lặp lại.</small>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách <?= count($questions) ?> câu hỏi trong đề</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">ID</th>
                            <th>Nội dung</th>
                            <th width="20%">Mức độ</th>
                            <th width="10%">Bỏ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?= $q['id'] ?></td>
                            <td><?= mb_strimwidth(strip_tags($q['content']), 0, 80, '...') ?></td>
                            <td>Mức <?= $q['level'] ?></td>
                            <td class="text-center">
                                <form method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Loại khỏi đề"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($questions) == 0): ?>
                        <tr><td colspan="4" class="text-center p-3 text-muted">Đề thi chưa có câu hỏi nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
