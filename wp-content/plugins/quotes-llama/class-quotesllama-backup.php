<?php
/**
 * Quotes Llama Backup Class
 *
 * Description. Import/Export quotes from the database.
 *
 * @Link        http://wordpress.org/plugins/quotes-llama/
 * @package     quotes-llama
 * @since       1.0.0
 * License:     Copyheart
 * License URI: http://copyheart.org
 */

/**
 * Class QuotesLlama_Backup.
 */
class QuotesLlama_Backup extends QuotesLlama {

	/**
	 * CSV Delimiter.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string
	 */
	private $separator;

	/**
	 * Filename prefix.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string
	 */
	private $filename;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $sep - csv delimiter.
	 */
	protected function __construct( $sep ) {
		$this->separator = $sep;
		$this->filename  = 'quotes';
	}

	/**
	 * Create .csv export file.
	 * Prompt browser to save file.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function create_csv() {
		$date_now = gmdate( 'd-m-Y_His' );
		$csv_file = $this->generate_csv();
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false ); // Set browser to download file.
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $this->filename . '_' . $date_now . '.csv";' );
		header( 'Content-Transfer-Encoding: binary' );
		echo wp_kses_post( $csv_file );
		die();
	}

	/**
	 * Get data for .csv
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return string $csv_output - quote data with columns.
	 */
	protected function generate_csv() {
		global $wpdb;
		$csv_output = '';

		$values = $wpdb->get_results( // phpcs:ignore
			'SHOW COLUMNS FROM ' .
			$wpdb->prefix .
			'quotes_llama'
		);

		// Create column headers.
		if ( count( $values ) > 0 ) {

			// Remove quote_id from header columns.
			unset( $values[0] );

			foreach ( $values as $row ) {
				$csv_output = $csv_output . $row->Field . $this->separator; // phpcs:ignore
			}

			// Trim trailing separator.
			$csv_output = substr( $csv_output, 0, -1 );
		}

		$csv_output .= "\n"; // Add new line for quotes.

		$values = $wpdb->get_results( // phpcs:ignore
			'SELECT * FROM '
			. $wpdb->prefix .
			'quotes_llama',
			ARRAY_A
		);

		foreach ( $values as $value ) {

			// Remove the quote_id.
			unset( $value['quote_id'] );

			// Index the array numerically.
			$csv_fields = array_values( $value );

			// String with field separator.
			$csv_string = stripslashes(
				implode(
					$this->separator,
					$csv_fields
				)
			);

			$csv_output .= $csv_string;
			$csv_output .= "\n";
		}

		return $csv_output;
	}

	/**
	 * Get data for .json and create the file.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function create_json() {
		global $wpdb;
		$date_now = gmdate( 'd-m-Y_His' );

		$quotes = $wpdb->get_results( // phpcs:ignore
			'SELECT * FROM ' .
			$wpdb->prefix .
			'quotes_llama',
			ARRAY_A
		);

		foreach ( $quotes as $quote => $data ) {
			unset( $data['quote_id'] );
			$data['quote']       = htmlspecialchars( $data['quote'] );
			$data['title_name']  = htmlspecialchars( $data['title_name'] );
			$data['first_name']  = htmlspecialchars( $data['first_name'] );
			$data['last_name']   = htmlspecialchars( $data['last_name'] );
			$data['source']      = htmlspecialchars( $data['source'] );
			$data['img_url']     = $data['img_url'];
			$data['author_icon'] = htmlspecialchars( $data['author_icon'] );
			$data['source_icon'] = htmlspecialchars( $data['source_icon'] );
			$data['category']    = htmlspecialchars( $data['category'] );
			$quotes[ $quote ]    = $data;
		}

		$json_output = wp_json_encode( $quotes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		header( 'Content-Type: text/json' );
		header( 'Content-Disposition: attachment; filename="' . $this->filename . '_' . $date_now . '.json";' );
		echo wp_kses_post( $json_output );
		die();
	}

	/**
	 * Generates the import query string for importing from a json or csv file.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function generate_import() {
		global $wpdb;

		// Check that we have a table to write to.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "quotes_llama'" ) === $wpdb->prefix . 'quotes_llama' ) { // phpcs:ignore
			// Accepted file extensions.
			$exts = array(
				'json',
				'JSON',
				'CSV',
				'csv',
			);

			// Sanitize, unslash filename.
			$filename = isset( $_FILES['quotes-llama-file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['quotes-llama-file']['name'] ) ) : '';

			// Sanitize, unslash temp filename.
			$filetmp = isset( $_FILES['quotes-llama-file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['quotes-llama-file']['tmp_name'] ) ) : '';

			// Files extension.
			$fileext = pathinfo( $filename, PATHINFO_EXTENSION );

			// File error.
			$fileerror = isset( $_FILES['quotes-llama-file']['error'] ) ? absint( wp_unslash( $_FILES['quotes-llama-file']['error'] ) ) : '';

			// Upload file or error.
			if ( $fileerror ) {

				// Translate error code.
				$thismsg = $this->error_messages( $fileerror );
				return wp_kses_post( $thismsg );
			}

			// Upload success.
			if ( UPLOAD_ERR_OK === $fileerror && is_uploaded_file( $filetmp ) ) {

				// Validate file extension.
				if ( ! in_array( $fileext, $exts, true ) ) {
					return 'The file type ' . esc_html( $fileext ) . ' is not supported.';
				}

				$json_data = file_get_contents( $filetmp ); // phpcs:ignore

				// Validate has data.
				if ( ! $json_data ) {
					return esc_html__( 'Unable to import because the file is empty.' );
				}

				// Decode objects into array. Validate the decode.
				if ( 'json' === $fileext || 'JSON' === $fileext ) {
					$quote_json = json_decode( $json_data, true );
					if ( is_null( $quote_json ) ) {
						return wp_kses_post( $this->error_messages() );
					} else {

						// Sanitize each.
						foreach ( $quote_json as $quote => $data ) {

							$allowed_html = $this->quotes_llama_allowed_html( 'style' );

							// Filter the quote for allowed html tags.
							if ( isset( $data['quote'] ) ) {
								$data['quote'] = wp_check_invalid_utf8( wp_unslash( $data['quote'] ) );
								$data['quote'] = wp_kses( trim( $data['quote'] ), $allowed_html );
							} else {

								// if no quote.
								$data['quote'] = '';
							}

							// Filter the source for allowed html tags.
							if ( isset( $data['source'] ) ) {
								$data['source'] = wp_check_invalid_utf8( wp_unslash( $data['source'] ) );
								$data['source'] = wp_kses( trim( $data['source'] ), $allowed_html );
							} else {
								$data['source'] = '';
							}

							$data['quote']        = htmlspecialchars_decode( $data['quote'] );
							$data['title_name']   = sanitize_text_field( htmlspecialchars_decode( $data['title_name'] ) );
							$data['first_name']   = sanitize_text_field( htmlspecialchars_decode( $data['first_name'] ) );
							$data['last_name']    = sanitize_text_field( htmlspecialchars_decode( $data['last_name'] ) );
							$data['source']       = htmlspecialchars_decode( $data['source'] );
							$data['img_url']      = esc_url_raw( $data['img_url'] );
							$data['author_icon']  = sanitize_text_field( htmlspecialchars_decode( $data['author_icon'] ) );
							$data['source_icon']  = sanitize_text_field( htmlspecialchars_decode( $data['source_icon'] ) );
							$data['category']     = sanitize_text_field( htmlspecialchars_decode( $data['category'] ) );
							$quote_json[ $quote ] = $data;
						}

						// End Import JSON data.
						$result = $this->quotes_import( $quote_json );
					}

					// If CSV file.
				} elseif ( 'csv' === $fileext || 'CSV' === $fileext ) {
					$header        = null;
					$quote_entries = array();
					$count         = 1;
					$handle        = fopen( $filetmp, 'r' ); // phpcs:ignore

					if ( false !== $handle ) {
						while ( ( $row = fgetcsv( $handle, 2000, $this->separator ) ) !== false ) { // phpcs:ignore

							// Check count of $row.
							if ( count( $row ) === 7 ) {
								fclose( $handle ); // phpcs:ignore
								return esc_html__(
									'There was an error. Verification returned on line ',
									'quotes-llama'
								) . absint(
									$count
								) . esc_html__(
									'. Be sure the csv delimiter in the options tab is set to match your file. Your files encoding may not be supported. Improper file structure such as incorrect columns and fields can cause the import to fail as well.',
									'quotes-llama'
								);
							} else {

								// Combine our header and data. Assign first row data to header columns [header][row].
								if ( ! $header ) {
									$header = $row;
									for ( $i = 0; $i <= 7; $i++ ) {

										// CSV in utf8 might have BOM characters in the headers. Remove BOM characters.
										$header[ $i ] = preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $header[ $i ] );

										// Sanitize header.
										$header[ $i ] = sanitize_text_field( $header[ $i ] );
									}
								} else {
									for ( $i = 0; $i <= 7; $i++ ) {
										$allowed_html = $this->quotes_llama_allowed_html( 'style' );

										// Filter the row for allowed html tags.
										if ( isset( $row[ $i ] ) ) {
											$row[ $i ] = wp_check_invalid_utf8( wp_unslash( $row[ $i ] ) );
											$row[ $i ] = wp_kses( trim( $row[ $i ] ), $allowed_html );
										} else {

											// if no data in row.
											$row[ $i ] = '';
										}
									}

									$quote_entries[] = array_combine( $header, $row );
								}
							}

							++$count;
						}
						fclose( $handle ); // phpcs:ignore

						// End Import CSV data.
						$result = $this->quotes_import( $quote_entries );
					}
				}

				if ( ! $result ) {
					return esc_html__( 'Import failed. Please try again.', 'quotes-llama' );
				} elseif ( 0 === $result ) {
					return esc_html__( 'No quotes imported', 'quotes-llama' );
				} else {
					/*
					 * Translators: Number of quotes imported.
					 */
					$importcount = esc_attr( _n( '%d quote imported', '%d quotes imported', $result, 'quotes-llama' ) );
					return sprintf( $importcount, $result );
				}

				return;
			}
		} else {
			return 'Check the database!';
		}
	}

	/**
	 * Import quotes from array provided by either .csv or .json formats.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $quotes_data - Array of quote data.
	 *
	 * @return mixed - int results count, or error string.
	 */
	protected function quotes_import( $quotes_data = array() ) {
		global $wpdb;

		// Check that we have import data.
		if ( ! $quotes_data ) {
			return 0;
		}

		// Check that we have a table to write to.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "quotes_llama'" ) === $wpdb->prefix . 'quotes_llama' ) { // phpcs:ignore
			$query = 'INSERT INTO ' . $wpdb->prefix . 'quotes_llama' .
				' (quote, title_name, first_name, last_name, source, img_url, author_icon, source_icon, category)' .
				' VALUES ';

			$values = array();
			foreach ( $quotes_data as $quote ) {
				$values[] = $wpdb->prepare(
					'(%s,%s,%s,%s,%s,%s,%s,%s,%s)',
					$quote['quote'],
					$quote['title_name'],
					$quote['first_name'],
					$quote['last_name'],
					$quote['source'],
					$quote['img_url'],
					$quote['author_icon'],
					$quote['source_icon'],
					$quote['category']
				);
			}

			// Combine statement with prepared values.
			$query .= implode( ",\n", $values );
			return $wpdb->query( $query ); // phpcs:ignore
		}
	}

	/**
	 * Handle error messages for file uploads and JSON decodes.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $error_code - The error code encountered.
	 *
	 * @return string - Description of the error.
	 */
	protected function error_messages( $error_code = 'none' ) {

		// If no error code provided it is a JSON error.
		if ( 'none' === $error_code ) {
			if ( ! function_exists( 'json_last_error_msg' ) ) {
				/**
				 * Handle error messages for validating JSON file data.
				 *
				 * @since 1.0.0
				 * @access public
				 *
				 * @return string - Description of the error.
				 */
				function json_last_error_msg() {
					static $e_r_r_o_r_s = array(
						JSON_ERROR_NONE           => 'No error.',
						JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded in the JSON file.',
						JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON).',
						JSON_ERROR_CTRL_CHAR      => 'Control character error, possibly incorrectly encoded JSON file.',
						JSON_ERROR_SYNTAX         => 'Import failed. Syntax error in the JSON file.',
						JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded JSON file.',
					);
					$error              = json_last_error();
					return isset( $e_r_r_o_r_s[ $error ] ) ? $e_r_r_o_r_s[ $error ] : 'Unknown error.';
				}
			}

			return json_last_error_msg();
		}

		switch ( $error_code ) {
			case UPLOAD_ERR_INI_SIZE:
				$message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = 'The uploaded file was only partially uploaded..';
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = 'No file was uploaded.';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = 'Missing a temporary folder.';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = 'Failed to write file to disk.';
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = 'File upload stopped by extension.';
				break;
			default:
				$message = 'File upload stopped by an unrecognized error.';
				break;
		}

		return $message;
	}
}
