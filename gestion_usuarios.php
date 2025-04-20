<?php
require("auth.php");
requireAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - BitáQorA</title>
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
    <h1>Gestión de Usuarios</h1>

    <section>
        <h2 id="form-title">Agregar Nuevo Usuario</h2>
        <form id="usuario-form">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <label>Nombre: <input type="text" id="nombre" required></label>
            <label>Contraseña: <input type="password" id="pass" required></label>
            <label>Tipo: 
                <select id="tipo" required>
                    <option value="1">Administrador</option>
                    <option value="0">Usuario</option>
                </select>
            </label>
            <button type="submit">Guardar Usuario</button>
        </form>
    </section>

    <section>
        <h2>Lista de Usuarios</h2>
        <div id="lista-usuarios"></div>
    </section>

    <script>
        const form = document.getElementById('usuario-form');
        const listaDiv = document.getElementById('lista-usuarios');
        const idInput = document.getElementById('id_usuario');
        const nombre = document.getElementById('nombre');
        const pass = document.getElementById('pass');
        const tipo = document.getElementById('tipo');

        let modo = 'crear';

        const cargarUsuarios = async () => {
            const res = await fetch('usuarios_api.php');
            const usuarios = await res.json();

            let html = `<table>
                <tr><th>Nombre</th><th>Tipo</th><th>Acciones</th></tr>`;
            usuarios.forEach(u => {
                html += `<tr>
                    <td>${u.nombre}</td>
                    <td>${u.tipo == 1 ? 'Administrador' : 'Usuario'}</td>
                    <td>
                        <button onclick='editarUsuario(${JSON.stringify(u)})'>Modificar</button>
                        <button style="color:red" onclick="eliminarUsuario(${u.id_usuario})">Eliminar</button>
                    </td>
                </tr>`;
            });
            html += `</table>`;
            listaDiv.innerHTML = html;
        };

        const editarUsuario = (u) => {
            idInput.value = u.id_usuario;
            nombre.value = u.nombre;
            pass.value = u.pass;
            tipo.value = u.tipo;
            modo = 'modificar';
            document.getElementById('form-title').textContent = "Editar Usuario";
        };

        const eliminarUsuario = async (id) => {
            await fetch('usuarios_api.php', {
                method: 'POST',
                body: JSON.stringify({ accion: 'eliminar', id_usuario: id }),
                headers: { 'Content-Type': 'application/json' }
            });
            cargarUsuarios();
        };

        form.addEventListener('submit', async e => {
            e.preventDefault();

            const datos = {
                accion: modo,
                id_usuario: idInput.value,
                nombre: nombre.value,
                pass: pass.value,
                tipo: tipo.value
            };

            await fetch('usuarios_api.php', {
                method: 'POST',
                body: JSON.stringify(datos),
                headers: { 'Content-Type': 'application/json' }
            });

            form.reset();
            modo = 'crear';
            document.getElementById('form-title').textContent = "Agregar Nuevo Usuario";
            cargarUsuarios();
        });

        cargarUsuarios();
    </script>
</body>
</html>
