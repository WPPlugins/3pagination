<?php

/**
 * Plugin Name: 3pagination
 * Description: Reach any page with no more than 3 clicks
 * Version: 1.3.2b
 * Author: Michael Schröder <ms@ts-webdesign.net>
 * TextDomain: 3pagination
 * DomainPath: /languages
 */
if ( !class_exists( 'threepagination' ) ) {

	if ( function_exists( 'add_filter' ) ) {
		
		add_filter( 'plugins_loaded', array( 'threepagination', 'get_object' ) );
		
		// Upon deactivation
		register_uninstall_hook( __FILE__, array( 'threepagination', 'uninstall' ) );
	}

	class threepagination {

		/**
		 * Textdomain string
		 * 
		 * @var string 
		 */
		protected $textdomain;

		/**
		 * Class object
		 * 
		 * @var object 
		 */
		static $_object;

		/**
		 * Create class object
		 *
		 * @access public
		 * @return object $_object
		 * @since 0.1a
		 */
		public static function get_object() {

			if ( NULL == self::$_object ) {
				self::$_object = new self;
			}
			return self::$_object;
		}

		/**
		 * Class init 
		 * 
		 * @since 1.1
		 */
		public function __construct() {

			// Get files
			$this->include_files();

			// Set textdomain string
			add_filter( 'init', array( $this, 'set_textdomain' ), 1 );
			add_filter( 'init', array( $this, 'load_plugin_textdomain' ), 2 );

			add_filter( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			add_filter( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		}

		/**
		 * Include files
		 * 
		 * @since 1.2b 
		 */
		public function include_files() {

			require_once( plugin_dir_path( __FILE__ ) . 'class.settings.php' );
		}

		/**
		 * Set plugin's textdomain
		 * 
		 * @since 1.2b 
		 */
		public function set_textdomain() {

			$this->textdomain = '3pagination';
		}

		public function load_plugin_textdomain() {

			load_plugin_textdomain( $this->textdomain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Load admin scripts
		 * 
		 * @since 1.2b 
		 */
		public function admin_scripts() {

			wp_enqueue_style( 'threepagination-css', plugins_url( 'examples/style.css', __FILE__ ) );
		}

		/**
		 * Load frontend scripts
		 * 
		 * @since 1.2b
		 */
		public function frontend_scripts() {

			wp_enqueue_style( 'threepagination-css', plugins_url( 'examples/style.css', __FILE__ ) );

			wp_enqueue_script( '3pagination-js', plugins_url( '/js/3pagination.js', __FILE__ ), array( 'jquery', 'json2' ) );
			wp_localize_script( '3pagination-js', 'threepag_vars', $this->frontend_vars() );
		}

		/**
		 * Set frontend vars
		 * 
		 * @TODO: Pages, Custom post types?
		 * 
		 * @return type 
		 * @since 1.2b
		 */
		private function frontend_vars() {

			$vars = array( );

			$settings = get_option( '3pagination_settings' );

			// Check placement
			if ( 'on' == $this->init_var( $settings, 'placement_header_index' ) && is_home() ||
					'on' == $this->init_var( $settings, 'placement_header_archive' ) && is_archive() ||
					'on' == $this->init_var( $settings, 'placement_header_category' ) && is_category() ||
					'on' == $this->init_var( $settings, 'placement_header_search' ) && is_search() )
				$vars[ 'placement_header' ] = TRUE;

			if ( 'on' == $this->init_var( $settings, 'placement_footer_index' ) && is_home() ||
					'on' == $this->init_var( $settings, 'placement_footer_archive' ) && is_archive() ||
					'on' == $this->init_var( $settings, 'placement_footer_category' ) && is_category() ||
					'on' == $this->init_var( $settings, 'placement_footer_search' ) && is_search() )
				$vars[ 'placement_footer' ] = TRUE;

			if ( 'on' == $this->init_var( $settings, 'placement_prepend_index' ) && is_home() ||
					'on' == $this->init_var( $settings, 'placement_prepend_archive' ) && is_archive() ||
					'on' == $this->init_var( $settings, 'placement_prepend_category' ) && is_category() ||
					'on' == $this->init_var( $settings, 'placement_prepend_search' ) && is_search() ) {
				$vars[ 'placement_prepend' ] = TRUE;
				$vars[ 'placement_prepend_id' ] = $settings[ 'placement_prepend_id' ];
			}

			if ( 'on' == $this->init_var( $settings, 'placement_append_index' ) && is_home() ||
					'on' == $this->init_var( $settings, 'placement_append_archive' ) && is_archive() ||
					'on' == $this->init_var( $settings, 'placement_append_category' ) && is_category() ||
					'on' == $this->init_var( $settings, 'placement_append_search' ) && is_search() ) {
				$vars[ 'placement_append' ] = TRUE;
				$vars[ 'placement_append_id' ] = $settings[ 'placement_append_id' ];
			}

			// HTML output
			$vars[ 'html' ] = json_encode( self::get() );

			return $vars;
		}

		/**
		 * Returns a HTML string containing the navigation.
		 * 
		 * @global object $wp_query | the current query object used to gather pagination information
		 * @global type $wp
		 * @param bool $pretty | pretty permalink strukture. TRUE or FALSE
		 * @param int $max_num_pages | total page count
		 * @param bool $labels | show labels, TRUE or FALSE
		 * @param string $css | the css class name appended to the 'threepagination' wrapper div
		 * @return void 
		 * @since 0.1a
		 */
		public static function get( $pretty = TRUE, $max_num_pages = FALSE, $labels = TRUE, $css = 'classic', $wp_query = FALSE ) {

			global $wp;
		
			// Set current query object
			if ( FALSE == $wp_query || ! is_object( $wp_query ) )
				global $wp_query;
			
			// Get global settings
			$settings = get_option( '3pagination_settings' );

			// Permalink structure
			$pretty = ( 'on' == $settings[ 'other_pretty' ] ) ? TRUE : FALSE;

			// Get the page count. 
			$total_pages = ( FALSE == $max_num_pages ) ? $wp_query->max_num_pages : $max_num_pages;

			// No need for navi
			if ( 1 == $total_pages )
				return;

			// For now, 3pagination supports up to 999 pages only
			if ( intval( $settings[ 'other_maxnumpages' ] ) < $total_pages )
				$total_pages = intval( $settings[ 'other_maxnumpages' ] );

			// Get currently visited page 
			$on_page = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

			$digits = strlen( $on_page );

			$page_string = '';

			// Start some complicated calculations. The benfit should
			// be a navigation that lets you navigate to any page between 1 and 999
			// without more than 3 mouse clicks. Maximum page number is 999.
			switch ( $digits ) {
				case 1:
				case 2:
					for ( $i = 1; $i <= $total_pages; ++$i ) {

						if ( $i == 1
								OR ($i <= 10 AND $digits == 1)
								OR ($i < 100 AND substr( $i, -2, 1 ) == substr( $on_page, -2, 1 ) AND $digits == strlen( $i ))
								OR ($i >= 10 AND $i < 100 AND is_int( $i / 10 ))
								OR (is_int( $i / 100 ))
								OR ($i == $total_pages)
						) {
							$url = self::url( $wp, $i, $pretty );
							$page_string .= ( $i == $on_page ) ? "<span class='page-numbers current'>" . $i . "</span>" : "<a class='page-numbers' href='" . $url . "'>" . $i . "</a>";
							if ( $i < $total_pages ) {
								$page_string .= "&nbsp;";
							}
						}
					}
					break;

				case 3:
					for ( $i = 1; $i <= $total_pages; ++$i ) {

						if ( $i == 1
								OR ($i >= 100 AND substr( $i, -3, 2 ) == substr( $on_page, -3, 2 ))
								OR ($i >= 100 AND is_int( $i / 10 ) AND substr( $i, -3, 1 ) == substr( $on_page, -3, 1 ))
								OR (is_int( $i / 100 ))
								OR ($i == $total_pages)
						) {
							$url = self::url( $wp, $i, $pretty );
							$page_string .= ( $i == $on_page ) ? "<span class='page-numbers current'>" . $i . "</span>" : "<a href='" . $url . "'>" . $i . "</a>";
							if ( $i < $total_pages ) {
								$page_string .= "&nbsp;";
							}
						}
					}
					break;

				default:
					for ( $i = 1; $i <= 999; ++$i ) {

						if ( $i == 1
								OR ($i >= 100 AND substr( $i, -3, 2 ) == substr( $on_page, -3, 2 ))
								OR ($i >= 100 AND is_int( $i / 10 ) AND substr( $i, -3, 1 ) == substr( $on_page, -3, 1 ))
								OR (is_int( $i / 100 ))
								OR ($i == $total_pages)
						) {
							$url = self::url( $wp, $i, $pretty );
							$page_string .= ( $i == $on_page ) ? "<span class='page-numbers current'>" . $i . "</span>" : "<a href='" . $url . "'>" . $i . "</a>";
							if ( $i < $total_pages ) {
								$page_string .= "&nbsp;";
							}
						}
					}
					break;
			}

			$css = isset( $settings[ 'css_class' ] ) ? $settings[ 'css_class' ] : $css;

			// Navigation labels
			if ( FALSE !== $labels && 'on' == $settings[ 'labels_show' ] ) {

				if ( $on_page > 1 ) {
					$i = $on_page - 1;
					$page_string = "<a class='page-numbers label-first' href='" . self::url( $wp, 1, $pretty ) . "'>" . self::init_var( $settings, 'labels_first', '&laquo;', TRUE ) . "</a>&nbsp;" . $page_string;
					$page_string = "<a class='page-numbers label-previous' href='" . self::url( $wp, $i, $pretty ) . "'>" . self::init_var( $settings, 'labels_previous', '&lsaquo;', TRUE ) . "</a>&nbsp;" . $page_string;
				}

				if ( $on_page < $total_pages ) {
					$i = $on_page + 1;
					$page_string .= "&nbsp;<a class='page-numbers label-last' href='" . self::url( $wp, $total_pages, $pretty ) . "'>" . self::init_var( $settings, 'labels_last', '&raquo;', TRUE ) . "</a>";
					$page_string .= "&nbsp;<a class='page-numbers label-next' href='" . self::url( $wp, $i, $pretty ) . "'>" . self::init_var( $settings, 'labels_next', '&rsaquo;', TRUE ) . "</a>";
				}
			}

			// Glue together the HTML string
			$page_string = "<div class='threepagination $css'><div class='threepagination-pages'>" . $page_string . "</div></div>";

			// Return string
			return $page_string;
		}

		/**
		 * Main display function. Should be called in a static fashion:
		 * threepagination::draw();
		 * 
		 * @global object $wp_query | the current query object used to gather pagination information
		 * @global type $wp
		 * @param bool $pretty | pretty permalink structure. TRUE or FALSE, defaults to TRUE
		 * @param int $num_items | can be used to override the global number of items
		 * @param int $per_page | can be used to override the global posts per page
		 * @param bool $labels | show labels, TRUE or FALSE
		 * @return void 
		 * 
		 * @since 0.1a
		 */
		public static function draw( $pretty = TRUE, $max_num_pages = FALSE, $labels = TRUE, $css = 'classic', $wp_query = FALSE ) {

			echo self::get( $pretty, $max_num_pages, $labels, $css, $wp_query );
		}

		/**
		 * Create link href
		 * 
		 * @TODO: _always_ append possibly existing URL parameters
		 * 
		 * @param object $wp | WP object
		 * @param int $i | current element
		 * @param bool $pretty | pretty permalink structure. TRUE or FALSE, defaults to TRUE
		 * @return string $url | the href attribute of our pagination element link
		 */
		private static function url( $wp, $i, $pretty ) {

			// Pretty URLs
			if ( TRUE == $pretty ) {
				if ( get_query_var( 'paged' ) )
					$url = preg_replace( '!(/page/\d+)/?$!', '/page/' . $i, home_url( $wp->request ) );
				else
					$url = home_url( $wp->request ) . '/page/' . $i;
			}
			// Default URLs
			else 
				$url = home_url( $wp->request ) . '?paged=' . $i;

			// Does the current URL have any parameters? If so,
			// we will always append them to the new link url, even
			// if pretty URLs = on
			$base_url = 'http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
			if ( strpos( $base_url, '=' ) ) {
				// has at least one param
				$params = parse_url( $base_url );
				$params_default = preg_replace( '!&?paged=\d{1,3}&?!', '', $params[ 'query' ] );

				if ( TRUE == $pretty )
					$url.= '?' . $params[ 'query' ];
				else if ( '' == $params_default )
					$url.= '';
				else
					$url.= '&' . $params_default;
			}			

			return $url;
		}

		/**
		 * For not getting pissed of too much by PHP notices. This function should
		 * help to keep the "flow" of the code, i.e. limiting the amount of conditional
		 * statements in HTML blocks, etc.
		 * 
		 * Example use: selected( $this->init_var( $var2, $index ), $var )
		 * Instead of: if( !empty( $var2[ $index ] ) ) : selected( $var2[ $index ], $var ); endif;
		 * 
		 * @access	public
		 * @param	var $var | the variable to check
		 * @param	string $index | the index of the variable
		 * @param	string, boolean $default | var default value
		 * @param	bool $override_set_empty | Set var to default if it is emtpy
		 * @return	var $var[ $index ] | the value of $var[ $index ]
		 * @since	0.1a
		 */
		public function init_var( $var, $index, $default = FALSE, $override_set_empty = FALSE ) {

			// is the $index of $var not yet set or (optional) set but empty?
			if ( !isset( $var[ $index ] ) || ( TRUE == $override_set_empty && empty( $var[ $index ] ) ) )
				$var[ $index ] = ( FALSE == $default ) ? FALSE : $default;

			return $var[ $index ];
		}
		
		/**
		 * Cleanup
		 * 
		 * @since 1.3b 
		 */
		public static function uninstall() {
			
			delete_option( '3pagination_settings' );
		}

	}

}
?>