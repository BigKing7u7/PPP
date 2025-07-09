<?php
// includes/dbconnection.php

// Configuración de la conexión a la base de datos
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
try {
    // Establecemos la conexión PDO
    $dbh = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Registramos el error en un archivo de log y mostramos un mensaje genérico
    error_log($e->getMessage(), 3, 'logs/db_errors.log');
    exit('Error en la conexión a la base de datos. Por favor, inténtelo más tarde.');
}
?>
