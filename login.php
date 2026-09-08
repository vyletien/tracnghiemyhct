<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['login_type']) && $_POST['login_type'] === 'guest') {
        // Guest login logic
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address_detail = trim($_POST['address_detail'] ?? '');
        $ward = trim($_POST['ward'] ?? '');
        $address = $address_detail . ' - ' . $ward;

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

            session_regenerate_id(true);
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
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="login_type" value="guest">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control form-control-lg bg-light border-0" placeholder="VD: Nguyễn Văn A" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control form-control-lg bg-light border-0" placeholder="VD: 0912..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Địa chỉ (Số nhà, tên đường, thôn, xóm, làng...) <span class="text-danger">*</span></label>
                            <input type="text" name="address_detail" class="form-control form-control-lg bg-light border-0" placeholder="VD: Số 123 Nguyễn Huệ..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Xã/Phường <span class="text-danger">*</span></label>
                            <select name="ward" class="form-select form-select-lg bg-light border-0" required>
                                <option value="">-- Chọn Xã/Phường --</option>
                                <?php
                                $wards = [
                                    "Ban Phụ nữ Công an tỉnh", "Hội Phụ nữ cơ quan BCH Quân sự tỉnh",
                                    "Phường An Bình", "Phường An Khê", "Phường An Nhơn", "Phường An Nhơn Bắc",
                                    "Phường An Nhơn Đông", "Phường An Nhơn Nam", "Phường An Phú", "Phường Ayun Pa",
                                    "Phường Bình Định", "Phường Bồng Sơn", "Phường Diên Hồng", "Phường Hoài Nhơn",
                                    "Phường Hoài Nhơn Bắc", "Phường Hoài Nhơn Đông", "Phường Hoài Nhơn Nam", "Phường Hoài Nhơn Tây",
                                    "Phường Hội Phú", "Phường Pleiku", "Phường Quy Nhơn", "Phường Quy Nhơn Bắc",
                                    "Phường Quy Nhơn Đông", "Phường Quy Nhơn Nam", "Phường Quy Nhơn Tây", "Phường Tam Quan",
                                    "Phường Thống Nhất", "Xã Albá", "Xã An Hoà", "Xã An Lão", "Xã An Lương",
                                    "Xã An Nhơn Tây", "Xã An Toàn", "Xã An Vinh", "Xã Ayun", "Xã Ân Hảo",
                                    "Xã Ân Tường", "Xã Bàu Cạn", "Xã Biển Hồ", "Xã Bình An", "Xã Bình Dương",
                                    "Xã Bình Hiệp", "Xã Bình Khê", "Xã Bình Phú", "Xã Bờ Ngoong", "Xã Canh Liên",
                                    "Xã Canh Vinh", "Xã Cát Tiến", "Xã Cửu An", "Xã Chơ Long", "Xã Chư A Thai",
                                    "Xã Chư Krey", "Xã Chư Păh", "Xã Chư Prông", "Xã Chư Pưh", "Xã Chư Sê",
                                    "Xã Đak Đoa", "Xã Đak Pơ", "Xã Đak Rong", "Xã Đak Sơmei", "Xã Đăk Song",
                                    "Xã Đề Gi", "Xã Đức Cơ", "Xã Gào", "Xã Hoà Hội", "Xã Hoài Ân",
                                    "Xã Hội Sơn", "Xã Hra", "Xã Ia Băng", "Xã Ia Boòng", "Xã Ia Chia",
                                    "Xã Ia Dom", "Xã Ia Dơk", "Xã Ia Dreh", "Xã Ia Grai", "Xã Ia Hiao",
                                    "Xã Ia Hrú", "Xã Ia Hrung", "Xã Ia Ko", "Xã Ia Krái", "Xã Ia Krêl",
                                    "Xã Ia Khươl", "Xã Ia Lâu", "Xã Ia Le", "Xã Ia Ly", "Xã Ia Mơ",
                                    "Xã Ia Nan", "Xã Ia O", "Xã Ia Pa", "Xã Ia Pia", "Xã Ia Pnôn",
                                    "Xã Ia Púch", "Xã Ia Phí", "Xã Ia Rbol", "Xã Ia Rsai", "Xã Ia Sao",
                                    "Xã Ia Tôr", "Xã Ia Tul", "Xã Kbang", "Xã KDang", "Xã Kim Sơn",
                                    "Xã Kon Chiêng", "Xã Kon Gang", "Xã Kông Bơ La", "Xã Kông Chro", "Xã Krong",
                                    "Xã Lơ Pang", "Xã Mang Yang", "Xã Ngô Mây", "Xã Nhơn Châu", "Xã Pờ Tó",
                                    "Xã Phù Cát", "Xã Phù Mỹ", "Xã Phù Mỹ Bắc", "Xã Phù Mỹ Đông", "Xã Phù Mỹ Nam",
                                    "Xã Phù Mỹ Tây", "Xã Phú Túc", "Xã Phú Thiện", "Xã Sơn Lang", "Xã SRó",
                                    "Xã Tây Sơn", "Xã Tơ Tung", "Xã Tuy Phước", "Xã Tuy Phước Bắc", "Xã Tuy Phước Đông",
                                    "Xã Tuy Phước Tây", "Xã Uar", "Xã Vạn Đức", "Xã Vân Canh", "Xã Vĩnh Quang",
                                    "Xã Vĩnh Sơn", "Xã Vĩnh Thạnh", "Xã Vĩnh Thịnh", "Xã Xuân An", "Xã Ya Hội", "Xã Ya Ma"
                                ];
                                foreach ($wards as $w) {
                                    echo "<option value=\"" . h($w) . "\">" . h($w) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text mt-1 text-primary small"><i class="bi bi-info-circle"></i> Yêu cầu chọn Xã/Phường để ban giám khảo có thể liên hệ.</div>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
