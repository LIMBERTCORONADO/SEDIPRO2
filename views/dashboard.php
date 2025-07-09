<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'operador') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Operador</title>
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../public/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-green-100 min-h-screen">

    <!-- Encabezado -->
    <header class="bg-green-700 text-white py-4 shadow-md">
        <div class="max-w-6xl mx-auto px-4 flex items-center justify-between">
            <h1 class="text-xl font-bold"><i class="fas fa-gas-pump mr-2"></i>SEDIPRO - CARGA TOTAL</h1>
            <span class="text-sm hidden sm:inline">Operador: <?= htmlspecialchars($_SESSION['nombre']); ?></span>
        </div>
    </header>

    <main class="flex items-center justify-center py-10 px-4">
        <div class="w-full max-w-3xl bg-white shadow-lg rounded-lg p-4 sm:p-6 md:p-8">
            <!-- Buscar usuario -->
            <form id="buscarClienteForm" class="flex flex-col sm:flex-row gap-4 items-center justify-center mb-4">
                <label class="font-semibold text-gray-700"><i class="fas fa-id-card mr-1"></i>Buscar CI:</label>
                <input type="text" name="ci" id="ciInput" required class="border border-gray-300 rounded px-3 py-2 w-full sm:w-1/2">
                <button type="submit" class="bg-green-700 text-white px-4 py-2 rounded hover:bg-green-800">
                    <i class="fas fa-search mr-1"></i>Buscar
                </button>
            </form>

            <!-- Mensaje de verificación -->
            <p id="verificandoMensaje" class="hidden text-center text-yellow-600 font-semibold mt-2"></p>

            <!-- Acciones -->
            <div class="grid gap-4 mt-6">
                <a href="registro_recarga.php" class="block bg-green-700 hover:bg-green-800 text-white text-center py-2 px-4 rounded">
                    ➕ Registrar Recarga
                </a>
                <a href="consulta_ci.php" class="block bg-green-600 hover:bg-green-700 text-white text-center py-2 px-4 rounded">
                    🔍 Consultar Recargas
                </a>
                <a href="reporte_mensual.php" class="block bg-green-500 hover:bg-green-600 text-white text-center py-2 px-4 rounded">
                    📄 Reporte Mensual
                </a>
                <a href="solicitar_permiso.php" class="block bg-green-400 hover:bg-green-500 text-white text-center py-2 px-4 rounded">
                    🔐 Solicitar Permiso Especial
                </a>
                <a href="../logout.php" class="block bg-red-600 hover:bg-red-700 text-white text-center py-2 px-4 rounded">
                    🔓 Cerrar sesión
                </a>
            </div>
        </div>
    </main>

    <script>
    document.getElementById('buscarClienteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const ci = document.getElementById('ciInput').value;
        const mensaje = document.getElementById('verificandoMensaje');

        mensaje.textContent = '🔄 Verificando CI...';
        mensaje.classList.remove('hidden');

        fetch(`../api/verificar_usuario.php?ci=${ci}`)
            .then(res => res.json())
            .then(data => {
                mensaje.classList.add('hidden');

                if (data.existe && data.tiene_vehiculo) {
                    window.location.href = 'registro_recarga.php?ci=' + ci;
                } else if (data.existe && !data.tiene_vehiculo) {
                    window.location.href = 'registro_usuario_vehiculo.php?ci=' + ci + '&editar=1';
                } else {
                    window.location.href = 'registro_usuario_vehiculo.php?ci=' + ci;
                }
            })
            .catch(err => {
                mensaje.textContent = '⚠️ Error al consultar. Intente nuevamente.';
                mensaje.classList.remove('hidden');
                console.error('Error al consultar:', err);
            });
    });
    </script>

</body>
</html>
