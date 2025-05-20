<?php
require("bbdd.php");
session_start();
//Verificar sesion obteniendo el array _SESSION
//file_put_contents("session_debug.txt", print_r($_SESSION, true)); 
ob_start();

$con = conectar();
ob_clean();

if (!$con) {
    responderError(500, "Error de conexión");
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['modo']) && $_GET['modo'] === 'tabla') {
            devolverTablaRegistros($con);
        } else {
            devolverDatos($con);
        }
        break;

    case 'POST':
        registrarActividad($con);
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $datos);
        eliminarRegistro($con, $datos['id']);
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $datos);
        modificarRegistro($con, $datos);
        break;

    default:
        responderError(405, "Método no permitido");
}


// ---------------------- FUNCIONES ----------------------

function devolverDatos($con) {
    header('Content-Type: application/json; charset=UTF-8');

    $response = [];

    // Obtener tareas
    $tareas_result = mysqli_query($con, "SELECT id_tarea, nombre FROM tareas");
    $tareas = [];
    while ($row = mysqli_fetch_assoc($tareas_result)) {
        $tareas[] = $row;
    }
    $response['tareas'] = $tareas;

    // Obtener etiquetas
    $etiquetas_result = mysqli_query($con, "SELECT id_etiqueta, nombre FROM etiquetas");
    $etiquetas = [];
    while ($row = mysqli_fetch_assoc($etiquetas_result)) {
        $etiquetas[] = $row;
    }
    $response['etiquetas'] = $etiquetas;

    // Obtener proyectos
    $proyectos_result = mysqli_query($con, "SELECT id_proyecto, nombre FROM proyectos");
    $proyectos = [];
    while ($row = mysqli_fetch_assoc($proyectos_result)) {
        $proyectos[] = $row;
    }
    $response['proyectos'] = $proyectos;

    //USUARIOS (solo si eres admin)
    if (isset($_SESSION['tipo']) && $_SESSION['tipo'] == 1) {
    $u = mysqli_query($con, "SELECT id_usuario, nombre FROM usuarios");
    while ($row = mysqli_fetch_assoc($u)) $response['usuarios'][] = $row;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}


//----------------------Tabla de registros cargados

function registrarActividad($con) {
    if (!isset($_SESSION['id_usuario'])) {
        responderError(401, "No autorizado");
    }

    header('Content-Type: text/plain; charset=UTF-8');

    $id_usuario  = (int) $_SESSION['id_usuario'];
    $tarea       = isset($_POST['id_tarea']) ? (int) $_POST['id_tarea'] : 0;
    $proyecto    = isset($_POST['id_proyecto']) ? (int) $_POST['id_proyecto'] : null;
    $descripcion = $_POST['descripcion'] ?? "Actividad registrada";
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin    = $_POST['hora_fin'] ?? null;
    //  convertir las horas vacías en null
    $hora_inicio = $hora_inicio === '' ? null : $hora_inicio;
    $hora_fin    = $hora_fin  === '' ? null : $hora_fin;

    $duracion = null;
    if ($hora_inicio && $hora_fin) {
        $ini = new DateTime($hora_inicio);
        $fin = new DateTime($hora_fin);
        $diff = $ini->diff($fin);             // DateInterval
        // formatear como HH:MM:SS  (puede durar >24 h)
        $duracion = sprintf(
            '%02d:%02d:%02d',
            $diff->h + $diff->d * 24,         // días → horas
            $diff->i,
            $diff->s
        );
}
    $comentarios = $_POST['comentarios'] ?? null;

    // ► Ya no se comprueba $fecha
    if (!$tarea || !$proyecto) {
        responderError(400, "Datos incompletos");
    }

    // ► Eliminamos la columna/placeholder de fecha
    $sql = "INSERT INTO registro (
                id_usuario, proyecto, tarea, descripcion,
                hora_inicio, hora_fin, duracion, comentarios
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "iiisssss",
        $id_usuario, $proyecto, $tarea, $descripcion,
        $hora_inicio, $hora_fin, $duracion, $comentarios
    );

    if ($stmt->execute()) {

        $id_registro = $con->insert_id;

        // Insertar etiquetas si se enviaron
        if (isset($_POST['etiquetas']) && is_array($_POST['etiquetas'])) {
            foreach ($_POST['etiquetas'] as $id_etiqueta) {
                $sql_et = "INSERT INTO registro_etiquetas (id_registro, id_etiqueta) VALUES (?, ?)";
                $stmt_et = $con->prepare($sql_et);
                $stmt_et->bind_param("ii", $id_registro, $id_etiqueta);
                $stmt_et->execute();
            }
        }
        echo "Actividad registrada con éxito";
    } else {
        responderError(500, "Error al registrar actividad: " . $con->error);
    }

    $stmt->close();
    exit();
}

function devolverTablaRegistros($con) {

    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    if (!isset($_SESSION['id_usuario'])) {
        responderError(401, "No autorizado");
    }

    $id_usuario = (int)$_SESSION['id_usuario'];

    $query = "SELECT r.id_registro, u.nombre AS usuario, p.nombre AS proyecto, t.nombre AS tarea, r.descripcion, r.fecha, r.duracion
              FROM registro r
              JOIN usuarios u ON r.id_usuario = u.id_usuario
              JOIN proyectos p ON r.proyecto = p.id_proyecto
              JOIN tareas t ON r.tarea = t.id_tarea
              WHERE r.id_usuario = $id_usuario
              ORDER BY r.id_registro DESC";

    $res = mysqli_query($con, $query);

    $html = '<table><thead><tr>
        <th>Proyecto</th><th>Tarea</th>
        <th>Descripción</th><th>Fecha</th><th>Duración</th><th>Acciones</th>
        </tr></thead><tbody>';

    while ($row = mysqli_fetch_assoc($res)) {
        $html .= "<tr>

            <td>{$row['proyecto']}</td>
            <td>{$row['tarea']}</td>
            <td>{$row['descripcion']}</td>
            <td>{$row['fecha']}</td>
            <td>{$row['duracion']}</td>
            <td>
                <button onclick=\"modificarRegistro({$row['id_registro']})\">Modificar</button>
                <button onclick=\"borrarRegistro({$row['id_registro']})\">Borrar</button>
            </td>
        </tr>";
    }

    $html .= '</tbody></table>';
    echo $html;
    exit();
}


function eliminarRegistro($con, $id) {
    $stmt = $con->prepare("DELETE FROM registro WHERE id_registro = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "Registro eliminado";
    } else {
        responderError(500, "Error al eliminar: " . $con->error);
    }
    exit();
}

function modificarRegistro($con, $datos) {
    $id = (int)$datos['id'];
    $descripcion = mysqli_real_escape_string($con, $datos['descripcion']);

    $stmt = $con->prepare("UPDATE registro SET descripcion = ? WHERE id_registro = ?");
    $stmt->bind_param("si", $descripcion, $id);

    if ($stmt->execute()) {
        echo "Registro modificado";
    } else {
        responderError(500, "Error al modificar: " . $con->error);
    }
    exit();
}


function responderError($codigo, $mensaje) {
    http_response_code($codigo);
    echo $mensaje;
    exit();
}


