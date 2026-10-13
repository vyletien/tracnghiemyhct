<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
$contestName = getSiteSetting($pdo, 'contest_name', 'BỆNH VIỆN YHCT & PHCN QUY NHƠN');
$defaultOgImage = '/assets/og-default.png';
$configuredOgImage = getSiteSetting($pdo, 'og_image', $defaultOgImage);
$baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$ogImage = preg_match('/^https?:\\/\\//i', $configuredOgImage)
    ? $configuredOgImage
    : $baseUrl . '/' . ltrim($configuredOgImage, '/');
$pageUrl = $baseUrl . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($contestName) ?></title>
    <meta name="description" content="<?= h($contestName) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= h($contestName) ?>">
    <meta property="og:description" content="Tham gia <?= h($contestName) ?> trực tuyến.">
    <meta property="og:url" content="<?= h($pageUrl) ?>">
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= h($ogImage) ?>">
    <meta property="og:image:width" content="1536">
    <meta property="og:image:height" content="1024">
    <meta property="og:image:type" content="image/png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($contestName) ?>">
    <meta name="twitter:description" content="Tham gia <?= h($contestName) ?> trực tuyến.">
    <meta name="twitter:image" content="<?= h($ogImage) ?>">
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
        .navbar-brand { white-space: normal; line-height: 1.2; font-weight: 600; }
        @media (max-width: 575.98px) {
            .navbar > .container { position: relative; justify-content: center; }
            .navbar-brand { flex: 1 1 100%; min-width: 0; padding: 0 48px; text-align: center; font-size: 1rem; font-weight: 700; }
            .navbar-toggler { position: absolute; right: 12px; top: 7px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="/index.php"><?= h($contestName) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <?php if (isLoggedIn() && !isAdmin()): ?>
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
        <?php if (isAdmin()): ?>
        <li class="nav-item">
          <a class="nav-link" href="/admin/index.php">Quản trị</a>
        </li>
        <?php endif; ?>
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
