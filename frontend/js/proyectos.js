/* ruta única al backend */
const ENDPOINT = '../backend/proyectos_api.php';

const fNombre  = document.getElementById('nombre');
const fDesc    = document.getElementById('descripcion');
const fInicio  = document.getElementById('fecha_inicio');
const fFin     = document.getElementById('fecha_fin');
const fId      = document.getElementById('id_proyecto');
const titulo   = document.getElementById('form-title');
const listaDiv = document.getElementById('lista-proyectos');
const form     = document.getElementById('proyecto-form');

let modo = 'crear';

/* ────────── CARGAR LISTA ────────── */
async function cargarProyectos() {
  const url = `${ENDPOINT}?_=${Date.now()}`;               // anti‑cache
  const res = await fetch(url, { credentials:'include', cache:'no-store' });
  const proyectos = await res.json();

  let html = `<table><thead><tr>
      <th>Nombre</th><th>Descripción</th><th>Inicio</th><th>Fin</th><th></th></tr></thead><tbody>`;
  proyectos.forEach(p => {
    html += `<tr>
        <td>${p.nombre}</td>
        <td>${p.descripcion}</td>
        <td>${p.fecha_inicio}</td>
        <td>${p.fecha_fin ?? ''}</td>
        <td>
          <button onclick='editar(${JSON.stringify(p)})'>✏️</button>
          <button onclick='eliminar(${p.id_proyecto})'>🗑️</button>
        </td>
      </tr>`;
  });
  listaDiv.innerHTML = html + '</tbody></table>';
}

/* ────────── CRUD ────────── */
function editar(p) {
  fId.value        = p.id_proyecto;
  fNombre.value    = p.nombre;
  fDesc.value      = p.descripcion;
  fInicio.value    = p.fecha_inicio;
  fFin.value       = p.fecha_fin;
  titulo.textContent = 'Editar Proyecto';
  modo = 'modificar';
}

async function eliminar(id) {
    if (!confirm('¿Eliminar proyecto?')) return;
  
    const res  = await fetch(ENDPOINT, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ accion: 'eliminar', id_proyecto: id })
    });
  
    const data = await res.json();
  
    if (!res.ok) {
      // aquí llega el 409 con el mensaje del backend
      alert(data.error || 'Error al eliminar');
    } else {
      cargarProyectos();      // refresca la tabla
    }
  }
  

/* ────────── SUBMIT ────────── */
form.addEventListener('submit', async e => {
  e.preventDefault();
  const data = {
    accion: modo,
    id_proyecto: fId.value,
    nombre: fNombre.value,
    descripcion: fDesc.value,
    fecha_inicio: fInicio.value,
    fecha_fin: fFin.value
  };
  await fetch(ENDPOINT, {
    method:'POST',
    credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(data)
  });
  form.reset();
  modo = 'crear';
  titulo.textContent = 'Agregar Proyecto';
  cargarProyectos();
});

document.addEventListener('DOMContentLoaded', cargarProyectos);
