export async function verificarSesion() {
    try {
        const res = await fetch('../backend/session_status.php', {
            credentials: 'include'
        });
        const data = await res.json();

        if (!data.autenticado) {
            window.location.href = 'login.html';
            return null;
        }

        const pathname = window.location.pathname;

        // RESTRICCIÓN: Usuario normal no puede acceder a páginas de admin
        if (data.tipo === 0 && (
            pathname.includes('admin') ||
            pathname.includes('ver_actividades') ||
            pathname.includes('gestion_proyectos') ||
            pathname.includes('gestion_usuarios') ||
            pathname.includes('estadisticas') ||
            pathname.includes('etiquetas')
        )) {
            window.location.href = 'usuario.html';
            return null;
        }

        // RESTRICCIÓN: Admin no puede acceder a páginas de usuario
        if (data.tipo === 1 && (
            pathname.includes('usuario') ||
            pathname.includes('registro_tareas') ||
            pathname.includes('mi_progreso') ||
            pathname.includes('mis_tareas')
        )) {
            window.location.href = 'admin.html';
            return null;
        }

        return data;

    } catch (err) {
        console.error('Error verificando sesión:', err);
        window.location.href = 'login.html';
    }
}
