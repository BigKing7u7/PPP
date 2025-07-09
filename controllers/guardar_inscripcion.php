<?php
// controllers/guardar_inscripcion.php
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/dbconnection.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Configuración de archivos (usando constantes centralizadas)
const FILE_CONFIG = [
    'maxSize' => MAX_UPLOAD_SIZE,
    'allowedExtensions' => ALLOWED_EXTENSIONS,
    'documentosObligatorios' => DOCUMENTOS_OBLIGATORIOS
];

try {
    $dbh->beginTransaction();

    // 1) Datos principales del postulante
    $dni               = limpiarEntrada($_POST['dni'] ?? '');
    $nombres           = limpiarEntrada($_POST['nombres'] ?? '');
    $apellido_paterno  = limpiarEntrada($_POST['apellido_paterno'] ?? '');
    $apellido_materno  = limpiarEntrada($_POST['apellido_materno'] ?? '');
    $fecha_nacimiento  = $_POST['fecha_nacimiento'] ?? null;
    $ano_egreso        = intval($_POST['ano_egreso'] ?? 0);
    $genero_nombre     = $_POST['genero'] ?? '';
    $telefono          = limpiarEntrada($_POST['telefono'] ?? '');
    $email             = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
    $direccion         = limpiarEntrada($_POST['direccion'] ?? '');

    // Información adicional (estas van en la tabla inscripcion)
    $discapacidad      = $_POST['discapacidad'] ?? 'Ninguna';
    $desc_discapacidad = ($discapacidad === 'Sí') ? limpiarEntrada($_POST['desc_discapacidad'] ?? '') : '';
    $medio             = $_POST['medio'] ?? 'Internet';
    $medio_otro        = ($medio === 'Otro') ? limpiarEntrada($_POST['medio_otro'] ?? '') : '';

    // Ubigeos
    $nac_dep   = limpiarEntrada($_POST['nacimiento_departamento'] ?? '');
    $nac_prov  = limpiarEntrada($_POST['nacimiento_provincia'] ?? '');
    $nac_dist  = limpiarEntrada($_POST['nacimiento_distrito'] ?? '');
    
    $dom_dep   = limpiarEntrada($_POST['domicilio_departamento'] ?? '');
    $dom_prov  = limpiarEntrada($_POST['domicilio_provincia'] ?? '');
    $dom_dist  = limpiarEntrada($_POST['domicilio_distrito'] ?? '');

    // Validaciones básicas
    if (!preg_match('/^\d{8}$/', $dni)) {
        throw new Exception('DNI inválido');
    }
    if (empty($nombres) || empty($apellido_paterno) || empty($fecha_nacimiento) || !$email) {
        throw new Exception('Faltan datos obligatorios del postulante');
    }
    if ($ano_egreso < 1900 || $ano_egreso > intval(date('Y'))) {
        throw new Exception('Año de egreso inválido');
    }

    // Verificar si el DNI ya existe
    $stmt = $dbh->prepare("SELECT id FROM postulante WHERE dni = :dni");
    $stmt->execute([':dni' => $dni]);
    if ($stmt->fetchColumn()) {
        throw new Exception('Ya existe un postulante con este DNI');
    }

    // Verificar si el email ya existe
    $stmt = $dbh->prepare("SELECT id FROM postulante WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn()) {
        throw new Exception('Ya existe un postulante con este email');
    }

    // Obtener ubigeo de nacimiento
    $stmt = $dbh->prepare("SELECT id FROM ubigeo WHERE departamento = :dep AND provincia = :prov AND distrito = :dist");
    $stmt->execute([':dep' => $nac_dep, ':prov' => $nac_prov, ':dist' => $nac_dist]);
    $ubigeo_nacimiento_id = $stmt->fetchColumn();
    if (!$ubigeo_nacimiento_id) {
        throw new Exception('Ubigeo de nacimiento no encontrado');
    }

    // Obtener ubigeo de domicilio
    $stmt->execute([':dep' => $dom_dep, ':prov' => $dom_prov, ':dist' => $dom_dist]);
    $ubigeo_domicilio_id = $stmt->fetchColumn();
    if (!$ubigeo_domicilio_id) {
        throw new Exception('Ubigeo de domicilio no encontrado');
    }

    // Obtener el ID de género
    $stmt = $dbh->prepare("SELECT id FROM genero WHERE nombre = :nom");
    $stmt->execute([':nom' => $genero_nombre]);
    $genero_id = $stmt->fetchColumn();
    if (!$genero_id) {
        throw new Exception('Género inválido');
    }

    // Manejo de foto del postulante
    $foto_filename = null;
    if (isset($_FILES['foto_postulante']) && $_FILES['foto_postulante']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/fotos';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $foto_ext = strtolower(pathinfo($_FILES['foto_postulante']['name'], PATHINFO_EXTENSION));
        if (!in_array($foto_ext, FILE_CONFIG['allowedExtensions'])) {
            throw new Exception('Formato de foto no válido. Solo JPG, JPEG o PNG');
        }
        
        if ($_FILES['foto_postulante']['size'] > FILE_CONFIG['maxSize']) {
            throw new Exception('La foto es demasiado grande. Máximo 2MB');
        }
        
        $foto_filename = $dni . '_' . time() . '.' . $foto_ext;
        $foto_path = $uploadDir . '/' . $foto_filename;
        
        if (!move_uploaded_file($_FILES['foto_postulante']['tmp_name'], $foto_path)) {
            throw new Exception('Error al guardar la foto');
        }
    }

    // Insertar postulante (solo con las columnas que existen en la tabla)
    $stmt = $dbh->prepare("
        INSERT INTO postulante (
            dni, apellido_paterno, apellido_materno, nombres, fecha_nacimiento, 
            genero_id, telefono, email, direccion, ubigeo_id, ano_egreso, 
            ubigeo_nacimiento_id, foto, estado_inscripcion
        ) VALUES (
            :dni, :apPat, :apMat, :nom, :fNac, :gen, :tel, :email, :dir, 
            :ubi_dom, :ano, :ubi_nac, :foto, 0
        )
    ");
    $stmt->execute([
        ':dni'         => $dni,
        ':apPat'       => $apellido_paterno,
        ':apMat'       => $apellido_materno,
        ':nom'         => $nombres,
        ':fNac'        => $fecha_nacimiento,
        ':gen'         => $genero_id,
        ':tel'         => $telefono,
        ':email'       => $email,
        ':dir'         => $direccion,
        ':ubi_dom'     => $ubigeo_domicilio_id,
        ':ano'         => $ano_egreso,
        ':ubi_nac'     => $ubigeo_nacimiento_id,
        ':foto'        => $foto_filename
    ]);
    $postulante_id = $dbh->lastInsertId();

    // 2) Insertar en inscripcion (aquí van discapacidad, desc_discapacidad, medio_difusion, medio_otro)
    $proceso_admision = limpiarEntrada($_POST['proceso_admision'] ?? '');
    $modalidad_id     = intval($_POST['modalidad_id'] ?? 0);
    $tipo_exoneracion_id = !empty($_POST['tipo_exoneracion_id']) ? intval($_POST['tipo_exoneracion_id']) : null;

    // Validar que los IDs existan en las tablas correspondientes
    $stmt = $dbh->prepare("SELECT id FROM modalidad WHERE id = :id");
    $stmt->execute([':id' => $modalidad_id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Modalidad inválida');
    }

    if ($tipo_exoneracion_id !== null) {
        $stmt = $dbh->prepare("SELECT id FROM tipo_exoneracion WHERE id = :id");
        $stmt->execute([':id' => $tipo_exoneracion_id]);
        if (!$stmt->fetchColumn()) {
            throw new Exception('Tipo de exoneración inválido');
        }
    }

    // Insertar inscripcion (con discapacidad, desc_discapacidad, medio_difusion, medio_otro)
    $stmt = $dbh->prepare("
        INSERT INTO inscripcion (
            postulante_id, proceso_admision, modalidad_id, tipo_exoneracion_id,
            discapacidad, desc_discapacidad, medio_difusion, medio_otro
        ) VALUES (
            :post, :proc, :mod, :tipo, :disc, :desc_disc, :medio, :medio_otro
        )
    ");
    $stmt->execute([
        ':post'       => $postulante_id, 
        ':proc'       => $proceso_admision, 
        ':mod'        => $modalidad_id, 
        ':tipo'       => $tipo_exoneracion_id,
        ':disc'       => $discapacidad,
        ':desc_disc'  => $desc_discapacidad,
        ':medio'      => $medio,
        ':medio_otro' => $medio_otro
    ]);
    $inscripcion_id = $dbh->lastInsertId();

    // 3) Insertar programas
    $stmt = $dbh->prepare("INSERT INTO inscripcion_programa (inscripcion_id, programa_id, orden) VALUES (:ins, :prog, :ord)");

    // Validar que los programas existan
    $programa1_id = intval($_POST['programa1_id'] ?? 0);
    $stmt_check = $dbh->prepare("SELECT id FROM programa WHERE id = :id");
    $stmt_check->execute([':id' => $programa1_id]);
    if (!$stmt_check->fetchColumn()) {
        throw new Exception('El primer programa seleccionado no existe');
    }

    // Primera opción
    $stmt->execute([':ins' => $inscripcion_id, ':prog' => $programa1_id, ':ord' => 1]);

    // Segunda opción, si aplica
    if (!empty($_POST['segunda_opcion']) && !empty($_POST['programa2_id'])) {
        $programa2_id = intval($_POST['programa2_id']);
        $stmt_check->execute([':id' => $programa2_id]);
        if (!$stmt_check->fetchColumn()) {
            throw new Exception('El segundo programa seleccionado no existe');
        }
        
        // Verificar que no sea el mismo programa
        if ($programa1_id === $programa2_id) {
            throw new Exception('Los programas de primera y segunda opción deben ser diferentes');
        }
        
        $stmt->execute([':ins' => $inscripcion_id, ':prog' => $programa2_id, ':ord' => 2]);
    }

    // 4) Apoderado (si corresponde)
    if (($_POST['ap_parentesco'] ?? '') !== 'Ninguno') {
        $par       = limpiarEntrada($_POST['ap_parentesco'] ?? '');
        $ap_dni    = limpiarEntrada($_POST['ap_dni'] ?? '');
        $ap_nom    = limpiarEntrada($_POST['ap_nombres'] ?? '');
        $ap_apPat  = limpiarEntrada($_POST['ap_apellido_paterno'] ?? '');
        $ap_apMat  = limpiarEntrada($_POST['ap_apellido_materno'] ?? '');
        $ap_fnac   = $_POST['ap_fecha_nacimiento'] ?? null;
        $ap_tel    = limpiarEntrada($_POST['ap_telefono'] ?? '');
        $ap_dir    = limpiarEntrada($_POST['ap_direccion'] ?? '');

        // Validar datos obligatorios del apoderado
        if (empty($ap_dni) || empty($ap_nom) || empty($ap_apPat) || empty($ap_fnac)) {
            throw new Exception('Faltan datos obligatorios del apoderado: DNI, nombres, apellido paterno y fecha de nacimiento son requeridos');
        }

        if (!preg_match('/^\d{8}$/', $ap_dni)) {
            throw new Exception('DNI del apoderado inválido: debe tener 8 dígitos');
        }

        // Validar fecha de nacimiento del apoderado
        if (!strtotime($ap_fnac)) {
            throw new Exception('Fecha de nacimiento del apoderado inválida');
        }

        // Ubigeo del apoderado
        $ap_dep = limpiarEntrada($_POST['apoderado_departamento'] ?? '');
        $ap_prov = limpiarEntrada($_POST['apoderado_provincia'] ?? '');
        $ap_dist = limpiarEntrada($_POST['apoderado_distrito'] ?? '');

        if (empty($ap_dep) || empty($ap_prov) || empty($ap_dist)) {
            throw new Exception('Ubigeo del apoderado incompleto: departamento, provincia y distrito son requeridos');
        }

        $stmt = $dbh->prepare("SELECT id FROM ubigeo WHERE departamento = :dep AND provincia = :prov AND distrito = :dist");
        $stmt->execute([':dep' => $ap_dep, ':prov' => $ap_prov, ':dist' => $ap_dist]);
        $ub_ap = $stmt->fetchColumn();
        if (!$ub_ap) {
            throw new Exception('Ubigeo del apoderado no encontrado en la base de datos');
        }

        // Insertar en apoderado
        $stmt = $dbh->prepare("
            INSERT INTO apoderado (
                inscripcion_id, parentesco, dni, apellido_paterno, apellido_materno, 
                nombres, fecha_nacimiento, telefono, direccion, ubigeo_id
            ) VALUES (
                :ins, :par, :dni, :apPat, :apMat, :nom, :fNac, :tel, :dir, :ubi
            )
        ");
        $stmt->execute([
            ':ins'   => $inscripcion_id, 
            ':par'   => $par, 
            ':dni'   => $ap_dni, 
            ':apPat' => $ap_apPat, 
            ':apMat' => $ap_apMat, 
            ':nom'   => $ap_nom, 
            ':fNac'  => $ap_fnac, 
            ':tel'   => $ap_tel, 
            ':dir'   => $ap_dir, 
            ':ubi'   => $ub_ap
        ]);
    }

    // 5) Carga de documentos
    $uploadDir = __DIR__ . '/../uploads/documentos';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Obtener documentos de la BD
    $docs = $dbh->query("SELECT id, nombre_doc FROM documento ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $dbh->prepare("
        INSERT INTO documento_inscripcion (
            inscripcion_id, documento_id, file_name, storage_name, extension
        ) VALUES (
            :ins, :doc, :fname, :sname, :ext
        )
    ");

    // Documentos obligatorios (IDs 1-5 según la BD)
    $documentos_obligatorios = FILE_CONFIG['documentosObligatorios'];

    foreach ($docs as $d) {
        $field = 'doc_' . $d['id'];
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $origName = $_FILES[$field]['name'];
            $fileSize = $_FILES[$field]['size'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            
            // Validar extensión
            if (!in_array($ext, FILE_CONFIG['allowedExtensions'])) {
                throw new Exception("Extensión de archivo no permitida para {$d['nombre_doc']}. Solo se permiten: " . implode(', ', FILE_CONFIG['allowedExtensions']));
            }
            
            // Validar tamaño
            if ($fileSize > FILE_CONFIG['maxSize']) {
                throw new Exception("El archivo {$d['nombre_doc']} es demasiado grande. Máximo 2MB.");
            }
            
            // Validar que el archivo no esté vacío
            if ($fileSize === 0) {
                throw new Exception("El archivo {$d['nombre_doc']} está vacío.");
            }
            
            $storage = bin2hex(random_bytes(16));
            $dest = "$uploadDir/{$storage}.$ext";
            
            if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
                throw new Exception("Error al subir el archivo {$d['nombre_doc']}");
            }
            
            $stmt->execute([
                ':ins'   => $inscripcion_id, 
                ':doc'   => $d['id'], 
                ':fname' => $origName, 
                ':sname' => $storage, 
                ':ext'   => $ext
            ]);
        } else {
            // Verificar documentos obligatorios
            if (in_array($d['id'], $documentos_obligatorios)) {
                $error_msg = isset($_FILES[$field]) ? 'Error: ' . $_FILES[$field]['error'] : 'No se subió archivo';
                throw new Exception("El documento '{$d['nombre_doc']}' es obligatorio. $error_msg");
            }
        }
    }

    // Generar código de inscripción usando constantes centralizadas
    $codigo = CODIGO_PREFIX . CODIGO_YEAR . '-' . str_pad($inscripcion_id, 3, '0', STR_PAD_LEFT);
    
    // Obtener nombre del programa principal para la respuesta
    $stmt = $dbh->prepare("
        SELECT p.nombre_programa 
        FROM programa p 
        JOIN inscripcion_programa ip ON p.id = ip.programa_id 
        WHERE ip.inscripcion_id = :ins AND ip.orden = 1
    ");
    $stmt->execute([':ins' => $inscripcion_id]);
    $carrera = $stmt->fetchColumn() ?: 'Carrera seleccionada';

    $dbh->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Inscripción guardada correctamente.', 
        'inscripcion_id' => $inscripcion_id,
        'postulante_id' => $postulante_id,
        'codigo' => $codigo,
        'carrera' => $carrera
    ]);

} catch (Exception $e) {
    $dbh->rollBack();
    error_log("Error en guardar_inscripcion.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $dbh->rollBack();
    error_log("Error de base de datos en guardar_inscripcion.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>