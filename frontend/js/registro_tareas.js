// ---------------- GET proyectos y tareas -----------------------------

document.addEventListener("DOMContentLoaded", () => {

    fetch('../backend/tareas_api.php')
    
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

    fetch('../backend/tareas_api.php', {
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
    fetch('../backend/tareas_api.php?modo=tabla')
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
        fetch('../backend/tareas_api.php', {
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
        fetch('../backend/tareas_api.php', {
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
