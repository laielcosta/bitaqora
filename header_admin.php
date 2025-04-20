<?php 
if (!isset($_SESSION['usuario'])) {
    header('Location: login.html');
    exit();
}
?>
<head>
    <link rel="stylesheet" href="estilos.css">
</head>

<!-- ENCABEZADO -->
<header style="position: fixed; top: 0; width: 100%; background-color: #1a73e8; color: white; display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000;">
    <div style="display: flex; align-items: center;">
        <button id="menu-toggle" style="background-color: #1a73e8; color: white; border: none; padding: 10px 15px; font-size: 18px; cursor: pointer; border-radius: 5px; margin-right: 15px;">
            ☰
        </button>
        <img src="logo_bitaqora.png" alt="Logo BitáQorA" style="height: 35px; margin-right: 10px;">
    </div>
    <div>
        <a href="logout.php" style="color: white; text-decoration: none; font-weight: bold;">Cerrar sesión</a>
    </div>
</header>

<!-- SIDEBAR -->
<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 230px;
    background-color: #1a73e8;
    color: white;
    padding: 80px 20px 20px; /* Añade espacio arriba para no tapar el primer link */
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    transform: translateX(-100%);
    opacity: 0;
    transition: all 0.4s ease;
    z-index: 999;
}

.sidebar.visible {
    transform: translateX(0);
    opacity: 1;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    margin: 20px 0;
    font-weight: bold;
    transition: color 0.3s ease;
}

.sidebar a:hover {
    color: #ddd;
}
</style>

<div class="sidebar" id="sidebar">
    <a href="admin.html">Inicio</a>
    <a href="ver_actividades.php">Actividades</a>
    <a href="gestion_usuarios.php">Usuarios</a>
    <a href="estadisticas.php">Estadísticas</a>
</div>

<div style="height: 70px;"></div> <!-- Empuje visual del contenido -->

<script>
const toggleBtn = document.getElementById('menu-toggle');
const sidebar = document.getElementById('sidebar');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('visible');
});
</script>
