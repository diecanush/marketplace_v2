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

  // Helper interno de normalización de URLs para evitar dobles barras seguidas
  // a excepción de las barras del protocolo http:// o https://
  function normaliseUrl(url) {
    if (!url) return '';
    const protocolMatch = url.match(/^(https?:\/\/)/i);
    if (protocolMatch) {
      const protocol = protocolMatch[1];
      const remainder = url.substring(protocol.length);
      return protocol + remainder.replace(/\/+/g, '/');
    }
    return url.replace(/\/+/g, '/');
  }

  try {
    // 1. Cargar overrides del entorno si están definidos en env.js
    const env = window.AppEnv || {};

    if (env.BASE_URL) {
      baseUrl = normaliseUrl(env.BASE_URL);
      apiBaseUrl = normaliseUrl(env.API_BASE_URL || (baseUrl + '/api/routes'));
      uploadsBaseUrl = normaliseUrl(env.UPLOADS_BASE_URL || (baseUrl + '/uploads'));
      assetsBaseUrl = normaliseUrl(env.ASSETS_BASE_URL || (baseUrl + '/assets'));
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
          // Autodetección de subcarpeta (ej. /marketplace/) quitando archivos y carpetas del final
          let cleanPath = pathname;
          if (cleanPath.includes('.')) {
            cleanPath = cleanPath.substring(0, cleanPath.lastIndexOf('/'));
          }
          if (cleanPath.endsWith('/')) {
            cleanPath = cleanPath.slice(0, -1);
          }
          let changed = true;
          while (changed) {
            changed = false;
            if (cleanPath.endsWith('/admin')) {
              cleanPath = cleanPath.slice(0, -6);
              changed = true;
            } else if (cleanPath.endsWith('/panel')) {
              cleanPath = cleanPath.slice(0, -6);
              changed = true;
            }
          }
          baseUrl = window.location.origin + cleanPath;
        }
      }

      // Derivar rutas basadas en la URL base calculada
      apiBaseUrl = normaliseUrl(baseUrl + '/api/routes');
      uploadsBaseUrl = normaliseUrl(baseUrl + '/uploads');
      assetsBaseUrl = normaliseUrl(baseUrl + '/frontend/assets');
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

  // Exponer objeto global de configuración
  window.AppConfig = {
    BASE_URL: baseUrl,
    API_BASE_URL: apiBaseUrl,
    UPLOADS_BASE_URL: uploadsBaseUrl,
    ASSETS_BASE_URL: assetsBaseUrl,
    
    // Rutas fijas del frontend
    LOGIN_URL: normaliseUrl(baseUrl + frontendPath + '/login.html'),
    INDEX_URL: normaliseUrl(baseUrl + frontendPath + '/index.html'),
    ADMIN_BASE_URL: normaliseUrl(baseUrl + frontendPath + '/admin'),
    PANEL_BASE_URL: normaliseUrl(baseUrl + frontendPath + '/panel'),
    
    // Getter dinámico para el Dashboard según el rol del usuario autenticado
    get DASHBOARD_URL() {
      try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (user && user.rol === 'admin') {
          return normaliseUrl(this.ADMIN_BASE_URL + '/dashboard.html');
        }
      } catch (e) {
        console.error("Error al leer datos de usuario en localStorage", e);
      }
      return normaliseUrl(this.PANEL_BASE_URL + '/dashboard.html');
    },

    // 1. Resuelve URL de páginas y recursos estáticos del frontend
    resolveUrl(path) {
      if (!path) return this.BASE_URL;
      if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) {
        return path;
      }
      let clean = path.replace(/^\/+/, '');
      const hasFrontend = window.location.pathname.replace(/\\/g, '/').includes('/frontend/');
      if (hasFrontend && !clean.startsWith('frontend/')) {
        return normaliseUrl(this.BASE_URL + '/frontend/' + clean);
      }
      return normaliseUrl(this.BASE_URL + '/' + clean);
    },

    // 2. Resuelve URL de los endpoints de la API
    resolveApiUrl(path) {
      if (!path) return this.API_BASE_URL;
      if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) {
        return path;
      }
      let clean = path.replace(/^\/+/, '');
      if (clean.startsWith('api/routes/')) {
        clean = clean.substring(11);
      }
      return normaliseUrl(this.API_BASE_URL + '/' + clean);
    },

    // 3. Resuelve la URL de las imágenes (productos, tiendas, etc.)
    resolveImageUrl(path, size = 'full') {
      if (!path) {
        return 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80';
      }
      if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) {
        return path;
      }
      let clean = path.replace(/\\/g, '/').replace(/^\/+/, '');
      if (clean.startsWith('uploads/')) {
        clean = clean.substring(8);
      }
      
      if (size === 'thumb' || size === 'medium') {
        let parts = clean.split('/');
        if (parts.length > 1) {
          let filename = parts.pop();
          let folder = parts.join('/');
          clean = `${folder}/${size === 'thumb' ? 'thumbs' : 'medium'}/${filename}`;
        } else {
          clean = `${size === 'thumb' ? 'thumbs' : 'medium'}/${clean}`;
        }
      }
      return normaliseUrl(this.UPLOADS_BASE_URL + '/' + clean);
    },

    // 4. Realiza una redirección segura a un path del sistema
    goTo(path) {
      window.location.href = this.resolveUrl(path);
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
