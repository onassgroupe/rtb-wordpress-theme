/**
 * RTB Assistant — widget de chat local (vanilla JS). Aucune dépendance.
 * Un même cœur « Chat » est monté à deux endroits : la bulle flottante et la page /assistant.
 */
( function () {
	'use strict';

	var CFG = window.RTB_CHAT || {};
	if ( ! CFG.ajax ) { return; }

	var STORE = 'rtb_chat_history_v1';

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* ---- Cœur de chat, lié à un conteneur (scope) ---- */
	function Chat( scope ) {
		this.scope   = scope;
		this.log     = scope.querySelector( '.rtb-bot-log' );
		this.input   = scope.querySelector( '.rtb-bot-input' );
		this.form    = scope.querySelector( '.rtb-bot-form' );
		this.greeted = false;
		this.restore();
		this.bind();
	}

	Chat.prototype.scrollDown = function () { this.log.scrollTop = this.log.scrollHeight; };

	/* Persistance (localStorage) : la conversation survit aux changements de page. */
	Chat.prototype.save = function () {
		try { localStorage.setItem( STORE, this.log.innerHTML.replace( /autoplay=1/g, 'autoplay=0' ) ); } catch ( e ) {}
	};
	Chat.prototype.restore = function () {
		try {
			var html = localStorage.getItem( STORE );
			if ( html ) { this.log.innerHTML = html; this.greeted = true; this.scrollDown(); return true; }
		} catch ( e ) {}
		return false;
	};
	Chat.prototype.clear = function () {
		try { localStorage.removeItem( STORE ); } catch ( e ) {}
		this.log.innerHTML = '';
		this.greeted = false;
		this.greet();
	};

	Chat.prototype.addUser = function ( text ) {
		var b = document.createElement( 'div' );
		b.className = 'rtb-bot-msg rtb-bot-msg--user';
		b.innerHTML = '<div class="rtb-bot-bubble">' + esc( text ) + '</div>';
		this.log.appendChild( b );
		this.scrollDown();
		this.save();
	};

	Chat.prototype.addBot = function ( html ) {
		var b = document.createElement( 'div' );
		b.className = 'rtb-bot-msg rtb-bot-msg--bot';
		b.innerHTML = '<span class="rtb-bot-ava"><i class="fa-solid fa-headset"></i></span><div class="rtb-bot-bubble">' + html + '</div>';
		this.log.appendChild( b );
		this.scrollDown();
		this.save();
		return b;
	};

	Chat.prototype.typing = function () {
		var b = document.createElement( 'div' );
		b.className = 'rtb-bot-msg rtb-bot-msg--bot rtb-bot-typing';
		b.innerHTML = '<span class="rtb-bot-ava"><i class="fa-solid fa-headset"></i></span>' +
			'<div class="rtb-bot-bubble"><span class="rtb-bot-dots"><i></i><i></i><i></i></span></div>';
		this.log.appendChild( b );
		this.scrollDown();
		return b;
	};

	Chat.prototype.greet = function () {
		if ( this.greeted ) { return; }
		this.greeted = true;
		// Si une intro est déjà rendue côté serveur (page /assistant), on ne double pas.
		if ( this.log && this.log.children.length ) { return; }
		var chips = ( CFG.suggestions || [] ).map( function ( s ) {
			return '<button type="button" class="rtb-bot-chip" data-rtb-ask="' + esc( s ) + '">' + esc( s ) + '</button>';
		} ).join( '' );
		this.addBot( '<p>Bonjour ! Je suis l\'assistant de la <strong>RTB</strong>. Posez-moi une question sur l\'actualité, les JT, les émissions ou le direct.</p>' +
			( chips ? '<div class="rtb-bot-suggest">' + chips + '</div>' : '' ) );
	};

	Chat.prototype.send = function ( text ) {
		var self = this;
		text = ( text || '' ).trim();
		if ( ! text ) { return; }
		// Retire l'intro (page /assistant) dès le premier message.
		var intro = this.log.querySelector( '.rtb-assist-intro' );
		if ( intro ) { intro.remove(); }
		this.addUser( text );
		this.input.value = '';
		var t = this.typing();

		var fd = new FormData();
		fd.append( 'action', 'rtb_chat' );
		fd.append( 'nonce', CFG.nonce );
		fd.append( 'message', text );

		fetch( CFG.ajax, { method: 'POST', body: fd, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( j ) {
				t.remove();
				self.addBot( ( j && j.success && j.data && j.data.html ) ? j.data.html : '<p>Désolé, une erreur est survenue. Réessayez.</p>' );
			} )
			.catch( function () {
				t.remove();
				self.addBot( '<p>Connexion interrompue. Réessayez dans un instant.</p>' );
			} );
	};

	Chat.prototype.bind = function () {
		var self = this;
		this.form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			self.send( self.input.value );
		} );

		var clearBtn = this.scope.querySelector( '.rtb-bot-clear' );
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function ( e ) { e.preventDefault(); self.clear(); self.focus(); } );
		}

		// Délégation : chips suggérés + lecture vidéo inline.
		this.log.addEventListener( 'click', function ( e ) {
			var chip = e.target.closest( '[data-rtb-ask]' );
			if ( chip ) { e.preventDefault(); self.send( chip.getAttribute( 'data-rtb-ask' ) ); return; }
			var vid = e.target.closest( '[data-rtb-video]' );
			if ( vid ) { e.preventDefault(); self.playVideo( vid ); }
		} );
	};

	Chat.prototype.playVideo = function ( el ) {
		var id = el.getAttribute( 'data-rtb-video' );
		if ( ! id ) { return; }
		var url  = el.getAttribute( 'data-rtb-url' ) || '';
		var wrap = document.createElement( 'div' );
		wrap.className = 'rtb-bot-embed';
		wrap.innerHTML =
			'<iframe src="https://www.youtube-nocookie.com/embed/' + encodeURIComponent( id ) + '?autoplay=1&rel=0" title="Vidéo RTB" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>' +
			( url ? '<a class="rtb-bot-embed-link" href="' + url + '"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ouvrir la page de l\'émission</a>' : '' );

		// Lecteur en grand SOUS la rangée de cartes (et on remplace un lecteur déjà ouvert).
		var row = el.closest( '.rtb-bot-cards' );
		if ( row ) {
			var nx = row.nextElementSibling;
			if ( nx && nx.classList && nx.classList.contains( 'rtb-bot-embed' ) ) { nx.remove(); }
			row.parentNode.insertBefore( wrap, row.nextSibling );
		} else {
			el.replaceWith( wrap );
		}
		this.scrollDown();
		this.save();
	};

	Chat.prototype.focus = function () {
		var self = this;
		setTimeout( function () { self.input && self.input.focus(); }, 60 );
	};

	/* ---- Montage : bulle flottante ---- */
	function mountFloating() {
		var root = document.getElementById( 'rtb-bot' );
		if ( ! root ) { return; }
		var panel  = root.querySelector( '.rtb-bot-panel' );
		var toggle = root.querySelector( '.rtb-bot-toggle' );
		var chat   = new Chat( panel );

		function open() {
			root.setAttribute( 'data-open', 'true' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			chat.greet();
			chat.focus();
		}
		function close() {
			root.setAttribute( 'data-open', 'false' );
			toggle.setAttribute( 'aria-expanded', 'false' );
		}
		toggle.addEventListener( 'click', function () {
			root.getAttribute( 'data-open' ) === 'true' ? close() : open();
		} );
		root.querySelector( '.rtb-bot-min' ).addEventListener( 'click', close );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && root.getAttribute( 'data-open' ) === 'true' ) { close(); }
		} );
	}

	/* ---- Montage : page dédiée /assistant ---- */
	function mountInline() {
		var inline = document.querySelector( '[data-rtb-chat-inline]' );
		if ( ! inline ) { return; }
		var chat = new Chat( inline );
		chat.greet();
		chat.focus();
	}

	function init() {
		mountFloating();
		mountInline();
	}

	if ( document.readyState !== 'loading' ) { init(); }
	else { document.addEventListener( 'DOMContentLoaded', init ); }
} )();
