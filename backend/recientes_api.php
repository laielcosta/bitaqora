
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/bbdd.php';
$con = conectar();

if (!$con) {
    http_response_code(500);
    echo json_encode(['error' => 'Sin conexión a la BBDD']);
    exit;
}

// 1. Obtener tipo de usuario desde sesión
$tipo = $_SESSION['tipo'] ?? null;
$id_sesion = $_SESSION['id_usuario'] ?? null;

// 2. Validar y forzar seguridad
if ($tipo === null || $id_sesion === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión no válida']);
    exit;
}

// 3. Leer filtros
$usuario     = $_GET['usuario']     ?? '';
$proyecto    = $_GET['proyecto']    ?? '';
$tarea       = $_GET['tarea']       ?? '';
$etiqueta    = $_GET['etiqueta']    ?? '';
$descripcion = $_GET['descripcion'] ?? '';

// ⚠️ Si NO es admin (tipo 0), forzar su ID y anular otros
if ((int)$tipo === 0) {
    $usuario = $id_sesion;
}

// 4. Construir WHERE
$conds = [];
if ($usuario !== '') {
    $conds[] = "r.id_usuario = '" . mysqli_real_escape_string($con, $usuario) . "'";
}
if ($proyecto !== '') {
    $conds[] = "r.proyecto = '" . mysqli_real_escape_string($con, $proyecto) . "'";
}
if ($tarea !== '') {
    $conds[] = "r.tarea = '" . mysqli_real_escape_string($con, $tarea) . "'";
}
if ($etiqueta !== '') {
    $conds[] = "re.id_etiqueta = '" . mysqli_real_escape_string($con, $etiqueta) . "'";
}
if ($descripcion !== '') {
    $d = mysqli_real_escape_string($con, $descripcion);
    $conds[] = "r.descripcion LIKE '%$d%'";
}

$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

// 5. Consulta
$sql = "
    SELECT
      u.nombre               AS usuario,
      p.nombre               AS proyecto,
      t.nombre               AS tarea,
      r.descripcion          AS descripcion,
      r.fecha                AS fecha,
      GROUP_CONCAT(e.nombre) AS etiquetas
    FROM registro r
    JOIN usuarios  u ON r.id_usuario  = u.id_usuario
    JOIN proyectos p ON r.proyecto    = p.id_proyecto
    JOIN tareas    t ON r.tarea       = t.id_tarea
    LEFT JOIN registro_etiquetas re ON r.id_registro = re.id_registro
    LEFT JOIN etiquetas          e  ON re.id_etiqueta = e.id_etiqueta
    $where
    GROUP BY r.id_registro
    ORDER BY r.fecha DESC
";

$result = mysqli_query($con, $sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($con)]);
    exit;
}

// 6. Resultado
$actividades = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tags = $row['etiquetas'] ? array_map('trim', explode(',', $row['etiquetas'])) : [];
    $actividades[] = [
        'usuario'     => $row['usuario'],
        'proyecto'    => $row['proyecto'],
        'tarea'       => $row['tarea'],
        'descripcion' => $row['descripcion'],
        'fecha'       => $row['fecha'],
        'etiquetas'   => $tags
    ];
}

echo json_encode($actividades, JSON_UNESCAPED_UNICODE);
mysqli_close($con);
?>