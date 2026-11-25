(() => {
 'use strict';
 if (!window.HTMLDialogElement || !HTMLDialogElement.prototype.showModal || !window.fetch) return;
 const auth = document.querySelector('#ens-auth-dialog');
 const cart = document.querySelector('#ens-cart-drawer');
 const account = document.querySelector('#ens-account-dialog');
 let accountBusy = false;
 let accountLoad;
 let cartBusy = false;
 let authBusy = false;
 let authLoad;
 const status = (dialog, message = '', error = false) => {
  const node = dialog.querySelector('[data-overlay-status]');
  node.textContent = message;
  node.toggleAttribute('data-error', error);
  node.setAttribute('role', error ? 'alert' : 'status');
 };
 const returnFocus = new WeakMap();
 const open = (dialog, trigger = document.activeElement) => {
  if (!dialog.open) { returnFocus.set(dialog, trigger); dialog.showModal(); }
 };
 const ordinaryClick = event => event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey;
 const request = async (url, options = {}) => {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20000);
  try {
   const response = await fetch(url, {credentials:'same-origin', cache:'no-store', ...options, signal:controller.signal});
   if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('La sesión pudo haber vencido. Recargá la página antes de continuar.');
   const data = await response.json();
   if (!response.ok) {
    const errors = Object.values(data.errors || {}).flat();
    throw new Error(response.status === 419 ? 'Tu sesión venció. Recargá la página para continuar.' : errors.join(' ') || 'No se pudo completar la operación. Volvé a intentarlo.');
   }
   return data;
  } catch (error) {
   if (error.name === 'AbortError' || error instanceof TypeError) throw new Error('No pudimos confirmar el resultado. Revisá tu conexión y el estado actual antes de repetir la operación.');
   throw error;
  } finally { clearTimeout(timeout); }
 };
 const renderCart = data => {
  cart.querySelector('[data-cart-content]').innerHTML = data.html;
  document.querySelectorAll('[data-cart-open]').forEach(link => {
   const count = link.querySelector('[data-cart-count]');
   if (count) { count.textContent = data.count; count.hidden = data.count === 0; }
   link.setAttribute('aria-label', `Carrito: ${data.count} productos distintos`);
  });
  status(cart, data.message);
 };
 const loadCart = async () => {
  if (cartBusy) return;
  cartBusy = true;
  open(cart);
  cart.setAttribute('aria-busy', 'true');
  status(cart, 'Cargando carrito…');
  try { renderCart(await request(cart.dataset.cartUrl, {headers:{Accept:'application/json'}})); }
  catch (error) { status(cart, error.message, true); }
  finally { cartBusy = false; cart.removeAttribute('aria-busy'); }
 };
 const loadAuth = async mode => {
  if (authBusy) return;
  authLoad?.abort();
  const controller = new AbortController();
  authLoad = controller;
  const timeout = setTimeout(() => controller.abort(), 20000);
  open(auth);
  auth.querySelector('[data-auth-content]').replaceChildren();
  auth.querySelectorAll('[data-auth-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.authMode === mode)));
  auth.setAttribute('aria-busy', 'true');
  status(auth, 'Cargando formulario…');
  try {
   const response = await fetch(mode === 'register' ? auth.dataset.registerUrl : auth.dataset.loginUrl, {credentials:'same-origin', cache:'no-store', signal:controller.signal});
   if (!response.ok || response.redirected) throw new Error('No se pudo abrir el formulario. Recargá la página.');
   const page = new DOMParser().parseFromString(await response.text(), 'text/html');
   const section = page.querySelector('section.card.narrow');
   if (!section?.querySelector('form')) throw new Error('No se pudo abrir el formulario.');
   section.querySelectorAll('[autofocus]').forEach(input => input.removeAttribute('autofocus'));
   section.className = 'ens-auth-fields';
   section.querySelector('h1')?.remove();
   const form = section.querySelector('form');
   form.dataset.overlayAuthForm = '';
   auth.querySelector('[data-auth-content]').append(section);
   status(auth);
   if (auth.open) section.querySelector('input:not([type=hidden])')?.focus();
  } catch (error) {
   if (authLoad === controller) status(auth, error.name === 'AbortError' ? 'El formulario tardó demasiado. Volvé a elegir Ingresar o Crear cuenta.' : error.message, true);
  } finally {
   clearTimeout(timeout);
   if (authLoad === controller) auth.removeAttribute('aria-busy');
  }
 };
 const loadAccount = async url => {
  if (accountBusy) return;
  accountLoad?.abort();
  const controller = new AbortController();
  accountLoad = controller;
  const timeout = setTimeout(() => controller.abort(), 20000);
  open(account);
  account.querySelector('[data-account-content]').replaceChildren();
  account.setAttribute('aria-busy', 'true');
  status(account, 'Cargando tu cuenta…');
  try {
   const response = await fetch(url, {credentials:'same-origin', cache:'no-store', signal:controller.signal});
   if (!response.ok || response.redirected) throw new Error('No pudimos abrir tu cuenta. Recargá la página para verificar tu sesión.');
   const page = new DOMParser().parseFromString(await response.text(), 'text/html');
   const content = page.querySelector('#contenido');
   if (!content) throw new Error('No se pudo cargar esta sección.');
   const heading = content.querySelector('h1');
   account.querySelector('#ens-account-title').textContent = heading?.textContent || 'Mi cuenta';
   heading?.remove();
   const ids = new Map();
   content.querySelectorAll('[id]').forEach(node => { ids.set(node.id, `popup-${node.id}`); node.id = `popup-${node.id}`; });
   content.querySelectorAll('[for],[aria-describedby],[aria-labelledby]').forEach(node => {
    ['for','aria-describedby','aria-labelledby'].forEach(attr => {
     if (node.hasAttribute(attr)) node.setAttribute(attr, node.getAttribute(attr).split(' ').map(id => ids.get(id) || id).join(' '));
    });
   });
   content.querySelectorAll('form').forEach(form => {
    if (form.querySelector('[name="_method"]') && !form.action.includes('/notificaciones/')) form.dataset.accountForm = '';
   });
   account.querySelector('[data-account-content]').replaceChildren(...content.childNodes);
   account.scrollTop = 0;
   status(account);
  } catch (error) {
   if (accountLoad === controller) status(account, error.name === 'AbortError' ? 'La carga tardó demasiado. Volvé a abrir Mi cuenta.' : error.message, true);
  } finally {
   clearTimeout(timeout);
   if (accountLoad === controller) account.removeAttribute('aria-busy');
  }
 };
 document.querySelectorAll('.ens-overlay').forEach(dialog => {
  dialog.addEventListener('close', () => {
   const trigger = returnFocus.get(dialog);
   if (trigger?.isConnected && !trigger.disabled) trigger.focus({preventScroll:true});
  });
  dialog.addEventListener('click', event => {
   if (event.target.closest('[data-overlay-close]')) dialog.close();
   if (event.target === dialog) {
    const box = dialog.getBoundingClientRect();
    if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
   }
  });
 });
 document.addEventListener('click', event => {
  const link = event.target.closest('a');
  if (link && ordinaryClick(event)) {
   const url = new URL(link.href, location.href);
   if (account && (link.hasAttribute('data-account-open') || (link.closest('[data-account-content]') && url.origin === location.origin && (url.pathname === '/mi-cuenta' || url.pathname.startsWith('/mi-cuenta/'))))) {
    event.preventDefault();
    loadAccount(link.hasAttribute('data-account-open') ? account.dataset.accountUrl : url.href);
   } else if (auth && (link.hasAttribute('data-auth-open') || url.href === auth.dataset.loginUrl || url.href === auth.dataset.registerUrl)) {
    event.preventDefault();
    loadAuth(url.href === auth.dataset.registerUrl ? 'register' : 'login');
   } else if (cart && link.hasAttribute('data-cart-open')) {
    event.preventDefault();
    loadCart();
   }
  }
  const mode = event.target.closest('[data-auth-mode]');
  if (mode && auth) loadAuth(mode.dataset.authMode);
  const delta = event.target.closest('#ens-cart-drawer [data-delta]');
  if (delta && !cartBusy) {
   const input = delta.closest('[data-quantity]').querySelector('input');
   if (!/^\d{1,4}(\.\d{1,3})?$/.test(input.value.trim())) return;
   const step = Number(input.dataset.step || 1000);
   const value = Math.max(step, Math.min(9999999, Math.round(Number(input.value) * 1000) + Number(delta.dataset.delta) * step));
   input.value = (value / 1000).toFixed(3).replace(/\.?0+$/, '');
  }
 });
 document.addEventListener('submit', async event => {
  const form = event.target;
  const isAuth = form.matches('[data-overlay-auth-form]');
  const isAccount = account && form.matches('[data-account-form]');
  const isCart = cart && (form.matches('.ens-add-cart') || Boolean(form.closest('[data-cart-content]')));
  if (!isAuth && !isCart && !isAccount) return;
  event.preventDefault();
  if ((isAuth && authBusy) || (isCart && cartBusy) || (isAccount && accountBusy)) return;
  const body = new FormData(form);
  const buttons = [...form.querySelectorAll('button')];
  const disabled = buttons.map(button => button.disabled);
  buttons.forEach(button => { button.disabled = true; });
  form.setAttribute('aria-busy', 'true');
  const dialog = isAuth ? auth : isAccount ? account : cart;
  if (isAuth) authBusy = true;
  else if (isAccount) accountBusy = true;
  else { cartBusy = true; open(cart, event.submitter); }
  status(dialog, isAuth ? 'Procesando ingreso…' : isAccount ? 'Guardando tus datos…' : 'Actualizando carrito…');
  try {
   const data = await request(form.action, {method:'POST', body, headers:{Accept:'application/json'}});
   if (isAuth) { location.reload(); return; }
   if (isAccount) {
    document.querySelectorAll('input[name="_token"]').forEach(input => { input.value = data.csrf_token; });
    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.csrf_token);
    form.querySelectorAll('input[type="password"]').forEach(input => { input.value = ''; });
    if (form.closest('.ens-password-section')) form.closest('details').open = false;
    status(account, data.message);
    account.querySelector('[data-overlay-status]').scrollIntoView({block:'nearest'});
    return;
   }
   renderCart(data);
   cart.querySelector('[data-overlay-close]')?.focus();
  } catch (error) { status(dialog, error.message, true); }
  finally {
   if (isAuth) authBusy = false;
   else if (isAccount) accountBusy = false;
   else cartBusy = false;
   buttons.forEach((button, index) => { button.disabled = disabled[index]; });
   form.removeAttribute('aria-busy');
  }
 });
})();
