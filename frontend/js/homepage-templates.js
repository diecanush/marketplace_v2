/**
 * Homepage Templates Engine - Artesanías Sur V2
 * Centralizes layout components as pure, synchronous functions.
 */

(function() {
  // 1. Sanitization Helpers
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function(match) {
      const entities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      };
      return entities[match];
    });
  }

  function sanitizeUrl(url) {
    if (url === null || url === undefined) return '#';
    let clean = String(url).trim();
    if (clean.toLowerCase().startsWith('javascript:')) {
      return '#';
    }
    return clean.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function sanitizeImgUrl(url) {
    if (!url) {
      return 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80';
    }
    let clean = sanitizeUrl(url);
    if (typeof getImgUrl === 'function') {
      return getImgUrl(clean, 1);
    }
    return clean;
  }

  // Format font-family string safely
  function formatFont(font) {
    if (!font || font === 'inherit') return 'inherit';
    const serifFonts = ['Lora', 'Merriweather', 'Playfair Display'];
    const generic = serifFonts.includes(font) ? 'serif' : 'sans-serif';
    return `'${font}', ${generic}`;
  }

  // Dynamic YIQ contrast helper to keep text readable on custom color backgrounds
  function getContrastColor(hex) {
    if (!hex || hex.length < 6) return '#ffffff';
    let r = parseInt(hex.substring(1,3), 16);
    let g = parseInt(hex.substring(3,5), 16);
    let b = parseInt(hex.substring(5,7), 16);
    let yiq = ((r*299)+(g*587)+(b*114))/1000;
    return (yiq >= 128) ? '#2c2420' : '#ffffff';
  }

  // Convert Hex background to semi-transparent RGBA overlay for background images
  function hexToRgba(hex, alpha = 0.85) {
    if (!hex || hex.length < 6) return `rgba(44, 36, 32, ${alpha})`;
    let r = parseInt(hex.substring(1,3), 16);
    let g = parseInt(hex.substring(3,5), 16);
    let b = parseInt(hex.substring(5,7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  // Fallbacks in case config keys are missing
  const fallbacks = {
    title: 'Título de la Sección',
    subtitle: 'Descripción o subtítulo de la sección.',
    badge: 'Destacado',
    description: 'Descripción detallada en Artesanías Sur.',
    button_text: 'Ver más',
    button_link: '#',
    image_url: 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80'
  };

  function getVal(payload, key) {
    return (payload && payload[key] !== undefined) ? payload[key] : (fallbacks[key] || '');
  }

  // 2. Component Functions
  window.homepageTemplates = {
    hero: function(payload) {
      const title = escapeHtml(getVal(payload, 'title'));
      const badge = escapeHtml(getVal(payload, 'badge'));
      const description = escapeHtml(getVal(payload, 'description'));
      const button_text = escapeHtml(getVal(payload, 'button_text'));
      const button_link = sanitizeUrl(getVal(payload, 'button_link'));
      const image_url = sanitizeImgUrl(getVal(payload, 'image_url'));

      // Style variables with safety fallbacks
      const bg_color = escapeHtml(payload?.style_background_color || '#2c2420');
      const text_align = escapeHtml(payload?.style_text_align || 'left');
      
      const badge_color = escapeHtml(payload?.style_badge_color || '#ffc107');
      const badge_font = escapeHtml(payload?.style_badge_font_family || 'Outfit');
      const badge_size = escapeHtml(payload?.style_badge_size || 'fs-4');

      const title_color = escapeHtml(payload?.style_title_color || '#ffffff');
      const title_font = escapeHtml(payload?.style_title_font_family || 'Outfit');
      const title_size = escapeHtml(payload?.style_title_size || 'fs-1');

      const text_color = escapeHtml(payload?.style_text_color || '#f8f9fa');
      const text_font = escapeHtml(payload?.style_text_font_family || 'Inter');
      const text_size = escapeHtml(payload?.style_text_size || 'fs-3');

      const btn_color = escapeHtml(payload?.style_button_color || '#ffc107');
      const btn_text_color = escapeHtml(payload?.style_button_text_color || '#2c2420');
      const btn_font = escapeHtml(payload?.style_button_font_family || 'Outfit');
      const btn_size = escapeHtml(payload?.style_button_size || 'fs-3');

      const alignClass = text_align === 'left' ? 'text-start' : (text_align === 'right' ? 'text-end' : 'text-center');

      return `
        <header id="heroCarousel" class="carousel slide" data-bs-ride="carousel" 
                style="background-color: ${bg_color}; font-family: ${formatFont(text_font)};">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="${image_url}" class="d-block w-100 hero-img" alt="${title}" style="opacity: 0.85;">
              <div class="carousel-caption ${alignClass}" style="text-align: ${text_align}; left: 5%; right: 5%;">
                <span class="badge mb-2 px-3 py-2 text-uppercase fw-bold rounded-pill ${badge_size}" 
                      style="background-color: ${badge_color} !important; color: ${getContrastColor(badge_color)} !important; font-family: ${formatFont(badge_font)};">${badge}</span>
                <h1 class="fw-bold ${title_size}" style="color: ${title_color} !important; font-family: ${formatFont(title_font)} !important;">${title}</h1>
                <p class="lead ${text_size}" style="color: ${text_color} !important; font-family: ${formatFont(text_font)} !important;">${description}</p>
                <a class="btn px-4 fw-bold rounded-pill ${btn_size}" href="${button_link}" 
                   style="background-color: ${btn_color} !important; border-color: ${btn_color} !important; color: ${btn_text_color} !important; font-family: ${formatFont(btn_font)} !important;">${button_text}</a>
              </div>
            </div>
          </div>
        </header>
      `;
    },

    categories: function(payload) {
      const title = escapeHtml(getVal(payload, 'title'));

      // Style variables with safety fallbacks
      const bg_color = escapeHtml(payload?.style_background_color || '#f8f9fa');
      const text_align = escapeHtml(payload?.style_text_align || 'left');
      
      const title_color = escapeHtml(payload?.style_title_color || '#212529');
      const title_font = escapeHtml(payload?.style_title_font_family || 'Outfit');
      const title_size = escapeHtml(payload?.style_title_size || 'fs-2');

      const alignClass = text_align === 'left' ? 'text-start' : (text_align === 'right' ? 'text-end' : 'text-center');

      return `
        <section class="mb-5 p-4 rounded-4" style="background-color: ${bg_color};">
          <div class="section-title ${alignClass}" style="text-align: ${text_align};">
            <h2 class="${title_size} fw-bold" style="color: ${title_color} !important; font-family: ${formatFont(title_font)} !important;">${title}</h2>
          </div>
          <div class="scroll-row" id="chipsCategorias">
            <!-- Rendered via JS -->
          </div>
        </section>
      `;
    },

    stores: function(payload) {
      const title = escapeHtml(getVal(payload, 'title'));
      const subtitle = escapeHtml(getVal(payload, 'subtitle'));

      // Style variables with safety fallbacks
      const bg_color = escapeHtml(payload?.style_background_color || '#ffffff');
      const text_align = escapeHtml(payload?.style_text_align || 'left');
      
      const title_color = escapeHtml(payload?.style_title_color || '#212529');
      const title_font = escapeHtml(payload?.style_title_font_family || 'Outfit');
      const title_size = escapeHtml(payload?.style_title_size || 'fs-2');

      const text_color = escapeHtml(payload?.style_text_color || '#6c757d');
      const text_font = escapeHtml(payload?.style_text_font_family || 'Inter');
      const text_size = escapeHtml(payload?.style_text_size || 'fs-4');

      const alignClass = text_align === 'left' ? 'text-start' : (text_align === 'right' ? 'text-end' : 'text-center');

      return `
        <section id="tiendas" class="mb-5 p-4 rounded-4" style="background-color: ${bg_color};">
          <div class="section-title ${alignClass} mb-4" style="text-align: ${text_align};">
            <h2 class="${title_size} fw-bold mb-1" style="color: ${title_color} !important; font-family: ${formatFont(title_font)} !important;">${title}</h2>
            <p style="color: ${text_color} !important; font-family: ${formatFont(text_font)} !important;" class="mb-0 ${text_size}">${subtitle}</p>
          </div>
          <div class="scroll-row" id="storesContainer">
            <!-- Rendered via JS -->
          </div>
        </section>
      `;
    },

    offers: function(payload) {
      const title = escapeHtml(getVal(payload, 'title'));

      // Style variables with safety fallbacks
      const bg_color = escapeHtml(payload?.style_background_color || '#f8f9fa');
      const text_align = escapeHtml(payload?.style_text_align || 'left');
      
      const title_color = escapeHtml(payload?.style_title_color || '#212529');
      const title_font = escapeHtml(payload?.style_title_font_family || 'Outfit');
      const title_size = escapeHtml(payload?.style_title_size || 'fs-2');

      const alignClass = text_align === 'left' ? 'text-start' : (text_align === 'right' ? 'text-end' : 'text-center');

      return `
        <!-- Filters & Sort Area -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bg-white p-3 rounded-4 shadow-sm border" style="font-family: ${formatFont(title_font)};">
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted"><i class="bi bi-funnel-fill"></i> Filtrar:</span>
            <button class="btn btn-sm btn-outline-secondary rounded-pill active" id="btnFilterAll" onclick="filterByCategory('all')">Todos</button>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted"><i class="bi bi-sort-down"></i> Ordenar por:</span>
            <select id="sortSelect" class="form-select form-select-sm rounded-3 border-secondary-subtle" onchange="sortAndRender()" style="width: 180px;">
              <option value="default">Destacados</option>
              <option value="price-asc">Precio: Menor a Mayor</option>
              <option value="price-desc">Precio: Mayor a Menor</option>
              <option value="name-asc">Nombre: A - Z</option>
            </select>
          </div>
        </div>

        <section id="ofertas" class="mb-5 p-4 rounded-4" style="background-color: ${bg_color};">
          <div class="section-title ${alignClass}" style="text-align: ${text_align};">
            <h2 class="${title_size} fw-bold" style="color: ${title_color} !important; font-family: ${formatFont(title_font)} !important;">${title}</h2>
          </div>
          <div class="scroll-row" id="offersContainer">
            <!-- Rendered via JS -->
          </div>
        </section>
      `;
    },

    products: function(payload) {
      const title = escapeHtml(getVal(payload, 'title'));

      // Style variables with safety fallbacks
      const bg_color = escapeHtml(payload?.style_background_color || '#ffffff');
      const text_align = escapeHtml(payload?.style_text_align || 'left');
      
      const title_color = escapeHtml(payload?.style_title_color || '#212529');
      const title_font = escapeHtml(payload?.style_title_font_family || 'Outfit');
      const title_size = escapeHtml(payload?.style_title_size || 'fs-2');

      const alignClass = text_align === 'left' ? 'text-start' : (text_align === 'right' ? 'text-end' : 'text-center');

      return `
        <section id="categorias" class="mb-5 p-4 rounded-4" style="background-color: ${bg_color};">
          <div class="section-title ${alignClass}" style="text-align: ${text_align};">
            <h2 class="${title_size} fw-bold" style="color: ${title_color} !important; font-family: ${formatFont(title_font)} !important;">${title}</h2>
          </div>
          <div id="categoriesContainer">
            <!-- Rendered via JS -->
          </div>
        </section>
      `;
    },

    seller_cta: function(payload) {
      const title = escapeHtml(getVal(payload, 'title'));
      const badge = escapeHtml(getVal(payload, 'badge'));
      const description = escapeHtml(getVal(payload, 'description'));
      const button_text = escapeHtml(getVal(payload, 'button_text'));
      const image_url = sanitizeImgUrl(getVal(payload, 'image_url'));

      // Style variables with safety fallbacks
      const bg_color = escapeHtml(payload?.style_background_color || '#2c2420');
      const text_align = escapeHtml(payload?.style_text_align || 'center');

      const badge_color = escapeHtml(payload?.style_badge_color || '#ffc107');
      const badge_font = escapeHtml(payload?.style_badge_font_family || 'Outfit');
      const badge_size = escapeHtml(payload?.style_badge_size || 'fs-4');

      const title_color = escapeHtml(payload?.style_title_color || '#ffffff');
      const title_font = escapeHtml(payload?.style_title_font_family || 'Outfit');
      const title_size = escapeHtml(payload?.style_title_size || 'fs-2');

      const text_color = escapeHtml(payload?.style_text_color || '#f8f9fa');
      const text_font = escapeHtml(payload?.style_text_font_family || 'Inter');
      const text_size = escapeHtml(payload?.style_text_size || 'fs-3');

      const btn_color = escapeHtml(payload?.style_button_color || '#ffc107');
      const btn_text_color = escapeHtml(payload?.style_button_text_color || '#2c2420');
      const btn_font = escapeHtml(payload?.style_button_font_family || 'Outfit');
      const btn_size = escapeHtml(payload?.style_button_size || 'fs-3');

      const alignClass = text_align === 'left' ? 'text-start' : (text_align === 'right' ? 'text-end' : 'text-center');
      const overlayColor = hexToRgba(bg_color, 0.85);

      return `
        <section class="py-5 text-white ${alignClass} rounded-4 mb-5 shadow-lg position-relative overflow-hidden" id="sellerCtaBanner" 
                 style="display: none; background: linear-gradient(${overlayColor}, ${overlayColor}), url('${image_url}') center center / cover;">
          <div class="py-4 px-3 position-relative z-1">
            <span class="badge mb-2 px-3 py-2 text-uppercase fw-bold rounded-pill ${badge_size}"
                  style="background-color: ${badge_color} !important; color: ${getContrastColor(badge_color)} !important; font-family: ${formatFont(badge_font)};">${badge}</span>
            <h2 class="display-5 fw-bold font-title ${title_size}" style="color: ${title_color} !important; font-family: ${formatFont(title_font)} !important;">${title}</h2>
            <p class="lead mx-auto mb-4 ${text_size}" style="max-width: 600px; color: ${text_color} !important; font-family: ${formatFont(text_font)} !important;">${description}</p>
            <button class="btn btn-lg px-5 fw-bold rounded-pill shadow ${btn_size}" onclick="goToSellerApplication()"
                    style="background-color: ${btn_color} !important; border-color: ${btn_color} !important; color: ${btn_text_color} !important; font-family: ${formatFont(btn_font)} !important;">${button_text}</button>
          </div>
        </section>
      `;
    }
  };
})();
