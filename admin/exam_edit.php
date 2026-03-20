<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$id = $_GET['id'] ?? null;
$exam = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$id]);
    $exam = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    // handle empty datetime
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $duration_minutes = (int)$_POST['duration_minutes'];
    
    $shuffle_questions = isset($_POST['shuffle_questions']) ? 1 : 0;
    $shuffle_answers = isset($_POST['shuffle_answers']) ? 1 : 0;
    $show_results = isset($_POST['show_results']) ? 1 : 0;
    $enable_prediction = isset($_POST['enable_prediction']) ? 1 : 0;
    $allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE exams SET title=?, description=?, start_time=?, end_time=?, duration_minutes=?, shuffle_questions=?, shuffle_answers=?, show_results=?, enable_prediction=?, allow_multiple=? WHERE id=?");
        $stmt->execute([$title, $description, $start_time, $end_time, $duration_minutes, $shuffle_questions, $shuffle_answers, $show_results, $enable_prediction, $allow_multiple, $id]);
        redirect("exam_questions.php?id=$id");
    } else {
        $stmt = $pdo->prepare("INSERT INTO exams (title, description, start_time, end_time, duration_minutes, shuffle_questions, shuffle_answers, show_results, enable_prediction, allow_multiple) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $start_time, $end_time, $duration_minutes, $shuffle_questions, $shuffle_answers, $show_results, $enable_prediction, $allow_multiple]);
        $new_id = $pdo->lastInsertId();
        redirect("exam_questions.php?id=$new_id");
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $id ? 'Cấu hình Đề thi' : 'Tạo Đề thi mới' ?></h2>
    <a href="exams.php" class="btn btn-secondary">Quay lại</a>
</div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Tên đề thi *</label>
                        <input type="text" name="title" class="form-control" value="<?= h($exam['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả ngắn / Hướng dẫn thi</label>
                        <textarea name="description" class="form-control" rows="3"><?= h($exam['description'] ?? '') ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Thời gian làm bài (Phút) *</label>
                            <input type="number" name="duration_minutes" class="form-control" min="1" value="<?= $exam['duration_minutes'] ?? 60 ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Thời gian Mở đề</label>
                            <input type="datetime-local" name="start_time" class="form-control" value="<?= $exam['start_time'] ?? '' ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Thời gian Đóng đề</label>
                            <input type="datetime-local" name="end_time" class="form-control" value="<?= $exam['end_time'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3 border-bottom pb-2">Tùy chọn hiển thị</h5>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="shuffle_questions" name="shuffle_questions" <?= ($exam['shuffle_questions'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="shuffle_questions">Đảo vị trí câu hỏi (Trộn đề)</label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="shuffle_answers" name="shuffle_answers" <?= ($exam['shuffle_answers'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="shuffle_answers">Đảo vị trí đáp án (Chống gian lận)</label>
                    </div>
                    
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="show_results" name="show_results" <?= ($exam['show_results'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_results">Hiển thị điểm số ngay sau khi nộp bài</label>
                    </div>

                    <div class="form-check form-switch mb-4 p-3 bg-light rounded border border-primary border-opacity-25">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="enable_prediction" name="enable_prediction" <?= ($exam['enable_prediction'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-primary" for="enable_prediction">Yêu cầu Dự đoán số người tham gia (Cuối đề thi)</label>
                    </div>

                    <div class="form-check form-switch mb-4 p-3 bg-light rounded border border-success border-opacity-25">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="allow_multiple" name="allow_multiple" <?= ($exam['allow_multiple'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-success" for="allow_multiple">Cho phép thi nhiều lần (Thí sinh có thể thi lại sau khi đã nộp bài)</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Lưu cấu hình & Chuyển tới chọn Câu hỏi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
