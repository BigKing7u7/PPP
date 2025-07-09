<?php
// panel/confirmar_inscripcion.php

require_once __DIR__ . '/../auth/check_session.php'; // Verifica que el usuario esté autenticado
require_once __DIR__ . '/../includes/check_roles.php'; // Verifica si el usuario tiene el rol adecuado

// Si el usuario no es 'admin' ni 'secretaria', redirigir a la página de login
if (!in_array($_SESSION['rol'], ['admin', 'secretaria'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/dbconnection.php'; // Conexión a la base de datos

// Si se recibe una solicitud POST y existe el 'confirmar_id', proceder a confirmar la inscripción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_id'])) {
    // Preparar la consulta para confirmar la inscripción
    $stmt = $dbh->prepare("UPDATE inscripcion SET estado = 'confirmado' WHERE inscripcion_id = :id");
    $stmt->execute([':id' => $_POST['confirmar_id']]);
    $mensaje = "Inscripción confirmada correctamente."; // Mensaje de éxito
}

// Obtener las inscripciones pendientes de confirmación
$stmt = $dbh->query("SELECT i.inscripcion_id, p.dni, p.nombres, p.apellido_paterno, p.apellido_materno, i.estado
                      FROM inscripcion i
                      JOIN postulante p ON i.postulante_id = p.postulante_id
                      WHERE i.estado != 'confirmado'");
$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Confirmar Inscripción'; // Título de la página
include __DIR__ . '/../includes/templates/header.php'; // Incluir el encabezado

?>
<div class="container py-4">
  <h3>Confirmar Inscripción</h3>
  
  <!-- Mostrar mensaje de confirmación si está disponible -->
  <?php if (!empty($mensaje)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>
  
  <!-- Si hay inscripciones pendientes, mostrar la tabla -->
  <?php if (count($inscripciones)): ?>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>DNI</th>
          <th>Nombre Completo</th>
          <th>Estado</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inscripciones as $i): ?>
        <tr>
          <td><?= htmlspecialchars($i['inscripcion_id']) ?></td>
          <td><?= htmlspecialchars($i['dni']) ?></td>
          <td><?= htmlspecialchars($i['apellido_paterno']) . ' ' . htmlspecialchars($i['apellido_materno']) . ', ' . htmlspecialchars($i['nombres']) ?></td>
          <td><?= ucfirst(htmlspecialchars($i['estado'])) ?></td>
          <td>
            <!-- Formulario para confirmar inscripción -->
            <form method="post" class="d-inline">
              <input type="hidden" name="confirmar_id" value="<?= htmlspecialchars($i['inscripcion_id']) ?>">
              <button type="submit" class="btn btn-success btn-sm">Confirmar</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="text-muted">No hay inscripciones pendientes.</p>
  <?php endif; ?>
</div>

<?php
// Incluir el pie de página
include __DIR__ . '/../includes/templates/footer.php';
?>
