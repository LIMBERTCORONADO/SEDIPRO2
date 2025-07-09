<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'supervisor') {
    header('Location: login.php');
    exit;
}

require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permiso_id'], $_POST['accion'])) {
    $estado = $_POST['accion'] === 'aprobar' ? 'aprobado' : 'rechazado';
    $stmt = $conn->prepare("UPDATE permisos_especiales SET estado_aprobacion = ? WHERE id = ?");
    $stmt->execute([$estado, $_POST['permiso_id']]);
}

// Obtener solicitudes pendientes
$stmt = $conn->query("
    SELECT p.id, p.fecha, p.litros_autorizados, p.motivo,
           u.nombre, u.ci
    FROM permisos_especiales p
    JOIN usuarios u ON p.id_usuario = u.id
    WHERE p.estado_aprobacion = 'pendiente'
    ORDER BY p.fecha ASC
");
$permisos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aprobar Permisos Especiales</title>
    <link href="../public/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-green-50 min-h-screen p-6">

    <div class="max-w-5xl mx-auto bg-white p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold text-center text-green-700 mb-6">
            Permisos Especiales Pendientes
        </h2>

        <?php if (count($permisos) === 0): ?>
            <p class="text-center text-gray-600">No hay solicitudes pendientes.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full border border-green-300 text-sm table-auto">
                    <thead class="bg-green-200 text-green-900">
                        <tr>
                            <th class="px-2 py-1 border">CI</th>
                            <th class="px-2 py-1 border">Nombre</th>
                            <th class="px-2 py-1 border">Fecha</th>
                            <th class="px-2 py-1 border">Litros</th>
                            <th class="px-2 py-1 border">Motivo</th>
                            <th class="px-2 py-1 border">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permisos as $p): ?>
                        <tr class="text-center">
                            <td class="border px-2 py-1"><?= htmlspecialchars($p['ci']) ?></td>
                            <td class="border px-2 py-1"><?= htmlspecialchars($p['nombre']) ?></td>
                            <td class="border px-2 py-1"><?= $p['fecha'] ?></td>
                            <td class="border px-2 py-1"><?= $p['litros_autorizados'] ?></td>
                            <td class="border px-2 py-1 text-left"><?= htmlspecialchars($p['motivo']) ?></td>
                            <td class="border px-2 py-1">
                                <form method="post" class="flex flex-col gap-1">
                                    <input type="hidden" name="permiso_id" value="<?= $p['id'] ?>">
                                    <button name="accion" value="aprobar"
                                        class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-sm">
                                        ✅ Aprobar
                                    </button>
                                    <button name="accion" value="rechazar"
                                        class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded text-sm">
                                        ❌ Rechazar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="supervisor_aprobacion.php" class="text-green-700 hover:underline font-medium">← Volver</a>
        </div>
    </div>

</body>
</html>
