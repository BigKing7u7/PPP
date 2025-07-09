// Cliente JavaScript para gestión de postulantes
class PostulantesManager {
    constructor() {
        this.apiUrl = '../controllers/ver_postulantesAJAX.php';
        this.currentPage = 1;
        this.recordsPerPage = 10;
        this.isLoading = false;
        this.searchTerm = '';
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPostulantes();
        this.loadEstadisticas();
        this.loadProgramas();
    }

    bindEvents() {
        // Búsqueda
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchTerm = e.target.value;
                    this.currentPage = 1;
                    if (this.searchTerm.trim() === '') {
                        this.loadPostulantes();
                    } else {
                        this.buscarPostulantes();
                    }
                }, 500);
            });
        }

        // Cambio de registros por página
        const limitSelect = document.getElementById('recordsPerPage');
        if (limitSelect) {
            limitSelect.addEventListener('change', (e) => {
                this.recordsPerPage = parseInt(e.target.value);
                this.currentPage = 1;
                    this.loadPostulantes();
            });
        }

        // Formularios
        const createForm = document.getElementById('createPostulanteForm');
        if (createForm) {
            createForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createPostulante();
            });
        }

        const updateForm = document.getElementById('updatePostulanteForm');
        if (updateForm) {
            updateForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updatePostulante();
            });
        }

        const statusForm = document.getElementById('changeStatusForm');
        if (statusForm) {
            statusForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const dni = document.getElementById('changeStatusModal').dataset.dni;
                const estado = document.getElementById('estado').value;
                if (dni && estado) {
                    this.changeStatus(dni, estado);
                }
            });
        }

        // Cerrar modales al hacer clic fuera
        window.addEventListener('click', (e) => {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (e.target === modal) {
                    this.closeModal(modal.id);
                }
        }
        });
    }

    async makeRequest(data) {
        this.showLoading();
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Error desconocido');
            }

            return result;
        } catch (error) {
            console.error('Error en la petición:', error);
            this.showError(error.message);
            throw error;
        } finally {
            this.hideLoading();
        }
    }

    async loadPostulantes() {
        try {
            const result = await this.makeRequest({
                action: 'listar_postulantes',
                page: this.currentPage,
                limit: this.recordsPerPage
            });

            this.renderPostulantes(result.data);
            this.renderPagination(result.pagination);
        } catch (error) {
            console.error('Error cargando postulantes:', error);
        }
    }

    async buscarPostulantes() {
        if (this.searchTerm.trim() === '') {
            this.loadPostulantes();
            return;
        }

        try {
            const result = await this.makeRequest({
                action: 'buscar_postulantes',
                search: this.searchTerm,
                page: this.currentPage,
                limit: this.recordsPerPage
            });

            this.renderPostulantes(result.data);
            this.hidePagination();
        } catch (error) {
            console.error('Error en búsqueda:', error);
        }
    }

    async loadEstadisticas() {
        try {
            const result = await this.makeRequest({
                action: 'obtener_estadisticas'
            });

            this.renderEstadisticas(result.data);
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
        }
    }

    async loadProgramas() {
        try {
            const result = await this.makeRequest({
                action: 'obtener_programas'
            });

            this.populateProgramasSelect(result.data);
        } catch (error) {
            console.error('Error cargando programas:', error);
        }
    }

    async viewPostulante(dni) {
        try {
            const result = await this.makeRequest({
                action: 'obtener_postulante',
                dni: dni
            });

            this.showViewModal(result.data);
        } catch (error) {
            console.error('Error obteniendo postulante:', error);
        }
    }

    async editPostulante(dni) {
        try {
            const result = await this.makeRequest({
                action: 'obtener_postulante',
                dni: dni
            });

            this.showEditModal(result.data);
        } catch (error) {
            console.error('Error obteniendo postulante:', error);
        }
    }

    async createPostulante() {
        const form = document.getElementById('createPostulanteForm');
        const formData = new FormData(form);
        
        const data = {
            action: 'crear_postulante',
            dni: formData.get('dni'),
            nombres: formData.get('nombres'),
            apellidos: formData.get('apellidos'),
            email: formData.get('email'),
            telefono: formData.get('telefono'),
            programa_id: formData.get('programa_id')
        };

        try {
            const result = await this.makeRequest(data);
            this.showSuccess(result.message);
            this.closeModal('createPostulanteModal');
            this.loadPostulantes();
            this.loadEstadisticas();
            form.reset();
        } catch (error) {
            console.error('Error creando postulante:', error);
        }
    }

    async updatePostulante() {
        const form = document.getElementById('updatePostulanteForm');
        const formData = new FormData(form);
        
        const data = {
            action: 'actualizar_postulante',
            dni: formData.get('dni'),
            nombres: formData.get('nombres'),
            apellidos: formData.get('apellidos'),
            email: formData.get('email'),
            telefono: formData.get('telefono'),
            programa_id: formData.get('programa_id')
        };

        try {
            const result = await this.makeRequest(data);
            this.showSuccess(result.message);
            this.closeModal('updatePostulanteModal');
            this.loadPostulantes();
        } catch (error) {
            console.error('Error actualizando postulante:', error);
        }
    }

    async deletePostulante(dni) {
        if (!confirm('¿Estás seguro de que deseas eliminar este postulante? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            const result = await this.makeRequest({
                action: 'eliminar_postulante',
                dni: dni
            });

            this.showSuccess(result.message);
            this.loadPostulantes();
            this.loadEstadisticas();
        } catch (error) {
            console.error('Error eliminando postulante:', error);
        }
    }

    async changeStatus(dni, estado) {
        try {
            const result = await this.makeRequest({
                action: 'cambiar_estado',
                dni: dni,
                estado: parseInt(estado)
            });

            this.showSuccess(result.message);
            this.closeModal('changeStatusModal');
            this.loadPostulantes();
            this.loadEstadisticas();
        } catch (error) {
            console.error('Error cambiando estado:', error);
        }
    }

    async viewDocuments(dni) {
        try {
            const result = await this.makeRequest({
                action: 'obtener_documentos',
                dni: dni
            });

            this.showDocumentsModal(dni, result.data);
        } catch (error) {
            console.error('Error obteniendo documentos:', error);
        }
    }

    async approveDocument(documentId) {
        try {
            const result = await this.makeRequest({
                action: 'aprobar_documento',
                documento_id: documentId
            });

            this.showSuccess(result.message);
            // Recargar documentos si el modal está abierto
            const modal = document.getElementById('documentsModal');
            if (modal && modal.style.display === 'block') {
                const dni = modal.dataset.dni;
                this.viewDocuments(dni);
            }
        } catch (error) {
            console.error('Error aprobando documento:', error);
        }
    }

    async rejectDocument(documentId) {
        const comentarios = prompt('Ingrese comentarios sobre el rechazo:');
        if (comentarios === null) return;

        try {
            const result = await this.makeRequest({
                action: 'rechazar_documento',
                documento_id: documentId,
                comentarios: comentarios
            });

            this.showSuccess(result.message);
            // Recargar documentos si el modal está abierto
            const modal = document.getElementById('documentsModal');
            if (modal && modal.style.display === 'block') {
                const dni = modal.dataset.dni;
                this.viewDocuments(dni);
            }
        } catch (error) {
            console.error('Error rechazando documento:', error);
        }
    }

    renderPostulantes(postulantes) {
        const tbody = document.getElementById('postulantesTableBody');
        if (!tbody) return;

        if (postulantes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;"></i>
                        No se encontraron postulantes
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = postulantes.map(p => `
            <tr>
                <td><strong>${p.dni}</strong></td>
                <td>${p.nombre_completo}</td>
                <td>${p.email}</td>
                <td>${p.telefono}</td>
                <td>${p.programa_nombre || 'Sin programa'}</td>
                <td>
                    <span class="status ${p.estado_clase}">${p.estado_texto}</span>
                </td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="postulantesManager.viewDocuments('${p.dni}')" title="Ver Documentos">
                        <i class="fas fa-folder"></i>
                    </button>
                </td>
                <td>
                    <div class="actions">
                        <button class="btn btn-info btn-sm" onclick="postulantesManager.viewPostulante('${p.dni}')" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="postulantesManager.editPostulante('${p.dni}')" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-success btn-sm" onclick="postulantesManager.showChangeStatusModal('${p.dni}')" title="Cambiar Estado">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="postulantesManager.deletePostulante('${p.dni}')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        if (!container) return;

        const { current_page, total_pages, total_records } = pagination;
        
        if (total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<nav><ul class="pagination justify-content-center">';
        
        // Botón anterior
        if (current_page > 1) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="postulantesManager.goToPage(${current_page - 1})">Anterior</a></li>`;
        }
        
        // Números de página
        for (let i = Math.max(1, current_page - 2); i <= Math.min(total_pages, current_page + 2); i++) {
            const active = i === current_page ? 'active' : '';
            paginationHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="postulantesManager.goToPage(${i})">${i}</a></li>`;
        }
        
        // Botón siguiente
        if (current_page < total_pages) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="postulantesManager.goToPage(${current_page + 1})">Siguiente</a></li>`;
        }
        
        paginationHTML += '</ul></nav>';
        container.innerHTML = paginationHTML;
    }

    hidePagination() {
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }
    }

    renderEstadisticas(stats) {
        const elements = {
            totalPostulantes: document.getElementById('totalPostulantes'),
            totalInscritos: document.getElementById('totalInscritos'),
            totalMatriculados: document.getElementById('totalMatriculados'),
            totalObservados: document.getElementById('totalObservados')
        };

        if (elements.totalPostulantes) elements.totalPostulantes.textContent = stats.total || 0;
        if (elements.totalInscritos) elements.totalInscritos.textContent = stats.inscritos || 0;
        if (elements.totalMatriculados) elements.totalMatriculados.textContent = stats.matriculados || 0;
        if (elements.totalObservados) elements.totalObservados.textContent = stats.observados || 0;
    }

    populateProgramasSelect(programas) {
        const selects = ['programa_id', 'edit_programa_id'];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Seleccionar programa</option>';
                programas.forEach(programa => {
                    select.innerHTML += `<option value="${programa.id}">${programa.nombre_programa}</option>`;
                });
            }
        });
    }

    showViewModal(postulante) {
        document.getElementById('viewDni').textContent = postulante.dni;
        document.getElementById('viewNombres').textContent = postulante.nombres;
        document.getElementById('viewApellidos').textContent = postulante.apellidos;
        document.getElementById('viewEmail').textContent = postulante.email;
        document.getElementById('viewTelefono').textContent = postulante.telefono;
        document.getElementById('viewPrograma').textContent = postulante.programa_nombre || 'Sin programa';
        document.getElementById('viewEstado').innerHTML = `<span class="status ${postulante.estado_clase}">${postulante.estado_texto}</span>`;
        document.getElementById('viewFechaInscripcion').textContent = postulante.fecha_inscripcion;
        
        this.openModal('viewPostulanteModal');
    }

    showEditModal(postulante) {
        document.getElementById('edit_dni').value = postulante.dni;
        document.getElementById('edit_nombres').value = postulante.nombres;
        document.getElementById('edit_apellidos').value = postulante.apellidos;
        document.getElementById('edit_email').value = postulante.email;
        document.getElementById('edit_telefono').value = postulante.telefono;
        document.getElementById('edit_programa_id').value = postulante.programa_id || '';
        
        this.openModal('updatePostulanteModal');
    }

    showChangeStatusModal(dni) {
        document.getElementById('changeStatusModal').dataset.dni = dni;
        document.getElementById('estado').value = '';
        this.openModal('changeStatusModal');
    }

    showDocumentsModal(dni, documentos) {
        const content = document.getElementById('documentsContent');
        document.getElementById('documentsModal').dataset.dni = dni;

        if (documentos.length === 0) {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-folder-open" style="font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;"></i>
                    No hay documentos registrados para este postulante
                </div>
            `;
        } else {
            content.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h4>Documentos del postulante con DNI: ${dni}</h4>
                </div>
                <div style="border: 1px solid rgba(34, 197, 94, 0.2); padding: 15px; border-radius: 6px; background: rgba(34, 197, 94, 0.05);">
                    ${documentos.map(doc => `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px; border: 1px solid rgba(34, 197, 94, 0.1);">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-file-pdf" style="color: #ef4444;"></i>
                                    <span style="font-weight: 600;">${doc.tipo_documento}</span>
                                </div>
                                <div style="margin-top: 5px; font-size: 12px; color: #666;">
                                    ${doc.nombre_archivo} - ${doc.fecha_subida}
                                </div>
                                <div style="margin-top: 5px;">
                                    <span class="status ${doc.estado_clase}">${doc.estado_texto}</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-success btn-sm" onclick="postulantesManager.approveDocument(${doc.id})" title="Aprobar">
                                <i class="fas fa-check"></i>
                            </button>
                                <button class="btn btn-danger btn-sm" onclick="postulantesManager.rejectDocument(${doc.id})" title="Rechazar">
                                <i class="fas fa-times"></i>
                            </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        this.openModal('documentsModal');
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    goToPage(page) {
        this.currentPage = page;
        if (this.searchTerm.trim() === '') {
            this.loadPostulantes();
        } else {
            this.buscarPostulantes();
        }
    }

    showLoading() {
        const loading = document.getElementById('loadingSpinner');
        if (loading) {
            loading.style.display = 'block';
        }
    }

    hideLoading() {
        const loading = document.getElementById('loadingSpinner');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showError(message) {
        this.showAlert(message, 'error');
    }

    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) {
            console.log(`${type.toUpperCase()}: ${message}`);
            return;
        }

        alertContainer.className = `alert alert-${type}`;
        alertContainer.textContent = message;
        alertContainer.style.display = 'block';

        setTimeout(() => {
            alertContainer.style.display = 'none';
        }, 5000);
    }

    // Funciones de exportación
    exportToExcel() {
        try {
            const table = document.querySelector('.table');
            if (typeof XLSX !== 'undefined') {
                const wb = XLSX.utils.table_to_book(table, {sheet: "Postulantes"});
                XLSX.writeFile(wb, `postulantes_${new Date().toISOString().split('T')[0]}.xlsx`);
                this.showSuccess('Archivo exportado exitosamente');
            } else {
                this.showError('Librería XLSX no disponible');
            }
        } catch (error) {
            console.error('Error al exportar:', error);
            this.showError('Error al exportar archivo');
        }
    }

    printTable() {
        const printWindow = window.open('', '_blank');
        const table = document.querySelector('.table');
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Lista de Postulantes</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                    .inscrito { background-color: #d4edda; color: #155724; }
                    .matriculado { background-color: #cce5ff; color: #004085; }
                    .observado { background-color: #fff3cd; color: #856404; }
                </style>
            </head>
            <body>
                <h1>Lista de Postulantes</h1>
                ${table.outerHTML}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.postulantesManager = new PostulantesManager();
});

// Funciones globales para compatibilidad
function openModal(modalId) {
    if (window.postulantesManager) {
        window.postulantesManager.openModal(modalId);
    }
}

function closeModal(modalId) {
    if (window.postulantesManager) {
        window.postulantesManager.closeModal(modalId);
    }
}

function exportToExcel() {
    if (window.postulantesManager) {
        window.postulantesManager.exportToExcel();
    }
}

function printTable() {
    if (window.postulantesManager) {
        window.postulantesManager.printTable();
    }
}

// Función para alternar sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar) {
        if (sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        } else {
            sidebar.classList.add('show');
            if (overlay) overlay.classList.add('show');
        }
    }
}

// Crear overlay para sidebar móvil
function createSidebarOverlay() {
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', () => {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
        document.body.appendChild(overlay);
    }
}

// Inicializar overlay al cargar
document.addEventListener('DOMContentLoaded', function() {
    createSidebarOverlay();
});