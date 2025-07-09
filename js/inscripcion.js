// inscripcion.js - Script completo para formulario de inscripción
document.addEventListener('DOMContentLoaded', function() {
    // ========================
    // VARIABLES GLOBALES
    // ========================
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const nextBtn = document.getElementById('next1');
    const prevBtn = document.getElementById('prev2');
    const form = document.getElementById('inscripcionForm');
    
    // Datos JSON del servidor
    let programasData = [];
    let ubigeosData = [];
    
    try {
        const programasScript = document.getElementById('programasData');
        const ubigeosScript = document.getElementById('ubigeosData');
        
        if (programasScript) {
            programasData = JSON.parse(programasScript.textContent);
        }
        if (ubigeosScript) {
            ubigeosData = JSON.parse(ubigeosScript.textContent);
        }
    } catch (e) {
        console.error('Error al cargar datos JSON:', e);
    }
    
    // Variables para cropper
    let cropper;
    let currentPhotoFile = null;
    
    // Configuración de archivos (consistente con config.php)
    const FILE_CONFIG = {
        maxSize: 2 * 1024 * 1024, // 2MB como en config.php
        allowedTypes: ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
        allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
        documentosObligatorios: [1, 2, 3, 4, 5] // IDs de documentos obligatorios según BD
    };
    
    // ========================
    // NAVEGACIÓN ENTRE PASOS
    // ========================
    nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (validateStep1()) {
            goToStep(2);
        }
    });
    
    prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        goToStep(1);
    });
    
    function goToStep(stepNumber) {
        // Ocultar todos los pasos
        document.querySelectorAll('.form-step').forEach(step => {
            step.classList.remove('active');
        });
        
        // Mostrar el paso correspondiente
        if (stepNumber === 1) {
            step1.classList.add('active');
        } else if (stepNumber === 2) {
            step2.classList.add('active');
        }
        
        // Actualizar stepper visual
        updateStepper(stepNumber);
        
        // Scroll hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function updateStepper(activeStep) {
        const steps = document.querySelectorAll('.stepper .step');
        steps.forEach((step, index) => {
            if (index + 1 <= activeStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });
    }
    
    // ========================
    // VALIDACIONES
    // ========================
    function validateStep1() {
        const errors = [];
        
        // Validar DNI
        const dni = document.getElementById('dni').value.trim();
        if (!dni) {
            errors.push('El DNI es obligatorio');
        } else if (!/^\d{8}$/.test(dni)) {
            errors.push('El DNI debe tener exactamente 8 dígitos');
        }
        
        // Validar proceso de admisión
        const proceso = document.getElementById('proceso_admision').value;
        if (!proceso) {
            errors.push('Debe seleccionar un proceso de admisión');
        }
        
        // Validar modalidad
        const modalidad = document.getElementById('modalidad_id').value;
        if (!modalidad) {
            errors.push('Debe seleccionar una modalidad de admisión');
        }
        
        // Validar primera opción de programa
        const programa1 = document.getElementById('programa1_id').value;
        if (!programa1) {
            errors.push('Debe seleccionar su primera opción de carrera');
        }
        
        // Validar segunda opción si está marcada
        const segundaOpcion = document.getElementById('segunda_opcion').checked;
        if (segundaOpcion) {
            const programa2 = document.getElementById('programa2_id').value;
            if (!programa2) {
                errors.push('Debe seleccionar su segunda opción de carrera');
            } else if (programa1 === programa2) {
                errors.push('Las dos opciones de carrera deben ser diferentes');
            }
        }
        
        // Validar tipo de exoneración si es necesario
        const modalidadSelect = document.getElementById('modalidad_id');
        const modalidadText = modalidadSelect.options[modalidadSelect.selectedIndex]?.text?.toLowerCase();
        if (modalidadText && modalidadText.includes('exoner')) {
            const tipoExoneracion = document.getElementById('tipo_exoneracion_id').value;
            if (!tipoExoneracion) {
                errors.push('Debe seleccionar el tipo de exoneración');
            }
        }
        
        // Mostrar errores si los hay
        if (errors.length > 0) {
            showError('Errores en el Paso 1:', errors);
            return false;
        }
        
        return true;
    }
    
    function validateStep2() {
        const errors = [];
        
        // Validar datos básicos
        const nombres = document.getElementById('nombres').value.trim();
        const apellidoPaterno = document.getElementById('apellido_paterno').value.trim();
        const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
        const email = document.getElementById('email').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const anoEgreso = document.getElementById('ano_egreso').value;
        const direccion = document.getElementById('direccion').value.trim();
        
        if (!nombres) errors.push('Los nombres son obligatorios');
        if (!apellidoPaterno) errors.push('El apellido paterno es obligatorio');
        if (!fechaNacimiento) errors.push('La fecha de nacimiento es obligatoria');
        if (!email) errors.push('El email es obligatorio');
        if (!telefono) errors.push('El teléfono es obligatorio');
        if (!anoEgreso) errors.push('El año de egreso es obligatorio');
        if (!direccion) errors.push('La dirección es obligatoria');
        
        // Validar formato de email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            errors.push('El formato del email no es válido');
        }
        
        // Validar formato de teléfono
        if (telefono && !/^\d{9}$/.test(telefono)) {
            errors.push('El teléfono debe tener 9 dígitos');
        }
        
        // Validar año de egreso
        const currentYear = new Date().getFullYear();
        if (anoEgreso && (anoEgreso < 1900 || anoEgreso > currentYear)) {
            errors.push('El año de egreso no es válido');
        }
        
        // Validar ubigeos del postulante
        const ubigeoFields = [
            ['nacimiento', 'nacimiento'],
            ['domicilio', 'domicilio actual']
        ];
        
        ubigeoFields.forEach(([prefix, label]) => {
            const dep = document.getElementById(`${prefix}_departamento`).value;
            const prov = document.getElementById(`${prefix}_provincia`).value;
            const dist = document.getElementById(`${prefix}_distrito`).value;
            
            if (!dep) errors.push(`Debe seleccionar el departamento de ${label}`);
            if (!prov) errors.push(`Debe seleccionar la provincia de ${label}`);
            if (!dist) errors.push(`Debe seleccionar el distrito de ${label}`);
        });
        
        // Validar apoderado si es necesario
        const parentesco = document.querySelector('input[name="ap_parentesco"]:checked')?.value;
        console.log('Validando apoderado - Parentesco:', parentesco);
        
        if (parentesco && parentesco !== 'Ninguno') {
            const apDni = document.getElementById('ap_dni');
            const apNombres = document.getElementById('ap_nombres');
            const apApellidoPaterno = document.getElementById('ap_apellido_paterno');
            const apFechaNacimiento = document.getElementById('ap_fecha_nacimiento');
            
            console.log('Estado de campos del apoderado:');
            console.log('apDni:', {
                value: apDni?.value,
                disabled: apDni?.disabled,
                required: apDni?.required,
                element: apDni
            });
            console.log('apNombres:', {
                value: apNombres?.value,
                disabled: apNombres?.disabled,
                required: apNombres?.required,
                element: apNombres
            });
            console.log('apApellidoPaterno:', {
                value: apApellidoPaterno?.value,
                disabled: apApellidoPaterno?.disabled,
                required: apApellidoPaterno?.required,
                element: apApellidoPaterno
            });
            console.log('apFechaNacimiento:', {
                value: apFechaNacimiento?.value,
                disabled: apFechaNacimiento?.disabled,
                required: apFechaNacimiento?.required,
                element: apFechaNacimiento
            });
            
            // Verificar que los campos estén habilitados y tengan valor
            if (!apDni || apDni.disabled || !apDni.value.trim()) {
                console.log('Error: DNI del apoderado vacío o deshabilitado');
                errors.push('El DNI del apoderado es obligatorio');
            }
            
            if (!apNombres || apNombres.disabled || !apNombres.value.trim()) {
                console.log('Error: Nombres del apoderado vacíos o deshabilitados');
                console.log('Valor de apNombres:', apNombres?.value);
                console.log('Disabled:', apNombres?.disabled);
                errors.push('Los nombres del apoderado son obligatorios');
            }
            
            if (!apApellidoPaterno || apApellidoPaterno.disabled || !apApellidoPaterno.value.trim()) {
                console.log('Error: Apellido paterno del apoderado vacío o deshabilitado');
                errors.push('El apellido paterno del apoderado es obligatorio');
            }
            
            if (!apFechaNacimiento || apFechaNacimiento.disabled || !apFechaNacimiento.value) {
                console.log('Error: Fecha de nacimiento del apoderado vacía o deshabilitada');
                errors.push('La fecha de nacimiento del apoderado es obligatoria');
            }
            
            if (apDni && apDni.value.trim() && !/^\d{8}$/.test(apDni.value.trim())) {
                errors.push('El DNI del apoderado debe tener 8 dígitos');
            }
            
            // Validar ubigeo del apoderado solo si los campos están habilitados
            const apDep = document.getElementById('apoderado_departamento');
            const apProv = document.getElementById('apoderado_provincia');
            const apDist = document.getElementById('apoderado_distrito');
            
            console.log('Estado de ubigeos del apoderado:');
            console.log('Departamento:', apDep?.value, 'Disabled:', apDep?.disabled);
            console.log('Provincia:', apProv?.value, 'Disabled:', apProv?.disabled);
            console.log('Distrito:', apDist?.value, 'Disabled:', apDist?.disabled);
            
            if (!apDep || (!apDep.disabled && !apDep.value)) errors.push('Debe seleccionar el departamento del apoderado');
            if (!apProv || (!apProv.disabled && !apProv.value)) errors.push('Debe seleccionar la provincia del apoderado');
            if (!apDist || (!apDist.disabled && !apDist.value)) errors.push('Debe seleccionar el distrito del apoderado');
        }
        
        // Validar documentos obligatorios (IDs 1-5 según la BD)
        const documentosObligatorios = FILE_CONFIG.documentosObligatorios;
        documentosObligatorios.forEach(docId => {
            const fileInput = document.querySelector(`input[name="doc_${docId}"]`);
            if (fileInput && !fileInput.files.length) {
                const label = fileInput.previousElementSibling.textContent.replace('*', '').trim();
                errors.push(`El documento "${label}" es obligatorio`);
            }
        });
        
        // Validar términos y condiciones
        const acepto = document.getElementById('acepto').checked;
        if (!acepto) {
            errors.push('Debe aceptar los términos y condiciones');
        }
        
        return errors;
    }
    
    function showError(title, errors) {
        const errorMsg = `${title}\n\n• ${errors.join('\n• ')}`;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: title,
                html: errors.map(error => `• ${error}`).join('<br>'),
                confirmButtonText: 'Entendido'
            });
        } else {
            alert(errorMsg);
        }
    }
    
    function showSuccess(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: title,
                text: message,
                confirmButtonText: 'OK'
            });
        } else {
            alert(`${title}\n\n${message}`);
        }
    }
    
    // ========================
    // MANEJO DE SEGUNDA OPCIÓN
    // ========================
    const segundaOpcion = document.getElementById('segunda_opcion');
    const programa2Container = document.getElementById('programa2Container');
    
    segundaOpcion.addEventListener('change', function() {
        if (this.checked) {
            programa2Container.style.display = 'block';
            // Copiar opciones del primer select al segundo
            const programa1 = document.getElementById('programa1_id');
            const programa2 = document.getElementById('programa2_id');
            programa2.innerHTML = programa1.innerHTML;
        } else {
            programa2Container.style.display = 'none';
            document.getElementById('programa2_id').value = '';
        }
    });
    
    // ========================
    // MANEJO DE MODALIDAD Y EXONERACIÓN
    // ========================
    const modalidadSelect = document.getElementById('modalidad_id');
    const tipoExoneracionContainer = document.getElementById('tipoExoneracionContainer');
    
    modalidadSelect.addEventListener('change', function() {
        const modalidadText = this.options[this.selectedIndex]?.text?.toLowerCase();
        if (modalidadText && modalidadText.includes('exoner')) {
            tipoExoneracionContainer.style.display = 'block';
            document.getElementById('tipo_exoneracion_id').required = true;
        } else {
            tipoExoneracionContainer.style.display = 'none';
            document.getElementById('tipo_exoneracion_id').required = false;
            document.getElementById('tipo_exoneracion_id').value = '';
        }
    });
    
    // ========================
    // MANEJO DE APODERADO - VERSIÓN COMPLETAMENTE CORREGIDA
    // ========================
    
    // Función para manejar el cambio de parentesco
    function handleParentescoChange() {
        console.log('=== HANDLE PARENTESCO CHANGE EJECUTADO ===');
        
        const parentescoSeleccionado = document.querySelector('input[name="ap_parentesco"]:checked');
        const isNinguno = !parentescoSeleccionado || parentescoSeleccionado.value === 'Ninguno';
        
        console.log('Parentesco seleccionado:', parentescoSeleccionado?.value);
        console.log('¿Es Ninguno?', isNinguno);
        
        // Obtener todos los campos del apoderado
        const camposApoderado = [
            'ap_dni', 'ap_nombres', 'ap_apellido_paterno', 'ap_apellido_materno',
            'ap_fecha_nacimiento', 'ap_telefono', 'ap_direccion'
        ];
        
        const selectsUbigeo = [
            'apoderado_departamento', 'apoderado_provincia', 'apoderado_distrito'
        ];
        
        console.log('Procesando campos del apoderado...');
        
        // Habilitar/deshabilitar campos de texto
        camposApoderado.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                const estadoAnterior = {
                    disabled: campo.disabled,
                    required: campo.required,
                    value: campo.value
                };
                
                campo.disabled = isNinguno;
                if (isNinguno) {
                    campo.value = '';
                    campo.required = false;
                } else {
                    // Campos obligatorios
                    if (['ap_dni', 'ap_nombres', 'ap_apellido_paterno', 'ap_fecha_nacimiento'].includes(campoId)) {
                        campo.required = true;
                    }
                }
                
                const estadoNuevo = {
                    disabled: campo.disabled,
                    required: campo.required,
                    value: campo.value
                };
                
                console.log(`Campo ${campoId}:`, {
                    antes: estadoAnterior,
                    despues: estadoNuevo,
                    cambio: estadoAnterior.disabled !== estadoNuevo.disabled || 
                           estadoAnterior.required !== estadoNuevo.required
                });
            } else {
                console.log(`❌ Campo ${campoId} no encontrado`);
            }
        });
        
        console.log('Procesando selects de ubigeo...');
        
        // Habilitar/deshabilitar selects de ubigeo
        selectsUbigeo.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const estadoAnterior = {
                    disabled: select.disabled,
                    value: select.value,
                    optionsCount: select.options.length
                };
                
                select.disabled = isNinguno;
                if (isNinguno) {
                    select.value = '';
                    select.innerHTML = '<option value="">-- Selecciona --</option>';
                } else {
                    // Si se está habilitando, configurar el ubigeo del apoderado
                    if (selectId === 'apoderado_departamento') {
                        console.log('Configurando ubigeo del apoderado...');
                        setupApoderadoUbigeo();
                    }
                }
                
                const estadoNuevo = {
                    disabled: select.disabled,
                    value: select.value,
                    optionsCount: select.options.length
                };
                
                console.log(`Select ${selectId}:`, {
                    antes: estadoAnterior,
                    despues: estadoNuevo,
                    cambio: estadoAnterior.disabled !== estadoNuevo.disabled
                });
            } else {
                console.log(`❌ Select ${selectId} no encontrado`);
            }
        });
        
        console.log('=== FIN HANDLE PARENTESCO CHANGE ===');
    }
    
    // Función específica para configurar ubigeo del apoderado
    function setupApoderadoUbigeo() {
        const depSelect = document.getElementById('apoderado_departamento');
        const provSelect = document.getElementById('apoderado_provincia');
        const distSelect = document.getElementById('apoderado_distrito');
        
        if (!depSelect || !provSelect || !distSelect) return;
        
        // Limpiar selects
        provSelect.innerHTML = '<option value="">-- Selecciona --</option>';
        distSelect.innerHTML = '<option value="">-- Selecciona --</option>';
        provSelect.disabled = true;
        distSelect.disabled = true;
        
        // Cargar departamentos si no están cargados
        if (depSelect.options.length <= 1) {
            const departamentos = [...new Set(ubigeosData.map(u => u.departamento))].sort();
            departamentos.forEach(dep => {
                const option = document.createElement('option');
                option.value = dep;
                option.textContent = dep;
                depSelect.appendChild(option);
            });
        }
        
        // Event listener para departamento
        depSelect.removeEventListener('change', handleApoderadoDepartamentoChange);
        depSelect.addEventListener('change', handleApoderadoDepartamentoChange);
        
        // Event listener para provincia
        provSelect.removeEventListener('change', handleApoderadoProvinciaChange);
        provSelect.addEventListener('change', handleApoderadoProvinciaChange);
        
        console.log('Ubigeo del apoderado configurado');
    }
    
    function handleApoderadoDepartamentoChange() {
        const depSelect = document.getElementById('apoderado_departamento');
        const provSelect = document.getElementById('apoderado_provincia');
        const distSelect = document.getElementById('apoderado_distrito');
        
        const departamento = depSelect.value;
        provSelect.innerHTML = '<option value="">-- Selecciona --</option>';
        distSelect.innerHTML = '<option value="">-- Selecciona --</option>';
        provSelect.disabled = true;
        distSelect.disabled = true;
        
        if (departamento) {
            const provincias = [...new Set(
                ubigeosData
                    .filter(u => u.departamento === departamento)
                    .map(u => u.provincia)
            )].sort();
            
            provincias.forEach(prov => {
                const option = document.createElement('option');
                option.value = prov;
                option.textContent = prov;
                provSelect.appendChild(option);
            });
            
            provSelect.disabled = false;
        }
    }
    
    function handleApoderadoProvinciaChange() {
        const depSelect = document.getElementById('apoderado_departamento');
        const provSelect = document.getElementById('apoderado_provincia');
        const distSelect = document.getElementById('apoderado_distrito');
        
        const departamento = depSelect.value;
        const provincia = provSelect.value;
        distSelect.innerHTML = '<option value="">-- Selecciona --</option>';
        distSelect.disabled = true;
        
        if (departamento && provincia) {
            const distritos = ubigeosData
                .filter(u => u.departamento === departamento && u.provincia === provincia)
                .map(u => u.distrito)
                .sort();
            
            distritos.forEach(dist => {
                const option = document.createElement('option');
                option.value = dist;
                option.textContent = dist;
                distSelect.appendChild(option);
            });
            
            distSelect.disabled = false;
        }
    }
    
    // ========================
    // CÁLCULO DE EDAD
    // ========================
    const fechaNacimiento = document.getElementById('fecha_nacimiento');
    const edadInput = document.getElementById('edad');
    
    fechaNacimiento.addEventListener('change', function() {
        if (this.value) {
            const hoy = new Date();
            const nacimiento = new Date(this.value);
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            
            edadInput.value = edad;
        }
    });
    
    // ========================
    // MANEJO DE DISCAPACIDAD
    // ========================
    const discapacidadInputs = document.querySelectorAll('input[name="discapacidad"]');
    const descDiscapacidad = document.getElementById('desc_discapacidad');
    
    discapacidadInputs.forEach(input => {
        input.addEventListener('change', function() {
            const tieneDis = this.value === 'Sí';
            
            descDiscapacidad.disabled = !tieneDis;
            descDiscapacidad.required = tieneDis;
            
            if (!tieneDis) {
                descDiscapacidad.value = '';
            }
        });
    });
    
    // ========================
    // MANEJO DE MEDIO DE DIFUSIÓN
    // ========================
    const medioInputs = document.querySelectorAll('input[name="medio"]');
    const medioOtro = document.getElementById('medio_otro');
    
    medioInputs.forEach(input => {
        input.addEventListener('change', function() {
            const esOtro = this.value === 'Otro';
            
            medioOtro.disabled = !esOtro;
            medioOtro.required = esOtro;
            
            if (!esOtro) {
                medioOtro.value = '';
            }
        });
    });
    
    // ========================
    // MANEJO DE UBIGEOS
    // ========================
    function setupUbigeoHandlers(prefix) {
        const depSelect = document.getElementById(`${prefix}_departamento`);
        const provSelect = document.getElementById(`${prefix}_provincia`);
        const distSelect = document.getElementById(`${prefix}_distrito`);
        
        if (!depSelect || !provSelect || !distSelect) return;
        
        depSelect.addEventListener('change', function() {
            const departamento = this.value;
            provSelect.innerHTML = '<option value="">-- Selecciona --</option>';
            distSelect.innerHTML = '<option value="">-- Selecciona --</option>';
            provSelect.disabled = true;
            distSelect.disabled = true;
            
            if (departamento) {
                const provincias = [...new Set(
                    ubigeosData
                        .filter(u => u.departamento === departamento)
                        .map(u => u.provincia)
                )].sort();
                
                provincias.forEach(prov => {
                    const option = document.createElement('option');
                    option.value = prov;
                    option.textContent = prov;
                    provSelect.appendChild(option);
                });
                
                provSelect.disabled = false;
            }
        });
        
        provSelect.addEventListener('change', function() {
            const departamento = depSelect.value;
            const provincia = this.value;
            distSelect.innerHTML = '<option value="">-- Selecciona --</option>';
            distSelect.disabled = true;
            
            if (departamento && provincia) {
                const distritos = ubigeosData
                    .filter(u => u.departamento === departamento && u.provincia === provincia)
                    .map(u => u.distrito)
                    .sort();
                
                distritos.forEach(dist => {
                    const option = document.createElement('option');
                    option.value = dist;
                    option.textContent = dist;
                    distSelect.appendChild(option);
                });
                
                distSelect.disabled = false;
            }
        });
    }
    
    // Configurar ubigeos para nacimiento, domicilio y apoderado
    setupUbigeoHandlers('nacimiento');
    setupUbigeoHandlers('domicilio');
    setupUbigeoHandlers('apoderado');
    
    // ========================
    // MANEJO DE FOTO Y CROPPER
    // ========================
    const openCropperBtn = document.getElementById('openCropper');
    const cropperModal = new bootstrap.Modal(document.getElementById('cropperModal'));
    const fotoInput = document.getElementById('fotoInput');
    const cropperImage = document.getElementById('cropperImage');
    const cropAndSaveBtn = document.getElementById('cropAndSave');
    const avatarContainer = document.getElementById('avatarContainer');
    
    openCropperBtn.addEventListener('click', function() {
        cropperModal.show();
    });
    
    fotoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    cropperImage.src = e.target.result;
                    cropperImage.style.display = 'block';
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(cropperImage, {
                        aspectRatio: 354 / 418,
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        background: false
                    });
                };
                reader.readAsDataURL(file);
            } else {
                alert('Por favor seleccione un archivo de imagen válido');
            }
        }
    });
    
    cropAndSaveBtn.addEventListener('click', function() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({
                width: 354,
                height: 418
            });
            
            canvas.toBlob(function(blob) {
                currentPhotoFile = new File([blob], 'foto_postulante.jpg', { type: 'image/jpeg' });
                
                // Crear un input file virtual para el envío
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(currentPhotoFile);
                document.getElementById('foto_postulante').files = dataTransfer.files;
                
                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarContainer.innerHTML = `<img src="${e.target.result}" alt="Foto" class="rounded-circle" style="width:100px;height:100px;object-fit:cover;">`;
                };
                reader.readAsDataURL(currentPhotoFile);
                
                cropperModal.hide();
            }, 'image/jpeg', 0.8);
        }
    });
    
    // ========================
    // VALIDACIÓN DE ARCHIVOS
    // ========================
    function validateFile(file) {
        if (!file) return { valid: false, message: 'No se ha seleccionado ningún archivo' };
        
        if (!FILE_CONFIG.allowedTypes.includes(file.type)) {
            return { valid: false, message: 'Tipo de archivo no permitido. Solo se permiten PDF, JPG, JPEG y PNG' };
        }
        
        if (file.size > FILE_CONFIG.maxSize) {
            return { valid: false, message: `El archivo es demasiado grande. Máximo ${FILE_CONFIG.maxSize / 1024 / 1024}MB` };
        }
        
        if (file.size === 0) {
            return { valid: false, message: 'El archivo está vacío' };
        }
        
        return { valid: true };
    }
    
    // Validar archivos al seleccionarlos
    document.querySelectorAll('input[type="file"]').forEach(fileInput => {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const validation = validateFile(file);
                
                if (!validation.valid) {
                    alert(validation.message);
                    this.value = '';
                }
            }
        });
    });
    
    // ========================
    // FUNCIONES DE MANEJO DE ERRORES
    // ========================
    function showLoading() {
        Swal.fire({
            title: 'Procesando...',
            text: 'Estamos registrando tu inscripción',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    function showSmartError(title, message, errorCode = null) {
        // Procesar el mensaje para mostrar texto limpio
        let displayMessage = '';
        
        if (Array.isArray(message)) {
            // Es un array de errores de validación
            displayMessage = message.join('<br>• ');
            displayMessage = '• ' + displayMessage;
        } else {
            // Es un string simple
            displayMessage = String(message);
        }
        
        Swal.fire({
            icon: 'error',
            title: title,
            html: `
                <div class="error-message">
                    <p class="mb-3">${displayMessage}</p>
                </div>
            `,
            showDenyButton: true,
            showCancelButton: true,
            denyButtonText: '🔍 Consultar Estado',
            confirmButtonText: 'Volver',
            cancelButtonText: '📞 Contactar Soporte',
            reverseButtons: true,
            allowOutsideClick: true,
            allowEscapeKey: true,
            showCloseButton: true,
            customClass: {
                popup: 'swal2-custom-error',
                confirmButton: 'swal2-confirm btn-secondary',
                denyButton: 'swal2-deny btn-primary',
                cancelButton: 'swal2-cancel btn-outline-primary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Botón "Volver" - no hacer nada, solo cerrar
                return;
            } else if (result.isDenied) {
                // Botón "Consultar Estado"
                handleErrorAction('check_status', errorCode);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Botón "Contactar Soporte"
                handleErrorAction('contact_support', errorCode);
            }
            // Si se cierra con X, Escape o click afuera, no hacer nada
        });
    }
    
    function showSuccessWithActions(data) {
        let countdown = 10;
        let countdownInterval;
        
        const swalWithCountdown = Swal.fire({
            icon: 'success',
            title: '¡Inscripción Exitosa!',
            html: `
                <div class="mb-3">
                    <strong>Código:</strong> <span class="text-primary">${data.codigo || 'INS-2024-001'}</span><br>
                    <strong>Carrera:</strong> ${data.carrera || 'Tu carrera seleccionada'}
                </div>
                <div class="alert alert-info">
                    <small>💡 Guarda tu código de inscripción para futuras consultas</small>
                </div>
                <div class="alert alert-warning mt-3">
                    <small>⏱️ Serás redirigido a la página principal en <strong id="countdown">${countdown}</strong> segundos</small>
                </div>
            `,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '📄 Descargar Ficha',
            denyButtonText: '🏠 Ir al Inicio',
            cancelButtonText: '👀 Ver Inscripción',
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCloseButton: false,
            didOpen: () => {
                countdownInterval = setInterval(() => {
                    countdown--;
                    const countdownElement = document.getElementById('countdown');
                    if (countdownElement) {
                        countdownElement.textContent = countdown;
                    }
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        Swal.close();
                        window.location.href = '../index.php';
                    }
                }, 1000);
            },
            willClose: () => {
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
            }
        });
        
        swalWithCountdown.then((result) => {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            
            if (result.isConfirmed) {
                downloadFicha(data.codigo);
            } else if (result.isDenied) {
                window.location.href = '../index.php';
            } else if (result.isDismissed && result.dismiss !== Swal.DismissReason.timer) {
                window.location.href = `../user/ver_inscripcion.php?codigo=${data.codigo || 'INS-2024-001'}`;
            }
        });
    }
    
    function handleErrorAction(action, errorCode) {
        switch(action) {
            case 'check_status':
                window.location.href = '../user/consultar_estado.php';
                break;
                
            case 'contact_support':
                window.open('../help/contacto.php', '_blank');
                break;
                
            default:
                // Para cualquier otra acción o "Volver", no hacer nada
                break;
        }
    }
    
    function downloadFicha(codigo) {
        const link = document.createElement('a');
        link.href = `../reports/ficha_inscripcion.php?codigo=${codigo}`;
        link.download = `ficha_inscripcion_${codigo}.pdf`;
        link.click();
        
        setTimeout(() => {
            Swal.fire({
                title: '¿Qué deseas hacer ahora?',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Dashboard',
                denyButtonText: 'Página Principal',
                cancelButtonText: 'Quedarme aquí'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../user/dashboard.php';
                } else if (result.isDenied) {
                    window.location.href = '../index.php';
                }
            });
        }, 1000);
    }
    
    // ========================
    // ENVÍO DEL FORMULARIO
    // ========================
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('=== INICIANDO VALIDACIÓN DEL FORMULARIO ===');
        
        // Verificar estado de campos del apoderado antes de validar
        const parentesco = document.querySelector('input[name="ap_parentesco"]:checked')?.value;
        console.log('Parentesco antes de validar:', parentesco);
        
        if (parentesco && parentesco !== 'Ninguno') {
            console.log('=== VERIFICACIÓN PRE-VALIDACIÓN APODERADO ===');
            const camposApoderado = ['ap_dni', 'ap_nombres', 'ap_apellido_paterno', 'ap_fecha_nacimiento'];
            
            camposApoderado.forEach(campoId => {
                const campo = document.getElementById(campoId);
                console.log(`Campo ${campoId}:`, {
                    existe: !!campo,
                    valor: campo?.value,
                    valorTrim: campo?.value?.trim(),
                    disabled: campo?.disabled,
                    required: campo?.required,
                    elemento: campo
                });
            });
        }
        
        // Validar paso 2
        const errors = validateStep2();
        console.log('Errores encontrados:', errors);
        
        if (errors.length > 0) {
            showSmartError('Datos Incompletos', errors);
            return;
        }
        
        console.log('Validación exitosa, enviando formulario...');
        
        // Mostrar loading elegante
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        showLoading();
        submitBtn.disabled = true;
        
        // Preparar y enviar datos
        const formData = new FormData(this);
        
        // Debug: mostrar datos que se van a enviar
        console.log('Datos del formulario a enviar:');
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('ap_')) {
                console.log(`${key}: ${value}`);
            }
        }
        
        fetch('../controllers/guardar_inscripcion.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error ${response.status}: Problema del servidor`);
            }
            return response.json();
        })
        .then(data => {
            Swal.close();
            
            if (data.success) {
                showSuccessWithActions(data);
            } else {
                showSmartError('Error en la inscripción', data.message, data.error_code);
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error:', error);
            showSmartError('Error de conexión', error.message);
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // ========================
    // INICIALIZACIÓN
    // ========================
    function init() {
        console.log('=== INICIANDO CONFIGURACIÓN DEL FORMULARIO ===');
        
        // Configurar estado inicial
        updateStepper(1);
        
        // Verificar que todos los elementos necesarios existan
        const elementosRequeridos = [
            'ap_dni', 'ap_nombres', 'ap_apellido_paterno', 'ap_apellido_materno',
            'ap_fecha_nacimiento', 'ap_telefono', 'ap_direccion',
            'apoderado_departamento', 'apoderado_provincia', 'apoderado_distrito'
        ];
        
        console.log('Verificando elementos del apoderado:');
        elementosRequeridos.forEach(id => {
            const elemento = document.getElementById(id);
            console.log(`${id}: ${!!elemento ? '✅ Existe' : '❌ No existe'}`);
        });
        
        // Configurar apoderado con timing correcto
        setTimeout(() => {
            console.log('=== CONFIGURANDO APODERADO (200ms) ===');
            
            // Agregar event listeners a todos los radio buttons de parentesco
            const parentescoRadios = document.querySelectorAll('input[name="ap_parentesco"]');
            console.log('Radio buttons de parentesco encontrados:', parentescoRadios.length);
            
            parentescoRadios.forEach((radio, index) => {
                console.log(`Radio ${index}: value="${radio.value}", checked=${radio.checked}`);
                radio.addEventListener('change', handleParentescoChange);
            });
            
            // Configurar estado inicial del apoderado
            const parentescoNinguno = document.querySelector('input[name="ap_parentesco"][value="Ninguno"]');
            if (parentescoNinguno) {
                console.log('Configurando estado inicial: Ninguno seleccionado');
                parentescoNinguno.checked = true;
                handleParentescoChange();
            } else {
                console.log('❌ No se encontró el radio button "Ninguno"');
            }
            
            console.log('Event listeners del apoderado configurados');
        }, 200);
        
        // Configurar otros elementos con timing adicional
        setTimeout(() => {
            console.log('=== CONFIGURANDO OTROS ELEMENTOS (300ms) ===');
            
            // Trigger inicial para discapacidad
            const discapacidadNinguna = document.querySelector('input[name="discapacidad"][value="Ninguna"]');
            if (discapacidadNinguna) {
                discapacidadNinguna.checked = true;
                discapacidadNinguna.dispatchEvent(new Event('change'));
                console.log('✅ Discapacidad configurada');
            } else {
                console.log('❌ No se encontró el radio button de discapacidad');
            }
            
            // Trigger inicial para medio de difusión
            const medioInternet = document.querySelector('input[name="medio"][value="Internet"]');
            if (medioInternet) {
                medioInternet.checked = true;
                medioInternet.dispatchEvent(new Event('change'));
                console.log('✅ Medio de difusión configurado');
            } else {
                console.log('❌ No se encontró el radio button de medio de difusión');
            }
            
            console.log('Configuración inicial completada');
        }, 300);
        
        // Verificación final después de todo el setup
        setTimeout(() => {
            console.log('=== VERIFICACIÓN FINAL (500ms) ===');
            const apNombres = document.getElementById('ap_nombres');
            const parentesco = document.querySelector('input[name="ap_parentesco"]:checked')?.value;
            
            console.log('Estado final del apoderado:');
            console.log('- Parentesco seleccionado:', parentesco);
            console.log('- Campo ap_nombres existe:', !!apNombres);
            console.log('- Campo ap_nombres disabled:', apNombres?.disabled);
            console.log('- Campo ap_nombres required:', apNombres?.required);
            console.log('- Campo ap_nombres value:', apNombres?.value);
        }, 500);
        
        console.log('Formulario de inscripción inicializado correctamente');
    }
    
    // Ejecutar inicialización
    init();
});

// ========================
// FUNCIONES AUXILIARES
// ========================

// Función para formatear números de teléfono
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 9) {
        value = value.slice(0, 9);
    }
    input.value = value;
}

// Función para formatear DNI
function formatDNI(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 8) {
        value = value.slice(0, 8);
    }
    input.value = value;
}

// Aplicar formateo a campos específicos
document.addEventListener('DOMContentLoaded', function() {
    // Formatear DNI
    const dniInputs = document.querySelectorAll('#dni, #ap_dni');
    dniInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatDNI(this);
        });
    });
    
    // Formatear teléfonos
    const phoneInputs = document.querySelectorAll('#telefono, #ap_telefono');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    });
});