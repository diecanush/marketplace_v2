# Marketplace Artesanías Sur V2

Incluye:

- Frontend público parecido al mockup: tiendas, categorías, ofertas, productos por categoría y carrito.
- Registro y login.
- Primer usuario registrado como admin.
- Panel vendedor: mi tienda, productos y ventas.
- Panel admin: usuarios/roles, categorías, tiendas y ventas.
- API REST en PHP + PDO + MariaDB/MySQL.

## Instalación en XAMPP

1. Ejecutar este archivo:

```bash
python generar_marketplace_v2.py
```

2. Copiar la carpeta generada `marketplace_v2` a:

```txt
C:\xampp\htdocs\marketplace_v2
```

3. Entrar a phpMyAdmin, seleccionar tu base ya creada y ejecutar:

```txt
database_migrations.sql
```

4. Crear el archivo de credenciales desde la plantilla:

```txt
api/config/db.example.php -> api/config/db.php
```

5. Revisar credenciales en:

```txt
api/config/db.php
```

6. Abrir:

```txt
http://localhost/marketplace_v2/frontend/index.html
```

## Importante

Si cambiás el nombre de carpeta, modificá `API_BASE` en:

```txt
frontend/assets/js/api.js
```
