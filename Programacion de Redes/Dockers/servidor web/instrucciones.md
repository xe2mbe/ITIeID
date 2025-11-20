# Práctica: Servidor Web con Docker (Apache + Ubuntu)
### Carrera: Ingeniería en Tecnologías de la Información e Innovación Digital  
### Universidad Politécnica de Durango  
### Instructor: P.C. Eliud Bueno Moreno

---

## 🎯 Objetivo
Aprender a:
- Construir imágenes Docker usando un Dockerfile basado en Ubuntu.  
- Ejecutar contenedores **sin volumen** (modo clásico).  
- Ejecutar contenedores **con volumen** (modo desarrollo).  
- Comprender cómo funcionan los volúmenes y por qué reflejan cambios en tiempo real.

---

## 📁 Estructura del proyecto

Antes de comenzar, tu carpeta debe verse así:

tu-proyecto/
│
├── Dockerfile
│
└── www/
├── index.html
└── images/
└── ejemplo.png


---

## 📝 Archivo: `index.html`

Coloca esto dentro de `www/index.html`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenidos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 50px;
        }
        img {
            max-width: 300px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Bienvenidos a Nuestro Servidor Apache</h1>
    <p>Este es un ejemplo de página con CSS integrado y una imagen local.</p>
    <img src="images/ejemplo.png" alt="Imagen local">
</body>
</html>
```

## 🐳 Archivo: Dockerfile

Genera el archivo dockerfile en la raiz de tu proyecto:

```html
# Imagen base (Ubuntu)
FROM ubuntu:latest

# Información del mantenedor
LABEL maintainer="Eliud Bueno Moreno"

# Actualizamos e instalamos Apache
RUN apt-get update && apt-get install -y apache2

# Copiamos nuestro sitio web dentro del contenedor
COPY ./www/ /var/www/html/

# Exponemos el puerto 80
EXPOSE 80

# Ejecutamos Apache en primer plano
CMD ["apachectl", "-D", "FOREGROUND"]

```

## 🛠 1. Construir la imagen Docker

Ejecuta esto dentro de la carpeta del proyecto:
```bash
docker build -t "mi-servidor-web" .
```
---
# 🧪 EJERCICIO 1 — Ejecutar el contenedor SIN volumen

En este modo, el contenedor usa su propia copia interna del sitio web.
Cambiar los archivos en la carpeta www/ NO modifica lo que se ve en el navegador.

Ejecuta:
```bash
docker run -d -p 8080:80 --name "server1" "mi-servidor-web"
```
## 🌐 Prueba en el navegador:

http://localhost:8080
### ✔ Verifica: 
Si editas www/index.html NO se reflejan cambios en el navegador.
Este contenedor se comporta como un servidor en producción.

# 🧪 EJERCICIO 2 — Ejecutar el contenedor CON volumen

Ahora usaremos un volumen para enlazar tu carpeta local con la del contenedor.

💥 IMPORTANTE: Debido a que tu ruta contiene espacios, USA COMILLAS.
```bash
docker run -d -p 8081:80 \
  -v "$(pwd)/www:/var/www/html" \
  --name "server2" "mi-servidor-web"
```
## 🌐 Prueba en el navegador:

http://localhost:8081
### ✔ Verifica:

Edita www/index.html → Actualiza en caliente sin reiniciar Docker.
Cambia una imagen en www/images/ejemplo.png → Cambia en el navegador.
Agrega nuevas páginas → Se generan automáticamente.

Este es el modo ideal para desarrollo.
## 🔍 Diferencia entre ambos modos
Modo	            Usa archivos internos	Refleja cambios locales	Ideal para
Sin volumen	            ✔ Sí	              ❌  No	Producción
Con volumen	            ❌ No	            ✔ Sí	Desarrollo

## 🧹 Limpiar contenedores al final
```bash
docker stop "server1" "server2"
docker rm "server1" "server2"
```

