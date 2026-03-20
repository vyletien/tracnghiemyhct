<?php
// functions.php contains global helper functions

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function checkAdmin() {
    if (!isAdmin()) {
        redirect('/index.php');
    }
}

function checkLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}
?>
