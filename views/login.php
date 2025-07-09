<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'operador' || $_SESSION['rol'] === 'admin') {
        header('Location: dashboard.php');
    } elseif ($_SESSION['rol'] === 'supervisor') {
        header('Location: supervisor_aprobacion.php');
    }
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../config/config.php';
    require_once '../controllers/UsuarioController.php';

    $ci = $_POST['ci'];
    $contrasena = $_POST['contrasena'];

    $controller = new UsuarioController($conn);
    $error = $controller->login($ci, $contrasena);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión - SEDIPRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../public/css/estilos.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/3f149fb3d6.js" crossorigin="anonymous"></script>
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat flex items-center justify-center bg-[url('../public/images/fondo.jpg')]">

    <div class="w-full max-w-md bg-white/90 backdrop-blur-md shadow-xl p-6 sm:p-8 rounded-lg border-t-4 border-green-700 mx-4">
        <div class="text-center mb-6">
            <i class="fas fa-gas-pump text-green-700 text-4xl"></i>
            <h1 class="text-2xl font-bold text-green-800 mt-2">SEDIPRO - CARGA TOTAL</h1>
            <p class="text-sm text-gray-600">Sistema de Distribución de Combustible</p>
        </div>

        <form method="post" class="space-y-4">
            <div>
                <label for="ci" class="block text-sm font-medium text-gray-700">CI:</label>
                <input type="text" name="ci" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>

            <div>
                <label for="contrasena" class="block text-sm font-medium text-gray-700">Contraseña:</label>
                <input type="password" name="contrasena" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>

            <?php if (!empty($error)): ?>
                <p class="text-red-600 text-sm text-center"><?= $error ?></p>
            <?php endif; ?>

            <div>
                <button type="submit" class="w-full bg-green-700 text-white py-2 rounded hover:bg-green-800 transition duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>Iniciar sesión
                </button>
            </div>
        </form>
    </div>

</body>
</html>
