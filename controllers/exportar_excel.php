// controllers/exportar_excel.php - Exporta registros a archivo Excel
<?php
require_once __DIR__ . '/../includes/dbconnection.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=postulantes.xls");

$stmt = $dbh->query("SELECT dni, nombres, apellido_paterno, apellido_materno FROM inscripcion");

echo "<table border='1'>";
echo "<tr><th>DNI</th><th>Nombres</th><th>Apellido Paterno</th><th>Apellido Materno</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['dni']}</td><td>{$row['nombres']}</td><td>{$row['apellido_paterno']}</td><td>{$row['apellido_materno']}</td>";
    echo "</tr>";
}
echo "</table>";
