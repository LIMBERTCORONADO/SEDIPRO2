<?php
$ci = '1234567';
$password_input = '123456';
$hash_from_db = '$2y$10$eByhMT/7xIeb32gPZ8rfGuS4.LjG9D/jY92pR/hKhtjqZ/0BfEOAG'; // Copia esto desde phpMyAdmin

if (password_verify($password_input, $hash_from_db)) {
    echo "✅ Contraseña válida";
} else {
    echo "❌ Contraseña incorrecta";
}
