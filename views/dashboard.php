<?php  
// Incluir el archivo de conexión
require_once '../config.php';
require_once '../includes/check_roles.php';
require_once '../auth/check_session.php';

// Obtener datos del usuario actual
$rol = $_SESSION['rol'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$nombre_completo = $_SESSION['nombre'] ?? 'Usuario';

// Obtener datos adicionales del usuario desde la base de datos
try {
    $stmt = $dbh->prepare("SELECT * FROM usuarios WHERE id_usuario = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        $nombre_completo = $usuario['nombre_completo'] ?: $username;
    }
} catch (Exception $e) {
    // Si hay error, usar datos de sesión
    $nombre_completo = $_SESSION['nombre'] ?? $username;
}

// Generar iniciales del usuario para el avatar
$iniciales = '';
if ($nombre_completo) {
    $nombres = explode(' ', $nombre_completo);
    $iniciales = strtoupper(substr($nombres[0], 0, 1));
    if (count($nombres) > 1) {
        $iniciales .= strtoupper(substr($nombres[1], 0, 1));
    }
} else {
    $iniciales = strtoupper(substr($username, 0, 2));
}

// Definir configuraciones específicas por rol
$roleConfig = [
    'admin' => [
        'title' => 'Dashboard Administrativo',
        'welcome_message' => 'Panel de control completo del sistema de admisión',
        'stats_cards' => [
            ['id' => 'total_postulantes', 'title' => 'Total Postulantes', 'icon' => 'fa-users', 'color' => '#22c55e'],
            ['id' => 'inscritos', 'title' => 'Inscritos', 'icon' => 'fa-user-check', 'color' => '#3b82f6'],
            ['id' => 'matriculados', 'title' => 'Matriculados', 'icon' => 'fa-user-graduate', 'color' => '#16a34a'],
            ['id' => 'observados', 'title' => 'En Observación', 'icon' => 'fa-exclamation-triangle', 'color' => '#f59e0b'],
            ['id' => 'total_programas', 'title' => 'Programas Activos', 'icon' => 'fa-graduation-cap', 'color' => '#8b5cf6'],
            ['id' => 'total_documentos', 'title' => 'Documentos', 'icon' => 'fa-file-alt', 'color' => '#06b6d4']
        ],
        'quick_actions' => [
            ['title' => 'Nueva Inscripción', 'icon' => 'fa-user-plus', 'url' => 'inscripcion.php', 'color' => '#22c55e'],
            ['title' => 'Ver Postulantes', 'icon' => 'fa-search', 'url' => 'ver_postulantes.php', 'color' => '#3b82f6'],
            ['title' => 'Gestionar Programas', 'icon' => 'fa-cogs', 'url' => '#', 'color' => '#8b5cf6'],
            ['title' => 'Reportes', 'icon' => 'fa-chart-bar', 'url' => '#', 'color' => '#f59e0b'],
            ['title' => 'Usuarios', 'icon' => 'fa-users-cog', 'url' => '#', 'color' => '#06b6d4'],
            ['title' => 'Auditoría', 'icon' => 'fa-shield-alt', 'url' => '#', 'color' => '#ef4444']
        ]
    ],
    'secretaria' => [
        'title' => 'Dashboard Secretaría',
        'welcome_message' => 'Gestión de inscripciones y postulantes',
        'stats_cards' => [
            ['id' => 'total_postulantes', 'title' => 'Total Postulantes', 'icon' => 'fa-users', 'color' => '#22c55e'],
            ['id' => 'inscritos', 'title' => 'Inscritos', 'icon' => 'fa-user-check', 'color' => '#3b82f6'],
            ['id' => 'matriculados', 'title' => 'Matriculados', 'icon' => 'fa-user-graduate', 'color' => '#16a34a'],
            ['id' => 'inscripciones_hoy', 'title' => 'Inscripciones Hoy', 'icon' => 'fa-calendar-day', 'color' => '#f59e0b'],
            ['id' => 'documentos_pendientes', 'title' => 'Documentos Pendientes', 'icon' => 'fa-clock', 'color' => '#ef4444'],
            ['id' => 'programas_activos', 'title' => 'Programas Activos', 'icon' => 'fa-graduation-cap', 'color' => '#8b5cf6']
        ],
        'quick_actions' => [
            ['title' => 'Nueva Inscripción', 'icon' => 'fa-user-plus', 'url' => 'inscripcion.php', 'color' => '#22c55e'],
            ['title' => 'Ver Postulantes', 'icon' => 'fa-search', 'url' => 'ver_postulantes.php', 'color' => '#3b82f6'],
            ['title' => 'Revisar Documentos', 'icon' => 'fa-file-check', 'url' => 'ver_postulantes.php?filter=documentos', 'color' => '#f59e0b'],
            ['title' => 'Generar Reporte', 'icon' => 'fa-chart-bar', 'url' => '#', 'color' => '#8b5cf6']
        ]
    ],
    'verificador' => [
        'title' => 'Dashboard Verificador',
        'welcome_message' => 'Validación y revisión de documentos',
        'stats_cards' => [
            ['id' => 'documentos_pendientes', 'title' => 'Documentos Pendientes', 'icon' => 'fa-clock', 'color' => '#ef4444'],
            ['id' => 'revisados_hoy', 'title' => 'Revisados Hoy', 'icon' => 'fa-calendar-check', 'color' => '#22c55e'],
            ['id' => 'documentos_aprobados', 'title' => 'Documentos Aprobados', 'icon' => 'fa-check-circle', 'color' => '#16a34a'],
            ['id' => 'documentos_rechazados', 'title' => 'Documentos Rechazados', 'icon' => 'fa-times-circle', 'color' => '#dc2626']
        ],
        'quick_actions' => [
            ['title' => 'Revisar Documentos', 'icon' => 'fa-file-check', 'url' => 'ver_postulantes.php?filter=documentos', 'color' => '#22c55e'],
            ['title' => 'Ver Postulantes', 'icon' => 'fa-search', 'url' => 'ver_postulantes.php', 'color' => '#3b82f6'],
            ['title' => 'Reporte de Validación', 'icon' => 'fa-chart-bar', 'url' => '#', 'color' => '#f59e0b']
        ]
    ],
    'postulante' => [
        'title' => 'Mi Dashboard',
        'welcome_message' => 'Seguimiento de tu proceso de admisión',
        'stats_cards' => [
            ['id' => 'inscripciones', 'title' => 'Mis Inscripciones', 'icon' => 'fa-file-alt', 'color' => '#22c55e'],
            ['id' => 'documentos_subidos', 'title' => 'Documentos Subidos', 'icon' => 'fa-upload', 'color' => '#3b82f6'],
            ['id' => 'documentos_aprobados', 'title' => 'Documentos Aprobados', 'icon' => 'fa-check-circle', 'color' => '#16a34a'],
            ['id' => 'documentos_pendientes', 'title' => 'Documentos Pendientes', 'icon' => 'fa-clock', 'color' => '#f59e0b']
        ],
        'quick_actions' => [
            ['title' => 'Mi Perfil', 'icon' => 'fa-user', 'url' => '#', 'color' => '#22c55e'],
            ['title' => 'Subir Documentos', 'icon' => 'fa-upload', 'url' => '#', 'color' => '#3b82f6'],
            ['title' => 'Ver Estado', 'icon' => 'fa-info-circle', 'url' => '#', 'color' => '#f59e0b']
        ]
    ]
];

// Obtener configuración del rol actual
$config = $roleConfig[$rol] ?? $roleConfig['admin'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['title']; ?> - NOS</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar-overlay"></div>
    <!-- Header -->
    <header class="header">
        <div class="header-inner">
            <div class="logo-section">
                <button class="hamburger" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">🎓</div>
                <div class="brand-name"><?php echo $config['title']; ?> - NOS</div>
            </div>
            <div class="header-right">
                <div class="theme-toggle" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="user-profile" onclick="toggleProfileMenu()">
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                    <span><?php echo htmlspecialchars($nombre_completo); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <!-- Profile Dropdown Menu -->
                <div class="profile-menu" id="profile-menu" aria-modal="true" role="dialog">
                    <ul>
                        <li><a href="#"><i class="fas fa-user"></i> Mi Perfil</a></li>
                        <li><a href="#"><i class="fas fa-cog"></i> Configuración</a></li>
                        <li><a href="#"><i class="fas fa-bell"></i> Notificaciones</a></li>
                        <li><hr></li>
                        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-profile">
            <div class="profile-img"><?php echo $iniciales; ?></div>
            <div class="profile-name"><?php echo htmlspecialchars($nombre_completo); ?></div>
            <div class="profile-role"><?php echo ucfirst($rol); ?></div>
            <div class="profile-status">
                <span class="status-dot online"></span>
                En línea
            </div>
        </div>

        <nav>
            <div class="sidebar-section">
                <div class="section-title">Principal</div>
                <div class="sidebar-item active" onclick="navigateTo('dashboard')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tablero</span>
                    </div>
                </div>

                <!-- Menu de Recepción (Visible para todos los roles) -->
                <button class="sidebar-item" onclick="toggleSubmenu('recepcion')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Admisión</span>
                    </div>
                    <span class="submenu-toggle" id="recepcion-toggle">▼</span>
                </button>
                <div class="sidebar-subitems" id="recepcion-items">
                    <?php if ($rol != 'postulante'): ?>
                    <a href="inscripcion.php" class="sidebar-subitem">
                        <i class="fas fa-user-plus"></i> Inscripción
                    </a>
                    <a href="ver_postulantes.php" class="sidebar-subitem">
                        <i class="fas fa-users"></i> Ver Postulantes
                        <span class="badge" id="badge-postulantes">0</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($rol == 'admin' || $rol == 'secretaria'): ?>
                    <a href="ver_postulantes.php?filter=matriculados" class="sidebar-subitem">
                        <i class="fas fa-user-graduate"></i> Ver Matriculados
                        <span class="badge" id="badge-matriculados">0</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Menu Académico -->
            <?php if ($rol == 'admin'): ?>
            <div class="sidebar-section">
                <div class="section-title">Académico</div>
                <button class="sidebar-item" onclick="toggleSubmenu('academico')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Académico</span>
                    </div>
                    <span class="submenu-toggle" id="academico-toggle">▼</span>
                </button>
                <div class="sidebar-subitems" id="academico-items">
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-list"></i> Gestionar Programas
                        <span class="badge" id="badge-programas">0</span>
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-cogs"></i> Configurar Modalidades
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-percentage"></i> Tipos de Exoneración
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-calendar-alt"></i> Períodos de Admisión
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-sliders-h"></i> Configuración General
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Menú de Reportes (Solo visible para Admin y Secretaria) -->
            <?php if ($rol == 'admin' || $rol == 'secretaria'): ?>
            <div class="sidebar-section">
                <div class="section-title">Reportes</div>
                <button class="sidebar-item" onclick="toggleSubmenu('reportes')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes</span>
                    </div>
                    <span class="submenu-toggle" id="reportes-toggle">▼</span>
                </button>
                <div class="sidebar-subitems" id="reportes-items">
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-list-alt"></i> Listado de Postulantes
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-chart-pie"></i> Inscripciones por Programa
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-chart-line"></i> Inscripciones por Modalidad
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-file-alt"></i> Estados de Documentos
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-chart-area"></i> Resumen Ejecutivo
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-download"></i> Exportar a Excel/PDF
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Menú Auditoría (Solo visible para Admin) -->
            <?php if ($rol == 'admin'): ?>
            <div class="sidebar-section">
                <div class="section-title">Auditoría</div>
                <button class="sidebar-item" onclick="toggleSubmenu('auditoria')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-shield-alt"></i>
                        <span>Auditoría</span>
                    </div>
                    <span class="submenu-toggle" id="auditoria-toggle">▼</span>
                </button>
                <div class="sidebar-subitems" id="auditoria-items">
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-user-clock"></i> Actividad de Usuarios
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-history"></i> Cambios en Registros
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-sign-in-alt"></i> Accesos al Sistema
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-database"></i> Backup de Datos
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Menú Gestión Documentaria (Visible para todos los roles excepto postulante) -->
            <?php if ($rol != 'postulante'): ?>
            <div class="sidebar-section">
                <div class="section-title">Gestión Documentaria</div>
                <button class="sidebar-item" onclick="toggleSubmenu('documentaria')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-file-alt"></i>
                        <span>Gestión Documentaria</span>
                    </div>
                    <span class="submenu-toggle" id="documentaria-toggle">▼</span>
                </button>
                <div class="sidebar-subitems" id="documentaria-items">
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-cog"></i> Configurar Documentos
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-check-circle"></i> Validar Documentos
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-upload"></i> Ver Archivos Subidos
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-clipboard-check"></i> Estados de Validación
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Menú Seguridad y Usuarios (Solo Admin) -->
            <?php if ($rol == 'admin'): ?>
            <div class="sidebar-section">
                <div class="section-title">Seguridad y Usuarios</div>
                <button class="sidebar-item" onclick="toggleSubmenu('seguridad')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-lock"></i>
                        <span>Seguridad y Usuarios</span>
                    </div>
                    <span class="submenu-toggle" id="seguridad-toggle">▼</span>
                </button>
                <div class="sidebar-subitems" id="seguridad-items">
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-users-cog"></i> Gestionar Usuarios
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-user-tag"></i> Asignar Roles
                    </a>
                    <a href="#" class="sidebar-subitem">
                        <i class="fas fa-key"></i> Cambiar Contraseñas
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>¡Bienvenido, <?php echo htmlspecialchars(explode(' ', $nombre_completo)[0]); ?>!</h1>
                <p><?php echo $config['welcome_message']; ?> - <?php echo date('d/m/Y'); ?></p>
            </div>
            <div class="welcome-actions">
                <?php if ($rol != 'postulante'): ?>
                <button class="btn btn-primary" onclick="location.href='inscripcion.php'">
                    <i class="fas fa-plus"></i> Nueva Inscripción
                </button>
                <button class="btn btn-secondary" onclick="location.href='ver_postulantes.php'">
                    <i class="fas fa-users"></i> Ver Postulantes
                </button>
                <?php else: ?>
                <button class="btn btn-primary" onclick="location.href='#'">
                    <i class="fas fa-user"></i> Mi Perfil
                </button>
                <button class="btn btn-secondary" onclick="location.href='#'">
                    <i class="fas fa-upload"></i> Subir Documentos
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid" id="statsGrid">
            <!-- Las tarjetas se cargarán dinámicamente según el rol -->
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="dashboard-grid">
            <!-- Quick Actions -->
            <div class="quick-actions-section">
                <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                <div class="quick-actions-grid" id="quickActionsGrid">
                    <!-- Las acciones se cargarán dinámicamente según el rol -->
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity-section">
                <h3><i class="fas fa-clock"></i> Actividad Reciente</h3>
                <div class="activity-list" id="activityList">
                    <!-- La actividad se cargará dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Audit Table (Solo para Admin) -->
        <?php if ($rol == 'admin'): ?>
        <section class="audit-section">
            <header class="audit-header">
                <div class="audit-title">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Registro de Auditoría</h3>
                </div>
                <div class="audit-controls">
                    <input type="text" class="search-box" placeholder="Buscar en auditoría..." id="searchInput" onkeyup="searchTable()">
                    <button class="btn btn-secondary" onclick="exportAuditData()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </header>
            <table class="audit-table" id="auditTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Usuario</th>
                        <th>Fecha y Hora</th>
                        <th>IP</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>465</td>
                        <td><span class="badge badge-logout">LOGOUT</span></td>
                        <td><?php echo htmlspecialchars($username); ?></td>
                        <td><?php echo date('d/m/Y H:i:s'); ?></td>
                        <td><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></td>
                        <td><button class="view-btn" data-id="465">👁️ Ver</button></td>
                    </tr>
                    <tr>
                        <td>464</td>
                        <td><span class="badge badge-login">LOGIN</span></td>
                        <td><?php echo htmlspecialchars($username); ?></td>
                        <td><?php echo date('d/m/Y H:i:s', strtotime('-5 minutes')); ?></td>
                        <td><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></td>
                        <td><button class="view-btn" data-id="464">👁️ Ver</button></td>
                    </tr>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
        // Configuración del rol actual
        const currentRole = '<?php echo $rol; ?>';
        const roleConfig = <?php echo json_encode($config); ?>;
        
        // Funciones adicionales para el dashboard dinámico
        function navigateTo(page) {
            console.log('Navegando a:', page);
        }

        function exportAuditData() {
            alert('Funcionalidad de exportación en desarrollo');
        }

        // Renderizar tarjetas de estadísticas según el rol
        function renderStatsCards() {
            const statsGrid = document.getElementById('statsGrid');
            if (!statsGrid) return;

            statsGrid.innerHTML = roleConfig.stats_cards.map(card => `
                <div class="stat-card" data-stat="${card.id}" data-tooltip="Haz clic para ver detalles">
                    <div class="stat-icon" style="background: linear-gradient(135deg, ${card.color}, ${adjustColor(card.color, -20)})">
                        <i class="fas ${card.icon}"></i>
                    </div>
                    <div class="stat-info">
                        <h3 data-value="0">0</h3>
                        <p>${card.title}</p>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <span data-trend="${card.id}">Cargando...</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Renderizar acciones rápidas según el rol
        function renderQuickActions() {
            const quickActionsGrid = document.getElementById('quickActionsGrid');
            if (!quickActionsGrid) return;

            quickActionsGrid.innerHTML = roleConfig.quick_actions.map(action => `
                <div class="quick-action-card" onclick="location.href='${action.url}'">
                    <i class="fas ${action.icon}" style="color: ${action.color}"></i>
                    <span>${action.title}</span>
                </div>
            `).join('');
        }

        // Función para ajustar color (hacer más oscuro)
        function adjustColor(color, amount) {
            const hex = color.replace('#', '');
            const r = Math.max(0, Math.min(255, parseInt(hex.substr(0, 2), 16) + amount));
            const g = Math.max(0, Math.min(255, parseInt(hex.substr(2, 2), 16) + amount));
            const b = Math.max(0, Math.min(255, parseInt(hex.substr(4, 2), 16) + amount));
            return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
        }

        // Cargar datos dinámicos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Renderizar elementos según el rol
            renderStatsCards();
            renderQuickActions();
            
            // Cargar datos dinámicos
            loadDynamicData();
            
            // Configurar actualizaciones automáticas
            setInterval(loadDynamicData, 300000); // Actualizar cada 5 minutos
        });

        // Función para cargar datos dinámicos
        async function loadDynamicData() {
            try {
                // Cargar estadísticas
                await updateStats();
                
                // Cargar actividad reciente
                await updateRecentActivity();
                
                // Cargar información del usuario
                await updateUserInfo();
                
            } catch (error) {
                console.error('Error loading dynamic data:', error);
                showNotification('Error al cargar datos dinámicos', 'error');
            }
        }

        // Función para actualizar estadísticas
        async function updateStats() {
            try {
                const response = await fetch('../controllers/dashboard_stats_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'get_stats' })
                });

                const data = await response.json();
                
                if (data.success) {
                    updateStatCards(data.data);
                    updateSidebarBadges(data.data);
                }
                
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }

        // Función para actualizar tarjetas de estadísticas
        function updateStatCards(statsData) {
            roleConfig.stats_cards.forEach(card => {
                const statCard = document.querySelector(`[data-stat="${card.id}"]`);
                if (statCard) {
                    const statValue = statCard.querySelector('h3');
                    if (statValue) {
                        let value = 0;
                        
                        // Buscar el valor en los datos según la estructura simplificada
                        if (statsData[card.id]) {
                            value = statsData[card.id];
                        } else if (statsData.postulantes && statsData.postulantes[card.id]) {
                            value = statsData.postulantes[card.id];
                        } else if (statsData.documentos && statsData.documentos[card.id]) {
                            value = statsData.documentos[card.id];
                        }
                        
                        // Animar el cambio de número
                        animateNumberChange(statValue, parseInt(statValue.getAttribute('data-value') || '0'), value);
                        statValue.setAttribute('data-value', value);
                    }
                }
            });
        }

        // Función para actualizar badges del sidebar
        function updateSidebarBadges(statsData) {
            const badges = {
                'badge-postulantes': statsData.total_postulantes || 0,
                'badge-matriculados': statsData.total_inscripciones || 0,
                'badge-programas': statsData.total_programas || 0
            };

            Object.keys(badges).forEach(badgeId => {
                const badge = document.getElementById(badgeId);
                if (badge) {
                    badge.textContent = badges[badgeId];
                }
            });
        }

        // Función para actualizar actividad reciente
        async function updateRecentActivity() {
            try {
                const response = await fetch('../controllers/dashboard_stats_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'get_activity' })
                });

                const data = await response.json();
                
                if (data.success) {
                    renderRecentActivity(data.data);
                }
                
            } catch (error) {
                console.error('Error updating activity:', error);
            }
        }

        // Función para renderizar actividad reciente
        function renderRecentActivity(activities) {
            const activityList = document.getElementById('activityList');
            if (!activityList || !activities) return;

            activityList.innerHTML = activities.map((activity, index) => `
                <div class="activity-item" style="animation-delay: ${index * 0.1}s">
                    <div class="activity-icon" style="background: ${activity.color || '#22c55e'}">
                        <i class="fas ${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <p>${activity.description}</p>
                        <span class="activity-time">${activity.time}</span>
                    </div>
                </div>
            `).join('');
        }

        // Función para actualizar información del usuario
        async function updateUserInfo() {
            try {
                const response = await fetch('../controllers/dashboard_stats_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'get_user_info' })
                });

                const data = await response.json();
                
                if (data.success) {
                    updateUserProfile(data.data);
                }
                
            } catch (error) {
                console.error('Error updating user info:', error);
            }
        }

        // Función para actualizar perfil de usuario
        function updateUserProfile(userData) {
            const profileName = document.querySelector('.profile-name');
            const profileRole = document.querySelector('.profile-role');
            const userAvatar = document.querySelector('.user-avatar');
            const headerUserName = document.querySelector('.user-profile span');
            
            if (profileName && userData.nombre_completo) {
                profileName.textContent = userData.nombre_completo;
            }
            
            if (profileRole && userData.rol) {
                profileRole.textContent = userData.rol.toUpperCase();
            }
            
            if (userAvatar && userData.iniciales) {
                userAvatar.textContent = userData.iniciales;
            }
            
            if (headerUserName && userData.nombre_completo) {
                headerUserName.textContent = userData.nombre_completo;
            }
        }

        // Función para animar cambio de números
        function animateNumberChange(element, from, to) {
            const duration = 1000;
            const start = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                const current = Math.floor(from + (to - from) * progress);
                element.textContent = current.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }

        // ===== FUNCIONES DEL SIDEBAR =====
        
        // Función para alternar submenús
        function toggleSubmenu(menuId) {
            const submenu = document.getElementById(`${menuId}-items`);
            const toggle = document.getElementById(`${menuId}-toggle`);
            
            if (submenu && toggle) {
                const isOpen = submenu.style.display === 'block';
                
                if (isOpen) {
                    submenu.style.display = 'none';
                    toggle.textContent = '▼';
                    toggle.style.transform = 'rotate(0deg)';
                } else {
                    submenu.style.display = 'block';
                    toggle.textContent = '▲';
                    toggle.style.transform = 'rotate(180deg)';
                }
            }
        }

        // Función para alternar tema
        function toggleTheme() {
            const body = document.body;
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                themeToggle.className = 'fas fa-moon';
                localStorage.setItem('darkTheme', 'false');
            } else {
                body.classList.add('dark-theme');
                themeToggle.className = 'fas fa-sun';
                localStorage.setItem('darkTheme', 'true');
            }
        }

        // Función para alternar menú de perfil
        function toggleProfileMenu() {
            const menu = document.getElementById('profile-menu');
            if (menu) {
                menu.classList.toggle('show');
            }
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${getNotificationIcon(type)}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            // Agregar estilos
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${getNotificationColor(type)};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 400px;
                animation: slideInRight 0.3s ease;
            `;
            
            // Agregar al DOM
            document.body.appendChild(notification);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Función para obtener icono de notificación
        function getNotificationIcon(type) {
            switch (type) {
                case 'success': return 'fa-check-circle';
                case 'error': return 'fa-exclamation-circle';
                case 'warning': return 'fa-exclamation-triangle';
                default: return 'fa-info-circle';
            }
        }

        // Función para obtener color de notificación
        function getNotificationColor(type) {
            switch (type) {
                case 'success': return '#22c55e';
                case 'error': return '#ef4444';
                case 'warning': return '#f59e0b';
                default: return '#3b82f6';
            }
        }

        // Función para buscar en tabla
        function searchTable() {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('auditTable');
            
            if (!searchInput || !table) return;
            
            const searchTerm = searchInput.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        // Cerrar menú de perfil al hacer clic fuera
        document.addEventListener('click', function(e) {
            const profileMenu = document.getElementById('profile-menu');
            const userProfile = document.querySelector('.user-profile');
            
            if (profileMenu && userProfile && !userProfile.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
        });

        // Cerrar submenús al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sidebar-item')) {
                const submenus = document.querySelectorAll('.sidebar-subitems');
                const toggles = document.querySelectorAll('.submenu-toggle');
                
                submenus.forEach(submenu => {
                    submenu.style.display = 'none';
                });
                
                toggles.forEach(toggle => {
                    toggle.textContent = '▼';
                    toggle.style.transform = 'rotate(0deg)';
                });
            }
        });

        // Cargar tema guardado al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('darkTheme');
            if (savedTheme === 'true') {
                document.body.classList.add('dark-theme');
                const themeToggle = document.querySelector('.theme-toggle i');
                if (themeToggle) {
                    themeToggle.className = 'fas fa-sun';
                }
            }
        });
    </script>
</body>
</html>
