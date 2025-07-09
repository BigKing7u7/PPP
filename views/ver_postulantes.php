<?php  
// Incluir el archivo de conexión
require_once '../config.php';
require_once '../includes/check_roles.php';
require_once '../auth/check_session.php';

// Obtener el rol del usuario desde la sesión
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Postulantes - NOS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/DashboardGestiones.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo-section">
            <button class="hamburger" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">🎓</div>
            <div class="brand-name">Gestión de Postulantes - NOS</div>
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
            <div class="profile-menu" id="profile-menu">
                <ul>
                    <li><a href="#"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Configuración</a></li>
                    <li><a href="#"><i class="fas fa-bell"></i> Notificaciones</a></li>
                    <li><hr></li>
                    <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                </ul>
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
                <a href="dashboard.php" class="sidebar-item">
                    <div class="sidebar-item-left">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tablero</span>
                    </div>
                </a>

                <!-- Menu de Admisión -->
                <button class="sidebar-item" onclick="toggleSubmenu('admision')">
                    <div class="sidebar-item-left">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Admisión</span>
                    </div>
                    <span class="submenu-toggle" id="admision-toggle">▼</span>
                </button>
                <div class="sidebar-subitems" id="admision-items">
                    <?php if ($rol != 'postulante'): ?>
                    <a href="inscripcion.php" class="sidebar-subitem">
                        <i class="fas fa-user-plus"></i> Inscripción
                    </a>
                    <a href="ver_postulantes.php" class="sidebar-subitem active">
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

                <?php if ($rol == 'admin'): ?>
                <div class="sidebar-section">
                    <div class="section-title">Administración</div>
                    <a href="#" class="sidebar-item">
                        <div class="sidebar-item-left">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Programas</span>
                        </div>
                    </a>
                    <a href="#" class="sidebar-item">
                        <div class="sidebar-item-left">
                            <i class="fas fa-users-cog"></i>
                            <span>Usuarios</span>
                        </div>
                    </a>
                    <a href="#" class="sidebar-item">
                        <div class="sidebar-item-left">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reportes</span>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <div class="sidebar-section">
                    <div class="section-title">Sistema</div>
                    <a href="../auth/logout.php" class="sidebar-item">
                        <div class="sidebar-item-left">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Cerrar Sesión</span>
        </div>
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
            <!-- Content -->
            <div class="content">
                <div class="content-header">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar postulante..." id="searchInput">
                    </div>
                    <?php if ($rol != 'verificador'): ?>
                <button class="btn btn-primary" onclick="openModal('createPostulanteModal')">
                        <i class="fas fa-plus"></i> Nuevo Postulante
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Stats Cards -->
                <div class="stats-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 15px; padding: 20px; border: 1px solid rgba(102, 126, 234, 0.2);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 24px; margin: 0; color: #333;" id="totalPostulantes">0</h3>
                                <p style="margin: 0; color: #666; font-size: 14px;">Total Postulantes</p>
                            </div>
                        </div>
                    </div>
                    
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 15px; padding: 20px; border: 1px solid rgba(102, 126, 234, 0.2);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 24px; margin: 0; color: #333;" id="totalInscritos">0</h3>
                                <p style="margin: 0; color: #666; font-size: 14px;">Inscritos</p>
                            </div>
                        </div>
                    </div>
                    
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 15px; padding: 20px; border: 1px solid rgba(102, 126, 234, 0.2);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #16a34a, #15803d); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 24px; margin: 0; color: #333;" id="totalMatriculados">0</h3>
                                <p style="margin: 0; color: #666; font-size: 14px;">Matriculados</p>
                            </div>
                        </div>
                    </div>
                    
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 15px; padding: 20px; border: 1px solid rgba(102, 126, 234, 0.2);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 24px; margin: 0; color: #333;" id="totalObservados">0</h3>
                                <p style="margin: 0; color: #666; font-size: 14px;">En Observación</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <!-- Contenedor de alertas -->
                    <div id="alertContainer" style="display: none;" class="alert alert-warning">¡Alerta!</div>
                    <!-- Spinner de carga -->
                    <div id="loadingSpinner" style="display: none;">Cargando...</div>

                    <!-- Selector de registros por página -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="recordsPerPage" style="font-weight: 600; color: #333;">Mostrar:</label>
                        <select id="recordsPerPage" style="padding: 8px 12px; border: 1px solid rgba(102, 126, 234, 0.2); border-radius: 6px; background: rgba(255, 255, 255, 0.9);">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span style="color: #666; font-size: 14px;">registros por página</span>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="exportToExcel()" style="background: rgba(102, 126, 234, 0.1); color: #667eea; border: 1px solid rgba(102, 126, 234, 0.2);">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        <button class="btn btn-secondary" onclick="printTable()" style="background: rgba(102, 126, 234, 0.1); color: #667eea; border: 1px solid rgba(102, 126, 234, 0.2);">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                        </div>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Programa</th>
                                <th>Estado</th>
                                <th>Documentos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="postulantesTableBody">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div id="paginationContainer" class="pagination">
                    <!-- La paginación se generará dinámicamente -->
                </div>
            </div>
    </main>

    <!-- Scripts -->
    <script src="../js/dashboard.js"></script>
    <script src="../js/ver_postulantesAJAX.js"></script>
    <script>
        // Inicializar el gestor de postulantes
        document.addEventListener('DOMContentLoaded', function() {
            window.postulantesManager = new PostulantesManager();
        });
    </script>
</body>
</html>
