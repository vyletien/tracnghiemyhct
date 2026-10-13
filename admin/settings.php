<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$defaultContestName = 'BỆNH VIỆN YHCT & PHCN QUY NHƠN';
$defaultOgImage = '/assets/og-default.png';
$contestName = $defaultContestName;
$ogImage = $defaultOgImage;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $contestName = trim($_POST['contest_name'] ?? '');
    $ogImage = trim($_POST['og_image'] ?? $defaultOgImage);

    if ($contestName === '') {
        $error = 'Vui lòng nhập tên cuộc thi.';
    } elseif (mb_strlen($contestName) > 255) {
        $error = 'Tên cuộc thi không được dài quá 255 ký tự.';
    } elseif ($ogImage === '' || mb_strlen($ogImage) > 1000 || (!preg_match('/^https?:\\/\\//i', $ogImage) && !str_starts_with($ogImage, '/'))) {
        $error = 'Ảnh đại diện phải là URL http(s) hoặc đường dẫn bắt đầu bằng /.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value)
            VALUES ('contest_name', ?), ('og_image', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$contestName, $ogImage]);
        $success = 'Đã cập nhật thông tin cuộc thi.';
    }
} else {
    $contestName = getSiteSetting($pdo, 'contest_name', $defaultContestName);
    $ogImage = getSiteSetting($pdo, 'og_image', $defaultOgImage);
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
                    <div class="mb-3">
                        <label for="ogImage" class="form-label">URL ảnh đại diện khi chia sẻ</label>
                        <input id="ogImage" type="text" name="og_image" class="form-control" maxlength="1000" value="<?= h($ogImage) ?>" required>
                        <div class="form-text">Dùng URL https://... khi website chạy Internet. Mặc định dùng ảnh Trắc nghiệm trong thư mục assets.</div>
                        <div class="mt-3 p-2 border rounded bg-light text-center">
                            <img src="<?= h($ogImage) ?>" alt="Ảnh đại diện cuộc thi" style="max-width:100%;max-height:180px;object-fit:contain;">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu cấu hình</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
