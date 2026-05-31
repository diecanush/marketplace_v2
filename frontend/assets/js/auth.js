const Auth = {
  save(d) {
    localStorage.setItem('token', d.token);
    localStorage.setItem('user', JSON.stringify(d.user));
  },
  
  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    // Determinar la ruta relativa correcta al login.html
    if (location.pathname.includes('/panel/') || location.pathname.includes('/admin/')) {
      location.href = '../login.html';
    } else {
      location.href = 'login.html';
    }
  },
  
  require() {
    if (!API.token()) {
      if (location.pathname.includes('/panel/') || location.pathname.includes('/admin/')) {
        location.href = '../login.html';
      } else {
        location.href = 'login.html';
      }
    }
  },
  
  role(rs) {
    this.require();
    let u = API.user();
    if (!u || !rs.includes(u.rol)) {
      alert('No autorizado');
      if (location.pathname.includes('/panel/') || location.pathname.includes('/admin/')) {
        location.href = '../index.html';
      } else {
        location.href = 'index.html';
      }
    }
  },
  
  paint() {
    let u = API.user();
    let e = document.getElementById('userBox');
    if (u && e) {
      e.textContent = `${u.nombre} (${u.rol})`;
    }

    // Renderizado centralizado del sidebar para vistas de admin
    let nav = document.querySelector('aside.side nav.nav');
    if (nav && u && u.rol === 'admin' && location.pathname.includes('/admin/')) {
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
        <a href="../index.html"><i class="bi bi-globe"></i> Ver Sitio</a>
        <a href="#" onclick="event.preventDefault(); Auth.logout()"><i class="bi bi-box-arrow-right text-danger"></i> Salir</a>
      `;
    }

    // Renderizado centralizado del sidebar para vistas de panel (vendedor / cliente)
    if (nav && u && (location.pathname.includes('/panel/') || location.pathname.endsWith('/panel'))) {
      let path = location.pathname;
      let isDashboard = path.includes('/dashboard.html') || path.endsWith('/panel') || path.endsWith('/panel/');
      let isTienda = path.includes('/tienda.html');
      let isProductos = path.includes('/productos.html');
      let isVentas = path.includes('/ventas.html');

      const isSeller = u.rol === 'vendedor' || u.rol === 'admin';
      const displayStyle = isSeller ? '' : 'style="display: none !important;"';

      nav.innerHTML = `
        <a href="dashboard.html" class="${isDashboard ? 'active' : ''}"><i class="bi bi-speedometer2"></i> Inicio</a>
        <a href="tienda.html" id="navShop" class="${isTienda ? 'active' : ''}" ${displayStyle}><i class="bi bi-palette"></i> Diseño de tienda</a>
        <a href="productos.html" id="navProducts" class="${isProductos ? 'active' : ''}" ${displayStyle}><i class="bi bi-grid"></i> Productos</a>
        <a href="ventas.html" id="navSales" class="${isVentas ? 'active' : ''}" ${displayStyle}><i class="bi bi-currency-dollar"></i> Ventas</a>
        <hr class="text-white-50">
        <a href="../index.html"><i class="bi bi-globe"></i> Ver Sitio</a>
        <a href="#" onclick="event.preventDefault(); Auth.logout()"><i class="bi bi-box-arrow-right text-danger"></i> Salir</a>
      `;
    }
  }
};
