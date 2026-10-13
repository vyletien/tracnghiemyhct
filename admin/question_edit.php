<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$id = $_GET['id'] ?? null;
$qData = null;
$answersData = [];

// Prepare Categories
$stmtCat = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $qData = $stmt->fetch();

    $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC");
    $stmtAns->execute([$id]);
    $answersData = $stmtAns->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $content = $_POST['content'] ?? '';
    $category_id = $_POST['category_id'] ?: null;
    $type = $_POST['type'] ?? 'single';
    $level = $_POST['level'] ?? '1';
    $ansContents = $_POST['ans_content'] ?? [];
    $isCorrects = $_POST['is_correct'] ?? [];

    $pdo->beginTransaction();
    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE questions SET category_id=?, content=?, type=?, level=? WHERE id=?");
            $stmt->execute([$category_id, $content, $type, $level, $id]);
            $question_id = $id;

            // Delete old answers
            $pdo->prepare("DELETE FROM answers WHERE question_id=?")->execute([$id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO questions (category_id, content, type, level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category_id, $content, $type, $level]);
            $question_id = $pdo->lastInsertId();
        }

        // Insert answers
        $stmtAns = $pdo->prepare("INSERT INTO answers (question_id, content, is_correct) VALUES (?, ?, ?)");
        foreach ($ansContents as $index => $ansContent) {
            if (trim(strip_tags($ansContent)) === '') continue; // Skip empty answers
            // in multiple choice, is_correct is an array of checked indexes
            // in single choice, is_correct is a string holding the index
            $correct = false;
            if ($type === 'single' && isset($_POST['is_correct_single']) && $_POST['is_correct_single'] == $index) {
                $correct = true;
            } elseif ($type === 'multiple' && in_array($index, $isCorrects)) {
                $correct = true;
            }
            $stmtAns->execute([$question_id, $ansContent, $correct ? 1 : 0]);
        }
        $pdo->commit();
        redirect('questions.php');
    } catch (\Exception $e) {
        $pdo->rollBack();
        $error = "Đã xảy ra lỗi: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $id ? 'Cập nhật Câu hỏi' : 'Thêm Câu hỏi mới' ?></h2>
    <a href="questions.php" class="btn btn-secondary">Quay lại</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" id="questionForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white font-weight-bold">Nội dung câu hỏi</div>
                <div class="card-body">
                    <textarea name="content" id="question_content"><?= $qData ? htmlspecialchars($qData['content']) : '' ?></textarea>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>Các phương án trả lời</span>
                    <button type="button" class="btn btn-sm btn-outline-success" id="btnAddAnswer"><i class="bi bi-plus"></i> Thêm phương án</button>
                </div>
                <div class="card-body" id="answersContainer">
                    <!-- Javascript will populate answers here -->
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white">Phân loại</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Loại câu hỏi</label>
                        <select name="type" id="question_type" class="form-select">
                            <option value="single" <?= ($qData['type'] ?? '') == 'single' ? 'selected' : '' ?>>Một đáp án đúng (Radio)</option>
                            <option value="multiple" <?= ($qData['type'] ?? '') == 'multiple' ? 'selected' : '' ?>>Nhiều đáp án đúng (Checkbox)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mức độ</label>
                        <select name="level" class="form-select">
                            <option value="1" <?= ($qData['level'] ?? '') == '1' ? 'selected' : '' ?>>Nhận biết</option>
                            <option value="2" <?= ($qData['level'] ?? '') == '2' ? 'selected' : '' ?>>Thông thường</option>
                            <option value="3" <?= ($qData['level'] ?? '') == '3' ? 'selected' : '' ?>>Thông hiểu</option>
                            <option value="4" <?= ($qData['level'] ?? '') == '4' ? 'selected' : '' ?>>Vận dụng</option>
                            <option value="5" <?= ($qData['level'] ?? '') == '5' ? 'selected' : '' ?>>Vận dụng cao</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Danh mục (Tùy chọn)</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Không chọn --</option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($qData['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Lưu Câu hỏi</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/<?= TINYMCE_KEY ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#question_content, .answer-editor',
        plugins: 'advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking save table directionality emoticons template',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code preview',
        height: 300,
        branding: false,
        promotion: false
    });

    let ansCount = 0;
    const existingAnswers = <?= json_encode($answersData) ?>;
    const qTypeSelect = document.getElementById('question_type');

    function renderAnswers() {
        const container = document.getElementById('answersContainer');
        container.innerHTML = '';
        ansCount = 0;
        
        let answersToRender = existingAnswers.length > 0 ? existingAnswers : [{content: '', is_correct: 0}, {content: '', is_correct: 0}, {content: '', is_correct: 0}, {content: '', is_correct: 0}];
        
        answersToRender.forEach(ans => addAnswer(ans.content, ans.is_correct == 1));
    }

    function addAnswer(content = '', isCorrect = false) {
        const type = qTypeSelect.value;
        const container = document.getElementById('answersContainer');
        const index = ansCount++;
        
        const inputType = type === 'single' ? 'radio' : 'checkbox';
        const inputName = type === 'single' ? 'is_correct_single' : `is_correct[]`;
        const checked = isCorrect ? 'checked' : '';
        const inputValue = index;

        const html = `
            <div class="input-group mb-3 answer-row" id="ans_row_${index}">
                <div class="input-group-text bg-white">
                    <input class="form-check-input mt-0" type="${inputType}" name="${inputName}" value="${inputValue}" ${checked} title="Đánh dấu là đáp án đúng" required>
                </div>
                <!-- Sử dụng textarea cho linh hoạt -->
                <textarea name="ans_content[${index}]" class="form-control" rows="2" placeholder="Nhập phương án..." required>${content}</textarea>
                <button type="button" class="btn btn-outline-danger" onclick="removeAnswer(${index})"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        
        // Remove required attribute from radios if it's single, but we'll handle validation manually for simplicity.
        // Actually, HTML5 requires at least one radio if required, which is good. For checkboxes, required might mean ALL checkboxes need to be checked. We remove required from them.
        const checkboxes = document.querySelectorAll('input[type="checkbox"][name="is_correct[]"]');
        checkboxes.forEach(cb => cb.removeAttribute('required'));
    }

    function removeAnswer(index) {
        document.getElementById(`ans_row_${index}`).remove();
    }

    document.getElementById('btnAddAnswer').addEventListener('click', () => addAnswer());
    
    qTypeSelect.addEventListener('change', () => {
        // Backup content 
        const textareas = document.querySelectorAll('textarea[name^="ans_content"]');
        const contents = Array.from(textareas).map(ta => ta.value);
        
        // Re-render
        const container = document.getElementById('answersContainer');
        container.innerHTML = '';
        ansCount = 0;
        
        contents.forEach(content => addAnswer(content, false));
    });

    // Init
    renderAnswers();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
