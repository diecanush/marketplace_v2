/**
 * Homepage Admin Content Editor - Artesanías Sur V2
 * Dynamically handles modal form generation, live preview, base64 file uploads, and WCAG contrast check.
 */

(function() {
  let activeEditItem = null;
  let tempPayload = {};

  // Dictionaries for Friendly Labels and Help Texts
  const friendlyLabels = {
    title: 'Título principal',
    subtitle: 'Subtítulo',
    description: 'Descripción / Resumen',
    badge: 'Etiqueta destacada (Badge)',
    button_text: 'Texto del botón',
    button_link: 'Enlace del botón',
    image_url: 'Imagen de portada',
    style_background_color: 'Color de fondo de la sección',
    style_text_align: 'Alineación de contenidos',
    // Insignia (Badge)
    style_badge_color: 'Color de la insignia (Badge)',
    style_badge_font_family: 'Tipografía de la insignia',
    style_badge_size: 'Tamaño de la insignia',
    // Título
    style_title_color: 'Color del título principal',
    style_title_font_family: 'Tipografía del título',
    style_title_size: 'Tamaño del título',
    // Texto / Descripción
    style_text_color: 'Color del texto descriptivo',
    style_text_font_family: 'Tipografía de la descripción',
    style_text_size: 'Tamaño de la descripción',
    // Botón
    style_button_color: 'Color del botón principal',
    style_button_text_color: 'Color del texto del botón',
    style_button_font_family: 'Tipografía del botón',
    style_button_size: 'Tamaño del botón'
  };

  const helpTexts = {
    image_url: 'Sube un archivo de imagen local. Se guardará de forma segura en el servidor.',
    button_link: 'Destino del botón. Puedes usar un anclaje interno (ej. "#tiendas", "#ofertas") o una URL externa ("https://...")',
    style_background_color: 'Color de fondo general que tendrá toda la sección del componente.',
    style_text_align: 'Establece la alineación horizontal para todos los textos de esta sección.',
    style_button_color: 'Color de fondo para el botón principal de acción.',
    style_button_text_color: 'Color del texto sobre el botón. Elija uno que garantice una buena lectura sobre el fondo del botón.'
  };

  // HTML escaping helper
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

  // Sanitizes and resolves image URLs (depth = 2 for admin folder)
  function sanitizeImgUrl(url) {
    if (!url) {
      return 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80';
    }
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
      return url;
    }
    if (typeof getImgUrl === 'function') {
      return getImgUrl(url, 2);
    }
    return url;
  }

  // WCAG 2.0 Luminance & Contrast Ratio calculation helpers
  function getLuminance(hex) {
    if (!hex || hex.length < 7) return 0;
    let r = parseInt(hex.substring(1, 3), 16) / 255;
    let g = parseInt(hex.substring(3, 5), 16) / 255;
    let b = parseInt(hex.substring(5, 7), 16) / 255;
    let a = [r, g, b].map(v => {
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return a[0] * 0.2126 + a[1] * 0.7152 + a[2] * 0.0722;
  }

  function getContrastRatio(hexBg, hexText) {
    let l1 = getLuminance(hexBg);
    let l2 = getLuminance(hexText);
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
  }

  // Audits contrast and displays dynamic WCAG warning banners
  function auditContrast() {
    const msgContainer = document.getElementById('msg-contrast');
    if (!msgContainer) return;

    msgContainer.innerHTML = ''; // Clear previous warnings

    const bg = tempPayload.style_background_color;
    const titleCol = tempPayload.style_title_color;
    const textCol = tempPayload.style_text_color;
    const btnBg = tempPayload.style_button_color;
    const btnText = tempPayload.style_button_text_color;

    let warnings = [];

    if (bg) {
      if (titleCol) {
        const ratioTitle = getContrastRatio(bg, titleCol);
        if (ratioTitle < 4.5) {
          warnings.push(`<strong>Título principal</strong> contra fondo (contraste: ${ratioTitle.toFixed(2)}:1, recomendado mínimo: 4.5:1)`);
        }
      }
      if (textCol) {
        const ratioText = getContrastRatio(bg, textCol);
        if (ratioText < 4.5) {
          warnings.push(`<strong>Texto descriptivo</strong> contra fondo (contraste: ${ratioText.toFixed(2)}:1, recomendado mínimo: 4.5:1)`);
        }
      }
    }
    if (btnBg && btnText) {
      const ratioBtn = getContrastRatio(btnBg, btnText);
      if (ratioBtn < 4.5) {
        warnings.push(`<strong>Texto del botón</strong> contra fondo del botón (contraste: ${ratioBtn.toFixed(2)}:1, recomendado mínimo: 4.5:1)`);
      }
    }

    if (warnings.length > 0) {
      msgContainer.innerHTML = `
        <div class="alert alert-warning border-0 shadow-sm p-3 mb-0 rounded-3 small">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
            <div>
              <span class="fw-bold d-block mb-1">Bajo Contraste Detectado (WCAG)</span>
              La combinación de colores elegida dificulta la lectura para personas con discapacidad visual en:
              <ul class="mb-0 mt-1 ps-3">${warnings.map(w => `<li>${w}</li>`).join('')}</ul>
              <span class="text-muted d-block mt-1.5 small">Sugerencia: Cambie a colores más contrastados para mejorar la lectura.</span>
            </div>
          </div>
        </div>
      `;
    }
  }

  // Updates visual layout compiled preview in real-time
  window.updatePreview = function() {
    const previewContainer = document.getElementById('component-preview-container');
    if (!previewContainer || !activeEditItem) return;

    const renderer = window.homepageTemplates[activeEditItem.name];
    if (typeof renderer !== 'function') {
      previewContainer.innerHTML = '<div class="text-muted p-4">Previsualización no disponible para este componente.</div>';
      return;
    }

    try {
      let html = renderer(tempPayload);
      
      // Mimic public index.html container wrapping
      if (activeEditItem.name !== 'hero') {
        html = `<div class="container py-3" style="width: 100%;">${html}</div>`;
      } else {
        html = `<div style="width: 100%;">${html}</div>`;
      }

      previewContainer.innerHTML = `
        <div class="preview-wrapper w-100" style="background-color: #f8f9fa; font-family: 'Inter', sans-serif;">
          ${html}
        </div>
      `;

      // Hydrate custom active displays (like seller application button clicks)
      const sellerBanner = document.getElementById('sellerCtaBanner');
      if (sellerBanner) {
        sellerBanner.style.display = 'block'; // Make visible inside preview modal
      }
    } catch (e) {
      previewContainer.innerHTML = `<div class="alert alert-danger m-3 small">Error al compilar plantilla: ${e.message}</div>`;
    }
  };

  // Bind values back to payload and re-render preview/contrast
  window.updatePayloadValue = function(key, val, textId) {
    tempPayload[key] = val;
    if (textId) {
      const textEl = document.getElementById(textId);
      if (textEl) textEl.textContent = val;
    }
    window.updatePreview();
    auditContrast();
  };

  // Convert files to Base64 using FileReader
  window.handleImageChange = function(inputEl, previewId, key) {
    const file = inputEl.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
      const base64Data = e.target.result;
      document.getElementById(previewId).src = base64Data; // Immediate visual preview
      tempPayload[key] = base64Data; // Persist base64 data for PUT upload
      window.updatePreview();
    };
    reader.readAsDataURL(file);
  };

  // Helper form rendering builders
  function renderColorField(key, label, value, helpText) {
    return `
      <div class="mb-3">
        <label class="form-label fw-bold text-secondary mb-1" style="font-size: 0.9rem;">${label}</label>
        <div class="d-flex align-items-center gap-2 bg-white p-2 border rounded-3 shadow-sm">
          <input type="color" class="form-control form-control-color p-0 border-0" style="width: 42px; height: 32px; border-radius: 6px; cursor: pointer;" value="${value}" oninput="updatePayloadValue('${key}', this.value, 'color-val-${key}')">
          <code class="text-dark fw-bold" id="color-val-${key}">${value}</code>
        </div>
        ${helpText}
      </div>
    `;
  }

  function renderSelectField(key, label, value, options, helpText) {
    const optionStr = options.map(opt => `<option value="${opt.value}" ${value === opt.value ? 'selected' : ''}>${opt.label}</option>`).join('');
    return `
      <div class="mb-3">
        <label class="form-label fw-bold text-secondary mb-1" style="font-size: 0.9rem;">${label}</label>
        <select class="form-select rounded-3 shadow-sm" onchange="updatePayloadValue('${key}', this.value)" style="font-size: 0.9rem;">
          ${optionStr}
        </select>
        ${helpText}
      </div>
    `;
  }

  // Generates HTML forms inside the left modal column
  window.renderFormFields = function() {
    const formFieldsContainer = document.getElementById('dynamicFormFields');
    if (!formFieldsContainer) return;

    let contentHtml = '';
    let stylesHtml = '';
    const keys = Object.keys(tempPayload);
    
    if (keys.length === 0) {
      contentHtml = '<p class="text-muted">Este componente no requiere configuración.</p>';
    } else {
      // Setup options lists
      const alignOptions = [
        { value: 'left', label: 'Izquierda' },
        { value: 'center', label: 'Centrado' },
        { value: 'right', label: 'Derecha' }
      ];

      const sizeOptions = [
        { value: 'fs-1', label: 'Muy grande' },
        { value: 'fs-2', label: 'Grande' },
        { value: 'fs-3', label: 'Mediano' },
        { value: 'fs-4', label: 'Pequeño' }
      ];

      const fonts = ['Outfit', 'Montserrat', 'Poppins', 'Lora', 'Merriweather', 'Playfair Display', 'Roboto', 'Raleway', 'Nunito', 'Quicksand'];
      const fontOptions = [
        { value: 'inherit', label: 'Por defecto (inherit)' },
        ...fonts.map(f => ({ value: f, label: f }))
      ];

      // Formulate styling groups
      let groupSectionHtml = '';
      let groupBadgeHtml = '';
      let groupTitleHtml = '';
      let groupTextHtml = '';
      let groupButtonHtml = '';

      keys.forEach(key => {
        const val = tempPayload[key];
        const label = friendlyLabels[key] || key.replace(/_/g, ' ').toUpperCase();
        const helpText = helpTexts[key] ? `<div class="form-text text-muted mb-1 small"><i class="bi bi-info-circle"></i> ${helpTexts[key]}</div>` : '';
        
        if (key.startsWith('style_')) {
          // Process styling keys into respective groups
          if (key === 'style_background_color') {
            groupSectionHtml += renderColorField(key, label, val, helpText);
          } else if (key === 'style_text_align') {
            groupSectionHtml += renderSelectField(key, label, val, alignOptions, helpText);
          } else if (key.startsWith('style_badge_')) {
            if (key.endsWith('_color')) {
              groupBadgeHtml += renderColorField(key, label, val, helpText);
            } else if (key.endsWith('_font_family')) {
              groupBadgeHtml += renderSelectField(key, label, val, fontOptions, helpText);
            } else if (key.endsWith('_size')) {
              groupBadgeHtml += renderSelectField(key, label, val, sizeOptions, helpText);
            }
          } else if (key.startsWith('style_title_')) {
            if (key.endsWith('_color')) {
              groupTitleHtml += renderColorField(key, label, val, helpText);
            } else if (key.endsWith('_font_family')) {
              groupTitleHtml += renderSelectField(key, label, val, fontOptions, helpText);
            } else if (key.endsWith('_size')) {
              groupTitleHtml += renderSelectField(key, label, val, sizeOptions, helpText);
            }
          } else if (key.startsWith('style_text_')) {
            if (key.endsWith('_color')) {
              groupTextHtml += renderColorField(key, label, val, helpText);
            } else if (key.endsWith('_font_family')) {
              groupTextHtml += renderSelectField(key, label, val, fontOptions, helpText);
            } else if (key.endsWith('_size')) {
              groupTextHtml += renderSelectField(key, label, val, sizeOptions, helpText);
            }
          } else if (key.startsWith('style_button_')) {
            if (key.endsWith('_color') || key.endsWith('_text_color')) {
              groupButtonHtml += renderColorField(key, label, val, helpText);
            } else if (key.endsWith('_font_family')) {
              groupButtonHtml += renderSelectField(key, label, val, fontOptions, helpText);
            } else if (key.endsWith('_size')) {
              groupButtonHtml += renderSelectField(key, label, val, sizeOptions, helpText);
            }
          }
        } else {
          // Render in Content Tab
          // 1. Image keys
          if (key.includes('image_url') || key.includes('imagen')) {
            contentHtml += `
              <div class="mb-3">
                <label class="form-label fw-bold text-secondary mb-1">${label}</label>
                <div class="d-flex align-items-start gap-3 border p-2.5 rounded-3 bg-white">
                  <img id="preview-${key}" src="${sanitizeImgUrl(val)}" class="img-thumbnail rounded-3 shadow-sm" style="width: 110px; height: 110px; object-fit: cover;">
                  <div class="flex-grow-1">
                    <input type="file" class="form-control form-control-sm rounded-3 shadow-sm" accept="image/*" onchange="handleImageChange(this, 'preview-${key}', '${key}')">
                    <small class="text-muted mt-1.5 d-block"><i class="bi bi-info-circle"></i> Sube un archivo de imagen.</small>
                  </div>
                </div>
                ${helpText}
              </div>
            `;
          }
          // 2. Textarea keys (descriptions, subtitles)
          else if (key.includes('description') || key.includes('desc') || key.includes('subtitle') || key.includes('subtitulo')) {
            contentHtml += `
              <div class="mb-3">
                <label class="form-label fw-bold text-secondary mb-1">${label}</label>
                <textarea class="form-control rounded-3 shadow-sm" rows="4" placeholder="Ingresa el texto..." oninput="updatePayloadValue('${key}', this.value)">${escapeHtml(val)}</textarea>
                ${helpText}
              </div>
            `;
          }
          // 3. Link or URL keys
          else if (key.includes('link') || key.includes('url') || key.includes('href')) {
            contentHtml += `
              <div class="mb-3">
                <label class="form-label fw-bold text-secondary mb-1">${label}</label>
                <input type="url" class="form-control rounded-3 shadow-sm" value="${escapeHtml(val)}" placeholder="https://..." oninput="updatePayloadValue('${key}', this.value)">
                ${helpText}
              </div>
            `;
          }
          // 4. Regular Text keys
          else {
            contentHtml += `
              <div class="mb-3">
                <label class="form-label fw-bold text-secondary mb-1">${label}</label>
                <input type="text" class="form-control rounded-3 shadow-sm" value="${escapeHtml(val)}" placeholder="Texto..." oninput="updatePayloadValue('${key}', this.value)">
                ${helpText}
              </div>
            `;
          }
        }
      });

      // Build grouped styles sections
      if (groupSectionHtml) {
        stylesHtml += `
          <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-secondary text-white py-2 fw-bold small"><i class="bi bi-aspect-ratio"></i> Sección y Fondo</div>
            <div class="card-body bg-light py-2">${groupSectionHtml}</div>
          </div>
        `;
      }
      if (groupBadgeHtml) {
        stylesHtml += `
          <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-dark text-white py-2 fw-bold small"><i class="bi bi-tag-fill"></i> Estilo de la Insignia</div>
            <div class="card-body bg-light py-2">${groupBadgeHtml}</div>
          </div>
        `;
      }
      if (groupTitleHtml) {
        stylesHtml += `
          <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white py-2 fw-bold small"><i class="bi bi-type-h1"></i> Estilo del Título</div>
            <div class="card-body bg-light py-2">${groupTitleHtml}</div>
          </div>
        `;
      }
      if (groupTextHtml) {
        stylesHtml += `
          <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-info text-white py-2 fw-bold small"><i class="bi bi-justify-left"></i> Estilo de la Descripción</div>
            <div class="card-body bg-light py-2">${groupTextHtml}</div>
          </div>
        `;
      }
      if (groupButtonHtml) {
        stylesHtml += `
          <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-warning text-dark py-2 fw-bold small"><i class="bi bi-hand-index-thumb"></i> Estilo del Botón</div>
            <div class="card-body bg-light py-2">${groupButtonHtml}</div>
          </div>
        `;
      }
    }

    if (stylesHtml) {
      formFieldsContainer.innerHTML = `
        <ul class="nav nav-tabs mb-4" id="editModalTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-secondary" id="content-tab" data-bs-toggle="tab" data-bs-target="#content-panel" type="button" role="tab" aria-controls="content-panel" aria-selected="true">
              <i class="bi bi-file-text me-1"></i> Contenido
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-secondary" id="styles-tab" data-bs-toggle="tab" data-bs-target="#styles-panel" type="button" role="tab" aria-controls="styles-panel" aria-selected="false">
              <i class="bi bi-palette me-1"></i> Estilos
            </button>
          </li>
        </ul>
        <div class="tab-content" id="editModalTabsContent">
          <div class="tab-pane fade show active" id="content-panel" role="tabpanel" aria-labelledby="content-tab">
            ${contentHtml}
          </div>
          <div class="tab-pane fade" id="styles-panel" role="tabpanel" aria-labelledby="styles-tab">
            ${stylesHtml}
          </div>
        </div>
      `;
    } else {
      formFieldsContainer.innerHTML = contentHtml;
    }
  };

  // Opens modal and initializes copy
  window.openEditModal = function(id) {
    if (!window.adminLayoutData) return;
    
    const item = window.adminLayoutData.find(x => x.id === id);
    if (!item) return;

    activeEditItem = item;
    tempPayload = JSON.parse(JSON.stringify(item.config_payload || {}));

    const modalTitle = document.getElementById('editModalLabel');
    if (modalTitle) {
      modalTitle.textContent = `Editar Componente: ${item.name.toUpperCase().replace('_', ' ')}`;
    }

    // Render form fields and preview
    window.renderFormFields();
    window.updatePreview();
    auditContrast();

    // Show bootstrap modal
    const modalEl = document.getElementById('editModal');
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();
  };

  // Restores component payload in memory to default_config values
  window.restoreDefaultConfig = function() {
    if (!activeEditItem || !window.adminComponentsData) return;

    const comp = window.adminComponentsData.find(x => x.name === activeEditItem.name);
    if (!comp) {
      alert('Error: No se encontró la plantilla de fábrica para este componente.');
      return;
    }

    if (confirm(`¿Estás seguro de que deseas restablecer los valores por defecto de la sección "${activeEditItem.name.toUpperCase().replace('_', ' ')}"?\n(Nota: Los cambios no se guardarán en el servidor hasta que presiones "Guardar Cambios")`)) {
      tempPayload = JSON.parse(JSON.stringify(comp.default_config || {}));
      
      // Re-render modal controls and refresh live preview
      window.renderFormFields();
      window.updatePreview();
      auditContrast();
    }
  };

  // Submit edits via PUT request
  window.saveComponentContent = async function() {
    if (!activeEditItem) return;

    const btn = document.getElementById('btnSaveContent');
    const restoreBtn = document.getElementById('btnRestoreDefault');
    btn.disabled = true;
    if (restoreBtn) restoreBtn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

    // Verify key integrity
    const originalKeys = Object.keys(activeEditItem.config_payload).sort();
    const currentKeys = Object.keys(tempPayload).sort();
    
    if (JSON.stringify(originalKeys) !== JSON.stringify(currentKeys)) {
      alert('Error: La estructura de llaves del payload ha sido alterada y no coincide con la firma del componente.');
      btn.disabled = false;
      if (restoreBtn) restoreBtn.disabled = false;
      btn.innerHTML = 'Guardar Cambios';
      return;
    }

    try {
      let res = await API.put(`/homepage.php?id=${activeEditItem.id}`, {
        config_payload: tempPayload
      });

      if (res.success) {
        if (typeof window.showToast === 'function') {
          window.showToast('Contenido del componente actualizado con éxito.', 'success');
        } else {
          alert('Contenido del componente actualizado con éxito.');
        }

        // Close modal
        const modalEl = document.getElementById('editModal');
        const bsModal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.hide();

        // Reload data grid
        if (typeof window.loadLayout === 'function') {
          await window.loadLayout();
        }
      } else {
        alert('Error al guardar cambios: ' + res.message);
      }
    } catch (err) {
      alert('Error de conexión al guardar el contenido.');
    } finally {
      btn.disabled = false;
      if (restoreBtn) restoreBtn.disabled = false;
      btn.innerHTML = 'Guardar Cambios';
    }
  };
})();
