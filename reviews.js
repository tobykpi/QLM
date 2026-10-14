(() => {
  const root = document.querySelector('.qlm-carousel');
  if (!root) return;
  const cards = [...root.querySelectorAll('.qlm-review')];
  const stage = root.querySelector('.qlm-review-stage');
  const controls = root.querySelector('.qlm-review-controls');
  const count = document.getElementById('qlm-count');
  const play = document.getElementById('qlm-play');
  const status = document.getElementById('qlm-review-status');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  let index = 0, paused = reduced.matches, hovered = false, focused = false, visible = false, timer, pointer;
  const schedule = () => {
    clearTimeout(timer);
    play.textContent = paused ? 'Play slideshow' : 'Pause slideshow';
    if (paused || hovered || focused || !visible || document.hidden || cards[index].querySelector('details[open]')) return;
    timer = setTimeout(() => show(index + 1, false), 10000);
  };
  const show = (next, manual = true) => {
    const direction = next < index ? -1 : 1;
    cards[index].hidden = true;
    index = (next + cards.length) % cards.length;
    cards[index].hidden = false;
    count.textContent = `${index + 1} / ${cards.length}`;
    if (!reduced.matches) cards[index].animate([{opacity:0,transform:`translateX(${direction * 24}px)`},{opacity:1,transform:'translateX(0)'}],{duration:500,easing:'cubic-bezier(.22,1,.36,1)'});
    if (manual) status.textContent = `Review ${index + 1} of ${cards.length}: ${cards[index].querySelector('h3').textContent}`;
    schedule();
  };
  cards.forEach((card, i) => {card.hidden = i !== 0; card.querySelector('details')?.addEventListener('toggle', schedule);});
  controls.hidden = false;
  document.getElementById('qlm-prev').addEventListener('click', () => show(index - 1));
  document.getElementById('qlm-next').addEventListener('click', () => show(index + 1));
  play.addEventListener('click', () => {paused = !paused; schedule();});
  root.addEventListener('mouseenter', () => {hovered = true; schedule();});
  root.addEventListener('mouseleave', () => {hovered = false; schedule();});
  root.addEventListener('focusin', () => {focused = true; schedule();});
  root.addEventListener('focusout', () => {requestAnimationFrame(() => {focused = root.contains(document.activeElement); schedule();});});
  root.addEventListener('keydown', event => {
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {event.preventDefault(); show(index + (event.key === 'ArrowRight' ? 1 : -1));}
  });
  stage.addEventListener('pointerdown', event => {if(event.pointerType !== 'mouse') {pointer = {x:event.clientX,y:event.clientY};clearTimeout(timer);}});
  stage.addEventListener('pointerup', event => {
    if (!pointer) return;
    const dx = event.clientX - pointer.x, dy = event.clientY - pointer.y;
    pointer = null;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) show(index + (dx < 0 ? 1 : -1));
    else schedule();
  });
  stage.addEventListener('pointercancel', () => {pointer = null;schedule();});
  document.addEventListener('visibilitychange', schedule);
  reduced.addEventListener('change', () => {paused = reduced.matches;schedule();});
  new IntersectionObserver(entries => {visible = entries[0].isIntersecting;schedule();},{threshold:.15}).observe(root);
  schedule();
})();