<?php
/**
 * Quotes Llama Widget Class
 *
 * Description. Widget functions.
 *
 * @Link        http://wordpress.org/plugins/quotes-llama/
 * @package     quotes-llama
 * @since       1.0.0
 * License:     Copyheart
 * License URI: http://copyheart.org
 */

/**
 * Class QuotesLlama_Widget.
 */
class QuotesLlama_Widget extends WP_Widget {

	/**
	 * Widget - constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array( 'description' => esc_html__( 'Display a quote.', 'quotes-llama' ) );
		parent::__construct( 'widgetquotesllama', esc_html__( 'Quotes llama', 'quotes-llama' ), $widget_ops );
	}

	/**
	 * Widget - Render admin page form.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $instance - Array of this particular widgets options.
	 */
	public function form( $instance ) {
		global $wpdb;

		if ( $instance ) {
			$title       = isset( $instance['title'] ) ? $instance['title'] : '';
			$show_author = isset( $instance['show_author'] ) ? $instance['show_author'] : true;
			$show_source = isset( $instance['show_source'] ) ? $instance['show_source'] : true;
			$show_image  = isset( $instance['show_image'] ) ? $instance['show_image'] : true;
			$next_quote  = isset( $instance['next_quote'] ) ? $instance['next_quote'] : true;
			$gallery     = isset( $instance['gallery'] ) ? $instance['gallery'] : false;
			$category    = isset( $instance['category'] ) ? $instance['category'] : '';
			$quote_id    = isset( $instance['quote_id'] ) ? $instance['quote_id'] : '';
		} else {
			$title       = '';
			$show_author = true;
			$show_source = true;
			$show_image  = true;
			$next_quote  = true;
			$gallery     = false;
			$category    = '';
			$quote_id    = '';
		}

		?>
		<p>
			<label for='<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>'>
				<?php esc_html_e( 'Title', 'quotes-llama' ); ?>
			</label>
			<input class='widefat'
				id='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>' 
				type='text'
				value='<?php echo esc_attr( $title ); ?>'
				placeholder='<?php esc_attr_e( '(Optional)', 'quotes-llama' ); ?>'>
		</p>
		<p>

			<input type='checkbox'
				id='<?php echo esc_attr( $this->get_field_id( 'show_author' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'show_author' ) ); ?>'
				value='1'
				<?php checked( '1', $show_author ); ?>>
			<label for='<?php echo esc_attr( $this->get_field_id( 'show_author' ) ); ?>'>
				<?php esc_html_e( 'Show author.', 'quotes-llama' ); ?>
			</label>
		</p>
		<p>
			<input type='checkbox'
				id='<?php echo esc_attr( $this->get_field_id( 'show_source' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'show_source' ) ); ?>'
				value='1'
				<?php checked( '1', $show_source ); ?>>
			<label for='<?php echo esc_attr( $this->get_field_id( 'show_source' ) ); ?>'>
				<?php esc_html_e( 'Show source.', 'quotes-llama' ); ?>
			</label>
		</p>
		<p>
			<input type='checkbox'
				id='<?php echo esc_attr( $this->get_field_id( 'show_image' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'show_image' ) ); ?>'
				value='1'
				<?php checked( '1', $show_image ); ?>>
			<label for='<?php echo esc_attr( $this->get_field_id( 'show_image' ) ); ?>'>
				<?php esc_html_e( 'Show image.', 'quotes-llama' ); ?>
			</label>
		</p>
		<p>
			<input type='checkbox'
				id='<?php echo esc_attr( $this->get_field_id( 'next_quote' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'next_quote' ) ); ?>'
				value='1'
				<?php checked( '1', $next_quote ); ?>>
			<label for='<?php echo esc_attr( $this->get_field_id( 'next_quote' ) ); ?>'>
				<?php esc_html_e( 'Show "next quote" link.', 'quotes-llama' ); ?>
			</label>
		</p>
		<p>
			<input type='checkbox'
				id='<?php echo esc_attr( $this->get_field_id( 'gallery' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'gallery' ) ); ?>'
				value='1'
				<?php checked( '1', $gallery ); ?>>
			<label for='<?php echo esc_attr( $this->get_field_id( 'gallery' ) ); ?>'>
				<?php esc_html_e( 'Auto-Refresh', 'quotes-llama' ); ?>
			</label>
		</p>
		<p>
			<label for='<?php echo esc_attr( $this->get_field_id( 'quote_id' ) ); ?>'>
				<?php esc_html_e( 'Quote ID', 'quotes-llama' ); ?>
			</label>
			<input class='widefat'
				id='<?php echo esc_attr( $this->get_field_id( 'quote_id' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'quote_id' ) ); ?>'
				type='text'
				value='<?php echo esc_attr( $quote_id ); ?>'
				placeholder='<?php esc_html_e( 'To display a static quote, enter the ID here.', 'quotes-llama' ); ?>'>
		</p>
		<p>
			<label for='<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>'>
				<?php esc_html_e( 'Category:', 'quotes-llama' ); ?>
			</label>
			<input class='widefat'
				id='<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>'
				name='<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>'
				type='text'
				value='<?php echo esc_attr( $category ); ?>'
				placeholder='<?php esc_html_e( 'Use a comma to separate categories... (category, category)', 'quotes-llama' ); ?>'>
		</p>
		<?php
	}

	/**
	 * Widget - update options.
	 *
	 * @param array $new_instance - New settings for this instance as input by the user.
	 * @param array $old_instance - Old settings for this instance.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array - The new settings returned and $instance now considered old settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = isset( $new_instance['title'] ) ? apply_filters( 'widget_title', $new_instance['title'] ) : '';
		$instance['show_author'] = isset( $new_instance['show_author'] ) ? $new_instance['show_author'] : false;
		$instance['show_source'] = isset( $new_instance['show_source'] ) ? $new_instance['show_source'] : false;
		$instance['show_image']  = isset( $new_instance['show_image'] ) ? $new_instance['show_image'] : false;
		$instance['next_quote']  = isset( $new_instance['next_quote'] ) ? $new_instance['next_quote'] : false;
		$instance['gallery']     = isset( $new_instance['gallery'] ) ? $new_instance['gallery'] : false;
		$instance['category']    = isset( $new_instance['category'] ) ? $new_instance['category'] : '';
		$instance['quote_id']    = isset( $new_instance['quote_id'] ) ? $new_instance['quote_id'] : '';
		return $instance;
	}

	/**
	 * Widget - Render sidebar. These are the widget options.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Array $args - The name of your widget class.
	 * @param Array $instance - Contains options for this particular widget.
	 */
	public function widget( $args, $instance ) {

		// Widget css.
		wp_enqueue_style( 'quotes-llama-css-widget' );

		$title         = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$show_author   = isset( $instance['show_author'] ) ? $instance['show_author'] : false;
		$show_source   = isset( $instance['show_source'] ) ? $instance['show_source'] : false;
		$show_image    = isset( $instance['show_image'] ) ? $instance['show_image'] : false;
		$next_quote    = isset( $instance['next_quote'] ) ? $instance['next_quote'] : false;
		$gallery       = isset( $instance['gallery'] ) ? $instance['gallery'] : false;
		$category      = isset( $instance['category'] ) ? $instance['category'] : '';
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$quote_id      = isset( $instance['quote_id'] ) ? $instance['quote_id'] : '';
		$widge         = new QuotesLlama();
		$nonce         = wp_create_nonce( 'quotes_llama_nonce' );

		// If next quote or gallery enabled then uses AJAX.
		if ( $next_quote || $gallery ) {
			wp_enqueue_script( 'quotesllamaAjax' );
		}

		// convert id string to array.
		if ( $quote_id ) {
			$quote_id = explode( ',', $quote_id );
			echo wp_kses_post( $before_widget );

			// Begin rendering the widget div.
			echo '<div class="widget-text wp_widget_plugin_box">';

			// Check if title is set.
			if ( $title ) {
				echo wp_kses_post( $before_title . $title . $after_title );
			}

			// Render each quote by id in widget area.
			foreach ( $quote_id as $id ) {
				$div_instance = 'q' . wp_rand( 1000, 100000 );
				echo '<div class="quotes-llama-' . esc_html( $div_instance ) . '-widget-random quotes-llama-widget-random">';
					$widge->widget_instance( $id, $show_author, $show_source, $show_image, $next_quote, $gallery, $category, $div_instance, $nonce );
				echo '</div>';
			}
			echo '</div>'; // End widget div.
			echo wp_kses_post( $after_widget );
			// No id provided so populate a random quote.
		} else {
			$quote_id     = 0;
			$div_instance = 'q' . wp_rand( 1000, 100000 );
			echo wp_kses_post( $before_widget );

			// Begin rendering the widget div.
			echo '<div class="widget-text wp_widget_plugin_box">';

			// Check if title is set.
			if ( $title ) {
				echo wp_kses_post( $before_title . $title . $after_title );
			}
				echo '<div class="quotes-llama-widget-random">';
					$widge->widget_instance( $quote_id, $show_author, $show_source, $show_image, $next_quote, $gallery, $category, $div_instance, $nonce );
				echo '</div>';

			// End widget div.
			echo '</div>';
			echo wp_kses_post( $after_widget );
		}
	}
}
