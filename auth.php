<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: login.html"); // redirige al login
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['tipo'] != 1) {
        header("Location: usuario.html"); // no es admin
        exit();
    }
}

function requireUser() {
    requireLogin();
    if ($_SESSION['tipo'] != 0) {
        header("Location: admin.html"); // no es usuario normal
        exit();
    }
}
?>
