<?php
// Devuelve estadísticas agregadas en JSON 

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/bbdd.php';
session_start();

/* ───────────────────────── 1 · Control de sesión ───────────────────────── */
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$uid  = (int)$_SESSION['id_usuario']; // ID del usuario logeado
$tipo = (int)$_SESSION['tipo'];       // 1 = admin

/* ───────────────────────── 2 · Parámetros ───────────────────────── */
$group = $_GET['group'] ?? 'tarea';
$start = ($_GET['start'] ?? date('Y-m-01')) . ' 00:00:00';
$end   = ($_GET['end']   ?? date('Y-m-d'))  . ' 23:59:59'; 

// ids() → devuelve array de enteros a partir de clave GET
function ids(string $key): array
{
    if (!isset($_GET[$key])) return [];
    $v = $_GET[$key];
    if (!is_array($v)) $v = [$v];
    return array_values(array_filter(array_map('intval', $v), fn ($x) => $x > 0));
}

$idsUsuario  = ids('usuario');
$idsProyecto = ids('proyecto');
$idsTarea    = ids('tarea');
$idsEtiqueta = ids('etiqueta');

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

/* ───────────────────────── 3 · Construir WHERE ───────────────────────── */
$where = "r.fecha BETWEEN '$start' AND '$end'";

if ($tipo !== 1 && empty($idsUsuario)) {
    // Usuario normal sin filtro explícito solo sus registros
    $where .= " AND r.id_usuario = $uid";
}

if ($idsUsuario)  $where .= ' AND r.id_usuario   IN (' . implode(',', $idsUsuario)  . ')';
if ($idsProyecto) $where .= ' AND r.proyecto     IN (' . implode(',', $idsProyecto) . ')';
if ($idsTarea)    $where .= ' AND r.tarea        IN (' . implode(',', $idsTarea)    . ')';

// Filtro de etiquetas 
$extraEtiqueta = '';
if ($idsEtiqueta) {
    if ($group === 'etiqueta') {
        // limitamos la unión principal para no duplicar otras etiquetas
        $extraEtiqueta = ' AND re.id_etiqueta IN (' . implode(',', $idsEtiqueta) . ')';
    } else {
        //  basta con que el registro tenga al menos una de ellas
        $where .= ' AND EXISTS (SELECT 1 FROM registro_etiquetas ref
                                WHERE ref.id_registro = r.id_registro
                                  AND ref.id_etiqueta IN (' . implode(',', $idsEtiqueta) . '))';
    }
}

/* ───────────────────────── 4 · Consultas de agregación ───────────────────────── */
$selectDur = ', SUM(TIME_TO_SEC(r.duracion)) AS tiempo_seg';

switch ($group) {
    case 'usuario':
        if ($tipo !== 1) {
            http_response_code(403);
            echo json_encode(['error' => 'Solo administradores']);
            exit;
        }
        $sql = "SELECT u.nombre AS label, COUNT(*) AS n$selectDur
                FROM registro r
                JOIN usuarios u ON u.id_usuario = r.id_usuario
                WHERE $where
                GROUP BY r.id_usuario";
        break;

    case 'proyecto':
        $sql = "SELECT p.nombre AS label, COUNT(*) AS n$selectDur
                FROM registro r
                JOIN proyectos p ON p.id_proyecto = r.proyecto
                WHERE $where
                GROUP BY p.id_proyecto";
        break;

    case 'tarea':
        $sql = "SELECT t.nombre AS label, COUNT(*) AS n$selectDur
                FROM registro r
                JOIN tareas t ON t.id_tarea = r.tarea
                WHERE $where
                GROUP BY t.id_tarea";
        break;

    case 'etiqueta':
        $sql = "SELECT e.nombre AS label, COUNT(*) AS n$selectDur
                FROM registro r
                JOIN registro_etiquetas re ON re.id_registro = r.id_registro
                JOIN etiquetas e ON e.id_etiqueta = re.id_etiqueta
                WHERE $where$extraEtiqueta
                GROUP BY e.id_etiqueta";
        break;

    case 'day':
        $sql = "SELECT DATE(r.fecha) AS label, COUNT(*) AS n$selectDur
                FROM registro r
                WHERE $where
                GROUP BY DATE(r.fecha)
                ORDER BY label";
        break;

    case 'month':
        $sql = "SELECT DATE_FORMAT(r.fecha, '%Y-%m') AS label, COUNT(*) AS n$selectDur
                FROM registro r
                WHERE $where
                GROUP BY DATE_FORMAT(r.fecha, '%Y-%m')
                ORDER BY label";
        break;
}

$res = mysqli_query($con, $sql);
if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($con)]);
    exit;
}

$rows = [];
while ($row = mysqli_fetch_assoc($res)) {
    $row['tiempo'] = gmdate('H:i:s', (int)$row['tiempo_seg']);
    $rows[] = $row;
}

echo json_encode($rows);
