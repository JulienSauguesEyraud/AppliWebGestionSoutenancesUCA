<?php
require_once __DIR__ . '/../models/Auth.php';

function auth_showLogin($db) {
    if (auth_model_isLoggedIn()) {
        header('Location: index.php?action=accueil');
        exit();
    }
    require_once __DIR__ . '/../views/auth/login.php';
}

function auth_processLogin($db) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $result = auth_model_login($db, $email, $password);
        if ($result['success']) {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_info'] = $result['user'];
            $_SESSION['logged_in'] = true;
            header('Location: index.php?action=accueil');
            exit();
        } else {
            $error_message = $result['message'];
            require_once __DIR__ . '/../views/auth/login.php';
        }
    }
}

function auth_logout($db) {
    auth_model_logout();
    header('Location: index.php?action=login');
    exit();
}

function auth_checkAccess($db) {
    if (!auth_model_isLoggedIn()) {
        header('Location: index.php?action=login');
        exit();
    }
}

function auth_processEvaluation($db) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Location: index.php?action=accueil');
        exit();
    } else {
        require_once __DIR__ . '/../views/FrontOffice/pageB.php';
    }
}

?>