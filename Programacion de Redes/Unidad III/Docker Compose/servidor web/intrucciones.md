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

### 1️⃣ Entrar a la carpeta del proyecto
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
### 2️⃣ Crear la carpeta para la base de datos **con permisos**

Como la base de datos `users.db` se va a guardar en un volumen (`./data:/var/www/data`),  
es importante que la carpeta `data` tenga permisos de escritura para el proceso de Apache/PHP.

```bash
mkdir -p data
sudo chmod 777 data
```

> En un entorno real no usarías `777`, pero para la práctica es perfecto y evita problemas de permisos con el volumen.

### 3️⃣ Levantar el contenedor con Docker Compose (primer plano)

```bash
sudo docker-compose up --build
```

- `--build` fuerza a construir la imagen a partir del `Dockerfile`.
- Deja este comando corriendo; verás los logs de Apache y PHP.

### 4️⃣ Probar en el navegador

Abre tu navegador y entra a:

```text
http://localhost:8080
```

Credenciales de prueba:

- Usuario: `admin`  
- Contraseña: `1234`

Si el login es correcto, verás la página de bienvenida con los pasos de la práctica.  
Si ves un error de **“Unable to open database: unable to open database file”**, revisa que:

- La carpeta `data` exista.
- Tenga permisos: `ls -ld data`
- Si es necesario, vuelve a aplicar:

```bash
sudo chmod 777 data
sudo docker-compose down
sudo docker-compose up --build
```

### 5️⃣ Detener y limpiar (cuando estás en primer plano)

Para detener el servicio:

```bash
# En la misma terminal donde corre docker-compose
CTRL + C
```

Para bajar contenedores y red:

```bash
sudo docker-compose down
```

---

### 🔎 Nota importante sobre el comando `sqlite3`

Para revisar o modificar la base de datos `users.db` puedes hacerlo de **dos maneras**:

#### Opción A: usar `sqlite3` dentro del contenedor (recomendado)

1. Ver el nombre del contenedor:

```bash
sudo docker ps
```

2. Entrar al contenedor (el nombre puede ser `apache_login_sqlite`):

```bash
sudo docker exec -it apache_login_sqlite bash
```

3. Dentro del contenedor, abrir la base de datos:

```bash
sqlite3 /var/www/data/users.db
```

4. Ya en la consola de `sqlite3`, puedes ejecutar:

```sql
.tables
SELECT * FROM users;
INSERT INTO users (username, password) VALUES ('alumno1', 'passwd1');
SELECT * FROM users;
.exit
```

Luego sales del contenedor con:

```bash
exit
```

#### Opción B: usar `sqlite3` en el host

Si prefieres usar `sqlite3` directamente en tu máquina (host), primero debes instalarlo:

```bash
sudo apt update
sudo apt install sqlite3
```

Después, desde la carpeta del proyecto:

```bash
cd "servidor web"
sqlite3 data/users.db
```

Y ahí puedes usar los mismos comandos SQL:

```sql
.tables
INSERT INTO users (username, password) VALUES ('alumno1', 'passwd1');
SELECT * FROM users;
.exit
```

> Si al ejecutar `sqlite3` en el host te aparece el mensaje  
> `Command 'sqlite3' not found, but can be installed with: sudo apt install sqlite3`,  
> significa que debes instalarlo (Opción B) o usar la Opción A dentro del contenedor.

---

## 🧪 Actividad extra: Agregar otro usuario y comprobar la persistencia

En esta actividad vas a:

1. Agregar un nuevo usuario directamente en la base de datos SQLite usando comandos SQL.
2. Comprobar que el usuario puede hacer login.
3. Bajar el contenedor y volverlo a levantar.
4. Ver que el usuario **sigue existiendo** gracias al volumen `./data`.

### 1️⃣ Agregar un nuevo usuario en SQLite

Asegúrate de que la práctica ya se ejecutó al menos una vez y que se creó el archivo `users.db`.

Desde la carpeta del proyecto:

```bash
cd "servidor web"
ls data
```

Deberías ver:

```text
users.db
```

Ahora entra a la base de datos:

- Opción A: dentro del contenedor (`sqlite3 /var/www/data/users.db`)
- Opción B: en el host (`sqlite3 data/users.db`)

Dentro de `sqlite3`, ejecuta:

```sql
.tables
SELECT * FROM users;
INSERT INTO users (username, password) VALUES ('alumno1', 'passwd1');
SELECT * FROM users;
.exit
```

### 2️⃣ Probar el nuevo usuario en el login

Ve al navegador y entra a:

```text
http://localhost:8080
```

Haz login con:

- Usuario: `alumno1`
- Contraseña: `passwd1`

Si todo está bien, deberías ver la misma página de bienvenida, pero con:

```text
¡Bienvenido, alumno1!
```

### 3️⃣ Comprobar la persistencia al bajar el contenedor

Ahora vamos a demostrar que los datos **no se pierden** cuando se baja el contenedor, gracias al volumen `./data`.

En la terminal donde está corriendo Docker Compose, detén el servicio con:

```bash
CTRL + C
```

Luego baja el proyecto:

```bash
sudo docker-compose down
```

Confirma que no hay contenedores del proyecto:

```bash
sudo docker-compose ps
```

La base de datos sigue en el host:

```bash
ls data
```

Deberías seguir viendo `users.db`.

### 4️⃣ Volver a levantar el contenedor y probar otra vez

Levanta de nuevo el servicio en **primer plano**:

```bash
sudo docker-compose up --build
```

Cuando esté arriba, vuelve al navegador:

```text
http://localhost:8080
```

Haz login otra vez con:

- Usuario: `alumno1`
- Contraseña: `passwd1`

✅ Si puedes iniciar sesión, significa que:

- El archivo `users.db` se quedó guardado en la carpeta `data` del **host**.
- Al recrear el contenedor, se volvió a montar el volumen `./data:/var/www/data`.
- Los datos **persisten** incluso cuando el contenedor se destruye.

> Este es justamente el beneficio de usar volúmenes:  
> **contenedores efímeros, datos persistentes**.

### 5️⃣ Levantar el servicio en segundo plano (modo “detached”)

Hasta ahora hemos usado:

```bash
sudo docker-compose up --build
```

que deja el servicio corriendo **en primer plano** (vemos los logs en la terminal).

Ahora vamos a levantar el servicio en **segundo plano** usando la opción `-d` (detached):

```bash
sudo docker-compose up -d
```

1. Ejecuta:

```bash
sudo docker-compose up -d
```

2. Verifica que el contenedor está arriba:

```bash
sudo docker-compose ps
```

3. Entra al navegador:

```text
http://localhost:8080
```

4. Haz login con el usuario que creaste (por ejemplo, `alumno1 / passwd1`) y confirma que sigue funcionando.

Para detener el servicio cuando está en segundo plano:

```bash
sudo docker-compose down
```

> Comenta con el grupo la diferencia entre levantar el servicio en primer plano  
> (`up` normal) y en segundo plano (`up -d`).

---

### 6️⃣ Eliminar el contenedor y comprobar de nuevo la persistencia

Hasta ahora has visto que los datos persisten aunque se baje el servicio con:

```bash
sudo docker-compose down
```

Ahora vamos a ser más explícitos y eliminar el contenedor, verificando que la base de datos sigue intacta.

1. Asegúrate de que el servicio está levantado (puede ser en modo detached):

```bash
sudo docker-compose up -d
```

2. Verifica el estado del contenedor:

```bash
sudo docker-compose ps
```

Deberías ver algo como:

```text
      Name               Command               State           Ports
----------------------------------------------------------------------------
apache_login_sqlite   docker-php-entrypoi…     Up      0.0.0.0:8080->80/tcp
```

3. Baja el servicio y elimina el contenedor con:

```bash
sudo docker-compose down
```

> `docker-compose down` detiene y elimina el contenedor y la red del proyecto,
> pero **NO borra** la carpeta `data/` ni el archivo `users.db` del host.

4. Comprueba que el contenedor ya no existe:

```bash
sudo docker-compose ps
```

Deberías ver la tabla vacía (sin servicios).

5. Revisa que la base de datos sigue en el host:

```bash
ls data
```

Deberías seguir viendo:

```text
users.db
```

Si quieres, incluso puedes abrirla:

```bash
sqlite3 data/users.db
.tables
SELECT * FROM users;
.exit
```

y ver que los usuarios (incluyendo `alumno1`) siguen ahí.

6. Levanta de nuevo el servicio (puede ser en segundo plano):

```bash
sudo docker-compose up -d
```

7. Entra otra vez al navegador:

```text
http://localhost:8080
```

Haz login con:

- Usuario: `alumno1`
- Contraseña: `passwd1`

✅ Aunque el contenedor anterior fue eliminado, los datos persisten porque:

- El archivo `users.db` vive en la carpeta `data/` del **host**.
- Al crear un contenedor nuevo, `docker-compose` vuelve a montar el volumen `./data:/var/www/data` y reutiliza la misma base de datos.

> Idea clave: **contenedores desechables, datos persistentes** gracias a los volúmenes.

---

## 📋 Comandos de repaso

```bash
# Ver contenedores activos
docker ps

# Ver contenedores activos y detenidos
docker ps -a

# Levantar servicio en primer plano (viendo logs)
sudo docker-compose up --build

# Levantar servicio en segundo plano (detached)
sudo docker-compose up -d

# Ver estado de los servicios del compose actual
sudo docker-compose ps

# Detener (en la misma terminal si está en primer plano)
CTRL + C

# Bajar servicio y red (tanto si estaba en primer o segundo plano)
sudo docker-compose down
```