<?php
// Controlador simplificado para estadísticas del dashboard
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

// Configurar respuesta JSON
header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'get_stats':
            getBasicStats($dbh);
            break;
        case 'get_activity':
            getBasicActivity($dbh);
            break;
        case 'get_user_info':
            getUserInfo($dbh);
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

// Función para obtener estadísticas básicas
function getBasicStats($dbh) {
    try {
        // Estadísticas de postulantes
        $postulantesStmt = $dbh->query("SELECT COUNT(*) as total FROM postulante");
        $total_postulantes = $postulantesStmt->fetchColumn();
        
        // Estadísticas de programas
        $programasStmt = $dbh->query("SELECT COUNT(*) as total FROM programa");
        $total_programas = $programasStmt->fetchColumn();
        
        // Estadísticas de documentos
        $docsStmt = $dbh->query("SELECT COUNT(*) as total FROM documento");
        $total_documentos = $docsStmt->fetchColumn();
        
        // Estadísticas de inscripciones
        $inscripcionesStmt = $dbh->query("SELECT COUNT(*) as total FROM inscripcion");
        $total_inscripciones = $inscripcionesStmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_postulantes' => $total_postulantes,
                'total_programas' => $total_programas,
                'total_documentos' => $total_documentos,
                'total_inscripciones' => $total_inscripciones
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_postulantes' => 0,
                'total_programas' => 0,
                'total_documentos' => 0,
                'total_inscripciones' => 0
            ]
        ]);
    }
}

// Función para obtener actividad básica
function getBasicActivity($dbh) {
    try {
        $activities = [
            [
                'icon' => 'fa-info-circle',
                'color' => '#22c55e',
                'description' => 'Sistema iniciado correctamente',
                'time' => 'Ahora'
            ],
            [
                'icon' => 'fa-check-circle',
                'color' => '#3b82f6',
                'description' => 'Dashboard cargado',
                'time' => 'Hace 1 minuto'
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $activities
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => true,
            'data' => [
                [
                    'icon' => 'fa-info-circle',
                    'color' => '#3b82f6',
                    'description' => 'No hay actividad reciente',
                    'time' => 'Ahora'
                ]
            ]
        ]);
    }
}

// Función para obtener información del usuario
function getUserInfo($dbh) {
    try {
        // Simular datos de usuario
        $userData = [
            'id_usuario' => 1,
            'username' => 'admin',
            'nombre_completo' => 'Administrador del Sistema',
            'rol' => 'admin',
            'iniciales' => 'AS',
            'email' => 'admin@nos.edu.pe',
            'estado' => 1
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $userData
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?> 