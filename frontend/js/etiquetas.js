/* etiquetas.js - Gestión de Etiquetas con aparición bajo demanda y toggle */

// Endpoint del backend
const ENDPOINT = '../backend/etiquetas_api.php';

// Elementos del DOM
const fId           = document.getElementById('etiqueta-id');
const fNombre       = document.getElementById('nombre-etiqueta');
const tablaBody     = document.getElementById('tabla-etiquetas').querySelector('tbody');
const form          = document.getElementById('form-etiqueta');
const btnEtiquetas  = document.getElementById('btn-etiquetas');
const secEtiquetas  = document.getElementById('gestion-etiquetas');

let modo = 'crear';

// Aseguramos que la sección esté oculta al iniciar
secEtiquetas.style.display = 'none';

/**
 * Carga y renderiza todas las etiquetas desde el servidor
 */
async function cargarEtiquetas() {
  try {
    const res = await fetch(`${ENDPOINT}?_=${Date.now()}`, { credentials: 'include' });
    if (res.status === 401) {
      alert('No autorizado: inicia sesión como administrador.');
      window.location.href = 'login.html';
      return;
    }
    if (!res.ok) throw new Error(`Error al cargar etiquetas (HTTP ${res.status})`);

    const data = await res.json();
    tablaBody.innerHTML = '';

    data.forEach(etq => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${etq.nombre}</td>
        <td>
          <button class="edit-btn" data-id="${etq.id}" data-nombre="${etq.nombre}">Editar</button>
          <button class="delete-btn" data-id="${etq.id}">Eliminar</button>
        </td>
      `;
      tablaBody.appendChild(tr);
    });

    // Asignar listeners a botones de edición
    tablaBody.querySelectorAll('.edit-btn').forEach(btn =>
      btn.addEventListener('click', () => {
        modo = 'editar';
        fId.value = btn.dataset.id;
        fNombre.value = btn.dataset.nombre;
        form.querySelector('button[type="submit"]').textContent = 'Actualizar';
      })
    );

    // Asignar listeners a botones de eliminación
    tablaBody.querySelectorAll('.delete-btn').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar esta etiqueta?')) return;
        try {
          const resDel = await fetch(ENDPOINT, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: btn.dataset.id })
          });
          if (resDel.status === 401) {
            alert('No autorizado: inicia sesión como administrador.');
            window.location.href = 'login.html';
            return;
          }
          const j = await resDel.json();
          if (!resDel.ok) throw new Error(j.error || `Error al eliminar etiqueta (HTTP ${resDel.status})`);
          cargarEtiquetas();
        } catch (err) {
          alert(err.message);
        }
      })
    );

  } catch (error) {
    console.error(error);
    alert(error.message);
  }
}

/**
 * Gestión del formulario para crear/actualizar etiquetas
 */
form.addEventListener('submit', async event => {
  event.preventDefault();
  const nombre = fNombre.value.trim();
  if (!nombre) return alert('El nombre de etiqueta es obligatorio');

  const payload = { nombre };
  let method = 'POST';

  if (modo === 'editar') {
    method = 'PUT';
    payload.id = fId.value;
  }

  try {
    const res = await fetch(ENDPOINT, {
      method,
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    if (res.status === 401) {
      alert('No autorizado: inicia sesión como administrador.');
      window.location.href = 'login.html';
      return;
    }
    const j = await res.json();
    if (!res.ok) throw new Error(j.error || `Error al guardar etiqueta (HTTP ${res.status})`);

    form.reset();
    modo = 'crear';
    form.querySelector('button[type="submit"]').textContent = 'Guardar';
    cargarEtiquetas();
  } catch (err) {
    alert(err.message);
  }
});

/**
 * Mostrar/ocultar sección de etiquetas al hacer click (toggle)
 */
btnEtiquetas.addEventListener('click', () => {
  const isHidden = secEtiquetas.style.display === 'none';
  if (isHidden) {
    secEtiquetas.style.display = 'block';
    cargarEtiquetas();
    secEtiquetas.scrollIntoView({ behavior: 'smooth' });
  } else {
    secEtiquetas.style.display = 'none';
  }
});
