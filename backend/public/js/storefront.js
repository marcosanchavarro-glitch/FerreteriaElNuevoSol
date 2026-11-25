(() => {
  'use strict';
  const navigation = document.querySelector('.ens-navigation');
  const searchToggle = document.querySelector('.ens-search-toggle');
  const header = document.querySelector('.ens-header');
  const setSearch = expanded => {
    header.classList.toggle('ens-search-open', expanded);
    searchToggle?.setAttribute('aria-expanded', String(expanded));
    searchToggle?.setAttribute('aria-label', expanded ? 'Cerrar búsqueda' : 'Abrir búsqueda');
  };
  searchToggle?.addEventListener('click', () => {
    const expanded = searchToggle.getAttribute('aria-expanded') !== 'true';
    setSearch(expanded);
    if (expanded) { if (navigation) navigation.open = false; document.querySelector('#header-search')?.focus(); }
  });
  header?.addEventListener('keydown', event => {
    if (event.key === 'Escape' && header.classList.contains('ens-search-open')) { setSearch(false); searchToggle.focus(); }
  });
  if (navigation) {
    const desktopNavigation = matchMedia('(min-width: 768px)');
    const adaptNavigation = () => { navigation.open = desktopNavigation.matches; };
    adaptNavigation();
    desktopNavigation.addEventListener('change', adaptNavigation);
    navigation.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !desktopNavigation.matches && navigation.open) {
        navigation.open = false;
        navigation.querySelector('summary').focus();
      }
    });
    navigation.addEventListener('toggle', () => {
      navigation.querySelector('summary').setAttribute('aria-label', navigation.open ? 'Cerrar menú y categorías' : 'Abrir menú y categorías');
      if (navigation.open && !desktopNavigation.matches) setSearch(false);
    });
  }
  document.querySelectorAll('[data-checkout-form]').forEach(form => {
    const button = form.querySelector('button[type="submit"]');
    const label = button.textContent;
    const status = document.createElement('p');
    status.setAttribute('role', 'status');
    form.append(status);
    form.addEventListener('submit', event => {
      if (form.dataset.busy) { event.preventDefault(); return; }
      form.dataset.busy = '1';
      form.setAttribute('aria-busy', 'true');
      button.disabled = true;
      button.textContent = 'Guardando pedido…';
      status.textContent = 'Estamos registrando tu pedido. Esperá la confirmación.';
    });
    window.addEventListener('pageshow', () => {
      delete form.dataset.busy;
      form.removeAttribute('aria-busy');
      button.disabled = false;
      button.textContent = label;
      status.textContent = '';
    });
  });
  document.querySelectorAll('img[data-placeholder]').forEach(image => {
    const fallback = () => { if (image.getAttribute('src') !== image.dataset.placeholder) image.src = image.dataset.placeholder; };
    image.addEventListener('error', fallback);
    if (image.complete && image.naturalWidth === 0) fallback();
  });
  const main = document.getElementById('ens-main-product-image');
  document.querySelectorAll('[data-gallery-src]').forEach(button => button.addEventListener('click', () => {
    if (!main) return;
    main.src = button.dataset.gallerySrc;
    main.alt = button.dataset.galleryAlt;
    document.querySelectorAll('[data-gallery-src]').forEach(other => {
      other.classList.toggle('ens-active', other === button);
      other.setAttribute('aria-pressed', String(other === button));
    });
  }));
  document.querySelectorAll('[data-quantity]').forEach(control => {
    const input = control.querySelector('input');
    control.querySelectorAll('[data-delta]').forEach(button => button.addEventListener('click', () => {
      const text = input.value.replace(',', '.');
      if (!/^\d{1,4}(\.\d{1,3})?$/.test(text)) return;
      const parts = text.split('.');
      const scaled = Number(parts[0]) * 1000 + Number((parts[1] || '').padEnd(3, '0'));
      const step = Number(input.dataset.step || '1000');
      const next = Math.max(step, Math.min(9999999, scaled + Number(button.dataset.delta) * step));
      input.value = (Math.floor(next / 1000) + '.' + String(next % 1000).padStart(3, '0')).replace(/\.?0+$/, '');
    }));
  });
  const selector = document.querySelector('[data-variant-selector]');
  if (!selector) return;
  const render = () => {
    const option = selector.selectedOptions[0];
    if (!option) return;
    ['price','unit','availability','sku','mode','name'].forEach(key => {
      const target = document.querySelector('[data-offer-' + key + ']');
      if (target) target.textContent = option.dataset[key];
    });
    const variantInput = document.querySelector('[data-cart-variant]');
    if (variantInput) variantInput.value = option.value;
    const quantity = document.getElementById('product-quantity');
    if (quantity) quantity.dataset.step = option.dataset.step;
    const add = document.querySelector('[data-cart-add]');
    if (add) {
      add.disabled = option.dataset.canAdd !== '1';
      add.textContent = add.disabled ? 'Sin stock' : 'Agregar al carrito';
    }
    const availability = document.querySelector('[data-offer-availability]');
    availability?.classList.toggle('ens-stock-out', option.dataset.availability === 'Sin stock');
    availability?.classList.toggle('ens-stock-unknown', option.dataset.availability === 'Consultar disponibilidad');
  };
  selector.addEventListener('change', () => {
    render();
    const url = new URL(window.location.href);
    url.searchParams.set('variante', selector.value);
    window.history.replaceState(null, '', url);
  });
  window.addEventListener('pageshow', render);
})();


// No Google request is made until the visitor explicitly loads the map.
document.querySelectorAll('[data-map-embed]').forEach(shell => {
 const button = shell.querySelector('[data-load-map]');
 const status = shell.parentElement.querySelector('[data-map-status]');
 button.addEventListener('click', () => {
  if (shell.dataset.started) return;
  shell.dataset.started = '1';
  const frame = document.createElement('iframe');
  frame.title = 'Ubicación del comercio en Google Maps';
  frame.loading = 'lazy';
  frame.referrerPolicy = 'strict-origin-when-cross-origin';
  frame.allowFullscreen = true;
  frame.src = shell.dataset.mapEmbed;
  shell.setAttribute('aria-busy','true');
  button.disabled = true;
  status.textContent = 'Solicitando el mapa a Google Maps. Si no aparece, usá el enlace de ubicación.';
  const timeout = setTimeout(() => {
   shell.removeAttribute('aria-busy');
   status.textContent = 'Si el mapa no aparece o tarda en cargar, abrí la ubicación con el enlace de Google Maps.';
  },12000);
  frame.addEventListener('load', () => {
   clearTimeout(timeout);
   shell.removeAttribute('aria-busy');
   status.textContent = 'Si el mapa no se visualiza correctamente, usá el enlace de Google Maps.';
  });
  frame.addEventListener('error', () => {
   clearTimeout(timeout);
   shell.removeAttribute('aria-busy');
   status.textContent = 'No se pudo cargar el mapa. Usá el enlace de Google Maps.';
  });
  button.replaceWith(frame);
  frame.tabIndex = 0;
  frame.focus({preventScroll:true});
 });
});
