const Cart = {
  items() {
    return JSON.parse(localStorage.getItem('cart') || '[]');
  },
  
  save(x) {
    localStorage.setItem('cart', JSON.stringify(x));
    this.paint();
  },
  
  add(p) {
    let x = this.items();
    let f = x.find(i => +i.id === +p.id);
    if (f) {
      f.cantidad++;
    } else {
      x.push({ ...p, cantidad: 1 });
    }
    this.save(x);
  },
  
  increase(id) {
    let x = this.items();
    let f = x.find(i => +i.id === +id);
    if (f) {
      f.cantidad++;
    }
    this.save(x);
  },
  
  decrease(id) {
    let x = this.items();
    let f = x.find(i => +i.id === +id);
    if (f) {
      f.cantidad--;
      if (f.cantidad <= 0) {
        x = x.filter(i => +i.id !== +id);
      }
    }
    this.save(x);
  },
  
  remove(i) {
    let x = this.items();
    x.splice(i, 1);
    this.save(x);
  },
  
  clear() {
    this.save([]);
  },
  
  paint() {
    let x = this.items();
    
    // Update badge count
    let c = document.getElementById('cartCount');
    if (c) {
      c.textContent = x.reduce((sum, item) => sum + Number(item.cantidad || 1), 0);
    }
    
    let b = document.getElementById('cartItems');
    if (!b) return;
    
    let emptyCart = document.getElementById('emptyCart');
    let checkoutForm = document.getElementById('checkoutFormContainer');
    let btnCheckout = document.getElementById('btnCheckout');
    let totalSumContainer = document.getElementById('cartTotalSum');
    
    if (x.length === 0) {
      if (emptyCart) emptyCart.style.display = 'block';
      if (checkoutForm) checkoutForm.style.display = 'none';
      if (btnCheckout) btnCheckout.disabled = true;
      b.innerHTML = '';
      if (totalSumContainer) totalSumContainer.textContent = money(0);
      return;
    }
    
    if (emptyCart) emptyCart.style.display = 'none';
    if (checkoutForm) checkoutForm.style.display = 'block';
    if (btnCheckout) btnCheckout.disabled = false;
    
    let total = 0;
    
    b.innerHTML = x.map((p, idx) => {
      let subtotal = Number(p.precio) * p.cantidad;
      total += subtotal;
      
      let imgUrl = getImgUrl(p.imagen, 1);
      
      return `
        <div class="cart-item">
          <div class="row g-2 align-items-center">
            <div class="col-3">
              <img src="${imgUrl}" class="img-fluid rounded-3" style="height: 60px; width: 100%; object-fit: cover;">
            </div>
            <div class="col-9">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="fw-bold mb-0 text-truncate" style="max-width: 180px;">${p.nombre}</h6>
                  <span class="text-muted small" style="font-size:0.75rem;"><i class="bi bi-shop"></i> ${p.tienda || 'Artesano'}</span>
                </div>
                <button class="btn btn-sm text-danger p-0 border-0" onclick="Cart.remove(${idx})"><i class="bi bi-trash"></i></button>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="price small fs-6">${money(p.precio)}</span>
                <div class="cart-quantity-control">
                  <button class="cart-quantity-btn" onclick="Cart.decrease(${p.id})">-</button>
                  <span class="px-2 fw-bold text-dark small" style="min-width:15px; text-align:center;">${p.cantidad}</span>
                  <button class="cart-quantity-btn" onclick="Cart.increase(${p.id})">+</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');
    
    if (totalSumContainer) {
      totalSumContainer.textContent = money(total);
    }
  }
};

document.addEventListener('DOMContentLoaded', () => Cart.paint());
