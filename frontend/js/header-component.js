class AppHeader extends HTMLElement {
    async connectedCallback() {
      // 1) verificar sesión
      let session;
      try {
        const r = await fetch('../backend/session_status.php', {
          credentials: 'include'
        });
        session = await r.json();
      } catch {
        return window.location.href = 'login.html';
      }
  
      const isAdminPage = this.hasAttribute('admin-page');
      const isUserPage  = this.hasAttribute('user-page');
      const tipo = session.tipo;
  
      // 2) control de acceso
      if (isAdminPage && tipo !== 1) {
        return window.location.href = 'usuario.html';
      }
      if (isUserPage && tipo !== 0) {
        return window.location.href = 'admin.html';
      }
  
      // 3) elegir partial
      const partial = tipo === 1
        ? 'partials/header-admin.html'
        : 'partials/header-user.html';
  
      // 4) cargar e inyectar
      try {
        const resp = await fetch(partial, { cache: 'no-store' });
        const html = await resp.text();
        this.innerHTML = html;
      } catch (e) {
        console.error('Error cargando header:', e);
        return;
      }
  
      // 5) rellenar el nombre en la cabecera
      if (tipo === 1) {
        this.querySelector('#admin-name').textContent = session.nombre;
      } else {
        this.querySelector('#user-name').textContent  = session.nombre;
      }
  
      // 6) toggle sidebar
      const sidebar = this.querySelector('#sidebar');
      const toggle  = this.querySelector('#menu-toggle');
      if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
          sidebar.classList.toggle('visible');
        });
      }
  
      // 7) logout
      const logout = this.querySelector('#logoutBtn');
      if (logout) {
        logout.addEventListener('click', () => {
          window.location.href = '../backend/logout.php';
        });
      }
    }
  }
  
  customElements.define('app-header', AppHeader);
  