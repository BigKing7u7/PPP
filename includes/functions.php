<?php
function limpiarEntrada($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

function calcularEdad($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d) return 0;
    $hoy = new DateTime('today');
    return $d->diff($hoy)->y;
}
