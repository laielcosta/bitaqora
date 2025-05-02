import { getSessionStatus, login } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
  const saludoAdmin = document.getElementById('saludoAdmin');
  const saludoUsuario = document.getElementById('saludoUsuario');
  const contAdmin = document.getElementById('contenidoAdmin');
  const contUsuario = document.getElementById('contenidoUsuario');
  const loginContainer = document.getElementById('loginContainer');
  const loginForm = document.getElementById('loginForm');

  try {
    const data = await getSessionStatus();

    // Si estamos en login.html
    const isLoginPage = window.location.pathname.includes('login.html');

    if (isLoginPage) {
      if (data.autenticado) {
        // Redirigir automáticamente al panel correcto
        if (data.tipo == 1) window.location.href = 'admin.html';
        else window.location.href = 'usuario.html';
        return;
      }

      // Mostrar el formulario
      if (loginContainer) loginContainer.style.display = 'block';

      // Manejar el envío del formulario
      if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const usuario = document.getElementById('usuario').value;
          const password = document.getElementById('password').value;

          const result = await login(usuario, password);

          if (result.success) {
            if (result.tipo == 1) window.location.href = 'admin.html';
            else window.location.href = 'usuario.html';
          } else {
            alert(result.mensaje);
          }
        });
      }

      return; // Detener aquí para no ejecutar lo demás
    }

    // Si estamos en otras páginas, verificar tipo y mostrar
    if (data.autenticado) {
      if (saludoAdmin && data.tipo == 1) {
        await cargarHeaderEstatico(data.tipo, data.usuario);
        saludoAdmin.textContent = `¡Hola ${data.usuario}! ¿Qué quieres hacer hoy?`;
        contAdmin.style.display = 'block';
      } else if (saludoUsuario && data.tipo == 0) {
        await cargarHeaderEstatico(data.tipo, data.usuario);
        saludoUsuario.textContent = `¡Hola ${data.usuario}! ¿Qué quieres hacer hoy?`;
        contUsuario.style.display = 'block';
      } else {
        // Usuario intentando acceder al panel que no le toca
        if (data.tipo == 1) window.location.href = 'admin.html';
        else window.location.href = 'usuario.html';
      }
    } else {
      // No autenticado, redirigir a login
      window.location.href = 'login.html';
    }

  } catch (err) {
    console.error(err);
    alert('Error al verificar sesión');
  }
});


/*
async function cargarHeaderEstatico(tipo, nombre) {
  const cabecera = document.getElementById('cabecera');
  if (!cabecera) return;

  const res = await fetch('partials/header.html');
  const html = await res.text();
  cabecera.innerHTML = html;

  const sidebar = document.getElementById('sidebar');
  sidebar.innerHTML = tipo === 1
    ? `<p>👋 Hola, ${nombre}</p>
       <a href="admin.html">Inicio</a>
       <a href="ver_actividades.html">Ver actividades</a>
       <a href="gestion_proyectos.html">Proyectos</a>
       <a href="gestion_usuarios.html">Usuarios</a>
       <a href="estadisticas.html">Estadísticas</a>`
    : `<p>👋 Hola, ${nombre}</p>
       <a href="usuario.html">Inicio</a>
       <a href="mis_tareas.html">Mis tareas</a>
       <a href="registro_tareas.html">Registrar tarea</a>
       <a href="mi_progreso.html">Mi progreso</a>`;

  const toggleBtn = document.getElementById('menu-toggle');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('visible');
    });
  }

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      window.location.href = '../backend/logout.php';
    });
  }
}
*/
