<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');
require("bbdd.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN: intentamos iniciar sesión
    $data = json_decode(file_get_contents("php://input"), true);
    $usuario = $data['usuario'] ?? '';
    $password = $data['password'] ?? '';

    $con = conectar();
    $stmt = $con->prepare("SELECT * FROM usuarios WHERE nombre = ? AND pass = ?");
    $stmt->bind_param("ss", $usuario, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Guardar datos en la sesión
        $_SESSION['usuario'] = $usuario;
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['tipo'] = $row['tipo'];

        echo json_encode([
            "success" => true,
            "autenticado" => true,
            "usuario" => $usuario,
            "tipo" => $row['tipo'],
            "id_usuario" => $row['id_usuario']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "autenticado" => false,
            "mensaje" => "Usuario o contraseña incorrectos."
        ]);
    }
    exit;
}

// GET: solo verificar si ya hay sesión activa
if (isset($_SESSION['usuario']) && isset($_SESSION['tipo']) && isset($_SESSION['id_usuario'])) {
    echo json_encode([
        "autenticado" => true,
        "usuario" => $_SESSION['usuario'],
        "tipo" => $_SESSION['tipo'],
        "id_usuario" => $_SESSION['id_usuario']
    ]);
} else {
    echo json_encode(["autenticado" => false]);
}
