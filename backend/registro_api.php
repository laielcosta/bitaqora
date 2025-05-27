<?php
/**  registro_api.php  –  Alta de usuarios estándar (tipo = 0)  **/
ob_start();                           
ini_set('display_errors', '0');       
ini_set('log_errors',    '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);          
    exit(json_encode(['error' => 'Solo se permite POST']));
}

/* ---------- leer cuerpo JSON ---------- */
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$usuario  = trim($body['usuario']  ?? '');
$password = trim($body['password'] ?? '');

if ($usuario === '' || $password === '') {
    http_response_code(400);
    exit(json_encode(['error' => 'Usuario y contraseña requeridos']));
}

/* ---------- conexión y alta ---------- */
require_once __DIR__ . '/bbdd.php';   

$con = conectar();
if (!$con) {
    http_response_code(500);
    exit(json_encode(['error' => 'Fallo al conectar con la BD']));
}

$stmt = $con->prepare('SELECT 1 FROM usuarios WHERE nombre = ? LIMIT 1');
$stmt->bind_param('s', $usuario);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows) {
    http_response_code(409);
    exit(json_encode(['error' => 'Ese nombre ya está registrado']));
}
$stmt->close();

/* insertar nuevo */
$stmt = $con->prepare(
    'INSERT INTO usuarios (nombre, pass, tipo) VALUES (?, ?, 0)'
);
$stmt->bind_param('ss', $usuario, $password);
if (!$stmt->execute()) {
    http_response_code(500);
    exit(json_encode(['error' => 'Error al insertar: ' . $con->error]));
}

http_response_code(201);             
echo json_encode(['ok' => true]);
