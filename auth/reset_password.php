<?php
// includes/check_session.php

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
  // No hay sesión activa
  header('Location: /auth/login.php');
  exit;
}
