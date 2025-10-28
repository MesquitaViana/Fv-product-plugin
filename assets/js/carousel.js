(function(){
  function initCarousel(root){
    var track = root.querySelector('.fvph-track');
    var slides = root.querySelectorAll('.fvph-slide');
    if(!track || slides.length===0) return;
    var index = 0, timer=null;
    function update(){ track.style.transform = 'translateX(' + (-index*100) + '%)'; }
    function next(){ index = (index+1)%slides.length; update(); }
    function prev(){ index = (index-1+slides.length)%slides.length; update(); }
    var prevBtn = root.querySelector('.fvph-nav.prev');
    var nextBtn = root.querySelector('.fvph-nav.next');
    if(prevBtn) prevBtn.addEventListener('click', function(){ prev(); resetAuto(); });
    if(nextBtn) nextBtn.addEventListener('click', function(){ next(); resetAuto(); });
    // thumbs
    var thumbsContainer = root.parentNode.querySelector('.fvph-thumbs');
    if(thumbsContainer){
      thumbsContainer.querySelectorAll('[data-fv-thumb]').forEach(function(btn, i){
        btn.addEventListener('click', function(){ index=i; update(); resetAuto(); });
      });
    }
    // autoplay
    var autoplay = root.getAttribute('data-autoplay')==='1';
    var interval = parseInt(root.getAttribute('data-interval')||'4000',10);
    function startAuto(){ if(autoplay){ timer = setInterval(next, interval); } }
    function stopAuto(){ if(timer){ clearInterval(timer); timer=null; } }
    function resetAuto(){ stopAuto(); startAuto(); }
    root.addEventListener('mouseenter', stopAuto);
    root.addEventListener('mouseleave', startAuto);
    update(); startAuto();
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-fv-carousel]').forEach(initCarousel);
  });
})();