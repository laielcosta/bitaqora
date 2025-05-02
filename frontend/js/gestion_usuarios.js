/* ENDPOINT backend */
const ENDPOINT = '../backend/usuarios_api.php';

const fId   = document.getElementById('id_usuario');
const fUser = document.getElementById('usuario');
const fPass = document.getElementById('clave');
const fTipo = document.getElementById('tipo');
const titulo = document.getElementById('form-title');
const lista  = document.getElementById('lista-usuarios');
const form   = document.getElementById('usuario-form');

let modo = 'crear';

/* —— cargar lista —— */
async function cargarUsuarios() {
  const url = `${ENDPOINT}?_=${Date.now()}`;
  const res = await fetch(url, { credentials:'include', cache:'no-store' });
  const usuarios = await res.json();

  let html = `<table><thead><tr>
      <th>Usuario</th><th>Tipo</th><th></th></tr></thead><tbody>`;
  usuarios.forEach(u => {
    html += `<tr>
      <td>${u.nombre}</td>
      <td>${u.tipo == 1 ? 'Admin' : 'Tester'}</td>
      <td>
        <button onclick='editar(${JSON.stringify(u)})'>✏️</button>
        <button onclick='eliminar(${u.id_usuario})'>🗑️</button>
      </td>
    </tr>`;
  });
  lista.innerHTML = html + '</tbody></table>';
}

/* —— editar —— */
function editar(u) {
  fId.value   = u.id_usuario;
  fUser.value = u.nombre;
  fTipo.value = u.tipo;
  titulo.textContent = 'Editar Usuario';
  modo = 'modificar';
}

/* —— eliminar —— */
async function eliminar(id) {
  if (!confirm('¿Eliminar usuario?')) return;
  const res = await fetch(ENDPOINT, {
    method:'POST',
    credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({accion:'eliminar', id_usuario:id})
  });
  const data = await res.json();
  if (!res.ok) alert(data.error || 'Error');
  cargarUsuarios();
}

/* —— submit —— */
form.addEventListener('submit', async e => {
  e.preventDefault();

  const data = {
    accion : modo,
    id_usuario : fId.value,
    nombre : fUser.value,
    clave  : fPass.value,
    tipo   : fTipo.value
  };
  const res = await fetch(ENDPOINT, {
    method:'POST',
    credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(data)
  });
  const j = await res.json();
  if (!res.ok) { alert(j.error || 'Error'); return; }

  form.reset();
  modo = 'crear';
  titulo.textContent = 'Agregar Usuario';
  cargarUsuarios();
});

document.addEventListener('DOMContentLoaded', cargarUsuarios);
