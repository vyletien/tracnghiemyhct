<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkLogin();
verify_csrf();

$attempt_id = $_POST['attempt_id'] ?? $_GET['attempt_id'] ?? null;
if (!$attempt_id) redirect('/index.php');

$user_id = $_SESSION['user_id'];

// Lock attempt to avoid multiple saves
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ? AND user_id = ?");
$stmt->execute([$attempt_id, $user_id]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['is_submitted']) {
    redirect("/index.php"); // Already submitted previously
}

$exam_id = $attempt['exam_id'];

// Enforce the deadline on the server; client-side JavaScript can be modified.
if ($attempt['end_time'] && strtotime($attempt['end_time']) <= time()) {
    http_response_code(403);
    exit('Đã hết thời gian làm bài.');
}

// Record User Answers
$userAnswers = $_POST['answers'] ?? []; // format: [question_id] => answer_id OR array of answer_ids

$pdo->beginTransaction();
try {
    $insertAnsStmt = $pdo->prepare("INSERT INTO attempt_answers (attempt_id, question_id, answer_id) VALUES (?, ?, ?)");

    foreach ($userAnswers as $qId => $ansData) {
        if (is_array($ansData)) {
            // Multiple choice
            foreach ($ansData as $ansId) {
                $insertAnsStmt->execute([$attempt_id, $qId, $ansId]);
            }
        } else {
            // Single choice
            if (!empty($ansData)) {
                $insertAnsStmt->execute([$attempt_id, $qId, $ansData]);
            }
        }
    }

    // --- Scoring Logic ---
    // Get all questions in exam
    $qStmt = $pdo->prepare("SELECT q.* FROM questions q JOIN exam_questions eq ON q.id = eq.question_id WHERE eq.exam_id = ?");
    $qStmt->execute([$exam_id]);
    $examQs = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalQuestions = count($examQs);
    $correctCount = 0;

    foreach ($examQs as $q) {
        $qId = $q['id'];
        
        // Correct answers for this question
        $ansStmt = $pdo->prepare("SELECT id FROM answers WHERE question_id = ? AND is_correct = 1");
        $ansStmt->execute([$qId]);
        $correctAnswers = $ansStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // User answers for this question
        $uAnsStmt = $pdo->prepare("SELECT answer_id FROM attempt_answers WHERE attempt_id = ? AND question_id = ?");
        $uAnsStmt->execute([$attempt_id, $qId]);
        $uAnswers = $uAnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if correct
        if ($q['type'] === 'single') {
            if (count($uAnswers) == 1 && in_array($uAnswers[0], $correctAnswers)) {
                $correctCount++;
            }
        } else {
            // Multiple choice - must match exactly
            sort($correctAnswers);
            sort($uAnswers);
            if (count($correctAnswers) > 0 && $correctAnswers === $uAnswers) {
                $correctCount++;
            }
        }
    }

    $score = ($totalQuestions > 0) ? round(($correctCount / $totalQuestions) * 10, 2) : 0;

    $prediction = isset($_POST['prediction']) ? (int)$_POST['prediction'] : null;

    // Update attempt
    $updStmt = $pdo->prepare("UPDATE exam_attempts SET is_submitted = 1, end_time = NOW(), score = ?, prediction_value = ? WHERE id = ?");
    $updStmt->execute([$score, $prediction, $attempt_id]);
    
    $pdo->commit();
    
    // Redirect to results
    redirect("/results.php?exam_id=$exam_id");

} catch (\Exception $e) {
    $pdo->rollBack();
    error_log('Exam submission failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể nộp bài. Vui lòng thử lại sau.');
    die("Lỗi khi nộp bài: " . $e->getMessage());
}
