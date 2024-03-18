/**
 * Quotes Llama dash-icons JS
 *
 * Description. Javascript functions for dash-icons drop-list
 *
 * @Link        http://wordpress.org/plugins/quotes-llama/
 * @package     quotes-llama
 * @since       1.3.0
 * License:     Copyheart
 * License URI: http://copyheart.org
 */

/**
 * Click event on either drop list.
 * https://api.jquery.com/toggleclass/
 */
jQuery(
	document
).on(
	'click',
	'.quotes-llama-icons-source a, .quotes-llama-icons-author a',
	function(event)
	{
		this_span = jQuery( this ); // Get clicked element data.
		href      = this_span.attr( 'href' ); // Get elements href value which is the id of the list.
		jQuery( href ).slideToggle( 'slow' ); // Hide or show.
		arrow = this_span.find( '.arr' ); // Get drop-list arrow.
		arrow.toggleClass( 'dashicons-arrow-down' ).toggleClass( 'dashicons-arrow-up' ); // Toggle the arrow.
	}
);

/**
 * Change event on source drop list.
 * https://www.w3schools.com/jsref/jsref_includes.asp
 */
jQuery(
	document
).on(
	'change',
	'#quotes-llama-icons-source-select input',
	function()
	{
		let source_selection = jQuery( '.quotes-llama-icons-source-sel' ); // Get selection element data.
		let source_icon      = this.value; // Selection made.
		let png              = source_icon.includes( '.png' ); // If .png.
		let jpg              = source_icon.includes( '.jpg' ); // If .jpg.
		let jpeg             = source_icon.includes( '.jpeg' ); // If .jpeg.
		let gif              = source_icon.includes( '.gif' ); // If .gif.
		let bmp              = source_icon.includes( '.bmp' ); // If .bmp.
		let svg              = source_icon.includes( '.svg' ); // If .svg.

		if ( png || jpg || jpeg || gif || bmp || svg ) {
			source_span = '<span class="quotes-llama-icons"><img src="' + quotes_llama_this_url + source_icon + '"></span>'; // Result to populate element with.
		} else {
			source_span = '<span class="dashicons dashicons-' + source_icon + '"></span>'; // Result to populate element with.
		}

		source_selection.html( source_span ); // Set element data.
		jQuery( '#source_icon' ).val( source_icon ); // Set options input textbox with selection made.
		jQuery( '.quotes-llama-icons-source a' ).click(); // Click arrow to toggle and close drop-list box.
	}
);

/**
 * Change event on author drop list.
 */
jQuery(
	document
).on(
	'change',
	'#quotes-llama-icons-author-select input',
	function()
	{
		let author_selection = jQuery( '.quotes-llama-icons-author-sel' ); // Get selection element data.
		let author_icon      = this.value; // Selection made.
		let png              = author_icon.includes( '.png' ); // If .png.
		let jpg              = author_icon.includes( '.jpg' ); // If .jpg.
		let jpeg             = author_icon.includes( '.jpeg' ); // If .jpeg.
		let gif              = author_icon.includes( '.gif' ); // If .gif.
		let bmp              = author_icon.includes( '.bmp' ); // If .bmp.
		let svg              = author_icon.includes( '.svg' ); // If .svg.

		if ( png || jpg || jpeg || gif || bmp || svg ) {
			author_span = '<span class="quotes-llama-icons"><img src="' + quotes_llama_this_url + author_icon + '"></span>'; // Result to populate element with.
		} else {
			author_span = '<span class="dashicons dashicons-' + author_icon + '"></span>'; // Result to populate element with.
		}

		author_selection.html( author_span );
		jQuery( '#author_icon' ).val( author_icon );
		jQuery( '.quotes-llama-icons-author a' ).click();
	}
);
