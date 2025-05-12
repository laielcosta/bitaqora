/* js/recientes.js
 * Funciona tanto para admin.html como para usuario.html
 * - Admin: puede filtrar por usuario, proyecto, tarea, etc.
 * - Usuario: ve solo sus propias actividades (data-role="0")
 */

const ENDPOINT_REC = '../backend/recientes_api.php';

/* -------- referencias a elementos (pueden ser null) -------- */
const listaRec       = document.getElementById('lista-actividades-recientes');
const filtroUsuario  = document.getElementById('filtro-usuario');
const filtroProyecto = document.getElementById('filtro-proyecto');
const filtroTarea    = document.getElementById('filtro-tarea');
const filtroEtiqueta = document.getElementById('filtro-etiqueta');
const filtroDesc     = document.getElementById('filtro-descripcion');
const btnFiltrar     = document.getElementById('btn-aplicar-filtros');

/* -------- datos del usuario extraídos desde el <body> -------- */
const USER_ROLE = document.body.dataset.role || null;      // "1" = admin, "0" = usuario
const USER_ID   = document.body.dataset.userId || null;    // ID del usuario logueado

/* -------- carga dinámica de <select> -------- */
async function cargarOpciones(tipo, selectEl) {
  if (!selectEl) return;  // puede no existir en usuario.html

  try {
    const res  = await fetch(`../backend/${tipo}_api.php`, { credentials:'include' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    const items = Array.isArray(data) ? data : (data[tipo] || []);

    // limpia opciones previas excepto la primera
    selectEl.querySelectorAll('option:not(:first-child)').forEach(o => o.remove());

    const idKey = {
      usuarios:  'id_usuario',
      proyectos: 'id_proyecto',
      tareas:    'id_tarea',
      etiquetas: 'id'
    }[tipo];

    items.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item[idKey];
      opt.textContent = item.nombre;
      selectEl.appendChild(opt);
    });

  } catch (e) {
    console.error(`Error cargando ${tipo}:`, e);
  }
}

/* -------- obtiene y pinta las actividades -------- */
async function actualizarRecientes() {
  try {
    if (!listaRec) return;

    const params = new URLSearchParams({
      usuario:    USER_ROLE === '0'
                    ? USER_ID
                    : (filtroUsuario ? filtroUsuario.value : ''),
      proyecto:   filtroProyecto ? filtroProyecto.value : '',
      tarea:      filtroTarea    ? filtroTarea.value    : '',
      etiqueta:   filtroEtiqueta ? filtroEtiqueta.value : '',
      descripcion:filtroDesc     ? filtroDesc.value.trim() : ''
    });

    const res  = await fetch(`${ENDPOINT_REC}?${params}`, { credentials:'include' });
    const text = await res.text();
    if (!res.ok) {
      console.error(`Servidor respondió ${res.status}:`, text);
      return;
    }

    const datos = JSON.parse(text);

    listaRec.innerHTML = '';
    if (!Array.isArray(datos) || datos.length === 0) {
      listaRec.innerHTML = '<li style="text-align:center;">No se encontraron actividades</li>';
      return;
    }

    datos.forEach(act => {
      const li = document.createElement('li');
      li.innerHTML = `
        <strong>${act.usuario}</strong>
        – <em>${act.proyecto}</em>
        – ${act.tarea}
        – ${act.descripcion}
        – [${act.etiquetas.join(', ')}]
        – <small>${act.fecha}</small>
      `;
      listaRec.appendChild(li);
    });

  } catch (e) {
    console.error('Error al actualizar actividades:', e);
  }
}

/* -------- inicialización -------- */
document.addEventListener('DOMContentLoaded', () => {

  // carga filtros solo si existen en la página
  if (filtroUsuario)  cargarOpciones('usuarios',   filtroUsuario);
  if (filtroProyecto) cargarOpciones('proyectos',  filtroProyecto);
  if (filtroTarea)    cargarOpciones('tareas',     filtroTarea);
  if (filtroEtiqueta) cargarOpciones('etiquetas',  filtroEtiqueta);

  // si es usuario normal, ocultar el filtro de usuario si existiera
  if (USER_ROLE === '0' && filtroUsuario) {
    filtroUsuario.style.display = 'none';  
  }

  if (btnFiltrar) {
    btnFiltrar.addEventListener('click', actualizarRecientes);
  }

  actualizarRecientes();
  setInterval(actualizarRecientes, 60000);
});
