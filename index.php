<?php
// index.php – Página principal

session_start();
require_once __DIR__ . '/config.php';

// Si ya hay sesión, vamos al panel adecuado
if (!empty($_SESSION['rol'])) {
    header('Location: router.php');
    exit;
}

$title = 'Inicio - Admisión NOS';

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/home_content.php';
include __DIR__ . '/views/footer.php';
