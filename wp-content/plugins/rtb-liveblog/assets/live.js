/* RTB Live Blog — polling micro-caché du fil de direct. */
(function () {
  var root = document.getElementById('rtb-live-post');
  if (!root || !window.rtbLive) return;
  var cfg = window.rtbLive;
  var id = root.getAttribute('data-live-id');
  var after = parseInt(root.getAttribute('data-after') || '0', 10);
  var stream = root.querySelector('.rtb-live-post-stream');
  var updated = root.querySelector('.rtb-live-post-updated');
  var keyBlock = root.querySelector('.rtb-live-post-key');
  var keyList = root.querySelector('.rtb-live-post-keys');

  // Bouton partage (natif si dispo, sinon copie du lien).
  var share = root.querySelector('.rtb-live-post-share');
  if (share) share.addEventListener('click', function () {
    var url = location.href, title = document.title;
    if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); return; }
    var done = function () {
      var t = share.textContent;
      share.textContent = (cfg.i18n && cfg.i18n.copied) || 'Lien copié';
      share.classList.add('rtb-live-post-share--ok');
      setTimeout(function () { share.textContent = t; share.classList.remove('rtb-live-post-share--ok'); }, 1800);
    };
    if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, done); else done();
  });

  if (root.getAttribute('data-status') !== 'open') return; // direct fermé → pas de polling

  function fmt(t) { var d = new Date(t * 1000); return ('0' + d.getHours()).slice(-2) + 'h' + ('0' + d.getMinutes()).slice(-2); }

  function el(e) {
    var art = document.createElement('article');
    art.className = 'rtb-live-post-entry rtb-live-post-entry--new' + (e.key ? ' rtb-live-post-entry--key' : '');
    art.id = 'rtb-live-post-e' + e.id;
    art.setAttribute('data-id', e.id);
    var tag = e.label ? '<span class="rtb-live-post-tag rtb-live-post-tag--' + e.label + '">' + String(e.label).toUpperCase() + '</span>' : '';
    var fig = e.img ? '<figure class="rtb-live-post-figure"><img src="' + e.img + '" alt="" loading="lazy"></figure>' : '';
    art.innerHTML = '<div class="rtb-live-post-meta"><time>' + fmt(e.t) + '</time>' + tag + '</div>' + fig + '<div class="rtb-live-post-body">' + e.html + '</div>';
    return art;
  }

  function addKey(e) {
    if (!keyList || !e.key) return;
    var li = document.createElement('li');
    li.innerHTML = '<a href="#rtb-live-post-e' + e.id + '">' + (e.text || '') + '</a>';
    keyList.insertBefore(li, keyList.firstChild);
    if (keyBlock) keyBlock.hidden = false;
  }

  var timer;
  function schedule() { clearTimeout(timer); timer = setTimeout(poll, cfg.interval || 8000); }

  function poll() {
    var headers = { 'Accept': 'application/json' };
    if (cfg.nonce) headers['X-WP-Nonce'] = cfg.nonce; // front même-origine
    if (cfg.apiKey) headers['X-RTB-Api-Key'] = cfg.apiKey;
    fetch(cfg.endpoint + id + '?after=' + after, { headers: headers, credentials: 'same-origin' })
      .then(function (r) {
        if (r.status === 401 || r.status === 403) return 'stop'; // pas d'autorisation → on arrête
        return r.ok ? r.json() : null;
      })
      .then(function (d) {
        if (d === 'stop') return;
        if (d) {
          if (d.entries && d.entries.length) {
            d.entries.slice().sort(function (a, b) { return a.id - b.id; }).forEach(function (e) {
              stream.insertBefore(el(e), stream.firstChild);
              addKey(e);
              if (e.id > after) after = e.id;
            });
          }
          if (updated && d.now) updated.textContent = (cfg.i18n.updated || '') + ' ' + fmt(d.now);
          if (d.status && d.status !== 'open') {
            var b = root.querySelector('.rtb-live-post-badge');
            if (b) { b.className = 'rtb-live-post-badge rtb-live-post-badge--ended'; b.textContent = cfg.i18n.ended; }
            return; // direct terminé → stop
          }
        }
        schedule();
      })
      .catch(function () { schedule(); });
  }

  schedule();
})();
