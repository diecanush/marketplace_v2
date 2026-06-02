/**
 * Homepage Themes and Templates Configuration - Artesanías Sur V2
 * Contains predefined visual themes representing different styles.
 */

(function() {
  window.homepageThemes = {
    tierra_artesanal: {
      id: 'tierra_artesanal',
      name: 'Tierra artesanal',
      description: 'Estilo cálido y tradicional con tonos terracota, arcilla y oro cálido. Ideal para resaltar el trabajo hecho a mano y natural.',
      preview: {
        dominant: '#af4220', // Dominant 60%
        secondary: '#FAF8F5', // Secondary 30%
        accent: '#dfa84a', // Accent 10%
        text: '#2C2420' // Text
      },
      variables: {
        '--theme-60': '#af4220',
        '--theme-30': '#FAF8F5',
        '--theme-10': '#dfa84a',
        '--theme-text': '#2C2420',
        '--body-bg': '#FAF8F5',
        '--container-bg': '#FAF8F5',
        '--navbar-bg': 'rgba(44, 36, 32, 0.9)',
        '--navbar-text': 'rgba(255, 255, 255, 0.8)',
        '--card-bg': '#FFFFFF',
        '--card-border': '1px solid rgba(44, 36, 32, 0.04)',
        '--card-shadow': '0 10px 30px -10px rgba(44, 36, 32, 0.08), 0 1px 3px rgba(44, 36, 32, 0.03)',
        '--card-radius': '16px',
        '--card-body-bg': '#FFFFFF',
        '--card-body-text': '#2C2420',
        '--card-body-padding': '0.65rem',
        '--card-price-color': '#af4220',
        '--btn-primary-bg': '#af4220',
        '--btn-primary-text': '#ffffff',
        '--badge-bg': '#dfa84a',
        '--badge-text': '#2C2420',
        '--btn-close-filter': 'none',
        // Fallbacks/Legacy variables compatibility
        '--primary': '#af4220',
        '--primary-hover': '#8e3215',
        '--accent': '#dfa84a',
        '--bg-soft': '#FAF8F5',
        '--text-dark': '#2C2420',
        '--text-muted': '#7A6C65',
        '--border-color': 'rgba(44, 36, 32, 0.08)',
        '--border-radius-lg': '16px',
        '--border-radius-md': '12px',
        '--border-radius-sm': '8px',
        '--btn-shadow': '0 4px 12px rgba(175, 66, 32, 0.2)',
        '--btn-shadow-hover': '0 6px 16px rgba(175, 66, 32, 0.25)'
      }
    },
    minimalista_clara: {
      id: 'minimalista_clara',
      name: 'Minimalista clara',
      description: 'Diseño limpio y moderno con amplios espacios en blanco, tonos grises suaves y un toque sutil de verde pino.',
      preview: {
        dominant: '#2d4a43', // Forest Green 60%
        secondary: '#fcfcfc', // Near White 30%
        accent: '#e09f67', // Peach Sand 10%
        text: '#1a1d20'
      },
      variables: {
        '--theme-60': '#2d4a43',
        '--theme-30': '#fcfcfc',
        '--theme-10': '#e09f67',
        '--theme-text': '#1a1d20',
        '--body-bg': '#fcfcfc',
        '--container-bg': '#fcfcfc',
        '--navbar-bg': 'rgba(45, 74, 67, 0.9)',
        '--navbar-text': 'rgba(255, 255, 255, 0.9)',
        '--card-bg': '#ffffff',
        '--card-border': '1px solid rgba(0, 0, 0, 0.05)',
        '--card-shadow': '0 2px 8px rgba(0, 0, 0, 0.03)',
        '--card-radius': '8px',
        '--card-body-bg': '#ffffff',
        '--card-body-text': '#1a1d20',
        '--card-body-padding': '0.65rem',
        '--card-price-color': '#2d4a43',
        '--btn-primary-bg': '#2d4a43',
        '--btn-primary-text': '#ffffff',
        '--badge-bg': '#e09f67',
        '--badge-text': '#ffffff',
        '--btn-close-filter': 'none',
        // Fallbacks/Legacy variables compatibility
        '--primary': '#2d4a43',
        '--primary-hover': '#1e332e',
        '--accent': '#e09f67',
        '--bg-soft': '#fcfcfc',
        '--text-dark': '#1a1d20',
        '--text-muted': '#6c757d',
        '--border-color': 'rgba(0, 0, 0, 0.05)',
        '--border-radius-lg': '8px',
        '--border-radius-md': '6px',
        '--border-radius-sm': '4px',
        '--btn-shadow': 'none',
        '--btn-shadow-hover': 'none'
      }
    },
    comercial_moderna: {
      id: 'comercial_moderna',
      name: 'Comercial moderna',
      description: 'Vibrante y directa. Ideal para alta rotación de productos, con contrastes de azul profundo, coral alegre y fondos limpios.',
      preview: {
        dominant: '#0d6efd', // Electric Blue 60%
        secondary: '#f8f9fa', // Light gray 30%
        accent: '#fd7e14', // Coral 10%
        text: '#212529'
      },
      variables: {
        '--theme-60': '#0d6efd',
        '--theme-30': '#f8f9fa',
        '--theme-10': '#fd7e14',
        '--theme-text': '#212529',
        '--body-bg': '#f8f9fa',
        '--container-bg': '#f8f9fa',
        '--navbar-bg': 'rgba(13, 110, 253, 0.9)',
        '--navbar-text': 'rgba(255, 255, 255, 0.95)',
        '--card-bg': '#ffffff',
        '--card-border': '1px solid rgba(0, 0, 0, 0.08)',
        '--card-shadow': '0 10px 30px rgba(0, 0, 0, 0.04)',
        '--card-radius': '24px',
        '--card-body-bg': '#ffffff',
        '--card-body-text': '#212529',
        '--card-body-padding': '0.65rem',
        '--card-price-color': '#0d6efd',
        '--btn-primary-bg': '#0d6efd',
        '--btn-primary-text': '#ffffff',
        '--badge-bg': '#fd7e14',
        '--badge-text': '#ffffff',
        '--btn-close-filter': 'none',
        // Fallbacks/Legacy variables compatibility
        '--primary': '#0d6efd',
        '--primary-hover': '#0b5ed7',
        '--accent': '#fd7e14',
        '--bg-soft': '#f8f9fa',
        '--text-dark': '#212529',
        '--text-muted': '#5f6368',
        '--border-color': 'rgba(0, 0, 0, 0.08)',
        '--border-radius-lg': '24px',
        '--border-radius-md': '16px',
        '--border-radius-sm': '10px',
        '--btn-shadow': '0 4px 12px rgba(13, 110, 253, 0.15)',
        '--btn-shadow-hover': '0 6px 20px rgba(13, 110, 253, 0.25)'
      }
    },
    elegante_oscura: {
      id: 'elegante_oscura',
      name: 'Elegante oscura',
      description: 'Fondo nocturno con acentos de oro brillante y tipografías finas. Ideal para piezas de lujo o platería premium.',
      preview: {
        dominant: '#c5a880', // Champagne Gold 60%
        secondary: '#121212', // Pure Dark 30%
        accent: '#d4af37', // Gold Leaf 10%
        text: '#f5f5f7'
      },
      variables: {
        '--theme-60': '#c5a880',
        '--theme-30': '#121212',
        '--theme-10': '#d4af37',
        '--theme-text': '#f5f5f7',
        '--body-bg': '#121212',
        '--container-bg': '#121212',
        '--navbar-bg': 'rgba(18, 18, 18, 0.95)',
        '--navbar-text': 'rgba(245, 245, 247, 0.9)',
        '--card-bg': '#1e1e1e',
        '--card-border': '1px solid rgba(255, 255, 255, 0.08)',
        '--card-shadow': '0 8px 30px rgba(0, 0, 0, 0.45)',
        '--card-radius': '12px',
        '--card-body-bg': '#1e1e1e',
        '--card-body-text': '#f5f5f7',
        '--card-body-padding': '0.65rem',
        '--card-price-color': '#c5a880',
        '--btn-primary-bg': '#c5a880',
        '--btn-primary-text': '#121212',
        '--badge-bg': '#d4af37',
        '--badge-text': '#121212',
        '--btn-close-filter': 'invert(1)',
        // Fallbacks/Legacy variables compatibility
        '--primary': '#c5a880',
        '--primary-hover': '#b0936b',
        '--accent': '#d4af37',
        '--bg-soft': '#121212',
        '--text-dark': '#f5f5f7',
        '--text-muted': '#a1a1a6',
        '--border-color': 'rgba(255, 255, 255, 0.08)',
        '--border-radius-lg': '12px',
        '--border-radius-md': '8px',
        '--border-radius-sm': '6px',
        '--btn-shadow': '0 4px 12px rgba(197, 168, 128, 0.15)',
        '--btn-shadow-hover': '0 6px 20px rgba(197, 168, 128, 0.25)'
      }
    }
  };

  window.applyTheme = function(themeId, targetElement) {
    const theme = window.homepageThemes[themeId];
    if (!theme) return false;

    const target = targetElement || document.documentElement;

    Object.keys(theme.variables).forEach(varName => {
      target.style.setProperty(varName, theme.variables[varName]);
    });

    return true;
  };

  // Load custom themes from localStorage
  try {
    const customThemesJson = localStorage.getItem('custom_themes');
    if (customThemesJson) {
      const customThemes = JSON.parse(customThemesJson);
      Object.keys(customThemes).forEach(themeId => {
        window.homepageThemes[themeId] = customThemes[themeId];
      });
    }
  } catch (e) {
    console.error("Error loading custom themes", e);
  }
})();
