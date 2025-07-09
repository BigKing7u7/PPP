<?php
// router.php – Enrutador según rol de usuario

session_start();

// Verificar si la sesión está activa y si el usuario ha iniciado sesión correctamente
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
    // Si no hay una sesión válida, redirigir a login
    header('Location: auth/login.php');
    exit;
}

// Redirigir según el rol del usuario
switch ($_SESSION['rol']) {
    case 'admin':
    case 'secretaria':
    case 'verificador':
        // Redirigir al dashboard de administración
        header('Location: views/dashboard.php');
        exit;

    case 'postulante':
        // Redirigir al panel de postulante
        header('Location: user/inscripcion.php');
        exit;

    default:
        // Si el rol no es reconocido, destruir la sesión y redirigir a una página de error
        session_unset();  // Limpiar todas las variables de sesión
        session_destroy();  // Destruir la sesión
        header('Location: auth/error.php');  // Redirigir a la página de error personalizada
        exit;
}

?>
