<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) die("Missing exam_id");

// Fetch exam
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
if (!$exam) die("Exam not found");

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

// Fetch attempts with refined ranking
$stmtAtt = $pdo->prepare("
    SELECT ea.*, u.fullname, u.username, u.phone, u.address
    FROM exam_attempts ea 
    JOIN users u ON ea.user_id = u.id 
    WHERE ea.exam_id = ? AND ea.is_submitted = 1
    ORDER BY 
        ea.score DESC, 
        (ea.prediction_value IS NULL) ASC,
        ABS(CAST(COALESCE(ea.prediction_value, 0) AS SIGNED) - ?) ASC, 
        ea.start_time ASC
");
$stmtAtt->execute([$exam_id, $actual_participants_for_ranking]);
$attempts = $stmtAtt->fetchAll();

// --- BƯỚC 1: XUẤT SHEET 1 (ĐIỂM THÍ SINH) ---
$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
$html .= '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>';

// Cấu hình Sheet 1
$html .= '<x:ExcelWorksheet><x:Name>Diem Thi Sinh</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
// Cấu hình Sheet 2
$html .= '<x:ExcelWorksheet><x:Name>Thong Ke Cau Hoi</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';

$html .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';

// --- SHEET 1 ---
$html .= '<h2>BÁO CÁO ĐIỂM THI: ' . $exam['title'] . '</h2>';
$html .= '<p>Số bài nộp: <b>' . count($attempts) . '</b> | Số người dự thi (theo SĐT): <b>' . $unique_participants . '</b></p>';
$html .= '<table border="1">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th>STT</th>
            <th>Họ Tên</th>
            <th>Số Điện Thoại</th>
            <th>Địa Chỉ</th>
            <th>Câu dự đoán</th>
            <th>Thời gian bắt đầu</th>
            <th>Thời gian nộp bài</th>
            <th>Thời gian làm bài (Giây)</th>
            <th>Điểm số</th>
        </tr>
    </thead>
    <tbody>';

$stt = 1;
foreach ($attempts as $a) {
    $t1 = strtotime($a['start_time']);
    $t2 = strtotime($a['end_time']);
    $seconds = ($t2 - $t1);
    $html .= '<tr>
        <td>' . $stt++ . '</td>
        <td>' . htmlspecialchars($a['fullname']) . '</td>
        <td>' . htmlspecialchars($a['phone'] ?: 'N/A') . '</td>
        <td>' . htmlspecialchars($a['address'] ?: 'N/A') . '</td>
        <td>' . ($a['prediction_value'] ?? '-') . '</td>
        <td>' . date('d/m/Y H:i:s', $t1) . '</td>
        <td>' . date('d/m/Y H:i:s', $t2) . '</td>
        <td>' . $seconds . '</td>
        <td>' . number_format($a['score'] ?? 0, 2) . '</td>
    </tr>';
}
if (count($attempts) === 0) {
    $html .= '<tr><td colspan="9">Chưa có dữ liệu</td></tr>';
}
$html .= '</tbody></table><br><br><br>';


// --- BƯỚC 2: XUẤT SHEET 2 (THỐNG KÊ CÂU HỎI NHƯ YÊU CẦU) ---

// Get all questions in exam
$stmtQ = $pdo->prepare("SELECT q.* FROM questions q JOIN exam_questions eq ON q.id = eq.question_id WHERE eq.exam_id = ?");
$stmtQ->execute([$exam_id]);
$questions = $stmtQ->fetchAll();

$html .= '<h2>THỐNG KÊ ĐỘ KHÓ CỦA TỪNG CÂU HỎI</h2>';
$html .= '<table border="1">
    <thead>
        <tr style="background-color: #d9edf7;">
            <th>ID Câu hỏi</th>
            <th>Nội dung trích đoạn</th>
            <th>Loại</th>
            <th>Mức độ (1-4)</th>
            <th>Số lượt chọn ĐÚNG</th>
            <th>Tổng lượt làm</th>
            <th>Tỷ lệ Đúng (%)</th>
        </tr>
    </thead>
    <tbody>';

// Attempt IDs for this exam
$attempt_ids = array_column($attempts, 'id');

foreach ($questions as $q) {
    $qId = $q['id'];
    
    // Correct answers
    $ansStmt = $pdo->prepare("SELECT id FROM answers WHERE question_id = ? AND is_correct = 1");
    $ansStmt->execute([$qId]);
    $correctAnswers = $ansStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $correctCount = 0;
    $totalCount = count($attempt_ids);
    
    if ($totalCount > 0) {
        foreach ($attempt_ids as $attId) {
            $uAnsStmt = $pdo->prepare("SELECT answer_id FROM attempt_answers WHERE attempt_id = ? AND question_id = ?");
            $uAnsStmt->execute([$attId, $qId]);
            $uAnswers = $uAnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if ($q['type'] === 'single') {
                if (count($uAnswers) == 1 && in_array($uAnswers[0], $correctAnswers)) {
                    $correctCount++;
                }
            } else {
                sort($correctAnswers);
                sort($uAnswers);
                if (count($correctAnswers) > 0 && $correctAnswers === $uAnswers) {
                    $correctCount++;
                }
            }
        }
    }
    
    $ratio = $totalCount > 0 ? round(($correctCount / $totalCount) * 100, 2) : 0;
    
    $html .= '<tr>
        <td>' . $qId . '</td>
        <td>' . mb_strimwidth(strip_tags($q['content']), 0, 100, '...') . '</td>
        <td>' . ($q['type'] == 'single' ? 'Single' : 'Multiple') . '</td>
        <td>' . $q['level'] . '</td>
        <td>' . $correctCount . '</td>
        <td>' . $totalCount . '</td>
        <td>' . $ratio . '%</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

// Setup headers explicitly for Excel download
$filename = "BaoCao_DiemThi_" . date('Ymd_Hi') . ".xls";
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');
// Output contents
echo $html;
exit;
