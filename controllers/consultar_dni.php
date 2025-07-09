// controllers/consultar_dni.php - Controlador que consulta API y retorna JSON
<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../includes/check_roles.php';

if (!in_array($_SESSION['rol'], ['admin', 'verificador', 'secretaria'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$dni = $_GET['dni'] ?? '';
if (!preg_match('/^\d{8}$/', $dni)) {
    http_response_code(400);
    echo json_encode(['error' => 'DNI inválido']);
    exit;
}

require_once __DIR__ . '/../includes/api_consultas.php';
$response = consultar_dni_api($dni);

echo json_encode($response);
