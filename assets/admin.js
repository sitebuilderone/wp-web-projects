/* global wp */
(() => {
  'use strict';
  const rows = document.getElementById('wwp-gallery-rows');
  if (!rows) return;
  const text = wp.i18n ? wp.i18n.__ : (value) => value;
  function picker(multiple, selected) {
    const frame = wp.media({ title: text('Choose project images', 'wp-web-projects'), library: { type: 'image' }, multiple });
    frame.on('select', () => selected(frame.state().get('selection').toJSON()));
    frame.open();
  }
  function preview(container, item) {
    const img = document.createElement('img');
    img.src = item.sizes?.thumbnail?.url || item.url;
    img.alt = item.alt || '';
    container.replaceChildren(img);
  }
  function reindex() {
    const list = [...rows.children];
    list.forEach((row, index) => {
      row.querySelectorAll('[data-field]').forEach((field) => { field.name = `wwp_gallery[${index}][${field.dataset.field}]`; });
      row.querySelector('.wwp-up').disabled = index === 0;
      row.querySelector('.wwp-down').disabled = index === list.length - 1;
    });
  }
  document.getElementById('wwp-select-logo').addEventListener('click', () => picker(false, ([item]) => {
    document.getElementById('wwp-logo').value = item.id;
    preview(document.getElementById('wwp-logo-preview'), item);
  }));
  document.getElementById('wwp-remove-logo').addEventListener('click', () => {
    document.getElementById('wwp-logo').value = '';
    document.getElementById('wwp-logo-preview').replaceChildren();
  });
  document.getElementById('wwp-add-gallery').addEventListener('click', () => picker(true, (items) => {
    items.forEach((item) => {
      const fragment = document.getElementById('wwp-gallery-template').content.cloneNode(true);
      fragment.querySelector('[data-field="id"]').value = item.id;
      preview(fragment.querySelector('.wwp-gallery-preview'), item);
      rows.append(fragment);
    });
    reindex();
  }));
  rows.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    const row = button?.closest('.wwp-gallery-row');
    if (!row) return;
    if (button.classList.contains('wwp-remove')) {
      const next = row.nextElementSibling || row.previousElementSibling;
      row.remove();
      (next?.querySelector('input:not([type="hidden"])') || document.getElementById('wwp-add-gallery')).focus();
    }
    if (button.classList.contains('wwp-up') && row.previousElementSibling) row.previousElementSibling.before(row);
    if (button.classList.contains('wwp-down') && row.nextElementSibling) row.nextElementSibling.after(row);
    reindex();
  });
  reindex();
})();
