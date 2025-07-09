<?php
// === /controllers/guardar_verificaciones.php ===
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../includes/check_roles.php';
require_once __DIR__ . '/../includes/dbconnection.php';

if (!in_array($_SESSION['rol'], ['admin', 'verificador'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verificados'])) {
    try {
        $dbh->beginTransaction();

        foreach ($_POST['verificados'] as $inscripcionId => $documentos) {
            foreach ($documentos as $documentoId => $valor) {
                $valor = ($valor == '1') ? 1 : 0;

                // Verificamos si ya existe el registro
                $stmt = $dbh->prepare("SELECT COUNT(*) FROM documento_inscripcion 
                                       WHERE inscripcion_id = :iid AND documento_id = :did");
                $stmt->execute([':iid' => $inscripcionId, ':did' => $documentoId]);

                if ($stmt->fetchColumn() > 0) {
                    // Actualizamos
                    $upd = $dbh->prepare("UPDATE documento_inscripcion 
                                          SET verificado = :v 
                                          WHERE inscripcion_id = :iid AND documento_id = :did");
                    $upd->execute([
                        ':v' => $valor,
                        ':iid' => $inscripcionId,
                        ':did' => $documentoId
                    ]);
                } else {
                    // Insertamos
                    $ins = $dbh->prepare("INSERT INTO documento_inscripcion 
                        (inscripcion_id, documento_id, ruta_archivo, verificado)
                        VALUES (:iid, :did, '', :v)");
                    $ins->execute([
                        ':iid' => $inscripcionId,
                        ':did' => $documentoId,
                        ':v' => $valor
                    ]);
                }
            }
        }

        $dbh->commit();
        echo "<script>alert('Verificaciones actualizadas correctamente'); window.location.href = '../panel/verificar_documentos.php';</script>";

    } catch (Exception $e) {
        $dbh->rollBack();
        echo "<p class='alert alert-danger'>Error al guardar verificaciones: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='alert alert-warning'>No se recibió ninguna verificación para procesar.</p>";
}
