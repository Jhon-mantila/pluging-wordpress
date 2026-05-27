# Esquina Shortcodes Plugin

Plugin para WordPress que agrega múltiples shortcodes para mostrar contenido dinámico de YouTube, Facebook, categorías y últimas entradas en formato responsive.

Repositorio oficial:  
https://github.com/Jhon-mantila/pluging-wordpress

Última release estable:  
https://github.com/Jhon-mantila/pluging-wordpress/releases/tag/v1.4.3

---

# Características

- Integración con YouTube Data API v3
- Soporte para videos largos y Shorts
- Carga dinámica vía AJAX
- Grid responsive configurable
- Integración con publicaciones de Facebook
- Listado de categorías con contador
- Últimas entradas compactas
- Compatible con themes modernos
- Optimizado para sitios de noticias, anime y gaming

---

# Instalación

## Método 1 — Descargar release

1. Descarga la última versión:
   https://github.com/Jhon-mantila/pluging-wordpress/releases/tag/v1.4.3

2. Ve a:
   WordPress → Plugins → Añadir nuevo → Subir plugin

3. Activa el plugin.

---

## Método 2 — Git Clone

```bash
git clone https://github.com/Jhon-mantila/pluging-wordpress.git
```

Copiar dentro de:

```bash
wp-content/plugins/
```

Luego activar desde WordPress.

---

# Configuración de APIs

## YouTube Data API v3

Necesitas:

- API Key
- Channel ID

Puedes obtenerlos desde:

https://console.cloud.google.com/

---

## Facebook Graph API

⚠️ NO coloques el token directamente en los shortcodes.

Puedes configurarlo de dos maneras:

---

### Opción 1 — wp-config.php (Recomendado)

```php
define('ESQUINA_FB_PAGE_ACCESS_TOKEN', 'TU_TOKEN_AQUI');
```

---

### Opción 2 — Guardar opción en WordPress

```php
esquina_fb_page_access_token
```

---

# Shortcodes Disponibles

---

# 1. Videos largos de YouTube

## Shortcode

```txt
[youtube_largo channel_id="UC..." api_key="..." max="6" columns="3"]
```

## Parámetros

| Parámetro | Descripción |
|---|---|
| channel_id | ID del canal de YouTube |
| api_key | API Key de YouTube |
| max | Cantidad máxima de videos |
| columns | Número de columnas del grid (1–6) |
| batch | Cantidad de videos por lote vía AJAX |

---

## Funcionamiento

- Muestra únicamente videos con duración superior a 60 segundos.
- Compatible con carga dinámica mediante AJAX.
- Si `max=""` o `max="all"` se cargan todos los videos disponibles.

---

## Ejemplos

### Limitado

```txt
[youtube_largo channel_id="UCxxxx" api_key="xxxx" max="6" columns="3"]
```

### Sin límite

```txt
[youtube_largo channel_id="UCxxxx" api_key="xxxx" max="all" columns="4" batch="8"]
```

---

# 2. Shorts de YouTube

## Shortcode

```txt
[youtube_shorts channel_id="UC..." api_key="..." max="6" columns="4"]
```

## Parámetros

| Parámetro | Descripción |
|---|---|
| channel_id | ID del canal |
| api_key | API Key |
| max | Cantidad máxima de Shorts |
| columns | Número de columnas del grid (1–6) |
| batch | Cantidad de Shorts por lote vía AJAX |

---

## Funcionamiento

- Muestra únicamente videos de 60 segundos o menos.
- Compatible con paginación dinámica vía AJAX.
- Soporta carga completa usando `max="all"`.

---

## Ejemplos

### Limitado

```txt
[youtube_shorts channel_id="UCxxxx" api_key="xxxx" max="8" columns="4"]
```

### Sin límite

```txt
[youtube_shorts channel_id="UCxxxx" api_key="xxxx" max="all" columns="5" batch="10"]
```

---

# 3. Grid de categorías

## Shortcode

```txt
[categorias_grid number="6" columns="3"]
```

---

## Parámetros

| Parámetro | Descripción |
|---|---|
| number | Número de categorías a mostrar |
| columns | Número de columnas del grid |

---

## Ejemplos

### Básico

```txt
[categorias_grid]
```

### Configurado

```txt
[categorias_grid number="6" columns="3"]
```

---

# 4. Contador de entradas por categoría

## Shortcode

```txt
[category_post_count category="anime"]
```

---

## Parámetros

| Parámetro | Descripción |
|---|---|
| category | Slug de la categoría |

---

## Ejemplo

```txt
[category_post_count category="anime"]
```

---

# 5. Publicaciones de Facebook

## Shortcode

```txt
[facebook_posts page_id="631930116676494" limit="25" per_page="4"]
```

---

## Parámetros

| Parámetro | Descripción |
|---|---|
| page_id | ID de la página de Facebook |
| limit | Cantidad máxima de publicaciones |
| per_page | Cantidad mostrada por carga |

---

## Funcionamiento

- Consume publicaciones usando Facebook Graph API.
- Compatible con carga dinámica.
- Utiliza el token configurado en `wp-config.php` o en las opciones de WordPress.

---

## Ejemplo

```txt
[facebook_posts page_id="631930116676494" limit="25" per_page="4"]
```

---

# 6. Últimas entradas

## Shortcode

```txt
[ultimas_entradas number="6" width="90" height="68" footer="true"]
```

---

## Parámetros

| Parámetro | Descripción |
|---|---|
| number | Cantidad de entradas (máximo 10) |
| width | Ancho de miniatura |
| height | Alto de miniatura |
| footer | Diseño compacto para footer |

---

## Funcionamiento

- Muestra las últimas entradas publicadas.
- Diseño responsive y compacto.
- Optimizado para sidebars y footers.
- Con `footer="true"` utiliza un diseño aún más compacto.

---

## Ejemplos

### Normal

```txt
[ultimas_entradas number="6"]
```

### Compacto para footer

```txt
[ultimas_entradas number="6" footer="true"]
```

---

# Compatibilidad

- WordPress 6+
- PHP 8+
- AJAX Load More
- Responsive Design

---

# Recomendaciones

- Usa caché para mejorar rendimiento.
- Evita exponer tokens públicamente.
- Configura límites adecuados para evitar exceso de consumo de APIs.

---

# Roadmap

- Soporte para más redes sociales
- Caché automática
- Widgets Gutenberg
- Más estilos visuales
- Lazy loading avanzado

---

# Licencia

MIT License

---

# Autor

Desarrollado por EsquinaWeb.

- Sitio web: https://esquinaweb.com/
- GitHub: https://github.com/Jhon-mantila