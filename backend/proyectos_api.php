<?php
require("bbdd.php");
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 1) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$con = conectar();

// Obtener lista de proyectos
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $proyectos = mysqli_query($con, "SELECT * FROM proyectos");
    $lista = [];
    while ($p = mysqli_fetch_assoc($proyectos)) {
        $lista[] = $p;
    }
    echo json_encode($lista);
    exit();
}

// Crear, modificar, eliminar
$data = json_decode(file_get_contents("php://input"), true);

if ($data['accion'] == 'crear') {
    $nombre = $data['nombre'];
    $desc = $data['descripcion'];
    $inicio = $data['fecha_inicio'];
    $fin = $data['fecha_fin'];
    mysqli_query($con, "INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin) VALUES ('$nombre', '$desc', '$inicio', '$fin')");
    echo json_encode(['status' => 'ok']);
}

if ($data['accion'] == 'modificar') {
    $id = $data['id_proyecto'];
    $nombre = $data['nombre'];
    $desc = $data['descripcion'];
    $inicio = $data['fecha_inicio'];
    $fin = $data['fecha_fin'];
    mysqli_query($con, "UPDATE proyectos SET nombre='$nombre', descripcion='$desc', fecha_inicio='$inicio', fecha_fin='$fin' WHERE id_proyecto=$id");
    echo json_encode(['status' => 'ok']);
}

if ($data['accion'] == 'eliminar') {
    $id = $data['id_proyecto'];
    mysqli_query($con, "DELETE FROM proyectos WHERE id_proyecto = $id");
    echo json_encode(['status' => 'ok']);
}
