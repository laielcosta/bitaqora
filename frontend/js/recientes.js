const ENDPOINT_REC = '../backend/recientes_api.php';
const listaRec       = document.getElementById('lista-actividades-recientes');
const filtroUsuario  = document.getElementById('filtro-usuario');
const filtroProyecto = document.getElementById('filtro-proyecto');
const filtroTarea    = document.getElementById('filtro-tarea');
const filtroEtiqueta = document.getElementById('filtro-etiqueta');
const filtroDesc     = document.getElementById('filtro-descripcion');
const btnFiltrar     = document.getElementById('btn-aplicar-filtros');

// Carga select de filtros (funciona igual que antes)
async function cargarOpciones(tipo, selectEl) {
  try {
    const res = await fetch(`../backend/${tipo}_api.php`, { credentials: 'include' });
    const data = await res.json();
    const items = Array.isArray(data) ? data : (data[tipo] || []);
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

// Obtiene y muestra actividades filtradas
async function mostrarActividades() {
  try {
    const params = new URLSearchParams({
      usuario:    filtroUsuario.value,
      proyecto:   filtroProyecto.value,
      tarea:      filtroTarea.value,
      etiqueta:   filtroEtiqueta.value,
      descripcion:filtroDesc.value.trim()
    });
    const res  = await fetch(`${ENDPOINT_REC}?${params}`, { credentials: 'include' });
    const text = await res.text();
    if (!res.ok) {
      console.error('Error ' + res.status + ':', text);
      throw new Error(`HTTP ${res.status}`);
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

// Inicialización
window.addEventListener('DOMContentLoaded', () => {
  cargarOpciones('usuarios',   filtroUsuario);
  cargarOpciones('proyectos',  filtroProyecto);
  cargarOpciones('tareas',     filtroTarea);
  cargarOpciones('etiquetas',  filtroEtiqueta);

  btnFiltrar.addEventListener('click', mostrarActividades);
  mostrarActividades();
  setInterval(mostrarActividades, 60000);
});