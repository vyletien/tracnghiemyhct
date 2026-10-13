SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value)
VALUES ('contest_name', 'BỆNH VIỆN YHCT & PHCN QUY NHƠN');

CREATE TABLE units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_units_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO units (name) VALUES
('Phòng Kế Hoạch Tổng Hợp – Chỉ Đạo Tuyến'),
('Phòng Tổ chức – Hành chính'),
('Phòng Tài Chính – Kế Toán'),
('Phòng Điều Dưỡng'),
('Khoa Châm cứu – Dưỡng sinh'),
('Khoa Nội 1'),
('Khoa Nội 2'),
('Khoa Nội – Nhi'),
('Khoa Phục Hồi Chức Năng'),
('Khoa Khám Chữa – Bệnh Cấp Cứu'),
('Khoa Ngoại – Phụ'),
('Khoa Cận lâm sàng'),
('Khoa Kiểm soát nhiễm khuẩn – Dinh dưỡng'),
('Khoa Dược');

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    unit_id INT UNSIGNED NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_phone (phone),
    KEY idx_users_unit_id (unit_id),
    CONSTRAINT fk_users_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    content TEXT NOT NULL,
    type ENUM('single', 'multiple') NOT NULL DEFAULT 'single',
    level ENUM('1', '2', '3', '4', '5') NOT NULL DEFAULT '1' COMMENT '1: Nhận biết, 2: Thông hiểu, 3: Vận dụng, 4: Vận dụng cao, 5: Thông thường',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_questions_category_id (category_id),
    KEY idx_questions_level (level),
    CONSTRAINT fk_questions_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    KEY idx_answers_question_id (question_id),
    CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    shuffle_questions TINYINT(1) NOT NULL DEFAULT 1,
    shuffle_answers TINYINT(1) NOT NULL DEFAULT 1,
    show_results TINYINT(1) NOT NULL DEFAULT 1,
    enable_prediction TINYINT(1) NOT NULL DEFAULT 0,
    attempt_mode TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0: một lần, 1: nhiều lần, 2: tối đa một lần mỗi ngày',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_exams_schedule (start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exam_questions (
    exam_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (exam_id, question_id),
    KEY idx_exam_questions_question_id (question_id),
    CONSTRAINT fk_exam_questions_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_exam_questions_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exam_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    exam_id INT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    score DECIMAL(5,2) NULL,
    is_submitted TINYINT(1) NOT NULL DEFAULT 0,
    prediction_value INT UNSIGNED NULL,
    KEY idx_attempts_user_exam (user_id, exam_id),
    KEY idx_attempts_exam_submitted (exam_id, is_submitted),
    CONSTRAINT fk_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attempts_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attempt_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_attempt_answer (attempt_id, question_id, answer_id),
    KEY idx_attempt_answers_question_id (question_id),
    KEY idx_attempt_answers_answer_id (answer_id),
    CONSTRAINT fk_attempt_answers_attempt FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attempt_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attempt_answers_answer FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu để kiểm thử nhanh hệ thống
INSERT INTO categories (name) VALUES ('Mẫu kiểm tra YHCT');
SET @demo_category_id = LAST_INSERT_ID();

INSERT INTO questions (category_id, content, type, level) VALUES
(@demo_category_id, 'Y học cổ truyền thường sử dụng phương pháp nào sau đây để chẩn đoán?', 'single', '1');
SET @demo_q1 = LAST_INSERT_ID();
INSERT INTO answers (question_id, content, is_correct) VALUES
(@demo_q1, 'Tứ chẩn: vọng, văn, vấn, thiết', 1),
(@demo_q1, 'Chỉ đo huyết áp', 0),
(@demo_q1, 'Chỉ chụp X-quang', 0),
(@demo_q1, 'Chỉ xét nghiệm máu', 0);

INSERT INTO questions (category_id, content, type, level) VALUES
(@demo_category_id, 'Mục tiêu quan trọng của phục hồi chức năng là gì?', 'single', '1');
SET @demo_q2 = LAST_INSERT_ID();
INSERT INTO answers (question_id, content, is_correct) VALUES
(@demo_q2, 'Giúp người bệnh đạt mức độc lập tối đa trong sinh hoạt', 1),
(@demo_q2, 'Chỉ điều trị bằng thuốc', 0),
(@demo_q2, 'Kéo dài thời gian nằm viện', 0),
(@demo_q2, 'Thay thế hoàn toàn mọi hoạt động của người bệnh', 0);

INSERT INTO questions (category_id, content, type, level) VALUES
(@demo_category_id, 'Khoa, Phòng nào sau đây thuộc danh sách đơn vị mặc định của bệnh viện?', 'single', '2');
SET @demo_q3 = LAST_INSERT_ID();
INSERT INTO answers (question_id, content, is_correct) VALUES
(@demo_q3, 'Khoa Dược', 1),
(@demo_q3, 'Khoa Hàng không', 0),
(@demo_q3, 'Phòng Hàng hải', 0),
(@demo_q3, 'Khoa Thiết kế', 0);

INSERT INTO questions (category_id, content, type, level) VALUES
(@demo_category_id, 'Những yếu tố nào góp phần hỗ trợ phục hồi chức năng hiệu quả? (Chọn nhiều đáp án)', 'multiple', '2');
SET @demo_q4 = LAST_INSERT_ID();
INSERT INTO answers (question_id, content, is_correct) VALUES
(@demo_q4, 'Tập luyện phù hợp và đều đặn', 1),
(@demo_q4, 'Sự phối hợp của gia đình', 1),
(@demo_q4, 'Bỏ qua hướng dẫn của nhân viên y tế', 0),
(@demo_q4, 'Tự ý ngừng chương trình tập luyện', 0);

INSERT INTO questions (category_id, content, type, level) VALUES
(@demo_category_id, 'Khi tham gia thi trực tuyến, thí sinh cần làm gì sau khi hoàn thành bài?', 'single', '1');
SET @demo_q5 = LAST_INSERT_ID();
INSERT INTO answers (question_id, content, is_correct) VALUES
(@demo_q5, 'Kiểm tra lại đáp án và bấm Nộp bài', 1),
(@demo_q5, 'Đóng trình duyệt ngay lập tức', 0),
(@demo_q5, 'Đăng xuất trước khi nộp bài', 0),
(@demo_q5, 'Xóa lịch sử trình duyệt', 0);

INSERT INTO exams (title, description, duration_minutes, shuffle_questions, shuffle_answers, show_results, enable_prediction, attempt_mode)
VALUES ('Đề mẫu - Kiến thức Y học cổ truyền và Phục hồi chức năng', 'Đề thi mẫu gồm 5 câu hỏi để kiểm thử quy trình tạo đề, vào thi và nộp bài.', 15, 0, 0, 1, 0, 1);
SET @demo_exam_id = LAST_INSERT_ID();
INSERT INTO exam_questions (exam_id, question_id) VALUES
(@demo_exam_id, @demo_q1),
(@demo_exam_id, @demo_q2),
(@demo_exam_id, @demo_q3),
(@demo_exam_id, @demo_q4),
(@demo_exam_id, @demo_q5);

SET FOREIGN_KEY_CHECKS = 1;

-- Tài khoản quản trị mặc định (mật khẩu được lưu dưới dạng BCrypt)
INSERT INTO users (username, password, fullname, role)
VALUES ('admin', '$2y$10$gW6yuYAncm/pJypCCpVdlu0wuRd/roVJoOODmWThy6OHuhQvtMjwa', 'Administrator', 'admin');
