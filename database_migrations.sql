CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin','vendedor','cliente') DEFAULT 'cliente',
  activo BOOLEAN DEFAULT TRUE,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tiendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  rubro VARCHAR(120),
  descripcion TEXT,
  imagen VARCHAR(255),
  whatsapp VARCHAR(30),
  instagram VARCHAR(100),
  destacado BOOLEAN DEFAULT FALSE,
  activa BOOLEAN DEFAULT TRUE,
  estado ENUM('activa','pendiente','suspendida') DEFAULT 'activa',
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  slug VARCHAR(120) UNIQUE NOT NULL,
  icono VARCHAR(80),
  activa BOOLEAN DEFAULT TRUE,
  orden INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tienda_id INT NOT NULL,
  categoria_id INT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT DEFAULT 0,
  imagen VARCHAR(255),
  oferta BOOLEAN DEFAULT FALSE,
  destacado BOOLEAN DEFAULT FALSE,
  activo BOOLEAN DEFAULT TRUE,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_nombre VARCHAR(120),
  cliente_email VARCHAR(120),
  cliente_telefono VARCHAR(40),
  tienda_id INT NOT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado ENUM('nuevo','confirmado','preparacion','entregado','cancelado') DEFAULT 'nuevo',
  observaciones TEXT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS activo BOOLEAN DEFAULT TRUE;
ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS rubro VARCHAR(120);
ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS imagen VARCHAR(255);
ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS destacado BOOLEAN DEFAULT FALSE;
ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS activa BOOLEAN DEFAULT TRUE;
ALTER TABLE categorias ADD COLUMN IF NOT EXISTS icono VARCHAR(80);
ALTER TABLE categorias ADD COLUMN IF NOT EXISTS activa BOOLEAN DEFAULT TRUE;
ALTER TABLE categorias ADD COLUMN IF NOT EXISTS orden INT DEFAULT 0;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS oferta BOOLEAN DEFAULT FALSE;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS destacado BOOLEAN DEFAULT FALSE;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS observaciones TEXT;

INSERT IGNORE INTO categorias (id,nombre,slug,icono,activa,orden) VALUES
(1,'Joyería','joyeria','bi-gem',1,1),
(2,'Madera','madera','bi-tree',1,2),
(3,'Textiles','textiles','bi-basket',1,3),
(4,'Cerámica','ceramica','bi-cup-hot',1,4),
(5,'Resina','resina','bi-stars',1,5);

CREATE TABLE IF NOT EXISTS solicitudes_vendedor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nombre_tienda VARCHAR(120) NOT NULL,
  rubro VARCHAR(120),
  descripcion TEXT,
  whatsapp VARCHAR(30),
  instagram VARCHAR(100),
  imagenes TEXT,
  estado ENUM('pendiente', 'aprobado', 'rechazado', 'correcion') DEFAULT 'pendiente',
  comentarios_admin TEXT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE solicitudes_vendedor ADD COLUMN IF NOT EXISTS imagenes TEXT;

CREATE TABLE IF NOT EXISTS homepage_components (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  file_path VARCHAR(255) NOT NULL,
  default_config JSON NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_layout (
  id INT AUTO_INCREMENT PRIMARY KEY,
  component_id INT NOT NULL,
  is_enabled BOOLEAN DEFAULT TRUE,
  order_index INT NOT NULL DEFAULT 0,
  config_payload JSON NOT NULL,
  FOREIGN KEY (component_id) REFERENCES homepage_components(id) ON DELETE CASCADE,
  INDEX idx_enabled_order (is_enabled, order_index),
  INDEX idx_component_id (component_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO homepage_components (id, name, file_path, default_config) VALUES
(1, 'hero', 'components/hero.html', '{"image_url": "https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1600&q=80", "badge": "100% Hecho a Mano", "title": "Diseño local, alma patagónica", "description": "Conecta directamente con artesanos y adquiere piezas únicas con historia.", "button_text": "Explorar tiendas", "button_link": "#tiendas"}'),
(2, 'categories', 'components/categories.html', '{"title": "Categorías"}'),
(3, 'stores', 'components/stores.html', '{"title": "Tiendas Destacadas", "subtitle": "Cada emprendedor tiene su esencia única"}'),
(4, 'offers', 'components/offers.html', '{"title": "Ofertas Especiales"}'),
(5, 'products', 'components/products.html', '{"title": "Nuestros Productos"}'),
(6, 'seller_cta', 'components/seller_cta.html', '{"image_url": "https://images.unsplash.com/photo-1459749411175-04bf5292ceea?auto=format&fit=crop&w=1200&q=80", "badge": "¿Eres Artesano?", "title": "Vende tus artesanías en Artesanías Sur", "description": "Crea tu tienda virtual hoy mismo, llega a más personas de la Patagonia y gestiona tus productos fácilmente.", "button_text": "Solicitar ser Vendedor"}');

CREATE TABLE IF NOT EXISTS campanas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  imagen VARCHAR(255) NULL,
  activa TINYINT(1) DEFAULT 1,
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campana_productos (
  campana_id INT NOT NULL,
  producto_id INT NOT NULL,
  PRIMARY KEY (campana_id, producto_id),
  FOREIGN KEY (campana_id) REFERENCES campanas(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO homepage_components (name, file_path, default_config) VALUES
('campaigns', 'components/campaigns.html', '{"title": "Campañas Especiales", "subtitle": "Eventos y promociones de temporada"}');


INSERT IGNORE INTO homepage_layout (id, component_id, is_enabled, order_index, config_payload) VALUES
(1, 1, 1, 1, '{"image_url": "https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1600&q=80", "badge": "100% Hecho a Mano", "title": "Diseño local, alma patagónica", "description": "Conecta directamente con artesanos y adquiere piezas únicas con historia.", "button_text": "Explorar tiendas", "button_link": "#tiendas"}'),
(2, 2, 1, 2, '{"title": "Categorías"}'),
(3, 3, 1, 3, '{"title": "Tiendas Destacadas", "subtitle": "Cada emprendedor tiene su esencia única"}'),
(4, 4, 1, 4, '{"title": "Ofertas Especiales"}'),
(5, 5, 1, 5, '{"title": "Nuestros Productos"}'),
(6, 6, 1, 6, '{"image_url": "https://images.unsplash.com/photo-1459749411175-04bf5292ceea?auto=format&fit=crop&w=1200&q=80", "badge": "¿Eres Artesano?", "title": "Vende tus artesanías en Artesanías Sur", "description": "Crea tu tienda virtual hoy mismo, llega a más personas de la Patagonia y gestiona tus productos fácilmente.", "button_text": "Solicitar ser Vendedor"}');

CREATE TABLE IF NOT EXISTS configuraciones_globales (
  clave VARCHAR(50) PRIMARY KEY,
  valor TEXT NOT NULL,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuraciones_globales (clave, valor) VALUES 
('active_theme_id', 'tierra_artesanal'),
('custom_themes', '{}'),
('public_navbar_config', '{"logo_url":"","brand_name":"Artesanías Sur","link_inicio":"Inicio","link_tiendas":"Tiendas","link_ofertas":"Ofertas","link_productos":"Productos","font_family":"Outfit","font_size":"0.95rem"}');
