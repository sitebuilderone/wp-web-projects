(() => {
  'use strict';
  document.querySelectorAll('[data-wwp-portfolio]').forEach((root) => {
    const cards = [...root.querySelectorAll('.wwp-project')].map((element) => ({ element, terms: JSON.parse(element.dataset.wwpTerms) }));
    const inputs = [...root.querySelectorAll('input[data-taxonomy]')];
    const allButtons = [...root.querySelectorAll('[data-wwp-all]')];
    const matches = (card, selections, ignore) => Object.entries(selections).every(([taxonomy, ids]) => taxonomy === ignore || ids.length === 0 || ids.some((id) => card.terms[taxonomy].includes(id)));
    function update() {
      const selections = {};
      inputs.forEach((input) => {
        const ids = selections[input.dataset.taxonomy] ||= [];
        if (input.checked) ids.push(Number(input.value));
      });
      let total = 0;
      cards.forEach((card) => {
        const visible = matches(card, selections);
        card.element.hidden = !visible;
        if (visible) total++;
      });
      inputs.forEach((input) => {
        const taxonomy = input.dataset.taxonomy;
        const count = cards.filter((card) => matches(card, selections, taxonomy) && card.terms[taxonomy].includes(Number(input.value))).length;
        input.closest('label').querySelector('[data-wwp-count]').textContent = `(${count})`;
        input.disabled = count === 0 && !input.checked;
      });
      allButtons.forEach((button) => {
        button.setAttribute('aria-pressed', String(!inputs.some((input) => input.dataset.taxonomy === button.dataset.wwpAll && input.checked)));
      });
      const status = root.querySelector('[data-wwp-status]');
      status.textContent = status.dataset.format.replace('%d', total);
      root.querySelector('[data-wwp-empty]').hidden = total !== 0;
    }
    root.querySelector('.wwp-filters').hidden = false;
    root.querySelector('.wwp-results').classList.add('wwp-filtered-results');
    inputs.forEach((input) => input.addEventListener('change', update));
    allButtons.forEach((button) => button.addEventListener('click', () => {
      inputs.forEach((input) => { if (input.dataset.taxonomy === button.dataset.wwpAll) input.checked = false; });
      update();
    }));
    root.querySelector('[data-wwp-reset]').addEventListener('click', () => {
      inputs.forEach((input) => { input.checked = false; });
      update();
    });
    update();
  });

  document.querySelectorAll('[data-wwp-gallery]').forEach((root) => {
    const allLinks = [...root.querySelectorAll('[data-wwp-image]')];
    const visibleLinks = () => allLinks.filter((link) => !link.closest?.('.wwp-project')?.hidden);
    let links = visibleLinks();
    const dialog = root.querySelector('dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    let index = 0;
    let opener;
    const image = dialog.querySelector('img');
    function show(next) {
      index = (next + links.length) % links.length;
      const link = links[index];
      image.src = link.href;
      image.alt = link.querySelector('img')?.alt || '';
      dialog.querySelector('[data-wwp-heading]').textContent = link.dataset.heading;
      dialog.querySelector('[data-wwp-description]').textContent = link.dataset.description;
      dialog.querySelector('[data-wwp-position]').textContent = `${index + 1} / ${links.length}`;
    }
    allLinks.forEach((link) => link.addEventListener('click', (event) => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      opener = link;
      links = visibleLinks();
      show(links.indexOf(link));
      dialog.showModal();
    }));
    dialog.querySelector('[data-wwp-prev]').addEventListener('click', () => show(index - 1));
    dialog.querySelector('[data-wwp-next]').addEventListener('click', () => show(index + 1));
    dialog.querySelector('[data-wwp-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault();
        show(index + (event.key === 'ArrowLeft' ? -1 : 1));
      }
    });
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => opener?.focus());
  });
})();
