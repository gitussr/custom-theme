/**
 * Custom Theme - main script
 * Vanilla JavaScript, no dependencies. Loaded deferred via wp_enqueue_script().
 * Currently handles: accessible mobile navigation toggle.
 */

( function () {
	'use strict';

	var nav = document.getElementById( 'site-navigation' );
	var toggle = nav ? nav.querySelector( '.menu-toggle' ) : null;

	if ( ! nav || ! toggle ) {
		return;
	}

	function closeMenu() {
		nav.classList.remove( 'is-active' );
		toggle.setAttribute( 'aria-expanded', 'false' );
	}

	function openMenu() {
		nav.classList.add( 'is-active' );
		toggle.setAttribute( 'aria-expanded', 'true' );
	}

	toggle.addEventListener( 'click', function () {
		if ( nav.classList.contains( 'is-active' ) ) {
			closeMenu();
		} else {
			openMenu();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && nav.classList.contains( 'is-active' ) ) {
			closeMenu();
			toggle.focus();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( nav.classList.contains( 'is-active' ) && ! nav.contains( event.target ) ) {
			closeMenu();
		}
	} );
} )();
