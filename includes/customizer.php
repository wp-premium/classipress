<?php
/**
 * Functions that hook into WordPress to allow customizing the theme using the customizer.
 */

add_action( 'customize_controls_enqueue_scripts', '_cp_enqueue_customizer_color_previewer' );
add_action( 'after_setup_theme', 'cp_custom_header_setup' );

add_action( 'customize_register', '_cp_customize_color_scheme' );
add_action( 'customize_register', '_cp_customize_site' );
add_action( 'customize_register', '_cp_customize_footer' );

add_action( 'wp_head', '_cp_customize_css', 999 );


### Hooks Callbacks

/**
 * Updates the color pickers using javascript each time the user changes the theme color scheme.
 */
function _cp_enqueue_customizer_color_previewer() {
	global $cp_options;

	$min = cp_get_enqueue_suffix();

	wp_enqueue_script( 'cp_themecustomizer', get_template_directory_uri()."/includes/js/theme-customizer{$min}.js", array( 'customize-controls' ), CP_VERSION, true );

	$params = array(
		'color_scheme' => $cp_options->stylesheet,
		'colors'       => cp_get_customizer_color_defaults('all'),
	);

	wp_localize_script( 'cp_themecustomizer', 'customizer_params', $params );
}


/**
 * Set up the WordPress core custom header arguments and settings.
 *
 * @uses add_theme_support() to register support for 3.4 and up.
 * @uses cp_header_style() to style front-end.
 * @uses cp_admin_header_style() to style wp-admin form.
 * @uses cp_admin_header_image() to add custom markup to wp-admin form.
 *
 * @return void
 */
function cp_custom_header_setup() {
	global $cp_options;

	if ( strpos( $cp_options->stylesheet, 'black' ) !== false ) {
		$default_text_color = '#EFEFEF';
		$default_image = appthemes_locate_template_uri( 'images/cp_logo_white.png' );
	} else {
		$default_text_color = '#666666';
		$default_image = appthemes_locate_template_uri( 'images/cp_logo_black.png' );
	}

	$args = array(
		// Text color and image (empty to use none).
		'default-text-color'     => $default_text_color,
		'header-text'            => true,
		'default-image'          => $default_image,

		'flex-height'            => true,
		'flex-width'             => true,

		// Set height and width.
		'height'                 => 80,
		'width'                  => 300,

		// Random image rotation off by default.
		'random-default'         => false,

		// Callbacks for styling the header and the admin preview.
		'wp-head-callback'       => 'cp_header_style',
		'admin-preview-callback' => 'cp_admin_header_image',
	);

	add_theme_support( 'custom-header', $args );
}


/**
 * Style the header text displayed on the blog.
 * get_header_textcolor() options: fff is default, hide text (returns 'blank'), or any hex value.
 *
 * @return void
 */
function cp_header_style() {
	$text_color = get_header_textcolor();

	// If we get this far, we have custom styles.
	?>
	<style type="text/css" id="cp-header-css">
	<?php
		// Has the text been hidden?
		if ( ! display_header_text() ) {
	?>
		#logo .site-title,
		#logo .description {
			position: absolute;
			clip: rect(1px 1px 1px 1px); /* IE7 */
			clip: rect(1px, 1px, 1px, 1px);
		}
	<?php
		// If the user has set a custom color for the text, use that.
		} else {
	?>
		#logo h1 a,
		#logo h1 a:hover,
		#logo .description {
			color: #<?php echo $text_color; ?>;
		}
		<?php } ?>

	</style>
	<?php
}


/**
 * Output markup to be displayed on the Appearance > Header admin panel.
 * This callback overrides the default markup displayed there.
 *
 * @return void
 */
function cp_admin_header_image() {
?>
	<div id="headimg">
		<?php
		$nologo = '';

		if ( ! display_header_text() ) {
			$style = ' style="display:none;"';
		} else {
			$style = ' style="color:#' . get_header_textcolor() . ';"';
		}
		?>

		<?php $header_image = get_header_image();

		if ( ! empty( $header_image ) ): ?>

			<img src="<?php echo esc_url( $header_image ); ?>" class="header-image" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="" />

		<?php elseif ( display_header_text() ) :

			$nologo = ' nologo'; ?>
			<h1 class="displaying-header-text">
				<a id="name"<?php echo $style; ?> onclick="return false;" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php bloginfo( 'name' ); ?>
				</a>
			</h1>
		<?php endif; ?>

		<?php if ( display_header_text() ): ?>
			<div class="description displaying-header-text<?php echo $nologo; ?>"<?php echo $style; ?>><?php bloginfo( 'description' ); ?></div>
		<?php endif; ?>

	</div>
<?php
}


/**
 * Displays the theme color choices in the customizer.
 */
function _cp_customize_color_scheme( $wp_customize ){
	global $cp_options;

	$color_defaults = cp_get_customizer_color_defaults();

	$wp_customize->add_setting( 'cp_options[stylesheet]', array(
		'default' => $cp_options->stylesheet,
		'type'    => 'option',
	) );

	$wp_customize->add_control( 'cp_color_scheme', array(
		'label'      => __( 'Color Scheme', APP_TD ),
		'section'    => 'colors',
		'settings'   => 'cp_options[stylesheet]',
		'type'       => 'radio',
		'choices'	 => cp_get_color_choices(),
		'priority'	 => 1,
	) );

	$wp_customize->add_setting( 'cp_options[header_bgcolor]', array(
		'default' => $cp_options->header_bgcolor,
		'type'    => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_header_bgcolor',
			array(
				'label'		=> __( 'Header Background Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[header_bgcolor]',
				'priority'	=> 1,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[top_nav_bgcolor]', array(
		'default' => $color_defaults['cp_top_nav_bgcolor'],
		'type'    => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_top_nav_bgcolor',
			array(
				'label'		=> __( 'Top Navigation Bar Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[top_nav_bgcolor]',
				'priority'	=> 2,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[top_nav_links_color]', array(
		'default' => $color_defaults['cp_top_nav_links_color'],
		'type' => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_top_nav_links_color',
			array(
				'label'		=> __( 'Top Navigation Links Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[top_nav_links_color]',
				'priority'	=> 3,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[top_nav_text_color]', array(
		'default' => $color_defaults['cp_top_nav_text_color'],
		'type' => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_top_nav_text_color',
			array(
				'label'		=> __( 'Top Navigation Text Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[top_nav_text_color]',
				'priority'	=> 4,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[bgcolor]', array(
		'default' => $cp_options->bgcolor,
		'type'    => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_bgcolor',
			array(
				'label'		=> __( 'Main Background Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[bgcolor]',
				'priority'	=> 12,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[main_nav_bgcolor]', array(
		'default' => $color_defaults['cp_main_nav_bgcolor'],
		'type'    => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_main_nav_bgcolor',
			array(
				'label'		=> __( 'Main Navigation Bar Color', APP_TD ),
				'description' =>  __( 'Affects Header/Footer/Form Steps', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[main_nav_bgcolor]',
				'priority'	=> 12,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[buttons_bgcolor]', array(
		'default' => $color_defaults['cp_buttons_bgcolor'],
		'type'    => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_buttons_bgcolor',
			array(
				'label'		=> __( 'Buttons Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[buttons_bgcolor]',
				'priority'	=> 13,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[buttons_text_color]', array(
		'default' => $color_defaults['cp_buttons_text_color'],
		'type'    => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_buttons_text_color',
			array(
				'label'		=> __( 'Buttons Text Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[buttons_text_color]',
				'priority'	=> 14,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[links_color]', array(
		'default' => $color_defaults['cp_links_color'],
		'type' => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_links_color',
			array(
				'label'		  => __( 'Links Color', APP_TD ),
				'description' => __( 'Affects Links/Price Tags', APP_TD ),
				'section'	  => 'colors',
				'settings'	  => 'cp_options[links_color]',
				'priority'	  => 15,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[footer_bgcolor]', array(
		'default' => $cp_options->footer_bgcolor,
		'type'    => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_footer_bgcolor',
			array(
				'label'		=> __( 'Footer Background Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[footer_bgcolor]',
				'priority'	=> 16,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[footer_text_color]', array(
		'default' => $color_defaults['cp_footer_text_color'],
		'type' => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_footer_text_color',
			array(
				'label'		=> __( 'Footer Text Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[footer_text_color]',
				'priority'	=> 17,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[footer_titles_color]', array(
		'default' => $color_defaults['cp_footer_titles_color'],
		'type' => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_footer_titles_color',
			array(
				'label'		=> __( 'Footer Titles Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[footer_titles_color]',
				'priority'	=> 18,
			)
		)
	);

	$wp_customize->add_setting( 'cp_options[footer_links_color]', array(
		'default' => $color_defaults['cp_footer_links_color'],
		'type' => 'option'
	) );

	$wp_customize->add_control(
		new WP_Customize_Color_Control(	$wp_customize,
			'cp_footer_links_color',
			array(
				'label'		=> __( 'Footer Links Color', APP_TD ),
				'section'	=> 'colors',
				'settings'	=> 'cp_options[footer_links_color]',
				'priority'	=> 19,
			)
		)
	);

}

/**
 * Displays the theme listing options in the customizer.
 */
function _cp_customize_site( $wp_customize ) {
	global $cp_options;

	$wp_customize->add_section( 'cp_site', array(
		'title' => __( 'Site', APP_TD ),
		'priority' => 20,
	));

	$wp_customize->add_setting( 'cp_options[home_layout]', array(
		'default' => $cp_options->home_layout,
		'type' => 'option',
	) );

	$wp_customize->add_control( 'cp_home_layout', array(
		'label'      => __( 'Layout', APP_TD ),
		'section'    => 'cp_site',
		'settings'   => 'cp_options[home_layout]',
		'type'       => 'radio',
		'choices'	 => array(
			'standard'	 => __( 'Standard Style', APP_TD ),
			'directory'	 => __( 'Directory Style', APP_TD ),
		),
		'priority'	 => 1,
	) );

	$wp_customize->add_setting( 'cp_options[selectbox]', array(
		'default' => $cp_options->display_website_time,
		'type' => 'option',
	) );

	$wp_customize->add_control( 'cp_selectbox', array(
		'label'      => __( 'Use SelectBox JS', APP_TD ),
		'description'=> __( 'A jQuery plugin that replaces the default drop-downs with modern ones', APP_TD ),
		'section'    => 'cp_site',
		'settings'   => 'cp_options[selectbox]',
		'type'       => 'checkbox',
	) );

	$wp_customize->add_setting( 'cp_options[search_field_width]', array(
		'default' => $cp_options->search_field_width,
		'type' => 'option',
	) );

	$wp_customize->add_control( 'cp_search_field_width', array(
		'label'      => __( 'Search Field Width', APP_TD ),
		'description'=> __( 'Must be numeric followed by either px or % - i.e. 600px or 50%', APP_TD ),
		'section'    => 'cp_site',
		'settings'   => 'cp_options[search_field_width]',
		'type'       => 'text',
	) );

	$wp_customize->add_setting( 'cp_options[display_website_time]', array(
		'default' => $cp_options->display_website_time,
		'type' => 'option',
	) );

	$wp_customize->add_control( 'cp_display_website_time', array(
		'label'      => __( 'Show  time/timezone in the footer', APP_TD ),
		'section'    => 'cp_site',
		'settings'   => 'cp_options[display_website_time]',
		'type'       => 'checkbox',
	) );

}


/**
 * Displays the theme listing options in the customizer.
 */
function _cp_customize_footer( $wp_customize ) {
	global $cp_options;

	$wp_customize->add_section( 'cp_footer', array(
		'title' => __( 'Footer', APP_TD ),
		'priority' => 100,
	));

	$wp_customize->add_setting( 'cp_options[footer_width]', array(
		'default' => $cp_options->footer_width,
		'type' => 'option'
	));

	$wp_customize->add_control( 'cp_multi_column_footer_width', array(
		'label'      => __( 'Footer Width', APP_TD ),
		'description'=> __( 'Specify % or px', APP_TD ),
		'section'    => 'cp_footer',
		'settings'   => 'cp_options[footer_width]',
		'type'       => 'text',
	) );

	$wp_customize->add_setting( 'cp_options[footer_col_width]', array(
		'default' => $cp_options->footer_col_width,
		'type' => 'option'
	));

	$wp_customize->add_control( 'cp_footer_width', array(
		'label'      => __( 'Columns Width', APP_TD ),
		'description'=> __( 'Specify % or px', APP_TD ),
		'section'    => 'cp_footer',
		'settings'   => 'cp_options[footer_col_width]',
		'type'       => 'text',
	) );

}


### Helper functions & Other Callbacks

/**
 * Populates the theme dropdown with the default styles and adds any custom .css styles found on the styles path.
 * Styles must be placed under the child folder \styles\ (fallback to the parent /styles folder if directory does not exist).
 * The resulting styles array is filterable to allow adding custom theme styles.
 *
 * @uses apply_filters() Calls 'cp_theme_styles'
 */
function cp_get_color_choices() {

	$styles_path = get_stylesheet_directory() . '/styles/';
	if ( ! apply_filters( 'cp_load_style', ! is_child_theme() ) || ! file_exists( $styles_path ) ) {
		$styles_path = get_template_directory() . '/styles/';
	}

	$styles_pattern = $styles_path . 'style*.css';

	$styles = array(
		'aqua.css'  => __( 'Aqua Theme', APP_TD ),
		'blue.css'  => __( 'Blue Theme', APP_TD ),
		'green.css' => __( 'Green Theme', APP_TD ),
		'red.css'   => __( 'Red Theme', APP_TD ),
		'teal.css'  => __( 'Teal Theme', APP_TD ),
	);

	// Get all the available theme styles and append them to the defaults.
	$files = glob( $styles_pattern );

	if ( is_array( $files ) && count( $files ) > 0 ) {

		foreach ( $files as $filename ) {

			if ( FALSE !== strpos( $filename, '.min' ) ) continue;

			if ( ! array_key_exists( basename( $filename ), $styles ) ) {
				$styles[ basename( $filename ) ] = __( 'Custom Theme', APP_TD ) . ' (' . basename( $filename ) . ')';
			}
		}

	}

	return apply_filters( 'cp_theme_styles', $styles );
}


/**
 * Retrieves the customizer default colors based on the theme color scheme.
 */
function cp_get_customizer_color_defaults( $scheme = '' ) {
	global $cp_options;

	if ( ! $scheme ) {
		$scheme = $cp_options->stylesheet;
	}

	$color_defaults = array(
		'aqua.css'	=> array(
			// top nav
			'cp_top_nav_bgcolor'      => '#313131',
			'cp_top_nav_links_color'  => '#6dbd9d',
			'cp_top_nav_text_color'   => '#fff',
			// header
			'cp_header_bgcolor'       => '',
			// main nav
			'cp_main_nav_bgcolor'     => '#3e9286',
			'cp_main_nav_links_color' => '#fff',
			// other
			'cp_buttons_bgcolor'      => '#096E5F',
			'cp_buttons_text_color'   => '#fff',
			'cp_links_color'          => '#3e9286',
			// footer
			'cp_footer_bgcolor'       => '#313131',
			'cp_footer_text_color'    => '#3e9286',
			'cp_footer_links_color'   => '#3e9286',
			'cp_footer_titles_color'  => '#fff',
		),
		'blue.css' => array(
			// top nav
			'cp_top_nav_bgcolor'         => '#313131',
			'cp_top_nav_links_color'     => '#528BC3',
			'cp_top_nav_text_color'      => '#fff',
			// header
			'cp_header_bgcolor'          => '',
			// main nav
			'cp_main_nav_bgcolor'        => '#3b5998',
			'cp_main_nav_links_color'    => '#fff',
			// other
			'cp_buttons_bgcolor'         => '#19346C',
			'cp_buttons_text_color'      => '#fff',
			'cp_links_color'			 => '#3b5998',
			// footer
			'cp_footer_bgcolor'          => '#313131',
			'cp_footer_text_color'       => '#5671a9',
			'cp_footer_links_color'      => '#5671a9',
			'cp_footer_titles_color'     => '#fff',
		),
		'green.css'	=> array(
			// top nav
			'cp_top_nav_bgcolor'         => '#313131',
			'cp_top_nav_links_color'     => '#9dbd6d',
			'cp_top_nav_text_color'      => '#fff',
			// header
			'cp_header_bgcolor'          => '',
			// main nav
			'cp_main_nav_bgcolor'        => '#679325',
			'cp_main_nav_links_color'    => '#fff',
			// other
			'cp_buttons_bgcolor'         => '#536C2E',
			'cp_buttons_text_color'      => '#fff',
			'cp_links_color'			 => '#679325',
			// footer
			'cp_footer_bgcolor'          => '#313131',
			'cp_footer_text_color'       => '#9dbd6d',
			'cp_footer_links_color'      => '#9dbd6d',
			'cp_footer_titles_color'     => '#fff',
		),
		'red.css'	=> array(
			// top nav
			'cp_top_nav_bgcolor'         => '#313131',
			'cp_top_nav_links_color'     => '#E86B6B',
			'cp_top_nav_text_color'      => '#fff',
			// header
			'cp_header_bgcolor'          => '',
			// main nav
			'cp_main_nav_bgcolor'        => '#b22222',
			'cp_main_nav_links_color'    => '#fff',
			// other
			'cp_buttons_bgcolor'         => '#710505',
			'cp_buttons_text_color'      => '#fff',
			'cp_links_color'			 => '#b22222',
			// footer
			'cp_footer_bgcolor'          => '#313131',
			'cp_footer_text_color'       => '#d05959',
			'cp_footer_links_color'      => '#D07373',
			'cp_footer_titles_color'     => '#fff',
		),
		'teal.css'	=> array(
			// top nav
			'cp_top_nav_bgcolor'         => '#313131',
			'cp_top_nav_links_color'     => '#4BA0CA',
			'cp_top_nav_text_color'      => '#fff',
			// header
			'cp_header_bgcolor'          => '',
			// main nav
			'cp_main_nav_bgcolor'        => '#186c95',
			'cp_main_nav_links_color'    => '#fff',
			// other
			'cp_buttons_bgcolor'         => '#134E6B',
			'cp_buttons_text_color'      => '#fff',
			'cp_links_color'			 => '#186c95',
			// footer
			'cp_footer_bgcolor'          => '#313131',
			'cp_footer_text_color'       => '#186C95',
			'cp_footer_links_color'      => '#2883B0',
			'cp_footer_titles_color'     => '#fff',
		),
	);

	$color_defaults = apply_filters( 'cp_customizer_color_defaults', $color_defaults );

	if ( ! empty( $color_defaults[ $scheme ] ) ) {
		return $color_defaults[ $scheme ];
	}

	if ( 'all' == $scheme ) {
		return $color_defaults;
	}

	return $color_defaults['style-default.css'];
}


/**
 * Overrides the theme styles with the Customizer options.
 */
function _cp_customize_css() {
	global $cp_options;
?>

    <style type="text/css">

		<?php if ( $cp_options->bgcolor ) : ?>
			body { background: <?php echo $cp_options->bgcolor; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->links_color ) : ?>
			.content a:not(.cp-fixed-color):not(.selectBox) { color: <?php echo $cp_options->links_color; ?>; }
			#easyTooltip { background: <?php echo $cp_options->links_color; ?>; }
			.tags span { background: <?php echo $cp_options->links_color; ?>; }
			span.colour { color: <?php echo $cp_options->links_color; ?>; }
			.tags span:before { border-color: transparent <?php echo $cp_options->links_color; ?> transparent transparent; }
		<?php endif; ?>

		<?php if ( $cp_options->top_nav_bgcolor ) : ?>
			.header_top { background: <?php echo $cp_options->top_nav_bgcolor; ?>;  }
		<?php endif; ?>

		<?php if ( $cp_options->top_nav_links_color ) : ?>
			.header_top .header_top_res p a { color: <?php echo $cp_options->top_nav_links_color; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->top_nav_text_color ) : ?>
			.header_top .header_top_res p { color: <?php echo $cp_options->top_nav_text_color; ?>;  }
		<?php endif; ?>

		<?php if ( $cp_options->header_bgcolor ) : ?>
			.header_main, .header_main_bg { background: <?php echo $cp_options->header_bgcolor; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->main_nav_bgcolor ) : ?>
			.header_menu, .footer_menu { background: <?php echo $cp_options->main_nav_bgcolor; ?>; }
			ol.progtrckr li.progtrckr-done, ol.progtrckr li.progtrckr-todo { border-bottom-color: <?php echo $cp_options->main_nav_bgcolor; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->buttons_text_color ) : ?>
			.btn_orange, .pages a, .pages span, .btn-topsearch, .tab-dashboard ul.tabnavig li a, .tab-dashboard ul.tabnavig li a.selected, .reports_form input[type="submit"] { color: <?php echo $cp_options->buttons_text_color; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->buttons_bgcolor ) : ?>
			.btn_orange, .pages a, .pages span, .btn-topsearch,	.reports_form input[type="submit"] { background: <?php echo $cp_options->buttons_bgcolor; ?>; }
			.tab-dashboard ul.tabnavig li a.selected { border-bottom: 1px solid <?php echo $cp_options->buttons_bgcolor; ?>;background: <?php echo $cp_options->buttons_bgcolor; ?>; }
			.tab-dashboard ul.tabnavig li a { background:  <?php echo hex2rgb( $cp_options->buttons_bgcolor, '0.6' ); ?>; }
			ol.progtrckr li.progtrckr-done:before { background: <?php echo $cp_options->buttons_bgcolor; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->footer_bgcolor ) : ?>
			.footer { background: <?php echo $cp_options->footer_bgcolor; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->footer_text_color ) : ?>
			.footer_main_res div.column { color: <?php echo $cp_options->footer_text_color; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->footer_titles_color ) : ?>
			.footer_main_res div.column h1, .footer_main_res div.column h2, .footer_main_res div.column h3 { color: <?php echo $cp_options->footer_titles_color; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->footer_links_color ) : ?>
			.footer_main_res div.column a, .footer_main_res div.column ul li a { color: <?php echo $cp_options->footer_links_color; ?>; }
		<?php endif; ?>

		<?php if ( $cp_options->footer_width ) : ?>
			.footer_main_res { width: <?php echo $cp_options->footer_width; ?>; }
			@media screen and (max-width: 860px) {
				.footer_main_res {
					width: 100%;
					overflow: hidden;
				}
				#footer .inner {
					float: left;
					width: 95%;
				}
			}
		<?php endif; ?>

		<?php if ( $cp_options->footer_col_width ) : ?>
			.footer_main_res div.column { width: <?php echo $cp_options->footer_col_width; ?>; }
			@media screen and (max-width: 860px) {
				.footer_main_res div.column {
					float: left;
					width: 95%;
				}
			}
		<?php endif; ?>

	</style>
<?php
}
