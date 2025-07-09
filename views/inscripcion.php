<?php
// user/inscripcion.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/dbconnection.php';

// Obtener datos para selects
$queries = [
    'modalidades' => "SELECT id AS modalidad_id, nombre FROM modalidad ORDER BY nombre",
    'programas' => "SELECT id AS programa_id, nombre_programa FROM programa ORDER BY nombre_programa",
    'tiposExo' => "SELECT id AS tipo_exoneracion_id, nombre FROM tipo_exoneracion ORDER BY nombre",
    'ubigeos' => "SELECT id AS ubigeo_id, departamento, provincia, distrito FROM ubigeo ORDER BY departamento, provincia, distrito",
    'docs' => "SELECT id AS documento_id, nombre_doc FROM documento ORDER BY id"
];

$data = [];
foreach($queries as $key => $query) {
    $data[$key] = $dbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

$departamentos = array_unique(array_column($data['ubigeos'], 'departamento'));
sort($departamentos);

// Configuraciones
$config = [
    'parentesco_options' => ['Ninguno', 'Madre', 'Padre', 'Otro'],
    'medios_difusion' => ['Internet', 'Redes Sociales', 'Familia y/o amigos', 'Otro'],
    'apoderado_fields' => [
        ['ap_dni', 'DNI Apoderado', 8, 'text'],
        ['ap_apellido_paterno', 'Apellido Paterno', null, 'text'],
        ['ap_apellido_materno', 'Apellido Materno', null, 'text'],
        ['ap_nombres', 'Nombres', null, 'text'],
        ['ap_fecha_nacimiento', 'Fecha de Nac.', null, 'date'],
        ['ap_telefono', 'Teléfono', 9, 'tel'],
        ['ap_direccion', 'Dirección', null, 'text']
    ],
    // Documentos obligatorios según la BD (IDs 1-5)
    'documentos_obligatorios' => DOCUMENTOS_OBLIGATORIOS
];

// Funciones helper optimizadas
function renderSelect($id, $name, $options, $valueKey, $textKey, $required = false) {
    $req = $required ? 'required' : '';
    $html = "<select id=\"$id\" name=\"$name\" class=\"form-select\" $req>";
    $html .= "<option value=\"\">-- Selecciona --</option>";
    foreach($options as $option) {
        $value = htmlspecialchars($option[$valueKey]);
        $text = htmlspecialchars($option[$textKey]);
        $html .= "<option value=\"$value\">$text</option>";
    }
    return $html . "</select>";
}

function renderRadioGroup($name, $options, $defaultValue = null) {
    $html = "<div class=\"radio-group\">";
    foreach($options as $option) {
        $checked = ($option === $defaultValue) ? 'checked' : '';
        $html .= "<div class=\"form-check\">";
        $html .= "<input class=\"form-check-input\" type=\"radio\" name=\"$name\" value=\"$option\" $checked>";
        $html .= "<label class=\"form-check-label\">$option</label>";
        $html .= "</div>";
    }
    return $html . "</div>";
}

function renderFormSection($title, $icon, $content) {
    return "<div class=\"form-section\">
        <h3 class=\"section-title\">
            <span class=\"section-icon\"><i class=\"bi bi-$icon\"></i></span>
            $title
        </h3>
        $content
    </div>";
}

function renderStep($stepNum, $icon, $title, $description, $active = false) {
    $activeClass = $active ? 'active' : '';
    return "<div class=\"step $activeClass\" data-step=\"$stepNum\">
        <div class=\"step-icon\"><i class=\"bi bi-$icon\"></i></div>
        <div class=\"step-title\">$title</div>
        <div class=\"step-description\">$description</div>
    </div>";
}

// Función para renderizar ubigeo
function renderUbigeoSection($prefix, $departamentos) {
    $depOptions = implode('', array_map(fn($d) => '<option value="' . htmlspecialchars($d) . '">' . htmlspecialchars($d) . '</option>', $departamentos));
    return "<div class=\"row g-3\">
        <div class=\"col-md-3\">
            <label for=\"{$prefix}_departamento\" class=\"form-label required\">Departamento</label>
            <select id=\"{$prefix}_departamento\" name=\"{$prefix}_departamento\" class=\"form-select\" required>
                <option value=\"\">-- Selecciona --</option>$depOptions
            </select>
        </div>
        <div class=\"col-md-3\">
            <label for=\"{$prefix}_provincia\" class=\"form-label required\">Provincia</label>
            <select id=\"{$prefix}_provincia\" name=\"{$prefix}_provincia\" class=\"form-select\" disabled required>
                <option value=\"\">-- Selecciona --</option>
            </select>
        </div>
        <div class=\"col-md-3\">
            <label for=\"{$prefix}_distrito\" class=\"form-label required\">Distrito</label>
            <select id=\"{$prefix}_distrito\" name=\"{$prefix}_distrito\" class=\"form-select\" disabled required>
                <option value=\"\">-- Selecciona --</option>
            </select>
        </div>
    </div>";
}

// Función para renderizar campos de apoderado
function renderApoderadoFields($fields) {
    $html = '';
    
    // Campos básicos del apoderado con nombres exactos que espera el backend
    $apoderadoFields = [
        ['ap_dni', 'DNI', 8, 'text'],
        ['ap_nombres', 'Nombres', 0, 'text'],
        ['ap_apellido_paterno', 'Apellido Paterno', 0, 'text'],
        ['ap_apellido_materno', 'Apellido Materno', 0, 'text'],
        ['ap_fecha_nacimiento', 'Fecha de Nacimiento', 0, 'date'],
        ['ap_telefono', 'Teléfono', 9, 'tel'],
        ['ap_direccion', 'Dirección', 0, 'text']
    ];
    
    foreach ($apoderadoFields as $field) {
        $pattern = $field[2] ? "pattern=\"\\d{$field[2]}\"" : '';
        $required = in_array($field[0], ['ap_dni', 'ap_nombres', 'ap_apellido_paterno', 'ap_fecha_nacimiento']) ? 'required' : '';
        $asterisk = in_array($field[0], ['ap_dni', 'ap_nombres', 'ap_apellido_paterno', 'ap_fecha_nacimiento']) ? '<span class="text-danger">*</span>' : '';
        
        $html .= "<div class=\"col-md-4 ap-field\">
            <label for=\"{$field[0]}\" class=\"form-label\">{$field[1]} $asterisk</label>
            <input type=\"{$field[3]}\" class=\"form-control\" id=\"{$field[0]}\" name=\"{$field[0]}\" $pattern $required disabled>
        </div>";
    }
    
    return $html;
}

// Función para renderizar documentos (corregida para ser consistente con la BD)
function renderDocumentFields($docs, $documentosObligatorios) {
    return implode('', array_map(function($doc) use ($documentosObligatorios) {
        $required = in_array($doc['documento_id'], $documentosObligatorios) ? 'required' : '';
        $asterisk = in_array($doc['documento_id'], $documentosObligatorios) ? '<span class="text-danger">*</span>' : '';
        return "<div class=\"col-md-6\">
            <label class=\"form-label\">" . htmlspecialchars($doc['nombre_doc']) . " $asterisk</label>
            <input type=\"file\" class=\"form-control\" name=\"doc_{$doc['documento_id']}\" accept=\".pdf,.jpg,.jpeg,.png\" $required>
        </div>";
    }, $docs));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Inscripción | Admisión NOS</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta2/dist/css/bootstrap-select.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/inscripcion.css">
</head>
<body class="bg-light">

<!-- Contenedor Principal - Formulario Único -->
<div class="container-fluid py-4">
    <div class="stepper-container">
        <div class="stepper">
            <div class="step active" data-step="1">
                <div class="step-icon"><i class="bi bi-info-circle"></i></div>
                <div class="step-title">Información Inicial</div>
                <div class="step-description">Datos básicos y programas</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-icon"><i class="bi bi-person-fill"></i></div>
                <div class="step-title">Datos del Postulante</div>
                <div class="step-description">Completa los detalles</div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title">Formulario de Inscripción</h1>
            <p class="form-subtitle">Complete todos los campos para continuar con su postulación</p>
        </div>

        <!-- FORMULARIO ÚNICO -->
        <form id="inscripcionForm" method="post" action="../controllers/guardar_inscripcion.php" enctype="multipart/form-data" novalidate>
            
            <!-- PASO 1: Información Inicial -->
            <div id="step1" class="form-step active">
                <?= renderFormSection('Información Inicial', 'card-text', '
                    <div id="errorStep1" class="alert alert-danger" style="display:none;"></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="dni" class="form-label required">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni" pattern="\\d{8}" maxlength="8" required autocomplete="off" placeholder="12345678">
                            <div class="invalid-feedback">Ingrese un DNI válido de 8 dígitos</div>
                        </div>
                        <div class="col-md-4">
                            <label for="proceso_admision" class="form-label required">Proceso de Admisión</label>
                            <select id="proceso_admision" name="proceso_admision" class="form-select" required>
                                <option value="">-- Selecciona --</option>
                                <option value="2025-I">2025-I</option>
                            </select>
                            <div class="invalid-feedback">Seleccione un proceso de admisión</div>
                        </div>
                        <div class="col-md-4">
                            <label for="modalidad_id" class="form-label required">Modalidad de Admisión</label>
                            ' . renderSelect('modalidad_id', 'modalidad_id', $data['modalidades'], 'modalidad_id', 'nombre', true) . '
                            <div class="invalid-feedback">Seleccione una modalidad</div>
                        </div>
                        <div id="tipoExoneracionContainer" class="col-md-4" style="display:none;">
                            <label for="tipo_exoneracion_id" class="form-label required">Tipo de Exoneración</label>
                            ' . renderSelect('tipo_exoneracion_id', 'tipo_exoneracion_id', $data['tiposExo'], 'tipo_exoneracion_id', 'nombre') . '
                            <div class="invalid-feedback">Seleccione el tipo de exoneración</div>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="segunda_opcion" name="segunda_opcion" value="1">
                                <label class="form-check-label" for="segunda_opcion">¿Desea agregar una segunda opción de carrera?</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="programa1_id" class="form-label required">Primera Opción de Carrera</label>
                            ' . renderSelect('programa1_id', 'programa1_id', $data['programas'], 'programa_id', 'nombre_programa', true) . '
                            <div class="invalid-feedback">Seleccione su primera opción de carrera</div>
                        </div>
                        <div class="col-md-6" id="programa2Container" style="display:none;">
                            <label for="programa2_id" class="form-label">Segunda Opción de Carrera</label>
                            <select id="programa2_id" name="programa2_id" class="form-select">
                                <option value="">-- Selecciona Programa --</option>
                            </select>
                            <div class="invalid-feedback">Seleccione su segunda opción de carrera</div>
                        </div>
                    </div>
                ') ?>

                <div class="form-actions">
                    <div class="form-progress">
                        <div>Paso 1 de 2</div>
                        <div class="form-progress-bar">
                            <div class="form-progress-fill" style="width: 50%"></div>
                        </div>
                    </div>
                    <button type="button" id="next1" class="btn btn-primary">
                        Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 2: Datos del Postulante -->
            <div id="step2" class="form-step">
                <div class="form-section">
                    <div class="text-center mb-4">
                        <div class="profile-pic-wrapper mx-auto mb-2" style="width:140px;height:140px;">
                            <div id="avatarContainer" class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-2" style="width:140px;height:140px;">
                                <i class="bi bi-person-fill text-white fs-1"></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-center justify-content-center" style="width:100%;max-width:500px;margin:0 auto;">
                            <label for="foto_postulante" class="form-label mb-1 w-100 text-center" style="font-weight:500;color:#444;">Registra tu fotografía <span class="text-danger">*</span></label>
                            <div class="d-flex flex-row align-items-center justify-content-center w-100 gap-2" style="max-width:420px;">
                                <input type="file" class="form-control" id="foto_postulante" name="foto_postulante" accept="image/*" style="font-size:0.95rem;max-width:320px;">
                                <button type="button" id="openCropper" class="btn btn-success" style="height:38px;min-width:56px;">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <h4 class="section-title mb-3">Datos Personales</h4>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="nombres" class="form-label required">Nombres</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required autocomplete="given-name">
                        </div>
                        <div class="col-md-4">
                            <label for="apellido_paterno" class="form-label required">Apellido Paterno</label>
                            <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" required autocomplete="family-name">
                        </div>
                        <div class="col-md-4">
                            <label for="apellido_materno" class="form-label">Apellido Materno</label>
                            <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" autocomplete="family-name">
                        </div>
                        <div class="col-md-4">
                            <label for="dni" class="form-label required">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni" pattern="\\d{8}" maxlength="8" required autocomplete="off" placeholder="12345678">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Sexo</label>
                            <div class="d-flex gap-3 align-items-center mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="genero" id="sexo_m" value="Masculino" checked>
                                    <label class="form-check-label" for="sexo_m">Masculino</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="genero" id="sexo_f" value="Femenino">
                                    <label class="form-check-label" for="sexo_f">Femenino</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="genero" id="sexo_no" value="No especifica">
                                    <label class="form-check-label" for="sexo_no">No especifica</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_nacimiento" class="form-label required">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required autocomplete="bday">
                        </div>
                        <div class="col-md-4">
                            <label for="edad" class="form-label">Edad</label>
                            <input type="text" class="form-control" id="edad" name="edad" readonly>
                        </div>
                    </div>
                </div>

                <?= renderFormSection('Lugar de Nacimiento', 'map', renderUbigeoSection('nacimiento', $departamentos)) ?>

                <?= renderFormSection('Dirección Domiciliaria Actual', 'house-door', 
                    renderUbigeoSection('domicilio', $departamentos) . '
                    <div class="row g-3 mt-3">
                        <div class="col-md-9">
                            <label for="direccion" class="form-label required">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required autocomplete="address-line1">
                        </div>
                    </div>'
                ) ?> 

                <?= renderFormSection('Información de Contacto', 'envelope', '
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label required">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label required">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" pattern="\\d{9}" required autocomplete="tel">
                        </div>
                        <div class="col-md-6">
                            <label for="ano_egreso" class="form-label required">Año de Egreso</label>
                            <input type="number" class="form-control" id="ano_egreso" name="ano_egreso" min="1900" max="' . date('Y') . '" required>
                        </div>
                    </div>
                ') ?>

                <?= renderFormSection('Familiares y/o Apoderado', 'person-lines-fill', '
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">¿Apoderado o familiar?</label>
                            ' . renderRadioGroup('ap_parentesco', $config['parentesco_options'], 'Ninguno') . '
                        </div>' .
                        renderApoderadoFields($config['apoderado_fields']) .
                        '<div class="col-12 mt-3">
                            <h5>Dirección del Apoderado</h5>
                            ' . renderUbigeoSection('apoderado', $departamentos) . '
                        </div>
                    </div>
                ') ?> 

                <?= renderFormSection('Información Adicional', 'info-circle', '
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">¿Presenta discapacidad?</label>
                            ' . renderRadioGroup('discapacidad', ['Ninguna', 'Sí'], 'Ninguna') . '
                        </div>
                        <div class="col-md-6">
                            <label for="desc_discapacidad" class="form-label">Describa (si aplica)</label>
                            <input type="text" class="form-control" id="desc_discapacidad" name="desc_discapacidad" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Medio de difusión</label>
                            ' . renderRadioGroup('medio', $config['medios_difusion'], 'Internet') . '
                        </div>
                        <div class="col-md-6">
                            <label for="medio_otro" class="form-label">Especifique</label>
                            <input type="text" class="form-control" id="medio_otro" name="medio_otro" disabled>
                        </div>
                    </div>
                ') ?>

                <?= renderFormSection('Carga de Documentos', 'file-earmark', '
                    <div class="row g-3">' . renderDocumentFields($data['docs'], $config['documentos_obligatorios']) . '</div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="acepto" name="acepto" value="1" required>
                        <label class="form-check-label" for="acepto">Acepto los Términos y Condiciones *</label>
                    </div>
                ') ?>

                <div class="form-actions">
                    <div class="form-progress">
                        <div>Paso 2 de 2</div>
                        <div class="form-progress-bar">
                            <div class="form-progress-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <button type="button" id="prev2" class="btn btn-secondary px-4 me-2">
                        <i class="bi bi-arrow-left"></i> Volver
                    </button>
                    <button type="submit" class="btn btn-success px-4">
                        Registrar Inscripción <i class="bi bi-check-lg"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cropper -->
<div class="modal fade" id="cropperModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fotografía formal en formato digital</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Resolución recomendada: aprox 354×418 px</p>
                <input type="file" id="fotoInput" accept="image/*" class="form-control mb-3">
                <div><img id="cropperImage" style="max-width:100%"></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="cropAndSave" class="btn btn-success">Recortar y Guardar</button>
            </div>
        </div>
    </div>
</div>


<!-- Datos JSON -->
<script id="programasData" type="application/json"><?= json_encode($data['programas'], JSON_UNESCAPED_UNICODE) ?></script>
<script id="ubigeosData" type="application/json"><?= json_encode($data['ubigeos'], JSON_UNESCAPED_UNICODE) ?></script>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta2/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script principal de inscripción -->
<script src="../js/inscripcion.js"></script>


</body>
</html>