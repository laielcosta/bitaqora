<?php
require "bbdd.php";
session_start();
header('Content-Type: application/json; charset=UTF-8');

/* ─── sólo administrador (tipo = 1) ─── */
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 1) {
    http_response_code(401);
    echo json_encode(['error'=>'No autorizado']);
    exit;
}

$con = conectar();
if (!$con) {
    http_response_code(500);
    echo json_encode(['error'=>'DB down']);
    exit;
}

/* ─── GET: lista completa ─── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $r = $con->query(
        "SELECT id_usuario, nombre, tipo
         FROM usuarios
         ORDER BY id_usuario DESC"
    );
    echo json_encode($r->fetch_all(MYSQLI_ASSOC));
    exit;
}

/* ─── Decodificar JSON POST ─── */
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

/* ─── POST: crear / modificar / eliminar ─── */
switch ($data['accion'] ?? '') {

    case 'crear':
        // validación mínima
        if (empty($data['nombre']) || empty($data['clave'])) {
            http_response_code(400);
            echo json_encode(['error'=>'Faltan datos obligatorios']);
            exit;
        }
        $stmt = $con->prepare(
            "INSERT INTO usuarios (nombre, pass, tipo)
             VALUES (?, SHA2(?,256), ?)"
        );
        $stmt->bind_param(
            'ssi',
            $data['nombre'],
            $data['clave'],
            $data['tipo']
        );
        $stmt->execute();
        echo json_encode(['status'=>'ok','id'=>$con->insert_id]);
        break;

    case 'modificar':
        // validación mínima
        if (empty($data['id_usuario']) || empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error'=>'Faltan datos obligatorios']);
            exit;
        }
        // construir UPDATE dinámico según contraseña
        $sql = "UPDATE usuarios SET nombre=?, tipo=?";
        if (!empty($data['clave'])) {
            $sql .= ", pass=SHA2(?,256)";
        }
        $sql .= " WHERE id_usuario=?";
        $stmt = $con->prepare($sql);

        if (!empty($data['clave'])) {
            $stmt->bind_param(
                'sssi',
                $data['nombre'],
                $data['tipo'],
                $data['clave'],
                $data['id_usuario']
            );
        } else {
            $stmt->bind_param(
                'sii',
                $data['nombre'],
                $data['tipo'],
                $data['id_usuario']
            );
        }
        $stmt->execute();
        echo json_encode(['status'=>'ok']);
        break;

    case 'eliminar':
        if (empty($data['id_usuario'])) {
            http_response_code(400);
            echo json_encode(['error'=>'Falta id_usuario']);
            exit;
        }
        if ($data['id_usuario'] == $_SESSION['id_usuario']) {
            http_response_code(400);
            echo json_encode(['error'=>'No puedes eliminar tu propia cuenta']);
            exit;
        }
        $stmt = $con->prepare(
            "DELETE FROM usuarios WHERE id_usuario=?"
        );
        $stmt->bind_param('i', $data['id_usuario']);
        $stmt->execute();
        echo json_encode(['status'=>'ok']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error'=>'Acción inválida']);
}
