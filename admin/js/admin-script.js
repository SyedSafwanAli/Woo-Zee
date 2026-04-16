/**
 * Woo Zee Plugin — Admin Script
 *
 * Handles:
 *   1. Hero Slider repeatable slide manager (add / remove rows)
 *   2. WP Media Library picker for slide images
 *   3. Autoplay speed row toggle on the Product Carousel tab
 *
 * Depends on: jQuery, wp.media (loaded via wp_enqueue_media())
 *
 * @package WooZeePlugin
 */

( function ( $ ) {
	'use strict';

	// ── 1. Hero Slider — repeatable slide manager ─────────────────────────────

	var WZP_SlideManager = {

		$list     : null,
		$addBtn   : null,
		$template : null,

		init: function () {
			this.$list     = $( '#wzp-hero-slides-list' );
			this.$addBtn   = $( '#wzp-add-slide' );
			this.$template = $( '#wzp-slide-template' );

			if ( ! this.$list.length ) { return; }

			this.bindEvents();
			// Mark the first slide as open if the list already has entries.
			this.$list.find( '.wzp-slide-row' ).first().addClass( 'wzp-slide-row--open' );
		},

		bindEvents: function () {
			var self = this;

			// ── Add slide ─────────────────────────────────────────────────────
			this.$addBtn.on( 'click', function () {
				self.addSlide();
			} );

			// ── Remove slide (delegated — works for dynamically added rows) ───
			this.$list.on( 'click', '.wzp-slide-remove', function () {
				var $row = $( this ).closest( '.wzp-slide-row' );
				self.removeSlide( $row );
			} );

			// ── Toggle accordion header ───────────────────────────────────────
			this.$list.on( 'click', '.wzp-slide-header', function ( e ) {
				// Don't toggle when clicking buttons inside the header.
				if ( $( e.target ).is( 'button, button *' ) ) { return; }
				$( this ).closest( '.wzp-slide-row' ).toggleClass( 'wzp-slide-row--open' );
			} );

			// ── Media picker (delegated) ──────────────────────────────────────
			this.$list.on( 'click', '.wzp-media-btn', function () {
				WZP_MediaPicker.open( $( this ).closest( '.wzp-slide-row' ) );
			} );

			// ── Remove image ──────────────────────────────────────────────────
			this.$list.on( 'click', '.wzp-media-remove', function () {
				var $row = $( this ).closest( '.wzp-slide-row' );
				$row.find( '.wzp-slide-image-id' ).val( '' );
				$row.find( '.wzp-media-preview' ).html( '' );
				$row.find( '.wzp-media-remove' ).hide();
				$row.find( '.wzp-media-btn' ).text( wzpAdmin.labelSelectImage );
			} );
		},

		/**
		 * Get the next available index from the list's data attribute.
		 * @returns {number}
		 */
		nextIndex: function () {
			var idx = parseInt( this.$list.data( 'next-index' ) || 0, 10 );
			this.$list.data( 'next-index', idx + 1 );
			return idx;
		},

		/**
		 * Clone the template, replace __INDEX__ placeholders, and append.
		 */
		addSlide: function () {
			var idx  = this.nextIndex();
			var html = this.$template.html().replace( /__INDEX__/g, idx );
			var $row = $( html );

			// New slides open by default.
			$row.addClass( 'wzp-slide-row--open' );
			this.$list.append( $row );

			// Focus the heading field for quick entry.
			$row.find( '.wzp-slide-heading' ).trigger( 'focus' );
		},

		/**
		 * Animate and remove a slide row.
		 * @param {jQuery} $row
		 */
		removeSlide: function ( $row ) {
			$row.addClass( 'wzp-slide-row--removing' );
			setTimeout( function () { $row.remove(); }, 200 );
		}
	};

	// ── 2. WP Media Library picker ────────────────────────────────────────────

	var WZP_MediaPicker = {

		frame    : null,
		$context : null,  // the .wzp-slide-row that triggered the picker

		/**
		 * Open the WP media modal for a given slide row.
		 * Re-uses a single wp.media frame instance for performance.
		 *
		 * @param {jQuery} $row  The .wzp-slide-row element.
		 */
		open: function ( $row ) {
			this.$context = $row;

			if ( ! this.frame ) {
				this.frame = wp.media( {
					title    : wzpAdmin.labelMediaTitle,
					button   : { text: wzpAdmin.labelMediaButton },
					multiple : false,
					library  : { type: 'image' }
				} );

				this.frame.on( 'select', this.onSelect.bind( this ) );
			}

			this.frame.open();
		},

		/**
		 * Handle image selection — populate hidden input and preview.
		 */
		onSelect: function () {
			if ( ! this.$context ) { return; }

			var attachment = this.frame.state().get( 'selection' ).first().toJSON();
			var $row       = this.$context;

			// Store attachment ID.
			$row.find( '.wzp-slide-image-id' ).val( attachment.id );

			// Show thumbnail preview.
			var thumbUrl = ( attachment.sizes && attachment.sizes.thumbnail )
				? attachment.sizes.thumbnail.url
				: attachment.url;

			$row.find( '.wzp-media-preview' ).html(
				'<img src="' + $( '<div>' ).text( thumbUrl ).html() + '" alt="" />'
			);

			// Update button label and show remove link.
			$row.find( '.wzp-media-btn' ).text( wzpAdmin.labelChangeImage );
			$row.find( '.wzp-media-remove' ).show();
		}
	};

	// ── 3. Lookbook — background image picker + hotspot repeater ────────────────

	var WZP_LookbookAdmin = {

		frame: null,

		init: function () {
			if ( ! $( '#wzp-lookbook-media-btn' ).length ) { return; }

			var self = this;

			// ── Image picker ──────────────────────────────────────────────────
			$( '#wzp-lookbook-media-btn' ).on( 'click', function () {
				self.openPicker();
			} );

			$( '#wzp-lookbook-media-remove' ).on( 'click', function () {
				self.removeImage();
			} );

			// ── Hotspot add ───────────────────────────────────────────────────
			$( '#wzp-add-hotspot' ).on( 'click', function () {
				self.addHotspot();
			} );

			// ── Hotspot remove (delegated) ────────────────────────────────────
			$( '#wzp-hotspots-list' ).on( 'click', '.wzp-hotspot-remove', function () {
				$( this ).closest( '.wzp-hotspot-row' ).remove();
				self.reIndex();
			} );
		},

		/**
		 * Open the WP media library to pick the lookbook background image.
		 * Reuses a single frame instance.
		 */
		openPicker: function () {
			if ( ! this.frame ) {
				this.frame = wp.media( {
					title    : wzpAdmin.labelMediaTitle,
					button   : { text: wzpAdmin.labelMediaButton },
					multiple : false,
					library  : { type: 'image' }
				} );
				this.frame.on( 'select', this.onSelect.bind( this ) );
			}
			this.frame.open();
		},

		/**
		 * Populate the hidden input + preview after image selection.
		 */
		onSelect: function () {
			var attachment = this.frame.state().get( 'selection' ).first().toJSON();

			var previewUrl = ( attachment.sizes && attachment.sizes.medium )
				? attachment.sizes.medium.url
				: attachment.url;

			$( '#wzp-lookbook-image-id' ).val( attachment.id );
			$( '#wzp-lookbook-preview' ).html(
				'<img src="' + $( '<div>' ).text( previewUrl ).html() + '" alt="" />'
			);
			$( '#wzp-lookbook-media-btn' ).text( wzpAdmin.labelChangeImage );
			$( '#wzp-lookbook-media-remove' ).show();

			var canvasUrl = ( attachment.sizes && attachment.sizes.large )
				? attachment.sizes.large.url
				: ( attachment.sizes && attachment.sizes.full )
				? attachment.sizes.full.url
				: attachment.url;
			WZP_LookbookCanvas.updateCanvasImage( canvasUrl );
		},

		/**
		 * Clear the selected background image.
		 */
		removeImage: function () {
			$( '#wzp-lookbook-image-id' ).val( '' );
			$( '#wzp-lookbook-preview' ).html( '' );
			$( '#wzp-lookbook-media-btn' ).text( wzpAdmin.labelSelectImage );
			$( '#wzp-lookbook-media-remove' ).hide();
		},

		/**
		 * Append a new hotspot row by cloning the template.
		 */
		addHotspot: function () {
			var $list = $( '#wzp-hotspots-list' );
			var $tpl  = $( '#wzp-hotspot-template' );
			var idx   = parseInt( $list.data( 'next-index' ) || 0, 10 );

			$list.data( 'next-index', idx + 1 );

			var html = $tpl.html().replace( /__INDEX__/g, idx );
			var $row = $( html );

			// Show column headers lazily on first add.
			if ( ! $list.find( '.wzp-hotspot-row--header' ).length ) {
				$list.prepend(
					'<div class="wzp-hotspot-row wzp-hotspot-row--header" aria-hidden="true">' +
					'<span>X %</span><span>Y %</span><span>Product ID</span><span></span>' +
					'</div>'
				);
			}

			$list.append( $row );
			$row.find( 'input' ).first().trigger( 'focus' );
		},

		/**
		 * Re-index all hotspot rows' field names after a row is removed.
		 * Keeps the PHP-side array sequential.
		 */
		reIndex: function () {
			var $rows = $( '#wzp-hotspots-list .wzp-hotspot-row:not(.wzp-hotspot-row--header)' );

			$rows.each( function ( i ) {
				$( this ).data( 'index', i );
				$( this ).find( '[name]' ).each( function () {
					var name = $( this ).attr( 'name' )
						.replace( /\[hotspots\]\[\d+\]/, '[hotspots][' + i + ']' );
					$( this ).attr( 'name', name );
				} );
			} );

			$( '#wzp-hotspots-list' ).data( 'next-index', $rows.length );
		}
	};

	// ── 4. Product Carousel — autoplay speed toggle ───────────────────────────

	var WZP_CarouselToggle = {
		init: function () {
			var $checkbox = $( '#wzp-pc-autoplay' );
			var $row      = $( '#wzp-pc-speed-row' );

			if ( ! $checkbox.length ) { return; }

			$checkbox.on( 'change', function () {
				$row.toggle( this.checked );
			} );
		}
	};

	// ── 5. Testimonials — review repeater + avatar picker ────────────────────

	var WZP_TestimonialsAdmin = {

		/**
		 * One shared wp.media frame for all avatar pickers in the list.
		 * $context stores the .wzp-review-row that triggered the open.
		 */
		frame    : null,
		$context : null,

		init: function () {
			if ( ! $( '#wzp-testimonials-list' ).length ) { return; }

			var self = this;

			// ── Add review row ────────────────────────────────────────────────
			$( '#wzp-add-review' ).on( 'click', function () {
				self.addRow();
			} );

			// ── Remove review row (delegated) ─────────────────────────────────
			$( '#wzp-testimonials-list' ).on( 'click', '.wzp-review-remove', function () {
				var $row = $( this ).closest( '.wzp-review-row' );
				$row.addClass( 'wzp-review-row--removing' );
				setTimeout( function () {
					$row.remove();
					self.reIndex();
				}, 180 );
			} );

			// ── Avatar upload button (delegated) ─────────────────────────────
			$( '#wzp-testimonials-list' ).on( 'click', '.wzp-review-avatar-btn', function () {
				self.$context = $( this ).closest( '.wzp-review-row' );
				self.openPicker();
			} );

			// ── Avatar remove button (delegated) ─────────────────────────────
			$( '#wzp-testimonials-list' ).on( 'click', '.wzp-review-avatar-remove', function () {
				var $row = $( this ).closest( '.wzp-review-row' );
				$row.find( '.wzp-review-avatar-id' ).val( '' );
				$row.find( '.wzp-review-avatar-preview' ).html( '' );
				$row.find( '.wzp-review-avatar-btn' ).text( wzpAdmin.labelUpload );
				$( this ).hide();
			} );
		},

		/**
		 * Open (or re-open) the WP media library.
		 * Creates the frame once, re-uses it on subsequent calls.
		 */
		openPicker: function () {
			if ( ! this.frame ) {
				this.frame = wp.media( {
					title    : wzpAdmin.labelAvatarTitle,
					button   : { text: wzpAdmin.labelMediaButton },
					multiple : false,
					library  : { type: 'image' }
				} );
				this.frame.on( 'select', this.onSelect.bind( this ) );
			}
			this.frame.open();
		},

		/**
		 * Populate the avatar preview + hidden input after selection.
		 */
		onSelect: function () {
			if ( ! this.$context ) { return; }

			var attachment = this.frame.state().get( 'selection' ).first().toJSON();
			var $row       = this.$context;

			var thumbUrl = ( attachment.sizes && attachment.sizes.thumbnail )
				? attachment.sizes.thumbnail.url
				: attachment.url;

			$row.find( '.wzp-review-avatar-id' ).val( attachment.id );
			$row.find( '.wzp-review-avatar-preview' ).html(
				'<img src="' + $( '<div>' ).text( thumbUrl ).html() + '" alt="" />'
			);
			$row.find( '.wzp-review-avatar-btn' ).text( wzpAdmin.labelChangeImage );
			$row.find( '.wzp-review-avatar-remove' ).show();
		},

		/**
		 * Clone the template, replace __INDEX__, and append a new review row.
		 */
		addRow: function () {
			var $list = $( '#wzp-testimonials-list' );
			var $tpl  = $( '#wzp-testimonial-template' );
			var idx   = parseInt( $list.data( 'next-index' ) || 0, 10 );

			$list.data( 'next-index', idx + 1 );

			var html = $tpl.html().replace( /__INDEX__/g, idx );
			var $row = $( html );

			$list.append( $row );
			$row.find( '.wzp-review-name' ).trigger( 'focus' );
		},

		/**
		 * Re-number all rows' field names after a removal.
		 */
		reIndex: function () {
			$( '#wzp-testimonials-list .wzp-review-row' ).each( function ( i ) {
				$( this ).data( 'index', i );
				$( this ).find( '[name]' ).each( function () {
					var name = $( this ).attr( 'name' )
						.replace( /wzp_testimonials_data\[\d+\]/, 'wzp_testimonials_data[' + i + ']' );
					$( this ).attr( 'name', name );
				} );
			} );
			var total = $( '#wzp-testimonials-list .wzp-review-row' ).length;
			$( '#wzp-testimonials-list' ).data( 'next-index', total );
		}
	};

	// ── 6. Instagram Feed — token toggle + AJAX tools ────────────────────────

	var WZP_InstagramAdmin = {

		init: function () {
			if ( ! $( '#wzp-ig-section' ).length ) { return; }
			this.bindTokenToggle();
			this.bindTestConnection();
			this.bindClearCache();
		},

		// ── Show / hide the password field ───────────────────────────────────
		bindTokenToggle: function () {
			$( '#wzp-ig-token-toggle' ).on( 'click', function () {
				var $input   = $( '#wzp-ig-token' );
				var $btn     = $( this );
				var isHidden = $input.attr( 'type' ) === 'password';

				$input.attr( 'type', isHidden ? 'text' : 'password' );
				$btn.text( isHidden ? wzpAdmin.labelHide : wzpAdmin.labelShow )
				    .attr( 'aria-pressed', isHidden ? 'true' : 'false' );
			} );
		},

		/**
		 * Set the status span text and colour.
		 * @param {string} msg
		 * @param {string} type  'success' | 'error' | 'info'
		 */
		setStatus: function ( msg, type ) {
			var colours = { success: '#00a32a', error: '#b32d2e', info: '#666' };
			$( '#wzp-ig-status' )
				.text( msg )
				.css( 'color', colours[ type ] || colours.info );
		},

		// ── Test Connection ───────────────────────────────────────────────────
		bindTestConnection: function () {
			var self = this;

			$( '#wzp-ig-test' ).on( 'click', function () {
				var $btn  = $( this );
				var token = $( '#wzp-ig-token' ).val().trim();
				var count = parseInt( $( '#wzp-ig-count' ).val(), 10 ) || 6;

				if ( ! token ) {
					self.setStatus( wzpAdmin.igNoToken, 'error' );
					return;
				}

				$btn.prop( 'disabled', true );
				$( '#wzp-ig-clear-cache' ).prop( 'disabled', true );
				self.setStatus( wzpAdmin.igTesting, 'info' );

				$.post(
					wzpAdmin.ajaxUrl,
					{
						action : 'wzp_test_instagram',
						nonce  : wzpAdmin.igNonce,
						token  : token,
						count  : count
					},
					function ( res ) {
						if ( res.success ) {
							self.setStatus( res.data.message, 'success' );
						} else {
							self.setStatus( res.data.message, 'error' );
						}
					}
				).fail( function () {
					self.setStatus( wzpAdmin.igError, 'error' );
				} ).always( function () {
					$btn.prop( 'disabled', false );
					$( '#wzp-ig-clear-cache' ).prop( 'disabled', false );
				} );
			} );
		},

		// ── Clear Cache ───────────────────────────────────────────────────────
		bindClearCache: function () {
			var self = this;

			$( '#wzp-ig-clear-cache' ).on( 'click', function () {
				var $btn = $( this );

				$btn.prop( 'disabled', true );
				$( '#wzp-ig-test' ).prop( 'disabled', true );

				$.post(
					wzpAdmin.ajaxUrl,
					{
						action : 'wzp_clear_ig_cache',
						nonce  : wzpAdmin.igNonce
					},
					function ( res ) {
						var type = res.success ? 'success' : 'error';
						self.setStatus( res.data.message, type );
					}
				).fail( function () {
					self.setStatus( wzpAdmin.igError, 'error' );
				} ).always( function () {
					$btn.prop( 'disabled', false );
					$( '#wzp-ig-test' ).prop( 'disabled', false );
				} );
			} );
		}
	};

	// ── 7. Banner Cards — image pickers ─────────────────────────────────────

	var WZP_BannerCards = {

		frame    : null,
		$context : null,   // the .wzp-bce-card that triggered the picker

		init: function () {
			if ( ! $( '.wzp-bce-card' ).length ) { return; }

			var self = this;

			// Icon picker — toggle active class on selection.
			$( '.wzp-banner-card-editor' ).on( 'change', '.wzp-bce-icon-opt input[type="radio"]', function () {
				var $picker = $( this ).closest( '.wzp-bce-icon-picker' );
				$picker.find( '.wzp-bce-icon-opt' ).removeClass( 'wzp-bce-icon-opt--active' );
				$( this ).closest( '.wzp-bce-icon-opt' ).addClass( 'wzp-bce-icon-opt--active' );
			} );

			// Select image (delegated to all 4 cards).
			$( '.wzp-banner-card-editor' ).on( 'click', '.wzp-bce-media-btn', function () {
				self.$context = $( this ).closest( '.wzp-bce-card' );
				self.openPicker();
			} );

			// Remove image.
			$( '.wzp-banner-card-editor' ).on( 'click', '.wzp-bce-media-remove', function () {
				var $card = $( this ).closest( '.wzp-bce-card' );
				$card.find( '.wzp-bce-image-id' ).val( '' );
				$card.find( '.wzp-bce-preview' ).html( '' );
				$card.find( '.wzp-bce-media-btn' ).text( wzpAdmin.labelSelectImage );
				$( this ).hide();
			} );
		},

		openPicker: function () {
			if ( ! this.frame ) {
				this.frame = wp.media( {
					title    : wzpAdmin.labelMediaTitle,
					button   : { text: wzpAdmin.labelMediaButton },
					multiple : false,
					library  : { type: 'image' }
				} );
				this.frame.on( 'select', this.onSelect.bind( this ) );
			}
			this.frame.open();
		},

		onSelect: function () {
			if ( ! this.$context ) { return; }

			var attachment = this.frame.state().get( 'selection' ).first().toJSON();
			var $card      = this.$context;

			$card.find( '.wzp-bce-image-id' ).val( attachment.id );

			var previewUrl = ( attachment.sizes && attachment.sizes.medium )
				? attachment.sizes.medium.url
				: attachment.url;

			$card.find( '.wzp-bce-preview' ).html(
				'<img src="' + $( '<div>' ).text( previewUrl ).html() + '" alt="">'
			);
			$card.find( '.wzp-bce-media-btn' ).text( wzpAdmin.labelChangeImage );
			$card.find( '.wzp-bce-media-remove' ).show();
		}
	};

	// ── 8. Category Icons — per-row direct upload ────────────────────────────

	var WZP_CategoryIcons = {

		init: function () {
			if ( ! $( '.wzp-cat-upload-btn' ).length ) { return; }
			this.bindUploadBtn();
			this.bindFileInput();
			this.bindRemoveBtn();
			this.bindSvgBtn();
		},

		// "Upload Icon" button → trigger the hidden file input on the same row.
		bindUploadBtn: function () {
			$( document ).on( 'click', '.wzp-cat-upload-btn', function () {
				$( this ).closest( '.wzp-cat-icon-row' ).find( '.wzp-cat-file-input' ).trigger( 'click' );
			} );
		},

		// File chosen → AJAX upload.
		bindFileInput: function () {
			$( document ).on( 'change', '.wzp-cat-file-input', function () {
				var file = this.files[0];
				if ( ! file ) { return; }

				var $input  = $( this );
				var $row    = $input.closest( '.wzp-cat-icon-row' );
				var $btn    = $row.find( '.wzp-cat-upload-btn' );
				var $status = $row.find( '.wzp-cat-upload-status' );

				$btn.prop( 'disabled', true );
				$status.text( 'Uploading…' ).css( 'color', '#666' );

				var fd = new FormData();
				fd.append( 'action',        'wzp_upload_icon' );
				fd.append( 'nonce',         wzpAdmin.uploadIconNonce );
				fd.append( 'wzp_icon_file', file );

				$.ajax( {
					url         : wzpAdmin.ajaxUrl,
					type        : 'POST',
					data        : fd,
					processData : false,
					contentType : false,
					success: function ( res ) {
						$btn.prop( 'disabled', false );
						$input.val( '' );

						if ( res.success ) {
							var icon = res.data;

							// Store filename in hidden input.
							$row.find( '.wzp-cat-hidden-input' ).val( icon.filename );

							// SVG: render inline to avoid MIME/CORS issues. Others: use <img>.
							var previewHtml = icon.svg_content
								? '<div style="width:32px;height:32px;display:flex;align-items:center;">' + icon.svg_content + '</div>'
								: '<img src="' + $( '<div>' ).text( icon.url ).html() + '" alt="" style="width:32px;height:32px;object-fit:contain;">';

							$row.find( '.wzp-cat-icon-row__preview' ).html( previewHtml );

							// Show the Remove button.
							$row.find( '.wzp-cat-remove-btn' ).show();

							$status.text( 'Uploaded!' ).css( 'color', '#00a32a' );
							setTimeout( function () { $status.text( '' ); }, 2000 );
						} else {
							var msg = ( res.data && res.data.message ) ? res.data.message : 'Upload failed.';
							$status.text( msg ).css( 'color', '#b32d2e' );
						}
					},
					error: function () {
						$btn.prop( 'disabled', false );
						$input.val( '' );
						$status.text( 'Upload failed. Please try again.' ).css( 'color', '#b32d2e' );
					}
				} );
			} );
		},

		// "Remove" button → clear assignment on this row.
		bindRemoveBtn: function () {
			$( document ).on( 'click', '.wzp-cat-remove-btn', function () {
				var $row = $( this ).closest( '.wzp-cat-icon-row' );

				// Clear hidden input.
				$row.find( '.wzp-cat-hidden-input' ).val( '' );

				// Reset preview to "None".
				$row.find( '.wzp-cat-icon-row__preview' ).html(
					'<span class="wzp-cat-icon-none">— None —</span>'
				);

				// Hide the Remove button.
				$( this ).hide();
			} );
		},

		// "SVG Code" button → toggle the inline SVG editor.
		bindSvgBtn: function () {
			$( document ).on( 'click', '.wzp-cat-svg-btn', function () {
				var $editor = $( this ).closest( '.wzp-cat-icon-row__actions' ).find( '.wzp-cat-svg-editor' );
				$editor.slideToggle( 150 );
				if ( $editor.is( ':visible' ) ) {
					$editor.find( '.wzp-cat-svg-textarea' ).trigger( 'focus' );
				}
			} );

			// Cancel — hide the editor.
			$( document ).on( 'click', '.wzp-cat-svg-cancel', function () {
				$( this ).closest( '.wzp-cat-svg-editor' ).slideUp( 150 );
			} );

			// Save — AJAX to wzp_save_svg_icon.
			$( document ).on( 'click', '.wzp-cat-svg-save', function () {
				var $btn     = $( this );
				var $editor  = $btn.closest( '.wzp-cat-svg-editor' );
				var $row     = $btn.closest( '.wzp-cat-icon-row' );
				var $status  = $row.find( '.wzp-cat-upload-status' );
				var svgCode  = $editor.find( '.wzp-cat-svg-textarea' ).val().trim();
				var termId   = $row.data( 'term-id' );

				if ( ! svgCode ) {
					$status.text( 'Please paste SVG code first.' ).css( 'color', '#b32d2e' );
					return;
				}

				$btn.prop( 'disabled', true );
				$status.text( 'Saving…' ).css( 'color', '#666' );

				$.post(
					wzpAdmin.ajaxUrl,
					{
						action   : 'wzp_save_svg_icon',
						nonce    : wzpAdmin.svgIconNonce,
						svg_code : svgCode,
						term_id  : termId
					},
					function ( res ) {
						$btn.prop( 'disabled', false );

						if ( res.success ) {
							var icon = res.data;

							// Store filename.
							$row.find( '.wzp-cat-hidden-input' ).val( icon.filename );

							// Show inline SVG preview (renders properly without CORS issues).
							$row.find( '.wzp-cat-icon-row__preview' ).html(
								'<div style="width:32px;height:32px;">' + svgCode + '</div>'
							);

							// Show Remove button, hide editor.
							$row.find( '.wzp-cat-remove-btn' ).show();
							$editor.slideUp( 150 );
							$editor.find( '.wzp-cat-svg-textarea' ).val( '' );

							$status.text( 'Saved!' ).css( 'color', '#00a32a' );
							setTimeout( function () { $status.text( '' ); }, 2000 );
						} else {
							var msg = ( res.data && res.data.message ) ? res.data.message : 'Save failed.';
							$status.text( msg ).css( 'color', '#b32d2e' );
						}
					}
				).fail( function () {
					$btn.prop( 'disabled', false );
					$status.text( 'Request failed. Please try again.' ).css( 'color', '#b32d2e' );
				} );
			} );
		}
	};

	
	// ── 9. Lookbook — visual canvas hotspot editor ─────────────────────────────

	var WZP_LookbookCanvas = {

		nextIndex    : 0,
		activePinIdx : null,
		searchTimer  : null,
		isDragging   : false,
		hasMoved     : false,
		$dragPin     : null,
		dragStartX   : 0,
		dragStartY   : 0,
		pinStartLeft : 0,
		pinStartTop  : 0,

		init: function () {
			if ( ! $( '#wzp-canvas-outer' ).length ) { return; }
			this.nextIndex = $( '#wzp-hs-data-store .wzp-hs-row' ).length;
			this.bindCanvasEvents();
			this.bindPopupEvents();
			this.bindDrag();
			this.bindPreview();
		},

		bindCanvasEvents: function () {
			var self = this;
			$( '#wzp-canvas-wrap' ).on( 'click', function ( e ) {
				if ( $( e.target ).closest( '.wzp-canvas-pin' ).length ) { return; }
				if ( self.hasMoved ) { self.hasMoved = false; return; }
				var $wrap  = $( '#wzp-canvas-wrap' );
				var offset = $wrap.offset();
				var x = Math.max( 0, Math.min( 100, ( ( e.pageX - offset.left ) / $wrap.outerWidth()  ) * 100 ) );
				var y = Math.max( 0, Math.min( 100, ( ( e.pageY - offset.top  ) / $wrap.outerHeight() ) * 100 ) );
				x = Math.round( x * 100 ) / 100;
				y = Math.round( y * 100 ) / 100;
				var idx = self.addPin( x, y, 0 );
				self.openPopup( idx );
			} );
		},

		bindPopupEvents: function () {
			var self = this;
			$( '#wzp-canvas-wrap' ).on( 'click', '.wzp-hs-popup__close', function () {
				self.closePopup();
			} );
			$( document ).on( 'input', '#wzp-hs-search', function () {
				clearTimeout( self.searchTimer );
				var term = $.trim( $( this ).val() );
				$( '#wzp-hs-results' ).empty();
				if ( term.length < 1 ) { return; }
				self.searchTimer = setTimeout( function () { self.doSearch( term ); }, 300 );
			} );
			$( document ).on( 'click', '.wzp-hs-result', function () {
				self.assignProduct( self.activePinIdx, $( this ).data( 'id' ), $( this ).data( 'name' ), $( this ).data( 'price' ), $( this ).data( 'thumb' ) );
				self.closePopup();
			} );
			$( document ).on( 'click', '#wzp-hs-remove-pin', function () {
				if ( self.activePinIdx !== null ) {
					self.removePin( self.activePinIdx );
					self.closePopup();
				}
			} );
			$( '#wzp-canvas-wrap' ).on( 'click', '.wzp-canvas-pin__remove', function ( e ) {
				e.stopPropagation();
				var idx = parseInt( $( this ).closest( '.wzp-canvas-pin' ).data( 'index' ), 10 );
				self.closePopup();
				self.removePin( idx );
			} );
			$( document ).on( 'mousedown', function ( e ) {
				if (
					! $( e.target ).closest( '#wzp-hs-popup' ).length &&
					! $( e.target ).closest( '.wzp-canvas-pin' ).length
				) { self.closePopup(); }
			} );
		},

		bindDrag: function () {
			var self  = this;
			var $wrap = $( '#wzp-canvas-wrap' );
			$wrap.on( 'mousedown', '.wzp-canvas-pin', function ( e ) {
				e.preventDefault();
				self.isDragging   = true;
				self.hasMoved     = false;
				self.$dragPin     = $( this );
				self.dragStartX   = e.clientX;
				self.dragStartY   = e.clientY;
				self.pinStartLeft = parseFloat( self.$dragPin[0].style.left ) || 0;
				self.pinStartTop  = parseFloat( self.$dragPin[0].style.top  ) || 0;
				self.closePopup();
			} );
			$( document ).on( 'mousemove.wzpcanvas', function ( e ) {
				if ( ! self.isDragging || ! self.$dragPin ) { return; }
				var dx = e.clientX - self.dragStartX;
				var dy = e.clientY - self.dragStartY;
				if ( Math.abs( dx ) > 3 || Math.abs( dy ) > 3 ) {
					self.hasMoved = true;
					self.$dragPin.addClass( 'wzp-canvas-pin--dragging' );
				}
				if ( ! self.hasMoved ) { return; }
				var newL = Math.max( 0, Math.min( 100, self.pinStartLeft + ( dx / $wrap[0].offsetWidth  * 100 ) ) );
				var newT = Math.max( 0, Math.min( 100, self.pinStartTop  + ( dy / $wrap[0].offsetHeight * 100 ) ) );
				self.$dragPin.css( { left: newL + '%', top: newT + '%' } );
			} );
			$( document ).on( 'mouseup.wzpcanvas', function () {
				if ( ! self.isDragging || ! self.$dragPin ) { return; }
				self.isDragging = false;
				self.$dragPin.removeClass( 'wzp-canvas-pin--dragging' );
				if ( self.hasMoved ) {
					var idx  = parseInt( self.$dragPin.data( 'index' ), 10 );
					var newX = Math.round( parseFloat( self.$dragPin[0].style.left ) * 100 ) / 100;
					var newY = Math.round( parseFloat( self.$dragPin[0].style.top  ) * 100 ) / 100;
					$( '#wzp-hs-data-store .wzp-hs-row[data-index="' + idx + '"] input[name*="[x]"]' ).val( newX );
					$( '#wzp-hs-data-store .wzp-hs-row[data-index="' + idx + '"] input[name*="[y]"]' ).val( newY );
				}
				self.$dragPin = null;
			} );
		},

		addPin: function ( x, y, productId ) {
			var idx = this.nextIndex++;
			var num = $( '#wzp-canvas-pins .wzp-canvas-pin' ).length + 1;
			var $pin = $( '<button type="button" class="wzp-canvas-pin"></button>' )
				.attr( 'data-index', idx )
				.css( { left: x + '%', top: y + '%' } )
				.attr( 'title', productId ? 'Product #' + productId : 'Click to assign product' );
			$pin.append( '<span class="wzp-canvas-pin__num">' + num + '</span>' );
			$pin.append( '<span class="wzp-canvas-pin__remove" title="Remove pin">&#x2715;</span>' );
			$( '#wzp-canvas-pins' ).append( $pin );
			var $row = $( '<div class="wzp-hs-row"></div>' ).attr( 'data-index', idx );
			$row.append( '<input type="hidden" name="wzp_lookbook_options[hotspots][' + idx + '][x]" value="' + x + '">' );
			$row.append( '<input type="hidden" name="wzp_lookbook_options[hotspots][' + idx + '][y]" value="' + y + '">' );
			$row.append( '<input type="hidden" name="wzp_lookbook_options[hotspots][' + idx + '][product_id]" value="' + ( productId || 0 ) + '">' );
			$( '#wzp-hs-data-store' ).append( $row );
			return idx;
		},

		removePin: function ( idx ) {
			$( '#wzp-canvas-pins .wzp-canvas-pin[data-index="' + idx + '"]' ).remove();
			$( '#wzp-hs-data-store .wzp-hs-row[data-index="' + idx + '"]' ).remove();
			var num = 1;
			$( '#wzp-canvas-pins .wzp-canvas-pin' ).each( function () {
				$( this ).find( '.wzp-canvas-pin__num' ).text( num++ );
			} );
		},

		openPopup: function ( idx ) {
			var $pin = $( '#wzp-canvas-pins .wzp-canvas-pin[data-index="' + idx + '"]' );
			if ( ! $pin.length ) { return; }
			this.activePinIdx = idx;
			var pid = $( '#wzp-hs-data-store .wzp-hs-row[data-index="' + idx + '"] input[name*="[product_id]"]' ).val();
			$( '#wzp-hs-search' ).val( pid && pid !== '0' ? pid : '' );
			$( '#wzp-hs-results' ).empty();
			if ( pid && pid !== '0' ) { this.doSearch( pid ); }
			var $wrap = $( '#wzp-canvas-wrap' );
			var pos   = $pin.position();
			var pinW  = $pin.outerWidth();
			var popW  = 270;
			var left  = pos.left + pinW + 10;
			if ( left + popW > $wrap.outerWidth() ) { left = pos.left - popW - 10; }
			$( '#wzp-hs-popup' )
				.css( { left: left + 'px', top: Math.max( 0, pos.top - 10 ) + 'px' } )
				.removeAttr( 'hidden' )
				.show();
			$( '#wzp-hs-search' ).trigger( 'focus' );
		},

		closePopup: function () {
			$( '#wzp-hs-popup' ).hide().attr( 'hidden', 'hidden' );
			this.activePinIdx = null;
		},

		doSearch: function ( term ) {
			var $results = $( '#wzp-hs-results' );
			$results.html( '<p class="wzp-hs-status">' + wzpAdmin.labelSearching + '</p>' );
			$.ajax( {
				url  : wzpAdmin.ajaxUrl,
				type : 'POST',
				data : { action: 'wzp_search_products', nonce: wzpAdmin.searchProductsNonce, term: term },
				success: function ( res ) {
					$results.empty();
					if ( ! res.success || ! res.data.length ) {
						$results.html( '<p class="wzp-hs-status">' + wzpAdmin.labelNoProducts + '</p>' );
						return;
					}
					$.each( res.data, function ( _i, p ) {
						var $item = $( '<button type="button" class="wzp-hs-result"></button>' )
							.attr( 'data-id', p.id ).attr( 'data-name', p.name )
							.attr( 'data-price', p.price || '' ).attr( 'data-thumb', p.thumb || '' );
						if ( p.thumb ) {
							$item.append( '<img src="' + $( '<div>' ).text( p.thumb ).html() + '" alt="">' );
						}
						$item.append(
							'<span class="wzp-hs-result__info">'
							+ '<span class="wzp-hs-result__name">' + $( '<div>' ).text( p.name ).html() + '</span>'
							+ '<span class="wzp-hs-result__price">' + p.price + '</span>'
							+ '</span>'
						);
						$results.append( $item );
					} );
				}
			} );
		},

		assignProduct: function ( idx, pid, name, price, thumb ) {
			$( '#wzp-hs-data-store .wzp-hs-row[data-index="' + idx + '"] input[name*="[product_id]"]' ).val( pid );
			$( '#wzp-canvas-pins .wzp-canvas-pin[data-index="' + idx + '"]' )
				.attr( 'title', name )
				.attr( 'data-product-name',  name  || '' )
				.attr( 'data-product-price', price || '' )
				.attr( 'data-product-thumb', thumb || '' )
				.addClass( 'wzp-canvas-pin--assigned' );
		},

		bindPreview: function () {
			$( '#wzp-canvas-pins' ).on( 'mouseenter', '.wzp-canvas-pin--assigned', function () {
				var $pin  = $( this );
				var name  = $pin.attr( 'data-product-name'  ) || '';
				var price = $pin.attr( 'data-product-price' ) || '';
				var thumb = $pin.attr( 'data-product-thumb' ) || '';
				if ( ! name ) { return; }
				var imgHtml = thumb
					? '<img src="' + $( '<div>' ).text( thumb ).html() + '" alt="" class="wzp-pin-preview__img" width="60" height="60">'
					: '';
				var $card = $(
					'<div class="wzp-pin-preview">'
					+ '<div class="wzp-pin-preview__info">'
					+ '<span class="wzp-pin-preview__name">' + $( '<div>' ).text( name ).html() + '</span>'
					+ '<span class="wzp-pin-preview__price">' + price + '</span>'
					+ '</div>'
					+ imgHtml
					+ '</div>'
				);
				$pin.append( $card );
			} ).on( 'mouseleave', '.wzp-canvas-pin--assigned', function () {
				$( this ).find( '.wzp-pin-preview' ).remove();
			} );
		},

		updateCanvasImage: function ( url ) {
			var $outer = $( '#wzp-canvas-outer' );
			if ( ! url ) {
				$( '#wzp-canvas-wrap' ).hide();
				$( '#wzp-canvas-placeholder' ).show();
				return;
			}
			$( '#wzp-canvas-placeholder' ).hide();
			var $img = $( '#wzp-canvas-img' );
			if ( $img.length ) {
				$img.attr( 'src', url );
				$( '#wzp-canvas-wrap' ).show();
			} else {
				var safeUrl = $( '<div>' ).text( url ).html();
				$outer.prepend(
					'<div id="wzp-canvas-wrap" class="wzp-canvas-wrap">'
					+ '<img id="wzp-canvas-img" src="' + safeUrl + '" alt="" draggable="false">'
					+ '<div id="wzp-canvas-pins"></div>'
					+ '<div id="wzp-hs-popup" class="wzp-hs-popup" hidden>'
					+ '<div class="wzp-hs-popup__head">'
					+ '<span class="wzp-hs-popup__title">' + wzpAdmin.labelAssignProduct + '</span>'
					+ '<button type="button" class="wzp-hs-popup__close">&#x2715;</button>'
					+ '</div>'
					+ '<div class="wzp-hs-popup__body">'
					+ '<input type="text" id="wzp-hs-search" class="wzp-hs-search-input" autocomplete="off">'
					+ '<div id="wzp-hs-results" class="wzp-hs-results"></div>'
					+ '<div class="wzp-hs-popup__footer">'
					+ '<button type="button" id="wzp-hs-remove-pin" class="button-link button-link-delete">' + wzpAdmin.labelRemovePin + '</button>'
					+ '</div></div></div></div>'
				);
				this.bindCanvasEvents();
				this.bindDrag();
			}
		}
	};
	// ── 10. Single Banner — image picker ──────────────────────────────────────

	var WZP_SingleBannerAdmin = {

		frame: null,

		init: function () {
			if ( ! $( '#wzp-sb-media-btn' ).length ) { return; }
			var self = this;
			$( '#wzp-sb-media-btn' ).on( 'click', function () { self.openPicker(); } );
			$( '#wzp-sb-media-remove' ).on( 'click', function () { self.removeImage(); } );
		},

		openPicker: function () {
			if ( ! this.frame ) {
				this.frame = wp.media( {
					title   : wzpAdmin.labelMediaTitle,
					button  : { text: wzpAdmin.labelMediaButton },
					multiple: false,
					library : { type: 'image' }
				} );
				this.frame.on( 'select', this.onSelect.bind( this ) );
			}
			this.frame.open();
		},

		onSelect: function () {
			var attachment = this.frame.state().get( 'selection' ).first().toJSON();
			var previewUrl = ( attachment.sizes && attachment.sizes.medium )
				? attachment.sizes.medium.url : attachment.url;
			$( '#wzp-sb-image-id' ).val( attachment.id );
			$( '#wzp-sb-preview' ).html( '<img src="' + $( '<div>' ).text( previewUrl ).html() + '" alt="">' );
			$( '#wzp-sb-media-btn' ).text( wzpAdmin.labelChangeImage );
			$( '#wzp-sb-media-remove' ).show();
		},

		removeImage: function () {
			$( '#wzp-sb-image-id' ).val( '' );
			$( '#wzp-sb-preview' ).html( '' );
			$( '#wzp-sb-media-btn' ).text( wzpAdmin.labelSelectImage );
			$( '#wzp-sb-media-remove' ).hide();
		}
	};

	// ── 11. Product Grid Manager ──────────────────────────────────────────────

	var WZP_GridManager = {

		$form    : null,
		$list    : null,
		$addBtn  : null,
		editing  : false,

		init: function () {
			this.$addBtn = $( '#wzp-gm-add-btn' );
			if ( ! this.$addBtn.length ) { return; }
			this.$form   = $( '#wzp-gm-form-wrap' );
			this.$list   = $( '#wzp-gm-list' );
			this.bindEvents();
		},

		bindEvents: function () {
			var self = this;

			// Open blank form
			this.$addBtn.on( 'click', function () {
				self.openForm( null );
			} );

			// Cancel
			$( '#wzp-gm-cancel-btn' ).on( 'click', function () {
				self.closeForm();
			} );

			// Save
			$( '#wzp-gm-save-btn' ).on( 'click', function () {
				self.saveGrid();
			} );

			// Edit (delegated — cards are dynamic)
			this.$list.on( 'click', '.wzp-gm-edit-btn', function () {
				var grid = $( this ).data( 'grid' );
				self.openForm( grid );
			} );

			// Delete (delegated)
			this.$list.on( 'click', '.wzp-gm-delete-btn', function () {
				var id = $( this ).data( 'grid-id' );
				if ( ! window.confirm( wzpAdmin.labelConfirmDelete ) ) { return; }
				self.deleteGrid( id, $( this ).closest( '.wzp-gm-card' ) );
			} );

			// Copy shortcode (delegated)
			this.$list.on( 'click', '.wzp-gm-copy-btn', function () {
				var sc  = $( this ).data( 'shortcode' );
				var $btn = $( this );
				navigator.clipboard.writeText( sc ).then( function () {
					var orig = $btn.text();
					$btn.text( 'Copied!' );
					setTimeout( function () { $btn.text( orig ); }, 1500 );
				} );
			} );
		},

		/* ── Form helpers ────────────────────────────────────────── */

		openForm: function ( grid ) {
			this.editing = !! grid;

			// Reset
			$( '#wzp-gm-id' ).val( '' );
			$( '#wzp-gm-label' ).val( '' );
			$( '.wzp-gm-cat-cb' ).prop( 'checked', false );
			$( '.wzp-gm-columns[value="4"]' ).prop( 'checked', true );
			$( '#wzp-gm-count' ).val( 8 );
			$( '#wzp-gm-orderby' ).val( 'date' );
			$( '.wzp-gm-form__title' ).text( wzpAdmin.labelNewGrid || 'New Grid' );

			if ( grid ) {
				$( '#wzp-gm-id' ).val( grid.id || '' );
				$( '#wzp-gm-label' ).val( grid.label || '' );

				var cats = grid.categories || [];
				$.each( cats, function ( _i, slug ) {
					$( '.wzp-gm-cat-cb[value="' + slug + '"]' ).prop( 'checked', true );
				} );

				var col = grid.columns || '4';
				$( '.wzp-gm-columns[value="' + col + '"]' ).prop( 'checked', true );
				$( '#wzp-gm-count' ).val( grid.count || 8 );
				$( '#wzp-gm-orderby' ).val( grid.orderby || 'date' );
				$( '.wzp-gm-form__title' ).text( wzpAdmin.labelEditGrid || 'Edit Grid' );
			}

			var $gf = this.$form;
			$gf.slideDown( 200, function () {
				$( '#wzp-gm-label' ).trigger( 'focus' );
				$( 'html, body' ).animate( { scrollTop: $gf.offset().top - 40 }, 200 );
			} );
		},

		closeForm: function () {
			this.$form.slideUp( 200 );
		},

		/* ── AJAX ────────────────────────────────────────────────── */

		saveGrid: function () {
			var self = this;

			var cats = [];
			$( '.wzp-gm-cat-cb:checked' ).each( function () {
				cats.push( $( this ).val() );
			} );

			var grid = {
				id         : $( '#wzp-gm-id' ).val(),
				label      : $( '#wzp-gm-label' ).val().trim(),
				categories : cats,
				columns    : $( '.wzp-gm-columns:checked' ).val() || '4',
				count      : $( '#wzp-gm-count' ).val(),
				orderby    : $( '#wzp-gm-orderby' ).val()
			};

			if ( ! grid.label ) {
				$( '#wzp-gm-label' ).trigger( 'focus' );
				return;
			}

			$( '#wzp-gm-save-btn' ).prop( 'disabled', true );
			$( '#wzp-gm-saving' ).show();

			$.post(
				wzpAdmin.ajaxUrl,
				{
					action : 'wzp_save_grid',
					nonce  : wzpAdmin.gridNonce,
					grid   : grid
				},
				function ( res ) {
					if ( res.success ) {
						self.closeForm();
						self.upsertCard( res.data.grid );
					}
				}
			).always( function () {
				$( '#wzp-gm-save-btn' ).prop( 'disabled', false );
				$( '#wzp-gm-saving' ).hide();
			} );
		},

		deleteGrid: function ( id, $card ) {
			$.post(
				wzpAdmin.ajaxUrl,
				{
					action  : 'wzp_delete_grid',
					nonce   : wzpAdmin.gridNonce,
					grid_id : id
				},
				function ( res ) {
					if ( res.success ) {
						$card.fadeOut( 200, function () {
							$card.remove();
							if ( ! $( '.wzp-gm-card' ).length ) {
								$( '#wzp-gm-list' ).html( '<p class="wzp-gm-empty" id="wzp-gm-empty">No grids yet. Click "Add New Grid" to create your first one.</p>' );
							}
						} );
					}
				}
			);
		},

		/* ── DOM update ──────────────────────────────────────────── */

		upsertCard: function ( grid ) {
			var cats = grid.categories || [];

			// Build category display names from wzpAdmin.wcCategories
			var catNames = cats.map( function ( slug ) {
				var found = ( wzpAdmin.wcCategories || [] ).find( function ( c ) { return c.slug === slug; } );
				return found ? found.name : slug;
			} );

			var orderbyMap = {
				date       : 'Date (newest)',
				popularity : 'Popularity',
				rating     : 'Avg Rating',
				price      : 'Price (lowest first)'
			};

			var shortcode  = '[wzp_product_grid grid_id="' + grid.id + '"]';
			var catDisplay = catNames.length ? catNames.join( ', ' ) : 'All categories';
			var obLabel    = orderbyMap[ grid.orderby ] || grid.orderby;

			var cardHtml = '<div class="wzp-gm-card" data-grid-id="' + grid.id + '">'
				+ '<div class="wzp-gm-card__info">'
				+ '<strong class="wzp-gm-card__name">' + $( '<span>' ).text( grid.label || '(Unnamed)' ).html() + '</strong>'
				+ '<span class="wzp-gm-card__meta">'
				+ '<span>' + $( '<span>' ).text( catDisplay ).html() + '</span>'
				+ ' &middot; <span>' + grid.columns + ' cols</span>'
				+ ' &middot; <span>' + grid.count + ' products</span>'
				+ ' &middot; <span>' + obLabel + '</span>'
				+ '</span>'
				+ '<div class="wzp-gm-card__shortcode-row">'
				+ '<code class="wzp-shortcode-preview">' + $( '<span>' ).text( shortcode ).html() + '</code>'
				+ '<button type="button" class="button wzp-copy-btn wzp-gm-copy-btn" data-shortcode="' + shortcode + '">Copy</button>'
				+ '</div></div>'
				+ '<div class="wzp-gm-card__actions">'
				+ '<button type="button" class="button wzp-gm-edit-btn" data-grid="' + JSON.stringify( grid ).replace( /"/g, '&quot;' ) + '">Edit</button>'
				+ '<button type="button" class="button wzp-gm-delete-btn" data-grid-id="' + grid.id + '">Delete</button>'
				+ '</div></div>';

			var $existing = this.$list.find( '[data-grid-id="' + grid.id + '"]' );

			if ( $existing.length ) {
				$existing.replaceWith( cardHtml );
			} else {
				$( '#wzp-gm-empty' ).remove();
				this.$list.append( cardHtml );
			}
		}
	};

	// ── 12. Navbar Settings Admin ────────────────────────────────────────────

	var WZP_NavbarAdmin = {

		_mediaFrame: null,

		init: function () {
			if ( ! $( '#wzp-nb-save-btn' ).length ) { return; }
			this.bindEvents();
		},

		bindEvents: function () {
			var self = this;

			$( document ).on( 'change', 'input[name="nb_logo_type"]', function () {
				var val = $( this ).val();
				$( '.wzp-nb-logo-text-row' ).toggleClass( 'wzp-nb-hidden', val !== 'text' );
				$( '.wzp-nb-logo-image-row' ).toggleClass( 'wzp-nb-hidden', val !== 'image' );
			} );

			$( document ).on( 'click', '#nb-logo-select-btn', function () {
				self.openLogoPicker();
			} );

			$( document ).on( 'click', '#nb-logo-remove-btn', function () {
				$( '#nb_logo_id' ).val( '' );
				$( '#nb-logo-preview-img' ).attr( 'src', '' ).hide();
				$( '#nb-logo-select-btn' ).text( wzpAdmin.labelSelectImage || 'Select Logo' );
				$( '#nb-logo-remove-btn' ).hide();
			} );

			$( document ).on( 'input', '.wzp-nb-color-picker', function () {
				$( this ).siblings( '.wzp-nb-color-text' ).val( $( this ).val() );
			} );

			$( document ).on( 'input', '.wzp-nb-color-text', function () {
				var val = $( this ).val();
				if ( /^#[0-9a-fA-F]{6}$/.test( val ) ) {
					$( this ).siblings( '.wzp-nb-color-picker' ).val( val );
				}
			} );

			$( document ).on( 'click', '#wzp-nb-copy-sc', function () {
				var sc   = $( '.wzp-nb-shortcode-code' ).text();
				var $btn = $( this );
				navigator.clipboard.writeText( sc ).then( function () {
					var orig = $btn.text();
					$btn.text( 'Copied!' );
					setTimeout( function () { $btn.text( orig ); }, 1500 );
				} );
			} );

			$( document ).on( 'click', '#wzp-nb-save-btn', function () {
				self.save();
			} );
		},

		openLogoPicker: function () {
			if ( this._mediaFrame ) { this._mediaFrame.open(); return; }
			this._mediaFrame = wp.media( {
				title:    'Select Logo Image',
				button:   { text: 'Use this image' },
				library:  { type: 'image' },
				multiple: false,
			} );
			this._mediaFrame.on( 'select', function () {
				var attach = WZP_NavbarAdmin._mediaFrame.state().get( 'selection' ).first().toJSON();
				$( '#nb_logo_id' ).val( attach.id );
				var src = ( attach.sizes && attach.sizes.medium ) ? attach.sizes.medium.url : attach.url;
				$( '#nb-logo-preview-img' ).attr( 'src', src ).show();
				$( '#nb-logo-select-btn' ).text( 'Change Logo' );
				$( '#nb-logo-remove-btn' ).show();
			} );
			this._mediaFrame.open();
		},

		save: function () {
			var $btn = $( '#wzp-nb-save-btn' );
			$btn.prop( 'disabled', true ).text( 'Saving…' );

			$.post( wzpAdmin.ajaxUrl, {
				action:        'wzp_save_navbar',
				nonce:         wzpAdmin.navbarNonce,
				logo_type:     $( 'input[name="nb_logo_type"]:checked' ).val() || 'text',
				logo_id:       $( '#nb_logo_id' ).val() || 0,
				logo_text:     $( '#nb_logo_text' ).val(),
				menu_id:       $( '#nb_menu_id' ).val(),
				account_url:   $( '#nb_account_url' ).val(),
				wishlist_url:  $( '#nb_wishlist_url' ).val(),
				cart_url:      $( '#nb_cart_url' ).val(),
				show_search:   $( '#nb_show_search' ).is( ':checked' )   ? '1' : '0',
				show_account:  $( '#nb_show_account' ).is( ':checked' )  ? '1' : '0',
				show_wishlist: $( '#nb_show_wishlist' ).is( ':checked' ) ? '1' : '0',
				show_cart:     $( '#nb_show_cart' ).is( ':checked' )     ? '1' : '0',
				sticky:        $( '#nb_sticky' ).is( ':checked' )        ? '1' : '0',
				bg_color:      $( '#nb_bg_color' ).val(),
				text_color:    $( '#nb_text_color' ).val(),
				hover_color:   $( '#nb_hover_color' ).val(),
				active_color:  $( '#nb_active_color' ).val(),
				border_color:  $( '#nb_border_color' ).val(),
			}, function ( res ) {
				$btn.prop( 'disabled', false ).text( 'Save Navbar Settings' );
				if ( res.success ) {
					var $msg = $( '#wzp-nb-saved-msg' );
					$msg.fadeIn( 200 );
					setTimeout( function () { $msg.fadeOut( 400 ); }, 2500 );
				} else {
					alert( ( res.data && res.data.message ) || 'Save failed.' );
				}
			} ).fail( function () {
				$btn.prop( 'disabled', false ).text( 'Save Navbar Settings' );
				alert( 'Request failed. Please try again.' );
			} );
		}
	};

	// ── 13. Menu Builder ─────────────────────────────────────────────────────

	var WZP_MenuBuilder = {

		menus: [],

		init: function () {
			if ( ! $( '#wzp-mb-new-btn' ).length ) { return; }
			this.menus = ( wzpAdmin.savedMenus && Array.isArray( wzpAdmin.savedMenus ) )
				? wzpAdmin.savedMenus
				: [];
			this.bindEvents();
		},

		bindEvents: function () {
			var self = this;

			$( document ).on( 'click', '#wzp-mb-new-btn', function () {
				self.openEditor( null );
			} );

			$( document ).on( 'click', '.wzp-mb-edit-btn', function () {
				var menuId = $( this ).closest( '.wzp-mb-menu-row' ).data( 'menu-id' );
				var menu   = self.findMenu( menuId );
				if ( menu ) { self.openEditor( menu ); }
			} );

			$( document ).on( 'click', '.wzp-mb-delete-btn', function () {
				var $row   = $( this ).closest( '.wzp-mb-menu-row' );
				var menuId = $row.data( 'menu-id' );
				if ( ! window.confirm( 'Delete this menu?' ) ) { return; }
				self.deleteMenu( menuId, $row );
			} );

			$( document ).on( 'click', '#wzp-mb-add-item', function () {
				self.addItemRow( '', '', [] );
			} );

			$( document ).on( 'click', '.wzp-mb-add-child', function () {
				var $item = $( this ).closest( '.wzp-mb-item' );
				self.addChildRow( $item, '', '' );
			} );

			$( document ).on( 'click', '.wzp-mb-remove-item', function () {
				$( this ).closest( '.wzp-mb-item' ).remove();
			} );

			$( document ).on( 'click', '.wzp-mb-remove-child', function () {
				$( this ).closest( '.wzp-mb-child-row' ).remove();
			} );

			$( document ).on( 'click', '#wzp-mb-save-btn', function () {
				self.save();
			} );

			$( document ).on( 'click', '#wzp-mb-cancel-btn', function () {
				self.closeEditor();
			} );
		},

		findMenu: function ( menuId ) {
			for ( var i = 0; i < this.menus.length; i++ ) {
				if ( this.menus[ i ].id === menuId ) { return this.menus[ i ]; }
			}
			return null;
		},

		openEditor: function ( menu ) {
			var $editor = $( '#wzp-mb-editor' );
			var self    = this;

			$( '#wzp-mb-items' ).empty();
			$( '#wzp-mb-edit-id' ).val( menu ? menu.id : '' );
			$( '#wzp-mb-edit-name' ).val( menu ? menu.name : '' );

			if ( menu && menu.items ) {
				$.each( menu.items, function ( i, item ) {
					self.addItemRow( item.label || '', item.url || '', item.children || [] );
				} );
			}

			$( '#wzp-mb-new-btn' ).hide();
			$editor.slideDown( 200 );
			$( '#wzp-mb-edit-name' ).focus();
		},

		closeEditor: function () {
			$( '#wzp-mb-editor' ).slideUp( 200 );
			$( '#wzp-mb-new-btn' ).show();
			$( '#wzp-mb-saved-msg' ).hide();
		},

		addItemRow: function ( label, url, children ) {
			var self  = this;
			var $item = $( '<div class="wzp-mb-item">' +
				'<div class="wzp-mb-item-main">' +
					'<span class="wzp-mb-handle" aria-hidden="true">⠿</span>' +
					'<input type="text" class="wzp-mb-item-label regular-text" placeholder="Label" value="' + $( '<div>' ).text( label ).html() + '">' +
					'<input type="text" class="wzp-mb-item-url regular-text" placeholder="URL" value="' + $( '<div>' ).text( url ).html() + '">' +
					'<button type="button" class="button button-small wzp-mb-add-child">+ Sub</button>' +
					'<button type="button" class="button-link button-link-delete wzp-mb-remove-item">✕</button>' +
				'</div>' +
				'<div class="wzp-mb-children"></div>' +
			'</div>' );

			$( '#wzp-mb-items' ).append( $item );

			if ( children && children.length ) {
				$.each( children, function ( i, child ) {
					self.addChildRow( $item, child.label || '', child.url || '' );
				} );
			}
		},

		addChildRow: function ( $item, label, url ) {
			var $child = $( '<div class="wzp-mb-child-row">' +
				'<span class="wzp-mb-child-indent" aria-hidden="true">↳</span>' +
				'<input type="text" class="wzp-mb-child-label regular-text" placeholder="Sub label" value="' + $( '<div>' ).text( label ).html() + '">' +
				'<input type="text" class="wzp-mb-child-url regular-text" placeholder="Sub URL" value="' + $( '<div>' ).text( url ).html() + '">' +
				'<button type="button" class="button-link button-link-delete wzp-mb-remove-child">✕</button>' +
			'</div>' );

			$item.find( '.wzp-mb-children' ).append( $child );
		},

		collectItems: function () {
			var items = [];
			$( '#wzp-mb-items .wzp-mb-item' ).each( function () {
				var $item    = $( this );
				var label    = $item.find( '.wzp-mb-item-label' ).val().trim();
				var url      = $item.find( '.wzp-mb-item-url' ).val().trim();
				var children = [];

				$item.find( '.wzp-mb-child-row' ).each( function () {
					var cl = $( this ).find( '.wzp-mb-child-label' ).val().trim();
					var cu = $( this ).find( '.wzp-mb-child-url' ).val().trim();
					if ( cl ) { children.push( { label: cl, url: cu } ); }
				} );

				if ( label ) { items.push( { label: label, url: url, children: children } ); }
			} );
			return items;
		},

		save: function () {
			var self   = this;
			var $btn   = $( '#wzp-mb-save-btn' );
			var menuId = $( '#wzp-mb-edit-id' ).val();
			var name   = $( '#wzp-mb-edit-name' ).val().trim();

			if ( ! name ) { $( '#wzp-mb-edit-name' ).focus(); return; }

			$btn.prop( 'disabled', true ).text( 'Saving…' );

			$.post( wzpAdmin.ajaxUrl, {
				action:    'wzp_save_menu',
				nonce:     wzpAdmin.menuNonce,
				menu_id:   menuId,
				menu_name: name,
				items:     self.collectItems(),
			}, function ( res ) {
				$btn.prop( 'disabled', false ).text( 'Save Menu' );
				if ( res.success ) {
					self.menus = res.data.menus;
					self.renderList();
					self.syncNavbarSelect( res.data.menus );
					$( '#wzp-mb-saved-msg' ).fadeIn( 200 );
					setTimeout( function () {
						$( '#wzp-mb-saved-msg' ).fadeOut( 400 );
						self.closeEditor();
					}, 1200 );
				} else {
					alert( ( res.data && res.data.message ) || 'Save failed.' );
				}
			} ).fail( function () {
				$btn.prop( 'disabled', false ).text( 'Save Menu' );
				alert( 'Request failed. Please try again.' );
			} );
		},

		deleteMenu: function ( menuId, $row ) {
			var self = this;
			$.post( wzpAdmin.ajaxUrl, {
				action:  'wzp_delete_menu',
				nonce:   wzpAdmin.menuNonce,
				menu_id: menuId,
			}, function ( res ) {
				if ( res.success ) {
					self.menus = res.data.menus;
					$row.remove();
					self.syncNavbarSelect( res.data.menus );
					if ( ! $( '#wzp-mb-list .wzp-mb-menu-row' ).length ) {
						$( '#wzp-mb-list' ).html( '<p class="wzp-mb-empty">No menus yet. Create one below.</p>' );
					}
				} else {
					alert( ( res.data && res.data.message ) || 'Delete failed.' );
				}
			} ).fail( function () {
				alert( 'Request failed. Please try again.' );
			} );
		},

		renderList: function () {
			var $list = $( '#wzp-mb-list' ).empty();
			if ( ! this.menus.length ) {
				$list.html( '<p class="wzp-mb-empty">No menus yet. Create one below.</p>' );
				return;
			}
			$.each( this.menus, function ( i, m ) {
				$list.append(
					'<div class="wzp-mb-menu-row" data-menu-id="' + m.id + '">' +
						'<span class="wzp-mb-menu-name">' + $( '<div>' ).text( m.name ).html() + '</span>' +
						'<span class="wzp-mb-menu-id"><code>' + m.id + '</code></span>' +
						'<div class="wzp-mb-menu-actions">' +
							'<button type="button" class="button button-small wzp-mb-edit-btn">Edit</button>' +
							'<button type="button" class="button button-small button-link-delete wzp-mb-delete-btn">Delete</button>' +
						'</div>' +
					'</div>'
				);
			} );
		},

		syncNavbarSelect: function ( menus ) {
			var $sel    = $( '#nb_menu_id' );
			var current = $sel.val();
			$sel.empty().append( '<option value="">— None —</option>' );
			$.each( menus, function ( i, m ) {
				var $opt = $( '<option>' ).val( m.id ).text( m.name );
				if ( m.id === current ) { $opt.prop( 'selected', true ); }
				$sel.append( $opt );
			} );
		}
	};

	// ── 14. Product Detail Admin ──────────────────────────────────────────────

	var WZP_ProductDetailAdmin = {

		init: function () {
			if ( ! $( '#wzp-pd-save-btn' ).length ) { return; }
			this.bindEvents();
		},

		bindEvents: function () {
			var self = this;

			// Color pickers.
			$( document ).on( 'input', '.wzp-pd-color-picker', function () {
				$( this ).siblings( '.wzp-pd-color-text' ).val( $( this ).val() );
			} );
			$( document ).on( 'input', '.wzp-pd-color-text', function () {
				var val = $( this ).val();
				if ( /^#[0-9a-fA-F]{6}$/.test( val ) ) {
					$( this ).siblings( '.wzp-pd-color-picker' ).val( val );
				}
			} );

			// Benefits — add row.
			$( document ).on( 'click', '#wzp-pd-add-benefit', function () {
				var idx = $( '#wzp-pd-benefits .wzp-repeater-row' ).length;
				$( '#wzp-pd-benefits' ).append( self.benefitRowHtml( idx ) );
			} );

			// Shipping — add row.
			$( document ).on( 'click', '#wzp-pd-add-ship', function () {
				var idx = $( '#wzp-pd-shipping .wzp-repeater-row' ).length;
				$( '#wzp-pd-shipping' ).append( self.shipRowHtml( idx ) );
			} );

			// Remove repeater row.
			$( document ).on( 'click', '.wzp-repeater-remove', function () {
				$( this ).closest( '.wzp-repeater-row' ).remove();
			} );

			// Shortcode copy.
			$( document ).on( 'click', '#wzp-pd-copy-sc', function () {
				var $btn = $( this );
				navigator.clipboard.writeText( '[wzp_product_detail]' ).then( function () {
					var orig = $btn.text();
					$btn.text( 'Copied!' );
					setTimeout( function () { $btn.text( orig ); }, 1500 );
				} );
			} );

			// Save.
			$( document ).on( 'click', '#wzp-pd-save-btn', function () {
				self.save();
			} );
		},

		_iconSelectHtml: function ( cls ) {
			var opts = [
				[ 'leaf',    'Leaf — Sustainable'  ],
				[ 'return',  'Return — Refund'      ],
				[ 'diamond', 'Diamond — Premium'    ],
				[ 'truck',   'Truck — Shipping'     ],
				[ 'package', 'Package — Delivery'   ],
				[ 'shield',  'Shield — Guarantee'   ],
				[ 'heart',   'Heart — Care'         ],
				[ 'globe',   'Globe — Worldwide'    ],
				[ 'lock',    'Lock — Secure'        ],
				[ 'clock',   'Clock — Time'         ],
				[ 'check',   'Check — Verified'     ],
				[ 'gift',    'Gift'                  ],
				[ 'star',    'Star — Excellence'    ],
			];
			var html = '<select class="' + cls + '"><option value="">— Icon —</option>';
			$.each( opts, function ( i, o ) {
				html += '<option value="' + o[0] + '">' + o[1] + '</option>';
			} );
			return html + '</select>';
		},

		benefitRowHtml: function ( idx ) {
			return '<div class="wzp-repeater-row" data-index="' + idx + '">' +
				'<span class="wzp-repeater-handle" aria-hidden="true">⠿</span>' +
				this._iconSelectHtml( 'wzp-pd-benefit-icon' ) +
				'<input type="text" class="wzp-pd-benefit-title regular-text" placeholder="Title" value="">' +
				'<input type="text" class="wzp-pd-benefit-subtitle regular-text" placeholder="Subtitle" value="">' +
				'<button type="button" class="button-link button-link-delete wzp-repeater-remove" aria-label="Remove">✕</button>' +
			'</div>';
		},

		shipRowHtml: function ( idx ) {
			return '<div class="wzp-repeater-row" data-index="' + idx + '">' +
				'<span class="wzp-repeater-handle" aria-hidden="true">⠿</span>' +
				this._iconSelectHtml( 'wzp-pd-ship-icon' ) +
				'<input type="text" class="wzp-pd-ship-text large-text" placeholder="Shipping line text…" value="">' +
				'<button type="button" class="button-link button-link-delete wzp-repeater-remove" aria-label="Remove">✕</button>' +
			'</div>';
		},

		collectBenefits: function () {
			var out = [];
			$( '#wzp-pd-benefits .wzp-repeater-row' ).each( function () {
				var title = $( this ).find( '.wzp-pd-benefit-title' ).val().trim();
				if ( ! title ) { return; }
				out.push( {
					icon:     $( this ).find( '.wzp-pd-benefit-icon' ).val().trim(),
					title:    title,
					subtitle: $( this ).find( '.wzp-pd-benefit-subtitle' ).val().trim(),
				} );
			} );
			return out;
		},

		collectShipping: function () {
			var out = [];
			$( '#wzp-pd-shipping .wzp-repeater-row' ).each( function () {
				var text = $( this ).find( '.wzp-pd-ship-text' ).val().trim();
				if ( ! text ) { return; }
				out.push( {
					icon: $( this ).find( '.wzp-pd-ship-icon' ).val().trim(),
					text: text,
				} );
			} );
			return out;
		},

		save: function () {
			var self = this;
			var $btn = $( '#wzp-pd-save-btn' );
			$btn.prop( 'disabled', true ).text( 'Saving…' );

			$.post( wzpAdmin.ajaxUrl, {
				action:       'wzp_save_product_detail',
				nonce:        wzpAdmin.productDetailNonce,
				benefits:     self.collectBenefits(),
				shipping:     self.collectShipping(),
				accent_color: $( '#pd_accent_color' ).val(),
				btn_color:    $( '#pd_btn_color' ).val(),
				btn_text:     $( '#pd_btn_text' ).val(),
				price_color:  $( '#pd_price_color' ).val(),
			}, function ( res ) {
				$btn.prop( 'disabled', false ).text( 'Save Product Detail Settings' );
				if ( res.success ) {
					var $msg = $( '#wzp-pd-saved-msg' );
					$msg.fadeIn( 200 );
					setTimeout( function () { $msg.fadeOut( 400 ); }, 2500 );
				} else {
					alert( ( res.data && res.data.message ) || 'Save failed.' );
				}
			} ).fail( function () {
				$btn.prop( 'disabled', false ).text( 'Save Product Detail Settings' );
				alert( 'Request failed. Please try again.' );
			} );
		}
	};

	// ── Boot ──────────────────────────────────────────────────────────────────

	$( function () {
		WZP_SlideManager.init();
		WZP_LookbookAdmin.init();
		WZP_TestimonialsAdmin.init();
		WZP_InstagramAdmin.init();
		WZP_CarouselToggle.init();
		WZP_CategoryIcons.init();
		WZP_BannerCards.init();
		WZP_LookbookCanvas.init();
		WZP_SingleBannerAdmin.init();
		WZP_GridManager.init();
		WZP_NavbarAdmin.init();
		WZP_MenuBuilder.init();
		WZP_ProductDetailAdmin.init();
	} );

} )( jQuery );
