<?php
require "bbdd.php";
session_start();
header('Content-Type: application/json; charset=UTF-8');

/* ─── Seguridad ─── */
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 1) {
    http_response_code(401);
    echo json_encode(['error'=>'No autorizado']);
    exit;
}
$con = conectar();

/* ─── GET lista ─── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $result = $con->query("SELECT * FROM proyectos ORDER BY id_proyecto DESC");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

/* ─── POST CRUD ─── */
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$a = $data['accion'] ?? '';

switch ($a) {
case 'crear':
    $stmt = $con->prepare(
        "INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin)
         VALUES (?,?,?,?)");
    $stmt->bind_param('ssss',
        $data['nombre'], $data['descripcion'],
        $data['fecha_inicio'], $data['fecha_fin']);
    $stmt->execute();
    echo json_encode(['status'=>'ok','id'=>$con->insert_id]);
    break;

case 'modificar':
    $stmt = $con->prepare(
        "UPDATE proyectos SET nombre=?, descripcion=?, fecha_inicio=?, fecha_fin=?
         WHERE id_proyecto=?");
    $stmt->bind_param('ssssi',
        $data['nombre'], $data['descripcion'],
        $data['fecha_inicio'], $data['fecha_fin'],
        $data['id_proyecto']);
    $stmt->execute();
    echo json_encode(['status'=>'ok']);
    break;

    case 'eliminar':
        $id = $data['id_proyecto'];
    
        /* comprobar si hay registros asociados */
        $stmt = $con->prepare("SELECT COUNT(*) FROM registro WHERE proyecto=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
    
        if ($total > 0) {
            /* devolver 409 + mensaje */
            http_response_code(409);
            echo json_encode([
                'error'  => 'No se puede eliminar: hay '.$total.' registro(s) asociado(s)'
            ]);
            break;
        }
    
        /* si no hay registros, eliminar */
        $stmt = $con->prepare("DELETE FROM proyectos WHERE id_proyecto=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['status'=>'ok']);
        break;
    

default:
    http_response_code(400);
    echo json_encode(['error'=>'Acción inválida']);
}
