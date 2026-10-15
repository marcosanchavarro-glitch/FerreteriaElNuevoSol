(() => {
  'use strict';
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

