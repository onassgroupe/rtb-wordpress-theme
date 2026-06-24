/**
 * RTB Search — recherche instantanée (au fil de la frappe).
 * Vanilla JS, sans dépendance. S'attache à tout <input data-rtb-instant>.
 */
( function () {
	'use strict';

	var CFG = window.RTB_SEARCH || {};
	if ( ! CFG.ajax ) { return; }

	var MIN = 2, DEBOUNCE = 180;

	function el( tag, cls, html ) {
		var n = document.createElement( tag );
		if ( cls ) { n.className = cls; }
		if ( html != null ) { n.innerHTML = html; }
		return n;
	}

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	// Surligne les termes de la requête dans un texte.
	function highlight( text, q ) {
		var safe = esc( text );
		var words = q.trim().split( /\s+/ ).filter( function ( w ) { return w.length >= 2; } );
		words.forEach( function ( w ) {
			var re = new RegExp( '(' + w.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + ')', 'ig' );
			safe = safe.replace( re, '<mark>$1</mark>' );
		} );
		return safe;
	}

	function InstantBox( input ) {
		this.input = input;
		this.form = input.closest( 'form' );
		this.timer = null;
		this.active = -1;
		this.items = [];
		this.lastQ = '';
		this.cache = {};

		var host = input.parentElement;
		if ( host && getComputedStyle( host ).position === 'static' ) {
			host.style.position = 'relative';
		}
		this.panel = el( 'div', 'rtb-instant', '' );
		this.panel.setAttribute( 'role', 'listbox' );
		( host || input.parentNode ).appendChild( this.panel );

		this.bind();
	}

	InstantBox.prototype.bind = function () {
		var self = this;
		this.input.setAttribute( 'autocomplete', 'off' );
		this.input.addEventListener( 'input', function () { self.onInput(); } );
		this.input.addEventListener( 'focus', function () { self.onInput(); } );
		this.input.addEventListener( 'keydown', function ( e ) { self.onKey( e ); } );
		document.addEventListener( 'click', function ( e ) {
			if ( ! self.panel.contains( e.target ) && e.target !== self.input ) { self.close(); }
		} );
	};

	InstantBox.prototype.onInput = function () {
		var self = this, q = this.input.value.trim();
		clearTimeout( this.timer );
		this.timer = setTimeout( function () { self.fetch( q ); }, DEBOUNCE );
	};

	function skeleton() {
		var row = '<div class="rtb-instant-item rtb-sk" aria-hidden="true">' +
			'<span class="rtb-instant-thumb rtb-sk-box"></span>' +
			'<span class="rtb-instant-body">' +
				'<span class="rtb-sk-line rtb-sk-line--xs"></span>' +
				'<span class="rtb-sk-line rtb-sk-line--lg"></span>' +
				'<span class="rtb-sk-line rtb-sk-line--sm"></span>' +
			'</span></div>';
		return '<div class="rtb-instant-list">' + row + row + row + row + '</div>';
	}

	InstantBox.prototype.fetch = function ( q ) {
		var self = this;
		this.lastQ = q;
		clearTimeout( this.skTimer );
		if ( this.cache[ q ] ) { this.panel.classList.remove( 'is-loading' ); return this.render( this.cache[ q ], q ); }

		if ( q.length >= MIN ) {
			this.open();
			var hasContent = !! this.panel.querySelector( '.rtb-instant-list, .rtb-instant-chips' );
			if ( hasContent ) {
				// Affinement : on garde les résultats précédents (légèrement estompés) → pas de flash.
				this.panel.classList.add( 'is-loading' );
			} else {
				// Premier chargement : skeleton seulement si la réponse tarde (> 130 ms) → anti-flash.
				this.skTimer = setTimeout( function () {
					if ( self.lastQ === q ) { self.panel.innerHTML = skeleton(); }
				}, 130 );
			}
		}

		var url = CFG.ajax + '?action=rtb_instant&nonce=' + encodeURIComponent( CFG.nonce ) + '&q=' + encodeURIComponent( q );
		fetch( url, { credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( j ) {
				clearTimeout( self.skTimer );
				self.panel.classList.remove( 'is-loading' );
				if ( ! j || ! j.success || self.lastQ !== q ) { return; } // réponse obsolète
				self.cache[ q ] = j.data;
				self.render( j.data, q );
			} )
			.catch( function () { clearTimeout( self.skTimer ); self.panel.classList.remove( 'is-loading' ); self.close(); } );
	};

	InstantBox.prototype.render = function ( data, q ) {
		this.active = -1;
		this.items = [];
		this.panel.innerHTML = '';

		// Mode amorce (trop court) → tendances.
		if ( q.length < MIN ) {
			var tr = data.trending || [];
			if ( ! tr.length ) { return this.close(); }
			this.panel.appendChild( el( 'div', 'rtb-instant-label', esc( CFG.i18n.trending ) ) );
			var wrap = el( 'div', 'rtb-instant-chips', '' );
			var self = this;
			tr.forEach( function ( term ) {
				var a = el( 'a', 'rtb-instant-chip', esc( term ) );
				a.href = CFG.home + '?s=' + encodeURIComponent( term );
				wrap.appendChild( a );
			} );
			this.panel.appendChild( wrap );
			return this.open();
		}

		var res = data.results || [];
		if ( ! res.length ) {
			this.panel.appendChild( el( 'div', 'rtb-instant-state', esc( CFG.i18n.empty ) + ' « ' + esc( q ) + ' »' ) );
			return this.open();
		}

		var box = this, list = el( 'div', 'rtb-instant-list', '' );
		res.forEach( function ( r ) {
			var a = el( 'a', 'rtb-instant-item', '' );
			a.href = r.url;
			a.setAttribute( 'role', 'option' );
			var thumb = r.thumb
				? '<span class="rtb-instant-thumb" style="background-image:url(\'' + esc( r.thumb ) + '\')"></span>'
				: '<span class="rtb-instant-thumb rtb-instant-thumb--ph"><i class="fa-solid ' + ( r.kind === 'emission' ? 'fa-play' : 'fa-newspaper' ) + '"></i></span>';
			a.innerHTML = thumb +
				'<span class="rtb-instant-body">' +
					'<span class="rtb-instant-kind rtb-instant-kind--' + esc( r.kind ) + '">' + esc( r.type ) + '</span>' +
					'<span class="rtb-instant-title">' + highlight( r.title, q ) + '</span>' +
					'<span class="rtb-instant-meta">' + esc( r.cat ) + ' · ' + esc( r.date ) + '</span>' +
				'</span>';
			list.appendChild( a );
			box.items.push( a );
		} );
		this.panel.appendChild( list );

		var all = el( 'a', 'rtb-instant-all', esc( CFG.i18n.all ) + ' <i class="fa-solid fa-arrow-right"></i>' );
		all.href = CFG.home + '?s=' + encodeURIComponent( q );
		this.panel.appendChild( all );
		this.items.push( all );

		this.open();
	};

	InstantBox.prototype.onKey = function ( e ) {
		if ( ! this.isOpen() || ! this.items.length ) {
			if ( e.key === 'Escape' ) { this.close(); }
			return;
		}
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault(); this.move( 1 );
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault(); this.move( -1 );
		} else if ( e.key === 'Enter' ) {
			if ( this.active >= 0 && this.items[ this.active ] ) {
				e.preventDefault(); window.location.href = this.items[ this.active ].href;
			}
			// sinon : on laisse le formulaire se soumettre normalement (recherche complète).
		} else if ( e.key === 'Escape' ) {
			this.close();
		}
	};

	InstantBox.prototype.move = function ( dir ) {
		this.items.forEach( function ( i ) { i.classList.remove( 'is-active' ); } );
		this.active += dir;
		if ( this.active < 0 ) { this.active = this.items.length - 1; }
		if ( this.active >= this.items.length ) { this.active = 0; }
		var node = this.items[ this.active ];
		node.classList.add( 'is-active' );
		if ( node.scrollIntoView ) { node.scrollIntoView( { block: 'nearest' } ); }
	};

	InstantBox.prototype.open = function () { this.panel.classList.add( 'is-open' ); };
	InstantBox.prototype.close = function () { this.panel.classList.remove( 'is-open' ); this.active = -1; };
	InstantBox.prototype.isOpen = function () { return this.panel.classList.contains( 'is-open' ); };

	function init() {
		var inputs = document.querySelectorAll( 'input[data-rtb-instant]' );
		Array.prototype.forEach.call( inputs, function ( i ) {
			if ( ! i.__rtbInstant ) { i.__rtbInstant = new InstantBox( i ); }
		} );
	}

	if ( document.readyState !== 'loading' ) { init(); }
	else { document.addEventListener( 'DOMContentLoaded', init ); }
} )();
