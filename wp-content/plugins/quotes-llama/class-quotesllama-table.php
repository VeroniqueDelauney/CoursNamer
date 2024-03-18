<?php
/**
 * Quotes Llama Table Class
 *
 * Description. Display quotes table (in the admin panel) for adding, editing, or deleting quotes.
 *
 * @Link        http://wordpress.org/plugins/quotes-llama/
 * @package     quotes-llama
 * @since       1.0.0
 * License:     Copyheart
 * License URI: http://copyheart.org
 */

/**
 * Class QuotesLlama_Table.
 */
class QuotesLlama_Table extends QuotesLlama {

	/**
	 * The current list of items.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @var array
	 * @access private
	 */
	private $items;

	/**
	 * Various information about the current table.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @var array
	 * @access private
	 */
	private $args;

	/**
	 * Various information needed for displaying the pagination.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @var array
	 * @access private
	 */
	private $pagination_args = array();

	/**
	 * The current screen.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @var object
	 * @access private
	 */
	private $screen;

	/**
	 * Cached bulk actions.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @var array
	 * @access private
	 */
	private $actions_b;

	/**
	 * Instance of parent class.
	 *
	 * @since 2.0.8
	 * @var object
	 * @access private
	 */
	private $quotes_llama;

	/**
	 * Constructor.
	 *
	 * The child class should call this constructor from its own constructor to override
	 * the default $args.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access protected
	 *
	 * @param array|string $args {
	 *     Array or string of arguments.
	 *
	 *     @type string $plural   Plural value used for labels and the objects being listed.
	 *                            This affects things such as CSS class-names and nonces used
	 *                            in the list table, e.g. 'posts'. Default empty.
	 *     @type string $singular Singular label for an object being listed, e.g. 'post'.
	 *                            Default empty
	 *     @type string $screen   String containing the hook name used to determine the current
	 *                            screen. If left null, the current screen will be automatically set.
	 *                            Default null.
	 * }
	 */
	protected function __construct( $args = array() ) {
		$args         = wp_parse_args(
			$args,
			array(
				'plural'   => '',
				'singular' => '',
				'screen'   => null,
			)
		);
		$this->screen = convert_to_screen( $args['screen'] );
		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

		if ( ! $args['plural'] ) {
			$args['plural'] = $this->screen->base;
		}

		$args['plural']     = sanitize_key( $args['plural'] );
		$args['singular']   = sanitize_key( $args['singular'] );
		$this->_args        = $args;
		$this->quotes_llama = new QuotesLlama();
	}

	/**
	 * Allowed html lists.
	 *
	 * @since 2.0.9
	 * @access protected
	 *
	 * @param string $s - Which set of allowed tags.
	 *
	 * @return array - Allowed html entities.
	 */
	protected function quotes_llama_allowed_html( $s ) {
		return $this->quotes_llama->quotes_llama_allowed_html( $s );
	}

	/**
	 * Returns html with image or dashicon span.
	 *
	 * @since 2.0.9
	 * @access public
	 *
	 * @param string $s The dash-icon name or image.
	 *
	 * @return string - html span with icon image or dashicon span.
	 */
	public function show_icon( $s ) {
		return $this->quotes_llama->show_icon( $s );
	}

	/**
	 * Returns a plugin option value.
	 *
	 * @since 2.0.9
	 * @access protected
	 *
	 * @param string $s - The option to check on.
	 *
	 * @return mixed - False if no option or option string if found.
	 */
	protected function check_plugin_option( $s ) {
		return $this->quotes_llama->check_plugin_option( $s );
	}

	/**
	 * Returns plaintext URIs to HTMl.
	 *
	 * @since 2.0.9
	 * @access protected
	 *
	 * @param string $s Content to convert URIs.
	 *
	 * @return string Converted URIs.
	 */
	protected function quotes_llama_clickable( $s ) {
		return $this->quotes_llama->quotes_llama_clickable( $s );
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backwards-compatibility.
	 */
	private function bulk_actions( $which = '' ) {
		if ( is_null( $this->actions_b ) ) {
			$this->actions_b = $this->get_bulk_actions();
			$no_new_actions  = $this->actions_b;
			/**
			 * Filter the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, $this->screen->id, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->actions_b = apply_filters( "bulk_actions_{$this->screen->id}", $this->actions_b );
			$this->actions_b = array_intersect_assoc( $this->actions_b, $no_new_actions );
			$two             = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->actions_b ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html__( 'Select bulk action' ) . '</label>';
		echo '<select name="action' . esc_attr( $two ) . '" id="bulk-action-selector-' . esc_attr( $which ) . '">';
		echo '<option value="-1" selected="selected">' . esc_html__( 'Bulk Actions' ) . '</option>';

		foreach ( $this->actions_b as $name => $title ) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
			echo '\t<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
		}

		echo '</select>';
		submit_button( __( 'Apply' ), 'action', false, false, array( 'id' => 'doaction$two' ) );
		echo '';
	}

	/**
	 * ID column.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array $item - Table row.
	 *
	 * @return string - Formatted quote id.
	 */
	private function column_id( $item ) {
		return '<span style="color:silver">' . wp_kses_post( $item['quote_id'] ) . '</span>';
	}

	/**
	 * Quote column.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array  $item   - url parameters.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - Row actions and URL Parameters.
	 */
	private function column_thequote( $item, $nonce = '' ) {
		if ( isset( $nonce ) && wp_verify_nonce( $nonce, 'quotes_llama_column_nonce' ) ) {
			$return_page = '';

			if ( isset( $_GET['paged'] ) ) {
				$rpaged       = sanitize_text_field( wp_unslash( $_GET['paged'] ) ); // Get the paged variable.
				$return_page .= '&paged=' . $rpaged;
			}

			if ( isset( $_GET['s'] ) ) { // Get the search term variable.
				$rsearch      = sanitize_text_field( wp_unslash( $_GET['s'] ) );
				$return_page .= '&s=' . $rsearch;
			}

			if ( isset( $_GET['sc'] ) ) { // Get the search column variable.
				$scol         = sanitize_text_field( wp_unslash( $_GET['sc'] ) );
				$return_page .= '&sc=' . $scol;
			}

			if ( isset( $_GET['order'] ) ) { // Get the order variable.
				$sorder       = sanitize_text_field( wp_unslash( $_GET['order'] ) );
				$return_page .= '&order=' . $sorder;
			}

			if ( isset( $_GET['orderby'] ) ) { // Get the sort column variable.
				$sorderby     = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
				$return_page .= '&orderby=' . $sorderby;
			}

			$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			$edit    = wp_nonce_url( '?page=' . $page . $return_page . '&action=e&quote_id=' . $item['quote_id'], 'delete_edit' );
			$delete  = wp_nonce_url( '?page=' . $page . $return_page . '&action=quotes_llama_delete_single&quote_id=' . $item['quote_id'], 'delete_edit' );
			$actions = array(
				'edit'   => '<a href="' . $edit . '">Edit</a>',
				'delete' => '<a href="' . $delete . '">Delete</a>',
			);
			return $item['quote'] . $this->row_actions( $actions );
		}
	}

	/**
	 * Checkbox column.
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column.
	 * is given special treatment when columns are processed. It ALWAYS needs to.
	 * be defined and have it's own method.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array  $item   -  Table row.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - Checkbox html.
	 */
	private function column_cb( $item, $nonce = '' ) {
		return sprintf( '<input type="checkbox" name="bulkcheck[]" value="%s">', $item['quote_id'] );
	}

	/**
	 * Set a default column if not one.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array $item - A singular item (one full row's worth of data).
	 * @param array $column_name - The name/slug of the column to be processed.
	 *
	 * @return string - Default Text or HTML to be placed inside the column <td>.
	 */
	private function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'thequote':
			case 'titlename':
			case 'firstname':
			case 'lastname':
			case 'source':
			case 'category':
			case 'img':
				return $item[ $column_name ];
			default:
				return 'id';
		}
	}

	/**
	 * Title name column.
	 *
	 * @since 1.2.0
	 * @access private
	 *
	 * @param array  $item   - Table row.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - First name.
	 */
	private function column_titlename( $item, $nonce = '' ) {
		$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
		if ( $item['author_icon'] ) {
			$author_icon = $this->show_icon( $item['author_icon'] );

		} else {
			$author_icon = $this->check_plugin_option( 'author_icon' );
			$author_icon = $this->show_icon( $author_icon );
		}

		return trim( wp_kses( $author_icon . ' ' . $item['title_name'], $allowed_html ) );
	}

	/**
	 * First name column.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array  $item   - Table row.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - First name.
	 */
	private function column_firstname( $item, $nonce = '' ) {
		return esc_html( $item['first_name'] );
	}

	/**
	 * Last name column.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array  $item   - Table row.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - Last name.
	 */
	private function column_lastname( $item, $nonce = '' ) {
		return esc_html( $item['last_name'] );
	}

	/**
	 * Source column.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array  $item   - Table row.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - Quote source.
	 */
	private function column_source( $item, $nonce = '' ) {
		$source_icon  = $this->show_icon( $item['source_icon'] );
		$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
		if ( $item['source_icon'] ) {
			$source_icon = $this->show_icon( $item['source_icon'] );
		} else {
			$source_icon = $this->check_plugin_option( 'source_icon' );
			$source_icon = $this->show_icon( $source_icon );
		}

		return trim( wp_kses( $source_icon . ' ' . $item['source'], $allowed_html ) );
	}

	/**
	 * Category column.
	 *
	 * @since 1.4.0
	 * @access private
	 *
	 * @param array  $item   - Table row.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - Last name.
	 */
	private function column_category( $item, $nonce = '' ) {
		return esc_html( $item['category'] );
	}

	/**
	 * Image url column.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array  $item   -  Table row.
	 * @param string $nonce  - Nonce.
	 *
	 * @return string - linked image html.
	 */
	private function column_img( $item, $nonce = '' ) {
		$allowed_html = $this->quotes_llama_allowed_html( 'image' );

		if ( $item['img_url'] ) {
			return '<a href="' . esc_url( $item['img_url'] ) . '"' .
			' target="_blank" title="' . wp_kses( $this->column_firstname( $item ), $allowed_html ) .
			' ' . esc_attr( $this->column_lastname( $item ) ) . '">
			<img src="' . esc_url_raw( $item['img_url'] ) .
			'" alt="' . wp_kses( $this->column_firstname( $item ), $allowed_html ) .
			' ' . esc_attr( $this->column_lastname( $item ) ) . '"' .
			'></a>';
		} else {
			return esc_html__( 'No Image', 'quotes-llama' );
		}
	}

	/**
	 * Generate the table rows.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 */
	private function display_rows() {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}

	/**
	 * Generate the <tbody> part of the table.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 */
	private function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}

	/**
	 * Display the table.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access protected
	 */
	protected function display() {
		$nonce    = wp_create_nonce( 'quotes_llama_col_headers_nonce' );
		$singular = $this->_args['singular'];
		$this->display_tablenav( 'top' );
		?>
		<table class='quotes-llama-admin-table <?php echo wp_kses_post( implode( ' ', $this->get_table_classes() ) ); ?>'>
			<thead>
				<tr>
					<?php $this->print_column_headers( true, $nonce ); ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<?php $this->print_column_headers( false, $nonce ); ?>
				</tr>
			</tfoot>
			<tbody id='the-list'
				<?php
				if ( $singular ) {
					echo ' data-wp-lists="list:' . esc_html( $singular ) . '"';
				}
				?>
				>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param int $which - 1, 0, -1.
	 */
	private function display_tablenav( $which ) {
		?>
		<div class='tablenav <?php echo esc_attr( $which ); ?>'>

			<div class='alignleft actions'>
				<?php $this->bulk_actions(); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class='clear' />
		</div>
		<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param int $which - 1, 0, -1.
	 */
	private function extra_tablenav( $which ) {}

	/**
	 * Bulk actions dropdown.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return array - Drop-down list bulk actions.
	 */
	private function get_bulk_actions() {
		$actions = array(
			'delete' => esc_html__( 'Delete', 'quotes-llama' ),
		);
		return $actions;
	}

	/**
	 * Table columns and titles.
	 * Column => title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array - Titled table columns.
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'id'        => 'ID',
			'thequote'  => 'Quote',
			'titlename' => 'Title',
			'firstname' => 'First Name',
			'lastname'  => 'Last Name',
			'source'    => 'Source',
			'category'  => 'Category',
			'img'       => 'Image',
		);
		return $columns;
	}

	/**
	 * Return number of visible columns.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @return int
	 */
	private function get_column_count() {
		list ( $columns, $hidden ) = $this->get_column_info();
		$hidden                    = array_intersect( array_keys( $columns ), array_filter( $hidden ) );
		return count( $columns ) - count( $hidden );
	}

	/**
	 * Get a list of all, hidden and sortable columns, with filter applied.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @return array
	 */
	private function get_column_info() {
		if ( isset( $this->_column_headers ) ) {
			return $this->_column_headers;
		}

		$columns = get_column_headers( $this->screen );
		$hidden  = get_hidden_columns( $this->screen );

		$sortable_columns = $this->get_sortable_columns();
		/**
		 * Filter the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, $this->screen->id, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @since 3.5.0
		 *
		 * @param array $sortable_columns An array of sortable columns.
		 */
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );
		$sortable  = array();

		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$data = (array) $data;

			if ( ! isset( $data[1] ) ) {
				$data[1] = false;
			}

			$sortable[ $id ] = $data;
		}

		$this->_column_headers = array( $columns, $hidden, $sortable );
		return $this->_column_headers;
	}

	/**
	 * Get the current page number
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param string $nonce - Nonce.
	 *
	 * @return int
	 */
	private function get_pagenum( $nonce ) {
		if ( isset( $nonce ) && wp_verify_nonce( $nonce, 'pn' ) ) {
			$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;

			if ( isset( $this->pagination_args['total_pages'] ) && $pagenum > $this->pagination_args['total_pages'] ) {
				$pagenum = $this->pagination_args['total_pages'];
			}

			return max( 1, $pagenum );
		}
	}

	/**
	 * Access the pagination args
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param string $key - Page pagination args.
	 *
	 * @return array
	 */
	private function get_pagination_arg( $key ) {
		if ( 'page' === $key ) {
			$pagenum_nonce = wp_create_nonce( 'pn' );
			return $this->get_pagenum( $pagenum_nonce );
		}

		if ( isset( $this->pagination_args[ $key ] ) ) {
			return $this->pagination_args[ $key ];
		}
	}

	/**
	 * Sortable Columns
	 * One or more columns to be sortable.
	 * This merely defines which columns should be sortable and makes them clickable - it does not handle the actual sorting.
	 * Column => data field to sort.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return array - Sortable columns.
	 */
	private function get_sortable_columns() {
		$sortable_columns = array(
			'id'        => array( 'quote_id', false ), // True is already sorted.
			'thequote'  => array( 'quote', false ),
			'titlename' => array( 'title_name', false ),
			'firstname' => array( 'first_name', false ),
			'lastname'  => array( 'last_name', false ),
			'source'    => array( 'source', false ),
			'category'  => array( 'category', false ),
			'img'       => array( 'img_url', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Get a list of CSS classes for the <table> tag.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @return array
	 */
	private function get_table_classes() {
		return array( 'widefat', 'fixed', $this->_args['plural'] );
	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @return bool
	 */
	private function has_items() {
		return ! empty( $this->items );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 */
	private function no_items() {
		esc_html_e( 'No items found.' );
	}

	/**
	 * Display the pagination.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param int $which - 1, 0, -1.
	 */
	private function pagination( $which ) {
		if ( empty( $this->pagination_args ) ) {
			return;
		}

		$total_items     = $this->pagination_args['total_items'];
		$total_pages     = $this->pagination_args['total_pages'];
		$infinite_scroll = false;

		if ( isset( $this->pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->pagination_args['infinite_scroll'];
		}

		/*
		 * Translators: %d: Number of result items.
		 */
		$output        = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span> ';
		$pagenum_nonce = wp_create_nonce( 'pn' );
		$current       = $this->get_pagenum( $pagenum_nonce );

		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$server_host = esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) );

			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$server_uri    = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
				$current_url   = set_url_scheme( $server_host . $server_uri );
				$current_url   = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first', 'bd', 'd', 'action', 'action2' ), $current_url );
				$page_links    = array();
				$disable_last  = '';
				$disable_first = '';

				if ( 1 === $current ) {
					$disable_first = ' disabled';
				}

				if ( $current === $total_pages ) {
					$disable_last = ' disabled';
				}

				$page_links[] = sprintf(
					'<a class="%s" title="%s" href="%s">%s</a>',
					'first-page' . $disable_first,
					esc_attr__( 'Go to the first page' ),
					esc_url( remove_query_arg( 'paged', $current_url ) ),
					'<span class="dashicons dashicons-controls-skipback"></span>'
				);

				$page_links[] = sprintf(
					'<a class="%s" title="%s" href="%s">%s</a>',
					'prev-page' . $disable_first,
					esc_attr__( 'Go to the previous page' ),
					esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
					'<span class="dashicons dashicons-controls-back"></span>'
				);

				if ( 'bottom' === $which ) {
					$html_current_page = $current;
				} else {
					$html_current_page = sprintf(
						'%s<input class="current-page" id="current-page-selector" title="%s" type="text" name="paged" value="%s" size="%d" />',
						'<label for="current-page-selector" class="screen-reader-text">' . esc_html__( ' Select Page ' ) . '</label>',
						esc_attr__( 'Current page' ),
						$current,
						strlen( $total_pages )
					);
				}

				$html_total_pages = sprintf( '<span class="total-pages">%s</span>', number_format_i18n( $total_pages ) );

				/*
				 * Translators: Page numbers, current of total.
				 */
				$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging', 'quotes-llama' ), $html_current_page, $html_total_pages ) . '</span>';
				$page_links[] = sprintf(
					"<a class='%s' title='%s' href='%s'>%s</a>",
					'next-page' . $disable_last,
					esc_attr__( 'Go to the next page' ),
					esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
					'<span class="dashicons dashicons-controls-forward"></span>'
				);
				$page_links[] = sprintf(
					"<a class='%s' title='%s' href='%s'>%s</a>",
					'last-page' . $disable_last,
					esc_attr__( 'Go to the last page' ),
					esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
					'<span class="dashicons dashicons-controls-skipforward"></span>'
				);

				$pagination_links_class = 'pagination-links';

				if ( ! empty( $infinite_scroll ) ) {
					$pagination_links_class = ' hide-if-js';
				}

				$output .= '<span class="$pagination_links_class">' . join( "\n", $page_links ) . '</span>';

				if ( $total_pages ) {
					$page_class = $total_pages < 2 ? ' one-page' : '';
				} else {
					$page_class = ' no-pages';
				}

				$paginate     = "<div class='tablenav-pages{$page_class}'>$output</div>";
				$allowed_html = $this->quotes_llama_allowed_html( 'paginate' );
				echo wp_kses( $paginate, $allowed_html );
			}
		}
	}

	/**
	 * Prepare items.
	 * Displays, sorts the admin table.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $search       - Search term from user input.
	 * @param string $colsearch    - Search column.
	 * @param int    $per_page     - How many records per page.
	 * @param string $uasort_nonce - Nonce.
	 */
	protected function prepare_items( $search = '', $colsearch = '', $per_page = 20, $uasort_nonce = '' ) {
		if ( isset( $uasort_nonce ) && wp_verify_nonce( $uasort_nonce, 'quotes_llama_uasort_nonce' ) ) {
			global $wpdb;
			$this->_column_headers = $this->get_column_info(); // Get our columns.

			// Check that database table exists.
			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "quotes_llama'" ) === $wpdb->prefix . 'quotes_llama' ) { // phpcs:ignore
				/**
				 * Nested function to usort table.
				 *
				 * @since 1.0.0
				 * @access public
				 *
				 * @param array $a .
				 * @param array $b .
				 *
				 * @return int - 1, 0, -1
				 */
				function prepare_usort( $a, $b ) {
					$quotes_usort_options = get_option( 'quotes-llama-settings' ); // Nonce already passed to get to here so ignore phpcs nonce error.
					$ds                   = isset( $quotes_usort_options['default_sort'] ) ? $quotes_usort_options['default_sort'] : 'quote_id';
					$do                   = isset( $quotes_usort_options['default_order'] ) ? $quotes_usort_options['default_order'] : 'dsc';
					$byorder              = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore
					$bordery              = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : ''; // phpcs:ignore
					$orderby              = ( ! empty( $byorder ) ) ? $byorder : $ds; // If no sort, default to options.
					$order                = ( ! empty( $bordery ) ) ? $bordery : $do; // If no order, default to options.
					$result               = strnatcmp( $a[ $orderby ], $b[ $orderby ] ); // Determine sort order. Returns < 0 if $a is less than $b; > 0 if $a is greater than $b, and 0 if they are equal... http://php.net/manual/en/function.strnatcmp.php .
					return ( 'asc' === $order ) ? $result : -$result; // Send final sort direction to usort.
				}

				if ( $search ) {
					$qls = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // Admin search term.
					$las = isset( $_GET['as'] ) ? sanitize_text_field( wp_unslash( $_GET['as'] ) ) : ''; // Admin search nonce.

					if ( isset( $qls ) ) {
						if ( wp_verify_nonce( $las, 'llama_admin_search_nonce' ) ) {
							$search = sanitize_text_field( wp_unslash( $qls ) );
						}
					}

					$search        = trim( $search ); // Trim search term.
					$search_column = ! empty( $colsearch ) ? $colsearch : 'quote'; // Search column, default to quote.
					$like          = '%' . $wpdb->esc_like( $search ) . '%';
					$data          = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT * FROM ' .
							$wpdb->prefix .
							'quotes_llama WHERE %1s LIKE %s', // phpcs:ignore
							$search_column,
							$like
						),
						ARRAY_A
					);
				} else {
					$data = $wpdb->get_results( // phpcs:ignore
						'SELECT * FROM ' . // Query all our quote data.
						$wpdb->prefix . 'quotes_llama',
						ARRAY_A
					);
				}

				uasort( $data, 'prepare_usort' ); // Sort data.
				$pagenum_nonce          = wp_create_nonce( 'pn' );
				$current_page           = $this->get_pagenum( $pagenum_nonce ); // What page user is on.
				$total_items            = count( $data ); // How many items in data array.
				$user                   = get_current_user_id(); // get the current user ID.
				$screen                 = get_current_screen(); // get the current admin screen.
				$screen_option_per_page = $screen->get_option( 'per_page', 'option' ); // retrieve the 'per_page' option.
				$per_page               = get_user_meta( $user, $screen_option_per_page, true ); // retrieve the value of the option stored for the current user.

				if ( empty( $per_page ) || $per_page < 1 ) {
					$per_page = $screen->get_option( 'per_page', 'default' ); // get the default value if none is set.
				}

				$data        = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page ); // Paginate data to fit our page count. wp_list_table's search_box() doesn't paginate.
				$this->items = $data; // Set sorted data to items array.
				$this->set_pagination_args(
					array( // Register our pagination options.
						'total_items' => $total_items,
						'per_page'    => $per_page,
						'total_pages' => ceil( $total_items / $per_page ),
					)
				);
			} else {
				echo '<p class="quotes-llama-table-error">Database table cannot be found! Reactivate or reinstall the plugin to create the table.</p>';
			}
		}
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param bool   $with_id Whether to set the id attribute or not.
	 * @param string $nonce - Nonce.
	 */
	private function print_column_headers( $with_id, $nonce ) {
		if ( isset( $nonce ) && wp_verify_nonce( $nonce, 'quotes_llama_col_headers_nonce' ) ) {
			list( $columns, $hidden, $sortable ) = $this->get_column_info();

			if ( isset( $_SERVER['HTTP_HOST'] ) ) {
				$server_host = esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) );

				if ( isset( $_SERVER['REQUEST_URI'] ) ) {
					$server_uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
					$current_url = set_url_scheme( $server_host . $server_uri );
					$current_url = remove_query_arg( 'paged', $current_url );
					$orderby     = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
					$order       = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';

					if ( $orderby ) {
						$current_orderby = $orderby;
					} else {
						$current_orderby = '';
					}

					if ( 'desc' === $order ) {
						$current_order = 'desc';
					} else {
						$current_order = 'asc';
					}

					if ( ! empty( $columns['cb'] ) ) {
						static $cb_counter = 1;
						$columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . esc_html__( 'Select All' ) . '</label>'
							. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
						$cb_counter++;
					}

					foreach ( $columns as $column_key => $column_display_name ) {
						$class = array( 'manage-column', "column-$column_key", true );
						$style = '';

						if ( in_array( $column_key, $hidden, true ) ) {
							$style = 'display:none;';
						}

						$style = ' style="' . $style . '"';

						if ( 'cb' === $column_key ) {
							$class[] = 'check-column';
						} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ), true ) ) {
							$class[] = 'num';
						}

						if ( isset( $sortable[ $column_key ] ) ) {
							list( $orderby, $desc_first ) = $sortable[ $column_key ];

							if ( $current_orderby === $orderby ) {
								$order   = 'asc' === $current_order ? 'desc' : 'asc';
								$class[] = 'sorted';
								$class[] = $current_order;
							} else {
								$order   = $desc_first ? 'desc' : 'asc';
								$class[] = 'sortable';
								$class[] = $desc_first ? 'asc' : 'desc';
							}
							$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . esc_html( $column_display_name ) . '</span><span class="sorting-indicator"></span></a>';
						}

						$id = $with_id ? 'id="$column_key"' : '';

						if ( ! empty( $class ) ) {
							$class = 'class="' . join( ' ', $class ) . '"';
						}

						$allowed_html = $this->quotes_llama_allowed_html( 'print' );
						echo '<th scope="col" ' . wp_kses_post( $id ) . ' ' . wp_kses_post( $class ) . ' ' . wp_kses_post( $style ) . '>' . wp_kses( $column_display_name . '</th>', $allowed_html );
					}
				}
			}
		}
	}

	/**
	 * Generate row actions div
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param array $actions The list of actions.
	 * @param bool  $always_visible Whether the actions should be always visible.
	 *
	 * @return string - HTML div.
	 */
	private function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i            = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

		foreach ( $actions as $action => $link ) {
			++$i;
			( $i === $action_count ) ? $sep = '' : $sep = ' | ';
			$out                           .= '<span class="' . $action . '">' . $link . $sep . '</span>';
		}

		$out .= '</div>';
		return $out;
	}

	/**
	 * Display the search box.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access protected
	 *
	 * @param string $text     - The search button text.
	 * @param string $input_id - The search input id.
	 * @param string $nonce    - Nonce.
	 */
	protected function search_box( $text, $input_id, $nonce ) {
		if ( isset( $nonce ) && wp_verify_nonce( $nonce, 'quotes_llama_uasort_nonce' ) ) {
			if ( empty( $_GET['s'] ) && ! $this->has_items() ) {
				return;
			}

			// Input field id.
			$input_id = $input_id . '-search-input';

			// Sort orderby.
			$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';

			// Sort order.
			$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';

			// Mime type.
			$post_mime_type = isset( $_REQUEST['post_mime_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['post_mime_type'] ) ) : '';

			// Seemingly unused.
			$detached = isset( $_REQUEST['detached'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['detached'] ) ) : '';

			// Search column.
			$sc = isset( $_GET['sc'] ) ? sanitize_text_field( wp_unslash( $_GET['sc'] ) ) : '';

			if ( ! empty( $orderby ) ) {
				echo '<input type="hidden" name="orderby" value="' . esc_attr( $orderby ) . '" />';
			}

			if ( ! empty( $order ) ) {
				echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
			}

			if ( ! empty( $post_mime_type ) ) {
				echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $post_mime_type ) . '" />';
			}

			if ( ! empty( $detached ) ) {
				echo '<input type="hidden" name="detached" value="' . esc_attr( $detached ) . '" />';
			}
			?>

			<p class='search-box'>
				<label class='screen-reader-text' for='<?php echo esc_attr( $input_id ); ?>'><?php echo esc_html( $text ); ?>:</label>
				<input type='search' id='<?php echo esc_attr( $input_id ); ?>' name='s' value='<?php the_search_query(); ?>' />
					<select name='sc'>
						<option value='quote'
							<?php
							if ( 'quote' === $sc ) {
								echo ' selected'; }
							?>
						>
						<?php esc_html_e( 'Quote', 'quotes-llama' ); ?>
						</option>
						<option value='title_name'
							<?php
							if ( 'title_name' === $sc ) {
								echo ' selected';
							};
							?>
						>
						<?php esc_html_e( 'Title', 'quotes-llama' ); ?>
						</option>
						<option value='first_name'
							<?php
							if ( 'first_name' === $sc ) {
								echo ' selected';
							};
							?>
						>
							<?php esc_html_e( 'First Name', 'quotes-llama' ); ?>
						</option>
						<option value='last_name'
							<?php
							if ( 'last_name' === $sc ) {
								echo ' selected';
							};
							?>
						>
							<?php esc_html_e( 'Last Name', 'quotes-llama' ); ?>
						</option>
						<option value='source'
							<?php
							if ( 'source' === $sc ) {
								echo ' selected';
							};
							?>
						>
							<?php esc_html_e( 'Source', 'quotes-llama' ); ?>
						</option>						<option value='category'
							<?php
							if ( 'category' === $sc ) {
								echo ' selected';
							};
							?>
						>
							<?php esc_html_e( 'Category', 'quotes-llama' ); ?>
						</option>
					</select>
				<?php submit_button( $text, 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
			</p>
			<?php
		}
	}

	/**
	 * An internal method that sets all the necessary pagination arguments.
	 *
	 * @access private.
	 *
	 * @param array $args An associative array with information about the pagination.
	 */
	private function set_pagination_args( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'total_items' => 0,
				'total_pages' => 0,
				'per_page'    => 0,
			)
		);

		if ( ! $args['total_pages'] && $args['per_page'] > 0 ) {
			$args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );
		}

		$pagenum_nonce = wp_create_nonce( 'pn' );

		if ( ! headers_sent() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && $args['total_pages'] > 0 && $this->get_pagenum( $pagenum_nonce ) > $args['total_pages'] ) {

			// Redirect if page number is invalid and headers are not already sent.
			wp_safe_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
			exit;
		}

		$this->pagination_args = $args;
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param object $item The current item.
	 */
	private function single_row( $item ) {
		static $row_class = ' class="alternate"';
		echo wp_kses_post( '<tr' . $row_class . '>' );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Generates the columns for a single row of the table.
	 *
	 * @since 3.1.0 - WP_List_Table
	 * @access private
	 *
	 * @param object $item The current item.
	 */
	private function single_row_columns( $item ) {
		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = 'class="$column_name column-$column_name"';
			$style = '';

			if ( in_array( $column_name, $hidden, true ) ) {
				$style = ' style="display:none;"';
			}

			$attributes       = $class . ' ' . $style;
			$allowed_html_col = $this->quotes_llama_allowed_html( 'column' );
			if ( 'cb' === $column_name ) {
				$colcb = $this->column_cb( $item );
				echo '<th scope="row" class="check-column">';
				echo wp_kses( $colcb, $allowed_html_col );
				echo '</th>';
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				$quotes_llama_column_nonce = wp_create_nonce( 'quotes_llama_column_nonce' );
				echo wp_kses_post( '<td $attributes>' );
				$cuf = call_user_func( array( $this, 'column_' . $column_name ), $item, $quotes_llama_column_nonce );
				echo wp_kses( $this->quotes_llama_clickable( nl2br( $cuf ) ), $allowed_html_col );
				echo '</td>';
			} else {
				echo '<td ' . wp_kses_post( $attributes ) . '>';
				echo wp_kses_post( $this->column_default( $item, $column_name ) );
				echo '</td>';
			}
		}
	}
} // End class QuotesLlama_Table.
