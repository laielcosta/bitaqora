<?php
require("bbdd.php");
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 1) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$con = conectar();

// Obtener lista de usuarios
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $usuarios = mysqli_query($con, "SELECT * FROM usuarios");
    $lista = [];
    while ($u = mysqli_fetch_assoc($usuarios)) {
        $lista[] = $u;
    }
    echo json_encode($lista);
    exit();
}

// Crear, modificar, eliminar
$data = json_decode(file_get_contents("php://input"), true);

if ($data['accion'] == 'crear') {
    $nombre = $data['nombre'];
    $pass = $data['pass'];
    $tipo = $data['tipo'];
    mysqli_query($con, "INSERT INTO usuarios (nombre, pass, tipo) VALUES ('$nombre', '$pass', $tipo)");
    echo json_encode(['status' => 'ok']);
}

if ($data['accion'] == 'modificar') {
    $id = $data['id_usuario'];
    $nombre = $data['nombre'];
    $pass = $data['pass'];
    $tipo = $data['tipo'];
    mysqli_query($con, "UPDATE usuarios SET nombre='$nombre', pass='$pass', tipo='$tipo' WHERE id_usuario=$id");
    echo json_encode(['status' => 'ok']);
}

if ($data['accion'] == 'eliminar') {
    $id = $data['id_usuario'];
    mysqli_query($con, "DELETE FROM usuarios WHERE id_usuario = $id");
    echo json_encode(['status' => 'ok']);
}
