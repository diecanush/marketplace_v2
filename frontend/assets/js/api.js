const API_BASE = window.AppConfig ? window.AppConfig.API_BASE_URL : '';

const API = { token:()=>localStorage.getItem('token'), user:()=>{try{return JSON.parse(localStorage.getItem('user'))}catch{return null}}, headers:(a=false)=>{let h={'Content-Type':'application/json'}; if(a)h.Authorization='Bearer '+localStorage.getItem('token'); return h;}, async req(p,o={}){let r=await fetch(API_BASE+p,o); if(r.status===401){localStorage.removeItem('token'); localStorage.removeItem('user'); window.location.href = window.AppConfig ? window.AppConfig.LOGIN_URL : 'login.html'; return {success:false,message:'Sesión expirada. Redirigiendo...',status:401};} let t=await r.text(); try{return JSON.parse(t)}catch{return {success:false,message:'La API no devolvió JSON válido',raw:t,status:r.status}}}, get(p,a=false){return this.req(p,{headers:this.headers(a)})}, post(p,d,a=false){return this.req(p,{method:'POST',headers:this.headers(a),body:JSON.stringify(d)})}, put(p,d){return this.req(p,{method:'PUT',headers:this.headers(true),body:JSON.stringify(d)})}, del(p){return this.req(p,{method:'DELETE',headers:this.headers(true)})} };
const money=n=>new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS',maximumFractionDigits:0}).format(Number(n||0));
function showError(id,r){document.getElementById(id).innerHTML=`<div class="alert alert-danger">${r.message}<pre class="small">${r.raw||''}</pre></div>`}

const getImgUrl = (path, size = 'full') => {
  if (!path) return 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80';
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) return path;
  
  let clean = path.replace(/\\/g, '/').replace(/^\/+/, '');
  
  // Si la ruta ya incluye el prefijo 'uploads/', lo removemos para evitar duplicación
  if (clean.startsWith('uploads/')) {
    clean = clean.substring(8);
  }
  
  // Redirigir a la carpeta correspondiente para miniaturas y tamaños medianos
  if (size === 'thumb' || size === 'medium') {
    let parts = clean.split('/');
    if (parts.length > 1) {
      let filename = parts.pop();
      let folder = parts.join('/');
      clean = `${folder}/${size === 'thumb' ? 'thumbs' : 'medium'}/${filename}`;
    }
  }
  
  const uploadsBase = window.AppConfig ? window.AppConfig.UPLOADS_BASE_URL : '';
  return uploadsBase ? `${uploadsBase}/${clean}` : `/${clean}`;
};

