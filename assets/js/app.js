document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('form[data-ajax]').forEach(form=>form.addEventListener('submit',async e=>{
    e.preventDefault();const button=form.querySelector('[type=submit]');if(button)button.disabled=true;
    try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'X-CSRF-Token':APP.csrf}});const contentType=response.headers.get('content-type')||'';if(!contentType.includes('application/json')){const body=await response.text();console.error('Unexpected server response',body);throw new Error('The server could not process this request. Please try again.')}const data=await response.json();
      if(!response.ok||!data.ok)throw new Error(data.message||'Request failed');toast(data.message||'Saved','success');
      if(data.reload)setTimeout(()=>location.reload(),500);if(data.redirect)setTimeout(()=>location.href=data.redirect,400);
    }catch(err){toast(err.message,'danger')}finally{if(button)button.disabled=false}
  }));
  if(document.querySelector('#notificationMenu'))fetch(APP.base+'ajax/notifications_fetch.php').then(r=>r.json()).then(d=>{
    unreadCount.textContent=d.unread||'';notificationMenu.innerHTML=d.items?.length?d.items.map(x=>`<div class="dropdown-item-text border-bottom py-2"><small>${escapeHtml(x.data)}</small><div class="text-muted small">${escapeHtml(x.created_at)}</div></div>`).join(''):'<div class="text-muted p-2">No notifications</div>';
  }).catch(()=>{});
});
function toast(message,type='primary'){const el=document.createElement('div');el.className=`toast text-bg-${type} border-0`;el.innerHTML=`<div class="d-flex"><div class="toast-body">${escapeHtml(message)}</div><button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button></div>`;toastArea.append(el);new bootstrap.Toast(el).show();el.addEventListener('hidden.bs.toast',()=>el.remove())}
function escapeHtml(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML}
