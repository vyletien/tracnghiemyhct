<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$units = $pdo->query("SELECT id, name FROM units ORDER BY name ASC")->fetchAll();
$error = '';
$success = ($_GET['success'] ?? '') === 'updated' ? 'Đã cập nhật thông tin thí sinh.' : '';
$editParticipant = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $fullname = is_string($_POST['fullname'] ?? null) ? trim($_POST['fullname']) : '';
    $phone = normalizePhone($_POST['phone'] ?? '');
    $unitId = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);

    $unitStmt = $pdo->prepare("SELECT id FROM units WHERE id = ?");
    $unitStmt->execute([$unitId ?: 0]);
    $validUnitId = $unitStmt->fetchColumn();

    if (!$id) {
        $error = 'Không xác định được thí sinh cần cập nhật.';
    } elseif ($fullname === '' || mb_strlen($fullname) > 100) {
        $error = 'Họ tên không hợp lệ hoặc dài quá 100 ký tự.';
    } elseif (!isValidPhone($phone)) {
        $error = 'Số điện thoại không đúng định dạng.';
    } elseif (!$validUnitId) {
        $error = 'Đơn vị đã chọn không tồn tại.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, phone = ?, unit_id = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$fullname, $phone, $unitId, $id]);
            if ($stmt->rowCount() === 0) {
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
                $checkStmt->execute([$id]);
                if (!$checkStmt->fetchColumn()) {
                    $error = 'Không tìm thấy thí sinh.';
                } else {
                    redirect('participants.php?success=updated');
                }
            } else {
                redirect('participants.php?success=updated');
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Số điện thoại này đã được sử dụng bởi thí sinh khác.';
            } else {
                throw $e;
            }
        }
    }

    $editParticipant = ['id' => $id, 'fullname' => $fullname, 'phone' => $phone, 'unit_id' => $unitId];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare("SELECT id, fullname, phone, unit_id FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$editId]);
        $editParticipant = $stmt->fetch();
    }
}

$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$sql = "SELECT u.id, u.fullname, u.phone, u.unit_id, u.created_at, un.name AS unit_name
    FROM users u
    LEFT JOIN units un ON un.id = u.unit_id
    WHERE u.role = 'student'";
$params = [];
if ($search !== '') {
    $sql .= " AND (u.fullname LIKE ? OR u.phone LIKE ? OR un.name LIKE ?)";
    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}
$sql .= " ORDER BY u.created_at DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$participants = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Quản lý thí sinh</h2>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<?php if ($editParticipant): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Sửa thông tin thí sinh</div>
        <div class="card-body">
            <form method="post" class="row g-3 align-items-end">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$editParticipant['id'] ?>">
                <div class="col-md-4">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="fullname" class="form-control" maxlength="100" value="<?= h($editParticipant['fullname']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="tel" name="phone" class="form-control" maxlength="20" value="<?= h($editParticipant['phone']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Đơn vị</label>
                    <select name="unit_id" class="form-select" required>
                        <option value="">-- Chọn đơn vị --</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= (int)$unit['id'] ?>" <?= (string)$editParticipant['unit_id'] === (string)$unit['id'] ? 'selected' : '' ?>><?= h($unit['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Lưu</button>
                    <a href="participants.php" class="btn btn-light border">Hủy</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <form method="get" class="row g-2">
            <div class="col-md-10">
                <input type="search" name="q" class="form-control" value="<?= h($search) ?>" placeholder="Tìm theo họ tên, số điện thoại hoặc đơn vị">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Họ và tên</th><th>Số điện thoại</th><th>Đơn vị</th><th>Ngày đăng ký</th><th class="text-end">Thao tác</th></tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $participant): ?>
                    <tr>
                        <td><?= h($participant['fullname']) ?></td>
                        <td><?= h($participant['phone']) ?></td>
                        <td><?= h($participant['unit_name'] ?: 'Chưa xác định') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($participant['created_at'])) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="participants.php?edit=<?= (int)$participant['id'] ?>"><i class="bi bi-pencil"></i> Sửa</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$participants): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Không tìm thấy thí sinh.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($participants) === 200): ?>
        <div class="card-footer text-muted small">Đang hiển thị tối đa 200 thí sinh. Hãy dùng ô tìm kiếm để thu hẹp kết quả.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
