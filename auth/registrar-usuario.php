<?php
// auth/registrar_usuario.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/dbconnection.php';
require_once __DIR__ . '/../includes/check_roles.php';
requireRol(['admin']);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validación
  $usuario = trim($_POST['usuario'] ?? '');
  $contrasena = $_POST['contrasena'] ?? '';
  $rol = $_POST['rol'] ?? '';

  if (!$usuario || !$contrasena || !$rol) {
    $mensaje = 'Completa todos los campos.';
  } else {
    // Revisar si el usuario ya existe
    $stmt = $dbh->prepare("SELECT COUNT(*) FROM usuarios WHERE username = :usuario");
    $stmt->execute([':usuario' => $usuario]);
    if ($stmt->fetchColumn() > 0) {
      $mensaje = 'El usuario ya existe.';
    } else {
      // Hash de la contraseña
      $passwordHash = password_hash($contrasena, PASSWORD_BCRYPT);

      // Insertar
      $stmt = $dbh->prepare("INSERT INTO usuarios (username, password, rol) VALUES (:usuario, :password, :rol)");
      $stmt->execute([
        ':usuario' => $usuario,
        ':password' => $passwordHash,
        ':rol' => $rol
      ]);

      $mensaje = 'Usuario registrado exitosamente.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Usuario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card p-4 shadow">
          <h3 class="mb-3 text-center">Registrar Usuario</h3>

          <?php if ($mensaje): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label for="usuario" class="form-label">Nombre de Usuario</label>
              <input type="text" class="form-control" id="usuario" name="usuario" required>
            </div>

            <div class="mb-3">
              <label for="contrasena" class="form-label">Contraseña</label>
              <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            </div>

            <div class="mb-3">
              <label for="rol" class="form-label">Rol</label>
              <select name="rol" id="rol" class="form-select" required>
                <option value="">-- Selecciona un rol --</option>
                <option value="admin">Admin</option>
                <option value="verificador">Verificador</option>
                <option value="secretaria">Secretaria</option>
                <option value="postulante">Postulante</option>
              </select>
            </div>

            <div class="text-center">
              <button type="submit" class="btn btn-primary">Registrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
