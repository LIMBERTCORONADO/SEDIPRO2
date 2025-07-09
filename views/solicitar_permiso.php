<?php
require_once '../config/config.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ci = $_POST['ci'];
    $litros = $_POST['litros'];
    $motivo = $_POST['motivo'];

    if ($litros > 120) {
        $mensaje = "⚠️ No se pueden solicitar más de 120 litros por semana.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE ci = ?");
        $stmt->execute([$ci]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $mensaje = "❌ Usuario no encontrado.";
        } else {
            $id_usuario = $usuario['id'];

            $inicio_semana = date("Y-m-d", strtotime("last sunday"));
            $fin_semana = date("Y-m-d 23:59:59", strtotime("next sunday"));

            $stmt = $conn->prepare("SELECT * FROM permisos_especiales WHERE id_usuario = ? AND fecha BETWEEN ? AND ?");
            $stmt->execute([$id_usuario, $inicio_semana, $fin_semana]);
            $existe = $stmt->fetch();

            if ($existe) {
                $mensaje = "⚠️ Ya existe una solicitud de permiso especial esta semana.";
            } else {
                $stmt = $conn->prepare("INSERT INTO permisos_especiales (id_usuario, motivo, litros_autorizados, fecha, estado_aprobacion) VALUES (?, ?, ?, NOW(), 'pendiente')");
                $stmt->execute([$id_usuario, $motivo, $litros]);
                $mensaje = "✅ Solicitud enviada para aprobación.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Permiso Especial</title>
    <link rel="stylesheet" href="../public/css/estilos.css">
</head>
<body class="bg-green-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg bg-white shadow-md rounded-lg p-6 space-y-4">
    <h2 class="text-xl font-bold text-green-700 text-center">Solicitud de Permiso Especial</h2>

    <?php if (!empty($mensaje)): ?>
        <p class="text-center font-semibold <?= str_contains($mensaje, '✅') ? 'text-green-700' : 'text-red-600' ?>">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="post" id="permisoForm" class="space-y-4">
        <div>
            <label class="block font-medium text-gray-700">CI del usuario:</label>
            <input type="text" name="ci" required class="w-full border border-gray-300 rounded px-3 py-2">
        </div>

        <div>
            <label class="block font-medium text-gray-700">Litros solicitados (máx 120):</label>
            <input type="number" name="litros" id="litros" step="0.01" required class="w-full border rounded px-3 py-2">
            <span id="alerta" class="text-red-600 text-sm font-semibold"></span>
        </div>

        <div>
            <label class="block font-medium text-gray-700">Motivo:</label>
            <textarea name="motivo" rows="3" required class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
        </div>

        <button type="submit" id="btnEnviar"
            class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
            Enviar Solicitud
        </button>
    </form>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="text-blue-600 hover:underline">← Volver</a>
    </div>
</div>

<script>
document.getElementById('litros').addEventListener('input', function () {
    const litros = parseFloat(this.value);
    const alerta = document.getElementById('alerta');
    const boton = document.getElementById('btnEnviar');

    if (!isNaN(litros) && litros > 120) {
        alerta.textContent = "🚫 No puede solicitar más de 120 litros.";
        boton.disabled = true;
    } else {
        alerta.textContent = "";
        boton.disabled = false;
    }
});
</script>

</body>
</html>
