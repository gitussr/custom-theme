/**
 * Custom Theme - WooCommerce module script
 * Vanilla JavaScript, no dependencies. Enqueued only on WooCommerce pages
 * (see inc/woocommerce/class-woocommerce.php). Uses event delegation
 * throughout so listeners survive AJAX-driven DOM swaps without
 * duplicating.
 */

( function () {
	'use strict';

	var settings = window.customThemeWooCommerce || {};

	function ajax( action, data ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', settings.nonce );

		Object.keys( data || {} ).forEach( function ( key ) {
			body.append( key, data[ key ] );
		} );

		return fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} ).then( function ( response ) {
			return response.json();
		} );
	}

	function announce( message ) {
		var region = document.getElementById( 'custom-theme-notice' );

		if ( ! region || ! message ) {
			return;
		}

		region.textContent = message;
		region.classList.add( 'is-visible' );

		window.clearTimeout( region._hideTimeout );
		region._hideTimeout = window.setTimeout( function () {
			region.classList.remove( 'is-visible' );
		}, 4000 );
	}

	function applyFragments( fragments ) {
		if ( ! fragments ) {
			return;
		}

		Object.keys( fragments ).forEach( function ( selector ) {
			document.querySelectorAll( selector ).forEach( function ( el ) {
				el.outerHTML = fragments[ selector ];
			} );
		} );
	}

	/* ---------------------------------------------------------------
	 * AJAX Add to Cart - single product page (simple products) and
	 * Quick View. Variable/grouped products are left to submit their
	 * form normally; WooCommerce handles them natively.
	 * ------------------------------------------------------------- */

	function isSimpleProductForm( form ) {
		return (
			form &&
			! form.querySelector( '.variations' ) &&
			! form.querySelector( '.woocommerce-grouped-product-list' )
		);
	}

	function addToCart( productId, quantity, trigger ) {
		if ( ! productId ) {
			return Promise.resolve();
		}

		if ( trigger ) {
			trigger.disabled = true;
			trigger.classList.add( 'is-loading' );
		}

		return ajax( 'custom_theme_add_to_cart', {
			product_id: productId,
			quantity: quantity || 1,
		} )
			.then( function ( response ) {
				if ( response && response.success ) {
					applyFragments( response.data.fragments );
					announce( response.data.message );
				} else {
					announce( ( response && response.data && response.data.message ) || settings.i18n.error );
				}

				return response;
			} )
			.catch( function () {
				announce( settings.i18n.error );
			} )
			.finally( function () {
				if ( trigger ) {
					trigger.disabled = false;
					trigger.classList.remove( 'is-loading' );
				}
			} );
	}

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest( 'form.cart' );

		if ( ! form || ! isSimpleProductForm( form ) ) {
			return;
		}

		event.preventDefault();

		var productInput = form.querySelector( 'input[name="add-to-cart"], button[name="add-to-cart"]' );
		var quantityInput = form.querySelector( 'input[name="quantity"]' );
		var productId = productInput ? productInput.value : form.dataset.productId;

		addToCart( productId, quantityInput ? quantityInput.value : 1, form.querySelector( '[type="submit"]' ) );
	} );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.ajax-add-to-cart' );

		if ( ! button ) {
			return;
		}

		event.preventDefault();
		addToCart( button.dataset.productId, 1, button );
	} );

	/* ---------------------------------------------------------------
	 * Quick View
	 * ------------------------------------------------------------- */

	var quickViewModal;
	var quickViewLastFocused;

	function getQuickViewModal() {
		if ( quickViewModal ) {
			return quickViewModal;
		}

		quickViewModal = document.createElement( 'div' );
		quickViewModal.className = 'quick-view-modal';
		quickViewModal.setAttribute( 'role', 'dialog' );
		quickViewModal.setAttribute( 'aria-modal', 'true' );
		quickViewModal.setAttribute( 'aria-labelledby', 'quick-view-title' );
		quickViewModal.hidden = true;
		quickViewModal.innerHTML =
			'<div class="quick-view-backdrop"></div>' +
			'<div class="quick-view-dialog">' +
			'<button type="button" class="quick-view-close">' + ( settings.i18n.close || 'Close' ) + '</button>' +
			'<div class="quick-view-body"></div>' +
			'</div>';

		document.body.appendChild( quickViewModal );

		return quickViewModal;
	}

	function closeQuickView() {
		if ( ! quickViewModal || quickViewModal.hidden ) {
			return;
		}

		quickViewModal.hidden = true;

		if ( quickViewLastFocused ) {
			quickViewLastFocused.focus();
		}
	}

	function openQuickView( html ) {
		var modal = getQuickViewModal();
		modal.querySelector( '.quick-view-body' ).innerHTML = html;
		modal.hidden = false;
		modal.querySelector( '.quick-view-close' ).focus();
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.quick-view-button' );

		if ( ! button ) {
			return;
		}

		quickViewLastFocused = button;

		ajax( 'custom_theme_quick_view', { product_id: button.dataset.productId } ).then( function ( response ) {
			if ( response && response.success ) {
				openQuickView( response.data.html );
			} else {
				announce( ( response && response.data && response.data.message ) || settings.i18n.error );
			}
		} );
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! quickViewModal || quickViewModal.hidden ) {
			return;
		}

		if ( event.target.closest( '.quick-view-close' ) || event.target.closest( '.quick-view-backdrop' ) ) {
			closeQuickView();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( ! quickViewModal || quickViewModal.hidden ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			closeQuickView();
			return;
		}

		if ( 'Tab' !== event.key ) {
			return;
		}

		var focusable = quickViewModal.querySelectorAll( 'button, a[href], input, [tabindex]:not([tabindex="-1"])' );

		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	/* ---------------------------------------------------------------
	 * Mini Cart
	 * ------------------------------------------------------------- */

	function closeMiniCart() {
		var openToggle = document.querySelector( '.mini-cart-toggle[aria-expanded="true"]' );
		var panel = document.getElementById( 'mini-cart-panel' );

		if ( openToggle ) {
			openToggle.setAttribute( 'aria-expanded', 'false' );
		}

		if ( panel ) {
			panel.hidden = true;
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '.mini-cart-toggle' );

		if ( toggle ) {
			var panel = document.getElementById( 'mini-cart-panel' );
			var expanded = 'true' === toggle.getAttribute( 'aria-expanded' );

			toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );

			if ( panel ) {
				panel.hidden = expanded;
			}

			return;
		}

		var miniCart = document.querySelector( '.mini-cart' );

		if ( miniCart && ! miniCart.contains( event.target ) ) {
			closeMiniCart();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			closeMiniCart();
		}
	} );

	/* ---------------------------------------------------------------
	 * Shop Filters
	 * ------------------------------------------------------------- */

	var shopFilterForm = document.querySelector( '.shop-filters' );

	function runFilter( form, paged ) {
		var formData = new FormData( form );
		formData.set( 'action', 'custom_theme_filter_products' );
		formData.set( 'nonce', settings.nonce );
		formData.set( 'paged', paged || 1 );

		var container = document.querySelector( 'ul.products' );

		if ( container ) {
			container.setAttribute( 'aria-busy', 'true' );
		}

		return fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( response ) {
				if ( ! response || ! response.success || ! container ) {
					return;
				}

				container.outerHTML = response.data.products;

				var pagination = document.querySelector( '.woocommerce-pagination, .pagination' );
				if ( pagination ) {
					pagination.outerHTML = response.data.pagination;
				}

				announce( ( settings.i18n.filtered || '%d products found.' ).replace( '%d', response.data.count ) );

				var query = new URLSearchParams( formData );
				var url = new URL( window.location.href );
				url.search = query.toString();
				window.history.pushState( {}, '', url );
			} )
			.catch( function () {
				announce( settings.i18n.error );
			} )
			.finally( function () {
				if ( container ) {
					container.removeAttribute( 'aria-busy' );
				}
			} );
	}

	if ( shopFilterForm ) {
		shopFilterForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			runFilter( shopFilterForm );
		} );

		shopFilterForm.addEventListener( 'change', function ( event ) {
			if ( event.target.matches( 'input[type="checkbox"], input[type="number"]' ) ) {
				runFilter( shopFilterForm );
			}
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var pageLink = event.target.closest( '.woocommerce-pagination a, .pagination a' );

		if ( ! pageLink || ! shopFilterForm ) {
			return;
		}

		var url = new URL( pageLink.href );
		var paged = url.searchParams.get( 'paged' ) || 1;

		event.preventDefault();
		runFilter( shopFilterForm, paged );
	} );
} )();
