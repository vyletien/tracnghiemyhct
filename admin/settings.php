<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$defaultContestName = 'BỆNH VIỆN YHCT & PHCN QUY NHƠN';
$contestName = $defaultContestName;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $contestName = trim($_POST['contest_name'] ?? '');

    if ($contestName === '') {
        $error = 'Vui lòng nhập tên cuộc thi.';
    } elseif (mb_strlen($contestName) > 255) {
        $error = 'Tên cuộc thi không được dài quá 255 ký tự.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value)
            VALUES ('contest_name', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$contestName]);
        $success = 'Đã cập nhật tên cuộc thi.';
    }
} else {
    $contestName = getSiteSetting($pdo, 'contest_name', $defaultContestName);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Cấu hình cuộc thi</h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Thông tin hiển thị</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="contestName" class="form-label">Tên cuộc thi <span class="text-danger">*</span></label>
                        <input id="contestName" type="text" name="contest_name" class="form-control form-control-lg" maxlength="255" value="<?= h($contestName) ?>" required autofocus>
                        <div class="form-text">Tên này được dùng trên thanh tiêu đề, tiêu đề trình duyệt và nội dung chia sẻ mạng xã hội.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu cấu hình</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
