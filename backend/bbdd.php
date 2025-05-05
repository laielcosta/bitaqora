<?php
$host = "localhost";
$user = "root";
$pass = "";
$bd_name = "gestion_actividades";

function conectar() {
    $con = mysqli_connect($GLOBALS["host"], $GLOBALS["user"], $GLOBALS["pass"]);
    if (!$con) {
        die("Error al conectar: " . mysqli_connect_error());  
    }

    crear_bdd($con);

    if (!mysqli_select_db($con, $GLOBALS["bd_name"])) {
        die("Error al seleccionar la base de datos 'gestion_actividades': " . mysqli_error($con));
    }

    // Crear tablas (solo la tabla usuarios para la actividades, las demas tablas estan desarrollo) 
    crear_tabla_usuarios($con);
    crear_tabla_etiquetas($con);
    crear_tabla_proyecto($con);
    crear_tabla_tareas($con);
    crear_tabla_registro($con);
    crear_tabla_registro_etiquetas($con); 

    return $con;
}

function crear_bdd($con) {
    $result = mysqli_query($con, "CREATE DATABASE IF NOT EXISTS gestion_actividades");
    if (!$result) {
        die("Error al crear la base de datos: " . mysqli_error($con));  
    }
}

function crear_tabla_usuarios($con) {
    $result = mysqli_query($con, "CREATE TABLE IF NOT EXISTS usuarios (
        id_usuario INT PRIMARY KEY AUTO_INCREMENT NOT NULL, 
        nombre VARCHAR(255), 
        pass VARCHAR(255), 
        tipo INT
    )");
    if (!$result) {
        die("Error al crear la tabla usuarios: " . mysqli_error($con));
    }
    rellenar_tabla_usuarios($con);
}

function rellenar_tabla_usuarios($con) {
    $usuarioss = array(
        array("Miguel", "1234", 1),
        array("Luis", "0000", 0),
        array("Ana", "4321", 1),
        array("Carlos", "1111", 0),
        array("Antonio", "2222", 1)
    );

    $resultado = obtener_usuarioss($con);
    if (obtener_num_filas($resultado) == 0) {
        foreach ($usuarioss as $usuarios) {
            $nombre = $usuarios[0];
            $password = $usuarios[1];
            $tipo = $usuarios[2];

            $sql = "INSERT INTO usuarios (nombre, pass, tipo) VALUES ('$nombre', '$password', $tipo)";
            if (!mysqli_query($con, $sql)) {
                echo "Error al insertar usuarios '$nombre': " . mysqli_error($con) . "<br>";
            }
        }
    }
}
//funciones para obtener datos
function obtener_usuarioss($con) {
    $resultado = mysqli_query($con, "SELECT * FROM usuarios");
    return $resultado;
}

function obtener_proyectos($con) {
    $resultado = mysqli_query($con, "SELECT * FROM proyectos");
    return $resultado;
}

function obtener_num_filas($resultado) {
    return mysqli_num_rows($resultado);
}

// crear tabla etiquetas ===========================================================================================================================
function crear_tabla_etiquetas($con) {
    $resultado_usuarios = mysqli_query($con, "SHOW TABLES LIKE 'usuarios'");

    if (mysqli_num_rows($resultado_usuarios) == 0) {
        die("Error: la tabla usuarios no existe");
    }

    $result = mysqli_query($con, "CREATE TABLE IF NOT EXISTS etiquetas (
        id_etiqueta INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE
    );");

    if (!$result) {
        die("Error al crear la tabla etiquetas: " . mysqli_error($con));
    }

    // Solo insertar etiquetas si la tabla está vacía
    $hayFilas = mysqli_query($con, "SELECT 1 FROM etiquetas LIMIT 1");
    if (mysqli_num_rows($hayFilas) === 0) {
        rellenar_etiquetas($con);
    }
}

function rellenar_etiquetas($con) {
    $etiquetas = array(
        "Urgente",
        "En revisión",
        "Automatizado",
        "Bloqueado",
        "Reunión",
        "Script nuevo",
        "Test manual"
    );

    foreach ($etiquetas as $nombre) {
        $nombre_escapado = mysqli_real_escape_string($con, $nombre);
        $resultado = mysqli_query($con, "SELECT * FROM etiquetas WHERE nombre = '$nombre_escapado'");
        if (mysqli_num_rows($resultado) == 0) {
            $query = "INSERT INTO etiquetas (nombre) VALUES ('$nombre_escapado')";
            if (!mysqli_query($con, $query)) {
                echo "Error al insertar etiqueta '$nombre': " . mysqli_error($con) . "<br>";
            }
        }
    }
}



// crear tabla registro_etiquetas ===========================================================================================================================

function crear_tabla_registro_etiquetas($con) {
    $resultado_etiquetas = mysqli_query($con, "SHOW TABLES LIKE 'etiquetas'");

    if (mysqli_num_rows($resultado_etiquetas) == 0) {
        die("Error: la tabla etiquetas no existe");
    }

    $result = mysqli_query($con, "CREATE TABLE IF NOT EXISTS registro_etiquetas (
    id_registro INT NOT NULL,
    id_etiqueta INT NOT NULL,
    PRIMARY KEY (id_registro, id_etiqueta),
    FOREIGN KEY (id_registro) REFERENCES registro(id_registro) ON DELETE CASCADE,
    FOREIGN KEY (id_etiqueta) REFERENCES etiquetas(id_etiqueta) ON DELETE CASCADE
    );");
    if (!$result) {
        die("Error al crear la tabla registro_etiquetas: " . mysqli_error($con));
    }
    
}

// crear tabla proyectos ===========================================================================================================================

/* ============================================================
   Crea la tabla `proyectos` y rellena datos iniciales **una vez**
   ============================================================ */
function crear_tabla_proyecto($con) {

    /* —— 1. crear la tabla si no existe —— */
    $sql = "CREATE TABLE IF NOT EXISTS proyectos (
                id_proyecto   INT PRIMARY KEY AUTO_INCREMENT,
                nombre        VARCHAR(100)  NOT NULL,
                descripcion   VARCHAR(255)  NOT NULL,
                fecha_inicio  DATE          NOT NULL,
                fecha_fin     DATE          DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sql)) {
        die('Error al crear tabla proyectos: ' . mysqli_error($con));
    }

    /* —— 2. solo insertar semilla cuando la tabla está vacía —— */
    $hayFilas = mysqli_query($con, "SELECT 1 FROM proyectos LIMIT 1");
    if (mysqli_num_rows($hayFilas) === 0) {
        rellenar_proyectos($con);       // ← función que inserta los proyectos de ejemplo
    }
}


function rellenar_proyectos($con) {
    $proyectos = array(
        array("Desarrollo de Plataforma E-commerce", "Crear una tienda online completa con pasarela de pago, carrito de compras y gestión de inventario.", "2025-04-01", "2025-06-15"),
        array("Sistema de Gestión Escolar", "Diseñar e implementar un sistema web para la administración académica de un centro educativo.", "2025-04-10", "2025-07-01"),
        array("Red Social para Fotógrafos", "Desarrollar una red social donde los usuarios puedan subir, compartir y comentar fotografías.", "2025-04-15", "2025-07-30"),
        array("Dashboard Administrativo para PYMEs", "Construir un panel de control con gráficos, estadísticas y reportes para pequeñas empresas.", "2025-04-20", "2025-06-30"),
        array("Blog Profesional con CMS", "Implementar un sistema de gestión de contenidos personalizado para blogs con categorías, usuarios y editor WYSIWYG.", "2025-04-25", "2025-06-10")
    );

    foreach ($proyectos as $proyecto) {
        $nombre = mysqli_real_escape_string($con, $proyecto[0]);

        $resultado = mysqli_query($con, "SELECT * FROM proyectos WHERE nombre = '$nombre'");
        if (obtener_num_filas($resultado) == 0) {
            $descripcion = mysqli_real_escape_string($con, $proyecto[1]);
            $fecha_inicio = $proyecto[2];
            $fecha_fin = $proyecto[3];

            $query = "INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin)
                      VALUES ('$nombre', '$descripcion', '$fecha_inicio', '$fecha_fin')";

            if (!mysqli_query($con, $query)) {
                echo "Error al insertar proyecto '$nombre': " . mysqli_error($con) . "<br>";
            }
        }
    }
}


// crear tabla registro ===========================================================================================================================
function crear_tabla_registro($con) {
    $resultado_usuarios = mysqli_query($con, "SHOW TABLES LIKE 'usuarios'");
    $resultado_proyectos = mysqli_query($con, "SHOW TABLES LIKE 'proyectos'");

    if (mysqli_num_rows($resultado_usuarios) == 0 || mysqli_num_rows($resultado_proyectos) == 0) {
        die("Error: las tablas usuarios o proyectos no existen");
    }

    $result = mysqli_query($con, "CREATE TABLE IF NOT EXISTS registro (
        id_registro INT PRIMARY KEY AUTO_INCREMENT,
        id_usuario INT NOT NULL,
        proyecto INT NOT NULL,
        tarea INT NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        hora_inicio TIME NULL, 
        hora_fin TIME NULL,
        duracion TIME DEFAULT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        comentarios VARCHAR(255),

        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
        FOREIGN KEY (proyecto) REFERENCES proyectos(id_proyecto),
        FOREIGN KEY (tarea) REFERENCES tareas(id_tarea)

    )");
    if (!$result) {
        die("Error al crear la tabla registro: " . mysqli_error($con));
    }
}
// crear tabla tareas ===========================================================================================================================
function crear_tabla_tareas($con) {

    $query = "CREATE TABLE IF NOT EXISTS tareas (
        id_tarea INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL UNIQUE
    );";

    if (!mysqli_query($con, $query)) {
        die("Error al crear la tabla tareas: " . mysqli_error($con));
    }
    rellenar_tareas($con);
}

function rellenar_tareas($con) {
    $tareas = array(
        "Pruebas",
        "Diseño interfaz",
        "Revisión de bugs"
    );

    // Verificar si ya hay tareas
    $resultado = mysqli_query($con, "SELECT nombre FROM tareas LIMIT 1");
    if (mysqli_num_rows($resultado) == 0) {
        foreach ($tareas as $nombre) {
            $sql = "INSERT INTO tareas ( nombre) 
                    VALUES ('$nombre')";
            if (!mysqli_query($con, $sql)) {
                echo "Error al insertar tarea '$nombre': " . mysqli_error($con) . "<br>";
            }
        }
    }
}

    
// crear tabla REPORTES ===========================================================================================================================
function crear_tabla_reportes($con) {
    $result = mysqli_query($con, "CREATE TABLE IF NOT EXISTS reportes(
    id_reporte INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo_reporte ENUM('excel', 'pdf') NOT NULL,
    filtros_aplicados VARCHAR(500),
    fecha_generado DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX (id_usuario)

);");
    if (!$result) {
        die("Error al crear la tabla etiquetas: " . mysqli_error($con));
    }
}
?>