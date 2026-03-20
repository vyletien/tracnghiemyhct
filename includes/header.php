<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phần mềm Trắc nghiệm</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        body { background-color: #f8f9fa; }
        .sidebar-exam {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .question-nav {
            display: inline-flex;
            width: 35px;
            height: 35px;
            align-items: center;
            justify-content: center;
            margin: 2px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            text-decoration: none;
            color: #212529;
            background-color: white;
            font-weight: 500;
        }
        .question-nav:hover { background-color: #e9ecef; }
        .question-nav.answered { background-color: #198754; color: white; border-color: #198754; }
        .question-nav.active { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); border-color: #0d6efd; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="/index.php"><i class="bi bi-journal-check"></i> Trắc Nghiệm Online</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <?php if (isAdmin()): ?>
        <li class="nav-item">
          <a class="nav-link" href="/admin/index.php">Quản trị</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/admin/questions.php">Ngân hàng câu hỏi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/admin/exams.php">Quản lý đề thi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/admin/reports.php">Báo cáo</a>
        </li>
        <?php elseif (isLoggedIn()): ?>
        <li class="nav-item">
          <a class="nav-link" href="/index.php">Danh sách đề thi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/history.php">Lịch sử thi</a>
        </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if (isLoggedIn()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?= h($_SESSION['fullname'] ?? '') ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/logout.php">Đăng xuất</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
