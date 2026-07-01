// MEDISELL — UI interactions
(function () {
  'use strict';

  // 모바일 드로어
  var drawer = document.getElementById('drawer');
  var openBtn = document.querySelector('.nav-toggle');
  var closeBtn = document.querySelector('.drawer .d-close');
  var scrim = document.querySelector('.drawer .scrim');
  function open() { drawer && drawer.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function close() { drawer && drawer.classList.remove('open'); document.body.style.overflow = ''; }
  openBtn && openBtn.addEventListener('click', open);
  closeBtn && closeBtn.addEventListener('click', close);
  scrim && scrim.addEventListener('click', close);

  // 전체 카테고리 메가패널
  var catBar = document.querySelector('.cat-bar');
  var allCat = document.querySelector('.all-cat');
  if (catBar && allCat) {
    allCat.addEventListener('click', function (e) {
      e.stopPropagation();
      catBar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!catBar.contains(e.target)) catBar.classList.remove('open');
    });
  }

  // 히어로 슬라이더
  var hero = document.querySelector('.hero');
  if (hero) {
    var slides = hero.querySelectorAll('.slide');
    var dots = hero.querySelectorAll('.hero-dots button');
    var idx = 0, timer;
    function go(i) {
      idx = (i + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('on', k === idx); });
      dots.forEach(function (d, k) { d.classList.toggle('on', k === idx); });
    }
    function next() { go(idx + 1); }
    function start() { if (slides.length > 1) timer = setInterval(next, 5000); }
    function stop() { clearInterval(timer); }
    dots.forEach(function (d, k) { d.addEventListener('click', function () { stop(); go(k); start(); }); });
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);
    go(0); start();
  }

  // 수량 +/- 컨트롤 (data-qty 컨테이너)
  document.querySelectorAll('.qty').forEach(function (box) {
    var input = box.querySelector('input');
    var dec = box.querySelector('[data-dec]');
    var inc = box.querySelector('[data-inc]');
    function clamp(v) { return Math.max(1, parseInt(v, 10) || 1); }
    dec && dec.addEventListener('click', function () { input.value = clamp(input.value) - 1 || 1; input.dispatchEvent(new Event('change')); });
    inc && inc.addEventListener('click', function () { input.value = clamp(input.value) + 1; input.dispatchEvent(new Event('change')); });
  });

  // 회원유형 라디오 카드 (회원가입)
  document.querySelectorAll('[data-radio-cards]').forEach(function (group) {
    var cards = group.querySelectorAll('.radio-card');
    cards.forEach(function (card) {
      card.addEventListener('click', function () {
        var radio = card.querySelector('input[type=radio]');
        if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change', { bubbles: true })); }
        cards.forEach(function (c) { c.classList.remove('on'); });
        card.classList.add('on');
      });
    });
  });

  // 사업자 입력 토글
  var memberRadios = document.querySelectorAll('input[name=member_type]');
  var bizFields = document.getElementById('biz-fields');
  function syncBiz() {
    var v = document.querySelector('input[name=member_type]:checked');
    if (bizFields) bizFields.style.display = (v && v.value === 'business') ? '' : 'none';
  }
  memberRadios.forEach(function (r) { r.addEventListener('change', syncBiz); });
  syncBiz();

  // 결제수단 토글 (체크아웃: 무통장 입력 표시/숨김)
  var payRadios = document.querySelectorAll('input[name=payment_method]');
  var bankFields = document.getElementById('bank-fields');
  var tossHint = document.getElementById('toss-hint');
  function syncPay() {
    var v = document.querySelector('input[name=payment_method]:checked');
    var isBank = v && v.value === 'bank';
    if (bankFields) bankFields.style.display = isBank ? '' : 'none';
    if (tossHint) tossHint.style.display = isBank ? 'none' : '';
  }
  payRadios.forEach(function (r) { r.addEventListener('change', syncPay); });
  syncPay();

  // 맨 위로
  var toTop = document.querySelector('.to-top');
  window.addEventListener('scroll', function () {
    if (toTop) toTop.classList.toggle('show', window.scrollY > 500);
  });
  toTop && toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
})();
