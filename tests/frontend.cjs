const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const element = (extra = {}) => ({ hidden: false, listeners: {}, addEventListener(name, fn) { this.listeners[name] = fn; }, ...extra });
function portfolio() {
  const cards = [
    { category: [1], feature: [10] }, { category: [2], feature: [20] }, { category: [1, 2], feature: [20] }
  ].map((terms) => element({ dataset: { wwpTerms: JSON.stringify(terms) } }));
  const inputs = [['category', 1], ['category', 2], ['feature', 10], ['feature', 20]].map(([taxonomy, id]) => {
    const count = element();
    return element({ dataset: { taxonomy }, value: String(id), checked: false, closest: () => ({ querySelector: () => count }), count });
  });
  const status = { dataset: { format: '%d projects' } };
  const allButtons = ['category', 'feature'].map((taxonomy) => element({ dataset: { wwpAll: taxonomy }, setAttribute(name, value) { this[name] = value; } }));
  const empty = element(), filters = element(), reset = element();
  const nodes = { '[data-wwp-status]': status, '[data-wwp-empty]': empty, '.wwp-filters': filters, '.wwp-results': { classList: { add() {} } }, '[data-wwp-reset]': reset };
  return { cards, inputs, allButtons, status, empty, reset, querySelector: (selector) => nodes[selector], querySelectorAll: (selector) => selector === '.wwp-project' ? cards : selector === '[data-wwp-all]' ? allButtons : inputs };
}
const first = portfolio(), second = portfolio();
const image = {}, heading = {}, description = {}, position = {};
const prev = element(), next = element(), close = element();
let focused = false;
const links = [0, 1].map((id) => element({ href: `image-${id}.jpg`, dataset: { heading: `Heading ${id}`, description: `Description ${id}`, website: id === 0 ? 'https://client.example.test/' : '' }, querySelector: () => ({ alt: `Alt ${id}` }), focus: () => { focused = true; } }));
const website = element({ removeAttribute(name) { delete this[name]; } });
const dialogNodes = { '[data-wwp-website]': website, img: image, '[data-wwp-heading]': heading, '[data-wwp-description]': description, '[data-wwp-position]': position, '[data-wwp-prev]': prev, '[data-wwp-next]': next, '[data-wwp-close]': close };
const dialog = element({ showModal() { this.open = true; }, close() { this.open = false; this.listeners.close(); }, querySelector: (selector) => dialogNodes[selector] });
const titleTrigger = element({ closest: () => ({ querySelector: () => links[1] }), focus() { this.focused = true; } });
const gallery = { querySelectorAll: (selector) => selector === '[data-wwp-image]' ? links : [titleTrigger], querySelector: () => dialog };
vm.runInNewContext(fs.readFileSync('assets/frontend.js', 'utf8'), { document: { querySelectorAll: (selector) => selector === '[data-wwp-portfolio]' ? [first, second] : selector === '[data-wwp-gallery]' ? [gallery] : [] } });
assert.equal(first.status.textContent, '3 projects');
first.inputs[0].checked = true; first.inputs[0].listeners.change();
assert.deepEqual(first.cards.map((c) => c.hidden), [false, true, false]);
first.inputs[3].checked = true; first.inputs[3].listeners.change();
assert.deepEqual(first.cards.map((c) => c.hidden), [true, true, false]);
assert.equal(first.inputs[2].count.textContent, '(1)');
first.inputs[1].checked = true; first.inputs[1].listeners.change();
assert.deepEqual(first.cards.map((c) => c.hidden), [true, false, false]);
assert.equal(second.status.textContent, '3 projects', 'Instances stay independent');
first.inputs[3].checked = false; first.inputs[2].checked = true; first.inputs[0].checked = false; first.inputs[2].listeners.change();
assert.equal(first.empty.hidden, false);
first.reset.listeners.click();
assert.equal(first.status.textContent, '3 projects');
assert(first.inputs.every((i) => !i.checked && !i.disabled));
first.inputs[0].checked = true; first.inputs[3].checked = true; first.inputs[0].listeners.change();
assert.equal(first.allButtons[0]['aria-pressed'], 'false');
first.allButtons[0].listeners.click();
assert.equal(first.allButtons[0]['aria-pressed'], 'true');
assert.equal(first.inputs[3].checked, true, 'Category Show All preserves the feature selection');
assert.deepEqual(first.cards.map((c) => c.hidden), [true, false, false]);
assert.equal(second.status.textContent, '3 projects', 'Show All stays within its instance');
first.allButtons[1].listeners.click();
assert.equal(first.status.textContent, '3 projects');
links[0].listeners.click({ preventDefault() {} });
assert.equal(dialog.open, true); assert.equal(image.alt, 'Alt 0');
assert.equal(description.textContent, 'Description 0', 'Popup shows the project excerpt');
assert.equal(website.hidden, false); assert.equal(website.href, 'https://client.example.test/');
next.listeners.click(); assert.equal(image.src, 'image-1.jpg');
assert.equal(website.hidden, true); assert.equal(website.href, undefined, 'Clear previous website URL when next project has none');
next.listeners.click(); assert.equal(image.src, 'image-0.jpg');
assert.equal(website.hidden, false, 'Restore website action when navigating back');
dialog.listeners.keydown({ key: 'ArrowLeft', preventDefault() {} });
assert.equal(heading.textContent, 'Heading 1'); assert.equal(position.textContent, '2 / 2');
close.listeners.click(); assert.equal(dialog.open, false); assert.equal(focused, true);
console.log('PASS: filtering OR/AND, counts, reset, empty state, instance isolation and lightbox navigation/focus');

links[0].closest = () => first.cards[0];
links[1].closest = () => first.cards[1];
first.cards[0].hidden = true;
links[1].listeners.click({ preventDefault() {} });
next.listeners.click();
assert.equal(image.src, 'image-1.jpg', 'Filtered-out project images are skipped');
close.listeners.click();

// Scroll geometry covers responsive sizing, partial swipes, boundaries and RTL.
function carousel(rtl = false) {
  let offset = 0;
  const previous = element(), next = element(), controls = element(), viewport = element({ classList: { add() {} } });
  const track = element({ clientWidth: 400, scrollWidth: 1000, getBoundingClientRect: () => ({ left: 0, right: 400 }) });
  const cards = Array.from({ length: 5 }, (_, index) => ({
    getBoundingClientRect: () => ({ left: rtl ? 200 - index * 200 + offset : index * 200 - offset, right: rtl ? 400 - index * 200 + offset : index * 200 + 200 - offset, width: 200 }),
    scrollIntoView(options) { offset = Math.min(index * 200, track.scrollWidth - track.clientWidth); track.lastOptions = options; track.listeners.scroll(); }
  }));
  track.children = cards;
  const nodes = { '[data-wwp-viewport]': viewport, '[data-wwp-track]': track, '[data-wwp-carousel-prev]': previous, '[data-wwp-carousel-next]': next, '[data-wwp-carousel-controls]': controls };
  return { viewport, dataset: { wwpColumns: '4' }, track, previous, next, controls, rtl, swipe(value) { offset = value; track.listeners.scroll(); }, querySelector: (selector) => nodes[selector] };
}
const carouselOne = carousel(), carouselTwo = carousel(true);
const resizeCallbacks = [];
vm.runInNewContext(fs.readFileSync('assets/carousel.js', 'utf8'), {
  window: {},
  document: { querySelectorAll: (selector) => selector === '[data-wwp-carousel]' ? [carouselOne, carouselTwo] : [] },
  getComputedStyle: (track) => ({ direction: track === carouselTwo.track ? 'rtl' : 'ltr' }),
  matchMedia: () => ({ matches: true }),
  ResizeObserver: class { constructor(callback) { resizeCallbacks.push(callback); } observe() {} }
});
assert.equal(carouselOne.previous.disabled, true);
assert.equal(carouselOne.next.disabled, false);
carouselOne.next.listeners.click();
assert.equal(carouselOne.previous.disabled, false);
assert.equal(carouselTwo.previous.disabled, true, 'Carousel instances stay independent');
carouselOne.next.listeners.click();
assert.equal(carouselOne.next.disabled, true, 'Stop at the final card');
assert.equal(carouselOne.track.lastOptions.behavior, 'instant', 'Respect reduced motion');
carouselOne.previous.listeners.click();
assert.equal(carouselOne.next.disabled, false);
carouselOne.swipe(550);
carouselOne.next.listeners.click();
assert.equal(carouselOne.next.disabled, true, 'Finish a partial swipe even when no card starts beyond the viewport');
carouselTwo.next.listeners.click();
assert.equal(carouselTwo.previous.disabled, false, 'RTL advances in reading order');
carouselTwo.next.listeners.click();
assert.equal(carouselTwo.next.disabled, true, 'RTL boundary');
carouselOne.track.clientWidth = 1000;
resizeCallbacks[0]();
assert.equal(carouselOne.controls.hidden, true, 'Hide unnecessary controls after resize');
console.log('PASS: carousel navigation, partial swipes, RTL, reduced motion, resizing and instance isolation');

const swiperOne = carousel(), swiperTwo = carousel();
const instances = [];
class ThemeSwiper {
  constructor(viewport, options) {
    this.options = options; this.viewport = viewport;
    this.isBeginning = true; this.isEnd = false; this.isLocked = false;
    instances.push(this); options.on.init(this);
  }
  slideNext() { this.isBeginning = false; this.isEnd = true; this.options.on.slideChange(this); }
  slidePrev() { this.isBeginning = true; this.isEnd = false; this.options.on.slideChange(this); }
  slideTo(index) { this.focusIndex = index; }
}
vm.runInNewContext(fs.readFileSync('assets/carousel.js', 'utf8'), {
  window: { Swiper: ThemeSwiper, matchMedia: () => ({ matches: true }) },
  document: { querySelectorAll: () => [swiperOne, swiperTwo] }
});
assert.equal(instances.length, 2);
assert.equal(instances[0].options.breakpointsBase, 'container');
assert.equal(instances[0].options.breakpoints[1000].slidesPerView, 4);
assert.equal(instances[0].options.speed, 0);
assert.equal(instances[0].options.loop, false, 'No cloned lightbox images');
assert.equal(swiperOne.previous.disabled, true);
swiperOne.next.listeners.click();
assert.equal(swiperOne.next.disabled, true);
assert.equal(swiperTwo.next.disabled, false, 'Swiper controls remain local');
swiperOne.previous.listeners.click();
assert.equal(swiperOne.previous.disabled, true);
swiperOne.viewport.listeners.keydown({ target: swiperOne.viewport, key: 'ArrowRight', preventDefault() {} });
assert.equal(swiperOne.next.disabled, true, 'Focused keyboard control drives Swiper');
instances[0].isLocked = true;
instances[0].options.on.lock(instances[0]);
assert.equal(swiperOne.controls.hidden, true);
swiperOne.track.listeners.focusin({ target: { closest: () => swiperOne.track.children[3] } });
assert.equal(instances[0].focusIndex, 3, 'Keyboard focus brings offscreen cards into view');
let preventedPopup = false;
links[0].listeners.click({ defaultPrevented: true, preventDefault() { preventedPopup = true; } });
assert.equal(preventedPopup, false, 'Swiper drag-suppressed clicks do not open the popup');
console.log('PASS: theme Swiper integration, contained breakpoints, keyboard/focus, reduced motion, local controls and drag-safe popup');

titleTrigger.listeners.click({ preventDefault() {} });
assert.equal(image.src, 'image-1.jpg', 'Title opens the corresponding image');
assert.equal(position.textContent, '1 / 1', 'Filtered gallery count excludes title triggers');
close.listeners.click();
assert.equal(titleTrigger.focused, true, 'Popup returns focus to title opener');
first.cards[0].hidden = false;
titleTrigger.listeners.click({ preventDefault() {} });
assert.equal(position.textContent, '2 / 2', 'Title triggers never duplicate popup images');
close.listeners.click();
console.log('PASS: project title popup, correct image sequence and title focus return');
