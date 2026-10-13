<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Bảng điều khiển - Quản trị viên</h2>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-question-circle"></i> Ngân hàng câu hỏi</h5>
                <p class="card-text">Quản lý kho câu hỏi, thêm sửa xóa và phân loại mức độ.</p>
                <a href="/admin/questions.php" class="btn btn-light text-primary">Quản lý ngay</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-journal-text"></i> Quản lý Đề thi</h5>
                <p class="card-text">Tạo đề thi mới, bốc thăm câu hỏi và cài đặt thời gian.</p>
                <a href="/admin/exams.php" class="btn btn-light text-success">Quản lý ngay</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-bar-chart"></i> Báo cáo & Thống kê</h5>
                <p class="card-text">Xem điểm học sinh, xuất Excel và đánh giá câu hỏi.</p>
                <a href="/admin/reports.php" class="btn btn-light text-info">Xem báo cáo</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card text-white bg-secondary h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-building"></i> Quản lý đơn vị</h5>
                <p class="card-text">Thêm, sửa, xóa các đơn vị để thí sinh lựa chọn khi vào thi.</p>
                <a href="/admin/units.php" class="btn btn-light text-secondary">Quản lý ngay</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
