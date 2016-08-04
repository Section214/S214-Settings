<?php
/**
 * System Info handler for Section214
 *
 * @package     S214\Sysinfo
 * @since       1.1.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Section214 system info handler class
 *
 * @since       1.1.0
 */
class S214_Sysinfo {


	/**
	 * @var         string $slug The plugin slug
	 * @since       1.1.0
	 */
	private $slug;


	/**
	 * @var         string $func The plugin slug for names
	 * @since       1.1.0
	 */
	private $func;


	/**
	 * @var         string $ver The library version
	 * @since       1.1.0
	 */
	private $version;


	/**
	 * Class constructor
	 *
	 * @access      public
	 * @since       1.1.0
	 * @param       string $_slug
	 * @param       string $_func
	 * @return      void
	 */
	public function __construct( $_slug, $_func, $_version ) {
		$this->slug    = $_slug;
		$this->func    = $_func;
		$this->version = $_version;

		// Run action and filter hooks
		$this->hooks();
	}


	/**
	 * Run action and filter hooks
	 *
	 * @access      private
	 * @since       1.1.0
	 * @return      void
	 */
	private function hooks() {
		// Process sysinfo download
		add_action( $this->func . '_settings_download_system_info', array( $this,  'download_system_info' ) );
	}


	/**
	 * Get system info
	 *
	 * @access      public
	 * @since       1.1.0
	 * @global      object $wpdb The WordPress database object
	 * @return      string $return The system info to display
	 */
	public function get_system_info() {
		global $wpdb;

		if( ! class_exists( 'Browser' ) ) {
			require_once 'browser.php';
		}

		$browser = new Browser();

		// Get theme info
		if( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		}

		// Try to identify the hosting provider
		$host = $this->get_host();

		$return  = '### Begin System Info ###' . "\n\n";

		// Start with the basics...
		$return .= '-- Site Info' . "\n\n";
		$return .= 'Site URL:                 ' . site_url() . "\n";
		$return .= 'Home URL:                 ' . home_url() . "\n";
		$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

		$return  = apply_filters( $this->func . '_sysinfo_after_site_info', $return );

		// Can we determine the site's host?
		if( $host ) {
			$return .= "\n" . '-- Hosting Provider' . "\n\n";
			$return .= 'Host:                     ' . $host . "\n";

			$return  = apply_filters( $this->func . '_sysinfo_after_host_info', $return );
		}

		// The local users' browser information, handled by the Browser class
		$return .= "\n" . '-- User Browser' . "\n\n";
		$return .= $browser;

		$return  = apply_filters( $this->func . '_sysinfo_after_user_browser', $return );

		// WordPress configuration
		$return .= "\n" . '-- WordPress Configuration' . "\n\n";
		$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
		$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
		$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
		$return .= 'Active Theme:             ' . $theme . "\n";
		$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

		// Only show page specs if frontpage is set to 'page'
		if( get_option( 'show_on_front' ) == 'page' ) {
			$front_page_id = get_option( 'page_on_front' );
			$blog_page_id  = get_option( 'page_for_posts' );

			$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
			$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
		}

		// Make sure wp_remote_post() is working
		$request['cmd'] = '_notify-validate';

		$params = array(
			'sslverify'  => false,
			'timeout'    => 60,
			'user-agent' => 'S214-Settings/' . $this->version,
			'body'       => $request
		);

		$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

		if( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$WP_REMOTE_POST = 'wp_remote_post() works';
		} else {
			$WP_REMOTE_POST = 'wp_remote_post() does not work';
		}

		$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
		$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
		$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
		$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

		$return  = apply_filters( $this->func . '_sysinfo_after_wordpress_config', $return );

		// Get plugins that have an update
		$updates = get_plugin_updates();

		// Must-use plugins
		// NOTE: MU plugins can't show updates!
		$muplugins = get_mu_plugins();
		if( count( $muplugins > 0 ) ) {
			$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

			foreach( $muplugins as $plugin => $plugin_data ) {
				$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
			}

			$return = apply_filters( $this->func . '_sysinfo_after_wordpress_mu_plugins', $return );
		}

		// WordPress active plugins
		$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach( $plugins as $plugin_path => $plugin ) {
			if( ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$return  = apply_filters( $this->func . '_sysinfo_after_wordpress_plugins', $return );

		// WordPress inactive plugins
		$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

		foreach( $plugins as $plugin_path => $plugin ) {
			if( in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$return = apply_filters( $this->func . '_sysinfo_after_wordpress_plugins_inactive', $return );

		if( is_multisite() ) {
			// WordPress Multisite active plugins
			$return .= "\n" . '-- Network Active Plugins' . "\n\n";

			$plugins = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			foreach( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );

				if( ! array_key_exists( $plugin_base, $active_plugins ) ) {
					continue;
				}

				$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
				$plugin  = get_plugin_data( $plugin_path );
				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			$return = apply_filters( $this->func . '_sysinfo_after_wordpress_ms_plugins', $return );
		}

		// Server configuration (really just versioning)
		$return .= "\n" . '-- Webserver Configuration' . "\n\n";
		$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
		$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

		$return  = apply_filters( $this->func . '_sysinfo_after_webserver_config', $return );

		// PHP configs... now we're getting to the important stuff
		$return .= "\n" . '-- PHP Configuration' . "\n\n";
		$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
		$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

		$return  = apply_filters( $this->func . '_sysinfo_after_php_config', $return );

		// PHP extensions and such
		$return .= "\n" . '-- PHP Extensions' . "\n\n";
		$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
		$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

		$return  = apply_filters( $this->func . '_sysinfo_after_php_ext', $return );

		$return .= "\n" . '### End System Info ###';

		return $return;
	}


	/**
	 * Generates a System Info download file
	 *
	 * @param       public
	 * @since       1.1.0
	 * @return      void
	 */
	public function download_system_info() {
		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $this->slug . '-system-info.txt"' );

		echo wp_strip_all_tags( $this->get_system_info() );
		die();
	}


	/**
	 * Get AJAX URL
	 *
	 * @access      public
	 * @since       1.1.0
	 * @return      string URL to the AJAX file to call during AJAX requests.
	 */
	function get_ajax_url() {
		$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

		$current_url = $this->get_current_page_url();
		$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

		if( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
			$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
		}

		return apply_filters( $this->func . '_ajax_url', $ajax_url );
	}


	/**
	 * Get the current page URL
	 *
	 * @access      public
	 * @since       1.1.0
	 * @param       bool $nocache If we should bust cache on the returned URL
	 * @return      string $page_url Current page URL
	 */
	function get_current_page_url( $nocache = false ) {
		global $wp;

		if( get_option( 'permalink_structure' ) ) {
			$base = trailingslashit( home_url( $wp->request ) );
		} else {
			$base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
			$base = remove_query_arg( array( 'post_type', 'name' ), $base );
		}

		$scheme = is_ssl() ? 'https' : 'http';
		$uri    = set_url_scheme( $base, $scheme );

		if( is_front_page() ) {
			$uri = home_url( '/' );
		}

		$uri = apply_filters( $this->func . '_get_current_page_url', $uri );

		if ( $nocache ) {
			$uri = $this->add_cache_busting( $uri );
		}

		return $uri;
	}


	/**
	 * Adds the 'nocache' parameter to the provided URL
	 *
	 * @access      public
	 * @since       1.1.0
	 * @param       string $url The URL being requested
	 * @return      string The URL with cache busting added or not
	 */
	function add_cache_busting( $url = '' ) {
		if( $this->is_caching_plugin_active() ) {
			$url = add_query_arg( 'nocache', 'true', $url );
		}

		return $url;
	}


	/**
	 * Checks if a caching plugin is active
	 *
	 * @access      public
	 * @since       1.1.0
	 * @return      bool $caching True if caching plugin is enabled, false otherwise
	 */
	function is_caching_plugin_active() {
		$caching = ( function_exists( 'wpsupercache_site_admin' ) || defined( 'W3TC' ) || function_exists( 'rocket_init' ) );
		return apply_filters( $this->func . '_is_caching_plugin_active', $caching );
	}


	/**
	 * Get user host
	 *
	 * @access      public
	 * @since       1.1.0
	 * @return      mixed string $host if detected, fallback data otherwise
	 */
	function get_host() {
		if( defined( 'WPE_APIKEY' ) ) {
			$host = 'WP Engine';
		} elseif( defined( 'PAGELYBIN' ) ) {
			$host = 'Pagely';
		} elseif( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
			$host = 'ICDSoft';
		} elseif( DB_HOST == 'mysqlv5' ) {
			$host = 'NetworkSolutions';
		} elseif( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
			$host = 'iPage';
		} elseif( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
			$host = 'IPower';
		} elseif( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
			$host = 'MediaTemple Grid';
		} elseif( strpos( DB_HOST, '.pair.com' ) !== false ) {
			$host = 'pair Networks';
		} elseif( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
			$host = 'Rackspace Cloud';
		} elseif( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
			$host = 'SysFix.eu Power Hosting';
		} elseif( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
			$host = 'Flywheel';
		} else {
			// Adding a general fallback for data gathering
			$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
		}

		return $host;
	}


	/**
	 * Get user IP
	 *
	 * @access      public
	 * @since       1.1.0
	 * @return      string $ip User's IP address
	 */
	function get_ip() {
		$ip = '127.0.0.1';

		if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// Check if IP is from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Check if IP is passed from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return apply_filters( $this->func . '_get_ip', $ip );
	}
}
