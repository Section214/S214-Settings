<?php
/**
 * Section214 Settings Class
 *
 * @package     S214\Settings
 * @since       1.0.1
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
	public $version = '1.0.0';


	/**
	 * @var         string $slug The plugin slug
	 * @since       1.0.0
	 */
	public $slug;


	/**
	 * @var         string $func The plugin slug for names
	 * @since       1.0.0
	 */
	public $func;


	/**
	 * @var         string $default_tab The default tab to display
	 * @since       1.0.0
	 */
	public $default_tab;


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
		$this->slug         = $slug;
		$this->func         = str_replace( '-', '_', $slug );
		$this->default_tab  = $default_tab;

		// Run action and filter hooks
		$this->hooks();
	}


	/**
	 * Run action and filter hooks
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function hooks() {
		// Add the plugin setting page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 10 );

		// Register the plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( $this->func . '_settings_sanitize_text', array( $this, 'sanitize_text_field' ) );

		// Add styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );
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
			'type'          => 'menu',
			'parent'        => 'options-general.php',
			'page_title'	=> __( 'Section214 Settings', 's214' ),
			'menu_title'    => __( 'Section214 Settings', 's214' ),
			'capability'    => 'manage_options',
			'position'      => null
		) );

		if( $menu['type'] == 'submenu' ) {
			${$this->func . '_settings_page'} = add_submenu_page( $menu['parent'], $menu['page_title'], $menu['menu_title'], $menu['capability'], $this->slug . '-settings', array( $this, 'render_settings_page' ) );
		} else {
			${$this->func . '_settings_page'} = add_menu_page( $menu['page_title'], $menu['menu_title'], $menu['capability'], $this->slug . '-settings', array( $this, 'render_settings_page' ), '', $menu['position'] );
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
		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->get_settings_tabs() ) ? $_GET['tab'] : 'general';

		ob_start();
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php
				foreach( $this->get_settings_tabs() as $tab_id => $tab_name ) {
					$tab_url = add_query_arg( array(
						'settings-updated'  => false,
						'tab'               => $tab_id
					) );

					$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

					echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name )  . '" class="nav-tab' . $active . '">' . esc_html( $tab_name ) . '</a>';
				}
				?>
			</h2>
			<div id="tab_container">
				<form method="post" action="options.php">
					<table class="form-table">
						<?php
						settings_fields( $this->func . '_settings' );
						do_settings_fields( $this->func . '_settings_' . $active_tab, $this->func . '_settings_' . $active_tab );
						?>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}


	/**
	 * Retrieve the settings tabs
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array $tabs The registered tabs for this plugin
	 */
	public function get_settings_tabs() {
		return apply_filters( $this->func . '_settings_tabs', array() );
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
		$did_update = update_option( $this->func . '_settings', $options );

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

		foreach( $this->get_registered_settings() as $tab => $settings ) {
			add_settings_section(
				$this->func . '_settings_' . $tab,
				__return_null(),
				'__return_false',
				$this->func . '_settings_' . $tab
			);

			foreach( $settings as $option ) {
				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					$this->func . '_settings[' . $option['id'] . ']',
					$name,
					function_exists( $this->func . '_' . $option['type'] . '_callback' ) ? $this->func . '_' . $option['type'] . '_callback' : ( method_exists( $this, $option['type'] . '_callback' ) ? array( $this, $option['type'] . '_callback' ) : array( $this, 'missing_callback' ) ),
					$this->func . '_settings_' . $tab,
					$this->func . '_settings_' . $tab,
					array(
						'section'       => $tab,
						'id'            => isset( $option['id'] )           ? $option['id']             : null,
						'desc'          => ! empty( $option['desc'] )       ? $option['desc']           : '',
						'name'          => isset( $option['name'] )         ? $option['name']           : null,
						'size'          => isset( $option['size'] )         ? $option['size']           : null,
						'options'       => isset( $option['options'] )      ? $option['options']        : '',
						'std'           => isset( $option['std'] )          ? $option['std']            : '',
						'min'           => isset( $option['min'] )          ? $option['min']            : null,
						'max'           => isset( $option['max'] )          ? $option['max']            : null,
						'step'          => isset( $option['step'] )         ? $option['step']           : null,
						'select2'       => isset( $option['select2'] )      ? $option['select2']        : null,
						'placeholder'   => isset( $option['placeholder'] )  ? $option['placeholder']    : null,
						'multiple'      => isset( $option['multiple'] )     ? $option['multiple']       : null,
						'allow_blank'   => isset( $option['allow_blank'] )  ? $option['allow_blank']    : true,
						'readonly'      => isset( $option['readonly'] )     ? $option['readonly']       : false,
						'faux'          => isset( $option['faux'] )         ? $option['faux']           : false,
						'buttons'       => isset( $option['buttons'] )      ? $option['buttons']        : null,
						'wpautop'       => isset( $option['wpautop'] )      ? $option['wpautop']        : null,
						'teeny'         => isset( $option['teeny'] )        ? $option['teeny']          : null,
						'notice'        => isset( $option['notice'] )       ? $option['notice']         : false,
						'style'         => isset( $option['style'] )        ? $option['style']          : null,
						'header'        => isset( $option['header'] )       ? $option['header']         : null,
						'icon'          => isset( $option['icon'] )         ? $option['icon']           : null,
						'class'         => isset( $option['class'] )        ? $option['class']          : null
					)
				);
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

		if( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		parse_str( $_POST['_wp_http_referer'], $referrer );

		$settings   = $this->get_registered_settings();
		$tab        = isset( $referrer['tab'] ) ? $referrer['tab'] : $this->default_tab;

		$input = $input ? $input : array();
		$input = apply_filters( $this->func . '_settings_' . $tab . '_sanitize', $input );

		foreach( $input as $key => $value ) {
			$type = isset( $settings[$tab][$key]['type'] ) ? $settings[$tab][$key]['type'] : false;

			if( $type ) {
				// Field type specific filter
				$input[$key] = apply_filters( $this->func . '_settings_sanitize_' . $type, $value, $key );
			}

			// General filter
			$input[$key] = apply_filters( $this->func . '_settings_sanitize', $input[$key], $key );
		}

		if( ! empty( $settings[$tab] ) ) {
			foreach( $settings[$tab] as $key => $value ) {
				if( is_numeric( $key ) ) {
					$key = $value['id'];
				}

				if( empty( $input[$key] ) || ! isset( $input[$key] ) ) {
					unset( ${$this->func . '_options'}[$key] );
				}
			}
		}

		// Merge our new settings with the existing
		$input = array_merge( ${$this->func . '_options'}, $input );

		add_settings_error( $this->slug . '-notices', '', __( 'Settings updated.', 's214-settings' ), 'updated' );

		return $input;
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
		return trim( $input );
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

		if( isset( $args['faux'] ) && $args['faux'] === true ) {
			$name = '';
		} else {
			$name = ' name="' . $this->func . '_settings[' . $args['id'] . ']"';
		}

		$checked = isset( ${$this->func . '_options'}[$args['id']] ) ? checked( 1, ${$this->func . '_options'}[$args['id']], false ) : '';

		$html  = '<input type="checkbox" id="' . $this->func . '_settings[' . $args['id'] . ']"' . $name . ' value="1" ' . $checked . '/>&nbsp;';
		$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';

		echo $html;
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
		$html .= '<span class="s214-color-picker-label"><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo $html;
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
		echo wp_kses_post( $args['desc'] );
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

		$rows       = isset( $args['size'] ) ? $args['size'] : '10';
		$wpautop    = isset( $args['wpautop'] ) ? $args['wpautop'] : true;
		$buttons    = isset( $args['buttons'] ) ? $args['buttons'] : true;
		$teeny      = isset( $args['teeny'] ) ? $args['teeny'] : false;

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
		echo '<br /><label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';
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
			foreach( $args['options'] as $key => $option ) {
				$enabled = ( isset( ${$this->func . '_options'}[$args['id']][$key] ) ? $option : NULL );

				echo '<input name="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']" id="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked( $option, $enabled, false ) . ' />&nbsp;';
				echo '<label for="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br />';
			}
			echo '<p class="description">' . $args['desc'] . '</p>';
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

		if( isset( $args['faux'] ) && $args['faux'] === true ) {
			$args['readonly'] = true;
			$value = isset( $args['std'] ) ? $args['std'] : '';
			$name  = '';
		} else {
			$name  = ' name="' . $this->func . '_settings[' . $args['id'] . ']"';
		}

		$max    = isset( $args['max'] ) ? $args['max'] : 999999;
		$min    = isset( $args['min'] ) ? $args['min'] : 0;
		$step   = isset( $args['step'] ) ? $args['step'] : 1;
		$size   = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html  = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="' . $this->func . '_settings[' . $args['id'] . ']"' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . '/>&nbsp;';
		$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';

		echo $html;
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
		$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';

		echo $html;
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
			foreach( $args['options'] as $key => $option ) {
				$checked = false;

				if( isset( ${$this->func . '_options'}[$args['id']] ) && ${$this->func . '_options'}[$args['id']] == $key ) {
					$checked = true;
				} elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( ${$this->func . '_options'}[$args['id']] ) ) {
					$checked = true;
				}

				echo '<input name="' . $this->func . '_settings[' . $args['id'] . ']" id="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked( true, $checked, false ) . '/>&nbsp;';
				echo '<label for="' . $this->func . '_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br />';
			}

			echo '<p class="description">' . $args['desc'] . '</p>';
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

		if( isset( $args['multiple'] ) && $args['multiple'] === true ) {
			$html = '<select id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . '][]"' . $select2 . ' data-placeholder="' . $placeholder . '" multiple="multiple" />';
		} else {
			$html = '<select id="' . $this->func . '_settings[' . $args['id'] . ']" name="' . $this->func . '_settings[' . $args['id'] . ']"' . $select2 . ' data-placeholder="' . $placeholder . '" />';
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
		$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';

		echo $html;
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

		if( isset( $args['faux'] ) && $args['faux'] === true ) {
			$args['readonly'] = true;
			$value = isset( $args['std'] ) ? $args['std'] : '';
			$name  = '';
		} else {
			$name  = ' name="' . $this->func . '_settings[' . $args['id'] . ']"';
		}

		$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
		$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html  = '<input type="text" class="' . $size . '-text" id="' . $this->func . '_settings[' . $args['id'] . ']"' . $name . ' value="' . esc_attr( stripslashes( $value ) )  . '"' . $readonly . '/>&nbsp;';
		$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';

		echo $html;
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
		$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';

		echo $html;
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
		$html .= '<label for="' . $this->func . '_settings[' . $args['id'] . ']">' . $args['desc'] . '</label>';

		echo $html;
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
		$suffix     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$ui_style   = ( get_user_option( 'admin_color' ) == 'classic' ) ? 'classic' : 'fresh';
		$url_path   = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, dirname( __FILE__ ) );

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_style( 'jquery-ui-css', $url_path . '/assets/css/jquery-ui-' . $ui_style . $suffix . '.css' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css', array(), '4.0.0' );
		wp_enqueue_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js', array( 'jquery' ), '4.0.0' );

		wp_enqueue_style( $this->slug, $url_path . '/assets/css/admin' . $suffix . '.css', array(), $this->version );
		wp_enqueue_script( $this->slug, $url_path . '/assets/js/admin' . $suffix . '.js', array( 'jquery' ), $this->version );
		wp_localize_script( $this->slug, 's214_settings_vars', apply_filters( $this->func . 'localize_script', array(
			'func'                  => $this->func,
			'image_media_button'    => __( 'Insert Image', 's214-settings' ),
			'image_media_title'     => __( 'Select Image', 's214-settings' ),
		) ) );
	}
}