<?php
// config.php – Parámetros de configuración global

// Base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'admisionnos');
define('DB_USER', 'root');
define('DB_PASS', ''); // Si tienes una contraseña, reemplázala aquí
define('DB_CHARSET', 'utf8mb4');

try {
    // Establecer la conexión con la base de datos
    $dbh = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    // Configurar el manejo de errores para PDO
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Token externo (API DNI)
define('API_TOKEN_DNI', 'caff2796d3d73145cca19c805f60067a24d9dc8d4390bc2d2688b4d7bf213c18');

// Directorios de archivos
define('UPLOADS_DIR', __DIR__ . '/uploads/');
define('FICHAS_DIR', __DIR__ . '/fichas/');
define('LOGS_DIR',    __DIR__ . '/logs/');

// Configuración de archivos (consistente en todo el sistema)
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']);

// Documentos obligatorios según la BD (IDs 1-5)
define('DOCUMENTOS_OBLIGATORIOS', [1, 2, 3, 4, 5]);

// Configuración de códigos de inscripción
define('CODIGO_PREFIX', 'INS-');
define('CODIGO_YEAR', date('Y'));

// Asegurarse de que existan los directorios
foreach ([UPLOADS_DIR, FICHAS_DIR, LOGS_DIR] as $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
}

// Crear subdirectorios si no existen
$subdirs = [
    UPLOADS_DIR . 'fotos',
    UPLOADS_DIR . 'documentos',
    UPLOADS_DIR . 'profiles'
];

foreach ($subdirs as $subdir) {
    if (!is_dir($subdir)) mkdir($subdir, 0755, true);
}
?>
