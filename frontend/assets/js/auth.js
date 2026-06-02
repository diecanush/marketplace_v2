const Auth = {
  save(d) {
    localStorage.setItem('token', d.token);
    localStorage.setItem('user', JSON.stringify(d.user));
  },
  
  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    location.href = window.AppConfig ? window.AppConfig.LOGIN_URL : 'login.html';
  },
  
  require() {
    if (!API.token()) {
      location.href = window.AppConfig ? window.AppConfig.LOGIN_URL : 'login.html';
    }
  },
  
  role(rs) {
    this.require();
    let u = API.user();
    if (!u || !rs.includes(u.rol)) {
      alert('No autorizado');
      location.href = window.AppConfig ? window.AppConfig.INDEX_URL : 'index.html';
    }
  },
  
  paint() {
    let u = API.user();
    let e = document.getElementById('userBox');
    if (u && e) {
      e.textContent = `${u.nombre} (${u.rol})`;
    }

    const config = window.AppConfig || {};
    const currentUrl = window.location.href;

    // Renderizado centralizado del sidebar para vistas de admin
    let nav = document.querySelector('aside.side nav.nav');
    if (nav && u && u.rol === 'admin' && config.ADMIN_BASE_URL && currentUrl.includes(config.ADMIN_BASE_URL)) {
      let path = location.pathname;
      let isDashboard = path.includes('/dashboard.html');
      let isUsuarios = path.includes('/usuarios.html');
      let isTiendas = path.includes('/tiendas.html');
      let isCategorias = path.includes('/categorias.html');
      let isVentas = path.includes('/ventas.html');
      let isSolicitudes = path.includes('/solicitudes.html');
      let isLayout = path.includes('/layout.html');
      let isCampanas = path.includes('/campanas.html');

      nav.innerHTML = `
        <a href="dashboard.html" class="${isDashboard ? 'active' : ''}"><i class="bi bi-speedometer2"></i> Inicio</a>
        <a href="usuarios.html" class="${isUsuarios ? 'active' : ''}"><i class="bi bi-people"></i> Usuarios</a>
        <a href="tiendas.html" class="${isTiendas ? 'active' : ''}"><i class="bi bi-shop"></i> Tiendas</a>
        <a href="categorias.html" class="${isCategorias ? 'active' : ''}"><i class="bi bi-tags"></i> Categorías</a>
        <a href="ventas.html" class="${isVentas ? 'active' : ''}"><i class="bi bi-receipt"></i> Ventas</a>
        <a href="solicitudes.html" class="${isSolicitudes ? 'active' : ''}"><i class="bi bi-send-check"></i> Solicitudes <span id="navPendingBadge" class="badge text-bg-warning rounded-pill" style="display:none;">0</span></a>
        <a href="layout.html" class="${isLayout ? 'active' : ''}"><i class="bi bi-grid-1x2"></i> Diseño Home</a>
        <a href="campanas.html" class="${isCampanas ? 'active' : ''}"><i class="bi bi-megaphone"></i> Campañas</a>
        <hr class="text-white-50">
        <a href="${config.INDEX_URL}"><i class="bi bi-globe"></i> Ver Sitio</a>
        <a href="#" onclick="event.preventDefault(); Auth.logout()"><i class="bi bi-box-arrow-right text-danger"></i> Salir</a>
      `;
    }

    // Renderizado centralizado del sidebar para vistas de panel (vendedor / cliente)
    if (nav && u && config.PANEL_BASE_URL && (currentUrl.includes(config.PANEL_BASE_URL) || currentUrl.endsWith('/panel') || currentUrl.endsWith('/panel/'))) {
      let path = location.pathname;
      let isDashboard = path.includes('/dashboard.html') || path.endsWith('/panel') || path.endsWith('/panel/');
      let isTienda = path.includes('/tienda.html');
      let isProductos = path.includes('/productos.html');
      let isVentas = path.includes('/ventas.html');
      let isCampanas = path.includes('/campanas.html');

      const isSeller = u.rol === 'vendedor' || u.rol === 'admin';
      const displayStyle = isSeller ? '' : 'style="display: none !important;"';

      nav.innerHTML = `
        <a href="dashboard.html" class="${isDashboard ? 'active' : ''}"><i class="bi bi-speedometer2"></i> Inicio</a>
        <a href="tienda.html" id="navShop" class="${isTienda ? 'active' : ''}" ${displayStyle}><i class="bi bi-palette"></i> Diseño de tienda</a>
        <a href="productos.html" id="navProducts" class="${isProductos ? 'active' : ''}" ${displayStyle}><i class="bi bi-grid"></i> Productos</a>
        <a href="campanas.html" id="navCampanas" class="${isCampanas ? 'active' : ''}" ${displayStyle}><i class="bi bi-megaphone"></i> Campañas</a>
        <a href="ventas.html" id="navSales" class="${isVentas ? 'active' : ''}" ${displayStyle}><i class="bi bi-currency-dollar"></i> Ventas</a>
        <hr class="text-white-50">
        <a href="${config.INDEX_URL}"><i class="bi bi-globe"></i> Ver Sitio</a>
        <a href="#" onclick="event.preventDefault(); Auth.logout()"><i class="bi bi-box-arrow-right text-danger"></i> Salir</a>
      `;
    }
  }
};
