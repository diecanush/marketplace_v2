/**
 * frontend/assets/js/app-config.js
 * Configuración dinámica y centralizada de endpoints, assets y redirecciones.
 */
(function() {
  let baseUrl = '';
  let apiBaseUrl = '';
  let uploadsBaseUrl = '';
  let assetsBaseUrl = '';
  let resolutionError = null;

  try {
    // 1. Cargar overrides del entorno si están definidos en env.js
    const env = window.AppEnv || {};

    if (env.BASE_URL) {
      baseUrl = env.BASE_URL;
      apiBaseUrl = env.API_BASE_URL || (baseUrl + '/api/routes');
      uploadsBaseUrl = env.UPLOADS_BASE_URL || (baseUrl + '/uploads');
      assetsBaseUrl = env.ASSETS_BASE_URL || (baseUrl + '/frontend/assets');
    } else {
      const pathname = window.location.pathname.replace(/\\/g, '/');
      const protocol = window.location.protocol;

      if (protocol === 'file:') {
        // En protocolo local file://, buscamos la carpeta 'frontend' para deducir el path de localhost
        let frontendIdx = pathname.indexOf('/frontend/');
        if (frontendIdx !== -1) {
          let segments = pathname.substring(0, frontendIdx).split('/').filter(Boolean);
          
          // Buscar raíces de servidor web comunes
          let rootIdx = -1;
          const serverRoots = ['htdocs', 'www', 'public_html'];
          for (let r of serverRoots) {
            let idx = segments.map(s => s.toLowerCase()).indexOf(r);
            if (idx !== -1) {
              rootIdx = idx;
              break;
            }
          }

          if (rootIdx !== -1 && rootIdx < segments.length - 1) {
            let basePath = segments.slice(rootIdx + 1).join('/');
            baseUrl = `http://localhost/${basePath}`;
          } else if (segments.length > 0) {
            let lastSegment = segments[segments.length - 1];
            baseUrl = `http://localhost/${lastSegment}`;
          } else {
            throw new Error("No se pudo extraer la carpeta raíz del proyecto desde la ruta de archivos físicos.");
          }
        } else {
          throw new Error("Ruta física no compatible. La carpeta 'frontend' no se encontró en la ruta actual: " + pathname);
        }
      } else {
        // Protocolo HTTP/HTTPS
        let idx = pathname.indexOf('/frontend/');
        if (idx !== -1) {
          let basePath = pathname.substring(0, idx);
          baseUrl = window.location.origin + basePath;
        } else {
          // Asumir la raíz del dominio
          baseUrl = window.location.origin;
        }
      }

      // Derivar rutas basadas en la URL base calculada
      apiBaseUrl = baseUrl + '/api/routes';
      uploadsBaseUrl = baseUrl + '/uploads';
      assetsBaseUrl = baseUrl + '/frontend/assets';
    }

    // Validar resolución final
    if (!baseUrl && !apiBaseUrl) {
      throw new Error("La URL base no pudo ser autodetectada y no se proporcionó ninguna ruta explícita en env.js.");
    }

  } catch (err) {
    resolutionError = err.message;
    console.error("[AppConfig Error] Fallo al resolver las rutas del sistema:", err);
  }

  // Determinar la existencia de la subcarpeta '/frontend' en el path actual de la ventana
  const currentPath = window.location.pathname.replace(/\\/g, '/');
  const frontendPath = currentPath.includes('/frontend/') ? '/frontend' : '';

  // Exponer objeto global de configuración con getters para resolver dinámicamente según sesión
  window.AppConfig = {
    BASE_URL: baseUrl,
    API_BASE_URL: apiBaseUrl,
    UPLOADS_BASE_URL: uploadsBaseUrl,
    ASSETS_BASE_URL: assetsBaseUrl,
    
    // Rutas fijas del frontend
    LOGIN_URL: baseUrl + frontendPath + '/login.html',
    INDEX_URL: baseUrl + frontendPath + '/index.html',
    ADMIN_BASE_URL: baseUrl + frontendPath + '/admin',
    PANEL_BASE_URL: baseUrl + frontendPath + '/panel',
    
    // Getter dinámico para el Dashboard según el rol del usuario autenticado
    get DASHBOARD_URL() {
      try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (user && user.rol === 'admin') {
          return this.ADMIN_BASE_URL + '/dashboard.html';
        }
      } catch (e) {
        console.error("Error al leer datos de usuario en localStorage", e);
      }
      return this.PANEL_BASE_URL + '/dashboard.html';
    },

    error: resolutionError
  };

  // Mostrar diagnóstico visual si ocurrió un error en la carga
  if (resolutionError) {
    window.addEventListener('DOMContentLoaded', function() {
      const diagDiv = document.createElement('div');
      diagDiv.style.position = 'fixed';
      diagDiv.style.top = '0';
      diagDiv.style.left = '0';
      diagDiv.style.width = '100%';
      diagDiv.style.backgroundColor = '#f8d7da';
      diagDiv.style.color = '#721c24';
      diagDiv.style.padding = '15px';
      diagDiv.style.borderBottom = '3px solid #f5c6cb';
      diagDiv.style.zIndex = '999999';
      diagDiv.style.fontFamily = 'monospace';
      diagDiv.style.fontSize = '12px';
      diagDiv.innerHTML = `
        <h4 style="margin:0 0 5px 0; font-weight:bold;">⚠️ Error Crítico de Configuración (AppConfig)</h4>
        <p style="margin:0 0 10px 0;">No se pudo resolver la ruta base del sistema automáticamente.</p>
        <details>
          <summary style="cursor:pointer; font-weight:bold;">Ver diagnóstico técnico</summary>
          <pre style="margin:10px 0 0 0; background:#fff; padding:10px; border:1px solid #ddd; overflow:auto; color:#000;">
Detalle: ${resolutionError}
Protocolo: ${window.location.protocol}
Pathname: ${window.location.pathname}
Host: ${window.location.host}
Origin: ${window.location.origin}
          </pre>
        </details>
        <p style="margin:10px 0 0 0; font-size:11px;"><em>Solución sugerida: Copia 'frontend/assets/js/env.example.js' como 'env.js' y define explícitamente la propiedad 'API_BASE_URL' para tu entorno.</em></p>
      `;
      document.body.appendChild(diagDiv);
    });
  }
})();
