/**
 * Woo Zee Plugin — Frontend Script
 *
 * Depends on: jQuery, Swiper
 * wzpData.ajaxUrl and wzpData.nonce are available via wp_localize_script().
 *
 * @package WooZeePlugin
 * @version 1.0.0
 */

( function ( $, wzpData ) {
	'use strict';

	// ── Wishlist (localStorage fallback) ──────────────────────────────────────
	// Active only when YITH WooCommerce Wishlist is NOT present.
	// Persists an array of product IDs under the key 'wzp_wishlist'.
	// YITH renders its own buttons; our .wzp-wishlist buttons are the fallback.

	var WZP_Wishlist = {

		STORAGE_KEY: 'wzp_wishlist',

		/**
		 * Read current wishlist from localStorage.
		 * Returns array of objects: { id: number, added: ISO-string }
		 * Falls back gracefully from the old plain-array format.
		 * @returns {Array}
		 */
		getItems: function () {
			try {
				var raw = localStorage.getItem( this.STORAGE_KEY );
				if ( ! raw ) { return []; }
				var parsed = JSON.parse( raw );
				// Migrate legacy format (plain number array).
				if ( Array.isArray( parsed ) && parsed.length && typeof parsed[0] === 'number' ) {
					var now = new Date().toISOString();
					return parsed.map( function ( id ) { return { id: id, added: now }; } );
				}
				return Array.isArray( parsed ) ? parsed : [];
			} catch ( e ) {
				return [];
			}
		},

		/** Return plain product ID array for quick lookups. */
		getIds: function () {
			return this.getItems().map( function ( item ) { return item.id; } );
		},

		/**
		 * Persist the wishlist to localStorage.
		 * @param {Array} items
		 */
		saveItems: function ( items ) {
			try {
				localStorage.setItem( this.STORAGE_KEY, JSON.stringify( items ) );
			} catch ( e ) {}
		},

		/**
		 * Return true if a product ID is already wishlisted.
		 */
		has: function ( productId ) {
			return this.getIds().indexOf( productId ) !== -1;
		},

		/**
		 * Add a product ID (with current timestamp) to the wishlist.
		 */
		add: function ( productId ) {
			var items = this.getItems();
			if ( this.getIds().indexOf( productId ) === -1 ) {
				items.push( { id: productId, added: new Date().toISOString() } );
				this.saveItems( items );
			}
		},

		/**
		 * Remove a product ID from the wishlist.
		 */
		remove: function ( productId ) {
			var items = this.getItems().filter( function ( item ) {
				return item.id !== productId;
			} );
			this.saveItems( items );
		},

		/**
		 * Toggle wishlist membership and update the button's active state.
		 */
		toggle: function ( productId, $btn ) {
			if ( this.has( productId ) ) {
				this.remove( productId );
				$btn.removeClass( 'wzp-wishlist--active' )
				    .attr( 'aria-pressed', 'false' );
			} else {
				this.add( productId );
				$btn.addClass( 'wzp-wishlist--active' )
				    .attr( 'aria-pressed', 'true' );
				// Show toast with Add to Cart option
				var name     = $btn.data( 'product-name' ) || '';
				var cartUrl  = $btn.data( 'cart-url' )     || '';
				this.showToast( name, cartUrl );
			}
			this.updateCount();
		},

		/**
		 * Show wishlist-added toast notification.
		 */
		showToast: function ( name, cartUrl ) {
			var self = this;
			clearTimeout( self._toastTimer );

			var $toast = $( '.wzp-wl-toast' );
			if ( ! $toast.length ) {
				$toast = $( [
					'<div class="wzp-wl-toast" role="status" aria-live="polite">',
					  '<svg class="wzp-wl-toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
					    '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
					  '</svg>',
					  '<span class="wzp-wl-toast__text"></span>',
					  '<a class="wzp-wl-toast__cart ajax_add_to_cart add_to_cart_button" data-quantity="1" rel="nofollow">Add to Cart</a>',
					  '<button class="wzp-wl-toast__close" aria-label="Close">&times;</button>',
					'</div>'
				].join( '' ) );
				$( 'body' ).append( $toast );

				$toast.on( 'click', '.wzp-wl-toast__close', function () {
					$toast.removeClass( 'wzp-wl-toast--show' );
				} );
			}

			$toast.find( '.wzp-wl-toast__text' ).text( '\u2764 ' + ( name || 'Item' ) + ' saved!' );
			$toast.find( '.wzp-wl-toast__cart' ).attr( 'href', cartUrl );

			$toast.addClass( 'wzp-wl-toast--show' );
			self._toastTimer = setTimeout( function () {
				$toast.removeClass( 'wzp-wl-toast--show' );
			}, 4000 );
		},

		/**
		 * Update the navbar wishlist badge count.
		 */
		updateCount: function () {
			var count = this.getItems().length;
			$( '.wzp-nb__wishlist-count' ).text( count );
		},

		/**
		 * Sync the active class on every wishlist button on the page.
		 */
		syncButtons: function () {
			$( '.wzp-wishlist[data-product-id], .wzp-product-card__wishlist[data-product-id]' ).each( function () {
				var $btn      = $( this );
				var productId = parseInt( $btn.data( 'product-id' ), 10 );

				if ( WZP_Wishlist.has( productId ) ) {
					$btn.addClass( 'wzp-wishlist--active' )
					    .attr( 'aria-pressed', 'true' );
				} else {
					$btn.attr( 'aria-pressed', 'false' );
				}
			} );
			this.updateCount();
		},

		/**
		 * Bind click handler to all current and future wishlist buttons.
		 */
		bindEvents: function () {
			$( document ).on( 'click', '.wzp-wishlist[data-product-id], .wzp-product-card__wishlist[data-product-id]', function ( e ) {
				e.preventDefault();
				var $btn      = $( this );
				var productId = parseInt( $btn.data( 'product-id' ), 10 );

				if ( isNaN( productId ) || productId <= 0 ) { return; }

				WZP_Wishlist.toggle( productId, $btn );

				// If on the wishlist page, refresh the list after toggle.
				if ( $( '.wzp-wishlist-page' ).length ) {
					setTimeout( function () { WZP_Wishlist.loadPage(); }, 200 );
				}
			} );

			// Remove button inside wishlist page rows.
			$( document ).on( 'click', '.wzp-wl-row__remove', function () {
				var productId = parseInt( $( this ).data( 'product-id' ), 10 );
				if ( productId ) {
					WZP_Wishlist.remove( productId );
					WZP_Wishlist.updateCount();
					WZP_Wishlist.loadPage();
				}
			} );
		},

		/**
		 * Load wishlist page list via AJAX using stored items (IDs + dates).
		 */
		loadPage: function () {
			var self  = this;
			var $page = $( '.wzp-wishlist-page' );
			if ( ! $page.length ) { return; }

			var $wrap = $page.find( '.wzp-wishlist-page__wrap' );
			var items = this.getItems();

			if ( ! items.length ) {
				$wrap.html( '<p class="wzp-wishlist-page__empty">' + ( $page.data( 'empty' ) || 'Your wishlist is empty.' ) + '</p>' );
				return;
			}

			$wrap.html( '<div class="wzp-wishlist-page__loading"><span></span><span></span><span></span></div>' );

			$.post(
				wzpData.ajaxUrl,
				{ action: 'wzp_get_wishlist', nonce: wzpData.nonce, items: items },
				function ( res ) {
					if ( res.success ) {
						// Prune stale IDs (deleted / hidden products) from localStorage.
						if ( res.data.valid_ids && res.data.valid_ids.length !== undefined ) {
							var validIds = res.data.valid_ids.map( Number );
							var pruned   = self.getItems().filter( function ( item ) {
								return validIds.indexOf( item.id ) !== -1;
							} );
							self.saveItems( pruned );
						}
						if ( res.data.html ) {
							$wrap.html( res.data.html );
						} else {
							$wrap.html( '<p class="wzp-wishlist-page__empty">' + ( $page.data( 'empty' ) || 'Your wishlist is empty.' ) + '</p>' );
						}
						self.syncButtons();
					} else {
						$wrap.html( '<p class="wzp-wishlist-page__empty">' + ( $page.data( 'empty' ) || 'Your wishlist is empty.' ) + '</p>' );
					}
				}
			).fail( function () {
				$wrap.html( '<p class="wzp-wishlist-page__empty">Could not load wishlist.</p>' );
			} );
		},

		/**
		 * Initialise wishlist module.
		 */
		init: function () {
			this.bindEvents();
			this.syncButtons();
			this.loadPage();
		}
	};

	// ── Lookbook hotspots ─────────────────────────────────────────────────────
	//
	// Clicking a .wzp-hotspot__dot toggles .wzp-hotspot--open on its parent.
	// Clicking outside any hotspot closes all open ones.
	// aria-expanded on the button is kept in sync for accessibility.

	var WZP_Lookbook = {

		init: function () {
			if ( ! $( '.wzp-hotspot' ).length ) { return; }
			this.bindEvents();
		},

		bindEvents: function () {
			// Toggle open on dot click.
			$( document ).on( 'click', '.wzp-hotspot__dot', function ( e ) {
				e.stopPropagation();

				var $hotspot = $( this ).closest( '.wzp-hotspot' );
				var isOpen   = $hotspot.hasClass( 'wzp-hotspot--open' );

				// Close all open hotspots first.
				WZP_Lookbook.closeAll();

				// Re-open if it wasn't already open.
				if ( ! isOpen ) {
					$hotspot.addClass( 'wzp-hotspot--open' );
					$hotspot.find( '.wzp-hotspot__dot' ).attr( 'aria-expanded', 'true' );
				}
			} );

			// Prevent clicks inside the popup from closing it.
			$( document ).on( 'click', '.wzp-hotspot__popup', function ( e ) {
				e.stopPropagation();
			} );

			// Close all when clicking anywhere outside a hotspot.
			$( document ).on( 'click', function ( e ) {
				if ( ! $( e.target ).closest( '.wzp-hotspot' ).length ) {
					WZP_Lookbook.closeAll();
				}
			} );

			// Keyboard: Escape closes the active hotspot.
			$( document ).on( 'keydown', function ( e ) {
				if ( e.key === 'Escape' || e.keyCode === 27 ) {
					WZP_Lookbook.closeAll();
				}
			} );
		},

		/**
		 * Close every open hotspot and reset aria-expanded.
		 */
		closeAll: function () {
			$( '.wzp-hotspot--open' ).each( function () {
				$( this ).removeClass( 'wzp-hotspot--open' );
				$( this ).find( '.wzp-hotspot__dot' ).attr( 'aria-expanded', 'false' );
			} );
		}
	};

	// ── Navbar ────────────────────────────────────────────────────────────────
	//
	// Handles: search overlay, cart drawer (open/close/qty/remove),
	// mobile panel, desktop dropdowns, sticky hide-on-scroll behaviour.

	var WZP_Navbar = {

		$nav:       null,
		lastScrollY: 0,

		init: function () {
			this.$nav = $( '.wzp-nb' );
			if ( ! this.$nav.length ) { return; }
			this.bindSearch();
			this.bindCart();
			this.bindMobile();
			this.bindDropdowns();
			this.bindSticky();
		},

		// ── Search overlay ────────────────────────────────────────────────

		bindSearch: function () {
			var $overlay  = $( '.wzp-nb-search' );
			if ( ! $overlay.length ) { return; }

			var $input    = $overlay.find( '.wzp-nb-search__input' );
			var $results  = $overlay.find( '.wzp-nb-search__results' );
			var searchXHR = null;
			var delay     = null;

			function closeOverlay() {
				$overlay.attr( 'hidden', '' );
				$( '.wzp-nb__search-trigger' ).attr( 'aria-expanded', 'false' );
			}

			function showResults( html ) {
				$results.html( html ).removeAttr( 'hidden' );
				$overlay.removeClass( 'wzp-nb-search--loading' );
			}

			function doSearch( q ) {
				if ( q.length < 2 ) {
					$results.attr( 'hidden', '' ).html( '' );
					$overlay.removeClass( 'wzp-nb-search--loading' );
					return;
				}

				$overlay.addClass( 'wzp-nb-search--loading' );
				if ( searchXHR ) { searchXHR.abort(); }

				searchXHR = $.ajax( {
					url:      wzpData.ajaxUrl,
					method:   'GET',
					data:     { action: 'wzp_search', nonce: wzpData.nonce, q: q },
					success:  function ( res ) {
						if ( ! res.success ) { return; }
						var d        = res.data;
						var products = d.products || [];
						var html     = '';

						if ( products.length === 0 ) {
							html = '<span class="wzp-nb-search__no-results">' + wzpData.noResultsText + '</span>';
						} else {
							$.each( products, function ( i, p ) {
								html +=
									'<a class="wzp-nb-search__result-item" href="' + p.url + '">' +
										'<img class="wzp-nb-search__result-img" src="' + p.img + '" alt="" loading="lazy">' +
										'<span class="wzp-nb-search__result-info">' +
											( p.cat ? '<span class="wzp-nb-search__result-cat">' + p.cat + '</span>' : '' ) +
											'<span class="wzp-nb-search__result-name">' + p.name + '</span>' +
										'</span>' +
										'<span class="wzp-nb-search__result-price">' + p.price + '</span>' +
									'</a>';
							} );

							if ( d.total > products.length ) {
								html += '<a class="wzp-nb-search__view-all" href="' + d.search_url + '">' +
									wzpData.viewAllText.replace( '%d', d.total ) +
								'</a>';
							}
						}

						showResults( html );
					},
					error: function ( xhr ) {
						if ( xhr.statusText !== 'abort' ) {
							$overlay.removeClass( 'wzp-nb-search--loading' );
						}
					}
				} );
			}

			// Open
			$( document ).on( 'click', '.wzp-nb__search-trigger', function () {
				$overlay.removeAttr( 'hidden' );
				$input.focus();
				$( this ).attr( 'aria-expanded', 'true' );
				// Re-run search if input already has a value
				if ( $input.val().trim().length >= 2 ) { doSearch( $input.val().trim() ); }
			} );

			// Close
			$( document ).on( 'click', '.wzp-nb-search__close', closeOverlay );
			$( document ).on( 'keydown', function ( e ) {
				if ( ( e.key === 'Escape' || e.keyCode === 27 ) && ! $overlay.attr( 'hidden' ) ) {
					closeOverlay();
				}
			} );

			// Typing — debounced AJAX
			$input.on( 'input', function () {
				var q = $( this ).val().trim();
				clearTimeout( delay );
				delay = setTimeout( function () { doSearch( q ); }, 280 );
			} );

			// Trending tag click — fill input and search immediately
			$overlay.on( 'click', '.wzp-nb-search__tag', function () {
				var term = $( this ).data( 'term' );
				$input.val( term ).trigger( 'focus' );
				clearTimeout( delay );
				doSearch( term );
			} );
		},

		// ── Cart drawer ───────────────────────────────────────────────────

		bindCart: function () {
			var self     = this;
			var $drawer  = $( '.wzp-cart-drawer' );
			var $backdrop = $( '.wzp-cart-backdrop' );
			if ( ! $drawer.length ) { return; }

			function openCart() {
				$drawer.addClass( 'wzp-is-open' );
				$backdrop.addClass( 'wzp-is-open' );
				$( 'body' ).addClass( 'wzp-cart-open' );
				$( '.wzp-nb__cart-trigger' ).attr( 'aria-expanded', 'true' );
			}

			function closeCart() {
				$drawer.removeClass( 'wzp-is-open' );
				$backdrop.removeClass( 'wzp-is-open' );
				$( 'body' ).removeClass( 'wzp-cart-open' );
				$( '.wzp-nb__cart-trigger' ).attr( 'aria-expanded', 'false' );
			}

			$( document ).on( 'click', '.wzp-nb__cart-trigger', openCart );
			$( document ).on( 'click', '.wzp-cart-drawer__close, .wzp-cart-backdrop', closeCart );
			$( document ).on( 'keydown', function ( e ) {
				if ( e.key === 'Escape' || e.keyCode === 27 ) { closeCart(); }
			} );

			// Refresh cart drawer and update quick-add button after AJAX add-to-cart.
			$( document ).on( 'added_to_cart', function ( _e, _fragments, _hash, $button ) {
				// Remove WooCommerce's injected "View cart" anchor everywhere
				$( '.added_to_cart.wc-forward' ).remove();

				self.ajaxCart( 'wzp_get_cart', {} );
				openCart();

				// Swap the clicked quick-add button → "View Cart" (black)
				if ( $button && $button.length ) {
					$button
						.text( wzpData.viewCartText || 'View Cart' )
						.attr( 'href', wzpData.cartUrl || '#' )
						.addClass( 'wzp-quickadd--added' );
					$button.closest( '.wzp-product-card' ).addClass( 'wzp-card--added' );
				}
			} );

			// Qty buttons.
			$( document ).on( 'click', '.wzp-cart-qty-btn', function () {
				var $btn     = $( this );
				var $item    = $btn.closest( '.wzp-cart-item' );
				var cartKey  = $item.data( 'cart-key' );
				var action   = $btn.data( 'action' );
				var current  = parseInt( $item.find( '.wzp-cart-qty-val' ).text(), 10 ) || 1;
				var newQty   = action === 'plus' ? current + 1 : Math.max( 0, current - 1 );
				self.ajaxCart( 'wzp_update_cart', { cart_key: cartKey, quantity: newQty } );
			} );

			// Remove button.
			$( document ).on( 'click', '.wzp-cart-item__remove', function () {
				var cartKey = $( this ).closest( '.wzp-cart-item' ).data( 'cart-key' );
				self.ajaxCart( 'wzp_remove_cart_item', { cart_key: cartKey } );
			} );
		},

		ajaxCart: function ( action, extra ) {
			var $items = $( '.wzp-cart-drawer__items' );
			$items.addClass( 'wzp-cart-loading' );

			$.post(
				wzpData.ajaxUrl,
				$.extend( { action: action, nonce: wzpData.cartNonce }, extra ),
				function ( res ) {
					$items.removeClass( 'wzp-cart-loading' );
					if ( res.success ) {
						$items.html( res.data.html );
						WZP_Navbar.updateCartUI( res.data.count, res.data.subtotal );
					}
				}
			).fail( function () {
				$items.removeClass( 'wzp-cart-loading' );
			} );
		},

		updateCartUI: function ( count, subtotal ) {
			$( '.wzp-nb__cart-count' ).text( count );
			$( '.wzp-cart-drawer__count' ).text( '(' + count + ')' );
			$( '.wzp-cart-drawer__subtotal-val' ).html( subtotal );
		},

		// ── Mobile panel ──────────────────────────────────────────────────

		bindMobile: function () {
			var $panel = $( '#wzp-nb-mobile-panel' );
			if ( ! $panel.length ) { return; }

			function openPanel() {
				$panel.removeAttr( 'hidden' );
				$( '.wzp-nb__hamburger' ).attr( 'aria-expanded', 'true' );
				$( 'body' ).addClass( 'wzp-mobile-nav-open' );
			}

			function closePanel() {
				$panel.attr( 'hidden', '' );
				$( '.wzp-nb__hamburger' ).attr( 'aria-expanded', 'false' );
				$( 'body' ).removeClass( 'wzp-mobile-nav-open' );
			}

			$( document ).on( 'click', '.wzp-nb__hamburger', function () {
				$panel.attr( 'hidden' ) !== undefined && $panel.attr( 'hidden' ) !== false
					? openPanel() : closePanel();
			} );

			// Close button inside panel
			$( document ).on( 'click', '.wzp-nb-mobile__close', closePanel );

			// Close on backdrop click
			$( document ).on( 'click', function ( e ) {
				if ( ! $panel.attr( 'hidden' ) &&
				     ! $( e.target ).closest( '#wzp-nb-mobile-panel, .wzp-nb__hamburger' ).length ) {
					closePanel();
				}
			} );

			// Escape key
			$( document ).on( 'keydown', function ( e ) {
				if ( ( e.key === 'Escape' || e.keyCode === 27 ) && ! $panel.attr( 'hidden' ) ) {
					closePanel();
				}
			} );

			// Sub-menu toggle
			$( document ).on( 'click', '.wzp-nb-mobile__toggle', function () {
				var $btn = $( this );
				var $sub = $btn.siblings( '.wzp-nb-mobile__sub' );
				if ( $sub.attr( 'hidden' ) !== undefined ) {
					$sub.removeAttr( 'hidden' );
					$btn.attr( 'aria-expanded', 'true' );
				} else {
					$sub.attr( 'hidden', '' );
					$btn.attr( 'aria-expanded', 'false' );
				}
			} );
		},

		// ── Mega dropdown ─────────────────────────────────────────────────

		bindDropdowns: function () {
			$( document ).on( 'mouseenter', '.wzp-nb__item--has-mega', function () {
				var $item = $( this );
				clearTimeout( $item.data( 'wzp-close-timer' ) );
				$item.find( '.wzp-nb__mega' ).removeAttr( 'hidden' );
				$item.find( '> .wzp-nb__link' ).attr( 'aria-expanded', 'true' );
			} );

			$( document ).on( 'mouseleave', '.wzp-nb__item--has-mega', function () {
				var $item = $( this );
				var timer = setTimeout( function () {
					$item.find( '.wzp-nb__mega' ).attr( 'hidden', '' );
					$item.find( '> .wzp-nb__link' ).attr( 'aria-expanded', 'false' );
				}, 120 );
				$item.data( 'wzp-close-timer', timer );
			} );

			// Keep open when the cursor moves into the mega panel.
			$( document ).on( 'mouseenter', '.wzp-nb__mega', function () {
				var $item = $( this ).closest( '.wzp-nb__item--has-mega' );
				clearTimeout( $item.data( 'wzp-close-timer' ) );
			} );

			// Close all on Escape.
			$( document ).on( 'keydown', function ( e ) {
				if ( e.key === 'Escape' || e.keyCode === 27 ) {
					$( '.wzp-nb__mega' ).attr( 'hidden', '' );
					$( '.wzp-nb__item--has-mega > .wzp-nb__link' ).attr( 'aria-expanded', 'false' );
				}
			} );
		},

		// ── Sticky — hide on scroll down, show on scroll up ───────────────

		bindSticky: function () {
			var self = this;
			self.lastScrollY = window.scrollY || window.pageYOffset;

			$( window ).on( 'scroll.wzpNavbar', function () {
				var currentY = window.scrollY || window.pageYOffset;
				var navH     = self.$nav.outerHeight() || 80;

				if ( currentY <= navH ) {
					self.$nav.removeClass( 'wzp-nb--hidden' );
				} else if ( currentY > self.lastScrollY ) {
					self.$nav.addClass( 'wzp-nb--hidden' );    // scrolling down
				} else {
					self.$nav.removeClass( 'wzp-nb--hidden' );  // scrolling up
				}
				self.lastScrollY = currentY;
			} );
		}
	};

	// ── Product Detail ────────────────────────────────────────────────────────
	//
	// Handles: thumbnail gallery, qty +/-, variations, buy-it-now, share button.

	var WZP_ProductDetail = {

		init: function () {
			if ( ! $( '.wzp-pd' ).length ) { return; }
			this.bindGallery();
			this.bindQty();
			this.bindAddToBag();
			this.bindBuyNow();
			this.bindShare();
		},

		// ── Add To Bag (AJAX — opens cart drawer) ─────────────────────────

		bindAddToBag: function () {
			$( document ).on( 'submit', '.wzp-pd__form', function ( e ) {
				var $form   = $( this );
				var $btn    = $form.find( '.wzp-pd__atc-btn' );

				if ( $btn.hasClass( 'disabled' ) ) { return; }

				e.preventDefault();

				$btn.prop( 'disabled', true ).addClass( 'wzp-pd__atc-btn--loading' );

				$.ajax( {
					type: 'POST',
					url:  '/?wc-ajax=add_to_cart',
					data: $form.serialize(),
					success: function ( response ) {
						if ( response && ! response.error ) {
							$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $btn ] );
						}
					},
					complete: function () {
						$btn.prop( 'disabled', false ).removeClass( 'wzp-pd__atc-btn--loading' );
					}
				} );
			} );
		},

		// ── Thumbnail gallery ─────────────────────────────────────────────

		bindGallery: function () {
			$( document ).on( 'click', '.wzp-pd__thumb', function () {
				var $thumb  = $( this );
				var fullSrc = $thumb.data( 'full' );
				if ( ! fullSrc ) { return; }

				$thumb.closest( '.wzp-pd__thumbs' ).find( '.wzp-pd__thumb--active' )
					.removeClass( 'wzp-pd__thumb--active' );
				$thumb.addClass( 'wzp-pd__thumb--active' );

				var $mainImg = $thumb.closest( '.wzp-pd' ).find( '.wzp-pd__main-img' );
				$mainImg.addClass( 'wzp-pd__main-img--loading' );

				var img    = new Image();
				img.onload = function () {
					$mainImg.attr( 'src', fullSrc ).removeClass( 'wzp-pd__main-img--loading' );
				};
				img.src = fullSrc;
			} );
		},

		// ── Quantity +/- ──────────────────────────────────────────────────

		bindQty: function () {
			$( document ).on( 'click', '.wzp-pd__qty-btn', function () {
				var $btn    = $( this );
				var $input  = $btn.closest( '.wzp-pd__qty' ).find( '.wzp-pd__qty-input' );
				var current = parseInt( $input.val(), 10 ) || 1;
				var min     = parseInt( $input.attr( 'min' ), 10 ) || 1;
				var max     = parseInt( $input.attr( 'max' ), 10 ) || 9999;
				var action  = $btn.data( 'action' );

				if ( action === 'plus' && current < max ) {
					$input.val( current + 1 ).trigger( 'change' );
				} else if ( action === 'minus' && current > min ) {
					$input.val( current - 1 ).trigger( 'change' );
				}
			} );
		},

		// ── Buy It Now ────────────────────────────────────────────────────

		bindBuyNow: function () {
			$( document ).on( 'click', '.wzp-pd__buy-now', function () {
				var $btn         = $( this );
				var checkoutUrl  = $btn.data( 'checkout' );
				var $form        = $btn.closest( '.wzp-pd__form' );

				$btn.prop( 'disabled', true );

				// Use WooCommerce's wc-ajax endpoint to silently add then redirect.
				$.ajax( {
					type:     'POST',
					url:      '/?wc-ajax=add_to_cart',
					data:     $form.serialize(),
					complete: function () {
						window.location.href = checkoutUrl;
					}
				} );
			} );
		},

		// ── Share ─────────────────────────────────────────────────────────

		bindShare: function () {
			$( document ).on( 'click', '.wzp-pd__share-btn', function () {
				var $btn      = $( this );
				var url       = $btn.data( 'url' );
				var title     = $btn.data( 'title' );
				var $confirm  = $btn.siblings( '.wzp-pd__share-confirm' );

				if ( navigator.share ) {
					navigator.share( { title: title, url: url } );
				} else if ( navigator.clipboard ) {
					navigator.clipboard.writeText( url ).then( function () {
						$confirm.removeAttr( 'hidden' );
						setTimeout( function () { $confirm.attr( 'hidden', '' ); }, 2000 );
					} );
				}
			} );
		}
	};

	// ── Shop Page ─────────────────────────────────────────────────────────────
	//
	// Handles: sidebar toggle (mobile), filter checkboxes, price range slider,
	// sort dropdown, view toggle (grid/list), AJAX filtering, URL state sync,
	// active chip removal, pagination, structured data update.

	var WZP_Shop = {

		$shop:     null,
		$sidebar:  null,
		$backdrop: null,
		$grid:     null,
		$overlay:  null,
		searchTimer: null,

		init: function () {
			var self = this;
			self.$shop = $( '.wzp-shop[data-wzp-module="shop"]' );
			if ( ! self.$shop.length ) { return; }

			self.$sidebar  = self.$shop.find( '#wzp-shop-sidebar' );
			self.$backdrop = self.$shop.find( '.wzp-shop__backdrop' );
			self.$grid     = self.$shop.find( '#wzp-shop-grid' );
			self.$overlay  = self.$shop.find( '.wzp-shop__loading-overlay' );

			self.initPriceRange();
			self.bindSidebar();
			self.bindSort();
			self.bindViewToggle();
			self.bindFilters();
			self.bindChips();
			self.bindPagination();
			self.bindSearch();
		},

		// ── Sidebar (mobile open / close) ─────────────────────────────────────

		bindSidebar: function () {
			var self = this;

			self.$shop.on( 'click', '.wzp-shop__filter-toggle', function () {
				self.openSidebar();
			} );

			self.$shop.on( 'click', '.wzp-shop__sidebar-close', function () {
				self.closeSidebar();
			} );

			self.$backdrop.on( 'click', function () {
				self.closeSidebar();
			} );

			$( document ).on( 'keydown', function ( e ) {
				if ( ( e.key === 'Escape' || e.keyCode === 27 ) && self.$sidebar.hasClass( 'wzp-shop--open' ) ) {
					self.closeSidebar();
				}
			} );
		},

		openSidebar: function () {
			this.$sidebar.addClass( 'wzp-shop--open' );
			this.$backdrop.addClass( 'wzp-shop--open' );
			this.$sidebar.find( '.wzp-shop__sidebar-close' ).focus();
			this.$shop.find( '.wzp-shop__filter-toggle' ).attr( 'aria-expanded', 'true' );
		},

		closeSidebar: function () {
			this.$sidebar.removeClass( 'wzp-shop--open' );
			this.$backdrop.removeClass( 'wzp-shop--open' );
			this.$shop.find( '.wzp-shop__filter-toggle' ).attr( 'aria-expanded', 'false' ).focus();
		},

		// ── Price range dual-thumb slider ─────────────────────────────────────

		initPriceRange: function () {
			var self  = this;
			var $wrap = self.$shop.find( '.wzp-price-range' );
			if ( ! $wrap.length ) { return; }

			var $min   = $wrap.find( '.wzp-price-range__input--min' );
			var $max   = $wrap.find( '.wzp-price-range__input--max' );
			var $fill  = $wrap.find( '.wzp-price-range__fill' );
			var $valMin = $wrap.find( '.wzp-price-range__val-min' );
			var $valMax = $wrap.find( '.wzp-price-range__val-max' );

			var absMin = parseFloat( $wrap.data( 'min' ) ) || 0;
			var absMax = parseFloat( $wrap.data( 'max' ) ) || 10000;

			function updateFill() {
				var lo = parseFloat( $min.val() );
				var hi = parseFloat( $max.val() );
				if ( lo > hi ) { return; }
				var pct  = ( absMax - absMin ) || 1;
				var left = ( ( lo - absMin ) / pct ) * 100;
				var right = ( ( absMax - hi ) / pct ) * 100;
				$fill.css( { left: left + '%', right: right + '%' } );
				$valMin.text( lo.toLocaleString() );
				$valMax.text( hi.toLocaleString() );
			}

			$min.on( 'input', function () {
				var lo = parseFloat( $min.val() );
				var hi = parseFloat( $max.val() );
				if ( lo > hi ) { $min.val( hi ); }
				updateFill();
			} );

			$max.on( 'input', function () {
				var lo = parseFloat( $min.val() );
				var hi = parseFloat( $max.val() );
				if ( hi < lo ) { $max.val( lo ); }
				updateFill();
			} );

			updateFill();
		},

		// ── Sort dropdown ─────────────────────────────────────────────────────

		bindSort: function () {
			var self = this;
			self.$shop.on( 'change', '.wzp-shop__sort', function () {
				self.doFilter( { page: 1 } );
			} );
		},

		bindViewToggle: function () {},

		// ── Filter controls (category, price, sale, apply) ────────────────────

		bindFilters: function () {
			var self = this;

			// Apply button.
			self.$shop.on( 'click', '.wzp-shop__apply-btn', function () {
				self.closeSidebar();
				self.doFilter( { page: 1 } );
			} );

			// Reset buttons (both inside sidebar and inside empty state).
			self.$shop.on( 'click', '.wzp-shop__reset-btn', function () {
				self.resetFilters();
			} );

			// Category parent checkbox reveals children.
			self.$shop.on( 'change', '.wzp-shop__cat-item--has-children > .wzp-shop__cat-label .wzp-shop__cat-check', function () {
				var $children = $( this ).closest( '.wzp-shop__cat-item' ).find( '.wzp-shop__cat-children' );
				if ( this.checked ) {
					$children.removeAttr( 'hidden' );
				}
			} );
		},

		// ── Active filter chip removal ────────────────────────────────────────

		bindChips: function () {
			var self = this;

			self.$shop.on( 'click', '.wzp-shop__chip-remove', function () {
				var $chip = $( this ).closest( '.wzp-shop__chip' );
				var type  = $chip.data( 'chip-type' );
				var slug  = $chip.data( 'chip-slug' );

				if ( type === 'cat' && slug ) {
					self.$sidebar.find( '.wzp-shop__cat-check[value="' + slug + '"]' ).prop( 'checked', false );
				} else if ( type === 'price' ) {
					var $wrap  = self.$sidebar.find( '.wzp-price-range' );
					var absMin = parseFloat( $wrap.data( 'min' ) ) || 0;
					var absMax = parseFloat( $wrap.data( 'max' ) ) || 10000;
					$wrap.find( '.wzp-price-range__input--min' ).val( absMin ).trigger( 'input' );
					$wrap.find( '.wzp-price-range__input--max' ).val( absMax ).trigger( 'input' );
				} else if ( type === 'sale' ) {
					self.$sidebar.find( '.wzp-shop__on-sale' ).prop( 'checked', false );
				} else if ( type === 'search' ) {
					self.$sidebar.find( '.wzp-shop__search-input' ).val( '' );
				}

				self.doFilter( { page: 1 } );
			} );

			self.$shop.on( 'click', '.wzp-shop__clear-all', function () {
				self.resetFilters();
			} );
		},

		// ── AJAX-powered pagination ───────────────────────────────────────────

		bindPagination: function () {
			var self = this;
			self.$shop.on( 'click', '.wzp-shop__page-btn:not(.wzp-shop__page-btn--active)', function () {
				var page = parseInt( $( this ).data( 'page' ), 10 );
				if ( page ) {
					self.doFilter( { page: page } );
					window.scrollTo( { top: self.$shop.offset().top - 80, behavior: 'smooth' } );
				}
			} );
		},

		// ── Live search (debounced 400ms) ─────────────────────────────────────

		bindSearch: function () {
			var self = this;
			self.$shop.on( 'input', '.wzp-shop__search-input', function () {
				clearTimeout( self.searchTimer );
				self.searchTimer = setTimeout( function () {
					self.doFilter( { page: 1 } );
				}, 400 );
			} );
		},

		// ── Collect current filter state ──────────────────────────────────────

		collectFilters: function ( overrides ) {
			var self    = this;
			var filters = {};

			// Sort.
			filters.orderby = self.$shop.find( '.wzp-shop__sort' ).val() || 'date';

			// Categories (checked).
			filters.cats = [];
			self.$sidebar.find( '.wzp-shop__cat-check:checked' ).each( function () {
				filters.cats.push( $( this ).val() );
			} );

			// Price range.
			var $wrap = self.$sidebar.find( '.wzp-price-range' );
			if ( $wrap.length ) {
				var absMin = parseFloat( $wrap.data( 'min' ) ) || 0;
				var absMax = parseFloat( $wrap.data( 'max' ) ) || 10000;
				var lo = parseFloat( $wrap.find( '.wzp-price-range__input--min' ).val() );
				var hi = parseFloat( $wrap.find( '.wzp-price-range__input--max' ).val() );
				filters.min_price = ( lo > absMin ) ? lo : 0;
				filters.max_price = ( hi < absMax ) ? hi : 0;
			}

			// On sale.
			filters.on_sale = self.$sidebar.find( '.wzp-shop__on-sale' ).is( ':checked' ) ? 1 : 0;

			// Search.
			filters.search = self.$sidebar.find( '.wzp-shop__search-input' ).val() || '';

			// Static options from data attributes.
			filters.per_page = parseInt( self.$shop.data( 'per-page' ), 10 ) || 12;
			filters.columns  = parseInt( self.$shop.data( 'columns' ), 10 ) || 4;
			filters.nonce    = self.$shop.data( 'nonce' );

			return $.extend( { page: 1 }, filters, overrides || {} );
		},

		// ── Fire AJAX filter request ──────────────────────────────────────────

		doFilter: function ( overrides ) {
			var self    = this;
			var filters = self.collectFilters( overrides );

			// Push URL state.
			var params = new URLSearchParams();
			if ( filters.cats && filters.cats.length ) {
				filters.cats.forEach( function ( c ) { params.append( 'wzp_cats[]', c ); } );
			}
			if ( filters.min_price ) { params.set( 'wzp_min', filters.min_price ); }
			if ( filters.max_price ) { params.set( 'wzp_max', filters.max_price ); }
			if ( filters.orderby && filters.orderby !== 'date' ) { params.set( 'wzp_sort', filters.orderby ); }
			if ( filters.on_sale ) { params.set( 'wzp_sale', '1' ); }
			if ( filters.search ) { params.set( 'wzp_s', filters.search ); }
			if ( filters.page > 1 ) { params.set( 'wzp_page', filters.page ); }

			var newUrl = window.location.pathname + ( params.toString() ? '?' + params.toString() : '' );
			history.pushState( { wzp_filters: filters }, '', newUrl );

			// Show loading.
			self.$grid.attr( 'aria-busy', 'true' );
			self.$overlay.addClass( 'wzp-shop--is-loading' );

			$.post(
				wzpData.ajaxUrl,
				$.extend( { action: 'wzp_shop_filter' }, filters ),
				function ( res ) {
					self.$grid.attr( 'aria-busy', 'false' );
					self.$overlay.removeClass( 'wzp-shop--is-loading' );

					if ( ! res.success ) { return; }

					// Update grid.
					self.$grid.html( res.data.grid );

					// Update pagination.
					self.$shop.find( '#wzp-shop-pagination' ).html( res.data.pagination );

					// Update results count.
					self.$shop.find( '.wzp-shop__results-count' ).text( res.data.count_text );

					// Update active chips.
					self.renderChips( filters );

					// Update JSON-LD.
					if ( res.data.ld_json ) {
						var $existing = $( 'script[data-wzp-shop-ld]' );
						if ( $existing.length ) {
							$existing.text( res.data.ld_json );
						} else {
							$( '<script type="application/ld+json" data-wzp-shop-ld>' + res.data.ld_json + '<\/script>' ).appendTo( 'head' );
						}
					}

					// Re-sync wishlist hearts.
					if ( window.WZP_Wishlist ) { WZP_Wishlist.syncButtons(); }
				}
			).fail( function () {
				self.$grid.attr( 'aria-busy', 'false' );
				self.$overlay.removeClass( 'wzp-shop--is-loading' );
			} );
		},

		// ── Re-render active filter chips from current filters ────────────────

		renderChips: function ( filters ) {
			var self  = this;
			var chips = [];

			// Category chips.
			if ( filters.cats && filters.cats.length ) {
				filters.cats.forEach( function ( slug ) {
					var $label = self.$sidebar.find( '.wzp-shop__cat-check[value="' + slug + '"]' )
						.closest( '.wzp-shop__cat-label' ).find( 'span' ).first();
					var name = $label.text().trim() || slug;
					chips.push( '<span class="wzp-shop__chip" data-chip-type="cat" data-chip-slug="' + self.esc( slug ) + '" role="listitem">' + self.esc( name ) + '<button type="button" class="wzp-shop__chip-remove" aria-label="Remove filter">&#215;</button></span>' );
				} );
			}

			// Price chip.
			var $wrap  = self.$sidebar.find( '.wzp-price-range' );
			var absMin = parseFloat( $wrap.data( 'min' ) ) || 0;
			var absMax = parseFloat( $wrap.data( 'max' ) ) || 10000;
			if ( ( filters.min_price && filters.min_price > absMin ) || ( filters.max_price && filters.max_price < absMax && filters.max_price > 0 ) ) {
				var lo  = filters.min_price || absMin;
				var hi  = filters.max_price || absMax;
				var lbl = 'Rs ' + lo.toLocaleString() + ' \u2013 Rs ' + hi.toLocaleString();
				chips.push( '<span class="wzp-shop__chip" data-chip-type="price" role="listitem">' + lbl + '<button type="button" class="wzp-shop__chip-remove" aria-label="Remove filter">&#215;</button></span>' );
			}

			// Sale chip.
			if ( filters.on_sale ) {
				chips.push( '<span class="wzp-shop__chip" data-chip-type="sale" role="listitem">On Sale<button type="button" class="wzp-shop__chip-remove" aria-label="Remove filter">&#215;</button></span>' );
			}

			// Search chip.
			if ( filters.search ) {
				chips.push( '<span class="wzp-shop__chip" data-chip-type="search" role="listitem">&ldquo;' + self.esc( filters.search ) + '&rdquo;<button type="button" class="wzp-shop__chip-remove" aria-label="Remove filter">&#215;</button></span>' );
			}

			var $container = self.$shop.find( '.wzp-shop__active-filters' );
			if ( chips.length ) {
				if ( ! $container.length ) {
					self.$shop.find( '.wzp-shop__topbar' ).after( '<div class="wzp-shop__active-filters" role="list" aria-label="Active filters"></div>' );
					$container = self.$shop.find( '.wzp-shop__active-filters' );
				}
				$container.html( chips.join( '' ) + '<button type="button" class="wzp-shop__clear-all">Clear all</button>' );
			} else {
				$container.empty();
			}
		},

		// ── Reset all filters ─────────────────────────────────────────────────

		resetFilters: function () {
			var self = this;

			// Uncheck all categories.
			self.$sidebar.find( '.wzp-shop__cat-check' ).prop( 'checked', false );

			// Reset price slider.
			var $wrap  = self.$sidebar.find( '.wzp-price-range' );
			var absMin = parseFloat( $wrap.data( 'min' ) ) || 0;
			var absMax = parseFloat( $wrap.data( 'max' ) ) || 10000;
			$wrap.find( '.wzp-price-range__input--min' ).val( absMin ).trigger( 'input' );
			$wrap.find( '.wzp-price-range__input--max' ).val( absMax ).trigger( 'input' );

			// Uncheck sale.
			self.$sidebar.find( '.wzp-shop__on-sale' ).prop( 'checked', false );

			// Clear search.
			self.$sidebar.find( '.wzp-shop__search-input' ).val( '' );

			// Reset sort.
			self.$shop.find( '.wzp-shop__sort' ).val( 'date' );

			self.closeSidebar();
			self.doFilter( { page: 1 } );
		},

		// ── Simple HTML escaper ───────────────────────────────────────────────

		esc: function ( str ) {
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' );
		}
	};

	// ── New Arrivals ──────────────────────────────────────────────────────────
	//
	// Handles tab switching (7d / 30d / 90d / all) and pagination via AJAX.

	var WZP_NewArrivals = {

		$section:  null,
		$grid:     null,
		$overlay:  null,
		activeDays: 30,

		init: function () {
			var self = this;
			self.$section = $( '.wzp-na[data-wzp-module="new-arrivals"]' );
			if ( ! self.$section.length ) { return; }

			self.$grid    = self.$section.find( '#wzp-na-grid' );
			self.$overlay = self.$section.find( '.wzp-na__loading-overlay' );
			self.activeDays = parseInt( self.$section.find( '.wzp-na__tab--active' ).data( 'days' ), 10 ) || 30;

			// Tab clicks.
			self.$section.on( 'click', '.wzp-na__tab', function () {
				var $tab = $( this );
				if ( $tab.hasClass( 'wzp-na__tab--active' ) ) { return; }
				self.activeDays = parseInt( $tab.data( 'days' ), 10 );
				self.$section.find( '.wzp-na__tab' )
					.removeClass( 'wzp-na__tab--active' )
					.attr( 'aria-selected', 'false' );
				$tab.addClass( 'wzp-na__tab--active' ).attr( 'aria-selected', 'true' );
				self.load( 1 );
			} );

			// Pagination.
			self.$section.on( 'click', '.wzp-shop__page-btn:not(.wzp-shop__page-btn--active)', function () {
				var page = parseInt( $( this ).data( 'page' ), 10 );
				if ( page ) {
					self.load( page );
					window.scrollTo( { top: self.$section.offset().top - 80, behavior: 'smooth' } );
				}
			} );
		},

		load: function ( page ) {
			var self = this;

			// Push URL state.
			var params = new URLSearchParams();
			params.set( 'wzp_na_days', self.activeDays );
			if ( page > 1 ) { params.set( 'wzp_na_page', page ); }
			history.pushState( {}, '', window.location.pathname + '?' + params.toString() );

			self.$grid.attr( 'aria-busy', 'true' );
			self.$overlay.addClass( 'wzp-na--loading' );

			$.post(
				wzpData.ajaxUrl,
				{
					action:    'wzp_new_arrivals',
					nonce:     self.$section.data( 'nonce' ),
					days:      self.activeDays,
					page:      page,
					per_page:  parseInt( self.$section.data( 'per-page' ), 10 ) || 12,
				},
				function ( res ) {
					self.$grid.attr( 'aria-busy', 'false' );
					self.$overlay.removeClass( 'wzp-na--loading' );

					if ( ! res.success ) { return; }

					self.$grid.html( res.data.grid );
					self.$section.find( '#wzp-na-pagination' ).html( res.data.pagination );
					self.$section.find( '.wzp-na__count' ).html( res.data.count_text );

					if ( window.WZP_Wishlist ) { WZP_Wishlist.syncButtons(); }
				}
			).fail( function () {
				self.$grid.attr( 'aria-busy', 'false' );
				self.$overlay.removeClass( 'wzp-na--loading' );
			} );
		}
	};

	// ── Category Products ─────────────────────────────────────────────────────
	//
	// Handles sort dropdown and pagination via AJAX for [wzp_category_products].

	var WZP_CategoryProducts = {

		$section: null,
		$grid:    null,
		$overlay: null,

		init: function () {
			var self = this;
			self.$section = $( '.wzp-cp[data-wzp-module="category-products"]' );
			if ( ! self.$section.length ) { return; }

			self.$grid    = self.$section.find( '#wzp-cp-grid' );
			self.$overlay = self.$section.find( '.wzp-cp__loading-overlay' );

			// Sort change.
			self.$section.on( 'change', '.wzp-cp__sort', function () {
				self.load( 1 );
			} );

			// Pagination.
			self.$section.on( 'click', '.wzp-shop__page-btn:not(.wzp-shop__page-btn--active)', function () {
				var page = parseInt( $( this ).data( 'page' ), 10 );
				if ( page ) {
					self.load( page );
					window.scrollTo( { top: self.$section.offset().top - 80, behavior: 'smooth' } );
				}
			} );
		},

		load: function ( page ) {
			var self    = this;
			var orderby = self.$section.find( '.wzp-cp__sort' ).val() || 'date';

			// Push URL state.
			var params = new URLSearchParams();
			if ( orderby !== 'date' ) { params.set( 'wzp_cp_sort', orderby ); }
			if ( page > 1 )           { params.set( 'wzp_cp_page', page ); }
			history.pushState( {}, '', window.location.pathname + ( params.toString() ? '?' + params.toString() : '' ) );

			self.$grid.attr( 'aria-busy', 'true' );
			self.$overlay.addClass( 'wzp-cp--loading' );

			$.post(
				wzpData.ajaxUrl,
				{
					action:   'wzp_category_products',
					nonce:    self.$section.data( 'nonce' ),
					cat:      self.$section.data( 'cat' ),
					orderby:  orderby,
					page:     page,
					per_page: parseInt( self.$section.data( 'per-page' ), 10 ) || 12,
				},
				function ( res ) {
					self.$grid.attr( 'aria-busy', 'false' );
					self.$overlay.removeClass( 'wzp-cp--loading' );

					if ( ! res.success ) { return; }

					self.$grid.html( res.data.grid );
					self.$section.find( '#wzp-cp-pagination' ).html( res.data.pagination );
					self.$section.find( '.wzp-cp__count' ).html( res.data.count_text );

					if ( window.WZP_Wishlist ) { WZP_Wishlist.syncButtons(); }
				}
			).fail( function () {
				self.$grid.attr( 'aria-busy', 'false' );
				self.$overlay.removeClass( 'wzp-cp--loading' );
			} );
		}
	};

	// ── Sticky header — #custom-sticky-header ────────────────────────────────
	var WZP_StickyHeader = {
		init: function () {
			var $el = $( '#custom-sticky-header' );
			if ( ! $el.length ) { return; }

			var lastY     = window.pageYOffset;
			var threshold = 80;

			$( window ).on( 'scroll.wzpSticky', function () {
				var currentY = window.pageYOffset;

				if ( currentY > threshold ) {
					$el.addClass( 'wzp-sticky--scrolled' );
					if ( currentY > lastY ) {
						$el.addClass( 'wzp-sticky--hidden' );
					} else {
						$el.removeClass( 'wzp-sticky--hidden' );
					}
				} else {
					$el.removeClass( 'wzp-sticky--hidden wzp-sticky--scrolled' );
				}

				lastY = currentY;
			} );
		}
	};

	// ── WC Native Cart Enhancements ───────────────────────────────────────────
	var WZP_Cart = {

		FREE_SHIPPING_THRESHOLD: 0, // 0 = disabled; set a value like 5000 to enable bar
		_debounceTimer: null,

		init: function () {
			if ( ! $( 'body' ).hasClass( 'woocommerce-cart' ) ) { return; }
			this.buildEmptyCart();
			this.replaceRemoveIcons();
			this.injectSteppers();      // must run before injectMobilePrice (clones stepper)
			this.injectMobilePrice();
			this.bindSteppers();
			this.buildSidebar();
			this.injectBottomActions();
			this.injectButtonArrows();
		},

		// ── Empty cart state ─────────────────────────────────────────────────
		buildEmptyCart: function () {
			var $empty = $( 'p.cart-empty' );
			if ( ! $empty.length ) { return; }

			// Wrap the empty message + return button in a styled container
			var $returnWrap = $( '.return-to-shop' );
			var returnHtml  = $returnWrap.length ? $returnWrap[0].outerHTML : '';

			var html =
				'<div class="wzp-empty-cart">' +
					'<div class="wzp-empty-cart__icon">' +
						'<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
							'<path d="M8 8h4l6 32h28l6-20H18"/>' +
							'<circle cx="26" cy="54" r="3"/>' +
							'<circle cx="44" cy="54" r="3"/>' +
						'</svg>' +
					'</div>' +
					'<h2 class="wzp-empty-cart__title">Your cart is empty</h2>' +
					'<p class="wzp-empty-cart__sub">Looks like you haven\'t added anything yet.<br>Explore our collection and find something you\'ll love.</p>' +
					returnHtml +
				'</div>';

			$empty.replaceWith( html );
			$returnWrap.remove();
		},

		// ── On mobile: inject price + move qty stepper into name column ──────
		injectMobilePrice: function () {
			if ( window.innerWidth > 768 ) { return; }

			$( 'tr.woocommerce-cart-form__cart-item' ).each( function () {
				var $row  = $( this );
				var $info = $row.find( 'td.product-name' );

				// Price + qty on same row
				if ( ! $info.find( '.wzp-mobile-bottom' ).length ) {
					var $priceEl = $row.find( 'td.product-price .woocommerce-Price-amount' ).first().clone();
					var $qtyWrap = $row.find( 'td.product-quantity .quantity' ).clone( true );

					var $bottom = $( '<div class="wzp-mobile-bottom"></div>' );
					if ( $priceEl.length ) {
						$bottom.append( $( '<span class="wzp-mobile-price"></span>' ).append( $priceEl ) );
					}
					if ( $qtyWrap.length ) {
						$bottom.append( $( '<div class="wzp-mobile-qty"></div>' ).append( $qtyWrap.children() ) );
					}
					$info.append( $bottom );
				}
			} );
		},

		// ── Replace × remove link with trash SVG ────────────────────────────
		replaceRemoveIcons: function () {
			$( 'td.product-remove a.remove' ).each( function () {
				$( this ).html(
					'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
						'<polyline points="3 6 5 6 21 6"/>' +
						'<path d="M19 6l-1 14H6L5 6"/>' +
						'<path d="M10 11v6"/><path d="M14 11v6"/>' +
						'<path d="M9 6V4h6v2"/>' +
					'</svg>'
				);
			} );
		},

		// ── Inject +/– buttons around qty inputs ────────────────────────────
		injectSteppers: function () {
			$( 'td.product-quantity .quantity' ).each( function () {
				var $wrap = $( this );
				if ( $wrap.find( '.wzp-qty-btn' ).length ) { return; }
				var $input = $wrap.find( '.qty' );
				$input
					.before( $( '<button>', { type: 'button', 'class': 'wzp-qty-btn wzp-qty-btn--minus', 'aria-label': 'Decrease', text: '\u2212' } ) )
					.after(  $( '<button>', { type: 'button', 'class': 'wzp-qty-btn wzp-qty-btn--plus',  'aria-label': 'Increase', text: '+' } ) );
			} );
		},

		// ── Stepper click → change qty → AJAX update ────────────────────────
		bindSteppers: function () {
			var self = this;

			$( document ).on( 'click', '.wzp-qty-btn', function () {
				var $btn   = $( this );
				var $input = $btn.closest( '.quantity, .wzp-mobile-qty' ).find( '.qty' );
				var val    = parseInt( $input.val(), 10 ) || 0;
				var max    = parseInt( $input.attr( 'max' ), 10 ) || 9999;

				if ( $btn.hasClass( 'wzp-qty-btn--plus' ) ) {
					val = Math.min( val + 1, max );
				} else {
					val = Math.max( val - 1, 0 ); // allow 0 to trigger removal
				}

				$input.val( val );
				self.onQtyChange( $input );
			} );

			// Also handle manual input
			$( document ).on( 'change', 'td.product-quantity .qty', function () {
				self.onQtyChange( $( this ) );
			} );
		},

		onQtyChange: function ( $input ) {
			var self   = this;
			var key    = $input.closest( 'tr' ).find( '[name^="cart["]' ).attr( 'name' );
			// Extract item key from input name: cart[KEY][qty]
			if ( ! key ) {
				key = $input.attr( 'name' ); // "cart[abc123][qty]"
			}
			var itemKey = ( key || '' ).replace( /^cart\[/, '' ).replace( /\]\[qty\]$/, '' );
			var qty     = parseInt( $input.val(), 10 );

			if ( ! itemKey || isNaN( qty ) || qty < 0 ) { return; }

			// Qty 0 → remove product (click the hidden remove link)
			if ( qty === 0 ) {
				var $removeLink = $input.closest( 'tr' ).find( 'td.product-remove a.remove' );
				if ( $removeLink.length ) {
					$removeLink[0].click();
				}
				return;
			}

			// Optimistic row subtotal update
			var unitPriceText = $input.closest( 'tr' ).find( 'td.product-price .woocommerce-Price-amount' ).text();
			var unitPrice     = parseFloat( unitPriceText.replace( /[^0-9.]/g, '' ) ) || 0;
			if ( unitPrice && qty >= 0 ) {
				var newSubtotal = unitPrice * qty;
				// Keep the currency prefix from the existing subtotal cell
				var existing = $input.closest( 'tr' ).find( 'td.product-subtotal .woocommerce-Price-amount' ).text();
				var prefix   = existing.replace( /[\d.,]+/, '' ).trim();
				$input.closest( 'tr' ).find( 'td.product-subtotal bdi' ).text( prefix + newSubtotal.toLocaleString() );
			}

			// Debounce AJAX call
			clearTimeout( self._debounceTimer );
			self._debounceTimer = setTimeout( function () {
				self.ajaxUpdate( itemKey, qty );
			}, 600 );
		},

		// ── AJAX update via WooCommerce Store API ────────────────────────────
		ajaxUpdate: function ( itemKey, qty ) {
			var self     = this;
			var apiBase  = ( wzpData && wzpData.storeApiUrl ) ? wzpData.storeApiUrl : '/wp-json/wc/store/v1/';
			var nonce    = ( wzpData && wzpData.storeApiNonce ) ? wzpData.storeApiNonce : '';

			self.setLoading( true );

			fetch( apiBase + 'cart/update-item', {
				method:  'POST',
				headers: {
					'Content-Type': 'application/json',
					'Nonce':        nonce,
					'X-WC-Store-API-Nonce': nonce
				},
				body: JSON.stringify( { key: itemKey, quantity: qty } )
			} )
			.then( function ( r ) {
				if ( ! r.ok ) { throw new Error( 'API error ' + r.status ); }
				return r.json();
			} )
			.then( function ( cart ) {
				self.applyCartResponse( cart );
				self.setLoading( false );
			} )
			.catch( function () {
				// Fallback: reload page
				self.setLoading( false );
				window.location.reload();
			} );
		},

		// ── Apply Store API cart response to DOM ─────────────────────────────
		applyCartResponse: function ( cart ) {
			if ( ! cart || ! cart.totals ) { return; }

			var t          = cart.totals;
			var decimals   = parseInt( t.currency_minor_unit, 10 ) || 0;
			var divisor    = Math.pow( 10, decimals );
			var prefix     = t.currency_prefix  || '';
			var suffix     = t.currency_suffix  || '';

			function fmt( minor ) {
				var n = ( parseInt( minor, 10 ) || 0 ) / divisor;
				return prefix + n.toLocaleString( undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals } ) + suffix;
			}

			// Update per-item subtotals
			if ( cart.items && cart.items.length ) {
				cart.items.forEach( function ( item ) {
					var $row = $( 'tr[data-cartitemkey="' + item.key + '"], tr' ).filter( function () {
						return $( this ).find( '.qty[name*="' + item.key + '"]' ).length > 0;
					} );
					if ( $row.length ) {
						var lineTotal = ( parseInt( item.totals.line_subtotal, 10 ) + parseInt( item.totals.line_subtotal_tax || 0, 10 ) );
						$row.find( 'td.product-subtotal bdi' ).text( fmt( lineTotal ) );
					}
				} );
			}

			// Update sidebar subtotal
			var subtotal = parseInt( t.subtotal, 10 ) + parseInt( t.subtotal_tax || 0, 10 );
			$( '.cart-subtotal .woocommerce-Price-amount bdi' ).text( fmt( subtotal ) );

			// Update sidebar total
			var total = parseInt( t.total_price, 10 );
			$( '.order-total .woocommerce-Price-amount bdi' ).text( fmt( total ) );

			// Update item count label
			var itemCount = 0;
			if ( cart.items ) {
				cart.items.forEach( function ( item ) { itemCount += item.quantity; } );
			}
			var label = itemCount === 1 ? 'THERE IS 1 ITEM IN YOUR CART' : 'THERE ARE ' + itemCount + ' ITEMS IN YOUR CART';
			$( '.wzp-cart-count' ).text( label );

			// Update free shipping bar
			if ( this.FREE_SHIPPING_THRESHOLD > 0 ) {
				var subtotalVal = subtotal / divisor;
				this.updateShippingBar( subtotalVal );
			}

		},

		// ── Loading overlay ──────────────────────────────────────────────────
		setLoading: function ( on ) {
			var $sidebar = $( '.cart_totals' );
			if ( on ) {
				$sidebar.addClass( 'wzp-loading' );
			} else {
				$sidebar.removeClass( 'wzp-loading' );
			}
		},

		// ── Build sidebar: item count + free shipping bar ────────────────────
		buildSidebar: function () {
			var self    = this;
			var $totals = $( '.cart_totals' );
			if ( ! $totals.length ) { return; }

			// ── Item count banner ────────────────────────────────────────────
			var itemCount = 0;
			$( 'tr.woocommerce-cart-form__cart-item .qty' ).each( function () {
				itemCount += parseInt( $( this ).val(), 10 ) || 0;
			} );
			var countLabel = itemCount === 1 ? 'THERE IS 1 ITEM IN YOUR CART' : 'THERE ARE ' + itemCount + ' ITEMS IN YOUR CART';
			if ( ! $totals.find( '.wzp-cart-count' ).length ) {
				$totals.prepend( '<span class="wzp-cart-count">' + countLabel + '</span>' );
			}

			// ── Promo code field ─────────────────────────────────────────────
			if ( ! $totals.find( '.wzp-coupon-wrap' ).length ) {
				var couponHtml =
					'<div class="wzp-coupon-wrap">' +
						'<div class="wzp-coupon-row">' +
							'<span class="wzp-coupon-icon">' +
								'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>' +
							'</span>' +
							'<input type="text" class="wzp-coupon-input" placeholder="Add promo code" autocomplete="off">' +
							'<button type="button" class="wzp-coupon-btn">Apply</button>' +
						'</div>' +
						'<p class="wzp-coupon-msg"></p>' +
					'</div>';
				$totals.find( '.shop_table' ).before( couponHtml );
				self.bindCoupon();
			}

			// ── Trust badges (after checkout button) ─────────────────────────
			if ( ! $totals.find( '.wzp-cart-trust' ).length ) {
				var trustHtml =
					'<div class="wzp-cart-trust">' +
						'<div class="wzp-cart-trust__item">' +
							'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' +
							'Secure' +
						'</div>' +
						'<div class="wzp-cart-trust__item">' +
							'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>' +
							'Safe Pay' +
						'</div>' +
						'<div class="wzp-cart-trust__item">' +
							'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>' +
							'Fast Ship' +
						'</div>' +
					'</div>';
				$totals.append( trustHtml );
			}

			// ── Free shipping bar (only if threshold > 0) ────────────────────
			if ( self.FREE_SHIPPING_THRESHOLD > 0 && ! $totals.find( '.wzp-shipping-bar' ).length ) {
				var subtotalText = $( '.cart-subtotal .woocommerce-Price-amount' ).last().text().replace( /[^0-9.]/g, '' );
				self.updateShippingBar( parseFloat( subtotalText ) || 0 );
			}
		},

		// ── Coupon apply via Store API ───────────────────────────────────────
		bindCoupon: function () {
			var self    = this;
			var apiBase = ( wzpData && wzpData.storeApiUrl ) ? wzpData.storeApiUrl : '/wp-json/wc/store/v1/';
			var nonce   = ( wzpData && wzpData.storeApiNonce ) ? wzpData.storeApiNonce : '';

			function doApply() {
				var code = $.trim( $( '.wzp-coupon-input' ).val() );
				var $msg = $( '.wzp-coupon-msg' );
				if ( ! code ) { return; }

				$( '.wzp-coupon-btn' ).prop( 'disabled', true ).text( '...' );
				$msg.text( '' ).removeClass( 'wzp-coupon-msg--ok wzp-coupon-msg--err' );

				fetch( apiBase + 'cart/apply-coupon', {
					method:  'POST',
					headers: {
						'Content-Type': 'application/json',
						'Nonce': nonce,
						'X-WC-Store-API-Nonce': nonce
					},
					body: JSON.stringify( { code: code } )
				} )
				.then( function ( r ) { return r.json().then( function ( d ) { return { ok: r.ok, data: d }; } ); } )
				.then( function ( res ) {
					$( '.wzp-coupon-btn' ).prop( 'disabled', false ).text( 'Apply' );
					if ( res.ok ) {
						$msg.text( 'Coupon applied!' ).addClass( 'wzp-coupon-msg--ok' );
						$( '.wzp-coupon-input' ).val( '' );
						self.applyCartResponse( res.data );
					} else {
						var errMsg = ( res.data && res.data.message ) ? res.data.message : 'Invalid coupon code.';
						$msg.text( errMsg ).addClass( 'wzp-coupon-msg--err' );
					}
				} )
				.catch( function () {
					$( '.wzp-coupon-btn' ).prop( 'disabled', false ).text( 'Apply' );
					$msg.text( 'Something went wrong.' ).addClass( 'wzp-coupon-msg--err' );
				} );
			}

			$( document ).on( 'click', '.wzp-coupon-btn', doApply );
			$( document ).on( 'keydown', '.wzp-coupon-input', function ( e ) {
				if ( e.key === 'Enter' ) { doApply(); }
			} );
		},

		updateShippingBar: function ( subtotal ) {
			var threshold = this.FREE_SHIPPING_THRESHOLD;
			var remaining = Math.max( 0, threshold - subtotal );
			var pct       = Math.min( 100, Math.round( ( subtotal / threshold ) * 100 ) );
			var $bar      = $( '.wzp-shipping-bar' );

			if ( ! $bar.length ) {
				$( '.cart_totals' ).append( '<div class="wzp-shipping-bar"><p class="wzp-shipping-bar__label"></p><div class="wzp-shipping-bar__track"><div class="wzp-shipping-bar__fill"></div></div><p class="wzp-shipping-bar__note"></p></div>' );
				$bar = $( '.wzp-shipping-bar' );
			}

			$bar.find( '.wzp-shipping-bar__fill' ).css( 'width', pct + '%' );

			if ( remaining > 0 ) {
				$bar.find( '.wzp-shipping-bar__label' ).html( 'Spend <strong>Rs ' + remaining.toLocaleString() + '</strong> more for <span class="wzp-shipping-bar__free">FREE SHIPPING</span>' );
				$bar.find( '.wzp-shipping-bar__note' ).text( 'Free shipping on orders above Rs ' + threshold.toLocaleString() );
				$bar.removeClass( 'wzp-shipping-bar--unlocked' );
			} else {
				$bar.find( '.wzp-shipping-bar__label' ).html( '<span class="wzp-shipping-bar__free">✓ Free shipping unlocked!</span>' );
				$bar.find( '.wzp-shipping-bar__note' ).text( '' );
				$bar.addClass( 'wzp-shipping-bar--unlocked' );
			}
		},

		// ── Pill action buttons below table ──────────────────────────────────
		// ── Inject animated arrow spans into buttons ─────────────────────────
		injectButtonArrows: function () {
			var arrowSvg =
				'<span class="wzp-arrow">' +
					'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
						'<line x1="5" y1="12" x2="19" y2="12"/>' +
						'<polyline points="12 5 19 12 12 19"/>' +
					'</svg>' +
				'</span>';

			// Proceed to Checkout (sidebar)
			$( '.checkout-button' ).each( function () {
				var $btn = $( this );
				if ( $btn.find( '.wzp-arrow' ).length ) { return; }
				// Strip any stray theme ::after content by wrapping text
				var text = $btn.text().trim();
				$btn.html( '<span>' + text + '</span>' + arrowSvg );
			} );

			// Apply Coupon button
			$( '[name="apply_coupon"]' ).each( function () {
				var $btn = $( this );
				if ( $btn.find( '.wzp-arrow' ).length ) { return; }
				var text = $btn.text().trim();
				$btn.html( '<span>' + text + '</span>' + arrowSvg );
			} );
		},

		injectBottomActions: function () {
			var $form = $( 'form.woocommerce-cart-form' );
			if ( ! $form.length || $form.next( '.wzp-cart-actions' ).length ) { return; }

			var checkoutUrl = $( '.checkout-button' ).attr( 'href' ) || '#';
			var shopUrl     = ( wzpData && wzpData.shopUrl ) ? wzpData.shopUrl : '/shop/';

			var arrowSvg =
				'<span class="wzp-arrow">' +
					'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
						'<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>' +
					'</svg>' +
				'</span>';

			$form.after(
				'<div class="wzp-cart-actions">' +
					'<a href="' + shopUrl + '" class="wzp-cart-actions__shop">Continue Shopping</a>' +
					'<a href="' + checkoutUrl + '" class="wzp-cart-actions__checkout">Proceed To Checkout ' + arrowSvg + '</a>' +
				'</div>'
			);
		}
	};

	// ── Auto-dismiss WC notices after 6 seconds ──────────────────────────────
	function wzpAutoDismissNotices() {
		var $notices = $( '.woocommerce-message, .woocommerce-info, .woocommerce-error' );
		$notices.each( function () {
			var $n = $( this );
			setTimeout( function () {
				$n.css( { transition: 'opacity 0.5s ease, max-height 0.5s ease, margin 0.5s ease, padding 0.5s ease', opacity: '0', maxHeight: '0', margin: '0', padding: '0', overflow: 'hidden' } );
				setTimeout( function () { $n.remove(); }, 520 );
			}, 6000 );
		} );
	}

	// ── WZP Checkout — two-column layout + AJAX review update ────────────────
	var WZP_Checkout = {

		init: function () {
			if ( ! $( 'body' ).hasClass( 'woocommerce-checkout' ) ) { return; }

			// Standalone [wzp_checkout_form] shortcode
			if ( $( '.wzp-checkout-form-wrap' ).length ) {
				this.enhanceFormFields();
			}

			// Combined [wzp_checkout] shortcode only
			if ( $( '.wzp-checkout-page' ).length ) {
				this.buildLayout();
			}

			this.bindReviewUpdate();
		},

		// ── Enhance form fields ───────────────────────────────────────────────
		enhanceFormFields: function () {
			var self  = this;
			var $wrap = $( '.wzp-checkout-form-wrap' );
			if ( ! $wrap.length ) { return; }

			// ── 1. Move email + phone under Contact heading ──────────────────
			var $contactHead = $wrap.find( '.wzp-ckout-section-head' ).first();
			var $deliveryHead = $wrap.find( '.wzp-ckout-section-head--delivery' ).first();

			if ( $contactHead.length && $deliveryHead.length ) {
				var $emailRow = $wrap.find( '#billing_email_field' );
				var $phoneRow = $wrap.find( '#billing_phone_field' );

				// Only move if not already in the contact block
				if ( $emailRow.closest( '.wzp-ckout-contact-fields' ).length === 0 ) {
					var $block = $( '<div class="wzp-ckout-contact-fields"></div>' );
					if ( $emailRow.length ) { $block.append( $emailRow.detach() ); }
					if ( $phoneRow.length ) { $block.append( $phoneRow.detach() ); }
					$contactHead.after( $block );
				}
			}

			// ── 2. Floating label — init has-value and bind focus/blur/change ──
			$wrap.find( '.form-row' ).each( function () {
				var $row   = $( this );
				var $input = $row.find( 'input.input-text, select, textarea' ).first();
				if ( ! $input.length ) { return; }

				// Initialise has-value for pre-filled fields
				var v = $input.val();
				if ( v && v.trim() !== '' ) { $row.addClass( 'wzp-has-value' ); }

				// Bind events (namespaced to allow safe re-binding on AJAX refresh)
				$input.off( 'focus.wzpfl blur.wzpfl change.wzpfl input.wzpfl' );
				$input
					.on( 'focus.wzpfl',  function () { $row.addClass( 'wzp-focused' ); } )
					.on( 'blur.wzpfl',   function () { $row.removeClass( 'wzp-focused' ); } )
					.on( 'change.wzpfl input.wzpfl', function () {
						$row.toggleClass( 'wzp-has-value', !! ( $( this ).val() || '' ).trim() );
					} );
			} );

			// ── 3. Mark select rows + strip label text (shown as placeholder in option) ──
			$wrap.find( '.form-row' ).each( function () {
				var $row = $( this );
				if ( $row.find( 'select' ).length ) {
					$row.addClass( 'wzp-has-select' );
				}
			} );

			// ── 4. City / State / ZIP → 3-col grid ──────────────────────────
			$( [ 'billing', 'shipping' ] ).each( function ( _i, prefix ) {
				var $city  = $wrap.find( '#' + prefix + '_city_field' );
				var $state = $wrap.find( '#' + prefix + '_state_field' );
				var $post  = $wrap.find( '#' + prefix + '_postcode_field' );

				if ( $city.length && $state.length && $post.length &&
					! $city.parent().hasClass( 'wzp-city-state-zip' ) ) {
					var $grid = $( '<div class="wzp-city-state-zip"></div>' );
					$city.before( $grid );
					$grid.append( $city.detach() );
					$grid.append( $state.detach() );
					$grid.append( $post.detach() );
				}
			} );

			// Re-run on WC AJAX updates (country change, etc.)
			$( document.body ).off( 'updated_checkout.wzp' ).on( 'updated_checkout.wzp', function () {
				setTimeout( function () { self.enhanceFormFields(); }, 100 );
			} );
		},

		// ── Inject section headers into [wzp_checkout_form] ───────────────────
		buildFormSections: function () {
			var $wrap = $( '.wzp-checkout-form-wrap' );
			var loginUrl = ( wzpData && wzpData.loginUrl ) ? wzpData.loginUrl : '#';

			// Move email + phone to top of billing fields
			var $billing = $wrap.find( '.woocommerce-billing-fields' );
			var $emailRow = $billing.find( '#billing_email_field' );
			var $phoneRow = $billing.find( '#billing_phone_field' );

			// ── Contact heading ──────────────────────────────────────────────
			var $contactHead =
				'<div class="wzp-ckout-section-head">' +
					'<span class="wzp-ckout-section-head__title">Contact</span>' +
					'<a href="' + loginUrl + '" class="wzp-ckout-section-head__link">Sign in</a>' +
				'</div>';

			$wrap.find( 'form' ).prepend( $contactHead );

			// Move email + phone rows right after the contact heading
			var $contactBlock = $( '<div class="wzp-ckout-contact-fields"></div>' );
			$contactBlock.append( $emailRow.detach() );
			$contactBlock.append( $phoneRow.detach() );
			$wrap.find( '.wzp-ckout-section-head' ).first().after( $contactBlock );

			// ── Delivery heading ─────────────────────────────────────────────
			var $deliveryHead =
				'<div class="wzp-ckout-section-head">' +
					'<span class="wzp-ckout-section-head__title">Delivery</span>' +
				'</div>';
			$contactBlock.after( $deliveryHead );

			// ── Payment heading ──────────────────────────────────────────────
			var $paymentHead =
				'<div class="wzp-ckout-section-head" style="margin-top:32px">' +
					'<span class="wzp-ckout-section-head__title">Payment</span>' +
					'<span class="wzp-ckout-section-head__note">Secure &amp; encrypted</span>' +
				'</div>';
			$wrap.find( '#payment' ).before( $paymentHead );
		},

		// ── Split native form into .wzp-ckout-left / .wzp-ckout-right ─────────
		buildLayout: function () {
			var $form = $( '.wzp-checkout-page form.woocommerce-checkout' );
			if ( ! $form.length || $form.find( '.wzp-ckout-left' ).length ) { return; }

			// Collect left-side elements
			var $left = $( '<div class="wzp-ckout-left"></div>' );

			// ── Contact section ──────────────────────────────────────────────
			$left.append(
				'<div class="wzp-ckout-label">' +
					'<span class="wzp-ckout-label__title">Contact</span>' +
					'<a href="' + ( wzpData.loginUrl || '' ) + '" class="wzp-ckout-label__link">Sign in</a>' +
				'</div>'
			);

			var $email = $form.find( '#billing_email_field, #billing_phone_field' );
			$left.append( $email );

			// ── Delivery section ─────────────────────────────────────────────
			$left.append(
				'<div class="wzp-ckout-label">' +
					'<span class="wzp-ckout-label__title">Delivery</span>' +
				'</div>'
			);

			var $billing = $form.find( '.woocommerce-billing-fields' );
			// Remove email/phone (already moved)
			$billing.find( '#billing_email_field, #billing_phone_field' ).remove();
			$left.append( $billing );

			// Shipping toggle + fields
			var $shipping = $form.find( '.woocommerce-shipping-fields' );
			if ( $shipping.length ) { $left.append( $shipping ); }

			// Additional fields
			var $additional = $form.find( '.woocommerce-additional-fields' );
			if ( $additional.length ) { $left.append( $additional ); }

			// ── Payment section ──────────────────────────────────────────────
			$left.append(
				'<div class="wzp-ckout-label" style="margin-top:32px">' +
					'<span class="wzp-ckout-label__title">Payment</span>' +
					'<span class="wzp-ckout-label__note">All transactions are secure and encrypted</span>' +
				'</div>'
			);

			var $payment = $form.find( '#payment' );
			$left.append( $payment );

			// ── Right column — order review ──────────────────────────────────
			var $right  = $( '<div class="wzp-ckout-right"></div>' );
			var $review = $form.find( '#order_review' );
			$right.append( $review );

			// Clear form and rebuild
			$form.empty().append( $left ).append( $right );
		},

		// ── Bind WC's native update_checkout event to refresh order review ────
		bindReviewUpdate: function () {
			// WC fires 'update_checkout' and 'updated_checkout' natively.
			// We just ensure the order review table stays visible and smooth.
			$( document.body ).on( 'updating_checkout', function () {
				$( '#order_review' ).css( { opacity: '0.5', pointerEvents: 'none' } );
			} );

			$( document.body ).on( 'updated_checkout', function () {
				$( '#order_review' ).css( { opacity: '1', pointerEvents: '' } );
			} );

			// Trigger update on page load so totals are fresh
			$( document.body ).trigger( 'update_checkout' );
		}
	};

	// ── Mobile bottom nav — active state ─────────────────────────────────────
	function wzpMobileNavInit() {
		var $nav   = $( '.wzp-mobile-nav' );
		if ( ! $nav.length ) { return; }

		var current = window.location.href.replace( /\/$/, '' );

		$nav.find( '.wzp-mobile-nav__link' ).each( function () {
			var href = $( this ).attr( 'href' );
			if ( ! href ) { return; }
			var target = href.replace( /\/$/, '' );
			// Exact match OR current URL starts with target (for sub-pages like /shop/category/)
			if ( current === target || ( target.length > 10 && current.indexOf( target ) === 0 ) ) {
				$( this ).addClass( 'wzp-active' );
			}
		} );

	}

	// ── DOM ready ─────────────────────────────────────────────────────────────
	$( function () {
		WZP_Wishlist.init();
		WZP_Lookbook.init();
		WZP_Navbar.init();
		WZP_ProductDetail.init();
		WZP_Shop.init();
		WZP_NewArrivals.init();
		WZP_CategoryProducts.init();
		WZP_StickyHeader.init();
		WZP_Cart.init();
		WZP_Checkout.init();
		wzpAutoDismissNotices();
		wzpMobileNavInit();
	} );

} )( jQuery, window.wzpData || {} );
