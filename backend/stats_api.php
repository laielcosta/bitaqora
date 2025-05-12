<?php
// stats_api.php – Devuelve estadísticas agregadas en JSON (mysqli)
// Agrupaciones permitidas: usuario, proyecto, tarea, etiqueta, day, month
//-----------------------------------------------------------------------

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/bbdd.php';
session_start();

// ---------- 1 · Control de sesión ----------------------------------------
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$uid  = (int)$_SESSION['id_usuario'];
$tipo = (int)$_SESSION['tipo'];          // 1 = admin

// ---------- 2 · Parámetros GET -------------------------------------------
$group = $_GET['group'] ?? 'tarea';      // valor por defecto
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-d');
$live  = isset($_GET['live']);           // ?live=1 → modo tiempo real

$validGroups = ['usuario', 'proyecto', 'tarea', 'etiqueta', 'day', 'month'];
if (!in_array($group, $validGroups, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro group inválido']);
    exit;
}

$con = conectar();
if (!$con) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

// ---------- 3 · Modo tiempo real (total de registros) --------------------
if ($live) {
    $sql = ($tipo === 1)
        ? "SELECT COUNT(*) AS total FROM registro"
        : "SELECT COUNT(*) AS total FROM registro WHERE id_usuario = $uid";

    $res = mysqli_query($con, $sql);
    echo json_encode(mysqli_fetch_assoc($res));
    exit;
}

// ---------- 4 · WHERE común (rango de fechas + usuario no‑admin) ---------
$where = "fecha BETWEEN '$start' AND '$end'";
if ($tipo !== 1) $where .= " AND id_usuario = $uid";

// ---------- 5 · Query según agrupación -----------------------------------
switch ($group) {

    /* Agrupación por usuario (solo admins) */
    case 'usuario':
        if ($tipo !== 1) {
            http_response_code(403);
            echo json_encode(['error' => 'Solo administradores']);
            exit;
        }
        $sql = "SELECT u.nombre AS label, COUNT(*) AS n
                FROM registro r
                JOIN usuarios u ON u.id_usuario = r.id_usuario
                WHERE $where
                GROUP BY r.id_usuario";
        break;

    /* Agrupación por proyecto */
    case 'proyecto':
        $sql = "SELECT p.nombre AS label, COUNT(*) AS n
                FROM registro r
                JOIN proyectos p ON p.id_proyecto = r.proyecto
                WHERE $where
                GROUP BY p.id_proyecto";
        break;

    /* Agrupación por tarea */
    case 'tarea':
        $sql = "SELECT t.nombre AS label, COUNT(*) AS n
                FROM registro r
                JOIN tareas t ON t.id_tarea = r.tarea
                WHERE $where
                GROUP BY t.id_tarea";
        break;

    /* Agrupación por etiqueta */
    case 'etiqueta':
        $sql = "SELECT e.nombre AS label, COUNT(*) AS n
                FROM registro r
                JOIN registro_etiquetas re ON re.id_registro = r.id_registro
                JOIN etiquetas e ON e.id_etiqueta = re.id_etiqueta
                WHERE $where
                GROUP BY e.id_etiqueta";
        break;

    /* Día */
    case 'day':
        $sql = "SELECT DATE(fecha) AS label, COUNT(*) AS n
                FROM registro r
                WHERE $where
                GROUP BY DATE(fecha)
                ORDER BY label";
        break;

    /* Mes */
    case 'month':
        $sql = "SELECT DATE_FORMAT(fecha,'%Y-%m') AS label, COUNT(*) AS n
                FROM registro r
                WHERE $where
                GROUP BY DATE_FORMAT(fecha,'%Y-%m')
                ORDER BY label";
        break;
}

// ---------- 6 · Ejecución y respuesta ------------------------------------
$res = mysqli_query($con, $sql);
$datos = [];
while ($fila = mysqli_fetch_assoc($res)) {
    $datos[] = $fila;
}

echo json_encode($datos);
