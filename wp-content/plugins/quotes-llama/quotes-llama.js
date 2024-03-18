/**
 * Quotes Llama JS
 *
 * Description. Javascript functions.
 *
 * @Link        http://wordpress.org/plugins/quotes-llama/
 * @package     quotes-llama
 * @since       1.0.0
 * Version:     1.3.4
 * License:     Copyheart
 * License URI: http://copyheart.org
 */

/**
 * Url for admin-ajax.php.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_ajaxurl = quotesllamaOption.ajaxurl;

/**
 * Page - Last localid in author list.
 *
 * @since 1.0.0
 * @var integer
 */
var quotes_llama_cid = 0;

/**
 * Page - Current localid in author list.
 *
 * @since 1.0.0
 * @var integer
 */
var quotes_llama_lastid = jQuery( '.quotes-llama-page-link' ).last().attr( 'localid' );

/**
 * Page - Background color.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_foregroundcolor = quotesllamaOption.ForegroundColor;

/**
 * Page - Foreground color.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_backgroundcolor = quotesllamaOption.BackgroundColor;

/**
 * Character limit before showing read more link.
 *
 * @since 1.0.0
 * @var integer
 */
var quotes_llama_quotelimit = quotesllamaOption.Limit;

/**
 * Whether to display the countdown timer.
 *
 * @since 1.0.0
 * @var integer
 */
var quotes_llama_show_timer = quotesllamaOption.GalleryShowTimer;

/**
 * Gallery interval... how long it displays.
 *
 * @since 1.0.0
 * @var integer
 */
var quotes_llama_galleryinterval = quotesllamaOption.GalleryInterval;

/**
 * Gallery minimum time for display.
 *
 * @since 1.0.0
 * @var integer
 */
var quotes_llama_galleryminimum = quotesllamaOption.GalleryMinimum;

/**
 * Quote limit Ellipses.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_ellipses = quotesllamaOption.Ellipses;

/**
 * Sidebar position... Left or right.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_sidebarpos = quotesllamaOption.Sidebarpos;

/**
 * Text displayed for read more link in limited quotes.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_moretext = quotesllamaOption.MoreText;

/**
 * Text displayed for read less link in limited quotes.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_lesstext = quotesllamaOption.LessText;

/**
 * Display quote source on a new line.
 *
 * @since 1.0.0
 * @var string
 */
var quotes_llama_sourcenewline = quotesllamaOption.SourceNewLine;

/**
 * Display icons in quotes and sources.
 *
 * @since 1.0.0
 * @var integer
 */
var quotes_llama_showicons = quotesllamaOption.ShowIcons;

/**
 * URL to uploads.
 *
 * @since 1.3.1
 * @var string
 */
var quotes_llama_this_url = quotesllamaOption.ThisURL;

/**
 * DIR to uploads.
 *
 * @since 1.3.1
 * @var string
 */
var quotes_llama_this_dir = quotesllamaOption.ThisDIR;

/**
 * Align image.
 *
 * @since 1.3.3
 * @var bool
 */
var quotes_llama_borderradius = quotesllamaOption.BorderRadius;

/**
 * Align quote text... left, center, right.
 *
 * @since 1.3.3
 * @var string
 */
var quotes_llama_alignquote = quotesllamaOption.AlignQuote;

/**
 * Center image above quote.
 *
 * @since 1.3.3
 * @var bool
 */
var quotes_llama_imageattop = quotesllamaOption.ImageAtTop;

/**
 * Transition Speed.
 *
 * @since 1.3.4
 * @var int
 */
var quotes_llama_transitionspeed = parseInt( quotesllamaOption.TransitionSpeed );

// ***** Begin Admin *****

/*
 * Admin - Load media library and select a single image to get the thumbnail url.
 *
 * @since 1.0.0
 *
 * @param string selector        - Element to load image url into from selection in library.
 * @param string button_selector - Element of button clicked to open the media library.
 */
function quotes_llama_media_gallery ( selector, button_selector )  {
	let clicked_button;
	clicked_button = false;

	jQuery(
		selector
	).each(
		function (
			i,
			input
		)
		{
			let button;
			button = jQuery( input ).next( button_selector );
			button.click(
				function (
					event
				)
				{
					event.preventDefault();
					let selected_img;
					selected_img;
					clicked_button = jQuery( this );

					// Check for media manager instance.
					if ( wp.media.frames.quotes_llama_frame ) {
						wp.media.frames.quotes_llama_frame.open();
						return;
					}

					// Configuration of the media manager new instance.
					wp.media.frames.quotes_llama_frame = wp.media(
						{
							title: 'Select image',
							multiple: false,
							library: {
								type: 'image'
							},
							button: {
								text: 'Get Thumbnail URL'
							}
						}
					);

					let quotes_llama_media_gallery_set_image;
					quotes_llama_media_gallery_set_image = function() {

						// The image selected and media manager closing.
						let selection;
						selection = wp.media.frames.quotes_llama_frame.state().get( 'selection' );

						// No selection.
						if ( ! selection ) {
							return;
						}

						// Iterate through selected elements.
						selection.each(
							function (	attachment )
							{
								// View the object dump to get an idea of sizes available. Get thumbnail url. var_dump(attachment.attributes);.
								let url;
								url = attachment.attributes.sizes.thumbnail.url;
								clicked_button.prev( selector ).val( url );
							}
						);
					};

					// Closing event for media manger.
					wp.media.frames.quotes_llama_frame.on( 'close', quotes_llama_media_gallery_set_image );

					// Image selection event.
					wp.media.frames.quotes_llama_frame.on( 'select', quotes_llama_media_gallery_set_image );

					// Showing media manager.
					wp.media.frames.quotes_llama_frame.open();
				}
			);
		}
	);
};

jQuery( // Admin - Select image from media library button click.
	document
).on(
	'click',
	'.quotes-llama-media-button',
	function () {
		quotes_llama_media_gallery( '#quotes_llama_imgurl', '.quotes-llama-media-button' );
	}
);

// Admin - Run media gallery once so it is aware on first click in media library.
quotes_llama_media_gallery( '#quotes_llama_imgurl', '.quotes-llama-media-button' );

/*
 * Manage tab - Category, click on category name to populate textbox for renaming or deleting.
 *
 * @since 2.0.5
 */
jQuery( '.ql-manage-cat' ).each(
	function() {
		jQuery( this ).on(
			'click',
			function () {

				// Remove existing highlight.
				jQuery( '.ql-manage-cat' ).css( 'text-decoration', 'none' );

				let cat;
				cat = jQuery( this ).html();

				// Set textbox text.
				jQuery(	'#ql-bulk-category' ).val( cat );

				// Set old category, hidden.
				jQuery( '#ql-bulk-category-old' ).val( cat );

				// Highlight the button we selected.
				jQuery(	this ).css( 'text-decoration', 'underline' );
			}
		)
	}
);

/*
 * Confirm some bulk change to the table data.
 *
 * @since 1.3.4
 *
 * @returns bool - true or false.
 */
function quotes_llama_change_table_confirm() {
	return confirm( "Are you sure you want to take this action?\n This action cannot be undone!" );
}

/*
 * Add a new category checkbox to the category list.
 *
 * @since 2.0.1
 */
jQuery(
	document
).on(
	'click',
	'#ql-new-cat-btn',
	function() {
		let container = jQuery( '.ql-category' );
		let newCat    = jQuery( '#ql-new-category' ).val();

		// Check we have something, add it and clear input field.
		if ( newCat ) {
			let category = '<label><input checked type="checkbox" name="ql_category[]" value="' + newCat + '"> ' + newCat + '</label><br>';
			jQuery( category ).prependTo( container );
			jQuery( '#ql-new-category' ).val( '' );
		}
	}
);

// ***** End Admin *****
// ***** Begin Auto *****

// Auto, Setup our auto divs if any one class is loaded. If has any elements, then it exists.
if ( jQuery( '.quotes-llama-auto' )[0] ) {
	let quotes_llama_auto;
	quotes_llama_auto = jQuery( '.quotes-llama-auto' );
	jQuery.each(
		quotes_llama_auto,
		function (
			i,
			list
		)
		{
			let quotes_llama_elements;
			let quotes_llama_list_class;
			let quotes_llama_auto_category;

			quotes_llama_elements      = jQuery( list ).children( 'div' );
			quotes_llama_list_class    = { data: [] };
			quotes_llama_auto_category = quotes_llama_elements.attr( 'gcategory' );

			jQuery.each(
				quotes_llama_elements,
				function (
					i,
					element
				)
				{
					let quotes_llama_this_class;
					quotes_llama_this_class         = jQuery( element ).attr( 'class' );
					quotes_llama_list_class.data[i] = quotes_llama_this_class;
				}
			);

			quotes_llama_quote( 'auto', true, quotes_llama_list_class.data, 0, quotes_llama_auto_category );
		}
	);
}

/*
 * Auto, Re-enable quote auto-refresh.
 */
jQuery(
	document
).on(
	'click',
	'.quotes-llama-auto-reenable',
	function () {

		// If click id is Restart then enable gallery.
		if ( jQuery( this ).children( 'span' ).attr( 'id' ) == 'quotes-llama-restart' ) {

			let uid;
			let rcategory;

			// Get unique class name for this elements parent to load into.
			uid = jQuery( this ).parent().attr( 'class' );

			// Get category.
			rcategory = jQuery( this ).parent().attr( 'gcategory' );

			// Get quote.
			quotes_llama_quote( 'auto', true, uid, 0, rcategory );

			// Remove timer html.
			jQuery( '.' + uid + '-reenable' ).html( '' );

			// Set id of quote div to loop so on next click it will stop, not change the quote.
			jQuery( '.' + uid + '-quotebox' ).attr( 'id', 'loop' );

			// Set category.
			jQuery( '.' + uid + '-quotebox' ).attr( 'gcategory', rcategory );
		}
	}
);

// ***** End auto *****
// ***** Begin Gallery *****

// Gallery, Setup our gallery divs if any one class is loaded. If has any elements, then it exists.
if ( jQuery( '.quotes-llama-gallery' )[0] ) {
	let quotes_llama_gallery;

	quotes_llama_gallery = jQuery( '.quotes-llama-gallery' );

	jQuery.each(
		quotes_llama_gallery,
		function (
			i,
			list
		)
		{
			let quotes_llama_elements;
			let quotes_llama_list_class;
			let quotes_llama_gallery_category;

			quotes_llama_elements         = jQuery( list ).children( 'div' );
			quotes_llama_list_class       = { data: [] };
			quotes_llama_gallery_category = quotes_llama_elements.attr( 'gcategory' );

			jQuery.each(
				quotes_llama_elements,
				function (
					i,
					element
				)
				{
					let quotes_llama_this_class;
					quotes_llama_this_class         = jQuery( element ).attr( 'class' );
					quotes_llama_list_class.data[i] = quotes_llama_this_class;
				}
			);

			quotes_llama_quote( 'gallery', true, quotes_llama_list_class.data, 0, quotes_llama_gallery_category );
		}
	);
}

/*
 * Gallery, Re-enable quote slideshow.
 */
jQuery(
	document
).on(
	'click',
	'.quotes-llama-gallery-reenable',
	function () {

		// If click id is Restart then enable gallery. Get unique class name for this elements parent to load into.
		if ( jQuery( this ).children( 'span' ).attr( 'id' ) == 'quotes-llama-restart' ) {
			let uid;
			let lcategory;

			uid = jQuery( this ).parent().parent().attr( 'class' );

			lcategory = jQuery( this ).parent().parent().attr( 'gcategory' );

			quotes_llama_quote( 'gallery', true, uid, 0, lcategory );

			// Remove timer html.
			jQuery( '.' + uid + '-reenable' ).html( '' );

			// Set id of quote div to loop so on next click it will stop, not change the quote.
			jQuery( '.' + uid + '-quotebox' ).attr( 'id', 'loop' );

			// Set category.
			jQuery( '.' + uid + '-quotebox' ).attr( 'gcategory', lcategory );
		}
	}
);

/*
 * Gallery, Manually click for next quote.
 *
 * @since 1.0.0
 *
 * @param string uid  - Unique class name.
 * @param string tuid - Quote timer id.
 * @param string suid - Seconds timer id.
 * @param string mode - Gallery or auto.
 * @param string cat  - Category.
 */
function quotes_llama_manualnext( uid, tuid, suid, mode, cat ) {
	let qid;

	// Get ID of quote div for loop or manual.
	qid = jQuery(
		'.' + uid + '-quotebox'
	).attr(
		'id'
	);

	// If loop then this is first click.
	if ( qid == 'loop' ) {

		// Stop quote timer.
		quotes_llama_stoptimer( tuid );

		// Stop time timer.
		quotes_llama_stoptimer( suid );

		// Set re-enable link for starting timer again.
		jQuery(
			'.' + uid + '-countdown'
		).html(
			'<span class="' + uid + '-reenable dashicons dashicons-controls-forward" id="quotes-llama-restart" title="Restart"></span>'
		);

		// Set id of quote div to manual so on next click it will change the quote.
		jQuery( '.' + uid + '-quotebox' ).attr( 'id', 'manual' );
	} else {

		// Loop already disabled so just get next quote.
		quotes_llama_quote( mode, false, uid, 0, cat );
	}
}

/*
 *	Gallery, get random position from size of div.
 *
 * @since 1.0.0
 *
 *	@param string id - Unique class for div to get positioning from.
 *	@param string uid - Unique class parent of id.
 */
function quotes_llama_move( id, uid ) {
	let h;
	let w;
	let nh;
	let nw;

	// Height of div.
	h = jQuery( '.' + uid ).parent().height() * .1;

	// Width of div - min-width of div.
	w = jQuery( '.' + uid ).parent().width() * .1;

	// New height position. Round down to whole number.
	nh = Math.floor( Math.random() * h );

	// New width position.
	nw = Math.floor( Math.random() * w );

	// Move div to new postion.
	jQuery(
		'.' + id
	).animate(
		{
			opacity: '0.9',
			top: nh,
			left: nw
		},
		2000
	);
}

// ***** End Gallery *****
// ***** Begin Widget *****

// Widget, Setup widget instances for auto-refresh if any one class is loaded.
if ( jQuery( '.quotes-llama-widget-gallery' )[0] ) {
	let quotes_llama_this_widget;
	quotes_llama_this_widget = jQuery( '.quotes-llama-widget-gallery' );
	jQuery.each(
		quotes_llama_this_widget,
		function (
			i,
			list
		)
		{
			let quotes_llama_widget_uid;
			quotes_llama_widget_uid = jQuery( list ).attr( 'id' );
			quotes_llama_widget_cat = jQuery( list ).attr( 'category' );
			quotes_llama_widget_quote( quotes_llama_widget_uid, 0, quotes_llama_widget_cat );
		}
	);
}

/*
 *	Widget, Next quote click.
 */
jQuery(
	document
).on(
	'click',
	'.quotes-llama-widget-next',
	function () {
		let author;
		let source;
		let category;
		let img;
		let div_instance;
		let nonce;

		author       = jQuery( this ).attr( 'author' );
		source       = jQuery( this ).attr( 'source' );
		category     = jQuery( this ).attr( 'category' );
		img          = jQuery( this ).attr( 'img' );
		div_instance = jQuery( this ).attr( 'divid' );
		nonce        = jQuery( this ).attr( 'nonce' );

		jQuery.post(
			quotes_llama_ajaxurl,
			{
				action: 'widget_instance',
				author: author,
				source: source,
				category: category,
				img: img,
				div_instance: div_instance,
				nonce: nonce,
			},
			function ( response )
			{
				// Success, fadeout next link.
				jQuery( '.quotes-llama-' + div_instance + '-next' ).fadeOut( 500 );

				// Set response data to div. Strip return code 0 from ajax callback.
				jQuery(
					'#' + div_instance
				).fadeOut(
					quotes_llama_transitionspeed,
					function () {
						jQuery(
							'#' + div_instance
						).html(
							response.substr( response.length - 1, 1 ) === '0' ? response.substr( 0, response.length - 1 ) : response
						);

						// Reformat quote if having character limit.
						if ( quotes_llama_quotelimit > 0 ) {
							quotes_llama_limit_format( '.quotes-llama-' + div_instance + '-next-more', div_instance + '-next' );
							quotes_llama_limit_more( '.quotes-llama-' + div_instance + '-next-morelink' );

							// Hide the extra text as element is created after styles.
							jQuery(
								'.quotes-llama-' + div_instance + '-next-morelink'
							).prev().css(
								'display',
								'none'
							);
						}

						// Make images round rather than rectangle, if settings allow.
						if ( quotes_llama_borderradius ) {
							jQuery( '.quotes-llama-widget-random img' ).css( 'border-radius', '50%' );
						}

						// Make images display above the quote, if settings allow.
						if ( quotes_llama_imageattop ) {
							jQuery( '.quotes-llama-widget-random img' ).css( quotes_llama_css_image_at_top() );
						}

						// Align quote and format icons.
						jQuery( '.quotes-llama-widget-random' ).css( quotes_llama_css_align_quote() );
						jQuery( '.quotes-llama-icons img' ).css( quotes_llama_css_icons_reformat() );

						jQuery( '#' + div_instance ).fadeIn( quotes_llama_transitionspeed );

						// Fadein next link.
						jQuery( '.quotes-llama-' + div_instance + '-next' ).fadeIn( 1000 );
					}
				);
			}
		);
	}
);

/*
 * Widget, auto-refresh random quote.
 *
 * @since 1.0.0
 *
 * @param array uid    - Our elements id.
 * @param integer tuid - Timer for the id.
 * @param string cat   - Category.
 */
function quotes_llama_widget_quote( uid, tuid, cat ) {

	// .ajax post to admin-ajax.php...
	jQuery.post(
		quotes_llama_ajaxurl,
		{
			action: 'quotes_select',
			quotes_llama_random: 1,
			quotes_llama_category: cat
		},
		function ( quotes )
		{
			let quote_data;
			let show_author;
			let show_source;
			let show_image;

			quote_data  = jQuery.parseJSON( quotes );
			show_author = jQuery( '#' + uid ).attr( 'wauthor' );
			show_source = jQuery( '#' + uid ).attr( 'wsource' );
			show_image  = jQuery( '#' + uid ).attr( 'wimage' );

			// Fade out widget.
			jQuery( '#' + uid ).stop( true, true ).fadeOut(
				quotes_llama_transitionspeed,
				function () {
					let rand_quote;
					let author_icon;
					let source_icon;
					let rand_title;
					let rand_first;
					let rand_last;
					let rand_source;
					let rand_img;
					let rand_author_icon;
					let rand_source_icon;
					let rand_category;
					let rand_comma;
					let delayTime;
					let rand_length;

					rand_quote       = quotes_llama_stripslashes( quotes_llama_nl2br( quote_data.quote, false ) );
					author_icon      = '';
					source_icon      = '';
					rand_title       = quotes_llama_stripslashes( quote_data.title_name ) + ' ';
					rand_first       = quotes_llama_stripslashes( quote_data.first_name ) + ' ';
					rand_last        = quotes_llama_stripslashes( quote_data.last_name );
					rand_source      = quotes_llama_stripslashes( quote_data.source );
					rand_img         = quotes_llama_stripslashes( quote_data.img_url );
					rand_author_icon = quotes_llama_stripslashes( quote_data.author_icon );
					rand_source_icon = quotes_llama_stripslashes( quote_data.source_icon );
					rand_category    = quotes_llama_stripslashes( quote_data.category );
					rand_comma       = '';

					// If title is null.
					if ( null === rand_title ) {
						rand_title = '';
					}

					// If displaying icons in author.
					author_icon = quotes_llama_show_icons( rand_author_icon );

					// If showing author, populate fields.
					if ( rand_title && ( false == show_author ) ) {
						rand_title = '';
					}

					if ( rand_first && ( false == show_author ) ) {
						rand_first = '';
					}

					if ( rand_last && ( false == show_author ) ) {
						rand_last = '';
					}

					// If showing both author and source, populate rand_comma.
					if ( ( rand_first || rand_last && ( false != show_author ) ) && ( rand_source && ( false != show_source ) ) ) {

						// If the options are set to put source on a new line populate a line break instead.
						if ( quotes_llama_sourcenewline == 'br' ) {
							rand_comma = '<br>';
						} else {
							rand_comma = ', ';
						}
					}

					// If showing source, populate rand_source.
					if ( rand_source && ( false != show_source ) ) {

						// If displaying icons in source.
						source_icon = quotes_llama_show_icons( rand_source_icon );
						rand_source = '<span class="quotes-llama-widget-source">' + rand_source + '</span>';
					} else {
						rand_source = '';
					}

					// If showing image, populate rand_img.
					if ( rand_img && ( false != show_image  ) ) {
						rand_img = '<img src="' + rand_img +
						'" title="' + rand_title + rand_first + rand_last +
						'">';
					} else {
						rand_img = '';
					}

					// Length of quote with author and source.
					rand_length = rand_quote.length + rand_first.length + rand_last.length + rand_source.length;

					// Delay from length rounded. If less than minimum set in options then set to minimum.
					delayTime = parseInt( rand_length / quotes_llama_galleryinterval );

					if ( delayTime < quotes_llama_galleryminimum ) {
						delayTime = quotes_llama_galleryminimum;
					}

					// Set new timer interval.
					jQuery(
						'.quotes-llama-' + uid + '-countdown'
					).quotes_llama_countdown(
						delayTime,
						'widget',
						uid
					);

					// Stop quote.
					quotes_llama_stoptimer( tuid );

					// Schedule the next quote.
					tuid = setInterval(
						function () {
							quotes_llama_widget_quote( uid, tuid, rand_category );
						},
						delayTime * 1000
					);

					// Render the widget to the display.
					jQuery(
						'#' + uid
					).html(
						'<div class="quotes-llama-widget-quote">' +
						'<span class="quotes-llama-' + uid + '-countdown quotes-llama-widget-countdown"></span> ' +
						rand_img + '<span class="quotes-llama-' + uid + '-more">' + rand_quote + '</span>' +
						'<span class="quotes-llama-widget-author">' +
						author_icon.trim() + rand_title + rand_first + rand_last + rand_comma +
						'<span class="quotes-llama-widget-source">' + source_icon + rand_source + '</span>' +
						'</span></div>'
					);

					// Reformat quote if having character limit.
					if ( quotes_llama_quotelimit > 0 ) {
						quotes_llama_limit_format( '.quotes-llama-' + uid + '-more', uid );
						quotes_llama_limit_more( '.quotes-llama-' + uid + '-morelink' );

						// Hide the extra text as element is created after styles.
						jQuery(
							'.quotes-llama-' + uid + '-morelink'
						).prev().css(
							'display',
							'none'
						);
					}

					// Make images round rather than rectangle, if settings allow.
					if ( quotes_llama_borderradius ) {
						jQuery( '.quotes-llama-widget-gallery img, .quotes-llama-widget-random img' ).css( 'border-radius', '50%' );
					}

					// Make images display above the quote, if settings allow.
					if ( quotes_llama_imageattop ) {
						jQuery( '.quotes-llama-widget-gallery img, .quotes-llama-widget-random img' ).css( quotes_llama_css_image_at_top() );
					}

					// Align quote and format icons.
					jQuery( '.quotes-llama-widget-gallery, .quotes-llama-widget-random' ).css( quotes_llama_css_align_quote() );
					jQuery( '.quotes-llama-icons img' ).css( quotes_llama_css_icons_reformat() );

					// Fade in quotebox.
					jQuery( '#' + uid ).fadeIn( quotes_llama_transitionspeed );
				}
			);
		}
	);
};

// ***** End Widget *****
// ***** Begin Page *****

/*
 *	Page, Load quotes from selected author.
 */
jQuery( document ).on(
	'click',
	'.quotes-llama-page-link',
	function () {
		let title;
		let first;
		let last;
		let nonce;
		let localid;

		title   = jQuery( this ).attr( 'title-name' );
		first   = jQuery( this ).attr( 'first' );
		last    = jQuery( this ).attr( 'last' );
		nonce   = jQuery( this ).attr( 'nonce' );
		localid = jQuery( this ).attr( 'localid' );

		// Update status to loading.
		jQuery(
			'.quotes-llama-page-status'
		).html(
			'<center>Loading ' + title + ' ' + first + ' ' + last + '</center>'
		);

		jQuery.post(
			quotes_llama_ajaxurl,
			{
				action: 'quotes_select',
				author: 1,
				title: title,
				first: first,
				last: last,
				nonce: nonce
			},
			function ( quotes ) {
				jQuery( '.quotes-llama-page-quote' ).fadeOut(
					quotes_llama_transitionspeed,
					function() {
						jQuery( '.quotes-llama-page-quote' ).html( quotes );

						// reformat quote if having character limit.
						if ( quotes_llama_quotelimit > 0 ) {
							quotes_llama_limit_format( '.quotes-llama-page-quote-more', 'page' );
							quotes_llama_limit_more( '.quotes-llama-page-morelink' );
						}

						// Make images round rather than rectangle, if settings allow.
						if ( quotes_llama_borderradius ) {
							jQuery( '.quotes-llama-page-quote img' ).css( 'border-radius', '50%' );
						}

						// Make images display above the quote, if settings allow.
						if ( quotes_llama_imageattop ) {
							jQuery( '.quotes-llama-page-quote img' ).css( quotes_llama_css_image_at_top() );
						}

						// Align quote and format icons.
						jQuery( '.quotes-llama-page-quote' ).css( quotes_llama_css_align_quote() );
						jQuery( '.quotes-llama-icons img' ).css( quotes_llama_css_icons_reformat() );

						jQuery( '.quotes-llama-page-quote' ).fadeIn( quotes_llama_transitionspeed );

						jQuery(
							'.quotes-llama-page-status'
						).html(
							''
						);
					}
				);
			}
		);

		// Update local authors ID for next/prev.
		quotes_llama_cid = localid;

		// Move to top where quotes are loaded.
		jQuery(
			'html,body'
		).animate(
			{
				scrollTop: jQuery(
					'.quotes-llama-page-container'
				).offset().top
			},
			1000
		);
	}
);

/*
 * Page, Click next author button.
 */
jQuery(
	document
).on(
	'click',
	'.quotes-llama-page-next',
	function () {

		// Increment local id for next element.
		++quotes_llama_cid;

		// If at last in list, load last.
		if ( quotes_llama_cid >= quotes_llama_lastid ) {
			quotes_llama_cid = quotes_llama_lastid
		};

		// Click next element.
		jQuery( '.quotes-llama-page-link' ).eq( quotes_llama_cid ).click();
		jQuery( '.quotes-llama-current' ).html( quotes_llama_cid );
	}
);

/*
 * Page, Quote search.
 * Funtion also called after replacing innerHTML on print -
 * so that the search box will still work.
 */
jQuery(
	'div.quotes-llama-page-quotes-form'
).submit(
	function ()
	{
		let quotesearch;
		let quotecolumn;
		let nonce;

		// Get search value.
		quotesearch = jQuery( '.quotes-llama-page-quotesearch' ).val();

		// Get search column.
		quotecolumn = jQuery( '.sc' ).val();

		// Get nonce.
		nonce = jQuery( '.quotes-llama-page-quotesearch' ).attr( 'nonce' );

		if ( quotesearch ) {
			jQuery(
				'.quotes-llama-page-status'
			).html(
				'<div id="quotes-llama-page-status-info" >Searching...<br>' +
					quotesearch +
				'</div>'
			);

			jQuery.post(
				quotes_llama_ajaxurl,
				{
					action: 'quotes_select',
					search_for_quote: 1,
					term: quotesearch,
					sc: quotecolumn,
					nonce: nonce
				},
				function ( quotes )
				{
					jQuery( '.quotes-llama-page-quote' ).fadeOut(
						quotes_llama_transitionspeed,
						function() {
							jQuery( '.quotes-llama-page-quote' ).html( quotes );

							// Reformat quote if having character limit.
							if ( quotes_llama_quotelimit > 0 ) {
								quotes_llama_limit_format( '.quotes-llama-page-quote-more', 'page' );
								quotes_llama_limit_more( '.quotes-llama-page-morelink' );
							}

							// Make images round rather than rectangle, if settings allow.
							if ( quotes_llama_borderradius ) {
								jQuery( '.quotes-llama-page-quote img' ).css( 'border-radius', '50%' );
							}

							// Make images display above the quote, if settings allow.
							if ( quotes_llama_imageattop ) {
								jQuery( '.quotes-llama-page-quotebox img' ).css( quotes_llama_css_image_at_top() );
							}

							// Align quote and format icons.
							jQuery( '.quotes-llama-page-quotebox' ).css( quotes_llama_css_align_quote() );
							jQuery( '.quotes-llama-icons img' ).css( quotes_llama_css_icons_reformat() );

							// Fade in quote.
							jQuery( '.quotes-llama-page-quote' ).fadeIn( quotes_llama_transitionspeed );

							// Clear status.
							jQuery( '.quotes-llama-page-status' ).html( '' );
						}
					);
				}
			);

			// Move to top where quotes are loaded.
			jQuery(
				'html, body'
			).animate(
				{
					scrollTop: jQuery(
						'.quotes-llama-page-sidebarleft'
					).offset().top - 70
				},
				1000
			);
		}
	}
);

/*
 * Page, Prints an authors list of quotes.
 *
 * @since 1.0.0
 */
function quotes_llama_printdiv() {

	// Hide the return and print buttons from div to print.
	jQuery(
		'.quotes-llama-page-author-back'
	).hide();

	// Fix image size - contributed by: Salvatore.
	jQuery(
		'.quotes-llama-page-more > img.emoji'
	).css(
		{
			'width'  : '15px',
			'height' : '15px'
		}
	);

	jQuery(
		'.quotes-llama-page-more > img:not( [class] )'
	).css(
		{
			'width' : '150px',
			'height' : '150px'
		}
	);

	// Copy the div content that we want to print into a var.
	let printContents;
	printContents = document.getElementById( 'quotes-llama-printquote' ).innerHTML;

	// Show the return and print buttons for next author.
	jQuery( '.quotes-llama-page-author-back' ).show();

	// Create print window.
	let mywindow;
	mywindow = window.open(
		'',
		'quotes-llama-window',
		'height=400',
		'width=600'
	);

	// outterHMTL.
	mywindow.document.write( '<html><head><title>quotes</title></head><body>' );

	// innerHTML.
	mywindow.document.write( printContents );
	mywindow.document.write( '</body></html>' );

	// For IE >= 10.
	mywindow.document.close();

	// IE >= 10.
	mywindow.focus();

	// Execute print dialog.
	mywindow.print();

	// Close window.
	mywindow.close();
}

/*
 *	Page, Click print icon.
 */
jQuery( document ).on(
	'click',
	'.quotes-llama-print',
	function () {
		quotes_llama_printdiv();
	}
);

/*
 * Page, Click previous author button.
 */
jQuery(
	document
).on(
	'click',
	'.quotes-llama-page-previous',
	function () {

		// Decrement local id for previous element.
		--quotes_llama_cid;

		// If at start of list load first.
		if ( quotes_llama_cid < 0 ) {
			quotes_llama_cid = 0
		};

		// Click previous element.
		jQuery( '.quotes-llama-page-link' ).eq( quotes_llama_cid ).click();
		jQuery( '.quotes-llama-current' ).html( quotes_llama_cid );
	}
);

// ***** End Page *****
// ***** Begin Gallery-Auto-Widget *****

/*
 * Gallery, Auto and Widget, Display countdown timer.
 *
 * @since 1.0.0
 *
 * @param int duration - Duration of countdown to display.
 * @param string mode - Either gallery or widget.
 * @param string uid - If gallery then unique element, if widget then timer id.
 *
 * Return int - (only if mode is gallery) ID of timer instance.
 */
jQuery.fn.quotes_llama_countdown = function ( duration, mode, uid ) {

	// Must be enabled in options.
	if ( quotes_llama_show_timer ) {
		let gallery_timer;
		let widget_timer;
		let auto_timer;

		gallery_timer = {};
		widget_timer  = {};
		auto_timer    = {};

		if ( mode == 'gallery' ) {

			// Set the timer interval.
			gallery_timer[uid] = setInterval(
				function () {

					// If seconds remain.
					if ( --duration > 0 ) {

						// Update the timer display.
						jQuery( '.' + uid + '-countdown' ).html( '<small>' + duration + 's</small>' );
					} else {

						// Clear the timer display.
						quotes_llama_stoptimer( gallery_timer[uid] );
						jQuery( '.' + uid + '-countdown' ).html( '' );
					}
				},
				1000
			); // Run every second.

			// Return id of timer instance.
			return gallery_timer[uid];
		}

		if ( mode == 'auto' ) {

			// Set the timer interval.
			auto_timer[uid] = setInterval(
				function () {

					// If seconds remain.
					if ( --duration > 0 ) {

						// Update the timer display.
						jQuery( '.' + uid + '-countdown' ).html( '<small>' + duration + 's</small>' );
					} else {

						// Clear the timer display.
						quotes_llama_stoptimer( auto_timer[uid] );
						jQuery( '.' + uid + '-countdown' ).html( '' );
					}
				},
				1000
			); // Run every second.

			// Return id of timer instance.
			return auto_timer[uid];
		}

		if ( mode == 'widget' ) {
			widget_timer[uid] = setInterval(
				function () {
					if ( --duration > 0 ) {
						jQuery(	'.quotes-llama-' + uid + '-countdown' ).html( '<small>' + duration + 's</small>' );
					} else {
						quotes_llama_stoptimer( widget_timer[uid] );
						jQuery(	'.quotes-llama-' + uid + '-countdown' ).html( '' );
					}
				},
				1000
			);
		}
	}
};

/*
 * Gallery and Widget, Stop timer.
 *
 * @since 1.0.0
 *
 * @param string i - Name of timer to stop.
 */
function quotes_llama_stoptimer( i ) {
	clearInterval( i );
	i = false;
}

/*
 *	Widget - Page - Gallery, formats quote to the character limit specified in options.
 *
 * @since 1.0.0
 *
 *	@param string s - full class name for quote to format.
 *	@param string m - Class tag... if s is 'quotes-llama-auto-refresh-more' then m should be 'auto-refresh'.
 */
function quotes_llama_limit_format( s, m ) {
	jQuery( s ).each(
		function () {

			// If we are limiting the quote length in the options. To get a number from a string, prepend it with (+).
			if ( +quotes_llama_quotelimit > 0 ) {
				let content;

				content = jQuery(
					this
				).html().trim();

				// If quote length is greater than limit in options.
				if ( content.length > quotes_llama_quotelimit ) {
					let al;
					let eow;
					let stl;
					let ls;
					let rs;
					let html;
					let iend;

					iend = 0;

					// If first character is < then image, so get that length for exclusion.
					if ( content.charAt( 0 ) == '<' ) {
						iend = content.indexOf( '>' );
					}

					// Everything after the limit.
					al = content.substr( +quotes_llama_quotelimit + iend );

					// Make sure string ends in a word so asset doesn't become ass... get first space.
					eow = al.indexOf( ' ' );

					// If limit is on last word of quote then just leave it at entire quote.
					if ( eow < 0 ) {

						// In .each return true is equivelant to continue in a for loop, return false is exit.
						return;
					}

					// Add length to next space to the limit.
					stl = +quotes_llama_quotelimit + iend + eow;

					// The limited string.
					ls = content.substr( 0, stl );

					// The remaining string.
					rs = content.substr( +quotes_llama_quotelimit + iend + eow );

					// Create html containing the remainder of string.
					html = ls + '<span class="quotes-llama-widget-moreellipses"> ' +
					quotes_llama_ellipses + '</span><span class="quotes-llama-' + m + '-morecontent"><span>' +
					rs + '</span>&nbsp;<a href="" class="quotes-llama-' + m + '-morelink">' + quotes_llama_moretext + '</a></span>';

					// Set quote html.
					jQuery( this ).html( html );
				}
			}
		}
	);
}

/*
 * Widget - Page - Gallery, more or less class links for limited length quotes.
 *
 * @since 1.0.0
 *
 * @param string s - elements morelink class name.
 */
function quotes_llama_limit_more( s ) {

	// Toggle more or less link on limited quotes.
	jQuery(
		s
	).click(
		function () {
			if ( jQuery( this ).hasClass( 'quotes_llama_less' ) ) {
				jQuery( this ).removeClass( 'quotes_llama_less' );
				jQuery( this ).html( quotes_llama_moretext );
			} else {
				jQuery( this ).addClass( 'quotes_llama_less' );
				jQuery( this ).html( quotes_llama_lesstext );
			}

			// Toggle visiblity of extra text and ellipses.
			jQuery( this ).parent().prev().toggle();
			jQuery( this ).prev().toggle();

			return false;
		}
	);
}

/*
 * Gallery and Auto, Render a random quote.
 * .ajax post for localized path to admin-ajax.php.
 *
 * @since 1.0.0
 *
 * @param string mode - If 'gallery' then moves div to new random position.
 * @param bool loop   - If 'loop' will rotate quote.
 * @param string uid  - Unique element name.
 * @param int tuid    - Elements timer id.
 * @param string cat  - Category.
 */
function quotes_llama_quote( mode, loop, uid, tuid, cat ) {
	jQuery.post(
		quotes_llama_ajaxurl,
		{
			action: 'quotes_select',
			quotes_llama_random: 1,
			quotes_llama_category: cat
		},
		function ( quotes )
		{
			let quote_data;
			quote_data = jQuery.parseJSON( quotes );

			// Check that we have quote data.
			if ( quote_data ) {
				let show_author;
				let show_source;
				let show_image;
				let gcategory;

				// Are we going to display the Author.
				show_author = jQuery( '.' + uid ).attr( 'gauthor' );

				// Are we going to display the source.
				show_source = jQuery( '.' + uid ).attr( 'gsource' );

				// Are we going to display the image.
				show_image = jQuery( '.' + uid ).attr( 'gimage' );

				// Category name.
				gcategory = jQuery( '.' + uid ).attr( 'gcategory' );

				jQuery(
					'.' + uid + '-quotebox'
				).stop(
					true,
					true
				).fadeTo(
					quotes_llama_transitionspeed,
					0,
					function()
					{
						let rand_quote;
						let author_icon;
						let source_icon;
						let rand_title;
						let rand_first;
						let rand_last;
						let rand_source;
						let rand_img;
						let rand_author_icon;
						let rand_source_icon;
						let rand_comma;
						let suid;

						// Move to new random location if gallery mode.
						if ( mode == 'gallery' ) {
							quotes_llama_move( uid + '-quotebox', uid );
						}

						rand_quote       = quotes_llama_stripslashes( quotes_llama_nl2br( quote_data.quote, false ) );
						author_icon      = '';
						source_icon      = '';
						rand_title       = quotes_llama_stripslashes( quote_data.title_name ) + ' '; // Add space so it will be trimmed if title not included.
						rand_first       = quotes_llama_stripslashes( quote_data.first_name ) + ' ';
						rand_last        = quotes_llama_stripslashes( quote_data.last_name );
						rand_source      = quotes_llama_stripslashes( quote_data.source );
						rand_img         = quotes_llama_stripslashes( quote_data.img_url );
						rand_author_icon = quotes_llama_stripslashes( quote_data.author_icon );
						rand_source_icon = quotes_llama_stripslashes( quote_data.source_icon );
						rand_comma       = '';
						suid             = 0;

						// If title is null.
						if ( null === rand_title ) {
							rand_title = '';
						}

						// If displaying authors.
						if ( (rand_first || rand_last) && show_author ) {

							// If displaying icons in author.
							author_icon = quotes_llama_show_icons( rand_author_icon );
						}

						// If not showing author, empty strings.
						if ( rand_title && ( false == show_author ) ) {
							rand_title = '';
						}

						if ( rand_first && ( false == show_author ) ) {
							rand_first = '';
						}

						if ( rand_last && ( false == show_author ) ) {
							rand_last = '';
						}

						// If showing both author and source, will we use a comma or break.
						if ( ( rand_first || rand_last && ( false != show_author ) ) && ( rand_source && ( false != show_source ) ) ) {

							// If the options are set to put source on a new line populate a line break instead of comma.
							if ( quotes_llama_sourcenewline == 'br' ) {
								rand_comma = '<br>';
							} else {
								rand_comma = ', ';
							}
						}

						// If showing source, populate rand_source.
						if ( rand_source && ( false != show_source ) ) {

							// If displaying icons in author.
							source_icon = quotes_llama_show_icons( rand_source_icon );
							rand_source = '<span class="quotes-llama-' + mode + '-source">' + rand_source + '</span>';
						} else {
							rand_source = '';
						}

						// If showing image, populate rand_img.
						if ( rand_img && ( false != show_image  ) ) {
							rand_img = '<img src="' + rand_img +
							'" title="' + rand_title + rand_first + rand_last +
							'">';
						} else {
							rand_img = '';
						}

						// If gallery is to loop its quotes.
						if ( loop ) {
							let rand_length;
							let delayTime;

							// Length of quote with author and source.
							rand_length = rand_quote.length + rand_title.length + rand_first.length + rand_last.length + rand_source.length;

							// Delay from length rounded.
							delayTime = parseInt( rand_length / quotes_llama_galleryinterval );

							if ( delayTime < quotes_llama_galleryminimum ) {
								delayTime = quotes_llama_galleryminimum;
							}

							// Create new seconds timer if gallery.
							if ( mode == 'gallery' ) {
								suid = jQuery( '.' + uid + '-countdown' ).quotes_llama_countdown( delayTime, 'gallery', uid );
							}

							// Create new seconds timer if auto.
							if ( mode == 'auto' ) {
								suid = jQuery( '.' + uid + '-countdown' ).quotes_llama_countdown( delayTime, 'auto', uid );
							}

							// Stop quote timer. Set quote timer to fire a new quote in so many seconds from now.
							quotes_llama_stoptimer( tuid );
							tuid = setInterval(
								function () {
									quotes_llama_quote(
										mode,
										true,
										uid,
										tuid,
										gcategory
									);
								},
								delayTime * 1000
							);
						}

						// Mode being gallery or auto... Render to the div.
						jQuery(
							'.' + uid + '-quotebox'
						).html(
							"<div class='quotes-llama-" + mode + "-quote' onClick='quotes_llama_manualnext(\"" + uid + "\", " + tuid + ", " + suid + ", \"" + mode + "\", \"" + gcategory + "\");'>" + rand_img +
							"<div class='quotes-llama-" + mode + "-quote quotes-llama-" + uid + "-more'>" + rand_quote +
							" <span class='quotes-llama-" + mode + "-author'>" +
							author_icon.trim() + rand_title + rand_first + rand_last + rand_comma +
							"<span class='quotes-llama-" + mode + "-source'>" + source_icon + rand_source + "</span>" +
							"</span></div></div>"
						);

						// Reformat quote if having character limit.
						if ( quotes_llama_quotelimit > 0 ) {
							quotes_llama_limit_format(
								'.quotes-llama-' + uid + '-more',
								uid
							);

							quotes_llama_limit_more(
								'.quotes-llama-' + uid + '-morelink'
							);

							// Hide the extra text as element is created after styles.
							jQuery(
								'.quotes-llama-' + uid + '-morelink'
							).prev().css(
								'display',
								'none'
							);
						}

						// Make images round rather than rectangle, if settings allow.
						if ( quotes_llama_borderradius ) {
							jQuery( '.quotes-llama-gallery img, .quotes-llama-auto img' ).css( 'border-radius', '50%' );
						}

						// Make images display above the quote, if settings allow.
						if ( quotes_llama_imageattop ) {
							jQuery( '.quotes-llama-gallery img, .quotes-llama-auto img' ).css( quotes_llama_css_image_at_top() );
						}

						// Align quote and format icons.
						jQuery( '.quotes-llama-gallery, .quotes-llama-auto-quote' ).css( quotes_llama_css_align_quote() );
						jQuery( '.quotes-llama-icons img' ).css( quotes_llama_css_icons_reformat() );

						// Fade in quotebox.
						jQuery( '.' + uid + '-quotebox' ).fadeTo( quotes_llama_transitionspeed, 1 );
					}
				);
			}
		}
	);
};

// ***** End Gallery-Auto-Widget *****
// ***** Begin Formats *****

/*
 * Display icons in quotes.
 *
 * @since 1.3.1
 *
 * @param string icon - Dash-Icon name or image url.
 *
 * @returns string - html span element with icon, or no span at all.
 */
function quotes_llama_show_icons( icon ) {

	// If displaying icons in source.
	if ( quotes_llama_showicons ) {
		let png  = icon.includes( '.png' ); // If .png.
		let jpg  = icon.includes( '.jpg' ); // If .jpg.
		let jpeg = icon.includes( '.jpeg' ); // If .jpeg.
		let gif  = icon.includes( '.gif' ); // If .gif.
		let svg  = icon.includes( '.svg' ); // If .svg.

		if ( png || jpg || jpeg || gif || svg ) {
			icon = '<span class="quotes-llama-icons"><img src="' + quotes_llama_this_url + icon + '"></span>';
		} else {
			icon = '<span class="dashicons dashicons-' + icon + '"></span>';
		}

		return icon;
	}

	return '';
}

/*
 * Image css centered at top.
 *
 * @since 2.1.0
 */
function quotes_llama_css_image_at_top() {
	return {
		'float' : 'none',
		'display' : 'block',
		'margin-right' : 'auto',
		'margin-left' : 'auto',
		'width' : '50%',
		'max-height' : '150px'
	};
}

/*
 * Align quote text.
 *
 * @since 2.1.0
 */
function quotes_llama_css_align_quote() {
	let align;
	align = quotes_llama_alignquote;
	return {'text-align' : align};
}

/*
 * Icons css reformatted.
 *
 * @since 2.1.0
 */
function quotes_llama_css_icons_reformat() {
	return {
		'all' : 'unset',
		'display' : 'inline-block',
		'width' : '16px',
		'height' : '16px',
		'vertical-align' : 'middle',
		'margin-right' : '8px',
		'margin-bottom' : '1px',
		'text-align' : 'right',
		'float' : 'none'
	};
}

/*
 * Equivalent to stripslashes() in php.
 *
 * @since 1.0.0
 *
 * @param string str - string to filter.
 *
 * returns string - string with backslashes stripped.
 */
function quotes_llama_stripslashes ( str ) {
	return (
		str + ''
	).replace(
		/\\(.?)/g,
		function ( s, n1 ) {
			switch ( n1 ) {
				case '\\':
					return '\\';
				case '0':
					return '\u0000';
				case '':
					return '';
				default:
					return n1;
			}
		}
	);
}

/*
 * quotes_llama_var_dump()
 *
 * @since 1.0.0
 *
 * @param string object       - String or object to dump.
 * @param bool returnString - Whether to return or alert.
 *
 * @returns string - string with backslashes stripped.
 */
function quotes_llama_var_dump( object, returnString ) {
	let returning;
	let elem;

	returning = '';

	for ( var element in object ) {
		elem = object[element];

		if ( typeof elem == 'object' ) {
			elem = var_dump( object[element], true );
		}

		returning += element + ': ' + elem + '\n';
	}

	if ( returning == '' ) {
		returning = 'Empty object';
	}

	if ( returnString === true ) {
		return returning;
	}

	alert( returning );
}

/*
 * nl2br Change \n into <br>.
 *
 * @param string str    - String to filter.
 * @param bool is_xhtml - true or false.
 *
 * @returns string - Filtered string.
 */
function quotes_llama_nl2br( str, is_xhtml ) {
	let breakTag;

	if ( typeof str === 'undefined' || str === null ) {
		return '';
	}

	breakTag = ( is_xhtml || typeof is_xhtml === 'undefined' ) ? '<br />' : '<br>';
	return ( str + '' ).replace( /([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2' );
}

// Format any initial quotes if character limit.
if ( quotes_llama_quotelimit > 0 ) {
	quotes_llama_limit_format( '.quotes-llama-widget-more', 'widget' );
	quotes_llama_limit_more( '.quotes-llama-widget-morelink' );
	quotes_llama_limit_format( '.quotes-llama-widget-next-more', 'widget-next' );
	quotes_llama_limit_more( '.quotes-llama-widget-next-morelink' );
	quotes_llama_limit_format( '.quotes-llama-widget-static-more', 'widget-static' );
	quotes_llama_limit_more( '.quotes-llama-widget-static-morelink' );
}

// Page - Sidebar position left or right.
jQuery( '.quotes-llama-page-sidebarleft' ).css( 'float', quotes_llama_sidebarpos );

// Apply background color to index sidebar seperators. (background in options).
jQuery( '.quotes-llama-page-title, .quotes-llama-page-letter' ).css( 'background', quotes_llama_backgroundcolor );

// Apply font color to index sidebar seperators. (foreground in options).
jQuery( '.quotes-llama-page-title, .quotes-llama-page-letter a' ).css( 'color', quotes_llama_foregroundcolor );

// Make any remaining images round rather than rectangle, if settings allow.
if ( quotes_llama_borderradius ) {
	jQuery( '.quotes-llama-widget-random img' ).css( 'border-radius', '50%' );
}

// Make remaining images display above the quote, if settings allow. Otherwise float image left.
if ( quotes_llama_imageattop ) {
	jQuery( '.quotes-llama-widget-random img' ).css( quotes_llama_css_image_at_top() );
}

// Align remaining quotes and format icons.
jQuery( '.quotes-llama-widget-random' ).css( quotes_llama_css_align_quote() );
jQuery( '.quotes-llama-icons img' ).css( quotes_llama_css_icons_reformat() );

// Fadeout any messages.
jQuery( window ).on(
	'load',
	function() {
		if ( jQuery( '.qlmsg' )[0] ) {
			setTimeout(
				function() {
					jQuery( '.qlmsg' ).fadeOut( 1000 );
				},
				5000
			);
		}
	}
);
