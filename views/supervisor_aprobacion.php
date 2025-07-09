<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'supervisor') {
    header('Location: login.php');
    exit;
}

require_once '../config/config.php';

// Aprobación o rechazo desde POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['recarga_id'])) {
    $estado = ($_POST['accion'] === 'aprobar') ? 'aprobado' : 'rechazado';

    $stmt = $conn->prepare("UPDATE recargas SET estado_aprobacion = ? WHERE id = ?");
    $stmt->execute([$estado, $_POST['recarga_id']]);
}

// Obtener recargas pendientes
$stmt = $conn->query("
    SELECT r.id, r.fecha, r.cantidad, r.surtidor, r.foto, 
           v.chasis, u.nombre, u.ci
    FROM recargas r
    JOIN vehiculos v ON r.id_vehiculo = v.id
    JOIN usuarios u ON v.id_usuario = u.id
    WHERE r.estado_aprobacion = 'pendiente'
    ORDER BY r.fecha ASC
");
$recargas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Supervisor - Aprobación de Recargas</title>
    <link href="../public/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-green-50 min-h-screen p-6">

    <div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold text-green-700 text-center mb-6">
            Bienvenido Supervisor: <?= htmlspecialchars($_SESSION['nombre']) ?>
        </h2>

        <h3 class="text-xl font-semibold text-green-800 mb-4 text-center">Recargas Pendientes</h3>

        <?php if (count($recargas) === 0): ?>
            <p class="text-center text-gray-600">No hay recargas pendientes.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full table-auto border border-green-300 text-sm">
                    <thead class="bg-green-200 text-green-900">
                        <tr>
                            <th class="px-2 py-1 border">CI</th>
                            <th class="px-2 py-1 border">Nombre</th>
                            <th class="px-2 py-1 border">Chasis</th>
                            <th class="px-2 py-1 border">Fecha</th>
                            <th class="px-2 py-1 border">Cantidad (L)</th>
                            <th class="px-2 py-1 border">Surtidor</th>
                            <th class="px-2 py-1 border">Foto</th>
                            <th class="px-2 py-1 border">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recargas as $r): ?>
                        <tr class="text-center">
                            <td class="border px-2 py-1"><?= htmlspecialchars($r['ci']) ?></td>
                            <td class="border px-2 py-1"><?= htmlspecialchars($r['nombre']) ?></td>
                            <td class="border px-2 py-1"><?= htmlspecialchars($r['chasis']) ?></td>
                            <td class="border px-2 py-1"><?= $r['fecha'] ?></td>
                            <td class="border px-2 py-1"><?= $r['cantidad'] ?></td>
                            <td class="border px-2 py-1"><?= $r['surtidor'] ?></td>
                            <td class="border px-2 py-1">
                                <?php if ($r['foto']): ?>
                                    <a href="../uploads/<?= htmlspecialchars($r['foto']) ?>" target="_blank">
                                        <img src="../uploads/<?= htmlspecialchars($r['foto']) ?>" width="80" class="mx-auto rounded shadow">
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-500">No disponible</span>
                                <?php endif; ?>
                            </td>
                            <td class="border px-2 py-1">
                                <form method="post" class="flex flex-col gap-1">
                                    <input type="hidden" name="recarga_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="accion" value="aprobar"
                                        class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-sm">
                                        ✅ Aprobar
                                    </button>
                                    <button type="submit" name="accion" value="rechazar"
                                        class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm">
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
            <a href="aprobar_permisos.php" class="text-green-700 hover:underline font-medium">📋 Aprobar Permisos Especiales</a><br>
            <a href="../logout.php" class="text-red-600 hover:underline font-medium mt-4 inline-block">← Cerrar sesión</a>
        </div>
    </div>

</body>
</html>
