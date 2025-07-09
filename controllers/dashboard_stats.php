<?php
// Controlador para estadísticas dinámicas del dashboard basadas en roles
// Asegurar que no haya espacios antes de esta línea

// Desactivar la salida de errores para evitar HTML en la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';
require_once '../includes/check_roles.php';
require_once '../auth/check_session.php';

// Configurar respuesta JSON desde el inicio
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
    // Usar la variable de conexión correcta ($dbh en lugar de $pdo)
    switch ($action) {
        case 'get_stats':
            getStatsByRole($dbh, $_SESSION['rol']);
            break;
        case 'get_activity':
            getRecentActivity($dbh, $_SESSION['rol']);
            break;
        case 'get_user_info':
            getUserInfo($dbh, $_SESSION['user_id']);
            break;
        case 'get_chart_data':
            getChartData($dbh, $_SESSION['rol']);
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

// Función para obtener estadísticas basadas en el rol del usuario
function getStatsByRole($dbh, $rol) {
    $stats = [];
    
    switch ($rol) {
        case 'admin':
            // Estadísticas completas para administrador
            $stats = getAdminStats($dbh);
            break;
        case 'secretaria':
            // Estadísticas para secretaria
            $stats = getSecretariaStats($dbh);
            break;
        case 'verificador':
            // Estadísticas para verificador
            $stats = getVerificadorStats($dbh);
            break;
        case 'postulante':
            // Estadísticas para postulante
            $stats = getPostulanteStats($dbh, $_SESSION['user_id']);
            break;
        default:
            $stats = getBasicStats($dbh);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

// Estadísticas para administrador
function getAdminStats($dbh) {
    try {
        // Estadísticas de postulantes
        $postulantesStmt = $dbh->query("SELECT 
            COUNT(*) as total_postulantes,
            SUM(CASE WHEN estado_inscripcion = 0 THEN 1 ELSE 0 END) as inscritos,
            SUM(CASE WHEN estado_inscripcion = 1 THEN 1 ELSE 0 END) as matriculados,
            SUM(CASE WHEN estado_inscripcion = 2 THEN 1 ELSE 0 END) as observados
        FROM postulante");
        $postulantes = $postulantesStmt->fetch(PDO::FETCH_ASSOC);
        
        // Estadísticas de programas
        $programasStmt = $dbh->query("SELECT COUNT(*) as total_programas FROM programa");
        $total_programas = $programasStmt->fetchColumn();
        
        // Estadísticas de documentos
        $docsStmt = $dbh->query("SELECT 
            COUNT(*) as total_documentos,
            SUM(CASE WHEN estado_documento = 0 THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado_documento = 1 THEN 1 ELSE 0 END) as aprobados,
            SUM(CASE WHEN estado_documento = 2 THEN 1 ELSE 0 END) as rechazados
        FROM documento");
        $documentos = $docsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Estadísticas de usuarios
        $usuariosStmt = $dbh->query("SELECT 
            COUNT(*) as total_usuarios,
            SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admins,
            SUM(CASE WHEN rol = 'secretaria' THEN 1 ELSE 0 END) as secretarias,
            SUM(CASE WHEN rol = 'verificador' THEN 1 ELSE 0 END) as verificadores
        FROM usuarios WHERE estado = 1");
        $usuarios = $usuariosStmt->fetch(PDO::FETCH_ASSOC);
        
        // Estadísticas de inscripciones por modalidad
        $modalidadesStmt = $dbh->query("SELECT 
            m.nombre as modalidad,
            COUNT(i.id) as total
        FROM inscripcion i
        JOIN modalidad m ON i.modalidad_id = m.id
        GROUP BY m.id, m.nombre");
        $modalidades = $modalidadesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estadísticas de inscripciones por programa
        $programasStmt = $dbh->query("SELECT 
            p.nombre_programa as programa,
            COUNT(ip.inscripcion_id) as total
        FROM inscripcion_programa ip
        JOIN programa p ON ip.programa_id = p.id
        GROUP BY p.id, p.nombre_programa
        ORDER BY total DESC
        LIMIT 5");
        $programas = $programasStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'postulantes' => $postulantes,
            'programas' => $total_programas,
            'documentos' => $documentos,
            'usuarios' => $usuarios,
            'modalidades' => $modalidades,
            'top_programas' => $programas,
            'tendencias' => calculateTrends($dbh)
        ];
    } catch (Exception $e) {
        return [
            'postulantes' => ['total_postulantes' => 0, 'inscritos' => 0, 'matriculados' => 0, 'observados' => 0],
            'programas' => 0,
            'documentos' => ['total_documentos' => 0, 'pendientes' => 0, 'aprobados' => 0, 'rechazados' => 0],
            'usuarios' => ['total_usuarios' => 0, 'admins' => 0, 'secretarias' => 0, 'verificadores' => 0],
            'modalidades' => [],
            'top_programas' => [],
            'tendencias' => []
        ];
    }
}

// Estadísticas para secretaria
function getSecretariaStats($dbh) {
    try {
        // Estadísticas de postulantes
        $postulantesStmt = $dbh->query("SELECT 
            COUNT(*) as total_postulantes,
            SUM(CASE WHEN estado_inscripcion = 0 THEN 1 ELSE 0 END) as inscritos,
            SUM(CASE WHEN estado_inscripcion = 1 THEN 1 ELSE 0 END) as matriculados,
            SUM(CASE WHEN estado_inscripcion = 2 THEN 1 ELSE 0 END) as observados
        FROM postulante");
        $postulantes = $postulantesStmt->fetch(PDO::FETCH_ASSOC);
        
        // Estadísticas de documentos pendientes
        $docsPendientesStmt = $dbh->query("SELECT COUNT(*) as documentos_pendientes FROM documento WHERE estado_documento = 0");
        $documentos_pendientes = $docsPendientesStmt->fetchColumn();
        
        // Estadísticas de inscripciones recientes
        $inscripcionesRecientesStmt = $dbh->query("SELECT COUNT(*) as inscripciones_hoy FROM inscripcion WHERE DATE(fecha_inscripcion) = CURDATE()");
        $inscripciones_hoy = $inscripcionesRecientesStmt->fetchColumn();
        
        // Estadísticas de programas activos
        $programasActivosStmt = $dbh->query("SELECT COUNT(*) as programas_activos FROM programa");
        $programas_activos = $programasActivosStmt->fetchColumn();
        
        return [
            'postulantes' => $postulantes,
            'documentos_pendientes' => $documentos_pendientes,
            'inscripciones_hoy' => $inscripciones_hoy,
            'programas_activos' => $programas_activos,
            'tendencias' => calculateTrends($dbh)
        ];
    } catch (Exception $e) {
        return [
            'postulantes' => ['total_postulantes' => 0, 'inscritos' => 0, 'matriculados' => 0, 'observados' => 0],
            'documentos_pendientes' => 0,
            'inscripciones_hoy' => 0,
            'programas_activos' => 0,
            'tendencias' => []
        ];
    }
}

// Estadísticas para verificador
function getVerificadorStats($dbh) {
    try {
        // Documentos pendientes de revisión
        $docsPendientesStmt = $dbh->query("SELECT COUNT(*) as documentos_pendientes FROM documento WHERE estado_documento = 0");
        $documentos_pendientes = $docsPendientesStmt->fetchColumn();
        
        // Documentos revisados hoy
        $docsRevisadosHoyStmt = $dbh->query("SELECT COUNT(*) as revisados_hoy FROM documento WHERE estado_documento IN (1,2) AND DATE(fecha_revision) = CURDATE()");
        $revisados_hoy = $docsRevisadosHoyStmt->fetchColumn();
        
        // Documentos aprobados
        $docsAprobadosStmt = $dbh->query("SELECT COUNT(*) as documentos_aprobados FROM documento WHERE estado_documento = 1");
        $documentos_aprobados = $docsAprobadosStmt->fetchColumn();
        
        // Documentos rechazados
        $docsRechazadosStmt = $dbh->query("SELECT COUNT(*) as documentos_rechazados FROM documento WHERE estado_documento = 2");
        $documentos_rechazados = $docsRechazadosStmt->fetchColumn();
        
        return [
            'documentos_pendientes' => $documentos_pendientes,
            'revisados_hoy' => $revisados_hoy,
            'documentos_aprobados' => $documentos_aprobados,
            'documentos_rechazados' => $documentos_rechazados,
            'tendencias' => calculateDocumentTrends($dbh)
        ];
    } catch (Exception $e) {
        return [
            'documentos_pendientes' => 0,
            'revisados_hoy' => 0,
            'documentos_aprobados' => 0,
            'documentos_rechazados' => 0,
            'tendencias' => []
        ];
    }
}

// Estadísticas para postulante
function getPostulanteStats($dbh, $user_id) {
    try {
        // Obtener DNI del postulante desde la tabla usuarios
        $userStmt = $dbh->prepare("SELECT username FROM usuarios WHERE id_usuario = ?");
        $userStmt->execute([$user_id]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'inscripciones' => 0,
                'documentos_subidos' => 0,
                'documentos_aprobados' => 0,
                'documentos_pendientes' => 0
            ];
        }
        
        $dni = $user['username'];
        
        // Contar inscripciones del postulante
        $inscripcionesStmt = $dbh->prepare("SELECT COUNT(*) FROM inscripcion WHERE dni = ?");
        $inscripcionesStmt->execute([$dni]);
        $inscripciones = $inscripcionesStmt->fetchColumn();
        
        // Contar documentos subidos
        $docsSubidosStmt = $dbh->prepare("SELECT COUNT(*) FROM documento WHERE dni = ?");
        $docsSubidosStmt->execute([$dni]);
        $documentos_subidos = $docsSubidosStmt->fetchColumn();
        
        // Contar documentos aprobados
        $docsAprobadosStmt = $dbh->prepare("SELECT COUNT(*) FROM documento WHERE dni = ? AND estado_documento = 1");
        $docsAprobadosStmt->execute([$dni]);
        $documentos_aprobados = $docsAprobadosStmt->fetchColumn();
        
        // Contar documentos pendientes
        $docsPendientesStmt = $dbh->prepare("SELECT COUNT(*) FROM documento WHERE dni = ? AND estado_documento = 0");
        $docsPendientesStmt->execute([$dni]);
        $documentos_pendientes = $docsPendientesStmt->fetchColumn();
        
        return [
            'inscripciones' => $inscripciones,
            'documentos_subidos' => $documentos_subidos,
            'documentos_aprobados' => $documentos_aprobados,
            'documentos_pendientes' => $documentos_pendientes
        ];
    } catch (Exception $e) {
        return [
            'inscripciones' => 0,
            'documentos_subidos' => 0,
            'documentos_aprobados' => 0,
            'documentos_pendientes' => 0
        ];
    }
}

// Estadísticas básicas para roles no definidos
function getBasicStats($dbh) {
    try {
        $postulantesStmt = $dbh->query("SELECT COUNT(*) as total_postulantes FROM postulante");
        $total_postulantes = $postulantesStmt->fetchColumn();
        
        return [
            'total_postulantes' => $total_postulantes
        ];
    } catch (Exception $e) {
        return [
            'total_postulantes' => 0
        ];
    }
}

// Función para obtener actividad reciente
function getRecentActivity($dbh, $rol) {
    $activity = [];
    
    switch ($rol) {
        case 'admin':
            $activity = getAdminActivity($dbh);
            break;
        case 'secretaria':
            $activity = getSecretariaActivity($dbh);
            break;
        case 'verificador':
            $activity = getVerificadorActivity($dbh);
            break;
        default:
            $activity = getBasicActivity($dbh);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $activity
    ]);
}

// Actividad para administrador
function getAdminActivity($dbh) {
    try {
        $activities = [];
        
        // Últimas inscripciones
        $inscripcionesStmt = $dbh->query("
            SELECT 
                i.dni,
                CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
                i.fecha_inscripcion,
                'Nueva inscripción' as descripcion
            FROM inscripcion i
            JOIN postulante p ON i.dni = p.dni
            ORDER BY i.fecha_inscripcion DESC
            LIMIT 5
        ");
        
        while ($row = $inscripcionesStmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'icon' => 'fa-user-plus',
                'color' => '#22c55e',
                'description' => $row['descripcion'] . ' - ' . $row['nombre_completo'],
                'time' => formatTimeAgo($row['fecha_inscripcion'])
            ];
        }
        
        // Últimos documentos subidos
        $documentosStmt = $dbh->query("
            SELECT 
                d.dni,
                CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
                d.fecha_subida,
                d.tipo_documento
            FROM documento d
            JOIN postulante p ON d.dni = p.dni
            ORDER BY d.fecha_subida DESC
            LIMIT 5
        ");
        
        while ($row = $documentosStmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'icon' => 'fa-file-upload',
                'color' => '#3b82f6',
                'description' => 'Documento subido: ' . $row['tipo_documento'] . ' - ' . $row['nombre_completo'],
                'time' => formatTimeAgo($row['fecha_subida'])
            ];
        }
        
        // Ordenar por fecha más reciente
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, 10);
        
    } catch (Exception $e) {
        return [
            [
                'icon' => 'fa-info-circle',
                'color' => '#3b82f6',
                'description' => 'No hay actividad reciente',
                'time' => 'Ahora'
            ]
        ];
    }
}

// Actividad para secretaria
function getSecretariaActivity($dbh) {
    try {
        $activities = [];
        
        // Últimas inscripciones
        $inscripcionesStmt = $dbh->query("
            SELECT 
                i.dni,
                CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
                i.fecha_inscripcion
            FROM inscripcion i
            JOIN postulante p ON i.dni = p.dni
            ORDER BY i.fecha_inscripcion DESC
            LIMIT 8
        ");
        
        while ($row = $inscripcionesStmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'icon' => 'fa-user-plus',
                'color' => '#22c55e',
                'description' => 'Nueva inscripción - ' . $row['nombre_completo'],
                'time' => formatTimeAgo($row['fecha_inscripcion'])
            ];
        }
        
        return $activities;
        
    } catch (Exception $e) {
        return [
            [
                'icon' => 'fa-info-circle',
                'color' => '#3b82f6',
                'description' => 'No hay actividad reciente',
                'time' => 'Ahora'
            ]
        ];
    }
}

// Actividad para verificador
function getVerificadorActivity($dbh) {
    try {
        $activities = [];
        
        // Últimos documentos revisados
        $documentosStmt = $dbh->query("
            SELECT 
                d.dni,
                CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
                d.fecha_revision,
                d.tipo_documento,
                d.estado_documento
            FROM documento d
            JOIN postulante p ON d.dni = p.dni
            WHERE d.estado_documento IN (1, 2)
            ORDER BY d.fecha_revision DESC
            LIMIT 10
        ");
        
        while ($row = $documentosStmt->fetch(PDO::FETCH_ASSOC)) {
            $estado = $row['estado_documento'] == 1 ? 'aprobado' : 'rechazado';
            $color = $row['estado_documento'] == 1 ? '#22c55e' : '#ef4444';
            $icon = $row['estado_documento'] == 1 ? 'fa-check-circle' : 'fa-times-circle';
            
            $activities[] = [
                'icon' => $icon,
                'color' => $color,
                'description' => 'Documento ' . $estado . ': ' . $row['tipo_documento'] . ' - ' . $row['nombre_completo'],
                'time' => formatTimeAgo($row['fecha_revision'])
            ];
        }
        
        return $activities;
        
    } catch (Exception $e) {
        return [
            [
                'icon' => 'fa-info-circle',
                'color' => '#3b82f6',
                'description' => 'No hay actividad reciente',
                'time' => 'Ahora'
            ]
        ];
    }
}

// Actividad básica
function getBasicActivity($dbh) {
    return [
        [
            'icon' => 'fa-info-circle',
            'color' => '#3b82f6',
            'description' => 'Bienvenido al sistema',
            'time' => 'Ahora'
        ]
    ];
}

// Función para obtener información del usuario
function getUserInfo($dbh, $user_id) {
    try {
        $stmt = $dbh->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generar iniciales
            $nombre_completo = $user['nombre_completo'] ?: $user['username'];
            $nombres = explode(' ', $nombre_completo);
            $iniciales = strtoupper(substr($nombres[0], 0, 1));
            if (count($nombres) > 1) {
                $iniciales .= strtoupper(substr($nombres[1], 0, 1));
            } else {
                $iniciales = strtoupper(substr($user['username'], 0, 2));
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'id_usuario' => $user['id_usuario'],
                    'username' => $user['username'],
                    'nombre_completo' => $nombre_completo,
                    'rol' => $user['rol'],
                    'iniciales' => $iniciales,
                    'email' => $user['email'] ?? '',
                    'estado' => $user['estado']
                ]
            ]);
        } else {
            throw new Exception('Usuario no encontrado');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Función para obtener datos de gráficos
function getChartData($dbh, $rol) {
    $chartData = [];
    
    switch ($rol) {
        case 'admin':
            $chartData = getAdminChartData($dbh);
            break;
        case 'secretaria':
            $chartData = getSecretariaChartData($dbh);
            break;
        case 'verificador':
            $chartData = getVerificadorChartData($dbh);
            break;
        default:
            $chartData = getBasicChartData($dbh);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $chartData
    ]);
}

// Datos de gráficos para administrador
function getAdminChartData($dbh) {
    try {
        // Inscripciones por mes (últimos 6 meses)
        $inscripcionesMensuales = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('Y-m', strtotime("-$i months"));
            $stmt = $dbh->prepare("SELECT COUNT(*) FROM inscripcion WHERE DATE_FORMAT(fecha_inscripcion, '%Y-%m') = ?");
            $stmt->execute([$mes]);
            $inscripcionesMensuales[] = [
                'mes' => date('M Y', strtotime("-$i months")),
                'total' => $stmt->fetchColumn()
            ];
        }
        
        return [
            'inscripciones_mensuales' => $inscripcionesMensuales
        ];
        
    } catch (Exception $e) {
        return [
            'inscripciones_mensuales' => []
        ];
    }
}

// Datos de gráficos para secretaria
function getSecretariaChartData($dbh) {
    try {
        // Inscripciones de hoy
        $hoyStmt = $dbh->query("SELECT COUNT(*) FROM inscripcion WHERE DATE(fecha_inscripcion) = CURDATE()");
        $hoy = $hoyStmt->fetchColumn();
        
        // Inscripciones de ayer
        $ayerStmt = $dbh->query("SELECT COUNT(*) FROM inscripcion WHERE DATE(fecha_inscripcion) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
        $ayer = $ayerStmt->fetchColumn();
        
        return [
            'hoy' => $hoy,
            'ayer' => $ayer
        ];
        
    } catch (Exception $e) {
        return [
            'hoy' => 0,
            'ayer' => 0
        ];
    }
}

// Datos de gráficos para verificador
function getVerificadorChartData($dbh) {
    try {
        // Documentos por estado
        $estadosStmt = $dbh->query("
            SELECT 
                estado_documento,
                COUNT(*) as total
            FROM documento
            GROUP BY estado_documento
        ");
        $estados = $estadosStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'estados' => $estados
        ];
        
    } catch (Exception $e) {
        return [
            'estados' => []
        ];
    }
}

// Datos básicos de gráficos
function getBasicChartData($dbh) {
    return [];
}

// Función para calcular tendencias
function calculateTrends($dbh) {
    try {
        // Comparar con el mes anterior
        $mesActualStmt = $dbh->query("SELECT COUNT(*) FROM inscripcion WHERE DATE_FORMAT(fecha_inscripcion, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $mesActual = $mesActualStmt->fetchColumn();
        
        $mesAnteriorStmt = $dbh->query("SELECT COUNT(*) FROM inscripcion WHERE DATE_FORMAT(fecha_inscripcion, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')");
        $mesAnterior = $mesAnteriorStmt->fetchColumn();
        
        if ($mesAnterior > 0) {
            $porcentaje = (($mesActual - $mesAnterior) / $mesAnterior) * 100;
        } else {
            $porcentaje = $mesActual > 0 ? 100 : 0;
        }
        
        return [
            'total_postulantes' => [
                'percentage' => abs(round($porcentaje, 1)),
                'direction' => $porcentaje >= 0 ? 'positive' : 'negative'
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'total_postulantes' => [
                'percentage' => 0,
                'direction' => 'neutral'
            ]
        ];
    }
}

// Función para calcular tendencias de documentos
function calculateDocumentTrends($dbh) {
    try {
        // Documentos revisados hoy vs ayer
        $hoyStmt = $dbh->query("SELECT COUNT(*) FROM documento WHERE estado_documento IN (1,2) AND DATE(fecha_revision) = CURDATE()");
        $hoy = $hoyStmt->fetchColumn();
        
        $ayerStmt = $dbh->query("SELECT COUNT(*) FROM documento WHERE estado_documento IN (1,2) AND DATE(fecha_revision) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
        $ayer = $ayerStmt->fetchColumn();
        
        if ($ayer > 0) {
            $porcentaje = (($hoy - $ayer) / $ayer) * 100;
        } else {
            $porcentaje = $hoy > 0 ? 100 : 0;
        }
        
        return [
            'documentos_pendientes' => [
                'percentage' => abs(round($porcentaje, 1)),
                'direction' => $porcentaje >= 0 ? 'positive' : 'negative'
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'documentos_pendientes' => [
                'percentage' => 0,
                'direction' => 'neutral'
            ]
        ];
    }
}

// Función para formatear tiempo transcurrido
function formatTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Hace un momento';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Hace $minutes minuto" . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Hace $hours hora" . ($hours > 1 ? 's' : '');
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "Hace $days día" . ($days > 1 ? 's' : '');
    } else {
        return date('d/m/Y H:i', $time);
    }
}
?> 