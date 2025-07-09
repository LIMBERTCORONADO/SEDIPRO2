<?php
require_once '../helpers/validaciones.php';
require_once '../config/config.php';

$total_semana = 0;
$limite_litros = 120;
$precio_por_litro = 3.74;
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ci = $_POST['ci'];
    $cantidad = floatval($_POST['cantidad']);
    $surtidor = $_POST['surtidor'];

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE ci = ?");
    $stmt->execute([$ci]);
    $usuario = $stmt->fetch();

    if (!$usuario) die("❌ CI no registrado.");
    if (!validarCronograma($ci)) die("⚠️ Hoy no le corresponde cargar según su CI.");

    $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE id_usuario = ?");
    $stmt->execute([$usuario['id']]);
    $vehiculo = $stmt->fetch();

    if (!$vehiculo) die("❌ Este usuario no tiene ningún vehículo registrado.");

    $stmt = $conn->prepare("SELECT fecha FROM recargas WHERE id_vehiculo = ? ORDER BY fecha DESC LIMIT 1");
    $stmt->execute([$vehiculo['id']]);
    $ultima = $stmt->fetch();
    if ($ultima && diasEntre($ultima['fecha']) < 3) die("⏱️ Esperar 72 horas entre recargas.");

    $total_semana = litrosSemana($conn, $vehiculo['id']);
    if (($total_semana + $cantidad) > $limite_litros) {
        die("❌ No puede cargar. Ya lleva $total_semana litros esta semana. Límite: 120 L.");
    }

    $stmt = $conn->prepare("INSERT INTO recargas (id_vehiculo, fecha, cantidad, surtidor, foto, estado_aprobacion)
                            VALUES (?, NOW(), ?, ?, NULL, 'pendiente')");
    if ($stmt->execute([$vehiculo['id'], $cantidad, $surtidor])) {
        $mensaje = "✅ Recarga registrada correctamente. Total actual: " . ($total_semana + $cantidad) . " litros.";
    } else {
        $mensaje = "❌ Error al registrar recarga.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Recarga</title>
    <link href="../public/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-green-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-2xl bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-bold text-green-700 text-center mb-6">Registrar Recarga</h2>

    <?php if (!empty($mensaje)): ?>
        <p class="text-center font-semibold <?= str_contains($mensaje, '✅') ? 'text-green-600' : 'text-red-600' ?>">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="post" id="formularioRecarga" class="space-y-4">
        <div>
            <label for="ci" class="block font-medium text-gray-700">CI del usuario:</label>
            <input type="text" name="ci" id="ci" required
                   class="w-full border border-gray-300 rounded px-3 py-2">
            <span id="mensajeDia" class="text-sm font-semibold ml-2"></span>
        </div>

        <div>
            <label for="monto" class="block font-medium text-gray-700">Monto (Bs):</label>
            <input type="number" name="monto" id="monto" step="0.01" required
                   class="w-full border border-gray-300 rounded px-3 py-2">
        </div>

        <div>
            <label class="block font-medium text-gray-700">Litros calculados:</label>
            <input type="text" id="litros" readonly class="w-full border bg-gray-100 px-3 py-2 rounded">
            <input type="hidden" name="cantidad" id="cantidadOculta">
        </div>

        <div>
            <label for="surtidor" class="block font-medium text-gray-700">Surtidor:</label>
            <select name="surtidor" class="w-full border border-gray-300 px-3 py-2 rounded">
                <option value="Oasis">Oasis</option>
                <option value="Chaparral">Chaparral</option>
                <option value="Iriarte">Iriarte</option>
                <option value="Paititi">Paititi</option>
                <option value="Pompeya">Pompeya</option>
            </select>
        </div>

        <p class="text-red-600 font-semibold" id="mensajeLimite"></p>

        <div class="text-center">
            <button type="submit" id="botonSubmit"
                    class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-6 rounded">
                Registrar Recarga
            </button>
        </div>
    </form>

    <div class="text-center mt-6">
        <a href="dashboard.php" class="text-blue-600 hover:underline">← Volver al Panel</a>
    </div>
</div>

<!-- Scripts -->
<script>
    const precioPorLitro = 3.74;
    const limiteLitros = 120;
    let litrosAcumulados = 0;

    document.getElementById('monto').addEventListener('input', function () {
        const monto = parseFloat(this.value);
        const mensaje = document.getElementById('mensajeLimite');
        const litrosInput = document.getElementById('litros');
        const cantidadHidden = document.getElementById('cantidadOculta');
        const boton = document.getElementById('botonSubmit');

        if (!isNaN(monto) && monto > 0) {
            const litros = parseFloat((monto / precioPorLitro).toFixed(2));
            litrosInput.value = litros;
            cantidadHidden.value = litros;

            if ((litros + litrosAcumulados) > limiteLitros) {
                mensaje.textContent = `🚫 No puede cargar más de ${limiteLitros} L mesuales.`;
                boton.disabled = true;
            } else {
                mensaje.textContent = '';
                boton.disabled = false;
            }
        } else {
            litrosInput.value = '';
            cantidadHidden.value = '';
            mensaje.textContent = '';
            boton.disabled = false;
        }
    });

    function validarDiaCargar(ci) {
        const ultimo = ci.slice(-1);
        const dia = new Date().getDay();
        const mapa = {
            1: ['1', '2', '3'],  // lunes
            2: ['4', '5', '6'],  // martes
            3: ['7', '8', '9', '0'], // miércoles
            4: ['1', '2', '3'],  // jueves
            5: ['4', '5', '6'],  // viernes
            6: ['7', '8', '9', '0']  // sábado
        };

        const mensaje = document.getElementById('mensajeDia');
        if (!ci || isNaN(ultimo)) {
            mensaje.textContent = '';
            mensaje.style.color = '';
            return;
        }

        if (dia === 0) {
            mensaje.textContent = "✅ Hoy (domingo) todos pueden cargar.";
            mensaje.style.color = 'green';
        } else if (mapa[dia] && mapa[dia].includes(ultimo)) {
            mensaje.textContent = "✅ Hoy le corresponde cargar.";
            mensaje.style.color = 'green';
        } else {
            mensaje.textContent = "🚫 Hoy no le corresponde cargar.";
            mensaje.style.color = 'red';
        }
    }

    document.getElementById('ci').addEventListener('input', function () {
        validarDiaCargar(this.value);
    });
</script>

</body>
</html>
