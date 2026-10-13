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
    SELECT ea.*, u.fullname, u.username, u.phone, un.name AS unit_name,
           COALESCE(ce.cheat_count, 0) AS cheat_count,
           COALESCE(ce.reentry_count, 0) AS reentry_count
    FROM exam_attempts ea 
    JOIN users u ON ea.user_id = u.id
    LEFT JOIN units un ON u.unit_id = un.id
    LEFT JOIN (
        SELECT attempt_id,
               SUM(CASE WHEN event_type <> 'page_open' THEN 1 ELSE 0 END) AS cheat_count,
               GREATEST(SUM(CASE WHEN event_type = 'page_open' THEN 1 ELSE 0 END) - 1, 0) AS reentry_count
        FROM exam_cheat_events
        GROUP BY attempt_id
    ) ce ON ce.attempt_id = ea.id
    WHERE ea.exam_id = ? AND ea.is_submitted = 1
    ORDER BY 
        ea.score DESC, 
        (ea.prediction_value IS NULL) ASC,
        (CAST(COALESCE(ea.prediction_value, 0) AS SIGNED) >= ?) DESC,
        ABS(CAST(COALESCE(ea.prediction_value, 0) AS SIGNED) - ?) ASC, 
        ea.start_time ASC
");
$stmtAtt->execute([$exam_id, $actual_participants_for_ranking, $actual_participants_for_ranking]);
$attempts = $stmtAtt->fetchAll();

// Setup headers explicitly for Excel download TRƯỚC TIÊN TƯ THẾ ĐỂ TRÁNH TRÀN BỘ NHỚ
$filename = "BaoCao_DiemThi_" . date('Ymd_Hi') . ".xls";
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');


// --- BƯỚC 1: XUẤT SHEET 1 (ĐIỂM THÍ SINH) ---
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>';

// Cấu hình Sheet 1
echo '<x:ExcelWorksheet><x:Name>Diem Thi Sinh</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
// Cấu hình Sheet 2
echo '<x:ExcelWorksheet><x:Name>Thong Ke Cau Hoi</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';

echo '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';

// --- SHEET 1 ---
echo '<h2>BÁO CÁO ĐIỂM THI: ' . $exam['title'] . '</h2>';
echo '<p>Số bài nộp: <b>' . count($attempts) . '</b> | Số người dự thi (theo SĐT): <b>' . $unique_participants . '</b></p>';
echo '<table border="1">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th>STT</th>
            <th>Họ Tên</th>
            <th>Số Điện Thoại</th>
            <th>Đơn vị</th>
            <th>Câu dự đoán</th>
            <th>Số lần vào lại</th>
            <th>Số cảnh báo gian lận</th>
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
    echo '<tr>
        <td>' . $stt++ . '</td>
        <td>' . htmlspecialchars($a['fullname']) . '</td>
        <td>' . htmlspecialchars($a['phone'] ?: 'N/A') . '</td>
        <td>' . htmlspecialchars($a['unit_name'] ?: 'Chưa xác định') . '</td>
        <td>' . ($a['prediction_value'] ?? '-') . '</td>
        <td>' . (int)$a['reentry_count'] . '</td>
        <td>' . (int)$a['cheat_count'] . '</td>
        <td>' . date('d/m/Y H:i:s', $t1) . '</td>
        <td>' . date('d/m/Y H:i:s', $t2) . '</td>
        <td>' . $seconds . '</td>
        <td>' . number_format($a['score'] ?? 0, 2) . '</td>
    </tr>';
}
if (count($attempts) === 0) {
    echo '<tr><td colspan="11">Chưa có dữ liệu</td></tr>';
}
echo '</tbody></table><br><br><br>';


// --- BƯỚC 2: XUẤT SHEET 2 (THỐNG KÊ CÂU HỎI NHƯ YÊU CẦU) ---

// Get all questions in exam
$stmtQ = $pdo->prepare("SELECT q.* FROM questions q JOIN exam_questions eq ON q.id = eq.question_id WHERE eq.exam_id = ?");
$stmtQ->execute([$exam_id]);
$questions = $stmtQ->fetchAll();

echo '<h2>THỐNG KÊ ĐỘ KHÓ CỦA TỪNG CÂU HỎI</h2>';
echo '<table border="1">
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
$totalCount = count($attempt_ids);

// TỐI ƯU CÂU TRUY VẤN 1: Lấy danh sách đáp án đúng của toàn bộ bài thi ra trước
$stmtAns = $pdo->prepare("
    SELECT a.question_id, a.id 
    FROM answers a
    JOIN exam_questions eq ON a.question_id = eq.question_id
    WHERE eq.exam_id = ? AND a.is_correct = 1
");
$stmtAns->execute([$exam_id]);
$correctKeys = [];
foreach ($stmtAns->fetchAll() as $row) {
    if (!isset($correctKeys[$row['question_id']])) {
        $correctKeys[$row['question_id']] = [];
    }
    $correctKeys[$row['question_id']][] = $row['id'];
}

// TỐI ƯU CÂU TRUY VẤN 2: Lấy toàn bộ câu trả lời của tất cả thí sinh vào một mảng
$stmtUserAns = $pdo->prepare("
    SELECT aa.attempt_id, aa.question_id, aa.answer_id
    FROM attempt_answers aa
    JOIN exam_attempts ea ON aa.attempt_id = ea.id
    WHERE ea.exam_id = ? AND ea.is_submitted = 1
");
$stmtUserAns->execute([$exam_id]);
$userAnswersMap = [];
foreach ($stmtUserAns->fetchAll() as $row) {
    if (!isset($userAnswersMap[$row['question_id']])) {
        $userAnswersMap[$row['question_id']] = [];
    }
    if (!isset($userAnswersMap[$row['question_id']][$row['attempt_id']])) {
        $userAnswersMap[$row['question_id']][$row['attempt_id']] = [];
    }
    $userAnswersMap[$row['question_id']][$row['attempt_id']][] = $row['answer_id'];
}

$attempt_ids_map = array_flip($attempt_ids); // Để tra cứu O(1) xem có trong list nộp bài không

foreach ($questions as $q) {
    $qId = $q['id'];
    $correctCount = 0;
    
    $correctAnswers = isset($correctKeys[$qId]) ? $correctKeys[$qId] : [];
    sort($correctAnswers);
    
    if ($totalCount > 0) {
        if (isset($userAnswersMap[$qId])) {
            foreach ($userAnswersMap[$qId] as $attId => $uAnswers) {
                // Kiểm tra xem attempt_id có hợp lệ trong bài thi đã submit (đã lọc ở MAP)
                if (isset($attempt_ids_map[$attId])) {
                    if ($q['type'] === 'single') {
                        if (count($uAnswers) == 1 && in_array($uAnswers[0], $correctAnswers)) {
                            $correctCount++;
                        }
                    } else {
                        sort($uAnswers);
                        if (count($correctAnswers) > 0 && $correctAnswers === $uAnswers) {
                            $correctCount++;
                        }
                    }
                }
            }
        }
    }
    
    $ratio = $totalCount > 0 ? round(($correctCount / $totalCount) * 100, 2) : 0;
    
    echo '<tr>
        <td>' . $qId . '</td>
        <td>' . mb_strimwidth(strip_tags($q['content']), 0, 100, '...') . '</td>
        <td>' . ($q['type'] == 'single' ? 'Single' : 'Multiple') . '</td>
        <td>' . $q['level'] . '</td>
        <td>' . $correctCount . '</td>
        <td>' . $totalCount . '</td>
        <td>' . $ratio . '%</td>
    </tr>';
}

echo '</tbody></table></body></html>';

exit;
