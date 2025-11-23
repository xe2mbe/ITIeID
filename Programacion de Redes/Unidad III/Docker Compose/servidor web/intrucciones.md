# Práctica: Servidor Web con Docker Compose (Apache + PHP + SQLite)
## Universidad Politécnica de Durango
Instructor: P.C. Eliud Bueno Moreno
 

## 🎯 Objetivo

En esta práctica vas a:

- Levantar un **servidor web** con **Apache + PHP** usando **Docker Compose**.
- Usar un formulario de **login en HTML** que valida usuario y contraseña guardados en una base de datos **SQLite**.
- Entender qué es un **volumen** en Docker y cómo permite que:
  - El código (HTML/PHP) sea editable desde el host.
  - La base de datos SQLite se guarde fuera del contenedor.

Al final tendrás un mini sistema de login que, tras autenticarse correctamente, muestra una página de bienvenida con los pasos de la práctica.

---

## 📁 Estructura del proyecto

Dentro de tu repositorio entra a la carpeta:
```
ITIeID\Programacion de Redes\Unidad III\Docker Compose
```
ahí déberas ver una esctructura como la que sigue:
```text
Dokcer Compose/
    servidor web/
    ├── docker-compose.yml      # Definición del servicio web y volúmenes
    ├── Dockerfile              # Imagen personalizada con Apache + PHP + SQLite
    ├── instrucciones.md        # Archivo de instrucciones (este contenido)
    ├── www/                    # Sitio web (código accesible desde el host)
    │   ├── index.html          # Formulario de login (HTML)
    │   └── login.php           # Lógica del login y conexión a SQLite
    └── data/                   # Aquí se creará la base de datos users.db
```
Nota: La carpeta data/ puede crearse vacía; el archivo users.db se generará automáticamente desde login.php la primera vez que se use.

---

## 🧾 Archivo: docker-compose.yml

Abre el archivo docker-compose.yml dentro de servidor web/ y analiza su contenido:

```yaml
version: "3.9"  # Versión del formato de docker-compose

services:
  web:
    # Construimos la imagen usando el Dockerfile en este mismo directorio
    build: .
    container_name: apache_login_sqlite

    # Exponemos el puerto 80 del contenedor hacia el 8080 del host
    # Así accedemos en el navegador con: http://localhost:8080
    ports:
      - "8080:80"

    # Volúmenes:
    # 1) ./www  -> /var/www/html  : HTML y PHP accesibles desde fuera
    # 2) ./data -> /var/www/data  : base de datos SQLite persistente y visible
    volumes:
      - ./www:/var/www/html
      - ./data:/var/www/data
```

---

## 🐳 Archivo: Dockerfile

Abre el archivo Dockerfile dentro de servidor web/ y analiza su contenido:

```dockerfile
# Imagen base con Apache + PHP
FROM php:8.2-apache

# Instalamos soporte para SQLite3 en PHP
RUN apt-get update && \
    apt-get install -y libsqlite3-dev && \
    docker-php-ext-install sqlite3 && \
    rm -rf /var/lib/apt/lists/*

# Activamos el módulo rewrite por si luego lo quieres usar
RUN a2enmod rewrite

# Copiamos el contenido inicial del sitio
# (Luego se sobreescribirá por el volumen ./www:/var/www/html)
COPY ./www /var/www/html

# Directorio donde guardaremos la base de datos
RUN mkdir -p /var/www/data

# Damos permisos básicos (para demo; en producción habría que afinar)
RUN chown -R www-data:www-data /var/www

# Apache escucha en el puerto 80 (por defecto)
EXPOSE 80
```

---

## 📝 Archivo: www/index.html

ve a la carpeta www/ dentro de servidor web/ y dentro de www/ y analiza el archivo index.html:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login con SQLite (Docker + Apache)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
        }
        .contenedor {
            width: 320px;
            margin: 60px auto;
            padding: 20px;
            border: 1px solid #cccccc;
            background-color: #ffffff;
        }
        h1 {
            font-size: 18px;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        input {
            width: 95%;
            padding: 5px;
            margin-top: 3px;
        }
        button {
            width: 100%;
            margin-top: 15px;
            padding: 8px;
        }
        .info {
            font-size: 12px;
            color: #555555;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>Login de práctica</h1>

        <p class="info">
            Usuario de prueba: <b>admin</b><br>
            Contraseña de prueba: <b>1234</b>
        </p>

        <form method="post" action="login.php">
            <label for="username">Usuario:</label>
            <input type="text" name="username" id="username">

            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password">

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
```

---

## 🧠 Archivo: www/login.php

En la misma carpeta www/, abre el archivo login.php y analiza el siguiente contenido:

```php
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

// Generamos la respuesta HTML "a la vieja escuela"
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
```

---

## 🚀 Ejecución de la práctica

Ojo: la carpeta se llama servidor web (con espacio). Usa comillas o escapa el espacio.

1. Entrar a la carpeta del proyecto

Linux / macOS / WSL:

```bash
cd "servidor web"
# o
cd servidor\ web
```

Windows (PowerShell):

```powershell
cd "servidor web"
```

2. Crear la carpeta para la base de datos

```bash
mkdir -p data
```

3. Levantar el contenedor con Docker Compose

```bash
docker-compose up --build
```

4. Probar en el navegador

```text
http://localhost:8080
```

Usuario: admin
Contraseña: 1234

5. Detener y limpiar

```bash
# Detener
CTRL + C

# Bajar servicio y red
docker-compose down
```

---

## 🔍 Explicación de conceptos clave

### Imagen vs contenedor

- Imagen: plantilla/receta con Apache, PHP, soporte SQLite y archivos iniciales.
- Contenedor: instancia en ejecución de esa imagen.

### Puertos

```yaml
ports:
  - "8080:80"
```

- El contenedor escucha en el puerto 80 (Apache).
- Tú entras por el puerto 8080 del host → http://localhost:8080

### Volúmenes

```yaml
volumes:
  - ./www:/var/www/html
  - ./data:/var/www/data
```

- ./www:/var/www/html → código HTML/PHP visible y editable desde el host.
- ./data:/var/www/data → base de datos users.db persistente en el host.

### ¿Dónde corre SQLite?

- El código que ejecuta consultas (SQLite3 en PHP) corre dentro del contenedor.
- El archivo users.db se almacena en el host gracias al volumen ./data:/var/www/data.

Puedes inspeccionar la base:

```bash
cd "servidor web"
sqlite3 data/users.db
.tables
SELECT * FROM users;
.exit
```

---

## 🧪 Actividades sugeridas

1. Editar HTML en caliente
2. Explorar la base de datos con sqlite3
3. Agregar un nuevo usuario (alumno1 / passwd1)
4. Discutir imagen vs contenedor vs volumen