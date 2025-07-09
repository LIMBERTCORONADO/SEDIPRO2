<?php
session_start();
require_once '../config/config.php';
require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = "";
$qr_nombre = "";
$ci = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ci = $_POST['ci'];
    $nombre = $_POST['nombre'];
    $chasis = $_POST['chasis'];
    $tipo = $_POST['tipo'];
    $clase = $_POST['clase'];
    $servicio = $_POST['servicio'];
    $foto = null;

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE ci = ?");
    $stmt->execute([$ci]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $stmt = $conn->prepare("INSERT INTO usuarios (ci, nombre) VALUES (?, ?)");
        $stmt->execute([$ci, $nombre]);
        $usuario_id = $conn->lastInsertId();
    } else {
        $usuario_id = $usuario['id'];
    }

    $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE chasis = ?");
    $stmt->execute([$chasis]);
    if ($stmt->fetch()) {
        $mensaje = "⚠️ Este vehículo ya fue registrado con otro CI.";
    } else {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $nombre_foto = uniqid() . "_" . basename($_FILES["foto"]["name"]);
            $destino = "../uploads/" . $nombre_foto;
            $tipoArchivo = mime_content_type($_FILES["foto"]["tmp_name"]);

            if (strpos($tipoArchivo, "image") === 0) {
                move_uploaded_file($_FILES["foto"]["tmp_name"], $destino);
                $foto = $nombre_foto;
            } else {
                $mensaje = "❌ El archivo no es una imagen válida.";
            }
        }

        $contenidoQR = "CI: $ci\nNombre: $nombre\nChasis: $chasis\nTipo: $tipo\nClase: $clase\nServicio: $servicio";
        $qr_nombre = uniqid('qr_') . '.png';

        Builder::create()
            ->writer(new PngWriter())
            ->data($contenidoQR)
            ->size(300)
            ->margin(10)
            ->build()
            ->saveToFile(__DIR__ . '/../uploads/' . $qr_nombre);

        $stmt = $conn->prepare("INSERT INTO vehiculos (chasis, tipo, clase, servicio, id_usuario, foto, qr) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$chasis, $tipo, $clase, $servicio, $usuario_id, $foto, $qr_nombre])) {
            $mensaje = "✅ Usuario y vehículo registrados con éxito. QR generado.";
        } else {
            $mensaje = "❌ Error al registrar el vehículo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cliente y Vehículo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../public/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-green-100 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-2xl bg-white shadow-lg rounded-lg p-6 sm:p-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-center text-green-700 mb-6">Registrar Cliente y Vehículo</h2>

        <?php if (!empty($mensaje)): ?>
            <p class="mb-4 text-center font-semibold <?= str_contains($mensaje, '✅') ? 'text-green-600' : 'text-red-600' ?>">
                <?= $mensaje ?>
            </p>
            <?php if (!empty($qr_nombre) && file_exists('../uploads/' . $qr_nombre)): ?>
                <div class="flex justify-center">
                    <img src="../uploads/<?= htmlspecialchars($qr_nombre) ?>" alt="Código QR del cliente" class="w-48 h-48 border border-gray-300 rounded shadow">
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <h3 class="text-lg font-semibold text-green-800">Datos del Cliente</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <input type="text" name="ci" placeholder="CI" required class="border border-gray-300 px-3 py-2 rounded w-full">
                <input type="text" name="nombre" placeholder="Nombre completo" required class="border border-gray-300 px-3 py-2 rounded w-full">
            </div>

            <h3 class="text-lg font-semibold text-green-800">Datos del Vehículo</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <input type="text" name="chasis" placeholder="Chasis" required class="border border-gray-300 px-3 py-2 rounded w-full">

                <select name="tipo" class="border border-gray-300 px-3 py-2 rounded w-full">
                    <option value="privado">Privado</option>
                    <option value="publico">Público</option>
                </select>

                <select name="clase" class="border border-gray-300 px-3 py-2 rounded w-full" required>
                    <option value="moto">Moto</option>
                    <option value="auto">Auto</option>
                </select>

                <select name="servicio" class="border border-gray-300 px-3 py-2 rounded w-full">
                    <option value="gasolina">Gasolina</option>
                    <option value="diesel">Diesel</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mt-2">Foto del vehículo:</label>
                <input type="file" name="foto" accept="image/*" capture class="mt-1">
            </div>

            <div class="text-center">
                <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold py-2 px-6 rounded">
                    Registrar Cliente y Vehículo
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="dashboard.php" class="text-green-700 font-medium hover:underline">← Volver al Panel</a>
        </div>
    </div>

</body>
</html>
