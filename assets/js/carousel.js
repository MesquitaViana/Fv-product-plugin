/* ===== FVPH Carousel + Header spacer ===== */
(function () {
  function initCarousel(root) {
    const track  = root.querySelector('.fvph-track');
    const slides = Array.from(root.querySelectorAll('.fvph-slide'));
    if (!track || !slides.length) return;

    let index = 0, timer = null, locked = false;

    /* --- helpers --- */
    const update = () => {
      track.style.transform = 'translateX(' + (-index * 100) + '%)';
      // thumb ativa
      const thumbs = root.parentNode?.querySelectorAll('[data-fv-thumb]');
      thumbs && thumbs.forEach((b,i)=> b.classList.toggle('is-active', i===index));
    };

    const go = (i) => { index = (i + slides.length) % slides.length; update(); };
    const next = () => go(index + 1);
    const prev = () => go(index - 1);

    /* --- nav buttons --- */
    const prevBtn = root.querySelector('.fvph-nav.prev');
    const nextBtn = root.querySelector('.fvph-nav.next');
    prevBtn && prevBtn.addEventListener('click', () => { prev(); resetAuto(); });
    nextBtn && nextBtn.addEventListener('click', () => { next(); resetAuto(); });

    /* --- thumbs --- */
    const thumbsWrap = root.parentNode?.querySelector('.fvph-thumbs');
    if (thumbsWrap) {
      thumbsWrap.querySelectorAll('[data-fv-thumb]').forEach((btn, i) => {
        btn.setAttribute('tabindex','0');
        btn.addEventListener('click', () => { go(i); resetAuto(); });
        btn.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(i); resetAuto(); }
        });
      });
    }

    /* --- keyboard nav --- */
    root.setAttribute('tabindex', '0');
    root.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { next(); resetAuto(); }
      if (e.key === 'ArrowLeft')  { prev(); resetAuto(); }
    });

    /* --- swipe/pointer --- */
    let startX = 0, dx = 0, dragging = false;
    const onStart = (x) => { dragging = true; startX = x; dx = 0; locked = true; stopAuto(); };
    const onMove  = (x) => { if (!dragging) return; dx = x - startX; track.style.transform = 'translateX(' + (-index*100 + (dx/window.innerWidth)*100) + '%)'; };
    const onEnd   = () => {
      if (!dragging) return;
      dragging = false;
      const threshold = 40; // px
      if (dx < -threshold) next(); else if (dx > threshold) prev(); else update();
      locked = false; resetAuto();
    };

    root.addEventListener('touchstart', (e)=> onStart(e.touches[0].clientX), {passive:true});
    root.addEventListener('touchmove',  (e)=> onMove(e.touches[0].clientX),  {passive:true});
    root.addEventListener('touchend',   onEnd);

    root.addEventListener('pointerdown', (e)=> onStart(e.clientX));
    window.addEventListener('pointermove', (e)=> dragging && onMove(e.clientX));
    window.addEventListener('pointerup', onEnd);

    /* --- autoplay --- */
    const autoplay = root.getAttribute('data-autoplay') === '1';
    const interval = parseInt(root.getAttribute('data-interval') || '4000', 10);
    const startAuto = () => { if (autoplay && !timer && !locked) timer = setInterval(next, interval); };
    const stopAuto  = () => { if (timer) { clearInterval(timer); timer = null; } };
    const resetAuto = () => { stopAuto(); startAuto(); };

    root.addEventListener('mouseenter', stopAuto);
    root.addEventListener('mouseleave', startAuto);
    document.addEventListener('visibilitychange', () => {
      document.hidden ? stopAuto() : startAuto();
    });

    update(); startAuto();
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-fv-carousel]').forEach(initCarousel);
  });
})();

/* ===== Header spacer (Elementor header fixo) ===== */
(function(){
  const header = document.querySelector('.elementor-location-header');
  const main   = document.querySelector('.fvph-single');
  if (!header || !main) return;

  function setSpace(){
    const h = header.offsetHeight || 0;
    document.documentElement.style.setProperty('--fvph-header-h', h + 'px');
  }

  // inicial
  setSpace();

  // resize e scroll (caso o header mude de altura com sticky/shrink)
  window.addEventListener('resize', setSpace);
  window.addEventListener('scroll', setSpace, {passive:true});

  // observa mudanças estruturais no header (menus abrem, etc.)
  const mo = new MutationObserver(setSpace);
  mo.observe(header, { attributes:true, childList:true, subtree:true });
})();
