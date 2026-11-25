const adminMenu = document.querySelector('.admin-sidebar');
if (adminMenu) {
 const desktop = window.matchMedia('(min-width: 901px)');
 const adaptMenu = () => { adminMenu.open = desktop.matches; };
 adaptMenu();
 desktop.addEventListener('change', adaptMenu);
}
/* Product workspace: progressive forms keep the existing server routes authoritative. */
const storageGet = key => { try { return localStorage.getItem(key); } catch { return null; } };
const storageSet = (key, value) => { try { localStorage.setItem(key, value); } catch {} };
const cards = document.querySelector('[data-product-cards]');
if (cards) {
 const table = document.querySelector('[data-product-table]');
 const selectView = view => {
  const isTable = view === 'table';
  cards.hidden = isTable; table.hidden = !isTable;
  document.querySelector('[data-card-pagination]').hidden = isTable;
  document.querySelectorAll('[data-product-view]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.productView === view)));
  storageSet('ens.product-view', view);
 };
 selectView(new URL(location.href).searchParams.get('view') || storageGet('ens.product-view') || 'cards');
 document.querySelectorAll('[data-product-view]').forEach(button => button.addEventListener('click', () => selectView(button.dataset.productView)));
 document.querySelectorAll('a[href*="/edit"]').forEach(link => link.addEventListener('click', () => {
  try { sessionStorage.setItem('ens.product-context', JSON.stringify({url:location.href, scroll:scrollY})); } catch {}
 }));
 try { const context = JSON.parse(sessionStorage.getItem('ens.product-context')); if (context?.url === location.href) requestAnimationFrame(() => scrollTo(0, context.scroll)); } catch {}
}
const editorMain = document.querySelector('[data-editor-main]');
if (editorMain) {
 const dirty = new Set();
 const state = editorMain.querySelector('[data-save-state]');
 const report = message => { state.textContent = message; };
 const slugify = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'').slice(0,180).replace(/-$/,'');
 const slug = editorMain.querySelector('[name="slug"]');
 const name = editorMain.querySelector('[name="name"]');
 let slugEdited = Boolean(slug?.value);
 slug?.addEventListener('input', () => { slugEdited = true; });
 name?.addEventListener('input', () => { if (slug?.dataset.autoSlug === 'true' && !slugEdited) slug.value = slugify(name.value); });
 document.querySelectorAll('[data-slug-source]').forEach(input => {
  let edited = false; input.addEventListener('input', () => { edited = true; });
  document.getElementById(input.dataset.slugSource)?.addEventListener('input', event => { if (!edited) input.value = slugify(event.target.value); });
 });
 document.querySelectorAll('[data-back-products]').forEach(link => { try { const context = JSON.parse(sessionStorage.getItem('ens.product-context')); const url = new URL(context?.url); if (url.origin === location.origin && url.pathname === '/admin/products') link.href = url.href; } catch {} });
 document.addEventListener('input', event => {
  const form = event.target.closest('form');
  if (form && (form === editorMain || form.closest('[data-editor-panel]'))) { dirty.add(form); report('Cambios pendientes de guardar'); }
  if (event.target.matches('[data-category-search]')) {
   const term = event.target.value.toLocaleLowerCase('es');
   event.target.closest('[data-editor-panel]').querySelectorAll('label.check').forEach(label => { label.hidden = !label.textContent.toLocaleLowerCase('es').includes(term); });
  }
 });
 window.addEventListener('beforeunload', event => { if (dirty.size) { event.preventDefault(); event.returnValue = ''; } });
 document.addEventListener('submit', async event => {
  const form = event.target;
  const panel = form.closest('[data-editor-panel]');
  if (form !== editorMain && !panel) return;
  // Creation uses the normal redirect to establish the new product URL.
  if (form === editorMain && !location.pathname.endsWith('/edit')) { if (form.dataset.submitted) { event.preventDefault(); return; } form.dataset.submitted = 'true'; dirty.delete(form); report('Guardando…'); return; }
  event.preventDefault();
  if (form.dataset.busy) return;
  if (panel && [...dirty].some(other => other !== form && panel.contains(other))) { report('Guardá primero los otros cambios pendientes de esta sección.'); return; }
  if (form.querySelector('input[value="DELETE"]') && !confirm('¿Eliminar esta imagen y su archivo? Esta acción no se puede deshacer.')) return;
  let feedback = form.querySelector('[data-form-feedback]');
  if (!feedback) { feedback = document.createElement('p'); feedback.dataset.formFeedback = ''; feedback.setAttribute('role','status'); form.append(feedback); }
  const show = message => { report(message); feedback.textContent = message; };
  const data = new FormData(form);
  const buttons = [...form.querySelectorAll('button')].map(button => [button, button.disabled]);
  form.dataset.busy = 'true'; form.setAttribute('aria-busy','true'); buttons.forEach(([button]) => { button.disabled = true; });
  form.querySelectorAll('[data-inline-error]').forEach(error => error.remove());
  form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
  show('Guardando…');
  let committed = false;
  try {
   const response = await fetch(form.action, {method:'POST', body:data, headers:{Accept:'application/json'}, credentials:'same-origin'});
   if (response.status === 422) {
    const result = await response.json();
    Object.entries(result.errors || {}).forEach(([key,messages]) => {
     const field = [...form.elements].find(input => input.name === key || input.name === key+'[]');
     const error = document.createElement('p'); error.className = 'field-error'; error.dataset.inlineError = ''; error.textContent = messages.join(' ');
     if (field) { field.setAttribute('aria-invalid','true'); field.closest('.field')?.append(error) || field.after(error); } else form.prepend(error);
    });
    const invalid = form.querySelector('[aria-invalid]');
    let ancestor = invalid?.parentElement; while (ancestor) { if (ancestor.tagName === 'DETAILS') ancestor.open = true; ancestor = ancestor.parentElement; }
    invalid?.focus(); show('Revisá los campos indicados; los datos ingresados se conservaron.'); return;
   }
   if (!response.ok || new URL(response.url).pathname === '/login') throw new Error('No se pudo confirmar el guardado. Revisá tu sesión y conexión.');
   committed = true; dirty.delete(form);
   if (panel) {
    const refresh = await fetch(location.href,{headers:{Accept:'text/html'},credentials:'same-origin'});
    if (!refresh.ok) throw new Error('El cambio se guardó, pero no se pudo actualizar la sección. Recargá cuando hayas guardado los demás cambios.');
    const html = new DOMParser().parseFromString(await refresh.text(),'text/html');
    const updated = [...html.querySelectorAll('[data-editor-panel]')].find(candidate => candidate.dataset.editorPanel === panel.dataset.editorPanel);
    if (!updated) throw new Error('El cambio se guardó; recargá para ver la sección actualizada.');
    const openDetails = [...panel.querySelectorAll('details')].map(detail => detail.open);
    [...updated.querySelectorAll('details')].forEach((detail,index) => { if (index < openDetails.length) detail.open = openDetails[index]; });
    const position = scrollY;
    const notice = document.createElement('p'); notice.className = 'notice'; notice.setAttribute('role','status'); notice.textContent = 'Sección guardada'; updated.prepend(notice);
    panel.replaceWith(updated);
    requestAnimationFrame(() => scrollTo(0,position));
   }
   show(dirty.size ? 'Sección guardada; quedan otros cambios pendientes.' : 'Cambios guardados');
  } catch (error) { show(error.message || (committed ? 'Cambio guardado; vista pendiente de actualizar.' : 'Error de conexión. Verificá el resultado antes de volver a enviar.')); }
  finally { delete form.dataset.busy; form.removeAttribute('aria-busy'); buttons.forEach(([button,disabled]) => { button.disabled = disabled; }); }
 });
 document.addEventListener('change', event => {
  if (!event.target.matches('input[type="file"][name="image"]')) return;
  const file = event.target.files[0]; if (!file || !['image/jpeg','image/png','image/webp'].includes(file.type)) return;
  const previous = event.target.closest('form').querySelector('[data-upload-preview]'); if (previous) { URL.revokeObjectURL(previous.src); previous.remove(); }
  const image = document.createElement('img'); image.dataset.uploadPreview = ''; image.className = 'catalog-preview'; image.alt = 'Vista previa de la nueva fotografía'; image.src = URL.createObjectURL(file); event.target.after(image);
 });
}
// Existing endpoints remain available without JavaScript.
document.addEventListener('submit', event => {
 const form = event.target;
 if (event.defaultPrevented) return;
 if (form.action.endsWith('/quick')) { try { sessionStorage.setItem('ens.product-context',JSON.stringify({url:location.href,scroll:scrollY})); } catch {} }
 if (form.dataset.confirm && !confirm(form.dataset.confirm)) { event.preventDefault(); return; }
 if (!form.closest('.admin-workspace') || form.matches('[data-editor-main]') || form.closest('[data-editor-panel]')) return;
 if (form.dataset.submitted) { event.preventDefault(); return; }
 form.dataset.submitted = 'true';
 setTimeout(() => { form.querySelectorAll('button[type=submit],button:not([type])').forEach(button => { button.disabled = true; }); },0);
});
window.addEventListener('pageshow', event => { if (event.persisted) document.querySelectorAll('form[data-submitted]').forEach(form => { delete form.dataset.submitted; form.querySelectorAll('button').forEach(button => { button.disabled = false; }); }); });
const ordersContact = document.querySelector('[name="whatsapp_orders_contact"]');
if (ordersContact) {
 const form = ordersContact.form;
 const renderContacts = () => {
  const destinations = [];
  ['orders','inquiries'].forEach(purpose => {
   const choice = form.elements.namedItem('whatsapp_' + (purpose === 'orders' ? 'orders_contact' : 'inquiries_contact'));
   const custom = form.elements.namedItem(purpose === 'orders' ? 'whatsapp_number' : 'whatsapp_inquiries_number');
   custom.closest('.field').hidden = choice.value !== 'custom' && !custom.closest('.field').querySelector('.field-error');
   ['primary','secondary','tertiary'].forEach((key,index) => {
    const number = form.elements.namedItem('contact_' + key + '_number').value;
    choice.querySelector('option[value="'+key+'"]').textContent = ['Primario','Secundario','Terciario'][index] + (number ? ' · +' + number : ' · sin configurar');
   });
   const number = choice.value === 'custom' ? custom.value : form.elements.namedItem('contact_' + choice.value + '_number').value;
   destinations.push((purpose === 'orders' ? 'Pedidos: ' : 'Consultas: ') + (/^[1-9][0-9]{7,14}$/.test(number) ? '+' + number : 'Sin configurar o formato inválido'));
  });
  document.querySelector('[data-whatsapp-preview]').textContent = destinations.join(' · ') + '. Los cambios se aplican al guardar.';
 };
 form.addEventListener('input',renderContacts);
 form.addEventListener('change',renderContacts);
 renderContacts();
}
const filterPanel = document.querySelector('.product-filter-panel');
if (filterPanel && !location.search) filterPanel.open = matchMedia('(min-width:701px)').matches;
document.querySelector('[data-close-admin-menu]')?.addEventListener('click', () => { adminMenu.open = false; adminMenu.querySelector('summary').focus(); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && adminMenu?.open && matchMedia('(max-width:900px)').matches) { adminMenu.open = false; adminMenu.querySelector('summary').focus(); } });
document.addEventListener('invalid', event => { let parent = event.target.parentElement; while (parent) { if (parent.tagName === 'DETAILS') parent.open = true; parent = parent.parentElement; } }, true);
adminMenu?.addEventListener('toggle', () => {
 const open = adminMenu.open && matchMedia('(max-width:900px)').matches;
 document.querySelector('main').inert = open;
 document.querySelector('body>header').inert = open;
 if (open) adminMenu.querySelector('[data-close-admin-menu]')?.focus();
});
document.addEventListener('keydown', event => {
 if (event.key !== 'Tab' || !adminMenu?.open || !matchMedia('(max-width:900px)').matches) return;
 const links = [...adminMenu.querySelectorAll('.admin-nav a,.admin-nav button')].filter(element => element.getClientRects().length);
 const first = links[0], last = links[links.length-1];
 if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
 if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
});
document.addEventListener('input', event => {
 if (event.target.id === 'new-category-slug') event.target.dataset.manualSlug = 'true';
 if (event.target.id === 'new-category-name') {
  const slug = event.target.form.querySelector('[name=slug]');
  if (slug && !slug.dataset.manualSlug) slug.value = event.target.value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'').slice(0,180).replace(/-$/,'');
 }
});
matchMedia('(min-width:901px)').addEventListener('change', () => {
 const open = Boolean(adminMenu?.open && matchMedia('(max-width:900px)').matches);
 document.querySelector('main').inert = open;
 document.querySelector('body>header').inert = open;
});

// Restore only the form position; never persist entered customer data or credentials.
const contextForms = document.querySelectorAll('form[data-preserve-context]');
contextForms.forEach(form => {
 const key = 'ens.form-position:' + location.pathname;
 try {
  const saved = JSON.parse(sessionStorage.getItem(key) || 'null');
  sessionStorage.removeItem(key);
  if (saved && Number.isFinite(saved.scroll)) requestAnimationFrame(() => {
   const target = document.getElementById(saved.focus);
   target?.focus({preventScroll:true});
   scrollTo(0,saved.scroll);
  });
 } catch {}
 form.addEventListener('submit', () => {
  try { sessionStorage.setItem(key, JSON.stringify({scroll:scrollY,focus:document.activeElement.id || ''})); } catch {}
 });
});
const cmsEditor = document.querySelector('[data-cms-editor]');
if (cmsEditor) {
 const panel = document.querySelector('.cms-live-preview');
 const frame = panel.querySelector('[data-cms-frame]');
 const status = panel.querySelector('[data-cms-status]');
 const errors = panel.querySelector('[data-cms-preview-errors]');
 const refresh = panel.querySelector('[data-cms-refresh]');
 const save = cmsEditor.querySelector('#cms-save');
 let dirty = false, saving = false, previewing = false, revision = 0;
 const initialLabel = save.textContent;
 const updateFields = () => {
  const limit = cmsEditor.elements.namedItem('max_items');
  if (limit) limit.closest('.field').hidden = !['featured_products','featured_categories'].includes(cmsEditor.elements.namedItem('type').value);
 };
 const changed = () => { dirty = true; revision++; status.textContent = 'Cambios pendientes. Actualizá la vista previa y guardá cuando estés conforme.'; updateFields(); };
 cmsEditor.addEventListener('input',changed);
 cmsEditor.addEventListener('change',changed);
 updateFields();
 window.addEventListener('beforeunload', event => { if (dirty && !saving) { event.preventDefault(); event.returnValue = ''; } });
 // Other forms on this page (for example image removal) must not discard the draft silently.
 document.addEventListener('submit', event => {
  if (event.target !== cmsEditor && dirty && !confirm('Hay cambios sin guardar. ¿Querés descartarlos y continuar?')) event.preventDefault();
 }, true);
 cmsEditor.addEventListener('submit', event => {
  if (saving || event.defaultPrevented) { event.preventDefault(); return; }
  saving = true;
  cmsEditor.setAttribute('aria-busy','true');
  save.disabled = true;
  save.textContent = 'Guardando…';
  status.textContent = 'Guardando. Esperá la confirmación del servidor.';
 });
 window.addEventListener('pageshow', () => { saving = false; save.disabled = false; save.textContent = initialLabel; cmsEditor.removeAttribute('aria-busy'); });
 const resizePreview = () => {
  const width = panel.querySelector('#cms-width').value === 'mobile' ? 390 : 1280;
  frame.style.width = width + 'px';
  frame.style.zoom = Math.min(1,frame.parentElement.clientWidth / width);
 };
 panel.querySelector('#cms-width').addEventListener('change',resizePreview);
 new ResizeObserver(resizePreview).observe(frame.parentElement);
 resizePreview();
 const preview = async () => {
  if (previewing || !cmsEditor.reportValidity()) return;
  previewing = true; refresh.disabled = true; errors.hidden = true;
  const sentRevision = revision;
  status.textContent = 'Preparando vista previa sin guardar…';
  frame.setAttribute('aria-busy','true');
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(),20000);
  try {
   const data = new FormData(cmsEditor); data.delete('_method');
   const response = await fetch(cmsEditor.dataset.previewUrl,{method:'POST',body:data,credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},signal:controller.signal});
   if (!response.ok || response.redirected) {
    if (response.status === 422) {
     const payload = await response.json();
     throw new Error(Object.values(payload.errors || {}).flat().join(' ') || 'Revisá los campos.');
    }
    throw new Error(response.status === 419 || response.status === 401 || response.redirected ? 'La sesión venció. Guardá una copia de tus textos y volvé a ingresar.' : 'No se pudo preparar la vista previa. Intentá nuevamente.');
   }
   frame.srcdoc = await response.text();
   status.textContent = revision === sentRevision ? 'Vista previa actualizada. No se guardó ningún cambio.' : 'La vista previa quedó desactualizada: hay cambios posteriores. Volvé a actualizarla.';
  } catch (error) {
   errors.textContent = error.name === 'AbortError' ? 'La vista previa tardó demasiado. Intentá nuevamente.' : error.message;
   errors.hidden = false; status.textContent = 'No se pudo actualizar la vista previa. Tus cambios siguen en el formulario.';
  } finally { clearTimeout(timeout); previewing = false; refresh.disabled = false; frame.removeAttribute('aria-busy'); }
 };
 refresh.addEventListener('click',preview);
 if (document.querySelector('.errors')) { dirty = true; status.textContent = 'No se guardó. Corregí los errores indicados y volvé a intentar.'; }
 else if (document.querySelector('.notice')) status.textContent = 'Guardado correctamente.';
 preview();
}

const locationForm = document.querySelector('[data-settings-form]');
if (locationForm) {
 const previewLink = document.querySelector('[data-maps-link-preview]');
 const previewText = document.querySelector('[data-location-preview]');
 const previewStatus = document.querySelector('[data-location-preview-status]');
 const mapsPattern = /^https:\/\/(?:(?:www\.)?google\.com(?:\.ar)?\/maps(?:[/?#]|$)|maps\.google\.com\/|maps\.app\.goo\.gl\/|goo\.gl\/maps\/)[^\s]*$/;
 const updateLocationPreview = () => {
  previewText.textContent = ['business_name','pickup_address','locality','province','postal_code'].map(name => locationForm.elements.namedItem(name).value.trim()).filter(Boolean).join(' · ');
  const url = locationForm.elements.namedItem('maps_url').value.trim();
  let valid = mapsPattern.test(url);
  if (valid) {
   try { valid = ![...new URL(url).searchParams.keys()].some(key => ['key','api_key','apikey','token','access_token','signature'].includes(key.toLowerCase().split('[')[0])); }
   catch { valid = false; }
  }
  previewLink.hidden = !valid;
  if (valid) previewLink.href = url; else previewLink.removeAttribute('href');
  previewStatus.textContent = valid ? 'Enlace propuesto listo para revisar. Los cambios se aplican al guardar.' : 'Ingresá un enlace HTTPS válido de Google Maps. No se guarda hasta confirmar.';
 };
 locationForm.addEventListener('input',updateLocationPreview);
 updateLocationPreview();
 const save = locationForm.querySelector('#settings-save');
 const savedLabel = save.textContent;
 const saveStatus = document.createElement('p');
 saveStatus.setAttribute('role','status');
 locationForm.append(saveStatus);
 locationForm.addEventListener('submit', event => {
  if (locationForm.dataset.settingsBusy) { event.preventDefault(); return; }
  locationForm.dataset.settingsBusy = '1';
  locationForm.setAttribute('aria-busy','true');
  save.disabled = true;
  save.textContent = 'Guardando configuración…';
  saveStatus.textContent = 'Esperá la confirmación del servidor.';
 });
 window.addEventListener('pageshow', () => {
  delete locationForm.dataset.settingsBusy;
  locationForm.removeAttribute('aria-busy');
  save.disabled = false;
  save.textContent = savedLabel;
  saveStatus.textContent = '';
 });
}

// Remember listing context within this tab, never credentials or order bodies.
const orderListing = document.querySelector('[data-order-listing]');
if (orderListing) {
 try {
  const previous = JSON.parse(sessionStorage.getItem('ens.orders-return') || 'null');
  if (previous?.url === location.href && Number.isFinite(previous.scroll)) requestAnimationFrame(() => { scrollTo(0,previous.scroll); document.querySelector(`[data-order-open="${Number(previous.id)}"]`)?.focus({preventScroll:true}); });
 } catch {}
 orderListing.addEventListener('click',event => {
  const link = event.target.closest('[data-order-open]');
  if (link) try { sessionStorage.setItem('ens.orders-return',JSON.stringify({url:location.href,scroll:scrollY,id:Number(link.dataset.orderOpen)})); } catch {}
 });
}
const orderReturn = document.querySelector('[data-order-return]');
if (orderReturn) try {
 const previous = JSON.parse(sessionStorage.getItem('ens.orders-return') || 'null');
 if (previous) { const url = new URL(previous.url); if (url.origin === location.origin && url.pathname === '/admin/orders') orderReturn.href = url.href; }
} catch {}
