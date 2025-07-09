<?php
require_once '../config/config.php';
require_once '../helpers/validaciones.php';

$mensaje = "";
$resultado = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ci'])) {
    $ci = $_POST['ci'];

    $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE ci = ?");
    $stmt->execute([$ci]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $mensaje = "❌ Usuario no registrado.";
    } else {
        $id_usuario = $usuario['id'];

        $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $vehiculo = $stmt->fetch();

        if (!$vehiculo) {
            $mensaje = "⚠️ No se ha registrado ningún vehículo con este CI.";
        } else {
            $id_vehiculo = $vehiculo['id'];
            $total_semana = litrosSemana($conn, $id_vehiculo);

            $stmt = $conn->prepare("SELECT fecha FROM recargas WHERE id_vehiculo = ? ORDER BY fecha DESC LIMIT 1");
            $stmt->execute([$id_vehiculo]);
            $ultima = $stmt->fetch();

            $dias_espera = $ultima ? diasEntre($ultima['fecha']) : null;
            $puede_cargar_hoy = validarCronograma($ci);

            $resultado = [
                'nombre' => $usuario['nombre'],
                'chasis' => $vehiculo['chasis'],
                'tipo' => $vehiculo['tipo'],
                'servicio' => $vehiculo['servicio'],
                'ultima_carga' => $ultima ? $ultima['fecha'] : 'Nunca',
                'dias_pasados' => $dias_espera,
                'puede_cargar_hoy' => $puede_cargar_hoy,
                'total_semana' => $total_semana,
                'puede_cargar' => ($dias_espera === null || $dias_espera >= 3) && $puede_cargar_hoy
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta por CI</title>
    <link href="../public/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-green-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-2xl bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-bold text-green-700 text-center mb-6">Consultar Disponibilidad de Carga</h2>

    <form method="post" class="space-y-4">
        <div>
            <label class="block text-gray-700 font-medium">CI del usuario:</label>
            <input type="text" name="ci" required
                   class="w-full border border-gray-300 px-3 py-2 rounded">
        </div>
        <div class="text-center">
            <button type="submit"
                    class="bg-green-700 hover:bg-green-800 text-white font-semibold py-2 px-6 rounded">
                Consultar
            </button>
        </div>
    </form>

    <?php if (!empty($mensaje)): ?>
        <p class="mt-4 text-red-600 font-semibold text-center"><?= $mensaje ?></p>
    <?php elseif ($resultado): ?>
        <div class="mt-6 space-y-2 text-gray-800">
            <h3 class="text-lg font-semibold text-green-600">Resultado</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($resultado['nombre']) ?></p>
            <p><strong>Chasis:</strong> <?= htmlspecialchars($resultado['chasis']) ?></p>
            <p><strong>Tipo:</strong> <?= $resultado['tipo'] ?></p>
            <p><strong>Servicio:</strong> <?= $resultado['servicio'] ?></p>
            <p><strong>Última carga:</strong> <?= $resultado['ultima_carga'] ?></p>
            <p><strong>Días desde última carga:</strong> <?= $resultado['dias_pasados'] ?? 'N/A' ?></p>
            <p><strong>Total litros esta semana:</strong> <?= $resultado['total_semana'] ?> L</p>
            <p><strong>¿Puede cargar hoy según cronograma?:</strong>
                <?= $resultado['puede_cargar_hoy'] ? '<span class="text-green-600">✅ Sí</span>' : '<span class="text-red-600">❌ No</span>' ?>
            </p>
            <p><strong>¿Puede realizar una nueva recarga?:</strong>
                <?= $resultado['puede_cargar'] ? '<span class="text-green-600">✅ Sí</span>' : '<span class="text-red-600">⛔ No (Espere 72 hrs o no es su día)</span>' ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="mt-6 text-center">
        <a href="dashboard.php" class="text-blue-600 hover:underline">← Volver al Panel</a>
    </div>
</div>

</body>
</html>
