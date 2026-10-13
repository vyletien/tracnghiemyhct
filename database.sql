CREATE DATABASE IF NOT EXISTS tracnghiem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tracnghiem;

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    unit_id INT NULL,
    role ENUM('admin', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE RESTRICT
);

INSERT IGNORE INTO units (name) VALUES
('Ban Phụ nữ Công an tỉnh'),
('Hội Phụ nữ cơ quan BCH Quân sự tỉnh');

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    content TEXT NOT NULL,
    type ENUM('single', 'multiple') DEFAULT 'single',
    level ENUM('1', '2', '3', '4', '5') DEFAULT '1' COMMENT '1:Nhận biết, 2:Thông hiểu, 3:Vận dụng, 4:Vận dụng cao, 5:Thông thường',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    content TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    duration_minutes INT NOT NULL DEFAULT 60,
    shuffle_questions BOOLEAN DEFAULT TRUE,
    shuffle_answers BOOLEAN DEFAULT TRUE,
    show_results BOOLEAN DEFAULT TRUE,
    enable_prediction BOOLEAN DEFAULT FALSE,
    allow_multiple BOOLEAN DEFAULT FALSE,
    attempt_mode TINYINT NOT NULL DEFAULT 0 COMMENT '0: một lần, 1: nhiều lần, 2: tối đa một lần mỗi ngày',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS exam_questions (
    exam_id INT NOT NULL,
    question_id INT NOT NULL,
    PRIMARY KEY (exam_id, question_id),
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exam_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    score DECIMAL(5,2) DEFAULT NULL,
    is_submitted BOOLEAN DEFAULT FALSE,
    prediction_value INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attempt_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT NOT NULL,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE CASCADE
);

-- Pass admin default: admin / Hoiphunu@789
INSERT INTO users (username, password, fullname, role) VALUES 
('admin', '$2a$12$hT4IPEZUXW2n/KhETXoiAeo1W9CKYW7uQRN/ey0QIIXyvlH0kFXIi', 'Administrator', 'admin');
