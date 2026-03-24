<?php
/**
 * Jiji-Inspired-1.0 User Authentication Logic
 */

function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_user() {
    if (!is_user_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function user_logout() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    session_destroy();
    header('Location: /index.php');
    exit;
}
