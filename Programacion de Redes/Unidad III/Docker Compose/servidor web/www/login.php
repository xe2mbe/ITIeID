<?php
// Ruta al archivo de base de datos dentro del contenedor
// (Este directorio está montado como volumen ./data:/var/www/data)
$dbPath = "/var/www/data/users.db";

/**
 * Inicializa la base de datos si no existe.
 * Crea tabla users y un usuario admin/1234.
 */
function init_db($dbPath) {
    if (!file_exists($dbPath)) {
        // Aseguramos que exista el directorio
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $db = new SQLite3($dbPath);
        // Creamos la tabla de usuarios
        $db->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL
        );");

        // Usuario de prueba
        $db->exec("INSERT INTO users (username, password) VALUES ('admin', '1234');");

        $db->close();
    }
}

// Inicializamos la base de datos si hace falta
init_db($dbPath);

// Obtenemos usuario y contraseña enviados por POST
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Si no llegaron datos, regresamos al formulario
if ($username === '' || $password === '') {
    header("Location: index.html");
    exit();
}

// Abrimos la base de datos
$db = new SQLite3($dbPath);

// Preparamos una consulta simple para validar usuario y contraseña
$stmt = $db->prepare("SELECT * FROM users WHERE username = :u AND password = :p");
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$stmt->bindValue(':p', $password, SQLITE3_TEXT);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

$db->close();

// Generamos la respuesta HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado del login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
        }
        .contenedor {
            width: 500px;
            margin: 60px auto;
            padding: 20px;
            border: 1px solid #cccccc;
            background-color: #ffffff;
        }
        h1 {
            font-size: 20px;
            text-align: center;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .ok {
            color: green;
            font-weight: bold;
        }
        ol {
            margin-top: 10px;
            text-align: left;
        }
        a {
            display: inline-block;
            margin-top: 15px;
        }
        code {
            background-color: #eeeeee;
            padding: 2px 4px;
        }
    </style>
</head>
<body>
<div class="contenedor">
<?php if ($user): ?>
    <h1 class="ok">¡Bienvenido, <?php echo htmlspecialchars($username); ?>!</h1>
    <p>Has iniciado sesión correctamente usando credenciales almacenadas en una base de datos SQLite dentro de un contenedor Docker.</p>

    <p><b>Pasos de la práctica:</b></p>
    <ol>
        <li>Abrir el archivo <code>docker-compose.yml</code> y explicar:
            <ul>
                <li>Qué es el servicio <code>web</code>.</li>
                <li>Qué significan los puertos <code>8080:80</code>.</li>
                <li>Qué son los volúmenes <code>./www</code> y <code>./data</code>.</li>
            </ul>
        </li>
        <li>Modificar el texto de <code>index.html</code> (por ejemplo, cambiar el título o un label),
            guardar y recargar la página en el navegador para ver que el cambio se refleja
            gracias al volumen.</li>
        <li>Entrar al directorio <code>data</code> en la máquina host y comprobar que existe el archivo
            <code>users.db</code>. Abrirlo con <code>sqlite3</code> o alguna herramienta gráfica y mostrar
            la tabla <code>users</code>.</li>
        <li>Agregar un nuevo usuario directamente en la base de datos (por ejemplo,
            <code>alumno1 / passwd1</code>) y comprobar que puede iniciar sesión desde el formulario.</li>
        <li>Comentar con el grupo la diferencia entre:
            <ul>
                <li>Imagen de Docker (la receta, el "molde").</li>
                <li>Contenedor (la ejecución de esa imagen).</li>
                <li>Volumen (el lugar donde los datos sobreviven aunque el contenedor muera).</li>
            </ul>
        </li>
    </ol>

    <a href="index.html">Cerrar sesión y volver al login</a>
<?php else: ?>
    <h1 class="error">Login fallido</h1>
    <p>El usuario o la contraseña no son correctos.</p>
    <a href="index.html">Intentar de nuevo</a>
<?php endif; ?>
</div>
</body>
</html>
