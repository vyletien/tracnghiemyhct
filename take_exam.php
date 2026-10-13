<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkLogin();

$exam_id = $_GET['id'] ?? null;
if (!$exam_id) redirect('/index.php');

// Fetch exam details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    die("Đề thi không tồn tại.");
}

// Check time constraints
$now = new DateTime();
if ($exam['start_time']) {
    $st = new DateTime($exam['start_time']);
    if ($now < $st) die("Chưa tới thời gian mở đề.");
}
if ($exam['end_time']) {
    $et = new DateTime($exam['end_time']);
    if ($now > $et) die("Đã qua thời gian làm bài.");
}

$user_id = $_SESSION['user_id'];
$attempt_mode = (int)$exam['attempt_mode'];

// Check attempt
if ($attempt_mode === 1 || $attempt_mode === 2) {
    // If multiple attempts allowed, look for an active (unsubmitted) one
    $stmtAtt = $pdo->prepare("SELECT * FROM exam_attempts WHERE user_id = ? AND exam_id = ? AND is_submitted = 0 ORDER BY id DESC LIMIT 1");
    $stmtAtt->execute([$user_id, $exam_id]);
    $attempt = $stmtAtt->fetch();

    // Daily mode: after finishing one attempt today, no new attempt is allowed.
    if (!$attempt && $attempt_mode === 2) {
        $today_start = date('Y-m-d 00:00:00');
        $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $stmtDaily = $pdo->prepare("SELECT * FROM exam_attempts WHERE user_id = ? AND exam_id = ? AND is_submitted = 1 AND end_time >= ? AND end_time < ? ORDER BY id DESC LIMIT 1");
        $stmtDaily->execute([$user_id, $exam_id, $today_start, $tomorrow_start]);
        $daily_attempt = $stmtDaily->fetch();
        if ($daily_attempt) {
            redirect("/results.php?exam_id=$exam_id");
        }
    }
} else {
    // If only one attempt allowed, look for ANY attempt
    $stmtAtt = $pdo->prepare("SELECT * FROM exam_attempts WHERE user_id = ? AND exam_id = ? ORDER BY id DESC LIMIT 1");
    $stmtAtt->execute([$user_id, $exam_id]);
    $attempt = $stmtAtt->fetch();
    
    if ($attempt && $attempt['is_submitted']) {
        // Already submitted and re-taking not allowed
        redirect("/results.php?exam_id=$exam_id");
    }
}

if (!$attempt) {
    // Create new attempt
    $start_time = date('Y-m-d H:i:s');
    // Default end time max
    if ($exam['duration_minutes'] == 0) {
        $max_end = '2099-12-31 23:59:59'; // Virtually unlimited
    } else {
        $max_end = date('Y-m-d H:i:s', strtotime("+$exam[duration_minutes] minutes"));
    }
    
    if ($exam['end_time'] && $exam['end_time'] < $max_end) {
        $max_end = $exam['end_time'];
    }
    
    $stmtIns = $pdo->prepare("INSERT INTO exam_attempts (user_id, exam_id, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmtIns->execute([$user_id, $exam_id, $start_time, $max_end]);
    $attempt_id = $pdo->lastInsertId();
    
    // Fetch newly created
    $stmtLatest = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ?");
    $stmtLatest->execute([$attempt_id]);
    $attempt = $stmtLatest->fetch();
}

// Ghi nhận mỗi lần mở hoặc tiếp tục trang thi để admin nhận biết thí sinh đã vào lại.
$pdo->prepare("INSERT INTO exam_cheat_events (attempt_id, event_type, event_detail) VALUES (?, 'page_open', ?)")
    ->execute([$attempt['id'], 'Mở hoặc tiếp tục trang thi']);

// Ensure end_time is not in the past
if ($exam['duration_minutes'] > 0) {
    $time_left_seconds = strtotime($attempt['end_time']) - time();
    if ($time_left_seconds <= 0) {
        // Force submit if time is up
        redirect("/submit_exam.php?auto=1&attempt_id={$attempt['id']}");
    }
}

// Load questions for this exam
$stmtQ = $pdo->prepare("SELECT q.* FROM questions q JOIN exam_questions eq ON q.id = eq.question_id WHERE eq.exam_id = ?");
$stmtQ->execute([$exam_id]);
$questions = $stmtQ->fetchAll();

if ($exam['shuffle_questions']) {
    shuffle($questions);
}

// Fetch answers
$answers_by_q = [];
$q_ids = array_column($questions, 'id');
if (count($q_ids) > 0) {
    $in = str_repeat('?,', count($q_ids) - 1) . '?';
    $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id IN ($in)");
    $stmtAns->execute($q_ids);
    $allAns = $stmtAns->fetchAll();
    
    foreach ($allAns as $ans) {
        $answers_by_q[$ans['question_id']][] = $ans;
    }
}

if ($exam['shuffle_answers']) {
    foreach ($answers_by_q as $qId => &$ansArr) {
        shuffle($ansArr);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .exam-sticky-header {
        transition: padding .2s ease, box-shadow .2s ease;
    }

    @media (max-width: 767.98px) {
        .exam-sticky-header {
            gap: .5rem;
            flex-wrap: nowrap;
        }

        .exam-title {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .exam-sticky-header.is-scrolled .exam-title {
            display: none;
        }

        .exam-sticky-header.is-scrolled {
            justify-content: flex-end !important;
            padding-top: .5rem;
            padding-bottom: .5rem;
            box-shadow: 0 .125rem .35rem rgba(0, 0, 0, .12);
        }

        #countdownTimer {
            flex: 0 0 auto;
            font-size: 1.1rem !important;
            padding: .4rem .65rem !important;
        }
    }
</style>

<div class="row">
    <div class="col-md-9">
        <form id="examForm" method="POST" action="submit_exam.php">
            <?= csrf_field() ?>
            <input type="hidden" name="attempt_id" value="<?= $attempt['id'] ?>">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center sticky-top exam-sticky-header" style="top: 0; z-index: 1000;">
                    <h4 class="mb-0 text-primary exam-title"><?= h($exam['title']) ?></h4>
                    <div class="fw-bold fs-5 text-danger border p-2 rounded bg-light" id="countdownTimer">
                        00:00:00
                    </div>
                </div>
                <div class="card-body">
                    <?php /* Ẩn mô tả theo yêu cầu người dùng
                    if ($exam['description']): ?>
                        <div class="alert alert-info"><?= nl2br(h($exam['description'])) ?></div>
                    <?php endif; */ ?>

                    <?php 
                    $qNum = 1;
                    foreach ($questions as $q): 
                        $qId = $q['id'];
                        $ansList = $answers_by_q[$qId] ?? [];
                        $inputType = $q['type'] === 'single' ? 'radio' : 'checkbox';
                        $inputName = $q['type'] === 'single' ? "answers[$qId]" : "answers[$qId][]";
                    ?>
                        <div class="mb-5 border-bottom pb-4 question-block" id="q<?= $qId ?>" data-qid="<?= $qId ?>">
                            <div class="d-flex">
                                <div class="fw-bold me-2">Câu <?= $qNum ?>:</div>
                                <div class="question-content mb-3"><?= $q['content'] ?> <!-- Hiển thị HTML an toàn (TinyMCE) --></div>
                            </div>
                            
                            <div class="answers-list ms-4">
                                <?php foreach ($ansList as $ans): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input answer-input" type="<?= $inputType ?>" name="<?= $inputName ?>" value="<?= $ans['id'] ?>" id="ans<?= $ans['id'] ?>" data-parent="q<?= $qId ?>">
                                        <label class="form-check-label" for="ans<?= $ans['id'] ?>">
                                            <?= $ans['content'] ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php $qNum++; endforeach; ?>

                    <?php if ($exam['enable_prediction']): ?>
                        <div class="mt-5 p-4 border rounded bg-warning bg-opacity-10 border-warning">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-graph-up-arrow"></i> Câu hỏi phụ (Dự đoán)</h5>
                            <label class="form-label fw-bold">Dự đoán của bạn: Có bao nhiêu người sẽ tham gia cuộc thi này?</label>
                            <div class="input-group" style="max-width:300px;">
                                <input type="number" name="prediction" class="form-control" placeholder="Nhập số dự đoán..." required min="0">
                                <span class="input-group-text">người</span>
                            </div>
                            <div class="form-text text-muted mt-2">Dự đoán này không tính vào điểm số chính thức.</div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary btn-lg px-5" id="btnSubmitExam">Nộp bài</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Sidebar Status -->
    <div class="col-md-3">
        <div class="card shadow-sm sidebar-exam">
            <div class="card-header bg-white fw-bold text-center">
                Trạng thái làm bài
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-center" id="questionNavGrid">
                    <?php 
                    $qNum = 1;
                    foreach ($questions as $q): 
                    ?>
                        <a href="#q<?= $q['id'] ?>" class="question-nav" id="nav_q<?= $q['id'] ?>">
                            <?= $qNum ?>
                        </a>
                    <?php $qNum++; endforeach; ?>
                </div>
                
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center mb-2">
                        <span class="question-nav answered m-0 me-2" style="width:20px;height:20px;"></span> Đã làm
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="question-nav m-0 me-2" style="width:20px;height:20px;"></span> Chưa làm
                    </div>
                </div>
            </div>
            <div id="cheatWarning" class="alert alert-warning d-none" role="alert"></div>
        </div>
    </div>
</div>

<script>
    // Timer Countdown
    const duration = <?= (int)$exam['duration_minutes'] ?>;
    const endTimeStr = "<?= date('c', strtotime($attempt['end_time'])) ?>";
    const endTime = new Date(endTimeStr).getTime();
    const attemptId = <?= (int)$attempt['id'] ?>;
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    let wasHidden = false;
    let warningCount = 0;
    let isSubmitting = false;
    const examForm = document.getElementById('examForm');

    function logExamEvent(eventType, detail = '') {
        const suspiciousEvents = ['tab_hidden', 'page_unload', 'copy', 'cut', 'paste', 'context_menu', 'devtools_shortcut'];
        if (suspiciousEvents.includes(eventType)) {
            warningCount++;
            const warning = document.getElementById('cheatWarning');
            if (warning) {
                warning.textContent = `Cảnh báo: hệ thống đã ghi nhận ${warningCount} hành vi rời bài thi hoặc thao tác không được phép.`;
                warning.classList.remove('d-none');
            }
        }
        const body = JSON.stringify({attempt_id: attemptId, event_type: eventType, event_detail: detail, csrf_token: csrfToken});
        if (navigator.sendBeacon && eventType === 'page_unload') {
            navigator.sendBeacon('log_cheat_event.php', new Blob([body], {type: 'application/json'}));
            return;
        }
        fetch('log_cheat_event.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body,
            keepalive: true
        }).catch(() => {});
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            wasHidden = true;
            logExamEvent('tab_hidden', 'Rời tab hoặc chuyển sang cửa sổ khác');
        } else if (wasHidden) {
            wasHidden = false;
            logExamEvent('tab_visible', 'Quay lại trang thi');
        }
    });
    examForm.addEventListener('submit', () => { isSubmitting = true; });
    window.addEventListener('pagehide', () => {
        if (!isSubmitting) logExamEvent('page_unload', 'Rời khỏi hoặc đóng trang thi');
    });
    ['copy', 'cut', 'paste', 'contextmenu'].forEach(eventName => {
        document.addEventListener(eventName, event => {
            if (eventName !== 'copy' || event.target.closest('.question-content, .answers-list')) {
                event.preventDefault();
            }
            logExamEvent(eventName === 'contextmenu' ? 'context_menu' : eventName, 'Thao tác trên nội dung bài thi');
        });
    });
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && ['c', 'x', 'v', 'u', 's'].includes(event.key.toLowerCase())) {
            event.preventDefault();
            logExamEvent(event.key.toLowerCase() === 'c' ? 'copy' : event.key.toLowerCase() === 'x' ? 'cut' : event.key.toLowerCase() === 'v' ? 'paste' : 'devtools_shortcut', 'Phím tắt trình duyệt bị chặn');
        }
        if (event.key === 'F12' || (event.ctrlKey && event.shiftKey && ['i', 'j', 'c'].includes(event.key.toLowerCase()))) {
            event.preventDefault();
            logExamEvent('devtools_shortcut', 'Phím tắt công cụ nhà phát triển');
        }
    });

    function updateTimer() {
        if (duration === 0) {
            document.getElementById("countdownTimer").innerHTML = "KHÔNG GIỚI HẠN";
            return;
        }

        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            document.getElementById("countdownTimer").innerHTML = "HẾT GIỜ";
            document.getElementById("examForm").submit();
            return;
        }

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById("countdownTimer").innerHTML = 
            (hours < 10 ? "0" + hours : hours) + ":" + 
            (minutes < 10 ? "0" + minutes : minutes) + ":" + 
            (seconds < 10 ? "0" + seconds : seconds);
    }
    
    // Update every second
    if (duration > 0) {
        setInterval(updateTimer, 1000);
    }
    updateTimer();

    // Sidebar status sync
    document.querySelectorAll('.answer-input').forEach(input => {
        input.addEventListener('change', function() {
            const parentId = this.getAttribute('data-parent');
            const navBtn = document.getElementById('nav_' + parentId);
            
            // Check if any input in this question is checked
            const anyChecked = document.querySelectorAll(`#${parentId} .answer-input:checked`).length > 0;
            if (anyChecked) {
                navBtn.classList.add('answered');
            } else {
                navBtn.classList.remove('answered');
            }
        });
    });

    // Confirmation on manual submit
    document.getElementById('btnSubmitExam').addEventListener('click', function(e) {
        const total = <?= count($questions) ?>;
        const answered = document.querySelectorAll('#questionNavGrid .question-nav.answered').length;
        
        const predictionInput = document.getElementsByName('prediction')[0];
        const hasPrediction = predictionInput ? predictionInput.value.trim() !== '' : true;

        let msg = `Bạn đã làm xong ${answered}/${total} câu. Bạn có chắc chắn muốn nộp bài?`;
        if (answered < total) {
            msg = `CẢNH BÁO: Còn ${total - answered} câu chưa làm. Bạn vẫn muốn nộp bài?`;
        }

        if (!hasPrediction) {
            alert("Vui lòng nhập thông tin Dự đoán số người tham gia ở cuối đề trước khi nộp bài!");
            if (predictionInput) predictionInput.focus();
            return; // Stop submission
        }

        if (confirm(msg)) {
            document.getElementById('examForm').submit();
        }
    });

    // On mobile, keep only the countdown visible after the exam page is scrolled.
    const examStickyHeader = document.querySelector('.exam-sticky-header');
    function updateExamStickyHeader() {
        if (examStickyHeader) {
            examStickyHeader.classList.toggle('is-scrolled', window.innerWidth <= 767 && window.scrollY > 24);
        }
    }
    window.addEventListener('scroll', updateExamStickyHeader, { passive: true });
    window.addEventListener('resize', updateExamStickyHeader);
    updateExamStickyHeader();

    // Highlight current looking question
    window.addEventListener('scroll', function() {
        const blocks = document.querySelectorAll('.question-block');
        let currentId = '';
        blocks.forEach(block => {
            const rect = block.getBoundingClientRect();
            if (rect.top >= 0 && rect.top <= window.innerHeight / 2) {
                currentId = block.id;
            }
        });
        
        if (currentId) {
            document.querySelectorAll('.question-nav').forEach(nav => {
                nav.classList.remove('active');
            });
            const activeNav = document.getElementById('nav_' + currentId);
            if(activeNav) activeNav.classList.add('active');
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
