<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/bbdd.php';

$nombre  = trim($_POST['nombre'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$clave   = trim($_POST['clave'] ?? '');

if (!$nombre || !$usuario || !$clave) {
  http_response_code(400);
  echo json_encode(['mensaje' => 'Todos los campos son obligatorios.']);
  exit;
}

// Comprobar si el usuario ya existe
$sql_check = "SELECT id_usuario FROM usuarios WHERE usuario = ?";
$stmt = $db->prepare($sql_check);
$stmt->bind_param('s', $usuario);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  http_response_code(409);
  echo json_encode(['mensaje' => 'Ese nombre de usuario ya está en uso.']);
  exit;
}

// Insertar usuario (solo tipo 0 - usuario normal)
$clave_hash = password_hash($clave, PASSWORD_DEFAULT);
$sql_insert = "INSERT INTO usuarios (nombre, usuario, clave, tipo) VALUES (?, ?, ?, 0)";
$stmt = $db->prepare($sql_insert);
$stmt->bind_param('sss', $nombre, $usuario, $clave_hash);

if ($stmt->execute()) {
  echo json_encode(['mensaje' => 'Usuario registrado correctamente.']);
} else {
  http_response_code(500);
  echo json_encode(['mensaje' => 'Error al registrar usuario.']);
}
