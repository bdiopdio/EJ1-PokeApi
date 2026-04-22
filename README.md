# PokeAPI Integration Module

Módulo personalizado para Drupal que consume la API de PokeAPI para mostrar un listado de Pokémon y sus detalles técnicos.

## 📂 Estructura de Archivos

```text
pokeapi_integration/
├── pokeapi_integration.info.yml     # Información básica y dependencias
├── pokeapi_integration.libraries.yml  # Registro de CSS y librerías
├── pokeapi_integration.module       # Hooks de Drupal (ej: hook_theme)
├── pokeapi_integration.routing.yml    # Mapa de URLs del módulo
├── pokeapi_integration.services.yml   # Registro de servicios en el contenedor
├── css/
│   └── pokemon-styles.css           # Estilos para el Grid y detalles
├── src/
│   ├── Controller/
│   │   ├── PokemonListController.php  # Cerebro de la página de listado
│   │   └── PokemonDetailController.php # Cerebro de la página de detalles
│   └── Service/
│       └── PokeApiClient.php        # Cliente HTTP y gestión de caché
└── templates/
    ├── pokemon-list.html.twig       # Esqueleto HTML de la lista
    └── pokemon-detail.html.twig     # Esqueleto HTML de los detalles
```

<br>

## 💻 Setup e Instalación

Este proyecto ha sido desarrollado usando **DDEV**, pero es totalmente compatible con otros entornos locales (Lando, XAMPP, Docker puro).

### Requisitos del Sistema
- **PHP**: 8.1 o superior.
- **Drupal**: 9.4+ o 10.x.
- **Extensiones PHP**: `php-curl` (obligatoria para conectar con la API).

### Opción A: Con DDEV (Recomendado)
1. Asegúrate de tener el módulo en `web/modules/custom/pokeapi_integration`.
2. Activa el módulo y limpia caché:
   ```bash
   ddev drush en pokeapi_integration -y
   ddev drush cr
   ```

### Opción B: Sin DDEV (Entornos estándar)
Si utilizas un servidor local como XAMPP, Laragon o Apache:
1. Copia el módulo en la carpeta de módulos personalizados de tu instalación de Drupal.
2. Abre tu terminal en la raíz del proyecto.
3. Activa el módulo usando Drush global o la interfaz de Drupal (`/admin/modules`):
   ```bash
   drush en pokeapi_integration -y
   drush cr
   ```
4. **Importante**: Asegúrate de que tu PHP tenga habilitada la extensión `curl` y `openssl` para poder realizar peticiones externas por HTTPS.

<br>

## 🛠️ Decisiones Técnicas

### 1. Cliente HTTP y Resiliencia
Uso del servicio `http_client` de Drupal (Guzzle). Se ha implementado un control de errores mediante `ConnectException`. Si la API externa falla o no hay red, el sitio no "rompe"; en su lugar, devuelve un mensaje amigable al usuario manteniendo el resto de la web operativa.

### 2. Paginación y Persistencia
Para mejorar la navegación:
- El sistema recuerda el **offset** (la posición) al navegar.

### 3. Rendimiento mediante Caché
El módulo guarda las respuestas de la API en la base de datos de Drupal para evitar llamadas innecesarias:
- **Listas**: 1 hora.
- **Detalles**: 24 horas.

<br>

## ⚠️ Observaciones y Notas Importantes

### Pruebas "Offline"
Debido a la caché de Drupal, poner el navegador en "Offline" no siempre activa el mensaje de error. Si Drupal ya descargó los datos, los leerá de la base de datos local. 
- **Para probar el error real**: Se debe desconectar el internet (o bloquear el dominio `pokeapi.co`) y **limpiar la caché de Drupal** (`drush cr`) inmediatamente. Solo así se obliga al servidor a intentar una conexión fallida.

### Visualización en entornos locales
Si el sitio se carga pero se ve como texto plano (sin diseño), verifica que la carpeta de librerías de Drupal tenga permisos de lectura, ya que el módulo adjunta sus propios estilos mediante `#attached` en los controladores.

<br>

## 🎥 Demo de Previsualización

Para ver el funcionamiento de la aplicación (listado, paginación, navegación a detalles y persistencia del offset), puedes consultar el siguiente vídeo:

[![Mira la demo aquí](https://shields.io)](demo.mp4)

*Si tienes el vídeo en el propio repositorio, puedes embeberlo directamente así:*
<!-- <video src="docs/demo.mp4" width="800"></video> -->
