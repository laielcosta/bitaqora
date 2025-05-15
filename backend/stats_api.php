<?php
// stats_api.php – multifiltros (mysqli)
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/bbdd.php';
session_start();

if(!isset($_SESSION['id_usuario'])){
  http_response_code(401); echo json_encode(['error'=>'No autenticado']); exit;
}

$uid  = (int)$_SESSION['id_usuario'];
$tipo = (int)$_SESSION['tipo']; // 1 = admin

// --- parámetros GET ----------------------------------------------------
$g   = $_GET['group'] ?? 'tarea';
$start = $_GET['start'] ?? '1970-01-01';
$end   = $_GET['end']   ?? date('Y-m-d');
$live  = isset($_GET['live']);

$u  = $_GET['id_usuario']  ?? null;
$p  = $_GET['id_proyecto'] ?? null;
$t  = $_GET['id_tarea']    ?? null;
$e  = $_GET['id_etiqueta'] ?? null;

$valid = ['usuario','proyecto','tarea','etiqueta','day','month'];
if(!in_array($g,$valid,true)){ http_response_code(400); exit; }

$con = conectar();

// --- tiempo real -------------------------------------------------------
if($live){
  $sql = ($tipo===1) ? "SELECT COUNT(*) AS total FROM registro"
                     : "SELECT COUNT(*) AS total FROM registro WHERE id_usuario=$uid";
  echo json_encode(mysqli_fetch_assoc(mysqli_query($con,$sql))); exit;
}

// --- WHERE dinámico ----------------------------------------------------
$where  = ["fecha BETWEEN '$start' AND '$end'"];
if($tipo!==1) $where[] = "r.id_usuario = $uid";
if($u) $where[] = "r.id_usuario = ".intval($u);
if($p) $where[] = "r.proyecto   = ".intval($p);
if($t) $where[] = "r.tarea      = ".intval($t);
if($e){
  $e = intval($e);
  $where[] = "EXISTS(SELECT 1 FROM registro_etiquetas re WHERE re.id_registro=r.id_registro AND re.id_etiqueta=$e)";
}
$whereSql = implode(' AND ',$where);

// --- SELECT según agrupación ------------------------------------------
switch($g){
  case 'usuario':
    if($tipo!==1){ http_response_code(403); exit; }
    $sql = "SELECT u.nombre AS label, COUNT(*) AS n
            FROM registro r JOIN usuarios u ON u.id_usuario=r.id_usuario
            WHERE $whereSql GROUP BY r.id_usuario";
    break;

  case 'proyecto':
    $sql = "SELECT p.nombre AS label, COUNT(*) AS n
            FROM registro r JOIN proyectos p ON p.id_proyecto=r.proyecto
            WHERE $whereSql GROUP BY p.id_proyecto";
    break;

  case 'tarea':
    $sql = "SELECT t.nombre AS label, COUNT(*) AS n
            FROM registro r JOIN tareas t ON t.id_tarea=r.tarea
            WHERE $whereSql GROUP BY t.id_tarea";
    break;

  case 'etiqueta':
    $sql = "SELECT e.nombre AS label, COUNT(*) AS n
            FROM registro r
            JOIN registro_etiquetas re ON re.id_registro=r.id_registro
            JOIN etiquetas e ON e.id_etiqueta=re.id_etiqueta
            WHERE $whereSql GROUP BY e.id_etiqueta";
    break;

  case 'day':
    $sql = "SELECT DATE(fecha) AS label, COUNT(*) AS n
            FROM registro r WHERE $whereSql
            GROUP BY DATE(fecha) ORDER BY label";
    break;

  case 'month':
    $sql = "SELECT DATE_FORMAT(fecha,'%Y-%m') AS label, COUNT(*) AS n
            FROM registro r WHERE $whereSql
            GROUP BY 1 ORDER BY 1";
    break;
}

$res = mysqli_query($con,$sql);
echo json_encode(mysqli_fetch_all($res,MYSQLI_ASSOC));
