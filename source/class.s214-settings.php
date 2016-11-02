<?php
/**
 * Section214 Settings Class
 *
 * @package     S214\Settings
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Section214 settings handler class
 *
 * @since       1.0.0
 */
class S214_Settings {


	/**
	 * @var         string $version The settings class version
	 * @since       1.0.0
	 */
	private $version = '1.2.2';


	/**
	 * @var         string $slug The plugin slug
	 * @since       1.0.0
	 */
	private $slug;


	/**
	 * @var         string $func The plugin slug for names
	 * @since       1.0.0
	 */
	private $func;


	/**
	 * @var         string $default_tab The default tab to display
	 * @since       1.0.0
	 */
	private $default_tab;


	/**
	 * @var         bool $show_title Whether or not to display the page title
	 * @since       1.0.3
	 */
	private $show_title;


    /**
	 * @var         bool page_title The page title
	 * @since       1.2.1
	 */
	private $page_title;


	/**
	 * @var         object $sysinfo The sysinfo object
	 * @since       1.1.0
	 */
	private $sysinfo;


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.1
	 * @param       string $slug The plugin slug
	 * @param       string $default_tab The default settings tab to display
	 * @return      void
	 */
	public function __construct( $slug = false, $default_tab = 'general' ) {
		// Bail if no slug is specified
		if( ! $slug ) {
			return;
		}

		// Setup plugin variables
		$this->slug        = $slug;
		$this->func        = str_replace( '-', '_', $slug );
		$this->default_tab = $default_tab;

		// Run action and filter hooks
		$this->hooks();

		// Setup the Sysinfo class
		if( ! class_exists( 'S214_Sysinfo' ) ) {
			require_once 'modules/sysinfo/class.s214-sysinfo.php';
		}
		$this->sysinfo = new S214_Sysinfo( $this->slug, $this->func, $this->version );
	}


	/**
	 * Run action and filter hooks
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      void
	 */
	private function hooks() {
		// Add the plugin setting page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 10 );

		// Register the plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( $this->func . '_settings_sanitize_text', array( $this, 'sanitize_text_field' ) );

		// Add styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );

		// Process actions
		add_action( 'admin_init', array( $this, 'process_actions' ) );

		// Handle tooltips
		add_filter( $this->func . '_after_setting_output', array( $this, 'add_setting_tooltip' ), 10, 2 );
	}


	/**
	 * Add settings pages
	 *
	 * @access      public
	 * @since       1.0.0
	 * @global      string ${this->func . '_settings_page'} The settings page slug
	 * @return      void
	 */
	public function add_settings_page() {
		global ${$this->func . '_settings_page'};

		$menu = apply_filters( $this->func . '_menu', array(
			'type'       => 'menu',
			'parent'     => 'options-general.php',
			'page_title' => __( 'Section214 Settings', 's214' ),
			'show_title' => false,
			'menu_title' => __( 'Section214 Settings', 's214' ),
			'capability' => 'manage_options',
			'icon'       => '',
			'position'   => null
		) );

		$this->show_title = $menu['show_title'];
        $this->page_title = $menu['page_title'];

		if( $menu['type'] == 'submenu' ) {
			${$this->func . '_settings_page'} = add_submenu_page( $menu['parent'], $menu['page_title'], $menu['menu_title'], $menu['capability'], $this->slug . '-settings', array( $this, 'render_settings_page' ) );
		} else {
			${$this->func . '_settings_page'} = add_menu_page( $menu['page_title'], $menu['menu_title'], $menu['capability'], $this->slug . '-settings', array( $this, 'render_settings_page' ), $menu['icon'], $menu['position'] );
		}
	}


	/**
	 * Render settings page
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function render_settings_page() {
		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->get_settings_tabs() ) ? $_GET['tab'] : $this->default_tab;
		$sections   = $registered_sections = $this->get_settings_tab_sections( $active_tab );
		$key        = 'main';

		if( is_array( $sections ) ) {
			$key = key( $sections );
		}

		$section = isset( $_GET['section'] ) && ! empty( $registered_sections ) && array_key_exists( $_GET['section'], $registered_sections ) ? $_GET['section'] : $key;

		ob_start();
		?>
		<div class="wrap">
			<?php if( $this->show_title ) { ?>
				<h2><?php echo $this->page_title; ?></h2>
			<?php } ?>
			<h2 class="nav-tab-wrapper">
				<?php
				foreach( $this->get_settings_tabs() as $tab_id => $tab_name ) {
					$tab_url = add_query_arg( array(
						'settings-updated' => false,
						'tab'              => $tab_id
					) );

					// Remove the section from the tabs so we always end up at the main section
					$tab_url = remove_query_arg( 'section', $tab_url );

					$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

					echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name )  . '" class="nav-tab' . $active . '">' . esc_html( $tab_name ) . '</a>';
				}
				?>
			</h2>
			<?php
			$number_of_sections = count( $sections );
			$number = 0;

			if( $number_of_sections > 1 ) {
				echo '<div><ul class="subsubsub">';

				foreach( $sections as $section_id => $section_name ) {
					echo '<li>';

					$number++;
					$tab_url = add_query_arg( array(
						'settings-updated' => false,
						'tab'              => $active_tab,
						'section'          => $section_id
					) );
					$class = '';

					if( $section == $section_id ) {
						$class = 'current';
					}

					echo '<a class="' . $class . '" href="' . esc_url( $tab_url ) . '">' . $section_name . '</a>';

					if( $number != $number_of_sections ) {
						echo ' | ';
					}

					echo '</li>';
				}

				echo '</ul></div>';
			}
			?>
			<div id="tab_container">
				<form method="post" action="options.php">
					<table class="form-table">
						<?php
						settings_fields( $this->func . '_settings' );

						do_action( $this->func . '_settings_tab_top_' . $active_tab . '_' . $section );

						do_settings_sections( $this->func . '_settings_' . $active_tab . '_' . $section );

						do_action( $this->func . '_settings_tab_bottom_' . $active_tab . '_' . $section );
						?>
					</table>
					<?php
					if( ! in_array( $active_tab, apply_filters( $this->func . '_unsavable_tabs', array() ) ) ) {
						submit_button();
					}
					?>
				</form>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}


	/**
	 * Retrieve the settings tabs
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      array $tabs The registered tabs for this plugin
	 */
	private function get_settings_tabs() {
		return apply_filters( $this->func . '_settings_tabs', array() );
	}


	/**
	 * Retrieve settings tab sections
	 *
	 * @access      public
	 * @since       1.0.1
	 * @param       string $tab The current tab
	 * @return      array $section The section items
	 */
	public function get_settings_tab_sections( $tab = false ) {
		$tabs     = false;
		$sections = $this->get_registered_settings_sections();

		if( $tab && ! empty( $sections[$tab] ) ) {
			$tabs = $sections[$tab];
		} elseif( $tab ) {
			$tabs = false;
		}

		return $tabs;
	}


	/**
	 * Retrieve the plugin settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array $settings The plugin settings
	 */
	public function get_registered_settings() {
		return apply_filters( $this->func . '_registered_settings', array() );
	}


	/**
	 * Retrieve the plugin settings sections
	 *
	 * @access      private
	 * @since       1.0.1
	 * @return      array $sections The registered sections
	 */
	private function get_registered_settings_sections() {
		global ${$this->func . '_sections'};

		if ( !empty( ${$this->func . '_sections'} ) ) {
			return ${$this->func . '_sections'};
		}

		${$this->func . '_sections'} = apply_filters( $this->func . '_registered_settings_sections', array() );

		return ${$this->func . '_sections'};
	}


	/**
	 * Retrieve an option
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $key The key to retrieve
	 * @param       mixed $default The default value if key doesn't exist
	 * @global      array ${$this->func . '_options'} The options array
	 * @return      mixed $value The value to return
	 */
	public function get_option( $key = '', $default = false ) {
		global ${$this->func . '_options'};

		$value = ! empty( ${$this->func . '_options'}[$key] ) ? ${$this->func . '_options'}[$key] : $default;
		$value = apply_filters( $this->func . '_get_option', $value, $key, $default );

		return apply_filters( $this->func . '_get_option_' . $key, $value, $key, $default );
	}


	/**
	 * Update an option
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $key The key to update
	 * @param       mixed $value The value to set key to
	 * @return      bool true if updated, false otherwise
	 */
	public function update_option( $key = '', $value = false ) {
		// Bail if no key is set
		if( empty( $key ) ) {
			return false;
		}

		if( empty( $value ) ) {
			$remove_option = $this->delete_option( $key );
			return $remove_option;
		}

		// Fetch a clean copy of the options array
		$options = get_option( $this->func . '_settings' );

		// Allow devs to modify the value
		$value = apply_filters( $this->func . '_update_option', $value, $key );

		// Try to update the option
		$options[$key] = $value;
		$did_update    = update_option( $this->func . '_settings', $options );

		// Update the global
		if( $did_update ) {
			global ${$this->func . '_options'};
			${$this->func . '_options'}[$key] = $value;
		}

		return $did_update;
	}


	/**
	 * Delete an option
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $key The key to delete
	 * @return      bool true if deleted, false otherwise
	 */
	public function delete_option( $key = '' ) {
		// Bail if no key is set
		if( empty( $key ) ) {
			return false;
		}

		// Fetch a clean copy of the options array
		$options = get_option( $this->func . '_settings' );

		// Try to unset the option
		if( isset( $options[$key] ) ) {
			unset( $options[$key] );
		}

		$did_update = update_option( $this->func . '_settings', $options );

		// Update the global
		if( $did_update ) {
			global ${$this->func . '_options'};
			${$this->func . '_options'} = $options;
		}

		return $did_update;
	}


	/**
	 * Retrieve all options
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array $settings The options array
	 */
	public function get_settings() {
		$settings = get_option( $this->func . '_settings' );

		if( empty( $settings ) ) {
			$settings = array();

			update_option( $this->func . '_settings', $settings );
		}

		return apply_filters( $this->func . '_get_settings', $settings );
	}


	/**
	 * Add settings sections and fields
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	function register_settings() {
		if( get_option( $this->func . '_settings' ) == false ) {
			add_option( $this->func . '_settings' );
		}

		foreach( $this->get_registered_settings() as $tab => $sections ) {
			foreach( $sections as $section => $settings ) {
				// Check for backwards compatibility
				$section_tabs = $this->get_settings_tab_sections( $tab );

				if( ! is_array( $section_tabs ) || ! array_key_exists( $section, $section_tabs ) ) {
					$section  = 'main';
					$settings = $sections;
				}

				add_settings_section(
					$this->func . '_settings_' . $tab . '_' . $section,
					__return_null(),
					'__return_false',
					$this->func . '_settings_' . $tab . '_' . $section
				);

				foreach( $settings as $option ) {
					// For backwards compatibility
					if( empty( $option['id'] ) ) {
						continue;
					}

					$name = isset( $option['name'] ) ? $option['name'] : '';

					add_settings_field(
						$this->func . '_settings[' . $option['id'] . ']',
						$name,
						function_exists( $this->func . '_' . $option['type'] . '_callback' ) ? $this->func . '_' . $option['type'] . '_callback' : ( method_exists( $this, $option['type'] . '_callback' ) ? array( $this, $option['type'] . '_callback' ) : array( $this, 'missing_callback' ) ),
						$this->func . '_settings_' . $tab . '_' . $section,
						$this->func . '_settings_' . $tab . '_' . $section,
						array(
							'section'       => $section,
							'id'            => isset( $option['id'] )            ? $option['id']             : null,
							'desc'          => ! empty( $option['desc'] )        ? $option['desc']           : '',
							'name'          => isset( $option['name'] )          ? $option['name']           : null,
							'size'          => isset( $option['size'] )          ? $option['size']           : null,
							'options'       => isset( $option['options'] )       ? $option['options']        : '',
							'std'           => isset( $option['std'] )           ? $option['std']            : '',
							'min'           => isset( $option['min'] )           ? $option['min']            : null,
							'max'           => isset( $option['max'] )           ? $option['max']            : null,
							'step'          => isset( $option['step'] )          ? $option['step']           : null,
							'select2'       => isset( $option['select2'] )       ? $option['select2']        : null,
							'placeholder'   => isset( $option['placeholder'] )   ? $option['placeholder']    : null,
							'multiple'      => isset( $option['multiple'] )      ? $option['multiple']       : null,
							'allow_blank'   => isset( $option['allow_blank'] )   ? $option['allow_blank']    : true,
							'readonly'      => isset( $option['readonly'] )      ? $option['readonly']       : false,
							'buttons'       => isset( $option['buttons'] )       ? $option['buttons']        : null,
							'wpautop'       => isset( $option['wpautop'] )       ? $option['wpautop']        : null,
							'teeny'         => isset( $option['teeny'] )         ? $option['teeny']          : null,
							'tab'           => isset( $option['tab'] )           ? $option['tab']            : null,
							'tooltip_title' => isset( $option['tooltip_title'] ) ? $option['tooltip_title']  : false,
							'tooltip_desc'  => isset( $option['tooltip_desc'] )  ? $option['tooltip_desc']   : false
						)
					);
				}
			}
		}

		register_setting( $this->func . '_settings', $this->func . '_settings', array( $this, 'settings_sanitize' ) );
	}


	/**
	 * Settings sanitization
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $input The value entered in the field
	 * @global      array ${$this->func . '_options'} The options array
	 * @return      string $input The sanitized value
	 */
	public function settings_sanitize( $input = array() ) {
		global ${$this->func . '_options'};

		$doing_section = false;

		if( ! empty( $_POST['_wp_http_referer'] ) ) {
			$doing_section = true;
		}

		$setting_types = $this->get_registered_settings_types();
		$input         = $input ? $input : array();

		if( $doing_section ) {
			parse_str( $_POST['_wp_http_referer'], $referrer );

			$tab     = isset( $referrer['tab'] ) ? $referrer['tab'] : $this->default_tab;
			$section = isset( $referrer['section'] ) ? $referrer['section'] : 'main';
			$input   = apply_filters( $this->func . '_settings_' . $tab . '_sanitize', $input );
			$input   = apply_filters( $this->func . '_settings_' . $tab . '-' . $section . '_sanitize', $input );
		}

		$output = array_merge( ${$this->func . '_options'}, $input );

		foreach( $setting_types as $key => $type ) {
			if( empty( $type ) ) {
				continue;
			}

			// Bypass non-setting settings
			$non_setting_types = apply_filters( $this->func . '_non_setting_types', array(
				'header', 'descriptive_text', 'hook'
			) );

			if( in_array( $type, $non_setting_types ) ) {
				continue;
			}

			if( array_key_exists( $key, $output ) ) {
				$output[$key] = apply_filters( $this->func . '_settings_sanitize_' . $type, $output[$key], $key );
				$output[$key] = apply_filters( $this->func . '_settings_sanitize', $output[$key], $key );
			}

			if( $doing_section ) {
				switch( $type ) {
					case 'checkbox':
						if( array_key_exists( $key, $input ) && $output[$key] === '-1' ) {
							unset( $output[$key] );
						}
						break;
					default:
						if( array_key_exists( $key, $input ) && empty( $input[$key] ) ) {
							unset( $output[$key] );
						}
						break;
				}
			} else {
				if( empty( $input[$key] ) ) {
					unset( $output[$key] );
				}
			}
		}

		if( $doing_section ) {
			add_settings_error( $this->slug . '-notices', '', __( 'Settings updated.', 's214-settings' ), 'updated' );
		}

		return $output;
	}


	/**
	 * Flattens the set of registered settings and their type so we can easily sanitize all settings
	 *
	 * @since       1.2.0
	 * @return      array Key is the setting ID, value is the type of setting it is registered as
	 */
	function get_registered_settings_types() {
		$settings      = $this->get_registered_settings();
		$setting_types = array();

		foreach( $settings as $tab ) {
			foreach( $tab as $section_or_setting ) {
				// See if we have a setting registered at the tab level for backwards compatibility
				if( is_array( $section_or_setting ) && array_key_exists( 'type', $section_or_setting ) ) {
					$setting_types[$section_or_setting['id']] = $section_or_setting['type'];
					continue;
				}

				foreach( $section_or_setting as $section => $section_settings ) {
					$setting_types[$section_settings['id']] = $section_settings['type'];
				}
			}
		}

		return $setting_types;
	}


	/**
	 * Sanitize text fields
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $input The value entered in the field
	 * @return      string $input The sanitized value
	 */
	public function sanitize_text_field( $input ) {
		return trim( wp_strip_all_tags( $input, true ) );
	}


	/**
	 * Header callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @return      void
	 */
	public function header_callback( $args ) {
		echo '<hr />';
	}


	/**
	 * Checkbox callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The plugin options
	 * @return      void
	 */
	public function checkbox_callback( $args ) {
		global ${$this->func . '_options'};

		$name    = ' name="' . $this->func . '_settings[' . $args['id'] . ']"';
		$checked = isset( ${$this->func . '_options'}[$args['id']] ) ? checked( 1, ${$this->func . '_options'}[$args['id']], false ) : '';

		$html  = '<input type="hidden"' . $name . ' value="-1" />';
		$html .= '<input type="checkbox" id="' . $this->func . '_settings[' . $args['id'] . ']"' . $name . ' value="1" ' . $checked . '/>&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Color callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the settings
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function color_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$default = isset( $args['std'] ) ? $args['std'] : '';
		$size    = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html  = '<input type="text" class="s214-color-picker" id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />&nbsp;';
		$html .= '<span class="s214-color-picker-label description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Descriptive text callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @return      void
	 */
	public function descriptive_text_callback( $args ) {
		$html = wp_kses_post( $args['desc'] );

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Editor callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function editor_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];

			if( empty( $args['allow_blank'] ) && empty( $value ) ) {
				$value = isset( $args['std'] ) ? $args['std'] : '';
			}
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$rows    = isset( $args['size'] ) ? $args['size'] : '10';
		$wpautop = isset( $args['wpautop'] ) ? $args['wpautop'] : true;
		$buttons = isset( $args['buttons'] ) ? $args['buttons'] : true;
		$teeny   = isset( $args['teeny'] ) ? $args['teeny'] : false;

		wp_editor(
			$value,
			$this->func . '_settings_' . $args['id'],
			array(
				'wpautop'       => $wpautop,
				'media_buttons' => $buttons,
				'textarea_name' => $this->func . '_settings[' . $args['id'] . ']',
				'textarea_rows' => $rows,
				'teeny'         => $teeny
			)
		);
		$html = '<br /><span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * HTML callback
	 *
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function html_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$html  = '<textarea class="large-text s214-html" cols="50" rows="5" id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Multicheck callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function multicheck_callback( $args ) {
		global ${$this->func . '_options'};

		if( ! empty( $args['options'] ) ) {
			$html = '';

			foreach( $args['options'] as $key => $option ) {
				if( isset( ${$this->func . '_options'}[$args['id']][$key] ) ) {
					$enabled = $option;
				} else {
					$enabled = isset( $args['std'][$key] ) ? $args['std'][$key] : NULL;
				}

				$html .= '<input name="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']" id="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked( $option, $enabled, false ) . ' />&nbsp;';
				$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br />';
			}
			$html .= '<p class="description">' . $args['desc'] . '</p>';

			echo apply_filters( $this->func . '_after_setting_output', $html, $args );
		}
	}


	/**
	 * Number callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function number_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$name     = ' name="' . $this->func . '_settings[' . $args['id'] . ']"';
		$max      = isset( $args['max'] ) ? $args['max'] : 999999;
		$min      = isset( $args['min'] ) ? $args['min'] : 0;
		$step     = isset( $args['step'] ) ? $args['step'] : 1;
		$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';

		$html  = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="' . $this->func . '_settings[' . $args['id'] . ']"' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . '/>&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Password callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the settings
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function password_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html  = '<input type="password" class="' . $size . '-text" id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']" value="' . esc_attr( $value )  . '" />&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Radio callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function radio_callback( $args ) {
		global ${$this->func . '_options'};

		if( ! empty( $args['options'] ) ) {
			$html = '';

			foreach( $args['options'] as $key => $option ) {
				$checked = false;

				if( isset( ${$this->func . '_options'}[$args['id']] ) && ${$this->func . '_options'}[$args['id']] == $key ) {
					$checked = true;
				} elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( ${$this->func . '_options'}[$args['id']] ) ) {
					$checked = true;
				}

				$html .= '<input name="' . $this->func . '_settings[' . $args['id'] . ']" id="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked( true, $checked, false ) . '/>&nbsp;';
				$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br />';
			}

			$html .= '<p class="description">' . $args['desc'] . '</p>';

			echo apply_filters( $this->func . '_after_setting_output', $html, $args );
		}
	}


	/**
	 * Select callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function select_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$select2     = isset( $args['select2'] ) ? ' class="s214-select2"' : '';
        $width       = isset( $args['size'] ) ? ' style="width: ' . $args['size'] . '"' : '';

		if( isset( $args['multiple'] ) && $args['multiple'] === true ) {
			$html = '<select id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . '][]"' . $select2 . ' data-placeholder="' . $placeholder . '" multiple="multiple"' . $width . ' />';
		} else {
			$html = '<select id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']"' . $select2 . ' data-placeholder="' . $placeholder . '"' . $width . ' />';
		}

		foreach( $args['options'] as $option => $name ) {
			if( isset( $args['multiple'] ) && $args['multiple'] === true ) {
				if( is_array( $value ) ) {
					$selected = ( in_array( $option, $value ) ? 'selected="selected"' : '' );
				} else {
					$selected = '';
				}
			} else {
				if( is_string( $value ) ) {
					$selected = selected( $option, $value, false );
				} else {
					$selected = '';
				}
			}

			$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
		}

		$html .= '</select>&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Sysinfo callback
	 *
	 * @since       1.1.0
	 * @param       array $args Arguements passed by the settings
	 * @return      void
	 */
	public function sysinfo_callback( $args ) {
		global ${$this->func . '_options'};

		if( ! isset( ${$this->func . '_options'}[$args['tab']] ) || ( isset( ${$this->func . '_options'}[$args['tab']] ) && isset( $_GET['tab'] ) && $_GET['tab'] == ${$this->func . '_options'}[$args['tab']] ) ) {
			$html  = '<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="' . $this->func . '-system-info" title="' . __( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 's214-settings' ) . '">' . $this->sysinfo->get_system_info() . '</textarea>';
			$html .= '<p class="submit">';
			$html .= '<input type="hidden" name="' . $this->slug . '-settings-action" value="download_system_info" />';
			$html .= '<a class="button button-primary" href="' . add_query_arg( $this->slug . '-settings-action', 'download_system_info' ) . '">' . __( 'Download System Info File', 's214-settings' ) . '</a>';
			$html .= '</p>';

			echo apply_filters( $this->func . '_after_setting_output', $html, $args );
		}
	}


	/**
	 * Text callback
	 *
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function text_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$name     = ' name="' . $this->func . '_settings[' . $args['id'] . ']"';
		$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
		$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html  = '<input type="text" class="' . $size . '-text" id="' . $this->func . '_settings[' . $args['id'] . ']"' . $name . ' value="' . esc_attr( stripslashes( $value ) )  . '"' . $readonly . '/>&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Textarea callback
	 *
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function textarea_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$html  = '<textarea class="large-text" cols="50" rows="5" id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Upload callback
	 *
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function upload_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html  = '<input type="text" class="' . $size . '-text" id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" />&nbsp;';
		$html .= '<span><input type="button" class="' . $this->func . '_settings_upload_button button-secondary" value="' . __( 'Upload File', 's214-settings' ) . '" /></span>&nbsp;';
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * License field callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @global      array ${$this->func . '_options'} The Beacon options
	 * @return      void
	 */
	public function license_key_callback( $args ) {
		global ${$this->func . '_options'};

		if( isset( ${$this->func . '_options'}[$args['id']] ) ) {
			$value = ${$this->func . '_options'}[$args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html = '<input type="text" class="' . $size . '-text" id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" />&nbsp;';

		if( get_option( $args['options']['is_valid_license_option'] ) ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License',  's214-settings' ) . '"/>';
		}
		$html .= '<span class="description"><label for="' . $this->func . '_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		wp_nonce_field( $args['id'] . '-nonce', $args['id'] . '-nonce' );

		echo apply_filters( $this->func . '_after_setting_output', $html, $args );
	}


	/**
	 * Hook callback
	 *
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @return      void
	 */
	public function hook_callback( $args ) {
		do_action( $this->func . '_' . $args['id'] );
	}


	/**
	 * Missing callback
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       array $args Arguments passed by the setting
	 * @return      void
	 */
	public function missing_callback( $args ) {
		printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 's214-settings' ), $args['id'] );
	}


	/**
	 * Check if we should load admin scripts
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $hook The hook for the current page
	 * @return      bool true if we should load scripts, false otherwise
	 */
	public function load_scripts( $hook ) {
		global $typenow, $pagenow, ${$this->func . '_settings_page'};

		$ret    = false;
		$pages  = apply_filters( $this->func . '_admin_pages', array( ${$this->func . '_settings_page'} ) );

		if( in_array( $hook, $pages ) ) {
			$ret = true;
		}

		return (bool) apply_filters( $this->func . 'load_scripts', $ret );
	}


	/**
	 * Processes all actions sent via POST and GET by looking for the '$func-settings-action'
	 * request and running do_action() to call the function
	 *
	 * @since       1.1.0
	 * @return      void
	 */
	function process_actions() {
		if( ! isset( $_POST['submit'] ) ) {
			if( isset( $_POST[$this->slug . '-settings-action'] ) ) {
				do_action( $this->func . '_settings_' . $_POST[$this->slug . '-settings-action'], $_POST );
			}

			if( isset( $_GET[$this->slug . '-settings-action'] ) ) {
				do_action( $this->func . '_settings_' . $_GET[$this->slug . '-settings-action'], $_GET );
			}
		}
	}


	/**
	 * Enqueue scripts
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       string $hook The current page hook
	 * @return      void
	 */
	public function enqueue_scripts( $hook ) {
		if( ! apply_filters( $this->func . '_load_admin_scripts', $this->load_scripts( $hook ), $hook ) ) {
			return;
		}

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix      = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$ui_style    = ( get_user_option( 'admin_color' ) == 'classic' ) ? 'classic' : 'fresh';
		$url_path    = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, dirname( __FILE__ ) );
		$select2_cdn = 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/';
		$cm_cdn      = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.14.2/';

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-tooltip' );
		wp_enqueue_media();
		wp_enqueue_style( 'jquery-ui-css', $url_path . '/assets/css/jquery-ui-' . $ui_style . '.min.css' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'select2', $select2_cdn . 'css/select2.min.css', array(), '4.0.2' );
		wp_enqueue_script( 'select2', $select2_cdn . 'js/select2.min.js', array( 'jquery' ), '4.0.2' );

		wp_enqueue_style( $this->slug . '-cm', $cm_cdn . 'codemirror.css', array(), '5.10' );
		wp_enqueue_script( $this->slug . '-cm', $cm_cdn . 'codemirror.js', array( 'jquery' ), '5.14.2' );
		wp_enqueue_script( $this->slug . '-cm-html', $cm_cdn . 'mode/htmlmixed/htmlmixed.js', array( 'jquery', $this->slug . '-cm' ), '5.14.2' );
		wp_enqueue_script( $this->slug . '-cm-xml', $cm_cdn . 'mode/xml/xml.js', array( 'jquery', $this->slug . '-cm' ), '5.14.2' );
		wp_enqueue_script( $this->slug . '-cm-js', $cm_cdn . 'mode/javascript/javascript.js', array( 'jquery', $this->slug . '-cm' ), '5.14.2' );
		wp_enqueue_script( $this->slug . '-cm-css', $cm_cdn . 'mode/css/css.js', array( 'jquery', $this->slug . '-cm' ), '5.14.2' );
		wp_enqueue_script( $this->slug . '-cm-php', $cm_cdn . 'mode/php/php.js', array( 'jquery', $this->slug . '-cm' ), '5.14.2' );
		wp_enqueue_script( $this->slug . '-cm-clike', $cm_cdn . 'mode/clike/clike.js', array( 'jquery', $this->slug . '-cm' ), '5.14.2' );

		wp_enqueue_style( $this->slug . '-s214-settings', $url_path . '/assets/css/admin' . $suffix . '.css', array(), $this->version );
		wp_enqueue_script( $this->slug . '-s214-settings', $url_path . '/assets/js/admin' . $suffix . '.js', array( 'jquery' ), $this->version );
		wp_localize_script( $this->slug . '-s214-settings', 's214_settings_vars', apply_filters( $this->func . 'localize_script', array(
			'func'               => $this->func,
			'image_media_button' => __( 'Insert Image', 's214-settings' ),
			'image_media_title'  => __( 'Select Image', 's214-settings' ),
		) ) );
	}


	/**
	 * Add tooltips
	 *
	 * @access      public
	 * @since       1.2.0
	 * @param       string $html The current field HTML
	 * @param       array $args Arguments passed to the field
	 * @return      string $html The updated field HTML
	 */
	function add_setting_tooltip( $html, $args ) {
		if( ! empty( $args['tooltip_title'] ) && ! empty( $args['tooltip_desc'] ) ) {
			$tooltip = '<span alt="f223" class="s214-help-tip dashicons dashicons-editor-help" title="<strong>' . $args['tooltip_title'] . '</strong>: ' . $args['tooltip_desc'] . '"></span>';
			$html .= $tooltip;
		}

		return $html;
	}
}
