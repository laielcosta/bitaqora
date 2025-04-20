// Esta función comprueba si ya hay una sesión activa en el backend
export async function getSessionStatus() {

    const res = await fetch('../backend/session_status.php');
    return await res.json();
  }
  
  // Esta función intenta iniciar sesión con el usuario y contraseña que escribí en el formulario
  export async function login(usuario, password) {
    const res = await fetch('../backend/session_status.php', {
      method: 'POST', // método POST porque estamos enviando datos
      headers: { 'Content-Type': 'application/json' }, // indicamos que enviamos JSON
      body: JSON.stringify({ usuario, password }) // convertimos los datos a formato JSON
    });
  
    // Convertimos la respuesta en JSON para saber si el login fue exitoso
    return await res.json();
  }
  