<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$error = '';
$editUnit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($action === 'delete' && $id) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE unit_id = ?");
        $countStmt->execute([$id]);

        if ((int)$countStmt->fetchColumn() > 0) {
            redirect('units.php?error=in_use');
        }

        $deleteStmt = $pdo->prepare("DELETE FROM units WHERE id = ?");
        $deleteStmt->execute([$id]);
        redirect('units.php?success=deleted');
    }

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $error = 'Vui lòng nhập tên đơn vị.';
        } elseif (mb_strlen($name) > 255) {
            $error = 'Tên đơn vị không được dài quá 255 ký tự.';
        } else {
            try {
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE units SET name = ? WHERE id = ?");
                    $stmt->execute([$name, $id]);
                    redirect('units.php?success=updated');
                }

                $stmt = $pdo->prepare("INSERT INTO units (name) VALUES (?)");
                $stmt->execute([$name]);
                redirect('units.php?success=created');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = 'Tên đơn vị đã tồn tại.';
                } else {
                    throw $e;
                }
            }
        }

        $editUnit = ['id' => $id, 'name' => $name];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare("SELECT id, name FROM units WHERE id = ?");
        $stmt->execute([$editId]);
        $editUnit = $stmt->fetch();
    }
}

$units = $pdo->query("SELECT un.id, un.name, un.created_at, COUNT(u.id) AS participant_count
    FROM units un
    LEFT JOIN users u ON u.unit_id = un.id AND u.role = 'student'
    GROUP BY un.id, un.name, un.created_at
    ORDER BY un.name ASC")->fetchAll();

$successMessages = [
    'created' => 'Đã thêm đơn vị.',
    'updated' => 'Đã cập nhật đơn vị.',
    'deleted' => 'Đã xóa đơn vị.'
];
$success = $successMessages[$_GET['success'] ?? ''] ?? '';
if (($_GET['error'] ?? '') === 'in_use') {
    $error = 'Không thể xóa đơn vị đã có thí sinh. Bạn có thể đổi tên đơn vị nếu cần.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Quản lý đơn vị</h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold"><?= $editUnit ? 'Sửa đơn vị' : 'Thêm đơn vị' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($editUnit['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label for="unitName" class="form-label">Tên đơn vị <span class="text-danger">*</span></label>
                        <input id="unitName" type="text" name="name" class="form-control" maxlength="255" value="<?= h($editUnit['name'] ?? '') ?>" required autofocus>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu</button>
                    <?php if ($editUnit): ?>
                        <a class="btn btn-light border" href="units.php">Hủy</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Danh sách đơn vị (<?= count($units) ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px">STT</th>
                            <th>Tên đơn vị</th>
                            <th class="text-center">Thí sinh</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $index => $unit): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= h($unit['name']) ?></td>
                                <td class="text-center"><?= (int)$unit['participant_count'] ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="units.php?edit=<?= (int)$unit['id'] ?>" title="Sửa"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa đơn vị này?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$unit['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Xóa" <?= (int)$unit['participant_count'] > 0 ? 'disabled' : '' ?>><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$units): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Chưa có đơn vị nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
