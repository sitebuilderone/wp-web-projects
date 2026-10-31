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
  const empty = element(), filters = element(), reset = element();
  const nodes = { '[data-wwp-status]': status, '[data-wwp-empty]': empty, '.wwp-filters': filters, '.wwp-results': { classList: { add() {} } }, '[data-wwp-reset]': reset };
  return { cards, inputs, status, empty, reset, querySelector: (selector) => nodes[selector], querySelectorAll: (selector) => selector === '.wwp-project' ? cards : inputs };
}
const first = portfolio(), second = portfolio();
const image = {}, heading = {}, description = {}, position = {};
const prev = element(), next = element(), close = element();
let focused = false;
const links = [0, 1].map((id) => element({ href: `image-${id}.jpg`, dataset: { heading: `Heading ${id}`, description: `Description ${id}` }, querySelector: () => ({ alt: `Alt ${id}` }), focus: () => { focused = true; } }));
const dialogNodes = { img: image, '[data-wwp-heading]': heading, '[data-wwp-description]': description, '[data-wwp-position]': position, '[data-wwp-prev]': prev, '[data-wwp-next]': next, '[data-wwp-close]': close };
const dialog = element({ showModal() { this.open = true; }, close() { this.open = false; this.listeners.close(); }, querySelector: (selector) => dialogNodes[selector] });
const gallery = { querySelectorAll: () => links, querySelector: () => dialog };
vm.runInNewContext(fs.readFileSync('assets/frontend.js', 'utf8'), { document: { querySelectorAll: (selector) => selector === '[data-wwp-portfolio]' ? [first, second] : [gallery] } });
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
links[0].listeners.click({ preventDefault() {} });
assert.equal(dialog.open, true); assert.equal(image.alt, 'Alt 0');
next.listeners.click(); assert.equal(image.src, 'image-1.jpg');
next.listeners.click(); assert.equal(image.src, 'image-0.jpg');
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
