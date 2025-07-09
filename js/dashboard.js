// Dashboard JavaScript - Sistema de Admisión NOS
// Versión optimizada con paleta de colores verde y funcionalidades dinámicas

// Variables globales
let isDarkTheme = false;
let sidebarCollapsed = false;
let currentUser = null;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    loadDynamicData();
    setupEventListeners();
    setupKeyboardShortcuts();
    
    // Configurar actualizaciones automáticas
    setInterval(loadDynamicData, 300000); // Actualizar cada 5 minutos
});

// Función de inicialización del dashboard
function initializeDashboard() {
    // Crear overlay para sidebar móvil
    createSidebarOverlay();
    
    // Cargar tema guardado
    loadTheme();
    
    // Cargar estado del sidebar
    loadSidebarState();
    
    // Inicializar tooltips
    initializeTooltips();
    
    // Configurar animaciones de entrada
    animateDashboardElements();
    
    // Configurar comportamiento responsivo
    setupResponsiveBehavior();
}

// Función para cargar datos dinámicos
async function loadDynamicData() {
    try {
        await Promise.all([
            updateStats(),
            updateRecentActivity(),
            updateUserInfo(),
            updateSidebarBadges()
        ]);
    } catch (error) {
        console.error('Error loading dynamic data:', error);
        showNotification('Error al cargar datos dinámicos', 'error');
    }
}

// Función para actualizar estadísticas
async function updateStats() {
    try {
        const response = await fetch('../controllers/dashboard_stats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'get_stats' })
        });

        const data = await response.json();
        
        if (data.success) {
            updateStatCards(data.data);
        }
        
    } catch (error) {
        console.error('Error updating stats:', error);
    }
}

// Función para actualizar tarjetas de estadísticas
function updateStatCards(statsData) {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        const statId = card.getAttribute('data-stat');
        const statValue = card.querySelector('h3');
        const trendElement = card.querySelector('[data-trend]');
        
        if (statValue && statId) {
            let value = 0;
            
            // Buscar el valor en los datos según la estructura
            if (statsData.postulantes && statsData.postulantes[statId]) {
                value = statsData.postulantes[statId];
            } else if (statsData[statId]) {
                value = statsData[statId];
            } else if (statsData.documentos && statsData.documentos[statId]) {
                value = statsData.documentos[statId];
            }
            
            // Animar el cambio de número
            const currentValue = parseInt(statValue.getAttribute('data-value') || '0');
            animateNumberChange(statValue, currentValue, value);
            statValue.setAttribute('data-value', value);
        }
        
        // Actualizar tendencias si existen
        if (trendElement && statsData.tendencias && statsData.tendencias[statId]) {
            const trend = statsData.tendencias[statId];
            trendElement.textContent = `${trend.percentage}%`;
            trendElement.className = `stat-trend ${trend.direction}`;
        }
    });
}

// Función para actualizar actividad reciente
async function updateRecentActivity() {
    try {
        const response = await fetch('../controllers/dashboard_stats.php', {
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
        const response = await fetch('../controllers/dashboard_stats.php', {
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

// Función para actualizar badges del sidebar
async function updateSidebarBadges() {
    try {
        const response = await fetch('../controllers/dashboard_stats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'get_stats' })
        });

        const data = await response.json();
        
        if (data.success) {
            const badges = {
                'badge-postulantes': data.data.postulantes?.total_postulantes || 0,
                'badge-matriculados': data.data.postulantes?.matriculados || 0,
                'badge-programas': data.data.programas || 0
            };

            Object.keys(badges).forEach(badgeId => {
                const badge = document.getElementById(badgeId);
                if (badge) {
                    badge.textContent = badges[badgeId];
                }
            });
        }
        
    } catch (error) {
        console.error('Error updating badges:', error);
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

// Función para alternar tema
function toggleTheme() {
    isDarkTheme = !isDarkTheme;
    
    if (isDarkTheme) {
        document.body.classList.add('dark-theme');
        document.querySelector('.theme-toggle i').className = 'fas fa-sun';
    } else {
        document.body.classList.remove('dark-theme');
        document.querySelector('.theme-toggle i').className = 'fas fa-moon';
    }
    
    // Guardar preferencia
    localStorage.setItem('darkTheme', isDarkTheme);
    
    // Mostrar notificación
    showNotification(
        isDarkTheme ? 'Modo oscuro activado' : 'Modo claro activado',
        'success'
    );
}

// Función para cargar tema
function loadTheme() {
    const savedTheme = localStorage.getItem('darkTheme');
    if (savedTheme === 'true') {
        isDarkTheme = true;
        document.body.classList.add('dark-theme');
        document.querySelector('.theme-toggle i').className = 'fas fa-sun';
    }
}

// Función para alternar sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const overlay = document.querySelector('.sidebar-overlay');
    const profileMenu = document.getElementById('profile-menu');
    const isMobile = window.innerWidth < 768;
    if (sidebar && mainContent) {
        if (isMobile) {
            const isOpen = sidebar.classList.toggle('show');
            if (isOpen) {
                sidebar.classList.remove('collapsed');
                if (overlay) overlay.classList.add('show');
                document.body.classList.add('sidebar-open');
            } else {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }
            if (profileMenu && profileMenu.classList.contains('show')) {
                profileMenu.classList.remove('show');
                const closeBtn = profileMenu.querySelector('.close-modal-btn');
                if (closeBtn) closeBtn.remove();
            }
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            if (sidebar.classList.contains('collapsed')) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }
    }
}

// Función para cargar estado del sidebar
function loadSidebarState() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    if (sidebar && mainContent) {
        // Siempre extendido al cargar, sin importar localStorage
        sidebar.classList.remove('collapsed');
        sidebar.classList.remove('show');
        mainContent.classList.remove('expanded');
        document.body.classList.remove('sidebar-collapsed');
        document.body.classList.remove('sidebar-open');
    }
}

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

// Función para alternar menú de perfil
function toggleProfileMenu() {
    const menu = document.getElementById('profile-menu');
    if (menu) {
        const isMobile = window.innerWidth <= 600;
        menu.classList.toggle('show');
        if (isMobile && menu.classList.contains('show')) {
            // Agregar botón de cerrar si no existe
            if (!menu.querySelector('.close-modal-btn')) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'close-modal-btn';
                closeBtn.innerHTML = '&times;';
                closeBtn.onclick = function() {
                    menu.classList.remove('show');
                };
                menu.appendChild(closeBtn);
            }
        } else if (!menu.classList.contains('show')) {
            const closeBtn = menu.querySelector('.close-modal-btn');
            if (closeBtn) closeBtn.remove();
        }
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

// Función para inicializar tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                pointer-events: none;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            
            this.tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltip) {
                this.tooltip.remove();
                this.tooltip = null;
            }
        });
    });
}

// Función para animar elementos del dashboard
function animateDashboardElements() {
    const elements = document.querySelectorAll('.stat-card, .quick-action-card, .activity-item');
    
    elements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.5s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Función para crear overlay del sidebar
function createSidebarOverlay() {
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
                sidebar.classList.add('collapsed');
                overlay.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }
        });
        document.body.appendChild(overlay);
    }
}

// Función para configurar comportamiento responsivo
function setupResponsiveBehavior() {
    const handleResize = () => {
        const isMobile = window.innerWidth <= 1024;
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (isMobile) {
            // En móvil, siempre colapsar sidebar
            if (sidebar) {
                sidebar.classList.add('collapsed');
                sidebar.style.transform = 'translateX(-100%)';
            }
            if (overlay) {
                overlay.classList.remove('show');
            }
        } else {
            // En desktop, restaurar estado guardado
            if (sidebar) {
                sidebar.style.transform = '';
            }
            if (overlay) {
                overlay.classList.remove('show');
            }
        }
    };
    
    // Ejecutar al cargar y en resize
    handleResize();
    window.addEventListener('resize', debounce(handleResize, 250));
}

// Función para configurar event listeners
function setupEventListeners() {
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
    
    // Event listener para cards de estadísticas
    document.addEventListener('click', function(e) {
        if (e.target.closest('.stat-card')) {
            const card = e.target.closest('.stat-card');
            const statId = card.getAttribute('data-stat');
            
            if (statId) {
                showStatDetails(statId);
            }
        }
    });
}

// Función para configurar atajos de teclado
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K para búsqueda
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('.search-box');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Ctrl/Cmd + B para alternar sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            toggleSidebar();
        }
        
        // Ctrl/Cmd + D para alternar tema
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            toggleTheme();
        }
        
        // Escape para cerrar modales y menús
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            const profileMenu = document.getElementById('profile-menu');
            
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
            
            if (profileMenu) {
                profileMenu.classList.remove('show');
            }
        }
    });
}

// Función para mostrar detalles de estadísticas
function showStatDetails(statId) {
    // Crear modal con detalles
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    `;
    
    modal.innerHTML = `
        <div class="modal-content" style="
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Detalles de ${getStatTitle(statId)}</h3>
                <button onclick="this.closest('.modal').remove()" style="
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #666;
                ">&times;</button>
            </div>
            <div id="statDetailsContent">
                <p>Cargando detalles...</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Cargar detalles específicos
    loadStatDetails(statId, modal);
}

// Función para obtener título de estadística
function getStatTitle(statId) {
    const titles = {
        'total_postulantes': 'Total de Postulantes',
        'inscritos': 'Postulantes Inscritos',
        'matriculados': 'Postulantes Matriculados',
        'observados': 'Postulantes en Observación',
        'total_programas': 'Programas Activos',
        'total_documentos': 'Documentos',
        'documentos_pendientes': 'Documentos Pendientes',
        'revisados_hoy': 'Documentos Revisados Hoy'
    };
    
    return titles[statId] || 'Estadística';
}

// Función para cargar detalles de estadísticas
async function loadStatDetails(statId, modal) {
    try {
        const response = await fetch('../controllers/dashboard_stats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                action: 'get_chart_data',
                stat_id: statId
            })
        });

        const data = await response.json();
        
        if (data.success) {
            renderStatDetails(statId, data.data, modal);
        } else {
            modal.querySelector('#statDetailsContent').innerHTML = `
                <p style="color: #666; text-align: center;">No hay datos disponibles para esta estadística.</p>
            `;
        }
        
    } catch (error) {
        console.error('Error loading stat details:', error);
        modal.querySelector('#statDetailsContent').innerHTML = `
            <p style="color: #ef4444; text-align: center;">Error al cargar los detalles.</p>
        `;
    }
}

// Función para renderizar detalles de estadísticas
function renderStatDetails(statId, data, modal) {
    const content = modal.querySelector('#statDetailsContent');
    
    let html = '';
    
    switch (statId) {
        case 'total_postulantes':
        case 'inscritos':
        case 'matriculados':
        case 'observados':
            html = renderPostulantesDetails(data);
            break;
        case 'total_programas':
            html = renderProgramasDetails(data);
            break;
        case 'total_documentos':
        case 'documentos_pendientes':
            html = renderDocumentosDetails(data);
            break;
        default:
            html = renderGenericDetails(data);
    }
    
    content.innerHTML = html;
}

// Funciones auxiliares para renderizar detalles
function renderPostulantesDetails(data) {
    return `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #22c55e; margin-bottom: 15px;">Distribución por Programa</h4>
            ${data.programas ? data.programas.map(prog => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: rgba(34, 197, 94, 0.1); border-radius: 6px; margin-bottom: 8px;">
                    <span>${prog.programa}</span>
                    <span style="font-weight: bold; color: #22c55e;">${prog.total}</span>
                </div>
            `).join('') : '<p>No hay datos disponibles</p>'}
        </div>
    `;
}

function renderProgramasDetails(data) {
    return `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #22c55e; margin-bottom: 15px;">Programas Activos</h4>
            ${data.programas ? data.programas.map(prog => `
                <div style="padding: 15px; background: rgba(34, 197, 94, 0.1); border-radius: 8px; margin-bottom: 10px;">
                    <h5 style="margin: 0 0 8px 0; color: #333;">${prog.nombre_programa}</h5>
                    <p style="margin: 0; color: #666; font-size: 14px;">Modalidad: ${prog.modalidad || 'N/A'}</p>
                </div>
            `).join('') : '<p>No hay programas disponibles</p>'}
        </div>
    `;
}

function renderDocumentosDetails(data) {
    return `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #22c55e; margin-bottom: 15px;">Estado de Documentos</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                <div style="text-align: center; padding: 15px; background: rgba(34, 197, 94, 0.1); border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #22c55e;">${data.aprobados || 0}</div>
                    <div style="font-size: 12px; color: #666;">Aprobados</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(239, 68, 68, 0.1); border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #ef4444;">${data.rechazados || 0}</div>
                    <div style="font-size: 12px; color: #666;">Rechazados</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(245, 158, 11, 0.1); border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #f59e0b;">${data.pendientes || 0}</div>
                    <div style="font-size: 12px; color: #666;">Pendientes</div>
                </div>
            </div>
        </div>
    `;
}

function renderGenericDetails(data) {
    return `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-chart-bar" style="font-size: 48px; color: #22c55e; margin-bottom: 15px;"></i>
            <p style="color: #666;">Información detallada no disponible para esta estadística.</p>
        </div>
    `;
}

// Función para navegar a diferentes secciones
function navigateTo(page) {
    // Implementar navegación según la página
    switch (page) {
        case 'dashboard':
            // Ya estamos en el dashboard
            break;
        case 'postulantes':
            window.location.href = 'ver_postulantes.php';
            break;
        case 'inscripcion':
            window.location.href = 'inscripcion.php';
            break;
        default:
            console.log('Navegando a:', page);
    }
}

// Función para exportar datos de auditoría
function exportAuditData() {
    showNotification('Funcionalidad de exportación en desarrollo', 'info');
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

// Función para mostrar modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

// Función para cerrar modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Función para mostrar confirmación
function showConfirmation(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Función para mostrar loading
function showLoading(show = true) {
    const loadingElement = document.getElementById('loadingSpinner');
    if (loadingElement) {
        loadingElement.style.display = show ? 'block' : 'none';
    }
}

// Función para validar formularios
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Función para limpiar formularios
function clearForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.style.borderColor = '';
        });
    }
}

// Función para formatear fechas
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Función para formatear números
function formatNumber(number) {
    return new Intl.NumberFormat('es-ES').format(number);
}

// Función para generar colores aleatorios
function getRandomColor() {
    const colors = [
        '#22c55e', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444',
        '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Función para ajustar color (hacer más oscuro)
function adjustColor(color, amount) {
    const hex = color.replace('#', '');
    const r = Math.max(0, Math.min(255, parseInt(hex.substr(0, 2), 16) + amount));
    const g = Math.max(0, Math.min(255, parseInt(hex.substr(2, 2), 16) + amount));
    const b = Math.max(0, Math.min(255, parseInt(hex.substr(4, 2), 16) + amount));
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
}

// Función para debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Función para throttle
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Exportar funciones para uso global
window.dashboard = {
    toggleTheme,
    toggleSidebar,
    toggleSubmenu,
    toggleProfileMenu,
    showNotification,
    navigateTo,
    exportAuditData,
    searchTable,
    showModal,
    closeModal,
    showConfirmation,
    showLoading,
    validateForm,
    clearForm,
    formatDate,
    formatNumber,
    getRandomColor,
    adjustColor
};

// Agregar estilos CSS dinámicos
const dynamicStyles = document.createElement('style');
dynamicStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .notification {
        animation: slideInRight 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.2);
    }
    
    .quick-action-card:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.2);
    }
    
    .activity-item {
        animation: fadeIn 0.5s ease;
    }
    
    .tooltip {
        animation: fadeIn 0.2s ease;
    }
`;

document.head.appendChild(dynamicStyles);

// Cerrar sidebar con ESC en móvil
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        if (window.innerWidth < 768 && sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            sidebar.classList.add('collapsed');
            if (overlay) overlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    }
});
