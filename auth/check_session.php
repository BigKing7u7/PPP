<?php
// includes/check_session.php
// Solo iniciar la sesión si aún no ha sido iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que la sesión esté activa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
    // No hay sesión activa, redirigir al login
    header('Location: /auth/login.php');
    exit;
}
