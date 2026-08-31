(() => {
  'use strict';const cfg=window.CONFIG_INFORMES||{},root=document.querySelector('[data-config-informes]');if(!root)return;const preview=root.querySelector('#membrete-actual'),msg=root.querySelector('#mensaje-config-informes');
  const mostrar=d=>{preview.innerHTML=d?.membrete_path?`<img src="${cfg.baseUrl}${String(d.membrete_path).replace(/^\//,'')}" alt="Membrete actual"><p>Actualizado: ${d.updated_at||'ahora'}</p>`:'<p>No hay un membrete activo.</p>';};
  fetch(cfg.api).then(r=>r.json().then(j=>({r,j}))).then(({r,j})=>{if(!r.ok||j.success===false)throw new Error(j.message);mostrar(j.data);}).catch(e=>{msg.textContent=e.message;msg.className='app-message-error';});
  root.querySelector('#form-membrete').addEventListener('submit',async e=>{e.preventDefault();const formulario=e.currentTarget;msg.textContent='Subiendo imagen...';try{const r=await fetch(cfg.api,{method:'POST',body:new FormData(formulario)}),j=await r.json();if(!r.ok||j.success===false)throw new Error(j.message);mostrar(j.data);msg.textContent=j.message;msg.className='app-message-success';formulario.reset();}catch(x){msg.textContent=x.message;msg.className='app-message-error';}});
})();
