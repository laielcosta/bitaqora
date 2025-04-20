<?php
require("auth.php");
requireAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Proyectos - BitáQorA</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        form label {
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<?php require("header_admin.html"); ?>
<h1>Gestión de Proyectos</h1>

<section>
    <h2 id="form-title">Agregar Nuevo Proyecto</h2>
    <form id="proyecto-form">
        <input type="hidden" name="id_proyecto" id="id_proyecto">
        <label>Nombre: <input type="text" id="nombre" required></label>
        <label>Descripción: <input type="text" id="descripcion" required></label>
        <label>Inicio: <input type="date" id="fecha_inicio" required></label>
        <label>Fin: <input type="date" id="fecha_fin" required></label>
        <button type="submit">Guardar Proyecto</button>
    </form>
</section>

<section>
    <h2>Lista de Proyectos</h2>
    <div id="lista-proyectos"></div>
</section>

<script>
const form = document.getElementById('proyecto-form');
const listaDiv = document.getElementById('lista-proyectos');
const idInput = document.getElementById('id_proyecto');
const nombre = document.getElementById('nombre');
const descripcion = document.getElementById('descripcion');
const inicio = document.getElementById('fecha_inicio');
const fin = document.getElementById('fecha_fin');

let modo = 'crear';

const cargarProyectos = async () => {
    const res = await fetch('proyectos_api.php');
    const proyectos = await res.json();

    let html = `<table>
        <tr><th>Nombre</th><th>Descripción</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr>`;
    proyectos.forEach(p => {
        html += `<tr>
            <td>${p.nombre}</td>
            <td>${p.descripcion}</td>
            <td>${p.fecha_inicio}</td>
            <td>${p.fecha_fin}</td>
            <td>
                <button onclick='editarProyecto(${JSON.stringify(p)})'>Modificar</button>
                <button style="color:red" onclick="eliminarProyecto(${p.id_proyecto})">Eliminar</button>
            </td>
        </tr>`;
    });
    html += `</table>`;
    listaDiv.innerHTML = html;
};

const editarProyecto = (p) => {
    // Aquí ya no es necesario hacer un doble JSON.parse
    idInput.value = p.id_proyecto;
    nombre.value = p.nombre;
    descripcion.value = p.descripcion;
    inicio.value = p.fecha_inicio;
    fin.value = p.fecha_fin;
    modo = 'modificar';  // Cambiar a modo de modificación
    document.getElementById('form-title').textContent = "Editar Proyecto";  // Cambiar título del formulario
};


const eliminarProyecto = async (id) => {
    await fetch('proyectos_api.php', {
        method: 'POST',
        body: JSON.stringify({ accion: 'eliminar', id_proyecto: id }),
        headers: { 'Content-Type': 'application/json' }
    });
    cargarProyectos();
};

form.addEventListener('submit', async e => {
    e.preventDefault();

    const datos = {
        accion: modo,
        id_proyecto: idInput.value,
        nombre: nombre.value,
        descripcion: descripcion.value,
        fecha_inicio: inicio.value,
        fecha_fin: fin.value
    };

    await fetch('proyectos_api.php', {
        method: 'POST',
        body: JSON.stringify(datos),
        headers: { 'Content-Type': 'application/json' }
    });

    form.reset();
    modo = 'crear';
    document.getElementById('form-title').textContent = "Agregar Nuevo Proyecto";
    cargarProyectos();
});

cargarProyectos();
</script>
</body>
</html>
