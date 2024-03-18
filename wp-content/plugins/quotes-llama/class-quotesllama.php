<?php
/**
 * Plugin Name: Quotes llama
 * Plugin URI:  http://wordpress.org/plugins/quotes-llama/
 * Version:     2.2.0
 * Description: Share the thoughts that mean the most... display your quotes in blocks, widgets, pages, templates, galleries or posts.
 * Author:      oooorgle
 * Author URI:  https://oooorgle.com/plugins/wp/quotes-llama/
 * Text Domain: quotes-llama
 * Domain Path: /lang
 *
 * @package     quotes-llama
 * License:     Copyheart
 * License URI: http://copyheart.org
 */

defined( 'ABSPATH' ) || die( 'Cannot access pages directly.' ); // Deny access except through WordPress.

/**
 * Begin QuotesLlama class.
 */
class QuotesLlama {

	const QUOTES_LLAMA_PLUGIN_VERSION = '2.2.0';
	const QUOTES_LLAMA_DB_VERSION     = '2.0.0';

	/**
	 * Currently selected admin tab.
	 *
	 * @since 1.0.0
	 * @var string
	 * @access private
	 */
	private $active_tab;

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array
	 * @access private
	 */
	private $quotes_llama_plugin_options;

	/**
	 * Plugin url path.
	 *
	 * @since 1.0.0
	 * @var string
	 * @access private
	 */
	private $quotes_llama_plugin_url;

	/**
	 * Plugin name.
	 *
	 * @since 1.0.0
	 * @var string
	 * @access private
	 */
	private $quotes_llama_plugin_name;

	/**
	 * Icons url.
	 *
	 * @since 1.3.3
	 * @var string
	 * @access private
	 */
	private $quotes_llama_icons_url;

	/**
	 * Icons dir.
	 *
	 * @since 1.3.3
	 * @var string
	 * @access private
	 */
	private $quotes_llama_icons_dir;

	/**
	 * The message for success or failure of actions.
	 *
	 * @since 1.0.0
	 * @var string
	 * @access public
	 */
	public $msg;

	/**
	 * QuotesLlama class construct.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		global $wpdb;
		$upload_dir                        = wp_upload_dir();
		$this->msg                         = '';
		$this->quotes_llama_plugin_options = get_option( 'quotes-llama-settings' );
		$this->quotes_llama_plugin_url     = plugin_dir_url( __FILE__ );
		$this->quotes_llama_plugin_name    = plugin_basename( __FILE__ );
		$this->quotes_llama_icons_url      = $upload_dir['baseurl'] . '/quotes-llama/';
		$this->quotes_llama_icons_dir      = $upload_dir['basedir'] . '/quotes-llama/';

		// Process early $_POSTs.
		if ( ! did_action( 'init', array( $this, 'plugin_posts' ) ) ) {
			add_action( 'init', array( $this, 'plugin_posts' ) );
		}

		// Set shortcode for starting plugin.
		if ( ! did_action( 'init', array( $this, 'plugin_shortcodes' ) ) ) {
			add_action( 'init', array( $this, 'plugin_shortcodes' ) );
		}

		// Authenticated Ajax access to the function.
		add_action( 'wp_ajax_widget_instance', array( $this, 'widget_instance' ) );

		// Non-authenticated Ajax access to the function.
		add_action( 'wp_ajax_nopriv_widget_instance', array( $this, 'widget_instance' ) );

		// Authenticated.
		add_action( 'wp_ajax_quotes_select', array( $this, 'quotes_select' ) );

		// Non-authenticated.
		add_action( 'wp_ajax_nopriv_quotes_select', array( $this, 'quotes_select' ) );

		// Authenticated.
		add_action( 'wp_ajax_template_page_author', array( $this, 'template_page_author' ) );

		// Non-authenticated.
		add_action( 'wp_ajax_nopriv_template_page_author', array( $this, 'template_page_author' ) );

		// Define i18n language folder in function plugin_text_domain().
		add_action( 'plugin_text_domain', array( $this, 'plugin_text_domain' ) );

		// Not logged into administrative interface, front-end scripts and styles.
		if ( ! is_admin() ) {
			if ( ! did_action( 'wp_enqueue_scripts', array( $this, 'plugin_scripts' ) ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'plugin_scripts' ) );
			}

			if ( ! did_action( 'init', array( $this, 'register_front_end_scripts' ) ) ) {
				add_action( 'init', array( $this, 'register_front_end_scripts' ), 1 );
			}

			// Init widget class.
			if ( ! did_action( 'widgets_init', array( $this, 'register_widgets' ) ) ) {
				add_action( 'widgets_init', array( $this, 'register_widgets' ) );
			}
		} else {

			// Administrative (Dashboard) interface view - back-end.
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

			// Create plugin database table.
			register_activation_hook( __FILE__, array( $this, 'plugin_db_setup' ) );

			// Create plugin options.
			register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

			// Remove plugin options and settings when deactivating.
			register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

			// Create admin manage links, css, and page fields.
			add_filter( 'plugin_action_links_' . $this->quotes_llama_plugin_name, array( $this, 'plugin_manage_link' ) );

			// Set screen options.
			add_filter( 'set-screen-option', array( $this, 'quotes_llama_set_option' ), 10, 3 );

			// Path to plugin settings.
			if ( ! did_action( 'admin_menu', array( $this, 'plugin_settings_link' ) ) ) {
				add_action( 'admin_menu', array( $this, 'plugin_settings_link' ) );
			}

			// Admin page fields.
			if ( ! did_action( 'admin_init', array( $this, 'admin_page_fields' ) ) ) {
				add_action( 'admin_init', array( $this, 'admin_page_fields' ) );
			}

			// Create widget.
			// Path to plugin settings.
			if ( ! did_action( 'widgets_init', array( $this, 'register_widgets' ) ) ) {
				add_action( 'widgets_init', array( $this, 'register_widgets' ) );
			}
		}
	}

	/**
	 * Plugin version.
	 *
	 * @since 1.3.4
	 * @var string
	 *
	 * @access public
	 */
	public function quotes_llama_plugin_version() {
		return self::QUOTES_LLAMA_PLUGIN_VERSION;
	}

	/**
	 * Plugin database version.
	 *
	 * @since 1.3.4
	 * @var string
	 *
	 * @access public
	 */
	public function quotes_llama_db_version() {
		return self::QUOTES_LLAMA_DB_VERSION;
	}

	/**
	 * Dashboard scripts, localizations and styles.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_scripts() {

		// Javascript functions.
		wp_enqueue_script( 'quotesllamaAjax', $this->quotes_llama_plugin_url . 'quotes-llama.js', array( 'jquery' ), '1.3.4', true );

		// Javascript variable arrays quotesllamaOption and quotesllamaAjax, Back-end.
		wp_localize_script(
			'quotesllamaAjax',
			'quotesllamaOption',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'ThisURL' => $this->quotes_llama_icons_url,
				'ThisDIR' => $this->quotes_llama_icons_dir,
			)
		);

		// Javascript functions for dash-icons selection drop-list.
		wp_enqueue_script( 'quotesllamaDashIcons', $this->quotes_llama_plugin_url . 'inc/dashicons/dash-icons.js', array( 'jquery' ), $this->quotes_llama_plugin_version(), true );

		// Necessary to use all media JS APIs.
		wp_enqueue_media();

		// Admin css.
		wp_enqueue_style( 'quotes-llama-css-admin', $this->quotes_llama_plugin_url . 'css/quotes-llama-admin.css', array(), $this->quotes_llama_plugin_version() );

		// Dash-icons css.
		wp_enqueue_style( 'quotesllamaDashIcons', $this->quotes_llama_plugin_url . 'inc/dashicons/dash-icons.css', array(), $this->quotes_llama_plugin_version() );
	}

	/**
	 * Front-end styles, settings and ocalizations that are loaded in all short-codes and widgets.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_scripts() {

		// Javascript variable arrays quotesllamaOption and quotesllamaAjax, Front-end.
		wp_localize_script(
			'quotesllamaAjax',
			'quotesllamaOption',
			array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'BackgroundColor'  => isset( $this->quotes_llama_plugin_options['background_color'] ) ? $this->quotes_llama_plugin_options['background_color'] : '#444',
				'ForegroundColor'  => isset( $this->quotes_llama_plugin_options['foreground_color'] ) ? $this->quotes_llama_plugin_options['foreground_color'] : 'silver',
				'GalleryInterval'  => isset( $this->quotes_llama_plugin_options['gallery_timer_interval'] ) ? $this->quotes_llama_plugin_options['gallery_timer_interval'] : 12,
				'TransitionSpeed'  => isset( $this->quotes_llama_plugin_options['transition_speed'] ) ? $this->quotes_llama_plugin_options['transition_speed'] : 1000,
				'GalleryMinimum'   => isset( $this->quotes_llama_plugin_options['gallery_timer_minimum'] ) ? $this->quotes_llama_plugin_options['gallery_timer_minimum'] : 10,
				'GalleryShowTimer' => isset( $this->quotes_llama_plugin_options['gallery_timer_show'] ) ? $this->quotes_llama_plugin_options['gallery_timer_show'] : false,
				'Sidebarpos'       => isset( $this->quotes_llama_plugin_options['sidebar'] ) ? $this->quotes_llama_plugin_options['sidebar'] : 'left',
				'Limit'            => isset( $this->quotes_llama_plugin_options['character_limit'] ) ? $this->quotes_llama_plugin_options['character_limit'] : 0,
				'Ellipses'         => isset( $this->quotes_llama_plugin_options['ellipses_text'] ) ? $this->quotes_llama_plugin_options['ellipses_text'] : '...',
				'SourceNewLine'    => isset( $this->quotes_llama_plugin_options['source_newline'] ) ? $this->quotes_llama_plugin_options['source_newline'] : 'br',
				'MoreText'         => isset( $this->quotes_llama_plugin_options['read_more_text'] ) ? $this->quotes_llama_plugin_options['read_more_text'] : '&raquo;',
				'ShowIcons'        => isset( $this->quotes_llama_plugin_options['show_icons'] ) ? $this->quotes_llama_plugin_options['show_icons'] : false,
				'AuthorIcon'       => isset( $this->quotes_llama_plugin_options['author_icon'] ) ? $this->quotes_llama_plugin_options['author_icon'] : 'edit',
				'SourceIcon'       => isset( $this->quotes_llama_plugin_options['source_icon'] ) ? $this->quotes_llama_plugin_options['source_icon'] : 'migrate',
				'LessText'         => isset( $this->quotes_llama_plugin_options['read_less_text'] ) ? $this->quotes_llama_plugin_options['read_less_text'] : '&laquo;',
				'BorderRadius'     => isset( $this->quotes_llama_plugin_options['border_radius'] ) ? $this->quotes_llama_plugin_options['border_radius'] : false,
				'ImageAtTop'       => isset( $this->quotes_llama_plugin_options['image_at_top'] ) ? $this->quotes_llama_plugin_options['image_at_top'] : false,
				'AlignQuote'       => isset( $this->quotes_llama_plugin_options['align_quote'] ) ? $this->quotes_llama_plugin_options['align_quote'] : 'left',
				'ThisURL'          => $this->quotes_llama_icons_url,
			)
		);

		// Main css Front-end.
		wp_enqueue_style( 'quotes-llama-css-style', $this->quotes_llama_plugin_url . 'css/quotes-llama.css', array(), $this->quotes_llama_plugin_version() );

		// Enable admin dashicons set for Front-end.
		wp_enqueue_style( 'dashicons-style', get_stylesheet_uri(), array( 'dashicons' ), $this->quotes_llama_plugin_version() );
	}

	/**
	 * Front-end scripts and styles.
	 * Localized variables (quotesllamaAjax).
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_front_end_scripts() {

		// Javascript functions.
		wp_register_script( 'quotesllamaAjax', $this->quotes_llama_plugin_url . 'quotes-llama.js', array( 'jquery' ), '1.3.4', true );

		// Widget css.
		wp_register_style( 'quotes-llama-css-widget', $this->quotes_llama_plugin_url . 'css/quotes-llama-widget.css', array(), $this->quotes_llama_plugin_version() );

		// Gallery css.
		wp_register_style( 'quotes-llama-css-gallery', $this->quotes_llama_plugin_url . 'css/quotes-llama-gallery.css', array(), $this->quotes_llama_plugin_version() );

		// Page css.
		wp_register_style( 'quotes-llama-css-page', $this->quotes_llama_plugin_url . 'css/quotes-llama-page.css', array(), $this->quotes_llama_plugin_version() );

		// Auto css.
		wp_register_style( 'quotes-llama-css-auto', $this->quotes_llama_plugin_url . 'css/quotes-llama-auto.css', array(), $this->quotes_llama_plugin_version() );

		// Count css.
		wp_register_style( 'quotes-llama-css-count', $this->quotes_llama_plugin_url . 'css/quotes-llama-count.css', array(), $this->quotes_llama_plugin_version() );

		// All css.
		wp_register_style( 'quotes-llama-css-all', $this->quotes_llama_plugin_url . 'css/quotes-llama-all.css', array(), $this->quotes_llama_plugin_version() );

		// Center image above quote css.
		wp_register_style( 'quotes-llama-css-image-center', $this->quotes_llama_plugin_url . 'css/quotes-llama-image-center.css', array(), $this->quotes_llama_plugin_version() );

		// Make image round css.
		wp_register_style( 'quotes-llama-css-image-round', $this->quotes_llama_plugin_url . 'css/quotes-llama-image-round.css', array(), $this->quotes_llama_plugin_version() );

		// Align quote to center css.
		wp_register_style( 'quotes-llama-css-quote-center', $this->quotes_llama_plugin_url . 'css/quotes-llama-quote-center.css', array(), $this->quotes_llama_plugin_version() );

		// Align quote to left css.
		wp_register_style( 'quotes-llama-css-quote-left', $this->quotes_llama_plugin_url . 'css/quotes-llama-quote-left.css', array(), $this->quotes_llama_plugin_version() );

		// Align quote to right css.
		wp_register_style( 'quotes-llama-css-quote-right', $this->quotes_llama_plugin_url . 'css/quotes-llama-quote-right.css', array(), $this->quotes_llama_plugin_version() );

		// Format icon images css.
		wp_register_style( 'quotes-llama-css-icons-format', $this->quotes_llama_plugin_url . 'css/quotes-llama-icons-format.css', array(), $this->quotes_llama_plugin_version() );
	}

	/**
	 * Process initial $_POST $_GET requests.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_posts() {

		// $_GET for searching admin list table, clean the URL.
		if ( isset( $_GET['s'] ) ) {
			$llama_admin_search = isset( $_GET['as'] ) ? sanitize_text_field( wp_unslash( $_GET['as'] ) ) : '';
			if ( wp_verify_nonce( $llama_admin_search, 'llama_admin_search_nonce' ) ) {
				if ( isset( $_SERVER['HTTP_HOST'] ) ) {
					$server_host = esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) );
					if ( isset( $_SERVER['REQUEST_URI'] ) ) {
						$server_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
						if ( ! empty( $server_host && $server_uri ) ) {
							$current_url = $server_host . $server_uri;
							$new_url     = remove_query_arg(
								array( '_wp_http_referer', 'as', 'paged', 'action', 'action2' ),
								stripslashes( $current_url )
							);

							if ( wp_safe_redirect( $new_url ) ) {
								exit;
							}
						}
					}
				}
			}
		}

		// $_POST Category bulk actions to Delete/Rename a category.
		if ( isset( $_POST['ql-delete-cat-btn'] ) || isset( $_POST['ql-rename-cat-btn'] ) ) {
			$nonce    = isset( $_POST['quotes_llama_admin_tabs'] ) ? sanitize_text_field( wp_unslash( $_POST['quotes_llama_admin_tabs'] ) ) : '';
			$category = isset( $_POST['ql-bulk-category'] ) ? sanitize_text_field( wp_unslash( $_POST['ql-bulk-category'] ) ) : '';
			$cat_old  = isset( $_POST['ql-bulk-category-old'] ) ? sanitize_text_field( wp_unslash( $_POST['ql-bulk-category-old'] ) ) : '';

			if ( wp_verify_nonce( $nonce, 'quotes_llama_admin_tabs' ) ) {
				if ( isset( $_POST['ql-delete-cat-btn'] ) ) {
					if ( ! empty( $category ) ) {
						$this->msg = $this->category_bulk_actions( $category, 'delete' );
					} else {
						$this->msg = $this->message( esc_html__( 'Transaction failed: Select an existing category for deletion.' ), 'nay' );
					}
				}

				if ( isset( $_POST['ql-rename-cat-btn'] ) ) {
					if ( ! empty( $cat_old ) ) {
						$this->msg = $this->category_bulk_actions( $category, 'rename', $cat_old );
					} else {
						$this->msg = $this->message( esc_html__( 'Transaction failed: Select an existing category to rename.' ), 'nay' );
					}
				}
			}
		}

		// $_GET message to confirm bulk delete.
		if ( isset( $_GET['bd'] ) ) {
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			// Lower select box is action2.
			if ( wp_verify_nonce( $nonce, 'llama_admin_delete_bulk' ) ) {
				$bd = sanitize_text_field( wp_unslash( $_GET['bd'] ) );

				// Success.
				if ( 1 <= $bd ) {
					$this->msg = $this->message( esc_html__( 'Transaction completed: ' ) . $bd . ' ' . esc_html__( 'Quotes deleted.' ), 'yay' );
				}

				// Failed.
				if ( 'n' === $bd ) {
					$this->msg = $this->message( esc_html__( 'Transaction failed: Unable to delete quotes.' ), 'nay' );
				}

				// Empty checks.
				if ( 'u' === $bd ) {
					$this->msg = $this->message( esc_html__( 'Transaction failed: No quotes selected.' ), 'nay' );
				}

				// Empty params.
				if ( 'p' === $bd ) {
					$this->msg = $this->message( esc_html__( 'Transaction failed: Select a bulk action from the drop-down.' ), 'nay' );
				}
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_GET message to confirm single delete.
		if ( isset( $_GET['d'] ) ) {
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			if ( wp_verify_nonce( $nonce, 'delete_edit' ) ) {
				$d = sanitize_text_field( wp_unslash( $_GET['d'] ) );

				// Success.
				if ( 'y' === $d ) {
					$this->msg = $this->message( esc_html__( 'Transaction completed: Quote deleted.' ), 'yay' );
				}

				// Failed.
				if ( 'n' === $d ) {
					$this->msg = $this->message( esc_html__( 'Transaction failed: Unable to delete quote.' ), 'nay' );
				}
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_GET clicked tab or set initial tab.
		if ( isset( $_GET['tab'] ) ) {
			if ( isset( $_GET['_wpnonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
				if ( wp_verify_nonce( $nonce, 'quotes_llama_admin_tabs' ) ) {
					$tab              = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
					$this->active_tab = $tab ? $tab : 'quotes';
				}
			}
		} else {
			$this->active_tab = 'quotes';
		}

		// $_POST to Export quotes to csv.
		if ( isset( $_POST['quotes_llama_export_csv'] ) ) {
			if ( check_admin_referer( 'quotes_llama_export_nonce', 'quotes_llama_export_nonce' ) ) {
				if ( ! class_exists( 'QuotesLlama_Backup' ) ) {
					require_once 'class-quotesllama-backup.php';
					$export_csv = new QuotesLlama_Backup( $this->check_plugin_option( 'export_delimiter' ) );
					$export_csv->create_csv();
				} else {
					$this->msg = $this->message( esc_html__( 'Failed to include Backup class file.' ), 'nay' );
				}
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_POST to Export quotes to json.
		if ( isset( $_POST['quotes_llama_export_json'] ) ) {
			if ( check_admin_referer( 'quotes_llama_export_nonce', 'quotes_llama_export_nonce' ) ) {
				if ( ! class_exists( 'QuotesLlama_Backup' ) ) {
					require_once 'class-quotesllama-backup.php';
					$export_json = new QuotesLlama_Backup( $this->check_plugin_option( 'export_delimiter' ) );
					$export_json->create_json();
				} else {
					$this->msg = $this->message( esc_html__( 'Failed to include Backup class file.' ), 'nay' );
				}
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_POST to Import quotes.
		if ( isset( $_POST['quote_llama_import'] ) ) {
			if ( check_admin_referer( 'quote_llama_import_nonce', 'quote_llama_import_nonce' ) ) {
				if ( ! class_exists( 'QuotesLlama_Backup' ) ) {
					require_once 'class-quotesllama-backup.php';
					$import    = new QuotesLlama_Backup( $this->check_plugin_option( 'export_delimiter' ) );
					$this->msg = $this->message( 'Transaction completed: ' . $import->generate_import(), 'yay' );
				} else {
					$this->msg = $this->message( esc_html__( 'Failed to include Backup class file.' ), 'nay' );
				}
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_POST to remove quotes_llama table from database.
		if ( isset( $_POST['quotes_llama_remove_table'] ) ) {
			if ( check_admin_referer( 'quotes_llama_remove_table_nonce', 'quotes_llama_remove_table_nonce' ) ) {
				$sql = $this->plugin_db_remove();
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_POST to add quote.
		if ( isset( $_POST['quotes_llama_add_quote'] ) ) {
			if ( check_admin_referer( 'quotes_llama_form_nonce', 'quotes_llama_form_nonce' ) ) {
				$allowed_html = $this->quotes_llama_allowed_html( 'style' );

				// Filter the quote and source for allowed html tags.
				if ( isset( $_POST['quote'] ) ) {

					$quote = wp_check_invalid_utf8( wp_unslash( $_POST['quote'] ) ); // phpcs:ignore
					$quote = wp_kses( trim( $quote ), $allowed_html );
				} else {
					$quote = '';
				}

				if ( isset( $_POST['source'] ) ) {
					$source = wp_check_invalid_utf8( wp_unslash( $_POST['source'] ) ); // phpcs:ignore
					$source = wp_kses( trim( $source ), $allowed_html );
				} else {
					$source = '';
				}

				$title_name  = isset( $_POST['title_name'] ) ? sanitize_text_field( wp_unslash( $_POST['title_name'] ) ) : '';
				$first_name  = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
				$last_name   = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
				$img_url     = isset( $_POST['img_url'] ) ? sanitize_text_field( wp_unslash( $_POST['img_url'] ) ) : '';
				$author_icon = isset( $_POST['author_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['author_icon'] ) ) : $this->check_plugin_option( 'author_icon' );
				$source_icon = isset( $_POST['source_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['source_icon'] ) ) : $this->check_plugin_option( 'source_icon' );
				$category    = isset( $_POST['ql_category'] ) ? map_deep( wp_unslash( $_POST['ql_category'] ), 'sanitize_text_field' ) : array();
				$category    = implode( ', ', $category );
				$this->msg   = $this->quotes_insert( $quote, $title_name, $first_name, $last_name, $source, $img_url, $author_icon, $source_icon, $category );
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_POST to update quote.
		if ( isset( $_POST['quotes_llama_save_quote'] ) ) {
			if ( check_admin_referer( 'quotes_llama_form_nonce', 'quotes_llama_form_nonce' ) ) {
				$allowed_html = $this->quotes_llama_allowed_html( 'style' );

				// Filter the quote and source for allowed html tags.
				if ( isset( $_POST['quote'] ) ) {
					$quote = wp_check_invalid_utf8( wp_unslash( $_POST['quote'] ) ); // phpcs:ignore
					$quote = wp_kses( trim( $quote ), $allowed_html );
				} else {
					$quote = '';
				}

				if ( isset( $_POST['source'] ) ) {
					$source = wp_check_invalid_utf8( wp_unslash( $_POST['source'] ) ); // phpcs:ignore
					$source = wp_kses( trim( $source ), $allowed_html );
				} else {
					$source = '';
				}

				$quote_id    = isset( $_POST['quote_id'] ) ? sanitize_text_field( wp_unslash( $_POST['quote_id'] ) ) : '';
				$title_name  = isset( $_POST['title_name'] ) ? sanitize_text_field( wp_unslash( $_POST['title_name'] ) ) : '';
				$first_name  = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
				$last_name   = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
				$img_url     = isset( $_POST['img_url'] ) ? sanitize_text_field( wp_unslash( $_POST['img_url'] ) ) : '';
				$author_icon = isset( $_POST['author_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['author_icon'] ) ) : $this->check_plugin_option( 'author_icon' );
				$source_icon = isset( $_POST['source_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['source_icon'] ) ) : $this->check_plugin_option( 'source_icon' );
				$category    = isset( $_POST['ql_category'] ) ? map_deep( wp_unslash( $_POST['ql_category'] ), 'sanitize_text_field' ) : array();
				$category    = implode( ', ', $category );
				$this->msg   = $this->quotes_update( $quote_id, $quote, $title_name, $first_name, $last_name, $source, $img_url, $author_icon, $source_icon, $category );
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_GET to delete a single quote.
		if ( isset( $_GET['action'] ) && 'quotes_llama_delete_single' === $_GET['action'] ) {
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			$id    = isset( $_GET['quote_id'] ) ? sanitize_text_field( wp_unslash( $_GET['quote_id'] ) ) : '';
			$s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$s     = ! empty( $s ) ? '&s=' . $s : '';
			$sc    = isset( $_GET['sc'] ) ? sanitize_text_field( wp_unslash( $_GET['sc'] ) ) : '';
			$sc    = ! empty( $sc ) ? '&sc=' . $sc : '';
			$paged = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : '';
			$paged = ! empty( $paged ) ? '&paged=' . $paged : '';
			if ( wp_verify_nonce( $nonce, 'delete_edit' ) ) {
				$d = $this->quotes_delete( $id );
				header( 'Location: ' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=quotes-llama&d=' . $d . $s . $sc . $paged . '&_wpnonce=' . $nonce );
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// $_GET to bulk delete. Upper bulk select box is action. Lower bulk select box is action2.
		if ( ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] ) || ( isset( $_GET['action2'] ) && 'delete' === $_GET['action2'] ) ) {
			$nonce = isset( $_GET['llama_admin_delete_bulk'] ) ? sanitize_text_field( wp_unslash( $_GET['llama_admin_delete_bulk'] ) ) : '';
			$paged = isset( $_GET['paged'] ) ? '&paged=' . sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : '';
			if ( wp_verify_nonce( $nonce, 'llama_admin_delete_bulk' ) ) {
				if ( isset( $_GET['bulkcheck'] ) ) { // Sanitizes each value below. Generates phpcs error.
					$checks    = $_GET['bulkcheck']; // phpcs:ignore
					$bulkcheck = array();
					foreach ( $checks as $key => $val ) {
						$bulkcheck[ $key ] = ( isset( $checks[ $key ] ) ) ? sanitize_text_field( wp_unslash( $val ) ) : '';
					}

					$bd = $this->quotes_delete_bulk( $bulkcheck );
					header( 'Location: ' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=quotes-llama&bd=' . $bd . '&_wpnonce=' . $nonce . $paged );
				} else { // If no quotes selected.
					header( 'Location: ' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=quotes-llama&bd=u&_wpnonce=' . $nonce . $paged );
				}
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}
	}

	/**
	 * Base shortcode.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_shortcodes() {
		add_shortcode( 'quotes-llama', array( $this, 'plugin_start' ) );
	}

	/**
	 * Start plugin via template or page shortcodes. The order of execution is important!
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $atts - mode,cat,id or all.
	 */
	public function plugin_start( $atts ) {

		$att_array = shortcode_atts(
			array(
				'mode'   => 'quote',
				'id'     => 0,
				'all'    => 0,
				'cat'    => 0,
				'quotes' => 0,
			),
			$atts
		);

		// [quotes-llama mode='auto' cat='category'] Display quote from category in auto-refresh mode.
		if ( $att_array['cat'] && ( 'auto' === $att_array['mode'] ) ) {
			return $this->template_auto( $att_array['cat'] );
		}

		// [quotes-llama mode='auto'] Auto-refresh random quote. This should be called last in auto modes.
		if ( 'auto' === $att_array['mode'] ) {
			return $this->template_auto();
		}

		// [quotes-llama mode='gallery' cat='category'] Display quote from category in gallery mode.
		if ( $att_array['cat'] && ( 'gallery' === $att_array['mode'] ) ) {
			return $this->template_gallery( $att_array['cat'] );
		}

		// [quotes-llama mode='gallery'] Quotes gallery. This should be called last in gallery modes.
		if ( 'gallery' === $att_array['mode'] ) {
			return $this->template_gallery();
		}

		// [quotes-llama mode='page' cat='category'] Quotes Page of a category of qutoes.
		if ( $att_array['cat'] && ( 'page' === $att_array['mode'] ) ) {
			return $this->template_page( wp_create_nonce( 'quotes_llama_nonce' ), $att_array['cat'] );
		}

		// [quotes-llama mode='page'] Quotes Page of all quotes. This should be called last in page modes.
		if ( 'page' === $att_array['mode'] ) {
			return $this->template_page( wp_create_nonce( 'quotes_llama_nonce' ), '' );
		}

		// [quotes-llama] A single random quote .
		if ( 'quote' === $att_array['mode'] &&
			0 === $att_array['id'] &&
			0 === $att_array['all'] &&
			0 === $att_array['cat'] &&
			0 === $att_array['quotes']
		) {
			return $this->template_post();
		}

		// [quotes-llama quotes='#' cat='category'] Get a number of static quotes from category.
		if ( $att_array['quotes'] && $att_array['cat'] ) {
			return $this->template_posts( $att_array['cat'], $att_array['quotes'] );
		}

		// [quotes-llama quotes='#'] Get a number of random static quotes. This should be called last in quote and quotes.
		if ( $att_array['quotes'] ) {
			return $this->template_posts( '', $att_array['quotes'] );
		}

		// [quotes-llama id='id, ids'] Quotes by the ids.
		if ( $att_array['id'] ) {
			$quote_id  = explode( ',', $atts['id'] );
			$id_string = '';
			foreach ( $quote_id as $id ) {
				$id_string .= $this->template_id( $id );
			}
			return $id_string;
		}

		// [quotes-llama all='random, ascend, descend, id' cat='category'] All quotes by categories. This should be called first in 'all' shortcodes.
		if ( $att_array['all'] && $att_array['cat'] ) {
			return $this->template_all( $att_array['all'], $att_array['cat'] );
		}

		// [quotes-llama all='random'] All quotes by random.
		if ( 'random' === $att_array['all'] ) {
			return $this->template_all( 'random', '' );
		}

		// [quotes-llama all='ascend'] All quotes ascending.
		if ( 'ascend' === $att_array['all'] ) {
			return $this->template_all( 'ascend', '' );
		}

		// [quotes-llama all='descend'] All quotes descending.
		if ( 'descend' === $att_array['all'] ) {
			return $this->template_all( 'descend', '' );
		}

		// [quotes-llama all='id'] All quotes by id.
		if ( 'id' === $att_array['all'] ) {
			return $this->template_all( 'id', '' );
		}

		// [quotes-llama cat='category'] Display random quote from a category. This should be called last in cat shortcodes.
		if ( $att_array['cat'] ) {
			return $this->template_post( $att_array['cat'] );
		}
	}

	/**
	 * Register options array when activating plugin.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_activation() {

		// Options default values.
		$add_options = array(
			'show_page_author'       => true,
			'show_page_source'       => true,
			'show_page_image'        => true,
			'show_page_next'         => false,
			'show_gallery_author'    => true,
			'show_gallery_source'    => true,
			'show_gallery_image'     => true,
			'gallery_timer_show'     => true,
			'gallery_timer_interval' => '12',
			'gallery_timer_minimum'  => 10,
			'sidebar'                => 'left',
			'background_color'       => '#444',
			'foreground_color'       => 'silver',
			'default_sort'           => 'quote_id',
			'default_order'          => 'dsc',
			'permission_level'       => 'create_users',
			'admin_reset'            => true,
			'export_delimiter'       => '|',
			'character_limit'        => 0,
			'next_quote_text'        => '&hellip; (next quote)',
			'ellipses_text'          => '...',
			'source_newline'         => 'br',
			'read_more_text'         => '&raquo;',
			'read_less_text'         => '&laquo;',
			'show_icons'             => true,
			'author_icon'            => 'edit',
			'source_icon'            => 'migrate',
			'search_allow'           => false,
			'http_display'           => false,
			'border_radius'          => false,
			'image_at_top'           => false,
			'align_quote'            => 'left',
			'transition_speed'       => 1000,
		);
		add_option( 'quotes-llama-settings', $add_options );
	}

	/**
	 * Remove api setting when deactivating plugin and the options but, only if enabled in options.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_deactivation() {
		if ( isset( $this->quotes_llama_plugin_options['admin_reset'] ) ) {
			delete_option( 'quotes-llama-settings' );
		}
		unregister_setting( 'quotes-llama-settings', 'quotes-llama-settings' );
	}

	/**
	 * Define i18n language folder.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_text_domain() {
		load_plugin_textdomain( 'quotes-llama', false, $this->quotes_llama_plugin_url . 'lang' );
	}

	/**
	 * Information about plugin.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $i - Field name to get.
	 *
	 * returns string - Field text.
	 */
	public function plugin_information( $i ) {
		$data = get_plugin_data( __FILE__ );
		$info = $data[ $i ];
		return $info;
	}

	/**
	 * Plugin database table. If plugin table does not exist, create it.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_db_setup() {
		global $wpdb;

		// Set collation.
		$charset_collate = $wpdb->get_charset_collate();

		$sql = $wpdb->prepare(
			'CREATE TABLE ' . $wpdb->prefix . 'quotes_llama (
				quote_id mediumint( 9 ) NOT NULL AUTO_INCREMENT,
				quote TEXT NOT NULL,
				title_name VARCHAR( 255 ),	
				first_name VARCHAR( 255 ),
				last_name VARCHAR( 255 ),
				source VARCHAR( 255 ),
				img_url VARCHAR( 255 ),
				author_icon VARCHAR( 255 ),
				source_icon VARCHAR( 255 ),
				category VARCHAR( 255 ),
				UNIQUE KEY quote_id ( quote_id )
			) %1s;', // phpcs:ignore
			$charset_collate
		);

		// Instance of maybe_create_table.
		if ( ! function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Create table if not exist already.
		$results = maybe_create_table( $wpdb->prefix . 'quotes_llama', $sql );

		// Upgrade DB if older version.
		$this->plugin_db_upgrade();

		// If no icon folder, create it.
		$this->plugin_dir_create();
	}

	/**
	 * Plugin database table upgrade. Add column title_name.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function plugin_db_upgrade() {
		global $wpdb;

		// If before version 1.2.0 then add 'title_name' column to table.
		// Remove this check once reported install versions are all above 1.1.2.
		$vc1 = version_compare( '1.2.0', $this->quotes_llama_plugin_version(), '>=' );

		if ( $vc1 ) {

			// Check that database table exists.
			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "quotes_llama'" ) === $wpdb->prefix . 'quotes_llama' ) { // phpcs:ignore

				// Database name.
				$dbname = $wpdb->dbname;

				// Check for the title_name column.
				$istitle = $wpdb->get_results( // phpcs:ignore
					"SELECT
						COLUMN_NAME
							FROM
						INFORMATION_SCHEMA.COLUMNS
							WHERE
						table_name = '" . $wpdb->prefix .
						"quotes_llama' AND TABLE_SCHEMA = '" . $wpdb->dbname .
						"' AND COLUMN_NAME = 'title_name'"
				);

				// If no title_name column, create it.
				if ( empty( $istitle ) ) {
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'ALTER TABLE `%1s` ADD title_name VARCHAR( 255 ) NULL DEFAULT NULL AFTER quote; ', // phpcs:ignore
							$wpdb->prefix . 'quotes_llama'
						)
					);
				}
			}

			// If no icon folder, create it.
			$this->plugin_dir_create();
		}

		// If before version 1.3.0 then add 'author_icon' and 'source_icon' columns to table.
		// Remove this check once reported install versions are all above 1.2.0.
		$vc2 = version_compare( '1.3.0', $this->quotes_llama_plugin_version(), '>=' );

		if ( $vc2 ) {

			// Check that database table exists.
			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "quotes_llama'" ) === $wpdb->prefix . 'quotes_llama' ) { // phpcs:ignore

				// Database name.
				$dbname = $wpdb->dbname;

				// Check for the source_icon column.
				$issource = $wpdb->get_results( // phpcs:ignore
					"SELECT
						COLUMN_NAME
							FROM
						INFORMATION_SCHEMA.COLUMNS
							WHERE
						table_name = '" . $wpdb->prefix .
						"quotes_llama' AND TABLE_SCHEMA = '" . $wpdb->dbname .
						"' AND COLUMN_NAME = 'source_icon'"
				);

				// If no source_icon column, create it.
				if ( empty( $issource ) ) {
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'ALTER TABLE `%1s` ADD source_icon VARCHAR( 255 ) NULL DEFAULT NULL AFTER img_url; ', // phpcs:ignore
							$wpdb->prefix . 'quotes_llama'
						)
					);
				}

				// Check for the author_icon column.
				$isauthor = $wpdb->get_results( // phpcs:ignore
					"SELECT
						COLUMN_NAME
							FROM
						INFORMATION_SCHEMA.COLUMNS
							WHERE
						table_name = '" . $wpdb->prefix .
						"quotes_llama' AND TABLE_SCHEMA = '" . $wpdb->dbname .
						"' AND COLUMN_NAME = 'author_icon'"
				);

				// If no author_icon column, create it.
				if ( empty( $isauthor ) ) {
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'ALTER TABLE `%1s` ADD author_icon VARCHAR( 255 ) NULL DEFAULT NULL AFTER img_url; ', // phpcs:ignore
							$wpdb->prefix . 'quotes_llama'
						)
					);
				}
			}

			// If no icon folder, create it.
			$this->plugin_dir_create();
		}

		// If before version 1.3.3 then check for and create icons (quotes-llama) folder in uploads.
		// Remove this check once reported install versions are all above 1.3.2.
		$vc3 = version_compare( '1.3.3', $this->quotes_llama_plugin_version(), '>=' );

		if ( $vc3 ) {

			// If no icon folder, create it.
			$this->plugin_dir_create();
		}

		// If before version 2.0.0 then add 'category' column to table.
		// Remove this check once reported install versions are all above 1.3.6.
		$vc4 = version_compare( '2.0.0', $this->quotes_llama_plugin_version(), '>=' );

		if ( $vc4 ) {

			// Check that database table exists.
			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "quotes_llama'" ) === $wpdb->prefix . 'quotes_llama' ) { // phpcs:ignore

				// Database name.
				$dbname = $wpdb->dbname;

				// Check for the category column.
				$iscategory = $wpdb->get_results( // phpcs:ignore
					"SELECT
						COLUMN_NAME
							FROM
						INFORMATION_SCHEMA.COLUMNS
							WHERE
						table_name = '" . $wpdb->prefix .
						"quotes_llama' AND TABLE_SCHEMA = '" . $wpdb->dbname .
						"' AND COLUMN_NAME = 'category'"
				);

				// If no category column, create it.
				if ( empty( $iscategory ) ) {
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'ALTER TABLE `%1s` ADD category VARCHAR( 255 ) NULL DEFAULT NULL AFTER source_icon; ', // phpcs:ignore
							$wpdb->prefix . 'quotes_llama'
						)
					);
				}
			}

			// If no icon folder, create it.
			$this->plugin_dir_create();
		}
	}

	/**
	 * Manage tab - Removes the quotes_llama table from the database.
	 *
	 * @since 1.3.4
	 * @access private
	 */
	private function plugin_db_remove() {
		global $wpdb;
		$return = $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'quotes_llama' ); // phpcs:ignore

		if ( $return ) {
			$this->msg = $this->message( 'Transaction completed: Table removed.', 'yay' );
		} else {
			$this->msg = $this->message( 'Transaction failed: Failed to remove table. - ' . $return, 'nay' );
		}
	}

	/**
	 * Create icons folder (quote-llama) in uploads.
	 *
	 * @since 1.3.3
	 * @access private
	 */
	private function plugin_dir_create() {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['basedir'] ) ) {
			$icon_dirname = $upload_dir['basedir'] . '/quotes-llama';
			if ( ! file_exists( $icon_dirname ) ) {
				wp_mkdir_p( $icon_dirname );
			}
		}
	}

	/**
	 * Admin manage plugin link, admin panel -> plugins.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $links - Array of existing panel links.
	 *
	 * returns array with new link added.
	 */
	public function plugin_manage_link( $links ) {
		$plugin_manage_link = '<a href="options-general.php?page=quotes-llama">Manage</a>';
		array_unshift( $links, $plugin_manage_link );
		return $links;
	}

	/**
	 * Admin settings link, admin panel -> settings.
	 * Permission to manage the plugin.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_settings_link() {
		$pl   = isset( $this->quotes_llama_plugin_options['permission_level'] ) ? $this->quotes_llama_plugin_options['permission_level'] : 'create_users';
		$hook = add_menu_page(
			'Quotes llama',
			esc_html__( 'Quotes', 'quotes-llama' ),
			$pl,
			'quotes-llama',
			array( $this, 'admin_page' ),
			'dashicons-editor-quote',
			81
		);
		add_action( "load-$hook", array( $this, 'quotes_llama_add_option' ) );
	}

	/**
	 * Setup admin page settings, sections, and fields.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_page_fields() {
		register_setting( 'quotes-llama-settings', 'quotes-llama-settings' );

		// Section post. Settings sections defined here.
		if ( 'options' === $this->active_tab ) {
			add_settings_section(
				'page',
				'<u>' . esc_html__( 'Random/Static Quotes', 'quotes-llama' ) . '</u>',
				array(
					$this,
					'admin_section_page_callback',
				),
				'quotes-llama'
			);

			// Section gallery.
			add_settings_section(
				'gallery',
				'<u>' . esc_html__( 'Gallery Quotes', 'quotes-llama' ) . '</u>',
				array(
					$this,
					'admin_section_gallery_callback',
				),
				'quotes-llama'
			);

			// Page options.
			add_settings_section(
				'authors',
				'<u>' . esc_html__( 'Authors Page', 'quotes-llama' ) . '</u>',
				array(
					$this,
					'admin_section_authors_callback',
				),
				'quotes-llama'
			);

			// Quote auto-refresh options.
			add_settings_section(
				'auto_refresh',
				'<u>' . esc_html__( 'Quotes Auto-refresh', 'quotes-llama' ) . '</u>',
				array(
					$this,
					'admin_section_auto_refresh_callback',
				),
				'quotes-llama'
			);

			// Quote character limit options.
			add_settings_section(
				'limit',
				'<u>' . esc_html__( 'Quotes Display', 'quotes-llama' ) . '</u>',
				array(
					$this,
					'admin_section_limit_callback',
				),
				'quotes-llama'
			);

			// Quotes list.
			add_settings_section(
				'quotes_tab',
				'<u>' . esc_html__( 'Quotes List Tab', 'quotes-llama' ) . '</u>',
				array(
					$this,
					'admin_section_quotes_tab_callback',
				),
				'quotes-llama'
			);

			// Other options.
			add_settings_section(
				'other',
				'<u>' . esc_html__( 'Other Options', 'quotes-llama' ) . '</u>',
				array(
					$this,
					'admin_section_other_callback',
				),
				'quotes-llama'
			);

			// Show random author. Settings fields defined here...
			add_settings_field(
				'show_page_author',
				esc_html__( 'Author', 'quotes-llama' ),
				array(
					$this,
					'admin_page_author_callback',
				),
				'quotes-llama',
				'page'
			);

			// Show random source.
			add_settings_field(
				'show_page_source',
				esc_html__( 'Source', 'quotes-llama' ),
				array(
					$this,
					'admin_page_source_callback',
				),
				'quotes-llama',
				'page'
			);

			// Show random image.
			add_settings_field(
				'show_page_image',
				esc_html__( 'Image', 'quotes-llama' ),
				array(
					$this,
					'admin_page_image_callback',
				),
				'quotes-llama',
				'page'
			);

			// Show gallery author.
			add_settings_field(
				'show_gallery_author',
				esc_html__( 'Author', 'quotes-llama' ),
				array(
					$this,
					'admin_gallery_author_callback',
				),
				'quotes-llama',
				'gallery'
			);

			// Show gallery source.
			add_settings_field(
				'show_gallery_source',
				esc_html__( 'Source', 'quotes-llama' ),
				array(
					$this,
					'admin_gallery_source_callback',
				),
				'quotes-llama',
				'gallery'
			);

			// Show gallery image.
			add_settings_field(
				'show_gallery_image',
				esc_html__( 'Image', 'quotes-llama' ),
				array(
					$this,
					'admin_gallery_image_callback',
				),
				'quotes-llama',
				'gallery'
			);

			// Sidebar position.
			add_settings_field(
				'sidebar',
				esc_html__( 'Sidebar Position', 'quotes-llama' ),
				array(
					$this,
					'admin_sidebar_position_callback',
				),
				'quotes-llama',
				'authors'
			);

			// Background color.
			add_settings_field(
				'background_color',
				esc_html__( 'Background Color', 'quotes-llama' ),
				array(
					$this,
					'admin_background_color_callback',
				),
				'quotes-llama',
				'authors'
			);

			// Foreground color.
			add_settings_field(
				'foreground_color',
				esc_html__( 'Foreground Color', 'quotes-llama' ),
				array(
					$this,
					'admin_foreground_color_callback',
				),
				'quotes-llama',
				'authors'
			);

			// Quote character limit.
			add_settings_field(
				'character_limit',
				esc_html__( 'Character Limit', 'quotes-llama' ),
				array(
					$this,
					'admin_character_limit_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Show [quotes-llama] next quote link.
			add_settings_field(
				'show_page_next',
				esc_html__( 'Next Quote', 'quotes-llama' ),
				array(
					$this,
					'admin_page_next_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Next quote text.
			add_settings_field(
				'next_quote_text',
				esc_html__( 'Next Quote Text', 'quotes-llama' ),
				array(
					$this,
					'admin_next_quote_text_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Ellipses text.
			add_settings_field(
				'ellipses_text',
				esc_html__( 'Ellipses Text', 'quotes-llama' ),
				array(
					$this,
					'admin_ellipses_text_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Read more text.
			add_settings_field(
				'read_more_text',
				esc_html__( 'Read More Text', 'quotes-llama' ),
				array(
					$this,
					'admin_read_more_text_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Read less text.
			add_settings_field(
				'read_less_text',
				esc_html__( 'Read Less Text', 'quotes-llama' ),
				array(
					$this,
					'admin_read_less_text_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Round Images.
			add_settings_field(
				'border_radius',
				esc_html__( 'Round Images', 'quotes-llama' ),
				array(
					$this,
					'admin_border_radius_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Images above quotes.
			add_settings_field(
				'image_at_top',
				esc_html__( 'Images On Top', 'quotes-llama' ),
				array(
					$this,
					'admin_image_at_top_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Align quote text.
			add_settings_field(
				'align_quote',
				esc_html__( 'Align Quote Text', 'quotes-llama' ),
				array(
					$this,
					'admin_align_quote_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Display icons before author and source.
			add_settings_field(
				'show_icons',
				esc_html__( 'Display Icons', 'quotes-llama' ),
				array(
					$this,
					'admin_show_icons_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Author icon, which icon.
			add_settings_field(
				'author_icon',
				esc_html__( 'Author Icon', 'quotes-llama' ),
				array(
					$this,
					'admin_author_icon_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Source icon, which icon.
			add_settings_field(
				'source_icon',
				esc_html__( 'Source Icon', 'quotes-llama' ),
				array(
					$this,
					'admin_source_icon_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Display search form for all visitors.
			add_settings_field(
				'search_allow',
				esc_html__( 'Search Form', 'quotes-llama' ),
				array(
					$this,
					'admin_search_allow_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Display http in text links.
			add_settings_field(
				'http_display',
				esc_html__( 'Display HTTP', 'quotes-llama' ),
				array(
					$this,
					'admin_http_display_callback',
				),
				'quotes-llama',
				'limit'
			);

			// Display timer in quotes.
			add_settings_field(
				'gallery_timer_show',
				esc_html__( 'Display Timer', 'quotes-llama' ),
				array(
					$this,
					'admin_gallery_timer_show_callback',
				),
				'quotes-llama',
				'auto_refresh'
			);

			// Timer interval, how fast or slow.
			add_settings_field(
				'gallery_timer_interval',
				esc_html__( 'Timer', 'quotes-llama' ),
				array(
					$this,
					'admin_gallery_timer_interval_callback',
				),
				'quotes-llama',
				'auto_refresh'
			);

			// Transition speed, slow, normal, fast, instant.
			add_settings_field(
				'transition_speed',
				esc_html__( 'Transition Speed', 'quotes-llama' ),
				array(
					$this,
					'admin_transition_speed_callback',
				),
				'quotes-llama',
				'auto_refresh'
			);

			// Timer minimum seconds display.
			add_settings_field(
				'gallery_timer_minimum',
				esc_html__( 'Timer Minimum', 'quotes-llama' ),
				array(
					$this,
					'admin_gallery_timer_minimum_callback',
				),
				'quotes-llama',
				'auto_refresh'
			);

			// Default sort column.
			add_settings_field(
				'default_sort',
				esc_html__( 'Default Sort Column', 'quotes-llama' ),
				array(
					$this,
					'admin_orderby_callback',
				),
				'quotes-llama',
				'quotes_tab'
			);

			// Default sort order.
			add_settings_field(
				'default_order',
				esc_html__( 'Default Order By', 'quotes-llama' ),
				array(
					$this,
					'admin_order_callback',
				),
				'quotes-llama',
				'quotes_tab'
			);

			// Source on new line.
			add_settings_field(
				'source_newline',
				esc_html__( 'Source Separator', 'quotes-llama' ),
				array(
					$this,
					'admin_source_newline_callback',
				),
				'quotes-llama',
				'other'
			);

			// Permission level.
			add_settings_field(
				'permission_level',
				esc_html__( 'Manage Plugin', 'quotes-llama' ),
				array(
					$this,
					'admin_permission_level_callback',
				),
				'quotes-llama',
				'other'
			);

			// Reset options.
			add_settings_field(
				'admin_reset',
				esc_html__( 'Reset When Deactivating', 'quotes-llama' ),
				array(
					$this,
					'admin_reset_callback',
				),
				'quotes-llama',
				'other'
			);

			// CSV delimiter.
			add_settings_field(
				'export_delimiter',
				esc_html__( 'CSV Delimiter', 'quotes-llama' ),
				array(
					$this,
					'admin_export_delimiter_callback',
				),
				'quotes-llama',
				'other'
			);

			// Widgets.
			add_settings_field(
				'widget_page',
				esc_html__( 'Widgets', 'quotes-llama' ),
				array(
					$this,
					'admin_widget_page_callback',
				),
				'quotes-llama',
				'other'
			);
		}
	}

	/**
	 * Render tabs in admin page.
	 * Checks permisson to view the admin page.
	 * Check our database and upgrade if needed.
	 * Display our action msg.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_page() {
		$pl = isset( $this->quotes_llama_plugin_options['permission_level'] ) ? $this->quotes_llama_plugin_options['permission_level'] : 'create_users';

		if ( current_user_can( $pl ) ) {
			$this->plugin_db_upgrade();
			$admin_tabs_nonce = wp_create_nonce( 'quotes_llama_admin_tabs' );
			$allowed_html     = $this->quotes_llama_allowed_html( 'div' );
			echo wp_kses( $this->msg, $allowed_html );

			echo '<div class="wrap">';
				echo wp_kses_post( '<h2>' . $this->plugin_information( 'Name' ) . ' - <small>' . esc_html( $this->plugin_information( 'Version' ) ) . '</small></h2>' );
				echo wp_kses_post( '<h3>' . $this->plugin_information( 'Description' ) . '</h3>' );
				$this->admin_tabs( $admin_tabs_nonce );
				$this->admin_tab_quotes();
				$this->admin_tab_options();
				$this->admin_tab_add();
				$this->admin_tab_manage();
				$this->admin_tab_short_codes();
			echo '</div>';
		} else {
			echo wp_kses_post(
				$this->message(
					esc_html__(
						'You do not have sufficient permissions to access this page.',
						'quotes-llama'
					),
					'nay'
				)
			);
		}
	}

	/**
	 * Admin tabs list.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $nonce - Nonce.
	 */
	public function admin_tabs( $nonce ) {
		if ( wp_verify_nonce( $nonce, 'quotes_llama_admin_tabs' ) ) {
			$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$current_url = remove_query_arg(
				array(
					'bd',
					'd',
					's',
					'sc',
					'action',
					'action2',
					'paged',
					'action',
					'tab',
					'quote_id',
					'_wpnonce',
					'_wp_http_referer',
					'llama_admin_delete_bulk',
				),
				stripslashes( $current_url )
			);
			$quotes      = $current_url . '&tab=quotes&_wpnonce=' . $nonce;
			$add         = $current_url . '&tab=add&_wpnonce=' . $nonce;
			$options     = $current_url . '&tab=options&_wpnonce=' . $nonce;
			$manage      = $current_url . '&tab=manage&_wpnonce=' . $nonce;
			$shortcodes  = $current_url . '&tab=short_codes&_wpnonce=' . $nonce;
			?>
			<!-- admin tabs. -->
			<h2 class='nav-tab-wrapper'>
				<a href='<?php echo esc_url_raw( $quotes ); ?>'
					class='nav-tab <?php echo 'quotes' === $this->active_tab ? 'nav-tab-active' : ''; ?>'>
					<?php esc_html_e( 'Quotes List', 'quotes-llama' ); ?>
				</a>
				<a href='<?php echo esc_url_raw( $add ); ?>'
					class='nav-tab <?php echo 'add' === $this->active_tab ? 'nav-tab-active' : ''; ?>'>
					<?php esc_html_e( 'New Quote', 'quotes-llama' ); ?>
				</a>
				<a href='<?php echo esc_url_raw( $options ); ?>'
					class='nav-tab <?php echo 'options' === $this->active_tab ? 'nav-tab-active' : ''; ?>'>
					<?php esc_html_e( 'Options', 'quotes-llama' ); ?>
				</a>
				<a href='<?php echo esc_url_raw( $manage ); ?>'
					class='nav-tab <?php echo 'manage' === $this->active_tab ? 'nav-tab-active' : ''; ?>'>
					<?php esc_html_e( 'Manage', 'quotes-llama' ); ?>
				</a>
				<a href='<?php echo esc_url_raw( $shortcodes ); ?>'
					class='nav-tab <?php echo 'short_codes' === $this->active_tab ? 'nav-tab-active' : ''; ?>'>
					<?php esc_html_e( 'Shortcode', 'quotes-llama' ); ?>
				</a>
			</h2> 
			<?php
		}
	}

	/**
	 * Quotes list tab.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_tab_quotes() {
		global $quotes_table;

		if ( 'quotes' === $this->active_tab ) { // Tab - Quotes list.
			?>
			<div class='wrap'>
				<?php
				$action     = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
				$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
				$id         = isset( $_GET['quote_id'] ) ? sanitize_text_field( wp_unslash( $_GET['quote_id'] ) ) : '';
				$page       = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
				$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
				$search_col = isset( $_GET['sc'] ) ? sanitize_text_field( wp_unslash( $_GET['sc'] ) ) : '';

				// $_GET to get quote for editing.
				if ( 'e' === $action ) {

					// $_GET placed here so edit form will render inline.
					if ( wp_verify_nonce( $nonce, 'delete_edit' ) ) {
						?>
						<div class='wrap quotes-llama-admin-form'>
							<h2>
								<?php
									esc_html_e( 'Edit Quote', 'quotes-llama' );
								?>
							</h2>
							<?php
							$qform        = $this->quotes_form( $id, $this->return_page( $nonce ) );
							$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
							echo wp_kses( $qform, $allowed_html );
							?>
						</div>
						<?php
					} else {
						$this->msg = $this->message( '', 'nonce' );
					}
					return;
				}

				$uasort_nonce = wp_create_nonce( 'quotes_llama_uasort_nonce' );

				if ( isset( $search ) ) {

					// Searching quotes table.
					$quotes_table->prepare_items( $search, $search_col, 20, $uasort_nonce );
				} else {

					// Or get all quotes.
					$quotes_table->prepare_items( '', '', 20, $uasort_nonce );
				}
				?>
				<!-- Form that contains the search input and drop-list. -->
				<form id='quotes-filter' method='get' class='quotes-llama-admin-form'>
					<?php
					$quotes_table->search_box( esc_html__( 'Search', 'quotes-llama' ), 'quotes-llama-admin-search', $uasort_nonce );
					?>
					<input type='hidden' name='page' value='<?php echo esc_attr( $page ); ?>'>
					<?php
					wp_nonce_field( 'llama_admin_search_nonce', 'as' );
					?>
				</form>

				<!-- Form that contains the bulk actions and quotes table. -->					
				<form id='quotes-filter' method='get' class='quotes-llama-admin-form'>
					<input type='hidden' name='page' value='<?php echo esc_attr( $page ); ?>'>
					<?php
					wp_nonce_field( 'llama_admin_delete_bulk', 'llama_admin_delete_bulk' );

					// Render table.
					$quotes_table->display();
					?>
					<!-- Overwrite _wp_http_referer to nothing to prevent url too long events re-using page number and empty bulk button. -->
					<input type="hidden" name="_wp_http_referer" value="">
				</form>
			</div>
			<?php
		}
	}

	/**
	 * New Quote tab.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_tab_add() {
		if ( 'add' === $this->active_tab ) {
			?>
			<div id='addnew' class='quotes-llama-admin-form'>
				<h2>
					<?php
						esc_html_e( 'New Quote', 'quotes-llama' );
					?>
				</h2>
					<?php
					$qform        = $this->quotes_form( 0, '' );
					$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
					echo wp_kses( $qform, $allowed_html );
					?>
				</div>
			<?php
		}
	}

	/**
	 * Manage tab.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_tab_manage() {
		if ( 'manage' === $this->active_tab ) {
			$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
			?>
			<div class='quotes-llama-inline'>
				<!-- Manage Categories -->
				<?php $admin_tabs_nonce = wp_create_nonce( 'quotes_llama_admin_tabs' ); ?>
				<form name='' method='post' onsubmit="return quotes_llama_change_table_confirm()" action='<?php echo esc_url( get_bloginfo( 'wpurl' ) ); ?>/wp-admin/admin.php?page=quotes-llama&tab=manage&_wpnonce=<?php echo esc_attr( $admin_tabs_nonce ); ?>' enctype='multipart/form-data'> 
					<?php
						wp_nonce_field( 'quotes_llama_admin_tabs', 'quotes_llama_admin_tabs' );
						echo '<span class="quotes-llama-admin-form"><h2><u>' . esc_html__( 'Category (Rename/Delete)', 'quotes-llama' ) . '</u></h2></span>';
						echo '<p>' . esc_html__( 'Rename or delete existing categories... for new categories, add a "New Quote" or edit an existing quote.', 'quotes-llama' ) . '</p>';

						// Get all categories in button list format.
						$cat = $this->quotes_llama_get_categories();
						echo wp_kses( $cat, $allowed_html );
					?>
				</form>

				<!-- Export quotes. -->
				<form method='post' action='<?php echo esc_url( get_bloginfo( 'wpurl' ) ); ?>/wp-admin/admin.php?page=quotes-llama'> 
					<?php
						echo '<span class="quotes-llama-admin-form"><h2><u>' . esc_html__( 'Export Quotes (Backup)', 'quotes-llama' ) . '</u></h2></span>';
						echo '<p>' . esc_html__( 'Backup your quotes to either .csv or .json formats.', 'quotes-llama' ) . '</p>';
						wp_nonce_field( 'quotes_llama_export_nonce', 'quotes_llama_export_nonce' );
						submit_button( esc_html__( 'Export .csv', 'quotes-llama' ), 'large', 'quotes_llama_export_csv', false, array( 'quotes_llama_export_csv' => 'quotes' ) );
						echo '&nbsp';
						submit_button( esc_html__( 'Export .json', 'quotes-llama' ), 'large', 'quotes_llama_export_json', false, array( 'quotes_llama_export_json' => 'quotes' ) );
						echo '<p>' . esc_html__( 'The .csv delimiter can be set in the options tab.', 'quotes-llama' ) . '</p>';
					?>
				</form>

				<!-- Import quotes -->
				<form name='' method='post' action='<?php echo esc_url( get_bloginfo( 'wpurl' ) ); ?>/wp-admin/admin.php?page=quotes-llama'  enctype='multipart/form-data'> 
					<?php
						wp_nonce_field( 'quote_llama_import_nonce', 'quote_llama_import_nonce' );
						echo '<span class="quotes-llama-admin-form"><h2><u>' . esc_html__( 'Import Quotes (Restore)', 'quotes-llama' ) . '</u></h2></span>';
						echo '<p>' . esc_html__( 'Restore your quotes from either .csv or .json formats. Browse for a file, then select the import button.', 'quotes-llama' ) . '</p>';
					?>
					<input type='file' class='button button-large' name='quotes-llama-file' accept='.csv, .json'> 
					<?php
						submit_button( esc_html__( 'Import', 'quotes-llama' ), 'secondary', 'quote_llama_import', true, array( 'quote_llama_import' => 'quotes' ) );
					?>
				</form>

				<?php
				// Delete database table... Administrator only.
				if ( current_user_can( 'administrator' ) ) {
					?>
					<form method='post' onsubmit="return quotes_llama_change_table_confirm()" action='<?php echo esc_url( get_bloginfo( 'wpurl' ) ); ?>/wp-admin/admin.php?page=quotes-llama'> 
						<?php
						echo '<span class="quotes-llama-admin-form"><h2><u>' . esc_html__( 'Remove Table (Delete)', 'quotes-llama' ) . '</u></h2></span>';
						echo '<p>' . esc_html__( 'Remove the (..._quotes_llama) table from the database. This action cannot be undone!', 'quotes-llama' ) . '</p>';
						echo '<p>' . esc_html__( 'Create a backup of your database and export the quotes before continuing.', 'quotes-llama' ) . '</p>';
						wp_nonce_field( 'quotes_llama_remove_table_nonce', 'quotes_llama_remove_table_nonce' );
						echo '<input type="hidden" name="quotes_llama_remove_table" value="quotes">';
						?>
						<input type='submit' value='Remove Table' class='button button-small'> 
					</form>
					<?php
				}
				?>
			</div> 
			<?php
		}
	}

	/**
	 * Shortcodes tab.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_tab_short_codes() {
		if ( 'short_codes' === $this->active_tab ) {
			?>
			<div>
				<span class="quotes-llama-admin-form">
					<h2>
						<?php esc_html_e( 'Include this plugin in a block, page, or post:', 'quotes-llama' ); ?>
					</h2>
				</span>
				<table>
					<tr>
						<th>
							<?php esc_html_e( 'Shortcode:', 'quotes-llama' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Description:', 'quotes-llama' ); ?>
						</th>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama]</code></b>
						</td>
						<td>
							<?php esc_html_e( 'Random quote.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama cat='category']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'Random quote from a category.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama quotes='#']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'A number of random quotes.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama quotes='#' cat='category']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'A number of random quotes from a category.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama mode='gallery']</code>
						</td>
						<td>
							<?php esc_html_e( 'Gallery of all quotes.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama mode='gallery' cat='category']</code>
						</td>
						<td>
							<?php esc_html_e( 'Gallery of quotes from a category.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama mode='page']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'All Authors page.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama mode='page' cat='category']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'Authors page from a category.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama mode='auto']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'Random quote that will auto-refresh.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama mode='auto' cat='category']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'Random quote from a category that will auto-refresh.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama id='#,#,#']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'Static quote.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama all='id']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'All quotes sorted by id.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama all='random']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'All quotes by random selection.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama all='ascend']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'All quotes sorted ascending.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama all='descend']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'All quotes sorted descending.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><code>[quotes-llama all='*' cat='category']</code></b>
						</td>
						<td>
							<?php esc_html_e( 'All quotes from a category.', 'quotes-llama' ); ?>
						</td>
					</tr>
					<tr>
						<td>
						</td>
						<td>
							<?php esc_html_e( '* The asterik (*) should be one of the following (id, random, ascend or descend)', 'quotes-llama' ); ?>
						</td>
					</tr>
				</table>
				<span class="quotes-llama-admin-form">
					<h2>
						<?php esc_html_e( 'Include this plugin in a template file:', 'quotes-llama' ); ?>
					</h2>
				</span>
				<table>
						<tr>
							<th>
							</th>
						</tr>
						<tr>
							<th>
								<?php esc_html_e( 'Shortcode:', 'quotes-llama' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Description:', 'quotes-llama' ); ?>
							</th>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama]" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Random quote.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama cat='category']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Random quote from a category.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama quotes='#']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'A number of random quotes.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama quotes='#' cat='category']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'A number of random quotes from a category.', 'quotes-llama' ); ?>
							</td>
						</tr>
						</tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama mode='gallery']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Gallery of quotes.', 'quotes-llama' ); ?>
							</td>
						</tr>
						</tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama mode='gallery' cat='category']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Gallery of quotes from a category.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama mode='page']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Authors page.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama mode='page' cat='category']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Authors page from a category.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama mode='auto']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Random quote that will auto-refresh.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama mode='auto' cat='category']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Random quote from a category that will auto-refresh.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama id='#,#,#']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'Static quote.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama all='id']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'All quotes sorted by id.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama all='random']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'All quotes by random selection.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama all='ascend']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'All quotes sorted ascending.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama all='descend']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'All quotes sorted descending.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><code>do_shortcode( "[quotes-llama all='*' cat='category']" );</code></b>
							</td>
							<td>
								<?php esc_html_e( 'All quotes from a category.', 'quotes-llama' ); ?>
							</td>
						</tr>
						<tr>
							<td>
							</td>
							<td>
								<?php esc_html_e( '* The asterik (*) should be one of the following (id, random, ascend or descend)', 'quotes-llama' ); ?>
							</td>
						</tr>
					</table>

					<span class="quotes-llama-admin-form">
					<h2>
						<?php esc_html_e( 'Include this plugin in a Widget:', 'quotes-llama' ); ?>
					</h2>
					</span>
					<p>
						<?php
						esc_html_e( 'Widget options are set in the ', 'quotes-llama' );
						?>
						<a href='<?php echo esc_url( get_bloginfo( 'wpurl' ) . '/wp-admin/widgets.php' ); ?>'>
							<?php
								esc_html_e( 'widgets screen.', 'quotes-llama' );
							?>
						</a><br>
					</p>

				<span class="quotes-llama-admin-form">
					<h2>
						<?php esc_html_e( 'Tips:', 'quotes-llama' ); ?>
					</h2>
				</span>
				<ul>
					<li>
						<?php esc_html_e( 'Include your own custom icons by uploading (.png, .jpg, .jpeg, .gif, .bmp, .svg) images to the "quotes-llama" folder in your uploads directory.', 'quotes-llama' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Use a comma for multiple categories in shortcodes and widgets...', 'quotes-llama' ); ?> (cat='category, category')
					</li>
					<li>
						<?php esc_html_e( 'A Widget with a shortcode is another option to display quotes in widgets.', 'quotes-llama' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'You can include dash-icons and unicode symbols in the "next quote text" option field.', 'quotes-llama' ); ?>
						<br>&nbsp;<small>e.g. <code><?php echo esc_html( '<span class="dashicons dashicons-arrow-right-alt2">' ); ?></code></small>
						<a href="https://developer.wordpress.org/resource/dashicons/#dashboard" target="_blank" title="Dash-icons">Dashicons</a>
					</li>
					<li>
						<?php esc_html_e( 'Navigate to your Dashboard–>Appearance–>Customize–>Additional CSS.', 'quotes-llama' ); ?>
						<br>
						<?php
						esc_html_e( 'This will remove the line between the quote and the next quote link.', 'quotes-llama' );
						echo '<br><code>.quotes-llama-widget-random hr {display: none;}</code><br><br>';
						esc_html_e( 'DO NOT directly edit any theme/plugin files as they are ALL overwritten when updating.', 'quotes-llama' );
						?>
					</li>
				</ul>

				<span class="quotes-llama-admin-form">
					<h2>
						<?php echo esc_html( 'Support' ); ?>
					</h2>
				</span>

				<div class='quotes-llama-admin-div'>
					<a href='https://wordpress.org/support/plugin/quotes-llama/'
						target='_blank'
						title='<?php esc_attr_e( 'Support Forum', 'quotes-llama' ); ?>'>
						<?php esc_html_e( 'Plugin Support Forum', 'quotes-llama' ); ?>
					</a>
					<br>
					<a href='https://wordpress.org/support/view/plugin-reviews/quotes-llama'
						target='_blank'
						title='<?php esc_attr_e( 'Rate the plugin / Write a review.', 'quotes-llama' ); ?>'>
						<?php
						esc_html_e( ' Rate this plugin / Write a Review', 'quotes-llama' );
						?>
					</a>
					<br>
					<a href="<?php echo esc_url( $this->plugin_information( 'PluginURI' ) ); ?>"
						target="_blank"
						title="<?php echo esc_attr( $this->plugin_information( 'Name' ) ); ?>">
						<?php echo esc_html( $this->plugin_information( 'Name' ) ) . ' on WordPress'; ?>
					</a>
					<br>
					<a href='https://translate.wordpress.org/projects/wp-plugins/quotes-llama/'
						target='_blank'
						title='<?php esc_attr_e( 'You can help translate this plugin into your language.', 'quotes-llama' ); ?>'>
						<?php esc_html_e( 'Translate This Plugin', 'quotes-llama' ); ?>
					</a>
					<br>
					<a href='https://oooorgle.com/copyheart/'
						target='_blank'
						title='<?php esc_attr_e( 'CopyHeart', 'quotes-llama' ); ?>'>
						<?php esc_html_e( 'License: CopyHeart', 'quotes-llama' ); ?>
					</a>
					<br>
					<a href="https://oooorgle.com/plugins/wp/quotes-llama/"
						target="_blank"
						title="<?php esc_attr_e( 'Donate', 'quotes-llama' ); ?>">
						<?php esc_html_e( 'Donations', 'quotes-llama' ); ?>
					</a>
				</div>
			</div> 
			<?php
		}
	}

	/**
	 * Options tab.
	 * Save settings form and button.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_tab_options() {
		if ( 'options' === $this->active_tab ) {
			?>
			<form method='post' action='options.php' class='quotes-llama-admin-form'>
				<?php
					settings_fields( 'quotes-llama-settings' );
					do_settings_sections( 'quotes-llama' );
					'<li>' . esc_html__( 'Widget options are set in the', 'quotes-llama' ) . ' ' .
					'<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/widgets.php">' .
					esc_html__( 'widgets screen.', 'quotes-llama' ) .
					'</a></li>';
					submit_button( esc_html__( 'Save Options', 'quotes-llama' ) );
				?>
			</form> 
			<?php
		}
	}

	/**
	 * Options tab - section post.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_section_page_callback() {
		esc_html_e( 'When using', 'quotes-llama' );
		echo " <code>[quotes-llama]</code>, <code>[quotes-llama id='#']</code>, <code>[quotes-llama cat='category']</code>, <code>[quotes-llama all='*']</code>, <code>[quotes-llama quotes='#']</code> or <code>[quotes-llama mode='auto']</code>";
	}

	/**
	 * Options tab - section gallery.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_section_gallery_callback() {
		esc_html_e( 'When using', 'quotes-llama' );
		echo " <code>[quotes-llama mode='gallery']</code> ";
	}

	/**
	 * Options tab - section authors page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_section_authors_callback() {
		esc_html_e( 'When using', 'quotes-llama' );
		echo " <code>[quotes-llama mode='page']</code> ";
	}

	/**
	 * Options tab - section quote display.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_section_limit_callback() {
		esc_html_e( 'Other display options', 'quotes-llama' );
	}

	/**
	 * Options tab - section quote auto-refresh.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_section_auto_refresh_callback() {
		esc_html_e( 'When using', 'quotes-llama' );
		echo " <code>[quotes-llama mode='gallery'] or [quotes-llama mode='auto']</code> ";
		esc_html_e( 'and in widgets.', 'quotes-llama' );
	}

	/**
	 * Options tab - section quotes tab.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_section_quotes_tab_callback() {
		esc_html_e( 'Options for this plugins Quotes List management tab.', 'quotes-llama' );
	}

	/**
	 * Options tab - section other options.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_section_other_callback() {
		esc_html__( 'All other options.', 'quotes-llama' );
	}

	/**
	 * Options tab - show post author checkbox.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_page_author_callback() {
		?>
		<input type='checkbox'
			id='show_page_author'
			name='quotes-llama-settings[show_page_author]'
			<?php
			if ( $this->check_plugin_option( 'show_page_author' ) ) {
				echo 'checked';}
			?>
			>
		<label for='show_page_author'>
			<?php esc_html_e( 'Display author.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - show post source checkbox.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_page_source_callback() {
		?>
		<input type='checkbox'
			id='show_page_source'
			name='quotes-llama-settings[show_page_source]'
			<?php
			if ( $this->check_plugin_option( 'show_page_source' ) ) {
				echo 'checked';}
			?>
			>
		<label for='show_page_source'>
			<?php esc_html_e( 'Display source.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - show post image checkbox.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_page_image_callback() {
		?>
		<input type='checkbox'
			id='show_page_image'
			name='quotes-llama-settings[show_page_image]'
			<?php
			if ( $this->check_plugin_option( 'show_page_image' ) ) {
				echo 'checked';}
			?>
			>
		<label for='show_page_image'>
			<?php esc_html_e( 'Display image.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - [quotes-llama] next quote checkbox.
	 *
	 * @since 2.0.3
	 * @access public
	 */
	public function admin_page_next_callback() {
		?>
		<input type='checkbox'
			id='show_page_next'
			name='quotes-llama-settings[show_page_next]'
			<?php
			if ( $this->check_plugin_option( 'show_page_next' ) ) {
				echo 'checked';}
			?>
			>
		<label for='show_page_next'>
			<?php
			esc_html_e( 'Display "next quote" link in shortcode:', 'quotes-llama' );
			echo '<code>[quotes-llama]</code>';
			?>
		</label>
		<?php
	}

	/**
	 * Options tab - show gallery author checkbox.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_gallery_author_callback() {
		?>
		<input type='checkbox'
			id='show_gallery_author'
			name='quotes-llama-settings[show_gallery_author]'
			<?php
			if ( $this->check_plugin_option( 'show_gallery_author' ) ) {
				echo 'checked';}
			?>
			>
		<label for='show_gallery_author'>
			<?php esc_html_e( 'Display authors in the gallery.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - show gallery source checkbox.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_gallery_source_callback() {
		?>
		<input type='checkbox'
			id='show_gallery_source'
			name='quotes-llama-settings[show_gallery_source]'
			<?php
			if ( $this->check_plugin_option( 'show_gallery_source' ) ) {
				echo 'checked';
			}
			?>
			>
		<label for='show_gallery_source'>
			<?php esc_html_e( 'Display sources in the gallery.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - Gallery refresh interval adjuster.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_gallery_timer_interval_callback() {
		$allowed_html = $this->quotes_llama_allowed_html( 'option' );
		$t            = $this->check_plugin_option( 'gallery_timer_interval' );
		?>
		<select name='quotes-llama-settings[gallery_timer_interval]' id='gallery_timer_interval'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( '48', esc_html__( 'Shortest', 'quotes-llama' ), $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '43', '..', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '38', '...', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '33', '....', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '28', '.....', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '24', '......', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '20', '.......', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '17', '........', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '14', '.........', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '12', esc_html__( 'Default', 'quotes-llama' ), $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '10', '...........', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '8', '.............', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '6', '..............', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '5', '...............', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '4', esc_html__( 'Longest', 'quotes-llama' ), $t ), $allowed_html );
			?>
		</select>
		<label for='gallery_timer_interval'>
			<?php echo ' ' . esc_html__( 'Display quotes for a longer or shorter time according to this setting.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - transition_speed.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_transition_speed_callback() {
		$allowed_html = $this->quotes_llama_allowed_html( 'option' );
		$t            = $this->check_plugin_option( 'transition_speed' );
		?>
		<select name='quotes-llama-settings[transition_speed]' id='transition_speed'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( '2000', esc_html__( 'Slow', 'quotes-llama' ), $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '1000', esc_html__( 'Normal', 'quotes-llama' ), $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '500', esc_html__( 'Fast', 'quotes-llama' ), $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( '0', esc_html__( 'Instant', 'quotes-llama' ), $t ), $allowed_html );
			?>
		</select>
		<label for='transition_speed'>
			<?php echo ' ' . esc_html__( 'The speed that quotes transition. ', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - Gallery timer minimum time.
	 * This value is used in the JS function quotes_llama_quote()
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_gallery_timer_minimum_callback() {
		?>
		<input type='text'
			id='gallery_timer_minimum'
			name='quotes-llama-settings[gallery_timer_minimum]'
			value='<?php echo absint( esc_html( $this->check_plugin_option( 'gallery_timer_minimum' ) ) ); ?>'
			size='5'>
		<label for='gallery_timer_minimum'>
			<?php esc_html_e( 'Display all quotes for at least this many seconds.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - Gallery, display timer?
	 * This value is used in the JS function quotes_llama_quote()
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_gallery_timer_show_callback() {
		?>
		<input type='checkbox'
			id='gallery_timer_show'
			name='quotes-llama-settings[gallery_timer_show]'
			<?php
			if ( $this->check_plugin_option( 'gallery_timer_show' ) ) {
				echo 'checked';}
			?>
			>
		<label for='gallery_timer_show'>
			<?php esc_html_e( 'Display the countdown timer in quotes.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - show gallery image checkbox.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_gallery_image_callback() {
		?>
		<input type='checkbox'
			id='show_gallery_image'
			name='quotes-llama-settings[show_gallery_image]'
			<?php
			if ( $this->check_plugin_option( 'show_gallery_image' ) ) {
				echo 'checked';}
			?>
			>
		<label for='show_gallery_image'>
			<?php esc_html_e( 'Display images in the gallery.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - sidebar position textfield.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_sidebar_position_callback() {
		$allowed_html = $this->quotes_llama_allowed_html( 'option' );
		$sidebar      = $this->check_plugin_option( 'sidebar' );
		?>
		<select name='quotes-llama-settings[sidebar]' id='sidebar'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( 'left', esc_html__( 'Left', 'quotes-llama' ), $sidebar ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'right', esc_html__( 'Right', 'quotes-llama' ), $sidebar ), $allowed_html );
			?>
		</select>
		<label for='sidebar'>
			<?php echo ' ' . esc_html__( 'Align the sidebar.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - default orderby droplist.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_orderby_callback() {
		$allowed_html = $this->quotes_llama_allowed_html( 'option' );
		$default_sort = $this->check_plugin_option( 'default_sort' );
		?>
		<select name='quotes-llama-settings[default_sort]' id='default_sort'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( 'quote_id', esc_html__( 'ID', 'quotes-llama' ), $default_sort ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'quote', esc_html__( 'Quote', 'quotes-llama' ), $default_sort ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'last_name', esc_html__( 'Author', 'quotes-llama' ), $default_sort ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'source', esc_html__( 'Source', 'quotes-llama' ), $default_sort ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'category', esc_html__( 'Category', 'quotes-llama' ), $default_sort ), $allowed_html );
			?>
		</select>
		<label for='default_sort'>
			<?php echo ' ' . esc_html__( 'Sort by column.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - default order droplist.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_order_callback() {
		$allowed_html  = $this->quotes_llama_allowed_html( 'option' );
		$default_order = $this->check_plugin_option( 'default_order' );
		?>
		<select name='quotes-llama-settings[default_order]' id='default_order'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( 'asc', esc_html__( 'Asc', 'quotes-llama' ), $default_order ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'dsc', esc_html__( 'Dsc', 'quotes-llama' ), $default_order ), $allowed_html );
			?>
		</select>
		<label for='default_order'>
			<?php echo ' ' . esc_html__( 'Ascending/Descending.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - background color textfield.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_background_color_callback() {
		?>
		<input type='text'
			id='background_color'
			name='quotes-llama-settings[background_color]'
			value='<?php echo esc_attr( $this->check_plugin_option( 'background_color' ) ); ?>'
			size='5'>
		<label for='background_color'>
			<?php esc_html_e( 'Sets the background color for the quotes page index.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - foreground color textfield.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_foreground_color_callback() {
		?>
		<input type='text'
			id='foreground_color'
			name='quotes-llama-settings[foreground_color]'
			value='<?php echo esc_attr( $this->check_plugin_option( 'foreground_color' ) ); ?>'
			size='5'>
		<label for='foreground_color'>
			<?php esc_html_e( 'Sets the foreground color for the quotes page index.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - character limit for quotes display.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_character_limit_callback() {
		?>
		<input type='text'
			id='character_limit'
			name='quotes-llama-settings[character_limit]'
			value='<?php echo absint( esc_attr( $this->check_plugin_option( 'character_limit' ) ) ); ?>'
			size='5'>
		<label for='character_limit'>
			<?php esc_html_e( 'Limit quotes to # of characters. ( 0 = disable limit )', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - Next quote text.
	 *
	 * @since 2.0.3
	 * @access public
	 */
	public function admin_next_quote_text_callback() {
		?>
		<input type='text'
			id='next_quote_text'
			name='quotes-llama-settings[next_quote_text]'
			value='<?php echo esc_attr( $this->check_plugin_option( 'next_quote_text' ) ); ?>'
			size='50'>
		<label for='next_quote_text'>
			<?php esc_html_e( '"next quote" link text.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - ellipses text to display at end of character limit.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_ellipses_text_callback() {
		?>
		<input type='text'
			id='ellipses_text'
			name='quotes-llama-settings[ellipses_text]'
			value='<?php echo esc_attr( $this->check_plugin_option( 'ellipses_text' ) ); ?>'
			size='5'>
		<label for='ellipses_text'>
			<?php esc_html_e( 'Text that ends the quote limit.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - 'read more' text to display at end of limited quote.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_read_more_text_callback() {
		?>
		<input type='text'
			id='read_more_text'
			name='quotes-llama-settings[read_more_text]'
			value='<?php echo esc_attr( $this->check_plugin_option( 'read_more_text' ) ); ?>'
			size='5'>
		<label for='read_more_text'>
			<?php esc_html_e( 'The text to expand the quote.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - 'read less' text to display at end of limited quote.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_read_less_text_callback() {
		?>
		<input type='text'
			id='read_less_text'
			name='quotes-llama-settings[read_less_text]'
			value='<?php echo esc_attr( $this->check_plugin_option( 'read_less_text' ) ); ?>'
			size='5'>
		<label for='read_less_text'>
			<?php esc_html_e( 'The text to collapse the quote.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - Whether to display round image or not.
	 *
	 * @since 2.1.0
	 * @access public
	 */
	public function admin_border_radius_callback() {
		?>
		<input type='checkbox'
			id='border_radius'
			name='quotes-llama-settings[border_radius]'
			<?php
			if ( $this->check_plugin_option( 'border_radius' ) ) {
				echo 'checked';}
			?>
			>
		<label for='border_radius'>
			<span class='dashicons-before dashicons-edit'>
				<?php esc_html_e( 'Display round image in quotes.', 'quotes-llama' ); ?>
			</span>
		</label>
		<?php
	}

	/**
	 * Options tab - How to align the quote text.
	 *
	 * @since 2.1.0
	 * @access public
	 */
	public function admin_align_quote_callback() {
		$allowed_html = $this->quotes_llama_allowed_html( 'option' );
		$t            = $this->check_plugin_option( 'align_quote' );
		?>
		<select name='quotes-llama-settings[align_quote]' id='align_quote'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( 'left', 'Left', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'right', 'Right', $t ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'center', 'Center', $t ), $allowed_html );
			?>
		</select>
		<label for='align_quote'>
			<?php echo ' ' . esc_html__( 'Align the quote text.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - Whether to display images above the quote.
	 *
	 * @since 2.1.0
	 * @access public
	 */
	public function admin_image_at_top_callback() {
		?>
		<input type='checkbox'
			id='image_at_top'
			name='quotes-llama-settings[image_at_top]'
			<?php
			if ( $this->check_plugin_option( 'image_at_top' ) ) {
				echo 'checked';}
			?>
			>
		<label for='image_at_top'>
			<span class='dashicons-before dashicons-edit'>
				<?php esc_html_e( 'Display image centered above quotes.', 'quotes-llama' ); ?>
			</span>
		</label>
		<?php
	}

	/**
	 * Options tab - whether to display dashicon icons in quotes and sources.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_show_icons_callback() {
		?>
		<input type='checkbox'
			id='show_icons'
			name='quotes-llama-settings[show_icons]'
			<?php
			if ( $this->check_plugin_option( 'show_icons' ) ) {
				echo 'checked';}
			?>
			>
		<label for='show_icons'>
			<span class='dashicons-before dashicons-edit'>
				<?php esc_html_e( 'Display Icons in quotes.', 'quotes-llama' ); ?>
			</span>
		</label>
		<?php
	}

	/**
	 * Options tab - Source icon.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	public function admin_source_icon_callback() {
		$icon_set         = 'source';
		$icon_set_title   = 'Default source icon.';
		$icon_set_default = $this->check_plugin_option( 'source_icon' );
		echo '<input type="hidden" id="source_icon" name="quotes-llama-settings[source_icon]" value="' . esc_attr( $this->check_plugin_option( 'source_icon' ) ) . '">';
		$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
		echo wp_kses( require 'inc/dashicons/dash-icons.php', $allowed_html );
	}

	/**
	 * Options tab - Author icon.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	public function admin_author_icon_callback() {
		$icon_set         = 'author';
		$icon_set_title   = 'Default author icon.';
		$icon_set_default = $this->check_plugin_option( 'author_icon' );
		echo '<input type="hidden" id="author_icon" name="quotes-llama-settings[author_icon]" value="' . esc_attr( $this->check_plugin_option( 'author_icon' ) ) . '">';
		$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
		echo wp_kses( require 'inc/dashicons/dash-icons.php', $allowed_html );
	}

	/**
	 * Options tab - whether to display the search form to all visitors or just logged in.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_search_allow_callback() {
		?>
		<input type='checkbox'
			id='search_allow'
			name='quotes-llama-settings[search_allow]'
			<?php
			if ( $this->check_plugin_option( 'search_allow' ) ) {
				echo 'checked';}
			?>
			>
		<label for='search_allow'>
				<?php esc_html_e( 'Display the search form for all visitors.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab - show quote source on a new line instead of comma sepration drop list.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_source_newline_callback() {
		$allowed_html   = $this->quotes_llama_allowed_html( 'option' );
		$source_newline = $this->check_plugin_option( 'source_newline' );
		?>
		<select name='quotes-llama-settings[source_newline]' id='source_newline'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( 'comma', 'Comma [,]', $source_newline ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'br', 'New Line [br]', $source_newline ), $allowed_html );
			?>
		</select>
		<label for='source_newline'>
			<?php esc_html_e( 'Separate the author from the source with either a comma or new line.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab permission level required to manage plugin.
	 * Administrator or editor only.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_permission_level_callback() {
		$allowed_html     = $this->quotes_llama_allowed_html( 'option' );
		$permission_level = $this->check_plugin_option( 'permission_level' );
		?>
		<select name='quotes-llama-settings[permission_level]' id='permission_level'>
			<?php
			echo wp_kses( $this->quotes_llama_make_option( 'create_users', 'Administrators', $permission_level ), $allowed_html );
			echo wp_kses( $this->quotes_llama_make_option( 'edit_pages', 'Editors', $permission_level ), $allowed_html );
			?>
		</select>
		<label for='permission_level'>
			<?php echo ' ' . esc_html__( 'Set the role which has permission to manage this plugin.', 'quotes-llama' ); ?>
		</label>
		<?php
	}

	/**
	 * Options tab reset options checkbox in admin options.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_reset_callback() {
		?>
		<input type='checkbox'
			id='admin_reset'
			name='quotes-llama-settings[admin_reset]'
			<?php
			if ( $this->check_plugin_option( 'admin_reset' ) ) {
				echo 'checked'; }
			?>
			>
			<label for='admin_reset'>
				<?php esc_html_e( 'Reset plugin options to their defaults when deactivating this plugin.', 'quotes-llama' ); ?>
			</label>
			<?php
	}

	/**
	 * Options tab Export Delimiter.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_export_delimiter_callback() {
		?>
		<input type='text'
			id='export_delimiter'
			name='quotes-llama-settings[export_delimiter]'
			value='<?php echo esc_attr( $this->check_plugin_option( 'export_delimiter' ) ); ?>'
			size='3'>
			<label for='export_delimiter'>
				<?php
				esc_html_e( '.csv delimiter.', 'quotes-llama' );
				echo '<br>' . esc_html__( 'Field separator for importing and exporting quotes in .csv format.', 'quotes-llama' );
				?>
			</label>
			<?php
	}

	/**
	 * Options tab Whether to display http on make_clickable links.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_http_display_callback() {
		?>
		<input type='checkbox'
			id='http_display'
			name='quotes-llama-settings[http_display]'
			<?php
			if ( $this->check_plugin_option( 'http_display' ) ) {
				echo 'checked';}
			?>
			>
		<label for='http_display'>
			<span class='dashicons-before'>
				<?php esc_html_e( 'Display full URL (http) in text links... this does not apply to html links.', 'quotes-llama' ); ?>
			</span>
		</label>
		<?php
	}

	/**
	 * Options tab widget page link.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_widget_page_callback() {
		esc_html_e( 'Widget options are set in the', 'quotes-llama' );
		echo ' <a href="' . esc_url( get_bloginfo( 'wpurl' ) ) . '/wp-admin/widgets.php">';
		esc_html_e( 'widgets page', 'quotes-llama' );
		echo '</a>.';
	}

	/**
	 * Form to Add or edit a quote.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param int    $quote_id - id of quote to edit.
	 * @param string $return_page - Referer quote page.
	 *
	 * @return string - sanitized form to edit or add quote.
	 */
	private function quotes_form( $quote_id = 0, $return_page = '' ) {

		// Default values.
		$submit_value = __( 'Add Quote', 'quotes-llama' );
		$submit_type  = 'quotes_llama_add_quote';
		$form_name    = 'addquote';
		$action_url   = get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=quotes-llama#addnew';
		$quote        = '';
		$title_name   = '';
		$first_name   = '';
		$last_name    = '';
		$source       = '';
		$img_url      = '';
		$author_icon  = $this->check_plugin_option( 'author_icon' );
		$source_icon  = $this->check_plugin_option( 'source_icon' );
		$idcategory   = array();
		$hidden_input = '';
		$back         = '';

		// If there is an id, then editing a quote. Set quote values.
		if ( $quote_id ) {
			$form_name    = 'editquote';
			$quote_data   = $this->quotes_select( $quote_id, '' );
			$hidden_input = '<input type="hidden" name="quote_id" value="' . $quote_id . '" />';
			$submit_value = __( 'Save Quote', 'quotes-llama' );
			$submit_type  = 'quotes_llama_save_quote';
			$back         = '<input type="submit" name="submit" value="' . esc_html__( 'Back', 'quotes-llama' ) . '">&nbsp;';
			$action_url   = get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=quotes-llama' . $return_page;
			$quote        = isset( $quote_data['quote'] ) ? $quote_data['quote'] : '';
			$title_name   = isset( $quote_data['title_name'] ) ? $quote_data['title_name'] : '';
			$first_name   = isset( $quote_data['first_name'] ) ? $quote_data['first_name'] : '';
			$last_name    = isset( $quote_data['last_name'] ) ? $quote_data['last_name'] : '';
			$source       = isset( $quote_data['source'] ) ? $quote_data['source'] : '';
			$img_url      = isset( $quote_data['img_url'] ) ? $quote_data['img_url'] : '';
			$author_icon  = isset( $quote_data['author_icon'] ) ? $quote_data['author_icon'] : $this->check_plugin_option( 'author_icon' );
			$source_icon  = isset( $quote_data['source_icon'] ) ? $quote_data['source_icon'] : $this->check_plugin_option( 'source_icon' );
			$idcategory   = isset( $quote_data['category'] ) ? explode( ', ', $quote_data['category'] ) : array();
		} else {
			$quote_id = null;
		}

		// Get all categories in checkbox list format.
		$cat = $this->quotes_llama_get_categories( $idcategory );

		// Set field titles.
		$quote_label  = __( 'Quote', 'quotes-llama' );
		$title_label  = __( 'Title', 'quotes-llama' );
		$first_label  = __( 'First Name', 'quotes-llama' );
		$last_label   = __( 'Last Name', 'quotes-llama' );
		$source_label = __( 'Source', 'quotes-llama' );
		$imgurl_label = __( 'Image URL', 'quotes-llama' );
		$img_button   = '<button class="quotes-llama-media-button button button-large">Select image</button>';
		$cat_label    = __( 'Category', 'quotes-llama' );

		// Create our source icon selector droplist.
		$icon_set          = 'source';
		$icon_set_title    = 'Source icon.';
		$icon_set_default  = $source_icon;
		$source_icon_html  = '<input type="hidden" id="source_icon" name="source_icon" value="' . esc_attr( $source_icon ) . '">';
		$source_icon_html .= require 'inc/dashicons/dash-icons.php';

		// Create our author icon selector droplist.
		$icon_set          = 'author';
		$icon_set_title    = 'Author icon.';
		$icon_set_default  = $author_icon;
		$author_icon_html  = '<input type="hidden" id="author_icon" name="author_icon" value="' . esc_attr( $author_icon ) . '">';
		$author_icon_html .= require 'inc/dashicons/dash-icons.php';

		// Create nonce.
		$nonce = wp_nonce_field( 'quotes_llama_form_nonce', 'quotes_llama_form_nonce' );

		// Create the form.
		$quotes_edit_add                  = '<form name=' . $form_name .
			' method="post"
			action="' . esc_url( $action_url ) . '">
			<input type="hidden" name="quote_id" value="' . absint( $quote_id ) . '">' . $nonce .
			'<table class="form-table" cellpadding="5" cellspacing="2" width="100%">
				<tbody>
					<tr class="form-field form-required">
						<th style="text-align:left;"
							scope="row"
							valign="top">
							<label for="quotes_llama_quote">' . esc_html( $quote_label ) .
							'</label>
						</th>
						<td>
							<textarea id="quotes_llama_quote"
								name="quote"
								rows="5"
								cols="50"
								style="width: 97%;">' . esc_html( $quote ) . '</textarea>
						</td>
					</tr>
					<tr>
						
					</tr>
					<tr class="form-field">
						<th style="text-align:left;"
							scope="row"
							valign="top">
							<label for="quotes-llama-widget-title">' . esc_html( $title_label ) .
							'</label>
						</th>
						<td>
							<input type="text"
								id="quotes-llama-widget-title"
								name="title_name"
								size="15"
								value="' . wp_kses_post( $title_name ) .
								'" placeholder="optional">
						</td>
					</tr>
					<tr class="form-field">
						<th style="text-align:left;"
							scope="row"
							valign="top">
							<label for="quotes-llama-widget-author">' . esc_html( $first_label ) .
							'</label>
						</th>
						<td><input type="text"
								id="quotes-llama-widget-author"
								name="first_name"
								size="40"
								value="' . wp_kses_post( $first_name ) .
								'" placeholder="optional">
						</td>
					</tr>
					<tr class="form-field">
						<th style="text-align:left;"
							scope="row"
							valign="top">
							<label for="quotes-llama-widget-author">' . esc_html( $last_label ) .
							'</label>
						</th>
						<td>
							<input type="text"
								id="quotes-llama-widget-author"
								name="last_name"
								size="40"
								value="' . wp_kses_post( $last_name ) .
								'" placeholder="optional">
						</td>
					</tr>
					<tr>
						<th style="text-align:right;"
							scope="row"
							valign="top">
						</th>
						<td>';
						$quotes_edit_add .= $author_icon_html;
						$quotes_edit_add .= '</td>
					</tr>
					<tr class="form-field">
						<th style="text-align:left;"
							scope="row"
							valign="top">
							<label for="quotes_llama_source">' . esc_html( $source_label ) .
							'</label>
						</th>
						<td><input type="text"
								id="quotes_llama_source"
								name="source"
								size="40"
								value="' . esc_html( $source ) .
								'" placeholder="optional">
						</td>
					</tr>
					<tr>
						<th style="text-align:right;"
							scope="row"
							valign="top">
						</th>
						<td>';
						$quotes_edit_add .= $source_icon_html;
						$quotes_edit_add .= '</td>
					</tr>
					<tr class="form-field">
						<th style="text-align:left;"
							scope="row"
							valign="top">
							<label for="ql_category">' . esc_html( $cat_label ) .
							'</label>
						</th>
						<td id="ql-cat">' .
							$cat .
						'</td>
					</tr>
					<tr class="form-field">
						<th style="text-align:left;"
							scope="row"
							valign="top">
							<label for="quotes_llama_imgurl">' . esc_html( $imgurl_label ) .
							'</label>
						</th>
						<td>
							<input type="text"
								id="quotes_llama_imgurl"
								name="img_url"
								size="40"
								value="' . esc_url( $img_url ) .
								'" placeholder="optional">' . wp_kses_post( $img_button ) .
						'</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">' . wp_kses_post( $back ) .
				'<input name="' . esc_html( $submit_type ) . '"
					value="' . esc_html( $submit_value ) . '"
					type="submit"
					class="button button-primary">
			</p>
		</form>';
		return $quotes_edit_add;
	}

	/**
	 * Insert a quote.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $quote required - The text to be quoted.
	 * @param string $title_name     - The authors title.
	 * @param string $first_name     - Authors first and middle name.
	 * @param string $last_name      - Authors last name.
	 * @param string $source         - The source text.
	 * @param string $img_url        - The url to an image file.
	 * @param string $author_icon    - The author icon.
	 * @param string $source_icon    - The source icon.
	 * @param string $category       - Category.
	 *
	 * @return string - Message of result.
	 */
	private function quotes_insert( $quote, $title_name = '', $first_name = '', $last_name = '', $source = '', $img_url = '', $author_icon = '', $source_icon = '', $category = '' ) {
		global $allowedposttags;
		global $wpdb;

		if ( ! $quote ) {
			return $this->message( __( 'Transaction failed: There was no quote to add to the database.', 'quotes-llama' ), 'nay' );
		}

		$varget = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'quotes_llama' ) ); // phpcs:ignore

		if ( $varget !== $wpdb->prefix . 'quotes_llama' ) {
			return $this->message( esc_html__( 'Transaction failed: Database table not found!', 'quotes-llama' ), 'nay' );
		} else {
			$results = $wpdb->insert( // phpcs:ignore
				$wpdb->prefix . 'quotes_llama',
				array(
					'quote'       => $quote,
					'title_name'  => $title_name,
					'first_name'  => $first_name,
					'last_name'   => $last_name,
					'source'      => $source,
					'img_url'     => $img_url,
					'author_icon' => $author_icon,
					'source_icon' => $source_icon,
					'category'    => $category,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $results ) {
				return $this->message( esc_html__( 'Transaction failed: An error occurred in the MySQL query.', 'quotes-llama' ) . ' - ' . $results, 'nay' );
			} else {
				return $this->message( esc_html__( 'Transaction completed: Quote Added', 'quotes-llama' ), 'yay' );
			}
		}
	}

	/**
	 * Update a quote.
	 * Check for quote.
	 * Check that table exists.
	 * Update.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $quote_id required - The id of the quote in the database.
	 * @param string $quote required    - The text to be quoted.
	 * @param string $title_name        - The authors title.
	 * @param string $first_name        - Authors first and middle name.
	 * @param string $last_name         - Authors last name.
	 * @param string $source            - The source text.
	 * @param string $img_url           - The url to an image file.
	 * @param string $author_icon       - The author icon.
	 * @param string $source_icon       - The source icon.
	 * @param string $category          - Category.
	 *
	 * @return string - Message of success or failure.
	 */
	private function quotes_update( $quote_id, $quote, $title_name = '', $first_name = '', $last_name = '', $source = '', $img_url = '', $author_icon = '', $source_icon = '', $category = '' ) {
		global $allowedposttags;
		global $wpdb;

		if ( ! $quote ) {
			return $this->message( esc_html__( 'Transaction failed: There is no quote.', 'quotes-llama' ), 'nay' );
		}

		$varget = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'quotes_llama' ) ); // phpcs:ignore

		if ( $varget !== $wpdb->prefix . 'quotes_llama' ) {
			return $this->message( esc_html__( 'Transaction failed: Quotes llama database table not found', 'quotes-llama' ), 'nay' );
		} else {
			$results = $wpdb->update( // phpcs:ignore
				$wpdb->prefix . 'quotes_llama',
				array(
					'quote'       => $quote,
					'title_name'  => $title_name,
					'first_name'  => $first_name,
					'last_name'   => $last_name,
					'source'      => $source,
					'img_url'     => $img_url,
					'author_icon' => $author_icon,
					'source_icon' => $source_icon,
					'category'    => $category,
				),
				array( 'quote_id' => $quote_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $results ) {
				return $this->message( esc_html__( 'Transaction failed: There was an error in this MySQL query.', 'quotes-llama' ) . ' - ' . $results, 'nay' );
			} else {
				return $this->message( esc_html__( 'Transaction completed: Quote Saved', 'quotes-llama' ), 'yay' );
			}
		}
	}

	/**
	 * Delete a single quote.
	 * Check for quote.
	 * Sanitize quote id.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param int $quote_id - The quote_id of the quote to delete in the database.
	 *
	 * @return string - Message of result. y=success, n=failure.
	 */
	private function quotes_delete( $quote_id ) {
		if ( $quote_id ) {
			global $wpdb;
			$id     = sanitize_text_field( $quote_id );
			$result = $wpdb->query( $wpdb->prepare( 'DELETE FROM `%1s` WHERE quote_id = %d', $wpdb->prefix . 'quotes_llama', $id ) ); // phpcs:ignore

			if ( false === $result ) {
				return 'n';
			} else {
				return 'y';
			}
		} else {
			return 'n';
		}
	}

	/**
	 * Bulk delete quotes.
	 * Check for quotes ids.
	 * Validate ids to be int.
	 * Count the number of checkboxes.
	 * Create a placeholders array for prepare statment.
	 * String with the number of %s holders needed for checkboxes.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param Array $quote_ids - Array of ids to delete.
	 *
	 * @return string message of result. Success count, n=failure, u=nothing selected.
	 */
	private function quotes_delete_bulk( $quote_ids ) {
		if ( $quote_ids ) {
			global $wpdb;

			foreach ( $quote_ids as &$value ) {
				$value = absint( $value );
			}

			$id_count     = count( $quote_ids );
			$holder_count = array_fill( 0, $id_count, '%s' );
			$percent_s    = '( ' . implode( ', ', $holder_count ) . ' )';
			$result       = $wpdb->query( // phpcs:ignore
				$wpdb->prepare(
					'DELETE FROM ' .
					$wpdb->prefix .
					'quotes_llama WHERE quote_id IN ' .
					$percent_s, // phpcs:ignore
					$quote_ids
				)
			);

			if ( $result ) {
				return $id_count;
			} else {
				return 'n';
			}
		} else {
			return 'u';
		}
	}

	/**
	 * Get quotes for widget, gallery, page, search, and random requests.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int    $quote_id - Id of quote.
	 * @param string $cat      - Category.
	 * @param int    $qlcount  - How many quotes.
	 *
	 * @return result array.
	 */
	public function quotes_select( $quote_id = 0, $cat = '', $qlcount = 1 ) {
		global $wpdb;

		// Check that database table exists.
		//if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "quotes_llama'" ) === $wpdb->prefix . 'quotes_llama' ) { // phpcs:ignore
		//	$this->msg = $this->message( 'Database table found!', 'yay' );
		//} else {
		//	$this->msg = $this->message( 'Transaction failed: Database table cannot be found!', 'nay' );
		//	return '';
		//}

		// Get quote by id.
		if ( is_numeric( $quote_id ) && $quote_id > 0 ) {
			$quote_data = $wpdb->get_row( // phpcs:ignore
				$wpdb->prepare(
					'SELECT
					quote_id,
					quote,
					title_name,
					first_name,
					last_name,
					source,
					img_url,
					author_icon,
					source_icon,
					category FROM ' . $wpdb->prefix . 'quotes_llama' .
					' WHERE quote_id = %d',
					$quote_id
				),
				ARRAY_A
			);

			// Set default icons if none. This is for backwards compatibility.
			if ( empty( $quote_data['author_icon'] ) ) {
				$quote_data['author_icon'] = $this->check_plugin_option( 'author_icon' );
			}

			if ( empty( $quote_data['source_icon'] ) ) {
				$quote_data['source_icon'] = $this->check_plugin_option( 'source_icon' );
			}

			return $quote_data;
		}

		// Page, Get authors first, last name for sidebar author list.
		if ( 'author_list' === $quote_id ) {
			if ( $cat ) {

				// Category string to array.
				$cats = explode( ', ', $cat );

				// Begin building query string.
				$cat_query = 'SELECT
					title_name,
					first_name,
					last_name,
					count(first_name) AS quotecount FROM ' . $wpdb->prefix . 'quotes_llama WHERE (';

				// Setup each category placeholder and its value.
				foreach ( $cats as $categ ) {
					$cat_query   .= 'category LIKE %s OR ';
					$cat_values[] = '%' . $categ . '%';
				}

				// Strip trailing OR from query string.
				$cat_query = substr( $cat_query, 0, -4 );

				// Finish building query string.
				$cat_query .= ') GROUP BY title_name, last_name, first_name ORDER BY last_name';

				$authors = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						$cat_query, // phpcs:ignore
						$cat_values
					)
				);

				return $authors;
			}

			$authors = $wpdb->get_results( // phpcs:ignore
				'SELECT
				title_name,
				first_name,
				last_name,
				count(first_name) AS quotecount FROM ' . $wpdb->prefix . 'quotes_llama' .
				' GROUP BY title_name, last_name,
				first_name ORDER BY last_name'
			);
			return $authors;
		}

		// All categories list.
		if ( 'categories' === $quote_id ) {
			$categories = $wpdb->get_results( // phpcs:ignore
				'SELECT category FROM ' . $wpdb->prefix . 'quotes_llama' .
				' GROUP BY category'
			);
			return $categories;
		}

		// Quotes from selected categories.
		if ( $cat ) {

			// Category string to array.
			$cats = explode( ', ', $cat );

			// Begin building query string.
			$cat_query = 'SELECT
					quote,
					title_name,
					first_name,
					last_name,
					source,
					img_url,
					author_icon,
					source_icon,
					category FROM ' . $wpdb->prefix . 'quotes_llama WHERE (';

			// Setup each category placeholder and its value.
			foreach ( $cats as $categ ) {
				$cat_query   .= 'category LIKE %s OR ';
				$cat_values[] = '%' . $categ . '%';
			}

			// Strip trailing OR from query string.
			$cat_query = substr( $cat_query, 0, -4 );

			// How many quotes to get? %d.
			$cat_values[] = $qlcount;

			// Finish building query string.
			$cat_query .= ') ORDER BY RAND() LIMIT %d';

			$categories = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					$cat_query, // phpcs:ignore
					$cat_values
				),
				ARRAY_A
			);

			return $categories;
		}

		// Widget and Gallery, get random quote from all or category for .ajax request.
		if ( 'quotes_llama_random' === $quote_id || isset( $_POST['quotes_llama_random'] ) ) {
			$category = isset( $_REQUEST['quotes_llama_category'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['quotes_llama_category'] ) ) : '';

			// Use $_POST before $cat.
			$thiscat = isset( $category ) ? $category : $cat;

			// If getting several quotes.
			if ( $thiscat && $qlcount > 1 ) {

				// Category string to array.
				$cats = explode( ', ', $thiscat );

				// Begin building query string.
				$cat_query = 'SELECT
						quote,
						title_name,
						first_name,
						last_name,
						source,
						img_url,
						author_icon,
						source_icon,
						category FROM ' . $wpdb->prefix . 'quotes_llama WHERE (';

				// Setup each category placeholder and its value.
				foreach ( $cats as $categ ) {
					$cat_query   .= 'category LIKE %s OR ';
					$cat_values[] = '%' . $categ . '%';
				}

				// Strip trailing OR from query string.
				$cat_query = substr( $cat_query, 0, -4 );

				// How many quotes to get? %d.
				$cat_values[] = $qlcount;

				// Finish building query string.
				$cat_query .= ') ORDER BY RAND() LIMIT %d';

				$rand_data = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						$cat_query, // phpcs:ignore
						$cat_values
					),
					ARRAY_A
				);
			} elseif ( $qlcount > 1 ) {
				$rand_data = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT
						quote,
						title_name,
						first_name,
						last_name,
						source,
						img_url,
						author_icon,
						source_icon,
						category FROM ' . $wpdb->prefix . 'quotes_llama' .
						' ORDER BY RAND() LIMIT %d',
						$qlcount
					),
					ARRAY_A
				);
			}

			// If just a single quote.
			if ( $thiscat && 1 === $qlcount ) {

				// Category string to array.
				$cats = explode( ', ', $thiscat );

				// Begin building query string.
				$cat_query = 'SELECT
						quote,
						title_name,
						first_name,
						last_name,
						source,
						img_url,
						author_icon,
						source_icon,
						category FROM ' . $wpdb->prefix . 'quotes_llama WHERE (';

				// Setup each category placeholder and its value.
				foreach ( $cats as $categ ) {
					$cat_query   .= 'category LIKE %s OR ';
					$cat_values[] = '%' . $categ . '%';
				}

				// Strip trailing OR from query string.
				$cat_query = substr( $cat_query, 0, -4 );

				// Finish building query string.
				$cat_query .= ') ORDER BY RAND() LIMIT 1';

				$rand_data = $wpdb->get_row( // phpcs:ignore
					$wpdb->prepare(
						$cat_query, // phpcs:ignore
						$cat_values
					),
					ARRAY_A
				);
			} elseif ( 1 === $qlcount ) {
				$rand_data = $wpdb->get_row( // phpcs:ignore
					'SELECT
					quote,
					title_name,
					first_name,
					last_name,
					source,
					img_url,
					author_icon,
					source_icon,
					category FROM ' . $wpdb->prefix . 'quotes_llama' .
					' ORDER BY RAND() LIMIT 1',
					ARRAY_A
				);
			}

			// Set default icons if none. This is for backwards compatibility.
			if ( empty( $rand_data['author_icon'] ) ) {
				$rand_data['author_icon'] = $this->check_plugin_option( 'author_icon' );
			}

			if ( empty( $rand_data['source_icon'] ) ) {
				$rand_data['source_icon'] = $this->check_plugin_option( 'source_icon' );
			}

			// Make quote and source clickable before sending to ajax.
			if ( $rand_data ) {
					$rand_data['quote']  = isset( $rand_data['quote'] ) ? trim( $this->quotes_llama_clickable( $rand_data['quote'] ) ) : '';
					$rand_data['source'] = isset( $rand_data['source'] ) ? trim( $this->quotes_llama_clickable( $rand_data['source'] ) ) : '';
			}

			// If a quote for gallery.
			if ( isset( $_POST['quotes_llama_random'] ) ) {
				echo wp_json_encode( $rand_data );
				die();
			}

			// If just a random quote for sidebar.
			if ( 'quotes_llama_random' === $quote_id ) {
				return $rand_data;
			}
		}

		// Page, get all quotes for a author.
		if ( isset( $_POST['author'] ) ) {
			$nonce     = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			$san_title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$san_first = isset( $_POST['first'] ) ? sanitize_text_field( wp_unslash( $_POST['first'] ) ) : '';
			$san_last  = isset( $_POST['last'] ) ? sanitize_text_field( wp_unslash( $_POST['last'] ) ) : '';
			if ( wp_verify_nonce( $nonce, 'quotes_llama_nonce' ) ) {
				if ( '' !== $san_title ) {
					$title = $san_title;
				} else {
					$title = false;
				}

				if ( '' !== $san_first ) {
					$first = $san_first;
				} else {
					$first = false;
				}

				if ( '' !== $san_last ) {
					$last = $san_last;
				} else {
					$last = false;
				}

				// If title, first and last name.
				if ( $first && $last && $title ) {
					$quotes = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT
							quote,
							title_name,
							first_name,
							last_name,
							source,
							img_url,
							author_icon,
							source_icon,
							category FROM ' . $wpdb->prefix . 'quotes_llama' .
							' WHERE title_name = %s AND first_name = %s AND last_name = %s ORDER BY last_name, first_name, quote',
							$title,
							$first,
							$last
						)
					);

					// If title and first name only.
				} elseif ( $first && empty( $last ) && $title ) {
					$quotes = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT
							quote,
							title_name,
							first_name,
							last_name,
							source,
							img_url,
							author_icon,
							source_icon,
							category FROM ' . $wpdb->prefix . 'quotes_llama' .
							' WHERE title_name = %s AND first_name = %s ORDER BY first_name, quote',
							$title,
							$first
						)
					);

					// If title and last name only.
				} elseif ( empty( $first ) && $last && $title ) {
					$quotes = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT
							quote,
							title_name,
							first_name,
							last_name,
							source,
							img_url,
							author_icon,
							source_icon,
							category FROM ' . $wpdb->prefix . 'quotes_llama' .
							' WHERE title_name = %s AND last_name = %s ORDER BY last_name, quote',
							$title,
							$last
						)
					);

					// If first and last with no title.
				} elseif ( $first && $last ) {
					$quotes = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT
							quote,
							title_name,
							first_name,
							last_name,
							source,
							img_url,
							author_icon,
							source_icon,
							category FROM ' . $wpdb->prefix . 'quotes_llama' .
							' WHERE (title_name IS NULL OR title_name = " ") AND first_name = %s AND last_name = %s ORDER BY last_name, first_name, quote',
							$first,
							$last
						)
					);

					// If first with no last or title.
				} elseif ( $first && empty( $last ) ) {
					$quotes = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT
							quote,
							title_name,
							first_name,
							last_name,
							source,
							img_url,
							author_icon,
							source_icon,
							category FROM ' . $wpdb->prefix . 'quotes_llama' .
							' WHERE (title_name IS NULL OR title_name = " ") AND first_name = %s ORDER BY first_name, quote',
							$first
						)
					);

					// If last with no first or title.
				} elseif ( empty( $first ) && $last ) {
					$quotes = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT
							quote,
							title_name,
							first_name,
							last_name,
							source,
							img_url,
							author_icon,
							source_icon,
							category FROM ' . $wpdb->prefix . 'quotes_llama' .
							' WHERE (title_name IS NULL OR title_name = " ") AND last_name = %s ORDER BY last_name, quote',
							$last
						)
					);
				}

				// Array of allowed html.
				$allowed_html = $this->quotes_llama_allowed_html( 'quote' );
				echo wp_kses( $this->template_page_author( $quotes ), $allowed_html );
				die();
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}

		// Page, Search for quote.
		if ( isset( $_POST['search_for_quote'] ) ) {
			$nonce         = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			$term          = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
			$search_column = isset( $_POST['sc'] ) ? sanitize_text_field( wp_unslash( $_POST['sc'] ) ) : 'quote';

			if ( wp_verify_nonce( $nonce, 'quotes_llama_nonce' ) ) {
				$like   = '%' . $wpdb->esc_like( $term ) . '%';
				$quotes = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT
						quote,
						title_name,
						first_name,
						last_name,
						source,
						img_url,
						author_icon,
						source_icon,
						category FROM ' . $wpdb->prefix . 'quotes_llama' .
						' WHERE %1s LIKE %s' .  // phpcs:ignore
						'ORDER BY title_name, last_name, first_name, quote',
						$search_column,
						$like
					)
				);

				$this->template_page_search( $quotes );
				die();
			} else {
				$this->msg = $this->message( '', 'nonce' );
			}
		}
	}

	/**
	 * Category bulk actions - Delete/Rename a category.
	 *
	 * @since 2.0.5
	 * @access private
	 *
	 * @param string $category    - Category.
	 * @param string $mode        - delete or rename.
	 * @param string $cat_old     - Old category.
	 *
	 * @return string - Message of success or failure.
	 */
	private function category_bulk_actions( $category, $mode, $cat_old = null ) {
		global $wpdb;
		$result  = false;
		$results = 0;

		// Categories list to delete.
		if ( 'delete' === $mode ) {
			$like = '%' . $wpdb->esc_like( $category ) . '%';
		}

		// Categories list to rename.
		if ( 'rename' === $mode ) {
			$like = '%' . $wpdb->esc_like( $cat_old ) . '%';
		}

		$cats = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT
				quote_id,
				category FROM ' . $wpdb->prefix . 'quotes_llama' .
				' WHERE category LIKE %s', // phpcs:ignore
				$like
			)
		);

		// Unset/Replace category from each quote that it exists in.
		if ( isset( $cats ) ) {
			foreach ( $cats as $categ ) {

				// Turn .csv string into array.
				$categories = explode( ', ', $categ->category );

				// If deleting category.
				if ( 'delete' === $mode ) {
					$cat = array_search( $category, $categories, true );

					// Unset instance if exists.
					if ( false !== $cat ) {
						unset( $categories[ $cat ] );
					}
				}

				// If renaming category.
				if ( 'rename' === $mode ) {
					$cat = array_search( $cat_old, $categories, true );

					// Replace instance if exists.
					if ( false !== $cat ) {
						$categories[ $cat ] = $category;
					}
				}

				// Turn array back into .csv string.
				$new_cats = implode( ', ', $categories );

				// Update.
				$result = $wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'quotes_llama',
					array( 'category' => $new_cats ),
					array( 'quote_id' => $categ->quote_id ),
					'%s',
					'%d'
				);

				// Update total count.
				$results = $results + $result;
			}
		}

		if ( false === $result ) {
			return $this->message( esc_html__( 'Transaction failed:', 'quotes-llama' ) . ' ' . $mode . ' of ' . $results . ' records (' . $category . ') - ' . $results, 'nay' );
		} else {
			return $this->message( esc_html__( 'Transaction completed:', 'quotes-llama' ) . ' ' . $mode . ' of ' . $results . ' records (' . $category . ')', 'yay' );
		}
	}

	/**
	 * Check if an option isset.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $option - the option to check on.
	 *
	 * @return mixed - false if no option or option string if found.
	 */
	protected function check_plugin_option( $option ) {
		if ( ! $option ) {
			return false;
		}

		if ( isset( $this->quotes_llama_plugin_options[ "$option" ] ) ) {
			return $this->quotes_llama_plugin_options[ "$option" ];
		} else {
			return false;
		}
	}

	/**
	 * [quotes-llama mode='page']
	 * [quotes-llama mode='page' cat='cat']
	 * Renders page view. Lists all the authors and search form.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $nonce - Nonce.
	 * @param string $cat   - Category.
	 *
	 * @return String - HTML String.
	 */
	private function template_page( $nonce = '', $cat = '' ) {
		global $wpdb;

		// Enqueue conditional css.
		$this->quotes_llama_css_conditionals();

		// Page css.
		wp_enqueue_style( 'quotes-llama-css-page' );

		// Uses Ajax.
		wp_enqueue_script( 'quotesllamaAjax' );

		$search_allow           = $this->check_plugin_option( 'search_allow' );
		$nonce                  = wp_create_nonce( 'quotes_llama_nonce' );
		$default_sort           = isset( $this->quotes_llama_plugin_options['default_sort'] ) ? $this->quotes_llama_plugin_options['default_sort'] : 'quote_id';
		$template_page_loggedin = '';

		// Display search form for all visitors if enabled in options.
		if ( isset( $nonce ) && wp_verify_nonce( $nonce, 'quotes_llama_nonce' ) ) {
			if ( is_user_logged_in() || $search_allow ) {
				$search_text                    = esc_html__( 'Search', 'quotes-llama' );
				$search_column_quote            = '';
				$search_column_quote_title      = esc_html__( 'Quote', 'quotes-llama' );
				$search_column_title_name       = '';
				$search_column_title_name_title = esc_html__( 'Title', 'quotes-llama' );
				$search_column_first_name       = '';
				$search_column_first_name_title = esc_html__( 'First Name', 'quotes-llama' );
				$search_column_last_name        = '';
				$search_column_last_name_title  = esc_html__( 'Last Name', 'quotes-llama' );
				$search_column_source           = '';
				$search_column_source_title     = esc_html__( 'Source', 'quotes-llama' );
				$search_column_category         = '';
				$search_column_category_title   = esc_html__( 'Category', 'quotes-llama' );

				if ( isset( $_GET['sc'] ) ) {
					switch ( $_GET['sc'] ) {
						case 'quote':
							$search_column_quote = ' selected';
							break;
						case 'title_name':
							$search_column_title_name = ' selected';
							break;
						case 'first_name':
							$search_column_first_name = ' selected';
							break;
						case 'last_name':
							$search_column_last_name = ' selected';
							break;
						case 'source':
							$search_column_source = ' selected';
							break;
						case 'category':
							$search_column_category = ' selected';
							break;
						default:
							$search_column_quote = ' selected';
					}
				}
				$template_page_loggedin = '<div class="quotes-llama-page-quotes-form">' .
					'<form onsubmit="return false;" method="post">' .
						'<input type="text" ' .
							'class="quotes-llama-page-quotesearch" ' .
							'id="quotes-llama-page-quotesearch" ' .
							'name="quotes-llama-page-quotesearch" ' .
							'nonce="' . $nonce . '" ' .
							'size="20">' .
						'<br><select name="sc" class="sc">' .
							'<option value="quote">' .
								esc_html( $search_column_quote_title ) .
							'</option>' .
							'<option value="title_name">' .
								esc_html( $search_column_title_name_title ) .
							'</option>' .
							'<option value="first_name">' .
								esc_html( $search_column_first_name_title ) .
							'</option>' .
							'<option value="last_name">' .
								esc_html( $search_column_last_name_title ) .
							'</option>' .
							'<option value="source">' .
								wp_kses_post( $search_column_source_title ) .
							'</option>' .
							'<option value="category">' .
								esc_html( $search_column_category_title ) .
							'</option>' .
						'</select>' .
						'<button ' .
							'class="quotes-llama-page-searchbutton" ' .
							'id="quotes-llama-page-searchbutton" ' .
							'name="quotes-llama-page-searchbutton" ' .
							'size="30" type="submit">' .
								esc_html( $search_text ) .
						'</button>' .
					'</form>' .
				'</div>';
			}

			// List of all authors for page selection or just a category.
			if ( $cat ) {
				$quotesresult = $this->quotes_select( 'author_list', $cat );
			} else {
				$quotesresult = $this->quotes_select( 'author_list', '' );
			}

			// Array of authors title, first and last names.
			$initials = array();

			// A local id for each author used in next/prev buttons.
			$local_id = 0;

			// Array of alphabet letter links.
			$header_letter_list = '';

			// Array of authors name links.
			$author_link_list = '';

			// Page title.
			$quotes_title = esc_html__( 'Quotes', 'quotes-llama' );

			// Current Authors initial.
			$current_letter = '';

			// Get a random quote.
			if ( $cat ) {
				$initial_quote = do_shortcode( '[quotes-llama cat="' . $cat . '"]' );
			} else {
				$initial_quote = do_shortcode( '[quotes-llama]' );
			}

			// Iteration indicator for adding letter separator.
			$current_quote_data = '';

			// Check we have some quote data.
			if ( $quotesresult ) {
				foreach ( $quotesresult as $quoteresult ) {
					$countofquote = $quoteresult->quotecount; // Total number of quotes.
					$title_name   = trim( $quoteresult->title_name ); // Title.
					$first_name   = trim( $quoteresult->first_name ); // First and middle name.
					$last_name    = trim( $quoteresult->last_name ); // Last name.
					$name_shift   = false; // If just first name.

					if ( $last_name ) { // Does this author have last name.
						$name_index = strtoupper( substr( $last_name, 0, 1 ) );
					} else { // Prepare for sorting.
						if ( $first_name ) { // If last_name is empty then assign first to last so.
							$last_name  = $first_name; // It will sort into last names.
							$first_name = '';
							$name_index = strtoupper( substr( $last_name, 0, 1 ) );
							$name_shift = true;
						} else {
							$name_index = '';
						}
					}

					$initials[] = array(
						'index'      => $name_index,
						'last'       => $last_name,
						'count'      => $countofquote,
						'first'      => $first_name,
						'title_name' => $title_name,
						'name_shift' => $name_shift,
					);
				}

				// Get our columns to sort on.
				$last_lowercase = array_map( 'strtolower', array_column( $initials, 'last' ) );

				// Lower case to prevent case sensitivity when sorting.
				$first_lowercase = array_map( 'strtolower', array_column( $initials, 'first' ) );

				// Sort. Add $initals as the last parameter, to sort by the common key.
				array_multisort( $last_lowercase, SORT_ASC, SORT_NATURAL, $first_lowercase, SORT_ASC, SORT_NATURAL, $initials );

				// Undo our prepare for sorting above.
				foreach ( $initials as &$quote ) {

					// If first name is empty.
					if ( ! $quote['first'] ) {

						// But has last name so check name_shift.
						if ( $quote['last'] ) {

							// If shifted, update first so it will link correctly.
							if ( $quote['name_shift'] ) {
								$quote['first'] = $quote['last'];
								$quote['last']  = '';
							}
						}
					}
				}

				// Build string of letter links from index array. NAVIGATION, Next, Prev.
				$header_letter_list = '<div class="quotes-llama-page-navdiv">' .
					'<button class="quotes-llama-page-previous dashicons-before dashicons-arrow-left-alt" ' .
						'title="' . esc_attr__( 'Previous Author', 'quotes-llama' ) . '"></button>' .
					'<button class="quotes-llama-page-next dashicons-before dashicons-arrow-right-alt" ' .
						'title="' . esc_attr__( 'Next Author', 'quotes-llama' ) . '"></button></div>';

				foreach ( $initials as $letter ) {
					if ( $current_letter !== $letter['index'] ) {
						$header_letter_list .= '<a href="#' . esc_html( $letter['index'] ) . '"><button>' . esc_html( $letter['index'] ) . '</button></a>';
						$current_letter      = $letter['index'];
					}
				}

				// Build string of author links from index array.
				foreach ( $initials as $quote_author ) {

					// Add comma into title for echoing below.
					if ( $quote_author['title_name'] ) {
						$title_name = ', ' . $quote_author['title_name'];
					} else {
						$title_name = '';
					}

					if ( $current_quote_data === $quote_author['index'] ) {

						// Add just the author if separator already added.
						$author_link_list .= '<span class="quotes-llama-page-fixed-anchor" id="' . esc_attr( trim( $quote_author['title_name'] . ' ' . $quote_author['first'] . ' ' . $quote_author['last'] ) ) . '"></span>' .
							'<li>' .
								'<a class="quotes-llama-page-link" ' .
									'title-name="' . esc_attr( $quote_author['title_name'] ) . '" ' .
									'first="' . esc_attr( $quote_author['first'] ) . '" ' .
									'last="' . esc_attr( $quote_author['last'] ) . '" ' .
									'localID="' . esc_attr( $local_id ) . '" ' .
									'nonce="' . $nonce . '" ' .
									'href="#' . esc_attr( trim( $quote_author['title_name'] . ' ' . $quote_author['first'] . ' ' . $quote_author['last'] ) ) . '" ' .
									'title="' . esc_attr__( 'See all quotes from', 'quotes-llama' ) . ' ' . esc_attr( trim( $quote_author['title_name'] . ' ' . $quote_author['first'] . ' ' . $quote_author['last'] ) ) . '">';

						// If first and last name, or just first.
						if ( $quote_author['last'] ) {
							$author_link_list .= wp_kses_post( $this->quotes_llama_clickable( trim( $quote_author['last'] . ', ' . $quote_author['first'] . $title_name ) ) );
						} else {
							$author_link_list .= wp_kses_post( $this->quotes_llama_clickable( trim( $quote_author['first'] . $title_name ) ) );
						}

						$author_link_list .= '</a></li>';

						// Local id for next author.
						$local_id++;
					} else {

						// Add letter to sidebar separator and add author.
						$author_link_list .= '<div class="quotes-llama-page-letter">' .
								'<a name="' . esc_attr( $quote_author['index'] ) . '">' .
									esc_html( $quote_author['index'] ) .
								'</a>' .
							'</div>' .
							'<span class="quotes-llama-page-fixed-anchor" id="' . esc_attr( trim( $quote_author['title_name'] . ' ' . $quote_author['first'] . ' ' . $quote_author['last'] ) ) . '"></span>' .
							'<li>' .
								'<a class="quotes-llama-page-link" ' .
									'title-name="' . esc_attr( $quote_author['title_name'] ) . '" ' .
									'first="' . esc_attr( $quote_author['first'] ) . '" ' .
									'last="' . esc_attr( $quote_author['last'] ) . '" ' .
									'localID="' . esc_attr( $local_id ) . '" ' .
									'nonce="' . $nonce . '" ' .
									'href="#' . esc_attr( trim( $quote_author['title_name'] . ' ' . $quote_author['first'] . ' ' . $quote_author['last'] ) ) . '" ' .
									'title="' . esc_attr__( 'See all quotes from', 'quotes-llama' ) . ' ' . esc_attr( trim( $quote_author['title_name'] . ' ' . $quote_author['first'] . ' ' . $quote_author['last'] ) ) . '">';

						// If first and last name.
						if ( $quote_author['last'] ) {
							$author_link_list .= wp_kses_post( $this->quotes_llama_clickable( trim( $quote_author['last'] . ', ' . $quote_author['first'] . $title_name ) ) );
						} else {
							$author_link_list .= wp_kses_post( $this->quotes_llama_clickable( trim( $quote_author['first'] . $title_name ) ) );
						}

						$author_link_list  .= '</a></li>';
						$current_quote_data = $quote_author['index'];
						$local_id++;
					}
				}

				// Build output div.
				$template_page = '<div class="quotes-llama-page-container">' .
					'<div class="quotes-llama-page-sidebarleft">' .
						'<div class="quotes-llama-page-title">' .
								'<h3>' .
									esc_html( $quotes_title ) .
								'</h3>' .
								wp_kses_post( $header_letter_list ) .
						'</div>' .
						$this->quotes_llama_clickable( $author_link_list ) .
					'</div>' .
					'<div class="quotes-llama-page-status"></div>' .
					'<div id="quotes-llama-printquote" class="quotes-llama-page-quote">' .
						$this->quotes_llama_clickable( $initial_quote ) .
					'</div>' .
				'</div>';

				return $template_page_loggedin . $template_page;
			} else {
				$this->msg = $this->message( 'Transaction failed: No results. - ' . $quotesresult, 'nay' );
			}
		} else {
			$this->msg = $this->message( '', 'nonce' );
		}
	}

	/**
	 * Renders results of quotes search from the page view.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param Array $quotes - Array of search results.
	 */
	private function template_page_search( $quotes ) {
		if ( $quotes ) {

			// Show dashicon setting.
			$show_icons = $this->check_plugin_option( 'show_icons' );

			// New line seperator.
			$source_newline = $this->check_plugin_option( 'source_newline' );

			// For if author already displayed.
			$author = '';

			// For if image is new or not.
			$image = '';

			// Include hr tag.
			$hr = 0;

			foreach ( $quotes as $quote ) {

				// Set default icons if none. This is for backwards compatibility.
				if ( empty( $quote->author_icon ) ) {
					$quote->author_icon = $this->check_plugin_option( 'author_icon' );
				}

				if ( empty( $quote->source_icon ) ) {
					$quote->source_icon = $this->check_plugin_option( 'source_icon' );
				}

				if ( trim( $quote->title_name . ' ' . $quote->first_name . ' ' . $quote->last_name ) === $author ) {
					?>
					<div class='quotes-llama-page-quotebox quotes-llama-page-more'>
						<?php
						// Check that we have an image url to use.
						if ( $quote->img_url ) {

							// Already have this image already displayed for the author.
							if ( $image !== $quote->img_url ) {
								?>
								<img src='<?php echo esc_url( $quote->img_url ); ?>'
									hspace='5'> 
									<?php
							}
						}
						?>
						<span class='quotes-llama-page-quote-more'><?php echo wp_kses_post( $this->quotes_llama_clickable( nl2br( $quote->quote ) ) ); ?></span>
					</div>
					<div class='quotes-llama-page-source'>
						<?php
						if ( $quote->source ) {
							$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
							echo wp_kses( $this->show_icon( $quote->source_icon ), $allowed_html );
							echo wp_kses_post( $this->quotes_llama_clickable( $quote->source ) );
							echo '</span>';
						}
						?>
					</div> 
					<?php
				} else {
					// Skip very first hr.
					if ( $hr ) {
						echo wp_kses_post( '<hr>' );
					} else {
						$hr = 1;
					}
					?>
					<div class='quotes-llama-quote-author'>
						<h2>
							<?php
							$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
							echo wp_kses( $this->show_icon( $quote->author_icon ), $allowed_html );
							echo wp_kses_post( $this->quotes_llama_clickable( trim( $quote->title_name . ' ' . $quote->first_name . ' ' . $quote->last_name ) ) );
							echo '</span>';
							?>
						</h2>
					</div>
					<div class='quotes-llama-page-quotebox quotes-llama-page-more'>
						<?php
						if ( $quote->img_url ) {
							?>
							<!-- Check that we have an image url to use. -->
							<img src='<?php echo esc_url( $quote->img_url ); ?>'
								hspace='5'> 
							<?php
						}
						?>
						<span class='quotes-llama-page-quote-more'>
							<?php echo wp_kses_post( $this->quotes_llama_clickable( nl2br( $quote->quote ) ) ); ?>
						</span>
					</div>
					<div class='quotes-llama-page-source'>
						<?php
						if ( $quote->source ) {
							$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
							echo wp_kses( $this->show_icon( $quote->source_icon ), $allowed_html );
							echo wp_kses_post( $this->quotes_llama_clickable( $quote->source ) );
							echo '</span>';
						}
						?>
					</div> 
					<?php
				}
				$author = wp_kses_post(
					$this->quotes_llama_clickable(
						trim(
							$quote->title_name . ' ' . $quote->first_name . ' ' . $quote->last_name
						)
					)
				);
				$image  = $quote->img_url;
			}
			?>
			<div class='quotes-llama-page-author-back quotes-llama-inline'> 
			<?php
				echo '<input type="button" value="Print" class="quotes-llama-print">';
			?>
			</div> 
			<?php
		} else {
			echo wp_kses_post( $this->message( esc_html__( 'Search returned nothing', 'quotes-llama' ), 'error' ) );
		}
	}

	/**
	 * Renders a list of author quotes in the page view.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param Array $quotes - Array of authors quotes.
	 */
	private function template_page_author( $quotes ) {

		// Show dashicon setting.
		$show_icons = $this->check_plugin_option( 'show_icons' );

		// To check if author already displayed.
		$author = '';

		// To check if image is new or not.
		$image = '';

		foreach ( $quotes as $quote ) {

			// Set default icons if none. This is for backwards compatibility.
			if ( empty( $quote->author_icon ) ) {
				$quote->author_icon = $this->check_plugin_option( 'author_icon' );
			}

			if ( empty( $quote->source_icon ) ) {
				$quote->source_icon = $this->check_plugin_option( 'source_icon' );
			}

			if ( trim( $quote->title_name . ' ' . $quote->first_name . ' ' . $quote->last_name ) === $author ) {
				?>
				<!-- This for when we already have a quote displayed by the author, just print image and quote. -->
				<div class='quotes-llama-page-quotebox quotes-llama-page-more'>
					<?php
					// Check that we have an image url to use.
					if ( $quote->img_url ) {
						if ( $image !== $quote->img_url ) {
							?>
							<!-- This for when we already have this image displayed for the author. -->
							<img src='<?php echo esc_url( $quote->img_url ); ?>'
								hspace='5'> 
							<?php
						}
					}
					?>
					<span class='quotes-llama-page-quote-more'>
						<?php echo wp_kses_post( $this->quotes_llama_clickable( nl2br( $quote->quote ) ) ); ?>
					</span>
				</div>
				<div class='quotes-llama-page-source'>
					<?php
					// If there is a source.
					if ( $quote->source ) {
						$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
						echo wp_kses( $this->show_icon( $quote->source_icon ), $allowed_html );
						echo wp_kses_post( $this->quotes_llama_clickable( $quote->source ) );
						echo '</span>';
					}
					?>
				</div> 
				<?php
			} else {
				?>
				<!-- Include author. -->
				<div class='quotes-llama-quote-author'>
					<h2>
						<?php
						$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
						echo wp_kses( $this->show_icon( $quote->author_icon ), $allowed_html );
						echo wp_kses_post(
							$this->quotes_llama_clickable(
								trim(
									$quote->title_name . ' ' . $quote->first_name . ' ' . $quote->last_name
								)
							)
						);
						echo '</span>';
						?>
						<!-- End icon <span>. -->
					</h2>
				</div>
				<div class='quotes-llama-page-quotebox quotes-llama-page-more'>
					<?php
					if ( $quote->img_url ) {
						?>
						<!-- Check that we have an image url to use. -->
						<img src='<?php echo esc_url( $quote->img_url ); ?>'
							hspace='5'> 
							<?php
					}
					?>
					<span class='quotes-llama-page-quote-more'><?php echo wp_kses_post( $this->quotes_llama_clickable( nl2br( $quote->quote ) ) ); ?></span>
				</div>
				<div class='quotes-llama-page-source'>
					<?php
					// If there is a source.
					if ( $quote->source ) {
						$allowed_html = $this->quotes_llama_allowed_html( 'qform' );
						echo wp_kses( $this->show_icon( $quote->source_icon ), $allowed_html );
						echo wp_kses_post( $this->quotes_llama_clickable( $quote->source ) );
						echo '</span>';
					}
					?>
				</div> 
				<?php
			}
			$author = trim( $quote->title_name . ' ' . $quote->first_name . ' ' . $quote->last_name );
			$image  = $quote->img_url;
			echo '<hr>';
		}
		?>
		<div class='quotes-llama-page-author-back quotes-llama-inline'> 
		<?php
			echo '<a class="quotes-llama-page-author-back quotes-llama-inline" title="' .
				esc_attr__( 'Return to', 'quotes-llama' ) . ' ' .
				esc_html( $author ) . '" href="#' .
				esc_attr( $author ) . '"><input type="button" value="&larr;"></a>';
			echo '<input type="button" value="Print" class="quotes-llama-print">';
		?>
		</div>
		<?php
		die();
	}

	/**
	 * [quotes-llama id='#']
	 * Renders a static quote by id in page, post or template.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param int  $id - quote id.
	 * @param bool $show_author - show the author.
	 * @param bool $show_source - show the source.
	 * @param bool $show_image - show the image.
	 *
	 * @return String - must return string, not echo or display or will render at top of page regardless of positioning.
	 */
	private function template_id( $id = 1, $show_author = false, $show_source = false, $show_image = false ) {

		// Enqueue conditional css.
		$this->quotes_llama_css_conditionals();

		// bool Include field seperator.
		$use_comma = false;

		// bool Display dashicons.
		$show_icons = $this->check_plugin_option( 'show_icons' );

		// bool Display Author.
		$show_author = $this->check_plugin_option( 'show_page_author' );

		// bool Display Source.
		$show_source = $this->check_plugin_option( 'show_page_source' );

		// bool Display image.
		$show_image = $this->check_plugin_option( 'show_page_image' );

		// bool Display [quotes-llama] next quote link.
		$show_next = $this->check_plugin_option( 'show_page_next' );

		// string Seperator or new line.
		$source_newline = $this->check_plugin_option( 'source_newline' );

		// Get the quote by the id shortcode.
		$quote_data = $this->quotes_select( $id, '' );
		$image      = '';
		if ( $show_image ) {
			$isimage = isset( $quote_data['img_url'] ) ? $quote_data['img_url'] : '';
			if ( $isimage && ! empty( $isimage ) ) {
				$image_exist = $isimage;
				$image       = '<img src="' . esc_url_raw( $image_exist ) . '">';
			}
		}

		// If showing author or source.
		if ( $show_author || $show_source ) {
			$author_source = '<span class="quotes-llama-widget-author">';
			$istitle       = isset( $quote_data['title_name'] ) ? $quote_data['title_name'] : '';
			$isfirst       = isset( $quote_data['first_name'] ) ? $quote_data['first_name'] : '';
			$islast        = isset( $quote_data['last_name'] ) ? $quote_data['last_name'] : '';

			// If showing author, build string.
			if ( $show_author && ( $isfirst || $islast ) ) {
				$use_comma      = true;
				$author_source .= $this->show_icon( $quote_data['author_icon'] );
				$author_source .= wp_kses_post( trim( $istitle . ' ' . $isfirst . ' ' . $islast ) );
			}

			// If showing both author and source, add comma or new line.
			if ( $use_comma && ( $show_source && $quote_data['source'] ) ) {
				$author_source .= $this->separate_author_source( esc_html( $source_newline ) );
			}

			// If showing source build string.
			if ( $show_source ) {
				$issource = isset( $quote_data['source'] ) ? $quote_data['source'] : '';
				if ( $issource ) { // Check that there is a source.
					$author_source .= $this->show_icon( $quote_data['source_icon'] );
					$author_source .= '<span class="quotes-llama-widget-source">' . wp_kses_post( $issource ) . '</span>';
					$author_source .= '</span>';
				}
			}
			$author_source .= '</span>';
		} else {
			$author_source = '';
		}

		$isquote = isset( $quote_data['quote'] ) ? $quote_data['quote'] : '';

		// Build and return our div.
		return '<div class="quotes-llama-widget-random widget-text wp_widget_plugin_box">' .
			$image .
			'<span class="quotes-llama-widget-more">' .
				wp_kses_post( $this->quotes_llama_clickable( nl2br( $isquote ) ) ) .
			'</span>' .
			wp_kses_post( $this->quotes_llama_clickable( $author_source ) ) .
		'</div>';
	}

	/**
	 * [quotes-llama]
	 * Renders a single quote from all quotes or just a category.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $cat - Narrow to a category.
	 *
	 * @return String - must return string, not echo or display or will render at top of page regardless of positioning.
	 */
	private function template_post( $cat = '' ) {

		// Enqueue conditional css.
		$this->quotes_llama_css_conditionals();

		// Widget css.
		wp_enqueue_style( 'quotes-llama-css-widget' );

		// bool Include field seperator.
		$use_comma = false;

		// bool Display dashicons.
		$show_icons = $this->check_plugin_option( 'show_icons' );

		// bool Display Author.
		$show_author = $this->check_plugin_option( 'show_page_author' );

		// bool Display Source.
		$show_source = $this->check_plugin_option( 'show_page_source' );

		// bool Display image.
		$show_image = $this->check_plugin_option( 'show_page_image' );

		// bool Display [quotes-llama] next quote link.
		$show_next = $this->check_plugin_option( 'show_page_next' );

		// string Seperator or new line.
		$source_newline = $this->check_plugin_option( 'source_newline' );

		if ( $cat ) {

			// Get a random quote from a category.
			$quote_data = $this->quotes_select( 0, $cat );
			$quote_data = $quote_data[0];
		} else {

			// Get a random quote from all quotes.
			$quote_data = $this->quotes_select( 'quotes_llama_random', '' );
		}

		// Image src link.
		$image = '';

		if ( $show_image ) {
			$isimage = isset( $quote_data['img_url'] ) ? $quote_data['img_url'] : '';
			if ( $isimage && ! empty( $isimage ) ) {
				$image_exist = esc_url_raw( $isimage );
				$image       = '<img src="' . $image_exist . '">';
			}
		}

		// If showing author or source.
		if ( $show_author || $show_source ) {
			$author_source = '<span class="quotes-llama-widget-author">';

			$istitle = isset( $quote_data['title_name'] ) ? $quote_data['title_name'] : '';
			$isfirst = isset( $quote_data['first_name'] ) ? $quote_data['first_name'] : '';
			$islast  = isset( $quote_data['last_name'] ) ? $quote_data['last_name'] : '';
			if ( $show_author && ( $isfirst || $islast ) ) {
				$use_comma      = true;
				$author_source .= $this->show_icon( $quote_data['author_icon'] );
				$author_source .= wp_kses_post(
					$this->quotes_llama_clickable(
						trim( $istitle . ' ' . $isfirst . ' ' . $islast )
					)
				);
			}

			if ( $use_comma && ( $show_source && $quote_data['source'] ) ) {
				$author_source .= $this->separate_author_source( $source_newline );
			}

			// If showing source build string.
			if ( $show_source ) {
				$issource = isset( $quote_data['source'] ) ? $quote_data['source'] : '';

				// Check that there is a source.
				if ( $issource ) {
					$author_source .= wp_kses_post( $this->show_icon( $quote_data['source_icon'] ) );
					$author_source .= '<span class="quotes-llama-widget-source">' . wp_kses_post( $this->quotes_llama_clickable( $issource ) ) . '</span>';
				}
			}

			$author_source .= '</span>';
		} else {
			$author_source = '';
		}

		$isquote = isset( $quote_data['quote'] ) ? $quote_data['quote'] : '';

		if ( ! isset( $div_instance ) ) {
			$div_instance = 'q' . wp_rand( 1000, 100000 );
		}

		if ( $show_next ) {

			// Uses Ajax.
			wp_enqueue_script( 'quotesllamaAjax' );
			$allowed_html = $this->quotes_llama_allowed_html( 'span' );
			$next_quote   = '<hr>' .
					'<div class="quotes-llama-' . esc_attr( $div_instance ) . '-next quotes-llama-widget-next" ' .
					'divid="' . esc_attr( $div_instance ) . '" ' .
					'author="' . esc_attr( $show_author ) . '" ' .
					'source="' . esc_attr( $show_source ) . '" ' .
					'category="' . esc_attr( $cat ) . '" ' .
					'img="' . esc_attr( $show_image ) . '" ' .
					'nonce="' . esc_attr( wp_create_nonce( 'quotes_llama_nonce' ) ) . '">' .
					'<a href="#nextquote" onclick="return false;">' . wp_kses( $this->check_plugin_option( 'next_quote_text' ), $allowed_html ) . '</a>' .
				'</div>';
		} else {
			$next_quote = '';
		}

		return '<div id="' . esc_attr( $div_instance ) . '" class="quotes-llama-widget-random widget-text wp_widget_plugin_box">' .
			$image .
			'<span class="quotes-llama-widget-more">' .
				wp_kses_post( $this->quotes_llama_clickable( nl2br( $isquote ) ) ) .
			'</span>' .
			$author_source .
			$next_quote .
		'</div>';
	}

	/**
	 * [quotes-llama quotes='#']
	 * Renders several quotes from all or category.
	 *
	 * @since 2.1.6
	 * @access private
	 *
	 * @param string $cat     - Narrow to a category.
	 * @param int    $qlcount - How many quotes.
	 *
	 * @return String - must return string, not echo or display or will render at top of page regardless of positioning.
	 */
	private function template_posts( $cat = '', $qlcount = 1 ) {

		// Enqueue conditional css.
		$this->quotes_llama_css_conditionals();

		// Count css.
		wp_enqueue_style( 'quotes-llama-css-count' );

		// bool Include field seperator.
		$use_comma = false;

		// bool Display dashicons.
		$show_icons = $this->check_plugin_option( 'show_icons' );

		// bool Display Author.
		$show_author = $this->check_plugin_option( 'show_page_author' );

		// bool Display Source.
		$show_source = $this->check_plugin_option( 'show_page_source' );

		// bool Display image.
		$show_image = $this->check_plugin_option( 'show_page_image' );

		// bool Display [quotes-llama] next quote link.
		$show_next = $this->check_plugin_option( 'show_page_next' );

		// string Seperator or new line.
		$source_newline = $this->check_plugin_option( 'source_newline' );

		// Return string.
		$qlreturn = '';

		if ( $cat ) {

			// Get random quotes from a category.
			$quotes_data = $this->quotes_select( 0, $cat, intval( $qlcount ) );
		} else {
			// Get random quotes from all quotes.
			$quotes_data = $this->quotes_select( 'quotes_llama_random', '', intval( $qlcount ) );
		}

		foreach ( $quotes_data as $quote_data ) {

			// The quote.
			$isquote = isset( $quote_data['quote'] ) ? $quote_data['quote'] : '';

			// If array is empty or there is no quote, go to next record.
			if ( ! $quote_data || ! $isquote ) {
				continue;
			}

			// Image src link.
			$image = '';

			if ( $show_image ) {
				$isimage = isset( $quote_data['img_url'] ) ? $quote_data['img_url'] : '';
				if ( $isimage && ! empty( $isimage ) ) {
					$image_exist = esc_url_raw( $isimage );
					$image       = '<img src="' . $image_exist . '">';
				}
			}

			// If showing author or source.
			if ( $show_author || $show_source ) {
				$author_source = '<span class="quotes-llama-count-author">';

				$istitle  = isset( $quote_data['title_name'] ) ? $quote_data['title_name'] : '';
				$isfirst  = isset( $quote_data['first_name'] ) ? $quote_data['first_name'] : '';
				$islast   = isset( $quote_data['last_name'] ) ? $quote_data['last_name'] : '';
				$issource = isset( $quote_data['source'] ) ? $quote_data['source'] : '';
				if ( $show_author && ( $isfirst || $islast ) ) {
					$use_comma      = true;
					$author_source .= $this->show_icon( $quote_data['author_icon'] );
					$author_source .= wp_kses_post(
						$this->quotes_llama_clickable(
							trim( $istitle . ' ' . $isfirst . ' ' . $islast )
						)
					);
				}

				if ( $use_comma && ( $show_source && $issource ) ) {
					$author_source .= $this->separate_author_source( $source_newline );
				}

				// If showing source build string.
				if ( $show_source ) {

					// Check that there is a source.
					if ( $issource ) {
						$author_source .= wp_kses_post( $this->show_icon( $quote_data['source_icon'] ) );
						$author_source .= '<span class="quotes-llama-count-source">' . wp_kses_post( $this->quotes_llama_clickable( $issource ) ) . '</span>';
						$author_source .= '</span>';
					}
				} else {
					$author_source .= '</span>';
				}
			} else {
				$author_source = '';
			}

			if ( ! isset( $div_instance ) ) {
				$div_instance = 'q' . wp_rand( 1000, 100000 );
			}

			$qlreturn .= '<div id="' . esc_attr( $div_instance ) . '" class="quotes-llama-count-quote widget-text wp_widget_plugin_box">' .
				$image .
				'<span class="quotes-llama-count-more">' .
					wp_kses_post( $this->quotes_llama_clickable( nl2br( $isquote ) ) ) .
				'</span>' .
				$author_source .
			'</div>';
		}

		return $qlreturn;
	}

	/**
	 * [quotes-llama mode='auto']
	 * Container for shortcode call.
	 * See JS files Auto section for dynamic content and function.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $cat - Category.
	 *
	 * @return String - must return string, not echo or display or will render at top.
	 */
	private function template_auto( $cat = '' ) {

		// Enqueue conditional css.
		$this->quotes_llama_css_conditionals();
		
		// Gallery css.
		wp_enqueue_style( 'quotes-llama-css-auto' );
		
		// Uses Ajax.
		wp_enqueue_script( 'quotesllamaAjax' );

		// Unique div to load .ajax refresh into.
		$div_instance = 'ql' . wp_rand( 1000, 100000 );
		return '<div class="quotes-llama-auto">' .
			'<div class="' . $div_instance . '" ' .
				'gauthor="' . $this->check_plugin_option( 'show_page_author' ) . '" ' .
				'gsource="' . $this->check_plugin_option( 'show_page_source' ) . '" ' .
				'gimage="' . $this->check_plugin_option( 'show_page_image' ) . '" ' .
				'gcategory="' . $cat . '">' .
				'<div class="' .
					$div_instance . '-countdown quotes-llama-auto-countdown ' .
					$div_instance . '-reenable quotes-llama-auto-reenable"> ' .
				'</div>' .
				'<div class="' .
					$div_instance . '-quotebox quotes-llama-auto-quote gcategory="' . $cat . '" id="loop">
				</div>' .
			'</div>' .
		'</div>';
	}

	/**
	 * [quotes-llama mode='gallery']
	 * Html contianer for shortcode call.
	 * See JS files Gallery sections for dynamic content and funtion.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param bool $cat - Narrow to a category.
	 *
	 * @return String - must return string, not echo or display or will render at top of page regardless of positioning.
	 */
	private function template_gallery( $cat = '' ) {

		// Enqueue conditional css.
		$this->quotes_llama_css_conditionals();

		// Gallery css.
		wp_enqueue_style( 'quotes-llama-css-gallery' );

		// Uses Ajax.
		wp_enqueue_script( 'quotesllamaAjax' );

		$div_instance = 'ql' . wp_rand( 1000, 100000 );
		return '<div class="quotes-llama-gallery">' .
			'<div class="' . $div_instance . '" ' .
				'gauthor="' . $this->check_plugin_option( 'show_gallery_author' ) . '" ' .
				'gsource="' . $this->check_plugin_option( 'show_gallery_source' ) . '" ' .
				'gimage="' . $this->check_plugin_option( 'show_gallery_image' ) . '" ' .
				'gcategory="' . $cat . '">' .
				'<div class="quotes-llama-gallery-rotate">' .
					'<div class="' .
						$div_instance . '-countdown quotes-llama-gallery-countdown ' .
						$div_instance . '-reenable quotes-llama-gallery-reenable"> ' .
					'</div>' .
					'<div class="' .
						$div_instance . '-quotebox quotes-llama-gallery-quotebox"' .
						' gcategory="' . $cat . '" id="loop">
					</div>' .
				'</div>' .
			'</div>' .
		'</div>';
	}

	/**
	 * [quotes-llama all='index, random, ascend or descend']
	 * [quotes-llama all='index, random, ascend or descend' cat='category']
	 * All quotes in a sorted page.
	 *
	 * @since 1.3.5
	 * @access private
	 *
	 * @param string $sort - Sort values: index, random, ascend or descend. Optional category.
	 * @param string $cat  - Category.
	 */
	private function template_all( $sort, $cat ) {
		global $wpdb;

		// Enqueue conditional css.
		$this->quotes_llama_css_conditionals();

		// Page css.
		wp_enqueue_style( 'quotes-llama-css-all' );

		// bool Display Author.
		$show_author = $this->check_plugin_option( 'show_page_author' );

		// bool Display Source.
		$show_source = $this->check_plugin_option( 'show_page_source' );

		// String seperator or new line.
		$source_newline = $this->check_plugin_option( 'source_newline' );

		// bool Display image.
		$show_image = $this->check_plugin_option( 'show_page_image' );

		// return div.
		$all_return = '';

		// Allowed HTML.
		$allowed_html = $this->quotes_llama_allowed_html( 'qform' );

		// Quotes from selected categories.
		if ( $cat ) {

			// Category string to array.
			$cats = explode( ', ', $cat );

			// Begin building query string.
			$cat_query = 'SELECT
					quote,
					title_name,
					first_name,
					last_name,
					source,
					img_url,
					author_icon,
					source_icon,
					category FROM ' . $wpdb->prefix . 'quotes_llama WHERE (';

			// Setup each category placeholder and its value.
			foreach ( $cats as $categ ) {
				$cat_query   .= 'category LIKE %s OR ';
				$cat_values[] = '%' . $categ . '%';
			}

			// Strip trailing OR from query string.
			$cat_query = substr( $cat_query, 0, -4 );

			// Finish building query string.
			$cat_query .= ') GROUP BY title_name, last_name, first_name ORDER BY last_name';

			$values = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					$cat_query, // phpcs:ignore
					$cat_values
				),
				ARRAY_A
			);
		} else {
			$values = $wpdb->get_results( // phpcs:ignore
				'SELECT * FROM '
				. $wpdb->prefix .
				'quotes_llama',
				ARRAY_A
			);
		}

		// If sort is set to random.
		if ( 'random' === $sort ) {
			shuffle( $values );
		}

		// If sort is set to ascend.
		if ( 'ascend' === $sort ) {
			$asc_col = array_column( $values, 'quote' );
			array_multisort( $asc_col, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE, $values );
		}

		// If sort is set to descend.
		if ( 'descend' === $sort ) {
			$dsc_col = array_column( $values, 'quote' );
			array_multisort( $dsc_col, SORT_DESC, SORT_NATURAL | SORT_FLAG_CASE, $values );
		}

		foreach ( $values as $quote ) {

			// Set default icons if none. This is for backwards compatibility.
			if ( empty( $quote['author_icon'] ) ) {
				$quote['author_icon'] = $this->check_plugin_option( 'author_icon' );
			}

			if ( empty( $quote['source_icon'] ) ) {
				$quote['source_icon'] = $this->check_plugin_option( 'source_icon' );
			}

			// Build return div.
			$all_return .= '<div class="quotes-llama-all-quote quotes-llama-all-more">';

			if ( $show_image ) {
				$use_image = isset( $quote['img_url'] ) ? $quote['img_url'] : '';
				if ( $use_image && ! empty( $quote['img_url'] ) ) {
					$image_exist = esc_url_raw( $quote['img_url'] );
					$all_return .= '<img src="' . $image_exist . '">';
				}
			}

			// The quote.
			$all_return .= wp_kses_post( $this->quotes_llama_clickable( nl2br( $quote['quote'] ) ) );

			// If showing author or source.
			if ( $show_author || $show_source ) {
				$all_return .= '<span class="quotes-llama-all-author">';

				$istitle = isset( $quote['title_name'] ) ? $quote['title_name'] : '';
				$isfirst = isset( $quote['first_name'] ) ? $quote['first_name'] : '';
				$islast  = isset( $quote['last_name'] ) ? $quote['last_name'] : '';
				if ( $show_author && ( $isfirst || $islast ) ) {
					$use_comma   = true;
					$all_return .= $this->show_icon( $quote['author_icon'] );
					$all_return .= wp_kses_post(
						$this->quotes_llama_clickable(
							trim( $istitle . ' ' . $isfirst . ' ' . $islast )
						)
					);
				}

				if ( $use_comma && ( $show_source && $quote['source'] ) ) {
					$all_return .= $this->separate_author_source( $source_newline );
				}

				// If showing source build string.
				if ( $show_source ) {
					$issource = isset( $quote['source'] ) ? $quote['source'] : '';

					// Check that there is a source.
					if ( $issource ) {
						$all_return .= wp_kses_post( $this->show_icon( $quote['source_icon'] ) );
						$all_return .= '<span class="quotes-llama-all-source">' . wp_kses_post( $this->quotes_llama_clickable( $issource ) ) . '</span>';
					}
				}

				$all_return .= '</span></div>';
			} else {
				$all_return = '';
			}
		}
		return $all_return;
	}

	/**
	 * Author/source separator, either a comma or new line.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $s - which separator to use.
	 *
	 * @return string - html used to separate.
	 */
	private function separate_author_source( $s ) {
		if ( 'br' === $s ) {
			$a = '<br>';
		} else {
			$a = ', ';
		}
		return $a;
	}

	/**
	 * Show a icon from image or dashicon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $icon - which dash-icon or image name.
	 *
	 * @return string - html span with icon image or dashicon class.
	 */
	public function show_icon( $icon ) {
		$show_icons = $this->check_plugin_option( 'show_icons' );

		// If options allow icons.
		if ( $show_icons ) {

			// Image extensions.
			$image_extensions = array(
				'png',
				'jpg',
				'jpeg',
				'gif',
				'bmp',
				'svg',
			);

			// Get extenstion of image file.
			$ext = strtolower( pathinfo( $icon, PATHINFO_EXTENSION ) );

			// If extenstion in array or is a dashicon.
			if ( in_array( $ext, $image_extensions, true ) ) {
				return '<span class="quotes-llama-icons"><img src="' . $this->quotes_llama_icons_url . $icon . '"></span>';
			} else {
				return '<span class="dashicons dashicons-' . $icon . '"></span>';
			}
		}
		return '';
	}

	/**
	 * Validate that a file is in fact an image file.
	 *
	 * @since 1.3.4
	 * @access private
	 *
	 * @param string $file - Supposed image file.
	 *
	 * @return int - 1 true 0 false.
	 */
	private function validate_image( $file ) {
		$size = getimagesize( $file );
		if ( ! $size ) {
			return 0;
		}

		$valid_types = array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP );

		if ( in_array( $size[2], $valid_types, true ) ) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Registers the widget class.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_widgets() {
		if ( ! class_exists( 'QuotesLlama_Widget' ) ) {
			require_once 'class-quotesllama-widget.php';
		}

		register_widget( 'QuotesLlama_Widget' );
	}

	/**
	 * Renders a widget instance in the sidebar.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int    $quote_id     - list of ids to gets.
	 * @param bool   $show_author  - show the author.
	 * @param bool   $show_source  - show the source.
	 * @param bool   $show_image   - show the image.
	 * @param bool   $next_quote   - Display next quote link.
	 * @param bool   $gallery      - Auto-refresh.
	 * @param string $category     - Category.
	 * @param string $div_instance - previous instance id so we can replace it instead of nest into it.
	 * @param string $nonce        - Nonce.
	 */
	public function widget_instance( $quote_id = 0, $show_author = true, $show_source = true, $show_image = true, $next_quote = true, $gallery = false, $category = '', $div_instance = 0, $nonce = '' ) {

		$post_nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( $post_nonce ) {
			$nonce = $post_nonce;
		}

		if ( wp_verify_nonce( $nonce, 'quotes_llama_nonce' ) ) {
			$use_comma      = false;
			$show_icons     = $this->check_plugin_option( 'show_icons' );
			$source_newline = $this->check_plugin_option( 'source_newline' );

			if ( isset( $_POST['author'] ) ) {
				$show_author = sanitize_text_field( wp_unslash( $_POST['author'] ) );
			}

			if ( isset( $_POST['source'] ) ) {
				$show_source = sanitize_text_field( wp_unslash( $_POST['source'] ) );
			}

			if ( isset( $_POST['img'] ) ) {
				$show_image = sanitize_text_field( wp_unslash( $_POST['img'] ) );
			}

			if ( isset( $_POST['next_quote'] ) ) {
				$next_quote = sanitize_text_field( wp_unslash( $_POST['next_quote'] ) );
			}

			if ( isset( $_POST['gallery'] ) ) {
				$gallery = sanitize_text_field( wp_unslash( $_POST['gallery'] ) );
			}

			if ( isset( $_POST['quote_id'] ) ) {
				$quote_id = sanitize_text_field( wp_unslash( $_POST['quote_id'] ) );
			}

			if ( isset( $_POST['category'] ) ) {
				$category = sanitize_text_field( wp_unslash( $_POST['category'] ) );
			}

			if ( isset( $_POST['div_instance'] ) ) {
				$div_instance = sanitize_text_field( wp_unslash( $_POST['div_instance'] ) );
			}

			if ( ! $div_instance ) {
				$div_instance = 'q' . wp_rand( 1000, 100000 );
			}

			// Gallery widget mode.
			if ( $gallery ) {
				?>
				<div id       = '<?php echo esc_attr( $div_instance ); ?>'
					class     = 'quotes-llama-widget-gallery widget-text wp_widget_plugin_box'
					wauthor   = '<?php echo esc_attr( $show_author ); ?>'
					wsource   = '<?php echo esc_attr( $show_source ); ?>'
					wimage    = '<?php echo esc_attr( $show_image ); ?>'
					category  = '<?php echo esc_attr( $category ); ?>'
					nonce     = '<?php echo esc_attr( wp_create_nonce( 'quotes_llama_nonce' ) ); ?>'>
				</div>
				<?php
				return;
			}

			// Get a random quote from a category or one from all.
			if ( $category && ! $quote_id ) {
				$quote_data = $this->quotes_select( 0, $category );
				$quote_data = $quote_data[0];
			} else {
				$quote_data = $this->quotes_select( 'quotes_llama_random', '' );
			}

			// Get a quote by the id.
			if ( $quote_id > 0 ) {

				// Disable auto-refresh.
				$gallery = false;

				// Get quote by its ID.
				$quote_data = $this->quotes_select( $quote_id, '' );
			}

			$image         = '';
			$author_source = '';

			// If showing image, build string.
			if ( $show_image ) {
				$isimage = isset( $quote_data['img_url'] ) ? $quote_data['img_url'] : '';
				if ( $isimage && ! empty( $isimage ) ) {
					$image_exist = esc_url( $isimage );
					$image       = '<img src="' . $image_exist . '">';
				}
			}

			// Span for author and source.
			$author_source = '<span class="quotes-llama-widget-author">';
			$istitle       = isset( $quote_data['title_name'] ) ? $quote_data['title_name'] : '';
			$isfirst       = isset( $quote_data['first_name'] ) ? $quote_data['first_name'] : '';
			$islast        = isset( $quote_data['last_name'] ) ? $quote_data['last_name'] : '';

			// If showing author, add to author_source.
			if ( $show_author && ( $isfirst || $islast ) ) {
				$use_comma      = true;
				$author_source .= $this->show_icon( $quote_data['author_icon'] );
				$author_source .= trim( $istitle . ' ' . $isfirst . ' ' . $islast );
			}

			if ( $use_comma && ( $show_source && $quote_data['source'] ) ) {
				$author_source .= $this->separate_author_source( $source_newline );
			}

			$issource = isset( $quote_data['source'] ) ? $quote_data['source'] : '';

			// If showing source, add to author_source. Also close span either way.
			if ( $show_source && $issource ) {
				$author_source .= $this->show_icon( $quote_data['source_icon'] );
				$author_source .= '<span class="quotes-llama-widget-source">' . $issource . '</span>';
				$author_source .= '</span>';
			} else {
				$author_source .= '</span>';
			}
			?>
			<div id='<?php echo esc_attr( $div_instance ); ?>'
			class='quotes-llama-widget-random widget-text wp_widget_plugin_box'>
			<?php
			echo wp_kses_post( $image );

			// If quote id is provided set static class or just set next quote link class.
			if ( $quote_id > 0 ) {
				echo '<span class="quotes-llama-' .
					esc_attr( $div_instance ) .
					'-widget-static-more quotes-llama-widget-static-more">';
			} else {
				echo '<span class="quotes-llama-' .
					esc_attr( $div_instance ) .
					'-next-more quotes-llama-widget-next-more">';
			}

			$isquote      = isset( $quote_data['quote'] ) ? $quote_data['quote'] : '';
			$allowed_html = $this->quotes_llama_allowed_html( 'span' );
			echo wp_kses_post( $this->quotes_llama_clickable( nl2br( $isquote ) ) );
			echo '</span>';
			echo wp_kses_post( $this->quotes_llama_clickable( $author_source ) );

			if ( ! $quote_id && $next_quote ) {
				?>
				<hr>
				<!-- if showing static quote or if disabled in the widgets option, disable next quote link. -->
				<div class  ='quotes-llama-<?php echo esc_attr( $div_instance ); ?>-next quotes-llama-widget-next'
					divid    = '<?php echo esc_attr( $div_instance ); ?>'
					author   = '<?php echo esc_attr( $show_author ); ?>'
					source   = '<?php echo esc_attr( $show_source ); ?>'
					category = '<?php echo esc_attr( $category ); ?>'
					img      = '<?php echo esc_attr( $show_image ); ?>'
					nonce    = '<?php echo esc_attr( wp_create_nonce( 'quotes_llama_nonce' ) ); ?>'>
					<a href='#nextquote' onclick='return false;'><?php echo wp_kses( $this->check_plugin_option( 'next_quote_text' ), $allowed_html ); ?></a>
				</div> 
				<?php
			}

			echo '</div>';
		}
	}

	/**
	 * Adds screen options to admin page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function quotes_llama_add_option() {

		// Include table class.
		if ( ! class_exists( 'QuotesLlama_Table' ) ) {
			require_once 'class-quotesllama-table.php';
		}

		global $quotes_table;
		$option = 'per_page';
		$args   = array(
			'label'   => 'Quotes per page',
			'default' => 10,
			'option'  => 'quotes_llama_per_page',
			'icons'   => $this->check_plugin_option( 'show_icons' ),
			'imgpath' => $this->quotes_llama_icons_url,
		);
		add_screen_option( $option, $args );
		$quotes_table = new QuotesLlama_Table();
	}

	/**
	 * Create html option element.
	 *
	 * @since 2.0.6
	 * @access private
	 *
	 * @param string $n - Value.
	 * @param string $s - Name.
	 * @param string $t - Current setting.
	 *
	 * @return string - html option attribute for select element.
	 */
	private function quotes_llama_make_option( $n, $s, $t ) {
		$r = '<option value="' . $n . '"';

		if ( $n === $t ) {
			$r .= ' selected';
		}

		$r .= '>';
		$r .= $s;
		$r .= '</option>';
		return $r;
	}

	/**
	 * Sets value for table screen options in admin page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $status - The value to save instead of the option value.
	 * @param string $name - The option name.
	 * @param string $value - The option value.
	 *
	 * @return string - Sanitized string.
	 */
	public function quotes_llama_set_option( $status, $name, $value ) {
		return $value;
	}

	/**
	 * Sets url params.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $nonce - Nonce.
	 *
	 * @return string - return paramaters for url.
	 */
	private function return_page( $nonce ) {
		$return_page = '';

		if ( wp_verify_nonce( $nonce, 'delete_edit' ) ) {
			// Set the paged param.
			if ( isset( $_GET['paged'] ) ) {
				$return_page .= '&paged=' . sanitize_text_field( wp_unslash( $_GET['paged'] ) );
			}

			// Set the search term param.
			if ( isset( $_GET['s'] ) ) {
				$return_page .= '&s=' . sanitize_text_field( wp_unslash( $_GET['s'] ) );
			}

			// Set the search column param.
			if ( isset( $_GET['sc'] ) ) {
				$return_page .= '&sc=' . sanitize_text_field( wp_unslash( $_GET['sc'] ) );
			}

			// Set the order param.
			if ( isset( $_GET['order'] ) ) {
				$return_page .= '&order=' . sanitize_text_field( wp_unslash( $_GET['order'] ) );
			}

			// Set the sort column param.
			if ( isset( $_GET['orderby'] ) ) {
				$return_page .= '&orderby=' . sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			}

			return $return_page;
		}
	}

	/**
	 * Success and Error messaging.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $msg    - The message to echo.
	 * @param string $yaynay - yay, nay, nonce.
	 *
	 * @return string - Html div with message.
	 */
	private function message( $msg, $yaynay ) {
		if ( 'yay' === $yaynay ) {
			return '<div class="updated qlmsg"><p>' . esc_html( $msg ) . '</p></div>';
		}

		if ( 'nay' === $yaynay ) {
			return '<div class="error qlmsg"><p>' . esc_html( $msg ) . '</p></div>';
		}

		if ( 'nonce' === $yaynay ) {
			return '<div class="error qlmsg"><p>' . esc_html__( 'Security token mismatch, please reload the page and try again.', 'quotes-llama' ) . '</p></div>';
		}
	}

	/**
	 * Gets quote category list. This the list of categories available to choose from.
	 *
	 * @since 2.0.5
	 * @access protected
	 *
	 * @param array $idcategory - Existing categories.
	 *
	 * @return string Checkbox - list of categories.
	 */
	protected function quotes_llama_get_categories( $idcategory = null ) {
		global $wpdb;

		// Get all categories.
		$ql_category = $this->quotes_select( 'categories', '' );

		// stdObject to array. Remove empty values.
		foreach ( $ql_category as $ql_cat ) {
			if ( ! empty( $ql_cat->category ) ) {
				$ql_categ[] = $ql_cat->category;
			}
		}

		if ( isset( $ql_categ ) ) {
			// Array to string. To combine singular and plural category entries into single csv line.
			$ql_category = implode( ', ', $ql_categ );

			// Back to array with values all separated. Strip duplicates.
			$ql_category = array_unique( explode( ', ', $ql_category ) );

			// Sort the categories.
			sort( $ql_category );
		}
			// For sorting checked categories to top.
			$is_checked  = '';
			$not_checked = '';

		// If there are categories already, create checkbox list and check them.
		if ( isset( $idcategory ) ) {

			// Add new category textbox.
			$cat  = '<label for="ql-new-category">Add new category.</label>';
			$cat .= '<input type="text" value="" id="ql-new-category" name="ql-new-category" placeholder="';
			$cat .= esc_html__( 'Add a new category here... rename or delete in the Manage tab.', 'quotes-llama' );
			$cat .= '"><button type="button" id="ql-new-cat-btn" class="button button-large">Add Category</button><br>';
			$cat .= '<br>Select from Categories:<br>';
			$cat .= '<span class="ql-category">';

			// stdObj to array so we can use values as strings.
			$ql_category = json_decode( wp_json_encode( $ql_category ), true );

			foreach ( $ql_category as $category ) {

				// Check category is a string. No categories is an array.
				if ( is_string( $category ) ) {

					// Category checkboxes. If already a category for this quote, check it.
					if ( in_array( $category, $idcategory, true ) ) {
						$is_checked .= '<label><input type="checkbox" name="ql_category[]" value="' . $category . '" checked>';
						$is_checked .= ' ' . $category . '</label><br>';
					} else {
						$not_checked .= '<label><input type="checkbox" name="ql_category[]" value="' . $category . '">';
						$not_checked .= ' ' . $category . '</label><br>';
					}
				}
			}
		} else {
			$ql_category = json_decode( wp_json_encode( $ql_category ), true );

			// Or just a text list of categories.
			$cat  = '<input type="text" value="" id="ql-bulk-category" name="ql-bulk-category">';
			$cat .= '<input type="hidden" value="" id="ql-bulk-category-old" name="ql-bulk-category-old">';
			$cat .= '<button type="submit" id="ql-rename-cat-btn" name="ql-rename-cat-btn" class="button button-large" title="Rename">' . esc_html__( 'Rename', 'quotes-llama' ) . '</button>';
			$cat .= '<button type="submit" id="ql-delete-cat-btn" name="ql-delete-cat-btn" class="button button-large" title="Delete">' . esc_html__( 'Delete', 'quotes-llama' ) . '</button><br>';
			$cat .= 'Select a category to work with:<br>';
			$cat .= '<span class="ql-category">';

			foreach ( $ql_category as $category ) {
				if ( is_string( $category ) ) {
					$not_checked .= '<button type="button" class="ql-manage-cat">' . $category . '</button>';
				}
			}
		}

		$cat .= $is_checked . $not_checked;
		$cat .= '</span>';
		return $cat;
	}

	/**
	 * Converts plaintext URI to HTML links.
	 * Edit copy of make_clickable funcion from /includes/formatting.php.
	 *
	 * Converts URI, www and ftp, and email addresses. Finishes by fixing links
	 * within links.
	 *
	 * @since 1.1.1
	 * @access protected
	 *
	 * @param string $text Content to convert URIs.
	 *
	 * @return string Content with converted URIs.
	 */
	protected function quotes_llama_clickable( $text ) {

		// Return string.
		$r = '';

		// Split out HTML tags.
		$textarr = preg_split( '/(<[^<>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE );

		// Keep track of how many levels link is nested inside <pre> or <code>.
		$nested_code_pre = 0;

		// Process text links.
		foreach ( $textarr as $piece ) {
			if ( preg_match( '|^<code[\s>]|i', $piece ) ||
				preg_match( '|^<pre[\s>]|i', $piece ) ||
				preg_match( '|^<script[\s>]|i', $piece ) ||
				preg_match( '|^<style[\s>]|i', $piece ) ) {
					$nested_code_pre++;
			} elseif ( $nested_code_pre && ( '</code>' === strtolower( $piece ) ||
				'</pre>' === strtolower( $piece ) ||
				'</script>' === strtolower( $piece ) ||
				'</style>' === strtolower( $piece ) ) ) {
				$nested_code_pre--;
			}

			if ( $nested_code_pre ||
				empty( $piece ) ||
				( '<' === $piece[0] && ! preg_match( '|^<\s*[\w]{1,20}+://|', $piece ) ) ) {
					$r .= $piece;
					continue;
			}

			// Long strings might contain expensive edge cases...
			if ( 10000 < strlen( $piece ) ) {

				// 2100: Extra room for scheme and leading and trailing paretheses.
				foreach ( _split_str_by_whitespace( $piece, 2100 ) as $chunk ) {
					if ( 2101 < strlen( $chunk ) ) { // Too big.
						$r .= $chunk;
					} else {
						$r .= quotes_llama_clickable( $chunk );
					}
				}
			} else {

				// Pad with whitespace to simplify the regexes.
				$ret           = " $piece ";
				$url_clickable = '~
						([\\s(<.,;:!?])                                # 1: Leading whitespace, or punctuation.
						(                                              # 2: URL.
							[\\w]{1,20}+://                            # Scheme and hier-part prefix.
							(?=\S{1,2000}\s)                           # Limit to URLs less than about 2000 characters long.
							[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+     # Non-punctuation URL character.
							(?:                                        # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character.
								[\'.,;:!?)]                            # Punctuation URL character.
								[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character.
							)*
						)
						(\)?)                                          # 3: Trailing closing parenthesis (for parethesis balancing post processing).
					~xS';
				// The regex is a non-anchored pattern and does not have a single fixed starting character.
				$ret = preg_replace_callback( $url_clickable, array( $this, 'quotes_llama_make_url_clickable_callback' ), $ret ); // Creates links of http and https.
				$ret = preg_replace( "#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"http://\\2\" target=\"_blank\" rel=\"nofollow\">\\2</a>", $ret ); // Creates link of www.

				// Display www in links if enabled. Remove whitespace padding.
				if ( $this->check_plugin_option( 'http_display' ) ) {
					$ret = substr( $ret, 1, -1 );
					$r  .= $ret;
				} else {
					$ret = str_replace( 'www.', '', $ret );
					$ret = substr( $ret, 1, -1 );
					$r  .= $ret;
				}
			}
		}
		$r = preg_replace( '#(<a([ \r\n\t]+[^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', '$1$3</a>', $r ); // Cleanup of accidental links within links.
		return $this->quotes_llama_close_tags( $r );
	}

	/**
	 * Callback to convert URI match to HTML A element.
	 * Edit of _make_url_clickable_cb funcion from /includes/formatting.php.
	 *
	 * This function was backported from 2.5.0 to 2.3.2. Regex callback for make_clickable().
	 *
	 * @since 1.1.1
	 * @access private
	 *
	 * @param array $matches Single Regex Match.
	 * @return string HTML A element with URI address.
	 */
	private function quotes_llama_make_url_clickable_callback( $matches ) {
		$url = $matches[2];

		if ( ')' === $matches[3] && strpos( $url, '(' ) ) {

			// If the trailing character is a closing parethesis, and the URL has an opening parenthesis in it.
			$url .= $matches[3];

			// Add the closing parenthesis to the URL. Then we can let the parenthesis balancer do its thing below.
			$suffix = '';
		} else {
			$suffix = $matches[3];
		}

		// Include parentheses in the URL only if paired.
		while ( substr_count( $url, '(' ) < substr_count( $url, ')' ) ) {
			$suffix = strrchr( $url, ')' ) . $suffix;
			$url    = substr( $url, 0, strrpos( $url, ')' ) );
		}

		$url = esc_url( $url );
		if ( empty( $url ) ) {
			return $matches[0];
		}

		if ( 'comment_text' === current_filter() ) {
			$rel = 'nofollow ugc';
		} else {
			$rel = 'nofollow';
		}

		/**
		 * Filters the rel value that is added to URL matches converted to links.
		 *
		 * @param string $rel The rel value.
		 * @param string $url The matched URL being converted to a link tag.
		 */
		$rel = apply_filters( 'make_clickable_rel', $rel, $url );
		$rel = esc_attr( $rel );

		// Display http in links if enabled.
		if ( $this->check_plugin_option( 'http_display' ) ) {
			$nourl = $url;
		} else {
			$nourl = preg_replace( '(^https?://)', '', $url );
		}

		return $matches[1] . "<a href=\"$url\" target=\"_blank\" rel=\"nofollow\">$nourl</a>" . $suffix;
	}

	/**
	 * Count html tags and provide closing tag if missing.
	 * This does not close inline but at the end of the element.
	 * You will still see bleed out but not into the rest of the content.
	 *
	 * @since 1.1.2
	 * @access private
	 *
	 * @param string $html - String to check.
	 * @return string      - String with closing tags matched.
	 */
	private function quotes_llama_close_tags( $html ) {

		// Put all opened tags into an array.
		preg_match_all( '#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result );

		// Put all closed tags into an array.
		$openedtags = $result[1];
		preg_match_all( '#</([a-z]+)>#iU', $html, $result );
		$closedtags = $result[1];
		$len_opened = count( $openedtags );
		if ( count( $closedtags ) === $len_opened ) {
			return $html;
		}

		// Reverse array elements.
		$openedtags = array_reverse( $openedtags );

		for ( $i = 0; $i < $len_opened; $i++ ) {

			// If no close tag.
			if ( ! in_array( $openedtags[ $i ], $closedtags, true ) ) {
				$html .= '</' . $openedtags[ $i ] . '>'; // Make one.
			} else {

				// Close tag found, so remove from list.
				unset( $closedtags[ array_search( $openedtags[ $i ], $closedtags, true ) ] );
			}
		}
		return $html;
	}

	/**
	 * Conditional css enqueues.
	 *
	 * @since 2.1.2
	 * @access protected
	 */
	protected function quotes_llama_css_conditionals() {

		// CSS if image should be centered at top.
		if ( isset( $this->quotes_llama_plugin_options['image_at_top'] ) ) {
			wp_enqueue_style( 'quotes-llama-css-image-center' );
		}

		// CSS if image should be round.
		if ( isset( $this->quotes_llama_plugin_options['border_radius'] ) ) {
			wp_enqueue_style( 'quotes-llama-css-image-round' );
		}

		// CSS for quote alignment.
		if ( isset( $this->quotes_llama_plugin_options['align_quote'] ) ) {
			if ( 'center' === $this->quotes_llama_plugin_options['align_quote'] ) {
				wp_enqueue_style( 'quotes-llama-css-quote-center' );
			} elseif ( 'left' === $this->quotes_llama_plugin_options['align_quote'] ) {
				wp_enqueue_style( 'quotes-llama-css-quote-left' );
			} elseif ( 'right' === $this->quotes_llama_plugin_options['align_quote'] ) {
				wp_enqueue_style( 'quotes-llama-css-quote-right' );
			}
		}

		// CSS to reformat icon images.
		wp_enqueue_style( 'quotes-llama-css-icons-format' );
	}

	/**
	 * Allowed html lists.
	 *
	 * @since 1.1.2
	 * @access protected
	 *
	 * @param string $type - Which set of allowed tags.
	 * @return array - Allowed html entities.
	 */
	protected function quotes_llama_allowed_html( $type ) {

		if ( 'style' === $type ) {
			$allowed_html = array(
				'a'      => array(
					'href'   => true,
					'title'  => true,
					'target' => true,
					'class'  => true,
					'rel'    => true,
				),
				'img'    => array(
					'alt' => true,
					'src' => true,
				),
				'br'     => array(
					'clear' => true,
				),
				'b'      => array(),
				'del'    => array(),
				'mark'   => array(),
				'strong' => array(),
				'small'  => array(),
				'em'     => array(),
				'i'      => array(),
				'sub'    => array(),
				'sup'    => array(),
				'u'      => array(),
			);
			return $allowed_html;
		}

		if ( 'image' === $type ) {
			$allowed_html = array(
				'img' => array(
					'alt'   => true,
					'width' => true,
					'title' => true,
					'src'   => true,
				),
			);
			return $allowed_html;
		}

		if ( 'column' === $type ) {
			$allowed_html = array(
				'a'      => array(
					'href'    => true,
					'title'   => true,
					'target'  => true,
					'rel'     => true,
					'onclick' => true,
				),
				'div'    => array(
					'class' => true,
				),
				'th'     => array(
					'id'    => true,
					'class' => true,
					'scope' => true,
					'style' => true,
				),
				'img'    => array(
					'alt'   => true,
					'width' => true,
					'title' => true,
					'src'   => true,
				),
				'label'  => array(
					'for'   => true,
					'class' => true,
				),
				'input'  => array(
					'type'  => true,
					'name'  => true,
					'value' => true,
				),
				'span'   => array(
					'class' => true,
				),
				'br'     => array(
					'clear' => true,
				),
				'b'      => array(),
				'del'    => array(),
				'mark'   => array(),
				'strong' => array(),
				'small'  => array(),
				'em'     => array(),
				'i'      => array(),
				'sub'    => array(),
				'sup'    => array(),
				'u'      => array(),
			);
			return $allowed_html;
		}

		if ( 'div' === $type ) {
			$allowed_html = array(
				'div' => array(
					'class' => true,
				),
			);
			return $allowed_html;
		}

		if ( 'span' === $type ) {
			$allowed_html = array(
				'span' => array(
					'class' => true,
				),
			);
			return $allowed_html;
		}

		if ( 'option' === $type ) {
			$allowed_html = array(
				'option' => array(
					'value'    => true,
					'selected' => true,
					'disabled' => true,
					'hidden'   => true,
				),
			);
			return $allowed_html;
		}

		if ( 'qform' === $type ) {
			$allowed_html = array(
				'p'        => array(
					'class' => true,
				),
				'a'        => array(
					'href' => true,
				),
				'br'       => array(),
				'span'     => array(
					'class' => true,
				),
				'fieldset' => array(
					'class' => true,
				),
				'legend'   => array(),
				'ul'       => array(
					'id' => true,
				),
				'li'       => array(),
				'table'    => array(
					'class'       => true,
					'cellpadding' => true,
					'cellspacing' => true,
					'width'       => true,
				),
				'tbody'    => array(),
				'tr'       => array(
					'class' => true,
				),
				'th'       => array(
					'style'  => true,
					'scope'  => true,
					'valign' => true,
					'label'  => true,
				),
				'td'       => array(
					'style'    => true,
					'name'     => true,
					'textarea' => true,
					'rows'     => true,
					'cols'     => true,
					'id'       => true,
				),
				'textarea' => array(
					'id'    => true,
					'name'  => true,
					'style' => true,
					'rows'  => true,
					'cols'  => true,
				),
				'form'     => array(
					'name'   => true,
					'method' => true,
					'action' => true,
				),
				'label'    => array(
					'for' => true,
				),
				'input'    => array(
					'type'        => true,
					'name'        => true,
					'value'       => true,
					'class'       => true,
					'placeholder' => true,
					'size'        => true,
					'id'          => true,
					'list'        => true,
					'checked'     => true,
				),
				'button'   => array(
					'class' => true,
					'input' => true,
					'type'  => true,
					'id'    => true,
					'name'  => true,
				),
				'img'      => array(
					'src' => true,
					'alt' => true,
				),
				'option'   => array(
					'value'    => true,
					'selected' => true,
					'disabled' => true,
					'hidden'   => true,
				),
				'select'   => array(
					'id'       => true,
					'name'     => true,
					'multiple' => true,
					'size'     => true,
				),
			);
			return $allowed_html;
		}

		if ( 'quote' === $type ) {
			$allowed_html = array(
				'a'     => array(
					'href'  => true,
					'title' => true,
					'class' => true,
					'rel'   => true,
				),
				'div'   => array(
					'class' => true,
					'style' => true,
				),
				'input' => array(
					'class' => true,
					'type'  => true,
					'value' => true,
				),
				'img'   => array(
					'src'    => true,
					'id'     => true,
					'hspace' => true,
					'align'  => true,
				),
				'br'    => array(
					'clear' => true,
				),
				'hr'    => array(),
			);
			return $allowed_html;
		}

		if ( 'paginate' === $type ) {
			$allowed_html = array(
				'a'     => array(
					'href'  => true,
					'title' => true,
					'class' => true,
				),
				'div'   => array(
					'class' => true,
				),
				'span'  => array(
					'class' => true,
				),
				'input' => array(
					'class' => true,
					'id'    => true,
					'title' => true,
					'type'  => true,
					'name'  => true,
					'value' => true,
					'size'  => true,
				),
				'label' => array(
					'for'   => true,
					'class' => true,
				),
			);
			return $allowed_html;
		}

		if ( 'print' === $type ) {
			$allowed_html = array(
				'a'     => array(
					'href'  => true,
					'title' => true,
					'class' => true,
				),
				'div'   => array(
					'class' => true,
				),
				'th'    => array(
					'id'    => true,
					'class' => true,
					'scope' => true,
					'style' => true,
				),
				'label' => array(
					'for'   => true,
					'class' => true,
				),
				'input' => array(
					'class'    => true,
					'id'       => true,
					'title'    => true,
					'type'     => true,
					'scope'    => true,
					'style'    => true,
					'checkbox' => true,
				),
			);
			return $allowed_html;
		}
	}

} // end class QuotesLlama.

// Start the plugin.
$quotes_llama = new QuotesLlama();
?>
