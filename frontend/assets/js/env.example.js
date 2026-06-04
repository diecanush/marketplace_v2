/**
 * frontend/assets/js/env.example.js
 * Plantilla para variables de entorno del frontend.
 * Copia este archivo como 'env.js' en la misma carpeta para configurar tu entorno específico.
 */

// EJEMPLO A: Producción Hostinger (sin carpeta /frontend/ intermedia en la URL pública)
/*
window.AppEnv = {
  BASE_URL: '/marketplace/',
  API_BASE_URL: '/marketplace/api/routes/',
  UPLOADS_BASE_URL: '/marketplace/uploads/',
  ASSETS_BASE_URL: '/marketplace/assets/'
};
*/

// EJEMPLO B: Local XAMPP (ajustable según la carpeta local y estructura con /frontend/)
/*
window.AppEnv = {
  BASE_URL: '/marketplace_v2/frontend/',
  API_BASE_URL: '/marketplace_v2/api/routes/',
  UPLOADS_BASE_URL: '/marketplace_v2/uploads/',
  ASSETS_BASE_URL: '/marketplace_v2/frontend/assets/'
};
*/

// EJEMPLO C: Autodetección automática (Fallback por defecto)
// Dejar el objeto vacío para que el sistema intente deducir dinámicamente las rutas
window.AppEnv = {};
