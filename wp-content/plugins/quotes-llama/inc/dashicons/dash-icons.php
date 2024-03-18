<?php
/**
 * Quotes Llama Dash-Icons html
 *
 * Description. Display drop-list of dash-icons and image icons.
 *
 * @Link        http://wordpress.org/plugins/quotes-llama/
 * @package     quotes-llama
 * @since       1.3.0
 * License:     Copyheart
 * License URI: http://copyheart.org
 */

// These are the names of the WordPress dash-icons.
$dash_icons_array = array(
	'menu',
	'menu-alt',
	'menu-alt2',
	'menu-alt3',
	'admin-site',
	'admin-site-alt',
	'admin-site-alt2',
	'admin-site-alt3',
	'dashboard',
	'admin-post',
	'admin-media',
	'admin-links',
	'admin-page',
	'admin-comments',
	'admin-appearance',
	'admin-plugins',
	'plugins-checked',
	'admin-users',
	'admin-tools',
	'admin-settings',
	'admin-network',
	'admin-home',
	'admin-generic',
	'admin-collapse',
	'filter',
	'admin-customizer',
	'admin-multisite',
	'welcome-write-blog',
	'welcome-add-page',
	'welcome-view-site',
	'welcome-widgets-menus',
	'welcome-comments',
	'welcome-learn-more',
	'format-aside',
	'format-image',
	'format-gallery',
	'format-video',
	'format-status',
	'format-quote',
	'format-chat',
	'format-audio',
	'camera',
	'camera-alt',
	'images-alt',
	'images-alt2',
	'video-alt',
	'video-alt2',
	'video-alt3',
	'media-archive',
	'media-audio',
	'media-code',
	'media-default',
	'media-document',
	'media-interactive',
	'media-spreadsheet',
	'media-text',
	'media-video',
	'playlist-audio',
	'playlist-video',
	'controls-play',
	'controls-pause',
	'controls-forward',
	'controls-skipforward',
	'controls-back',
	'controls-skipback',
	'controls-repeat',
	'controls-volumeon',
	'controls-volumeoff',
	'image-crop',
	'image-rotate',
	'image-rotate-left',
	'image-rotate-right',
	'image-flip-vertical',
	'image-flip-horizontal',
	'image-filter',
	'undo',
	'redo',
	'database-add',
	'database-export',
	'database-import',
	'database-remove 	',
	'database-view',
	'align-full-width',
	'align-pull-left',
	'align-pull-right',
	'button',
	'cloud-saved',
	'cloud-upload',
	'columns',
	'cover-image',
	'ellipsis',
	'embed-audio',
	'embed-generic',
	'embed-post',
	'embed-video',
	'exit',
	'heading',
	'html',
	'insert-after',
	'insert-before',
	'insert',
	'move',
	'shortcode',
	'info-outline',
	'table-col-after',
	'table-col-before',
	'table-col-delete',
	'table-row-after',
	'table-row-before',
	'table-row-delete',
	'saved',
	'editor-bold',
	'editor-italic',
	'editor-ul',
	'editor-ol',
	'editor-quote',
	'editor-alignleft',
	'editor-aligncenter',
	'editor-alignright',
	'editor-insertmore',
	'editor-spellcheck',
	'editor-distractionfree',
	'editor-contract',
	'editor-kitchensink',
	'editor-underline',
	'editor-justify',
	'editor-textcolor',
	'editor-paste-word',
	'editor-paste-text',
	'editor-removeformatting',
	'editor-video',
	'editor-customchar',
	'editor-outdent',
	'editor-indent',
	'editor-help',
	'editor-strikethrough',
	'editor-unlink',
	'editor-rtl',
	'editor-ltr',
	'editor-break',
	'editor-code',
	'editor-table',
	'editor-paragraph',
	'align-left',
	'align-right',
	'align-center',
	'align-none',
	'lock',
	'unlock',
	'calendar',
	'calendar-alt',
	'visibility',
	'hidden',
	'post-status',
	'edit',
	'trash',
	'sticky',
	'external',
	'arrow-up',
	'arrow-down',
	'arrow-right',
	'arrow-left',
	'arrow-up-alt',
	'arrow-down-alt',
	'arrow-right-alt',
	'arrow-left-alt',
	'arrow-up-alt2',
	'arrow-down-alt2',
	'arrow-right-alt2',
	'arrow-left-alt2',
	'sort',
	'leftright',
	'randomize',
	'list-view',
	'exerpt-view',
	'grid-view',
	'move',
	'share',
	'share-alt',
	'share-alt2',
	'email',
	'email-alt',
	'twitter',
	'rss',
	'facebook',
	'facebook-alt',
	'googleplus',
	'networking',
	'amazon',
	'google',
	'linkedin',
	'pinterest',
	'reddit',
	'podio',
	'spotify',
	'twitch',
	'whatsapp',
	'xing',
	'youtube',
	'hammer',
	'art',
	'migrate',
	'performance',
	'universal-access',
	'universal-access-alt',
	'tickets',
	'nametag',
	'clipboard',
	'heart',
	'megaphone',
	'schedule',
	'tide',
	'rest-api',
	'code-standards',
	'buddicons-activity',
	'buddicons-bbpress-logo',
	'buddicons-buddypress-logo',
	'buddicons-community',
	'buddicons-forums',
	'buddicons-friends',
	'buddicons-groups',
	'buddicons-pm',
	'buddicons-replies',
	'buddicons-topics',
	'buddicons-tracking',
	'wordpress',
	'wordpress-alt',
	'pressthis',
	'update',
	'update-alt',
	'screenoptions',
	'info',
	'cart',
	'feedback',
	'cloud',
	'translation',
	'tag',
	'category',
	'archive',
	'tagcloud',
	'text',
	'bell',
	'yes',
	'yes-alt',
	'no',
	'no-alt',
	'no-alt',
	'plus',
	'plus-alt',
	'plus-alt2',
	'minus',
	'dismiss',
	'marker',
	'star-filled',
	'star-half',
	'star-empty',
	'flag',
	'warning',
	'location',
	'location-alt',
	'vault',
	'shield',
	'shield-alt',
	'sos',
	'search',
	'slides',
	'text-page',
	'analytics',
	'chart-pie',
	'chart-bar',
	'chart-line',
	'chart-area',
	'groups',
	'businessman',
	'businesswoman',
	'businessperson',
	'id',
	'id-alt',
	'products',
	'awards',
	'forms',
	'testimonial',
	'portfolio',
	'book',
	'book-alt',
	'download',
	'upload',
	'backup',
	'lightbulb',
	'microphone',
	'desktop',
	'laptop',
	'tablet',
	'smartphone',
	'phone',
	'index-card',
	'carrot',
	'building',
	'store',
	'album',
	'palmtree',
	'tickets-alt',
	'money',
	'money-alt',
	'smiley',
	'thumbs-up',
	'thumbs-down',
	'layout',
	'paperclip',
	'color-picker',
	'edit-large',
	'edit-page',
	'airplane',
	'bank',
	'beer',
	'calculator',
	'car',
	'coffee',
	'drumstick',
	'food',
	'fullscreen-alt',
	'fullscreen-exit-alt',
	'games',
	'hourglass',
	'open-folder',
	'pdf',
	'pets',
	'printer',
	'privacy',
	'superhero',
	'superhero-alt',
);

// Image extensions.
$image_extensions = array(
	'png',
	'jpg',
	'jpeg',
	'gif',
	'bmp',
	'svg',
);

// Directory where icons are located.
$dir = $this->quotes_llama_icons_dir;

// Get list of image files.
$all_img_png  = glob( $this->quotes_llama_icons_dir . '*.png' );
$all_img_jpg  = glob( $this->quotes_llama_icons_dir . '*.jpg' );
$all_img_jpeg = glob( $this->quotes_llama_icons_dir . '*.jpeg' );
$all_img_gif  = glob( $this->quotes_llama_icons_dir . '*.gif' );
$all_img_bmp  = glob( $this->quotes_llama_icons_dir . '*.bmp' );
$all_img_svg  = glob( $this->quotes_llama_icons_dir . '*.svg' );
$all_img      = array_merge( $all_img_png, $all_img_jpg, $all_img_jpeg, $all_img_gif, $all_img_bmp, $all_img_svg );

// For image icons html.
$icons_img = '';

// For Dash-Icons html.
$icons_dashicons = '';

// Get extenstions of image files.
$ext = strtolower( pathinfo( $icon_set_default, PATHINFO_EXTENSION ) );

// Current image file or dashicon.
if ( in_array( $ext, $image_extensions, true ) ) {
	$icon_span = '<span class="quotes-llama-icons"><img src="' . $this->quotes_llama_icons_url . $icon_set_default . '"></span>';
} else {
	$icon_span = '<span class="dashicons dashicons-' . esc_attr( $icon_set_default ) . '"></span></span>';
}

// Before.
$icons_before = '<fieldset class="quotes-llama-icons-' . esc_attr( $icon_set ) . '">
	<legend>' . esc_html( $icon_set_title ) . '</legend>
	<a href="#quotes-llama-icons-' . esc_attr( $icon_set ) . '-select">
		<span class="arr dashicons dashicons-arrow-down"></span>
		<span class="quotes-llama-icons-' . esc_attr( $icon_set ) . '-sel">' .
		$icon_span .
	'</a>
	<ul id="quotes-llama-icons-' . esc_attr( $icon_set ) . '-select">';

// Create html for all image files.
foreach ( $all_img as $img ) {

	// Get ext to check for svg file.
	$svg = strtolower( pathinfo( $img, PATHINFO_EXTENSION ) );

	// Validate images... svg by extension only.
	if ( $this->validate_image( $img ) || 'svg' === $svg ) {
		$img_path   = $img;
		$img        = str_replace( $dir, '', $img );
		$icons_img .= '<li>
				<label>
					<span class="quotes-llama-icons"><img src="' . $this->quotes_llama_icons_url . $img . '"></span>
					<input type="radio" class="quotes-llama-icons-' . esc_attr( $icon_set ) . '-hidden" name="icon" value="' . $img . '" id="' . $img . '">
				</label>
			</li>';
	}
}

// Create html for Dash-Icons.
foreach ( $dash_icons_array as $di ) {
	$icons_dashicons .= '<li>
			<label>
				<span class="dashicons dashicons-' . $di . '"></span>
				<input type="radio" class="quotes-llama-icons-' . esc_attr( $icon_set ) . '-hidden" name="icon" value="' . $di . '" id="' . $di . '">
			</label>
		</li>';
}

// After.
$icons_after = '</ul></fieldset>';

return $icons_before . $icons_img . $icons_dashicons . $icons_after;

