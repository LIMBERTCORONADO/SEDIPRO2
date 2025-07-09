<?php
// index.php (entrada principal del sistema)
session_start();

if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'operador' || $_SESSION['rol'] === 'admin') {
        header('Location: views/dashboard.php');
    } elseif ($_SESSION['rol'] === 'supervisor') {
        header('Location: views/supervisor_aprobacion.php');
    }
    exit;
} else {
    header('Location: views/login.php');
    exit;
}
