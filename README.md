# 📘 BitáQorA

**BitáQorA** es una plataforma web para la gestión de actividades diarias, diseñada especialmente para testers de software y jefes de proyecto. Permite registrar tareas, generar estadísticas, informes personalizados y visualizar el rendimiento del equipo de forma clara y eficiente.

---

## 🎯 Objetivo

Reemplazar el uso de Excel en el control de tareas diarias, ofreciendo una herramienta optimizada, moderna y accesible desde cualquier dispositivo.

---

## 🧩 Funcionalidades

- Autenticación de usuarios (testers y administradores)
- Registro de actividades con descripción, hora de inicio y fin, duración y comentarios
- Asociación de tareas con proyectos y etiquetas
- Visualización de registros y edición en línea
- Gráficas dinámicas con estadísticas (tiempos, tareas por proyecto, etc.)
- Filtros por fecha, usuario, proyecto o etiqueta
- Exportación y generación de reportes

---

## 🛠️ Tecnologías utilizadas

- **Frontend:** HTML, CSS, JavaScript (Vanilla JS + Chart.js)
- **Backend:** PHP (POO + API REST)
- **Base de datos:** MySQL
- **Servidor local:** XAMPP

---

## 📁 Estructura del proyecto

📦 bitaqora ├── backend │ ├── login_validation.php │ ├── tareas_api.php │ ├── proyectos_api.php │ └── usuarios_api.php ├── frontend │ ├── login.html │ ├── dashboard.html │ ├── tareas.html │ ├── css/ │ │ └── estilos.css │ ├── js/ │ │ ├── main.js │ │ └── api.js │ └── partials/ │ └── header.html ├── registro_tareas.html ├── bbdd.php ├── README.md


---

## ⚙️ Cómo ejecutar el proyecto localmente (XAMPP)

1. Copia la carpeta del proyecto a: `C:\xampp\htdocs\`
2. Abre XAMPP y enciende **Apache** y **MySQL**
3. Importa el archivo `.sql` de la base de datos (si lo tienes) en `phpMyAdmin`
4. Accede en tu navegador a:  
   👉 `http://localhost/bitaqora/frontend/login.html`

---

## 📅 Entrega

📌 **Fecha límite:** 23 de mayo de 2025  
🎓 Proyecto de grado del ciclo de Desarrollo de Aplicaciones Web

---

## 👨‍💻 Autor

Miguel Laiel Costa  
[GitHub: @laielcosta](https://github.com/laielcosta)
