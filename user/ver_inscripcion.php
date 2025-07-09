<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/dbconnection.php';

$codigo = $_GET['codigo'] ?? '';
$inscripcion = null;
$error = '';

if ($codigo) {
    // Obtener el ID de la inscripción a partir del código
    $stmt = $dbh->prepare("SELECT i.id, i.proceso_admision, i.modalidad_id, i.discapacidad, i.medio_difusion, p.dni, p.nombres, p.apellido_paterno, p.apellido_materno, p.email, p.telefono, p.foto, p.estado_inscripcion
        FROM inscripcion i
        JOIN postulante p ON i.postulante_id = p.id
        WHERE CONCAT(:pref, LPAD(i.id, 3, '0')) = :codigo");
    $codigo_prefix = CODIGO_PREFIX . CODIGO_YEAR . '-';
    $codigo_num = str_replace($codigo_prefix, '', $codigo);
    $stmt->execute([
        ':pref' => '',
        ':codigo' => $codigo_num
    ]);
    $inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inscripcion) {
        // Obtener carrera principal
        $stmt2 = $dbh->prepare("SELECT p.nombre_programa FROM inscripcion_programa ip JOIN programa p ON ip.programa_id = p.id WHERE ip.inscripcion_id = :ins AND ip.orden = 1");
        $stmt2->execute([':ins' => $inscripcion['id']]);
        $carrera = $stmt2->fetchColumn();
        // Obtener modalidad
        $stmt3 = $dbh->prepare("SELECT nombre FROM modalidad WHERE id = :id");
        $stmt3->execute([':id' => $inscripcion['modalidad_id']]);
        $modalidad = $stmt3->fetchColumn();
    } else {
        $error = 'No se encontró la inscripción con el código proporcionado.';
    }
} else {
    $error = 'No se proporcionó un código de inscripción.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Inscripción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="card-title mb-4 text-success">Detalle de Inscripción</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php else: ?>
                        <div class="mb-3 text-center">
                            <?php if (!empty($inscripcion['foto'])): ?>
                                <img src="../uploads/fotos/<?= htmlspecialchars($inscripcion['foto']) ?>" alt="Foto" class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-2" style="width:100px;height:100px;">
                                    <i class="bi bi-person-fill text-white fs-1"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <table class="table table-bordered bg-white">
                            <tr><th>Código</th><td><?= htmlspecialchars($codigo) ?></td></tr>
                            <tr><th>Nombres</th><td><?= htmlspecialchars($inscripcion['nombres']) ?></td></tr>
                            <tr><th>Apellidos</th><td><?= htmlspecialchars($inscripcion['apellido_paterno'] . ' ' . $inscripcion['apellido_materno']) ?></td></tr>
                            <tr><th>DNI</th><td><?= htmlspecialchars($inscripcion['dni']) ?></td></tr>
                            <tr><th>Carrera</th><td><?= htmlspecialchars($carrera ?? '-') ?></td></tr>
                            <tr><th>Modalidad</th><td><?= htmlspecialchars($modalidad ?? '-') ?></td></tr>
                            <tr><th>Email</th><td><?= htmlspecialchars($inscripcion['email']) ?></td></tr>
                            <tr><th>Teléfono</th><td><?= htmlspecialchars($inscripcion['telefono']) ?></td></tr>
                            <tr><th>Estado</th><td><?= $inscripcion['estado_inscripcion'] == 0 ? 'Inscrito' : 'Otro' ?></td></tr>
                        </table>
                        <a href="../index.php" class="btn btn-success mt-3">Volver al inicio</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html> 