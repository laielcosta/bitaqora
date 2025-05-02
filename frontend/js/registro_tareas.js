// ---------------- GET proyectos y tareas -----------------------------
document.addEventListener("DOMContentLoaded", () => {
    fetch("../backend/tareas_api.php", { credentials: "include" })
        .then(r => r.json())
        .then(data => {
            const selProyecto  = document.getElementById("select_proyecto");
            const selTarea     = document.getElementById("select_tarea");
            const contEtiq     = document.getElementById("contenedor-etiquetas");

            data.proyectos.forEach(p =>
                selProyecto.insertAdjacentHTML("beforeend",
                    `<option value="${p.id_proyecto}">${p.nombre}</option>`));

            data.tareas.forEach(t =>
                selTarea.insertAdjacentHTML("beforeend",
                    `<option value="${t.id_tarea}">${t.nombre}</option>`));

            data.etiquetas.forEach(e =>
                contEtiq.insertAdjacentHTML("beforeend",
                    `<label><input type="checkbox" name="etiquetas[]" value="${e.id_etiqueta}"> ${e.nombre}</label>`));
        })
        .catch(err => console.error("Error cargando datos:", err));
});

// ---------------- POST de Registro -----------------------------
document.getElementById("actividad-form").addEventListener("submit", e => {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch("../backend/tareas_api.php", {
        method: "POST",
        body: formData,
        credentials: "include"
    })
        .then(r => r.text())
        .then(msg => {
            document.getElementById("mensaje").textContent = msg;
            e.target.reset();
            cargarRegistros();
        })
        .catch(err => console.error("Error al registrar:", err));
});

// ---------------- Cronómetro -----------------------------
const timer = new easytimer.Timer();
const cronometro = document.getElementById("cronometro");

timer.addEventListener("secondsUpdated", () => {
    const { hours, minutes, seconds } = timer.getTimeValues();
    const hh = String(hours).padStart(2, "0");
    const mm = String(minutes).padStart(2, "0");
    const ss = String(seconds).padStart(2, "0");
    cronometro.textContent = `${hh}:${mm}:${ss}`;
    document.getElementById("horas_cronometro").value = `${hh}:${mm}:${ss}`;
});

window.iniciarCronometro   = () => timer.start({ precision: "seconds" });
window.detenerCronometro   = () => timer.pause();
window.reiniciarCronometro = () => {
    timer.reset();
    cronometro.textContent = "00:00:00";
    document.getElementById("horas_cronometro").value = "00:00:00";
};

// ---------------- CRUD Registros -----------------------------
function cargarRegistros() {
    fetch("../backend/tareas_api.php?modo=tabla", { credentials: "include" })
        .then(r => r.text())
        .then(html => (document.getElementById("tabla-registros").innerHTML = html))
        .catch(err => console.error("Error al cargar registros:", err));
}
window.cargarRegistros = cargarRegistros; // opcional

function borrarRegistro(id) {
    if (!confirm("¿Seguro que deseas borrar este registro?")) return;

    fetch("../backend/tareas_api.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id,
        credentials: "include"
    })
        .then(r => r.text())
        .then(() => cargarRegistros())
        .catch(err => console.error("Error al borrar:", err));
}
window.borrarRegistro = borrarRegistro;   // 👈 fuera del cuerpo

function modificarRegistro(id) {
    const nueva = prompt("Nueva descripción:");
    if (!nueva) return;

    fetch("../backend/tareas_api.php", {
        method: "PUT",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${id}&descripcion=${encodeURIComponent(nueva)}`,
        credentials: "include"
    })
        .then(r => r.text())
        .then(() => cargarRegistros())
        .catch(err => console.error("Error al modificar:", err));
}
window.modificarRegistro = modificarRegistro;   // 👈 fuera del cuerpo

// tabla inicial
document.addEventListener("DOMContentLoaded", cargarRegistros);
