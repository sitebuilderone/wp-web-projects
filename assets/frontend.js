(() => {
  'use strict';
  document.querySelectorAll('[data-wwp-portfolio]').forEach((root) => {
    const cards = [...root.querySelectorAll('.wwp-project')].map((element) => ({ element, terms: JSON.parse(element.dataset.wwpTerms) }));
    const inputs = [...root.querySelectorAll('input[data-taxonomy]')];
    const allButtons = [...root.querySelectorAll('[data-wwp-all]')];
    const grid = root.querySelector('.wwp-project-grid');
    const animations = new Set();
    const ghosts = new Set();
    const clearMotion = () => {
      animations.forEach((animation) => animation.cancel());
      animations.clear();
      ghosts.forEach((ghost) => ghost.remove());
      ghosts.clear();
    };
    const play = (element, frames, done) => {
      const animation = element.animate(frames, { duration: 650, easing: 'ease', fill: 'both' });
      animations.add(animation);
      animation.onfinish = () => {
        animation.cancel();
        animations.delete(animation);
        if (done) done();
      };
    };
    const matches = (card, selections, ignore) => Object.entries(selections).every(([taxonomy, ids]) => taxonomy === ignore || ids.length === 0 || ids.some((id) => card.terms[taxonomy].includes(id)));
    function update(animate = true) {
      const motion = animate && grid && typeof cards[0]?.element.animate === 'function' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      // Measure the current visual positions before cancelling an interrupted transition.
      const before = new Map();
      if (motion) cards.forEach(({ element }) => { if (!element.hidden) before.set(element, element.getBoundingClientRect()); });
      clearMotion();
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
      if (motion) {
        const bounds = grid.getBoundingClientRect();
        cards.forEach(({ element }) => {
          const previous = before.get(element);
          if (element.hidden) {
            if (!previous) return;
            // Decorative exit copies leave the real cards hidden immediately, so
            // keyboard focus and lightbox navigation use the new results at once.
            const ghost = element.cloneNode(true);
            ghost.hidden = false;
            ghost.inert = true;
            ghost.setAttribute('aria-hidden', 'true');
            ghost.removeAttribute('id');
            ghost.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
            ghost.classList.add('wwp-filter-ghost');
            Object.assign(ghost.style, { position: 'absolute', left: `${previous.left - bounds.left}px`, top: `${previous.top - bounds.top}px`, width: `${previous.width}px`, height: `${previous.height}px`, margin: '0', pointerEvents: 'none' });
            grid.append(ghost);
            ghosts.add(ghost);
            play(ghost, [{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(.001)' }], () => { ghost.remove(); ghosts.delete(ghost); });
          } else {
            const next = element.getBoundingClientRect();
            if (previous) {
              const x = previous.left - next.left, y = previous.top - next.top;
              if (x || y) play(element, [{ transform: `translate(${x}px, ${y}px)` }, { transform: 'translate(0, 0)' }]);
            } else {
              play(element, [{ opacity: 0, transform: 'scale(.001)' }, { opacity: 1, transform: 'scale(1)' }]);
            }
          }
        });
      }
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
    update(false);
    if (grid) window.addEventListener('resize', clearMotion);
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
      const website = dialog.querySelector('[data-wwp-website]');
      const url = link.dataset.website || '';
      website.hidden = !url.startsWith('https://');
      if (website.hidden) website.removeAttribute('href');
      else website.href = url;
      dialog.querySelector('[data-wwp-position]').textContent = `${index + 1} / ${links.length}`;
    }
    [...allLinks, ...root.querySelectorAll('[data-wwp-preview]')].forEach((trigger) => trigger.addEventListener('click', (event) => {
      if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      const link = allLinks.includes(trigger) ? trigger : trigger.closest('.wwp-project')?.querySelector('[data-wwp-image]');
      if (!link) return;
      event.preventDefault();
      opener = trigger;
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
