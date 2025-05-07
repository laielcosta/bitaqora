<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/bbdd.php';
$con = conectar();                         // mysqli

if (!$con) {
    http_response_code(500);
    echo json_encode(['error' => 'Sin conexión a la BBDD']);
    exit;
}

/* ---------- recoger filtros ---------- */
$usuario     = $_GET['usuario']     ?? '';
$proyecto    = $_GET['proyecto']    ?? '';
$tarea       = $_GET['tarea']       ?? '';
$etiqueta    = $_GET['etiqueta']    ?? '';
$descripcion = $_GET['descripcion'] ?? '';

/* ---------- construir WHERE ---------- */
$w = [];
if ($usuario   !== '') $w[] = "r.id_usuario = '".mysqli_real_escape_string($con,$usuario)."'";
if ($proyecto  !== '') $w[] = "r.proyecto   = '".mysqli_real_escape_string($con,$proyecto)."'";
if ($tarea     !== '') $w[] = "r.tarea      = '".mysqli_real_escape_string($con,$tarea)."'";
if ($etiqueta  !== '') $w[] = "re.id_etiqueta = '".mysqli_real_escape_string($con,$etiqueta)."'";
if ($descripcion !== '') {
    $d = mysqli_real_escape_string($con,$descripcion);
    $w[] = "r.descripcion LIKE '%$d%'";
}
$where = $w ? 'WHERE '.implode(' AND ',$w) : '';

/* ---------- consulta principal ---------- */
$sql = "
    SELECT
        u.nombre               AS usuario,
        p.nombre               AS proyecto,
        t.nombre               AS tarea,
        r.descripcion          AS descripcion,
        r.fecha                AS fecha,
        GROUP_CONCAT(e.nombre) AS etiquetas
    FROM registro r
    JOIN usuarios  u  ON r.id_usuario = u.id_usuario
    JOIN proyectos p  ON r.proyecto   = p.id_proyecto
    JOIN tareas    t  ON r.tarea      = t.id_tarea
    LEFT JOIN registro_etiquetas re ON r.id_registro = re.id_registro
    LEFT JOIN etiquetas          e  ON re.id_etiqueta = e.id_etiqueta
    $where
    GROUP BY r.id_registro
    ORDER BY r.fecha DESC
";

$result = mysqli_query($con, $sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error'=>mysqli_error($con)]);
    exit;
}

/* ---------- formatear salida ---------- */
$out = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tags = $row['etiquetas'] ? array_map('trim', explode(',',$row['etiquetas'])) : [];
    $out[] = [
        'usuario'     => $row['usuario'],
        'proyecto'    => $row['proyecto'],
        'tarea'       => $row['tarea'],
        'descripcion' => $row['descripcion'],
        'fecha'       => $row['fecha'],
        'etiquetas'   => $tags
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
mysqli_close($con);
