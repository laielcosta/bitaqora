<?php
// etiquetas_api.php
// API REST para gestión de etiquetas (backend)

// Mostrar errores en desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Validar sesión de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 1) {
    http_response_code(401);
    echo json_encode(['error'=>'No autorizado']);
    exit;
}

header('Content-Type: application/json');
// Incluir y conectar a la base de datos
include_once __DIR__ . '/bbdd.php';
$con = conectar();    // <<< Obtenemos la conexión llamando a la función

// Permitir métodos HTTP desde el frontend
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}

// Responder OK a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Listar todas las etiquetas
        $sql = "SELECT id_etiqueta AS id, nombre FROM etiquetas ORDER BY nombre";
        $result = $con->query($sql);
        $etiquetas = [];
        while ($row = $result->fetch_assoc()) {
            $etiquetas[] = $row;
        }
        echo json_encode($etiquetas);
        break;

    case 'POST':
        // Crear nueva etiqueta
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre de etiqueta requerido']);
            exit;
        }
        $stmt = $con->prepare("INSERT INTO etiquetas (nombre) VALUES (?)");
        $stmt->bind_param('s', $data['nombre']);
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            echo json_encode(['id' => $id, 'nombre' => $data['nombre']]);
        } elseif ($stmt->errno === 1062) {
            http_response_code(409);
            echo json_encode(['error' => 'Etiqueta ya existe']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear etiqueta']);
        }
        break;

    case 'PUT':
        // Actualizar etiqueta
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id']) || empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID y nombre requeridos']);
            exit;
        }
        $stmt = $con->prepare("UPDATE etiquetas SET nombre = ? WHERE id_etiqueta = ?");
        $stmt->bind_param('si', $data['nombre'], $data['id']);
        if ($stmt->execute()) {
            echo json_encode(['id' => $data['id'], 'nombre' => $data['nombre']]);
        } elseif ($stmt->errno === 1062) {
            http_response_code(409);
            echo json_encode(['error' => 'Etiqueta ya existe']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar etiqueta']);
        }
        break;

    case 'DELETE':
        // Borrar etiqueta
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de etiqueta requerido']);
            exit;
        }
        $stmt = $con->prepare("DELETE FROM etiquetas WHERE id_etiqueta = ?");
        $stmt->bind_param('i', $data['id']);
        if ($stmt->execute()) {
            echo json_encode(['deleted' => $data['id']]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar etiqueta']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

$con->close();
