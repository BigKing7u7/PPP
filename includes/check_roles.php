<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function requireRol(array $roles) {
  if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $roles)) {
    header('Location: ../auth/login.php');
    exit;
  }
}
