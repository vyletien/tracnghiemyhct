<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Social networks and browsers may append arbitrary tracking parameters.
// Ignore all query strings and always start from a clean login URL.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_SERVER['QUERY_STRING'] ?? '') !== '') {
    redirect('/login.php');
}

if (isLoggedIn()) {
    redirect('/index.php');
}

$units = $pdo->query("SELECT id, name FROM units ORDER BY name ASC")->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['login_type']) && $_POST['login_type'] === 'guest') {
        $fullname = is_string($_POST['fullname'] ?? null) ? trim($_POST['fullname']) : '';
        $phone = normalizePhone($_POST['phone'] ?? '');
        $unit_id = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);

        if (!isValidPhone($phone)) {
            $error = 'Số điện thoại không đúng định dạng!';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND role = 'student' ORDER BY id ASC LIMIT 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();

            if ($user && !$user['unit_id']) {
                $error = 'Thông tin đơn vị chưa đầy đủ. Vui lòng liên hệ Admin để cập nhật.';
            } elseif (!$user) {
                $unitStmt = $pdo->prepare("SELECT id FROM units WHERE id = ?");
                $unitStmt->execute([$unit_id ?: 0]);
                $validUnitId = $unitStmt->fetchColumn();

                if ($fullname === '' || !$unit_id) {
                    $error = 'Số điện thoại mới. Vui lòng nhập họ tên và chọn đơn vị!';
                } elseif (mb_strlen($fullname) > 100) {
                    $error = 'Họ tên không được dài quá 100 ký tự!';
                } elseif (!$validUnitId) {
                    $error = 'Đơn vị đã chọn không tồn tại. Vui lòng chọn lại!';
                } else {
                $username = 'guest_' . sha1($phone);
                $stmtIns = $pdo->prepare("INSERT INTO users (username, password, fullname, phone, unit_id, role) VALUES (?, NULL, ?, ?, ?, 'student')");
                $stmtIns->execute([$username, $fullname, $phone, $unit_id]);
                $stmt->execute([$phone]);
                $user = $stmt->fetch();
                }
            }

            if ($error === '' && $user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];

                redirect('/index.php');
            }
        }
    } else {
        // Standard login
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password IS NOT NULL AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            redirect('/admin/index.php');
        } else {
            $error = 'Tài khoản Quản trị không chính xác!';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <?php if ($error): ?>
            <div class="alert alert-danger shadow-sm border-start border-danger border-4"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white p-0 border-0">
                <ul class="nav nav-pills nav-fill" id="loginTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 fw-bold rounded-0" id="guest-tab" data-bs-toggle="pill" data-bs-target="#guest-form" type="button" role="tab">
                            <i class="bi bi-pencil-square"></i> THÍ SINH
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold rounded-0 text-muted" id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin-form" type="button" role="tab">
                            <i class="bi bi-shield-lock"></i> QUẢN TRỊ
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4 p-md-5 tab-content" id="loginTabsContent">
                <!-- Tab Thí sinh -->
                <div class="tab-pane fade show active" id="guest-form" role="tabpanel">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-primary">THÔNG TIN THÍ SINH</h4>
                        <p class="text-muted small">Nhập thông tin cá nhân để bắt đầu bài thi</p>
                    </div>
                    <form method="POST" action="/login.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="login_type" value="guest">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Số điện thoại <span class="text-danger">*</span></label>
                            <input id="participantPhone" type="tel" name="phone" class="form-control form-control-lg bg-light border-0" maxlength="20" value="<?= h($_POST['phone'] ?? '') ?>" placeholder="VD: 0912..." autocomplete="tel" inputmode="tel" required>
                            <div id="phoneLookupStatus" class="form-text">Nếu đã tham gia trước đây, chỉ cần nhập số điện thoại.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Họ và tên <span class="text-danger">*</span></label>
                            <input id="participantFullname" type="text" name="fullname" class="form-control form-control-lg bg-light border-0" maxlength="100" value="<?= h($_POST['fullname'] ?? '') ?>" placeholder="VD: Nguyễn Thị B" autocomplete="name">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Đơn vị <span class="text-danger">*</span></label>
                            <select id="participantUnit" name="unit_id" class="form-select form-select-lg bg-light border-0">
                                <option value="">-- Chọn đơn vị --</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?= (int)$unit['id'] ?>" <?= (string)($_POST['unit_id'] ?? '') === (string)$unit['id'] ? 'selected' : '' ?>><?= h($unit['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!$units): ?>
                                <div class="form-text mt-1 text-danger"><i class="bi bi-exclamation-circle"></i> Chưa có đơn vị. Vui lòng liên hệ quản trị viên.</div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">BẮT ĐẦU <i class="bi bi-chevron-right"></i></button>
                    </form>
                </div>

                <!-- Tab Admin -->
                <div class="tab-pane fade" id="admin-form" role="tabpanel">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-dark">QUẢN TRỊ VIÊN</h4>
                        <p class="text-muted small">Đăng nhập để quản lý đề thi và báo cáo</p>
                    </div>
                    <form method="POST" action="/login.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="login_type" value="member">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Username</label>
                            <input type="text" name="username" class="form-control bg-light border-0" placeholder="Tài khoản admin">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Password</label>
                            <input type="password" name="password" class="form-control bg-light border-0" placeholder="Mật khẩu">
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-bold">ĐĂNG NHẬP ADMIN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #loginTabs .nav-link { 
        color: #6c757d; 
        background: #f8f9fa; 
        border-bottom: 2px solid transparent; 
    }
    #loginTabs .nav-link.active { 
        background: #fff; 
        color: #0d6efd; 
        border-bottom: 3px solid #0d6efd; 
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('#guest-form form');
    const phoneInput = document.getElementById('participantPhone');
    const fullnameInput = document.getElementById('participantFullname');
    const unitSelect = document.getElementById('participantUnit');
    const status = document.getElementById('phoneLookupStatus');
    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    let lookupTimer;
    let populatedFromLookup = false;

    function normalizePhone(value) {
        return value.trim().replace(/[\s.\-()]+/g, '');
    }

    function unlockProfile(clearValues) {
        fullnameInput.readOnly = false;
        unitSelect.disabled = false;
        fullnameInput.required = true;
        unitSelect.required = true;
        if (clearValues) {
            fullnameInput.value = '';
            unitSelect.value = '';
        }
        populatedFromLookup = false;
    }

    phoneInput.addEventListener('input', function () {
        clearTimeout(lookupTimer);
        if (populatedFromLookup) {
            unlockProfile(true);
        }

        const phone = normalizePhone(phoneInput.value);
        if (!/^(?:\+84|0)\d{8,10}$/.test(phone)) {
            status.className = 'form-text';
            status.textContent = 'Nếu đã tham gia trước đây, chỉ cần nhập số điện thoại.';
            return;
        }

        status.className = 'form-text text-primary';
        status.textContent = 'Đang kiểm tra thông tin...';
        lookupTimer = setTimeout(async function () {
            const data = new FormData();
            data.append('csrf_token', csrfToken);
            data.append('phone', phone);

            try {
                const response = await fetch('/lookup_participant.php', { method: 'POST', body: data });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Không thể kiểm tra thông tin.');
                }
                if (normalizePhone(phoneInput.value) !== phone) {
                    return;
                }

                if (result.found) {
                    fullnameInput.value = result.fullname || '';
                    unitSelect.value = result.unit_id || '';
                    fullnameInput.readOnly = true;
                    unitSelect.disabled = true;
                    fullnameInput.required = false;
                    unitSelect.required = false;
                    populatedFromLookup = true;
                    status.className = 'form-text text-success';
                    status.textContent = 'Đã tìm thấy thông tin. Nếu thông tin chưa đúng, vui lòng liên hệ Admin để cập nhật.';
                } else {
                    unlockProfile(false);
                    status.className = 'form-text text-primary';
                    status.textContent = 'Số điện thoại mới. Vui lòng nhập họ tên và chọn đơn vị.';
                }
            } catch (error) {
                unlockProfile(false);
                status.className = 'form-text text-danger';
                status.textContent = error.message;
            }
        }, 400);
    });

    if (phoneInput.value.trim() !== '') {
        phoneInput.dispatchEvent(new Event('input'));
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
