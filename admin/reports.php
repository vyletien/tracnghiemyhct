<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) {
    // List exams with summary
    $stmt = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC");
    $exams = $stmt->fetchAll();
} else {
    // Exam specific report
    $stmtDate = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmtDate->execute([$exam_id]);
    $exam = $stmtDate->fetch();
    
    // Stats
    $stmtCount = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM exam_attempts WHERE exam_id = ? AND is_submitted = 1");
    $stmtCount->execute([$exam_id]);
    $done_attempts_count = $stmtCount->fetchColumn();

    $stmtUnique = $pdo->prepare("SELECT COUNT(DISTINCT u.phone) FROM exam_attempts ea JOIN users u ON ea.user_id = u.id WHERE ea.exam_id = ? AND ea.is_submitted = 1");
    $stmtUnique->execute([$exam_id]);
    $unique_participants = $stmtUnique->fetchColumn();

    $stmtActual = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM exam_attempts WHERE exam_id = ? AND is_submitted = 1");
    $stmtActual->execute([$exam_id]);
    $actual_participants_for_ranking = $stmtActual->fetchColumn();

    // Fetch attempts with refined ranking - LIMIT 200 for display
    $stmtAtt = $pdo->prepare("
        SELECT ea.*, u.fullname, u.username, u.phone, u.address
        FROM exam_attempts ea 
        JOIN users u ON ea.user_id = u.id 
        WHERE ea.exam_id = ? AND ea.is_submitted = 1
        ORDER BY 
            ea.score DESC, 
            (ea.prediction_value IS NULL) ASC,
            (CAST(COALESCE(ea.prediction_value, 0) AS SIGNED) >= ?) DESC,
            ABS(CAST(COALESCE(ea.prediction_value, 0) AS SIGNED) - ?) ASC, 
            ea.start_time ASC
        LIMIT 200
    ");
    $stmtAtt->execute([$exam_id, $actual_participants_for_ranking, $actual_participants_for_ranking]);
    $attempts = $stmtAtt->fetchAll();
    
    // Stats for other cards (Calculate from ALL attempts, not just the limited 200)
    $stmtStats = $pdo->prepare("SELECT COUNT(*) as total, MAX(score) as max_score, MIN(score) as min_score, AVG(score) as avg_score FROM exam_attempts WHERE exam_id = ? AND is_submitted = 1");
    $stmtStats->execute([$exam_id]);
    $rowStats = $stmtStats->fetch();
    
    $total = $rowStats['total'] ?? 0;
    $max = $rowStats['max_score'] ?? 0;
    $min = $rowStats['min_score'] ?? 0;
    $avg = isset($rowStats['avg_score']) ? round($rowStats['avg_score'], 2) : 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$exam_id): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Báo cáo & Thống kê</h2>
    </div>
    
    <div class="row">
        <?php foreach ($exams as $e): 
            $cStmt = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND is_submitted = 1");
            $cStmt->execute([$e['id']]);
            $doneCount = $cStmt->fetchColumn();
        ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= h($e['title']) ?></h5>
                    <p class="mb-2">Số lượng nộp bài: <strong><?= $doneCount ?></strong></p>
                    <a href="reports.php?exam_id=<?= $e['id'] ?>" class="btn btn-outline-info w-100">Xem chi tiết báo cáo</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Báo cáo: <?= h($exam['title']) ?></h2>
        <div>
            <a href="reports.php" class="btn btn-secondary me-2">Quay lại</a>
            <button onclick="exportExcel(<?= $exam_id ?>)" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Xuất Excel</button>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-info text-white text-center p-3 h-100">
                <h5>Số bài nộp</h5>
                <h2><?= $total ?></h2>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-dark text-white text-center p-3 h-100">
                <h5>Số người dự thi (theo SĐT)</h5>
                <h2><?= $unique_participants ?></h2>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white text-center p-3 h-100">
                <h5>Điểm cao nhất</h5>
                <h2><?= number_format($max, 2) ?></h2>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card bg-warning text-dark text-center p-3 h-100">
                <h5>Điểm thấp nhất</h5>
                <h2><?= number_format($min, 2) ?></h2>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card bg-primary text-white text-center p-3 h-100">
                <h5>Điểm trung bình</h5>
                <h2><?= number_format($avg, 2) ?></h2>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Danh sách Điểm Thí sinh (Hiển thị tối đa 200 dòng)</span>
            <?php if ($total > 200): ?>
                <span class="badge bg-warning text-dark">Lưu ý: Chỉ hiển thị 200/<?= $total ?> kết quả. Vui lòng xuất Excel để xem đầy đủ.</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th>Câu dự đoán</th>
                        <th>Thời gian Bắt đầu</th>
                        <th>Giây làm bài</th>
                        <th class="text-center text-danger">Điểm số</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $stt=1; foreach($attempts as $a): 
                        $t1 = strtotime($a['start_time']);
                        $t2 = strtotime($a['end_time']);
                        $secs = ($t2 - $t1);
                    ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><?= h($a['fullname']) ?></td>
                        <td><?= h($a['phone'] ?: 'N/A') ?></td>
                        <td><?= h($a['address'] ?: 'N/A') ?></td>
                        <td><?= h($a['prediction_value'] ?? '-') ?></td>
                        <td><?= date('d/m/Y H:i:s', $t1) ?></td>
                        <td><?= $secs ?> giây</td>
                        <td class="text-center fw-bold fs-5 text-danger"><?= number_format($a['score'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($attempts) === 0): ?>
                    <tr><td colspan="8" class="text-center p-3">Chưa có bài làm nào nộp trên hệ thống.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
async function exportExcel(examId) {
    Swal.fire({
        title: 'Đang xuất dữ liệu...',
        text: 'Dữ liệu đang được biên dịch. Vui lòng không đóng trang này!',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('export_excel.php?exam_id=' + examId, {
            method: 'GET'
        });

        if (!response.ok) {
            throw new Error('Lỗi tải file');
        }

        const blob = await response.blob();
        
        // Kiểm tra lỗi dưới dạng txt/html thay vì file XLS
        if (blob.type.includes("text/html") && blob.size < 1000) {
            const text = await blob.text();
            Swal.fire('Lỗi', text, 'error');
            return;
        }

        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        
        let filename = 'BaoCao_DiemThi.xls';
        const disposition = response.headers.get('content-disposition');
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }
        
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        Swal.close();
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Thất bại', 'Đã xảy ra sự cố khi xuất Excel. Vui lòng thử lại!', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
