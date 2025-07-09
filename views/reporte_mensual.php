<?php
require_once '../config/config.php';

$reporte = null;
$mensaje = "";
$precio_por_litro = 3.74;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chasis = $_POST['chasis'];
    $mes = $_POST['mes'];
    $anio = $_POST['anio'];

    $stmt = $conn->prepare("SELECT v.id, v.chasis, u.nombre, u.ci
                            FROM vehiculos v
                            JOIN usuarios u ON v.id_usuario = u.id
                            WHERE v.chasis = ?");
    $stmt->execute([$chasis]);
    $vehiculo = $stmt->fetch();

    if (!$vehiculo) {
        $mensaje = "❌ Vehículo no encontrado.";
    } else {
        $inicio = "$anio-$mes-01";
        $fin = date("Y-m-t", strtotime($inicio));

        $stmt = $conn->prepare("SELECT fecha, cantidad, surtidor, estado_aprobacion
                                FROM recargas
                                WHERE id_vehiculo = ? AND fecha BETWEEN ? AND ?");
        $stmt->execute([$vehiculo['id'], $inicio, $fin]);
        $recargas = $stmt->fetchAll();

        $total_litros = 0;
        foreach ($recargas as $r) {
            if ($r['estado_aprobacion'] === 'aprobado') {
                $total_litros += $r['cantidad'];
            }
        }

        $reporte = [
            'vehiculo' => $vehiculo,
            'mes' => date('F', strtotime($inicio)),
            'anio' => $anio,
            'recargas' => $recargas,
            'total_litros' => $total_litros,
            'cantidad_recargas' => count($recargas),
            'costo_estimado' => round($total_litros * $precio_por_litro, 2)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual por Vehículo</title>
    <link rel="stylesheet" href="../public/css/estilos.css">
    <style>
        @media print {
            button, form, a { display: none !important; }
        }
    </style>
</head>
<body class="bg-green-100 min-h-screen flex items-center justify-center p-4">

<div class="bg-white w-full max-w-4xl rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-green-700 text-center mb-6">Reporte Mensual de Consumo</h2>

    <form method="post" class="grid md:grid-cols-3 gap-4 mb-6">
        <input type="text" name="chasis" placeholder="Chasis del vehículo" required class="border px-3 py-2 rounded col-span-1">
        <select name="mes" class="border px-3 py-2 rounded">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= str_pad($m, 2, "0", STR_PAD_LEFT) ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
            <?php endfor; ?>
        </select>
        <select name="anio" class="border px-3 py-2 rounded">
            <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="bg-green-700 text-white font-semibold py-2 px-4 rounded hover:bg-green-800 col-span-1">
            Generar reporte
        </button>
    </form>

    <?php if (!empty($mensaje)): ?>
        <p class="text-red-600 font-semibold text-center"><?= $mensaje ?></p>
    <?php elseif ($reporte): ?>
        <div class="space-y-2 text-gray-800">
            <h3 class="text-lg font-semibold text-green-600">Vehículo: <?= $reporte['vehiculo']['chasis'] ?></h3>
            <p><strong>Propietario:</strong> <?= $reporte['vehiculo']['nombre'] ?> (CI: <?= $reporte['vehiculo']['ci'] ?>)</p>
            <p><strong>Mes:</strong> <?= $reporte['mes'] ?> <?= $reporte['anio'] ?></p>
            <p><strong>Total recargas:</strong> <?= $reporte['cantidad_recargas'] ?></p>
            <p><strong>Total litros aprobados:</strong> <?= $reporte['total_litros'] ?> L</p>
            <p class="text-green-700 font-bold"><strong>Costo estimado:</strong> Bs. <?= number_format($reporte['costo_estimado'], 2) ?></p>
        </div>

        <div class="overflow-x-auto mt-4">
            <table class="min-w-full border border-gray-300 text-sm text-left">
                <thead class="bg-green-200">
                    <tr>
                        <th class="px-4 py-2 border">Fecha</th>
                        <th class="px-4 py-2 border">Cantidad (L)</th>
                        <th class="px-4 py-2 border">Surtidor</th>
                        <th class="px-4 py-2 border">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reporte['recargas'] as $r): ?>
                        <tr class="hover:bg-green-50">
                            <td class="px-4 py-2 border"><?= $r['fecha'] ?></td>
                            <td class="px-4 py-2 border"><?= $r['cantidad'] ?></td>
                            <td class="px-4 py-2 border"><?= $r['surtidor'] ?></td>
                            <td class="px-4 py-2 border"><?= ucfirst($r['estado_aprobacion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-center">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                🖨️ Imprimir / Guardar PDF
            </button>
        </div>
    <?php endif; ?>

    <div class="mt-6 text-center">
        <a href="dashboard.php" class="text-blue-600 hover:underline">← Volver al Panel</a>
    </div>
</div>

</body>
</html>
