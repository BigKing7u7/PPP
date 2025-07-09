<?php
session_start();

// Verificar si ya hay una sesión activa. Si es así, redirigir al dashboard o a la página correspondiente.
if (isset($_SESSION['user_id'])) {
    header('Location: ../router.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/dbconnection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese ambos campos: usuario y contraseña.';
    } else {
        // Consulta a la base de datos para obtener el usuario
        $stmt = $dbh->prepare("SELECT * FROM usuarios WHERE username = :username AND estado = 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);  // Regenerar el ID de la sesión para prevenir fijación de sesión
            
            // Almacenar datos del usuario en la sesión
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['nombre'] = $user['nombre_completo'];

            // Redirigir después de un login exitoso
             header('Location: ../router.php');
            exit;
        } else {
            // Mensaje genérico de error
            $error = 'Credenciales incorrectas. Intenta nuevamente.';
            // Registro del error para monitoreo
            error_log("Intento fallido de login para usuario: $username", 3, '../logs/login_errors.log');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Iniciar Sesión - NOS</title>
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../css/login.css">
</head>
<body class="align">
  <div class="grid">
    <div class="register">
      <img src="../images/loguito.png" alt="Descripción de la imagen">
      <h3>Iniciar Sesión</h3>
      <form method="POST" class="form">
        <div class="form__field">
          <input type="text" name="username" placeholder="Usuario" required>
        </div>
        <div class="form__field">
          <input type="password" name="password" placeholder="Contraseña" required>
        </div>
        <div class="form__field">
          <input type="submit" value="Ingresar">
        </div>
        <?php if ($error): ?>
          <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <div class="error" style="display: none;"></div>
      </form>
      <div class="help-link">
        <p>¿Necesitas ayuda? <a href="#" onclick="alert('Contacte al administrador del sistema')">Contáctanos</a></p>
      </div>
    </div>
  </div>
  <script src="../js/login.js"></script>
</body>
</html>