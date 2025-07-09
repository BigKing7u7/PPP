<?php
// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/check_roles.php';
require_once '../auth/check_session.php';

// Mostrar errores en desarrollo (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar respuesta JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener datos JSON o POST
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $input = $_GET;
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'listar_postulantes':
            listarPostulantes($dbh, $input);
            break;
        case 'obtener_postulante':
            obtenerPostulante($dbh, $input);
            break;
        case 'crear_postulante':
            crearPostulante($dbh, $input);
            break;
        case 'actualizar_postulante':
            actualizarPostulante($dbh, $input);
            break;
        case 'cambiar_estado':
            cambiarEstadoPostulante($dbh, $input);
            break;
        case 'eliminar_postulante':
            eliminarPostulante($dbh, $input);
            break;
        case 'buscar_postulantes':
            buscarPostulantes($dbh, $input);
            break;
        case 'obtener_documentos':
            obtenerDocumentos($dbh, $input);
            break;
        case 'aprobar_documento':
            aprobarDocumento($dbh, $input);
            break;
        case 'rechazar_documento':
            rechazarDocumento($dbh, $input);
            break;
        case 'obtener_estadisticas':
            obtenerEstadisticas($dbh);
            break;
        case 'obtener_programas':
            obtenerProgramas($dbh);
            break;
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Función para obtener programas
function obtenerProgramas($dbh) {
    $sql = "SELECT id, nombre_programa FROM programa ORDER BY nombre_programa";
    $stmt = $dbh->query($sql);
    $programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $programas
    ]);
}

// Función para listar postulantes con paginación
function listarPostulantes($dbh, $params) {
    $page = intval($params['page'] ?? 1);
    $limit = intval($params['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    // Consulta para obtener postulantes con información del programa principal y estado de inscripción
    $sql = "SELECT 
                p.dni,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.email,
                p.telefono,
                p.estado_inscripcion,
                ip.programa_id,
                pr.nombre_programa,
                i.created_at AS fecha_inscripcion,
                CONCAT(p.nombres, ' ', p.apellido_paterno, ' ', p.apellido_materno) as nombre_completo
            FROM postulante p
            LEFT JOIN inscripcion i ON i.postulante_id = p.id
            LEFT JOIN inscripcion_programa ip ON ip.inscripcion_id = i.id AND ip.orden = 1
            LEFT JOIN programa pr ON ip.programa_id = pr.id
            ORDER BY i.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $postulantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener total de registros
    $totalStmt = $dbh->query("SELECT COUNT(*) as total FROM postulante");
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Formatear datos
    foreach ($postulantes as &$postulante) {
        $postulante['estado_texto'] = obtenerTextoEstado($postulante['estado_inscripcion']);
        $postulante['estado_clase'] = obtenerClaseEstado($postulante['estado_inscripcion']);
        $postulante['fecha_inscripcion'] = $postulante['fecha_inscripcion'] ? date('d/m/Y', strtotime($postulante['fecha_inscripcion'])) : '';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $postulantes,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => $total,
            'records_per_page' => $limit
        ]
    ]);
}

// Función para obtener un postulante específico
function obtenerPostulante($dbh, $params) {
    $dni = $params['dni'] ?? '';
    
    if (empty($dni)) {
        throw new Exception('DNI requerido');
    }
    
    $sql = "SELECT 
                p.*,
                p.estado_inscripcion,
                ip.programa_id,
                pr.nombre_programa,
                i.created_at AS fecha_inscripcion
            FROM postulante p
            LEFT JOIN inscripcion i ON i.postulante_id = p.id
            LEFT JOIN inscripcion_programa ip ON ip.inscripcion_id = i.id AND ip.orden = 1
            LEFT JOIN programa pr ON ip.programa_id = pr.id
            WHERE p.dni = :dni";
    
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':dni', $dni);
    $stmt->execute();
    
    $postulante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$postulante) {
        throw new Exception('Postulante no encontrado');
    }
    
    $postulante['estado_texto'] = obtenerTextoEstado($postulante['estado_inscripcion']);
    $postulante['estado_clase'] = obtenerClaseEstado($postulante['estado_inscripcion']);
    $postulante['fecha_inscripcion'] = $postulante['fecha_inscripcion'] ? date('d/m/Y', strtotime($postulante['fecha_inscripcion'])) : '';
    
    echo json_encode([
        'success' => true,
        'data' => $postulante
    ]);
}

// Función para crear nuevo postulante
function crearPostulante($dbh, $params) {
    // Esta función requiere lógica especial porque la estructura real usa varias tablas.
    throw new Exception('La creación de postulantes debe hacerse desde el flujo de inscripción, no desde aquí.');
}

// Función para actualizar postulante
function actualizarPostulante($dbh, $params) {
    // Esta función requiere lógica especial porque la estructura real usa varias tablas.
    throw new Exception('La actualización de postulantes debe hacerse desde el flujo de inscripción, no desde aquí.');
}

// Función para cambiar estado del postulante
function cambiarEstadoPostulante($dbh, $params) {
    $dni = $params['dni'] ?? '';
    $estado = $params['estado'] ?? '';
    
    if (empty($dni) || !in_array($estado, [0, 1, 2])) {
        throw new Exception('DNI y estado válido requeridos');
    }
    // Buscar el id del postulante
    $stmt = $dbh->prepare("SELECT id FROM postulante WHERE dni = :dni");
    $stmt->bindValue(':dni', $dni);
    $stmt->execute();
    $postulante_id = $stmt->fetchColumn();
    if (!$postulante_id) {
        throw new Exception('Postulante no encontrado');
    }
    // Cambiar estado en la tabla postulante
    $stmt = $dbh->prepare("UPDATE postulante SET estado_inscripcion = :estado WHERE id = :postulante_id");
    $stmt->bindValue(':estado', $estado, PDO::PARAM_INT);
    $stmt->bindValue(':postulante_id', $postulante_id);
    if ($stmt->execute()) {
        $estado_texto = obtenerTextoEstado($estado);
        echo json_encode([
            'success' => true,
            'message' => "Estado cambiado a: $estado_texto"
        ]);
    } else {
        throw new Exception('Error al cambiar estado');
    }
}

// Función para eliminar postulante
function eliminarPostulante($dbh, $params) {
    $dni = $params['dni'] ?? '';
    
    if (empty($dni)) {
        throw new Exception('DNI requerido');
    }
    // Buscar el id del postulante
    $stmt = $dbh->prepare("SELECT id FROM postulante WHERE dni = :dni");
    $stmt->bindValue(':dni', $dni);
    $stmt->execute();
    $postulante_id = $stmt->fetchColumn();
    if (!$postulante_id) {
        throw new Exception('Postulante no encontrado');
    }
    // Eliminar inscripciones y postulante
    $dbh->beginTransaction();
    $dbh->prepare("DELETE FROM inscripcion_programa WHERE inscripcion_id IN (SELECT id FROM inscripcion WHERE postulante_id = :postulante_id)")->execute([':postulante_id' => $postulante_id]);
    $dbh->prepare("DELETE FROM inscripcion WHERE postulante_id = :postulante_id")->execute([':postulante_id' => $postulante_id]);
    $dbh->prepare("DELETE FROM postulante WHERE id = :postulante_id")->execute([':postulante_id' => $postulante_id]);
    $dbh->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Postulante eliminado exitosamente'
    ]);
}

// Función para buscar postulantes
function buscarPostulantes($dbh, $params) {
    $search = $params['search'] ?? '';
    $page = intval($params['page'] ?? 1);
    $limit = intval($params['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT 
                p.dni,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.email,
                p.telefono,
                p.estado_inscripcion,
                ip.programa_id,
                pr.nombre_programa,
                i.created_at AS fecha_inscripcion,
                CONCAT(p.nombres, ' ', p.apellido_paterno, ' ', p.apellido_materno) as nombre_completo
            FROM postulante p
            LEFT JOIN inscripcion i ON i.postulante_id = p.id
            LEFT JOIN inscripcion_programa ip ON ip.inscripcion_id = i.id AND ip.orden = 1
            LEFT JOIN programa pr ON ip.programa_id = pr.id
            WHERE p.dni LIKE :search 
               OR p.nombres LIKE :search 
               OR p.apellido_paterno LIKE :search 
               OR p.apellido_materno LIKE :search
               OR p.email LIKE :search
               OR pr.nombre_programa LIKE :search
            ORDER BY i.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $dbh->prepare($sql);
    $searchTerm = "%$search%";
    $stmt->bindValue(':search', $searchTerm);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $postulantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    foreach ($postulantes as &$postulante) {
        $postulante['estado_texto'] = obtenerTextoEstado($postulante['estado_inscripcion']);
        $postulante['estado_clase'] = obtenerClaseEstado($postulante['estado_inscripcion']);
        $postulante['fecha_inscripcion'] = $postulante['fecha_inscripcion'] ? date('d/m/Y', strtotime($postulante['fecha_inscripcion'])) : '';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $postulantes
    ]);
}

// Función para obtener documentos del postulante
function obtenerDocumentos($dbh, $params) {
    $dni = $params['dni'] ?? '';
    
    if (empty($dni)) {
        throw new Exception('DNI requerido');
    }
    
    $sql = "SELECT 
                d.id,
                d.tipo_documento,
                d.nombre_archivo,
                d.ruta_archivo as archivo,
                d.estado_documento,
                d.fecha_subida,
                d.comentarios
            FROM documento d
            WHERE d.postulante_dni = :dni
            ORDER BY d.fecha_subida DESC";
    
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':dni', $dni);
    $stmt->execute();
    
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    foreach ($documentos as &$documento) {
        $documento['estado_texto'] = obtenerTextoEstadoDocumento($documento['estado_documento']);
        $documento['estado_clase'] = obtenerClaseEstadoDocumento($documento['estado_documento']);
        $documento['fecha_subida'] = date('d/m/Y H:i', strtotime($documento['fecha_subida']));
    }
    
    echo json_encode([
        'success' => true,
        'data' => $documentos
    ]);
}

// Función para aprobar documento
function aprobarDocumento($dbh, $params) {
    $documento_id = $params['documento_id'] ?? $params['document_id'] ?? '';
    $comentarios = $params['comentarios'] ?? '';
    
    if (empty($documento_id)) {
        throw new Exception('ID de documento requerido');
    }
    
    $sql = "UPDATE documento SET 
                estado_documento = 1,
                comentarios = :comentarios,
                fecha_revision = NOW()
            WHERE id = :documento_id";
    
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':documento_id', $documento_id);
    $stmt->bindValue(':comentarios', $comentarios);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Documento aprobado exitosamente'
        ]);
    } else {
        throw new Exception('Error al aprobar documento');
    }
}

// Función para rechazar documento
function rechazarDocumento($dbh, $params) {
    $documento_id = $params['documento_id'] ?? $params['document_id'] ?? '';
    $comentarios = $params['comentarios'] ?? '';
    
    if (empty($documento_id)) {
        throw new Exception('ID de documento requerido');
    }
    
    $sql = "UPDATE documento SET 
                estado_documento = 2,
                comentarios = :comentarios,
                fecha_revision = NOW()
            WHERE id = :documento_id";
    
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':documento_id', $documento_id);
    $stmt->bindValue(':comentarios', $comentarios);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Documento rechazado'
        ]);
    } else {
        throw new Exception('Error al rechazar documento');
    }
}

// Función para obtener estadísticas
function obtenerEstadisticas($dbh) {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado_inscripcion = 0 THEN 1 ELSE 0 END) as inscritos,
                SUM(CASE WHEN estado_inscripcion = 1 THEN 1 ELSE 0 END) as matriculados,
                SUM(CASE WHEN estado_inscripcion = 2 THEN 1 ELSE 0 END) as observados
            FROM postulante";
    
    $stmt = $dbh->query($sql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

// Funciones auxiliares
function obtenerTextoEstado($estado) {
    switch ($estado) {
        case 0: return 'Inscrito';
        case 1: return 'Matriculado';
        case 2: return 'Observado';
        default: return 'Desconocido';
    }
}

function obtenerClaseEstado($estado) {
    switch ($estado) {
        case 0: return 'inscrito';
        case 1: return 'matriculado';
        case 2: return 'observado';
        default: return 'inscrito';
    }
}

function obtenerTextoEstadoDocumento($estado) {
    switch ($estado) {
        case 0: return 'Pendiente';
        case 1: return 'Aprobado';
        case 2: return 'Rechazado';
        default: return 'Pendiente';
    }
}

function obtenerClaseEstadoDocumento($estado) {
    switch ($estado) {
        case 0: return 'pendiente';
        case 1: return 'aprobado';
        case 2: return 'rechazado';
        default: return 'pendiente';
    }
}
?>