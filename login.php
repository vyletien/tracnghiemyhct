<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_type']) && $_POST['login_type'] === 'guest') {
        // Guest login logic
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($fullname && $phone) {
            // Check if phone exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();

            if (!$user) {
                // Create guest user
                $username = 'guest_' . $phone;
                $stmtIns = $pdo->prepare("INSERT INTO users (username, password, fullname, phone, address, role) VALUES (?, NULL, ?, ?, ?, 'student')");
                $stmtIns->execute([$username, $fullname, $phone, $address]);
                $user_id = $pdo->lastInsertId();
                
                $stmt->execute([$phone]);
                $user = $stmt->fetch();
            } else {
                // Update info if existing
                $stmtUpd = $pdo->prepare("UPDATE users SET fullname = ?, address = ? WHERE id = ?");
                $stmtUpd->execute([$fullname, $address, $user['id']]);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            
            redirect('/index.php');
        } else {
            $error = 'Vui lòng nhập đầy đủ Họ tên và Số điện thoại!';
        }
    } else {
        // Standard login
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password IS NOT NULL AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
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
                    <form method="POST">
                        <input type="hidden" name="login_type" value="guest">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control form-control-lg bg-light border-0" placeholder="VD: Nguyễn Văn A" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control form-control-lg bg-light border-0" placeholder="VD: 0912..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Địa chỉ <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control form-control-lg bg-light border-0" placeholder="Số nhà, đường, phường/xã, tỉnh/thành..." required>
                            <div class="form-text mt-1 text-primary small"><i class="bi bi-info-circle"></i> Yêu cầu nhập đầy đủ để ban giám khảo có thể liên hệ.</div>
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
                    <form method="POST">
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
