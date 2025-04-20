<?php
require("auth.php");
requireUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Actividad - BitáQorA</title>
    <link rel="stylesheet" href="../estilos.css">
    <script src="https://cdn.jsdelivr.net/npm/easytimer.js@4.4.0/dist/easytimer.min.js"></script>
    <style>
        #mensaje { margin-top: 1rem; font-weight: 600; }
        #cronometro { font-weight: bold; font-size: 1.2em; margin: 10px; }
    </style>
</head>
<body>
<?php include("header_usu.php"); ?>
<div style="height: 70px;"></div>

<div class="contenedor">
    <h3>Registrar tarea</h3>
    <form id="actividad-form" action="tareas_api.php" method="POST">
        <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
        <input type="hidden" name="fecha" value="<?php echo date('Y-m-d'); ?>">

        <label for="proyecto">Proyecto:</label>
        <select id="select_proyecto" name="id_proyecto" required></select>

        <label for="tarea">Tarea:</label>
        <select id="select_tarea" name="id_tarea" required></select>

        <label for="descripcion">Descripción:</label>
        <input type="text" name="descripcion" required>

        <label for="etiquetas">Etiquetas:</label>
        <div id="contenedor-etiquetas" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>


        <label for="horas">Horas:</label>
        <div style="display: flex; gap: 10px;">
            <input type="datetime-local" name="hora_inicio">
            <input type="datetime-local" name="hora_fin">
        </div>

        <label for="duracion">Duración (cronómetro):</label>
        <input type="time" name="duracion" id="horas_cronometro">

        <div id="cronometro">00:00:00</div>
        <button type="button" onclick="iniciarCronometro()">Iniciar</button>
        <button type="button" onclick="detenerCronometro()">Pausar</button>
        <button type="button" onclick="reiniciarCronometro()">Reiniciar</button>

        <label for="comentarios">Comentarios:</label>
        <input type="text" name="comentarios">

        <button type="submit">Guardar</button>
    </form>

    <h3>Registros recientes</h3>
    <div id="tabla-registros"></div>

    <div id="mensaje"></div>
</div>

<footer>
    <p>BitáQorA © 2025</p>
</footer>

<script>
// ---------------- GET proyectos y tareas -----------------------------

document.addEventListener("DOMContentLoaded", () => {

    fetch('./backend/tareas_api.php')
    
        .then(response => response.json())
        .then(data => {
            console.log('RESPUESTA TEXTO PLANO:', data);
            const selectProyecto = document.getElementById('select_proyecto');
            const selectTarea = document.getElementById('select_tarea');
            const contenedorEtiquetas = document.getElementById('contenedor-etiquetas');

            data.proyectos.forEach(proyecto => {
                const option = document.createElement('option');
                option.value = proyecto.id_proyecto;
                option.textContent = proyecto.nombre;
                selectProyecto.appendChild(option);
            });

            data.tareas.forEach(tarea => {
                const option = document.createElement('option');
                option.value = tarea.id_tarea;
                option.textContent = tarea.nombre;
                selectTarea.appendChild(option);
            });

            data.etiquetas.forEach(etiqueta => {
                const label = document.createElement('label');
                label.innerHTML = `<input type="checkbox" name="etiquetas[]" value="${etiqueta.id_etiqueta}"> ${etiqueta.nombre}`;
                contenedorEtiquetas.appendChild(label);
            });

        })
        .catch(error => {
            console.error('Error cargando datos:', error);
        });
});

// ---------------- POST de Registro de tarea-----------------------------


document.getElementById("actividad-form").addEventListener("submit", function(e) {
    e.preventDefault(); // Evitar que el formulario se envíe de forma tradicional

    const form = document.getElementById("actividad-form");
    const formData = new FormData(form);

    fetch("tareas_api.php", {
        method: "POST",
        body: formData,
        credentials: "include" // 👈 Importante: permite enviar la cookie de sesión
    })
    .then(res => res.text())
    .then(msg => {
        console.log("Respuesta del servidor:", msg);
        document.getElementById("mensaje").textContent = msg;
    })
    .catch(error => {
        console.error("Error al registrar actividad:", error);
        document.getElementById("mensaje").textContent = "Error al registrar actividad.";
    });
});



// ---------------- Cronómetro -----------------------------
const timer = new easytimer.Timer();
const cronometro = document.getElementById("cronometro");

function actualizarCronometro(t) {
    const hh = String(t.hours).padStart(2, '0');
    const mm = String(t.minutes).padStart(2, '0');
    const ss = String(t.seconds).padStart(2, '0');
    cronometro.textContent = `${hh}:${mm}:${ss}`;
    document.getElementById("horas_cronometro").value = `${hh}:${mm}:${ss}`;
}

timer.addEventListener("secondsUpdated", () => actualizarCronometro(timer.getTimeValues()));

function iniciarCronometro() { timer.start({ precision: "seconds" }); }
function detenerCronometro() { timer.pause(); }
function reiniciarCronometro() {
    timer.reset();
    cronometro.textContent = "00:00:00";
    document.getElementById("horas_cronometro").value = "00:00:00";
}


function cargarRegistros() {
    fetch('./backend/tareas_api.php?modo=tabla')
        .then(res => res.text())
        .then(html => {
            document.getElementById("tabla-registros").innerHTML = html;
        })
        .catch(err => {
            console.error("Error al cargar los registros:", err);
        });
}

function borrarRegistro(id) {
    if (confirm("¿Seguro que deseas borrar este registro?")) {
        fetch("./backend/tareas_api.php", {
            method: "DELETE",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + id
        })
        .then(res => res.text())
        .then(msg => {
            console.log(msg);
            cargarRegistros();
        });
    }
}

function modificarRegistro(id, actual) {
    const nueva = prompt("Nueva descripción:", actual);
    if (nueva && nueva !== actual) {
        fetch("./backend/tareas_api.php", {
            method: "PUT",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${id}&descripcion=${encodeURIComponent(nueva)}`
        })
        .then(res => res.text())
        .then(msg => {
            console.log(msg);
            cargarRegistros();
        });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    cargarRegistros(); // al cargar la página
});

</script>
</body>
</html>
