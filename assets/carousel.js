(() => {
  'use strict';
  document.querySelectorAll('[data-wwp-carousel]').forEach((root) => {
    const track = root.querySelector('[data-wwp-track]');
    const previous = root.querySelector('[data-wwp-carousel-prev]');
    const next = root.querySelector('[data-wwp-carousel-next]');
    const controls = root.querySelector('[data-wwp-carousel-controls]');
    const viewport = root.querySelector('[data-wwp-viewport]');
    if (typeof window.Swiper === 'function') {
      // Reuse the theme's Swiper bundle. Keep each instance and its controls local.
      viewport.classList.add('wwp-swiper-active');
      function sync(swiper) {
        controls.hidden = swiper.isLocked;
        previous.disabled = swiper.isBeginning || swiper.isLocked;
        next.disabled = swiper.isEnd || swiper.isLocked;
      }
      const swiper = new window.Swiper(viewport, {
        wrapperClass: 'wwp-carousel-track',
        slideClass: 'wwp-carousel-card',
        slidesPerView: 1,
        spaceBetween: 24,
        breakpointsBase: 'container',
        breakpoints: { 600: { slidesPerView: 2 }, 1000: { slidesPerView: Number(root.dataset.wwpColumns) || 4 } },
        loop: false,
        autoplay: false,
        grabCursor: true,
        watchOverflow: true,
        speed: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 300,
        a11y: { enabled: true },
        on: { init: sync, slideChange: sync, resize: sync, lock: sync, unlock: sync, breakpoint: sync }
      });
      previous.addEventListener('click', () => swiper.slidePrev());
      next.addEventListener('click', () => swiper.slideNext());
      // Do not let document-wide keyboard handlers affect another carousel or the popup.
      viewport.addEventListener('keydown', (event) => {
        if (event.target !== viewport || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        const forward = (event.key === 'ArrowRight') !== swiper.rtl;
        if (forward) swiper.slideNext();
        else swiper.slidePrev();
      });
      // Tabbing to an off-screen card brings it into view without moving page focus.
      track.addEventListener('focusin', (event) => {
        const card = event.target.closest('.wwp-carousel-card');
        const index = [...track.children].indexOf(card);
        if (index >= 0) swiper.slideTo(index);
      });
      return;
    }
    // Measure physical positions so navigation also works in RTL layouts.
    function position() {
      const bounds = track.getBoundingClientRect();
      const cards = [...track.children];
      const rtl = getComputedStyle(track).direction === 'rtl';
      const start = rtl ? bounds.right : bounds.left;
      const offset = (card) => rtl ? start - card.getBoundingClientRect().right : card.getBoundingClientRect().left - start;
      return { cards, offset };
    }
    function update() {
      const { cards, offset } = position();
      controls.hidden = track.scrollWidth <= track.clientWidth + 2;
      previous.disabled = offset(cards[0]) >= -2;
      const last = cards[cards.length - 1];
      next.disabled = offset(last) + last.getBoundingClientRect().width <= track.clientWidth + 2;
    }
    function move(direction) {
      const { cards, offset } = position();
      const target = direction > 0 ? (cards.find((card) => offset(card) >= track.clientWidth - 2) || cards[cards.length - 1]) : [...cards].reverse().find((card) => offset(card) < -2);
      if (target) target.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'nearest', inline: 'start' });
    }
    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    viewport.addEventListener('keydown', (event) => {
      if (event.target !== viewport || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault();
      const forward = (event.key === 'ArrowRight') !== (getComputedStyle(track).direction === 'rtl');
      move(forward ? 1 : -1);
    });
    track.addEventListener('scroll', update, { passive: true });
    if (typeof ResizeObserver === 'function') new ResizeObserver(update).observe(track);
    else window.addEventListener('resize', update);
    update();
  });

})();
