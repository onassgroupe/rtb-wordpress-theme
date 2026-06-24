/* ============================================================
   RTB — bascule thème clair / sombre
   ============================================================ */
function rtbToggleTheme() {
  var el = document.documentElement;
  var dark = el.getAttribute('data-theme') === 'dark';
  if (dark) { el.removeAttribute('data-theme'); }
  else { el.setAttribute('data-theme', 'dark'); }
  try { localStorage.setItem('rtb-theme', dark ? 'light' : 'dark'); } catch (e) {}
}

/* ============================================================
   RTB — formulaire de contact (AJAX)
   ============================================================ */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('rtb-contact-form');
    if (!form || typeof rtbData === 'undefined') return;
    var status = document.getElementById('rtb-form-status');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = form.querySelector('button[type=submit]');
      btn.disabled = true; status.textContent = 'Envoi en cours…'; status.className = 'rtb-form-status';
      var data = new FormData(form);
      data.append('action', 'rtb_contact');
      data.append('nonce', rtbData.nonce);
      fetch(rtbData.ajaxUrl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          status.textContent = res.data.message;
          status.className = 'rtb-form-status ' + (res.success ? 'is-ok' : 'is-err');
          if (res.success) form.reset();
        })
        .catch(function () { status.textContent = 'Une erreur est survenue. Réessayez.'; status.className = 'rtb-form-status is-err'; })
        .finally(function () { btn.disabled = false; });
    });
  });
})();

/* ============================================================
   RTB — header sticky (ombre au scroll) + barre live
   ============================================================ */
(function () {
  function onScroll() {
    var box = document.getElementById('rtb-topbox') || document.getElementById('rtb-header');
    if (box) {
      if (window.scrollY > 8) { box.classList.add('is-scrolled'); }
      else { box.classList.remove('is-scrolled'); }
    }
  }
  document.addEventListener('DOMContentLoaded', onScroll);
  window.addEventListener('scroll', onScroll, { passive: true });
})();

/* ============================================================
   RTB — composants Alpine.js
   ============================================================ */
document.addEventListener('alpine:init', function () {

  /* Consentement cookies (modale + personnalisation) */
  Alpine.data('rtbCookie', function () {
    return {
      open: false,
      custom: false,
      prefs: true, stats: true, ads: true, social: true, geo: true,
      init: function () {
        var c = null;
        try { c = JSON.parse(localStorage.getItem('rtb-consent')); } catch (e) {}
        if (c && typeof c === 'object') {
          this.prefs = !!c.prefs; this.stats = !!c.stats; this.ads = !!c.ads; this.social = !!c.social; this.geo = !!c.geo;
        } else {
          var self = this;
          setTimeout(function () { self.open = true; }, 700);
        }
      },
      persist: function (o) {
        o.necessary = true;
        o.date = new Date().toISOString();
        try { localStorage.setItem('rtb-consent', JSON.stringify(o)); } catch (e) {}
        this.open = false; this.custom = false;
      },
      _set: function (v) { this.prefs = this.stats = this.ads = this.social = this.geo = v; },
      acceptAll: function () { this._set(true); this.persist({ prefs: true, stats: true, ads: true, social: true, geo: true }); },
      refuseAll: function () { this._set(false); this.persist({ prefs: false, stats: false, ads: false, social: false, geo: false }); },
      saveChoices: function () { this.persist({ prefs: this.prefs, stats: this.stats, ads: this.ads, social: this.social, geo: this.geo }); }
    };
  });

  /* Horloge + date FR (topbar) */
  Alpine.data('rtbClock', function () {
    return {
      time: '',
      date: '',
      init: function () {
        this.update();
        this._t = setInterval(this.update.bind(this), 1000);
      },
      destroy: function () { clearInterval(this._t); },
      update: function () {
        var now = new Date();
        this.time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        var d = now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        this.date = d.charAt(0).toUpperCase() + d.slice(1);
      }
    };
  });

  /* Grille des programmes (EPG) */
  Alpine.data('rtbGrid', function () {
    return {
      data: window.__RTB_GRID || [],
      today: typeof window.__RTB_TODAY === 'number' ? window.__RTB_TODAY : 0,
      chan: 0,
      day: 0,
      nowMin: 0,
      init: function () {
        this.day = this.today;
        this.tick();
        this._t = setInterval(this.tick.bind(this), 30000);
      },
      destroy: function () { clearInterval(this._t); },
      tick: function () {
        var d = new Date();
        this.nowMin = d.getHours() * 60 + d.getMinutes();
      },
      _min: function (t) { var p = t.split(':'); return (+p[0]) * 60 + (+p[1]); },
      get isToday() { return this.day === this.today; },
      get currentIndex() {
        if (!this.isToday) return -1;
        var slots = this.data[this.chan] ? this.data[this.chan].slots : [];
        var idx = -1;
        for (var i = 0; i < slots.length; i++) {
          if (this._min(slots[i][0]) <= this.nowMin) idx = i; else break;
        }
        return idx;
      },
      isNow: function (i) { return i === this.currentIndex; },
      isPast: function (i) { return this.isToday && this.currentIndex > -1 && i < this.currentIndex; },
      isNext: function (i) { return this.isToday && i === this.currentIndex + 1; },
      get nowLabel() {
        var ci = this.currentIndex;
        var slots = this.data[this.chan] ? this.data[this.chan].slots : [];
        return ci > -1 && slots[ci] ? slots[ci][1] : 'Programmes terminés';
      }
    };
  });

  /* Page d'accueil : hero/rail + onglets JT + player radio */
  Alpine.data('rtbHome', function () {
    return {
      data: window.__RTB || { channels: [], editions: [], stations: [] },
      active: 0,
      jtTab: 'Tout',
      radioStation: 0,
      radioPlaying: false,
      pastHero: false,
      footerVisible: false,

      init: function () {
        var self = this;
        var onScroll = function () { self.pastHero = window.scrollY > (window.innerHeight * 0.62); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        // Masquer la barre live dès que le footer entre dans l'écran (sinon il masque les liens du footer)
        var footer = document.querySelector('.rtb-footer');
        if (footer && 'IntersectionObserver' in window) {
          self._io = new IntersectionObserver(function (entries) {
            self.footerVisible = entries[0].isIntersecting;
          }, { rootMargin: '0px 0px -60px 0px' });
          self._io.observe(footer);
        }
      },

      get channel() { return this.data.channels[this.active] || {}; },
      get hero() { return this.channel.hero || {}; },
      get heroCover() { return this.channel.cover || ''; },
      get heroName() { return this.channel.name || ''; },

      get tabs() {
        return ['Tout'].concat(this.data.cats || []);
      },
      get editions() {
        if (this.jtTab === 'Tout') return this.data.editions;
        return this.data.editions.filter(function (e) { return e.cat === this.jtTab; }.bind(this));
      },

      get station() { return this.data.stations[this.radioStation] || {}; },
      playRadio: function () {
        var a = this.$refs.rtbAudio;
        if (!a) { this.radioPlaying = true; return; }
        var src = (this.station && this.station.stream) || this.data.radioStream || '';
        if (!src) { this.radioPlaying = true; return; }
        if (a.dataset.src !== src) {
          a.src = src + (src.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
          a.dataset.src = src;
        }
        var self = this;
        a.play().then(function () { self.radioPlaying = true; })
                 .catch(function () { self.radioPlaying = false; });
      },
      toggleRadio: function () {
        var a = this.$refs.rtbAudio;
        if (a && this.radioPlaying) { a.pause(); this.radioPlaying = false; return; }
        this.playRadio();
      },
      selectStation: function (i) { this.radioStation = i; this.playRadio(); }
    };
  });
});
