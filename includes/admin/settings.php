<?php
/**
 * Admin Settings.
 *
 * @package ClassiPress\Admin\Settings
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */

/**
 * General Settings Page.
 */
class CP_Theme_Settings_General extends APP_Tabs_Page {

	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title'			 => __( 'ClassiPress Settings', APP_TD ),
			'menu_title'			 => __( 'Settings', APP_TD ),
			'page_slug'				 => 'app-settings',
			'parent'				 => 'app-dashboard',
			'screen_icon'			 => 'options-general',
			'admin_action_priority'	 => 10,
		);

		add_action( 'admin_notices', array( $this, 'admin_tools' ) );
	}

	public function admin_tools() {

		if ( isset( $_GET['pruneads'] ) && $_GET['pruneads'] == 1 ) {
			cp_check_expired_cron();
			echo scb_admin_notice( __( 'Expired ads have been pruned.', APP_TD ) );
		}

		if ( isset( $_GET['resetstats'] ) && $_GET['resetstats'] == 1 ) {
			appthemes_reset_stats();
			echo scb_admin_notice( __( 'Statistics have been reseted.', APP_TD ) );
		}

		// flush out the cache so changes can be visible
		cp_flush_all_cache();
	}

	protected function init_tabs() {
		// Remove unwanted query args from urls
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'firstrun', 'pruneads', 'resetstats' ), $_SERVER['REQUEST_URI'] );

		$this->tabs->add( 'general', __( 'General', APP_TD ) );
		$this->tabs->add( 'categories', __( 'Categories', APP_TD ) );
		$this->tabs->add( 'listings', __( 'Listings', APP_TD ) );
		$this->tabs->add( 'security', __( 'Security', APP_TD ) );
		$this->tabs->add( 'advertise', __( 'Advertising', APP_TD ) );
		$this->tabs->add( 'advanced', __( 'Advanced', APP_TD ) );

		$this->tab_sections['general']['appearance'] = array(
			'title' => __( 'Appearance', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Design', APP_TD ),
					'desc' => sprintf( __( 'Customize the look and feel of your website by visiting the <a href="%s">WordPress customizer</a>.' , APP_TD), 'customize.php' ),
					'type' => 'text',
					'name' => '_blank',
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => '',
				),
				array(
					'title'	 => __( 'Favicon', APP_TD ),
					'desc'	 => $this->wrap_upload( 'favicon_url', '<br />' . sprintf( __( '<a target="_blank" href="%s">Create your own</a> favicon or paste an image URL directly. Must be a 16x16 .ico file.', APP_TD ), 'http://www.favicon.cc/' ) ),
					'type'	 => 'text',
					'name'	 => 'favicon_url',
					'tip'	 => __( 'This will replace the default favicon logo.(i.e. http://www.yoursite.com/favicon.ico)', APP_TD ),
				),
			),
		);

		$this->tab_sections['general']['configuration'] = array(
			'title' => __( 'Social', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Feedburner URL', APP_TD ),
					'desc'	 => sprintf( __( '%1$s Sign up for a free <a target="_blank" href="%2$s">Feedburner account</a>.', APP_TD ), '<i class="social-ico dashicons-before feedburnerico"></i>', 'https://feedburner.google.com' ),
					'type'	 => 'text',
					'name'	 => 'feedburner_url',
					'tip'	 => __( 'Automatically redirect your default RSS feed to Feedburner.', APP_TD ),
				),
				array(
					'title'	 => __( 'Twitter ID', APP_TD ),
					'desc'	 => sprintf( __( '%1$s Sign up for a free <a target="_blank" href="%2$s">Twitter account</a>.', APP_TD ), '<i class="social-ico dashicons-before twitterico"></i>', 'https://twitter.com' ),
					'type'	 => 'text',
					'name'	 => 'twitter_username',
					'tip'	 => __( 'Automatically redirect your Twitter link to your Twitter page.', APP_TD ),
				),
				array(
					'title'	 => __( 'Facebook ID', APP_TD ),
					'desc'	 => sprintf( __( '%1$s Sign up for a free <a target="_blank" href="%2$s">Facebook account</a>.', APP_TD ), '<i class="social-ico dashicons-before facebookico"></i>', 'https://www.facebook.com' ),
					'type'	 => 'text',
					'name'	 => 'facebook_id',
					'tip'	 => __( 'Display a Facebook icon in the header that links to your page.', APP_TD ),
				),
				array(
					'title'		 => __( 'Analytics Code', APP_TD ),
					'desc'		 => sprintf( __( '%1$sSign up for a free <a target="_blank" href="%2$s">Google Analytics account</a>.', APP_TD ), '<i class="social-ico dashicons-before googleico"></i>', 'https://www.google.com/analytics/' ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'google_analytics',
					'extra'		 => array(
						'rows'	 => 10,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => __( 'You can use Google Analytics or other providers as well.', APP_TD ),
				),
			),
		);

		$this->tab_sections['general']['google_maps'] = array(
			'title'	 => __( 'Google Maps', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Region Biasing', APP_TD ),
					'desc'	 => sprintf( __( 'Find your two-letter <a href="%s" target="_blank">region code</a>', APP_TD ), esc_attr( 'http://en.wikipedia.org/wiki/ISO_3166-1#Current_codes' ) ),
					'type'	 => 'text',
					'name'	 => 'gmaps_region',
					'tip'	 => __( "If you set this to 'IT' and a user enters 'Florence' in the location search field, it will target 'Florence, Italy' rather than 'Florence, Alabama'.", APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
				array(
					'title'	 => __( 'Language', APP_TD ),
					'desc'	 => sprintf( __( 'Find your two-letter <a href="%s" target="_blank">language code</a>', APP_TD ), 'http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes' ),
					'type'	 => 'text',
					'name'	 => 'gmaps_lang',
					'tip'	 => __( 'Used to format the address and map controls.', APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
				array(
					'title'	 => __( 'Distance Unit', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'distance_unit',
					'values' => array(
						'km' => __( 'Kilometers', APP_TD ),
						'mi' => __( 'Miles', APP_TD ),
					),
					'tip'	 => '',
				),
				array(
					'title' => __( 'API Key', APP_TD ),
					'desc' => sprintf( __( 'Get started using the <a href="%s" target="_blank">Maps API</a>', APP_TD ), 'https://developers.google.com/maps/documentation/javascript/tutorial#api_key' ),
					'type' => 'text',
					'name' => 'api_key',
					'tip' => __( 'Activate your Google Maps JavaScript API Service and paste in the API key here. This field is required.', APP_TD ),
				),
			),
		);

		$this->tab_sections['general']['search_settings'] = array(
			'title'	 => __( 'Search', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Pages', APP_TD ),
					'name'	 => 'search_ex_pages',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Exclude from search results', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Blog Posts', APP_TD ),
					'name'	 => 'search_ex_blog',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Exclude from search results', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Refine Price Slider', APP_TD ),
					'name'	 => 'refine_price_slider',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Use a price slider instead of input fields in the "refine search" widget', APP_TD ),
					'tip'	 => '',
				),
			),
		);

		$this->tab_sections['categories']['category_menu_options'] = array(
			'title'	 => __( 'Categories Menu', APP_TD ),
			'fields' => $this->categories_options( 'cat_menu' ),
		);

		$this->tab_sections['categories']['category_dir_options'] = array(
			'title'	 => __( 'Categories Page', APP_TD ),
			'fields' => $this->categories_options( 'cat_dir' ),
		);

		$this->tab_sections['categories']['search_dropdown'] = array(
			'title'	 => __( 'Search Categories', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Depth', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'search_depth',
					'values' => array(
						'0'	 => __( 'Show All', APP_TD ),
						'1'	 => '1',
						'2'	 => '2',
						'3'	 => '3',
						'4'	 => '4',
						'5'	 => '5',
						'6'	 => '6',
						'7'	 => '7',
						'8'	 => '8',
						'9'	 => '9',
						'10' => '10',
					),
					'tip'	 => __( "This sets the depth of categories shown in the category drop-down. Use 'Show All' unless you have a lot of sub-categories and do not want them all listed.", APP_TD ),
				),
				array(
					'title'	 => __( 'Hierarchy', APP_TD ),
					'name'	 => 'cat_hierarchy',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Indent sub-categories within the drop-down', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Show Count', APP_TD ),
					'name'	 => 'cat_count',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Display the ad total next to each category in the drop-down', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Hide Empty', APP_TD ),
					'name'	 => 'cat_hide_empty',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Hide empty categories within the drop-down', APP_TD ),
					'tip'	 => '',
				),
			),
		);

		$this->tab_sections['general']['messages'] = array(
			'title'	 => __( 'Messages', APP_TD ),
			'fields' => array(
				array(
					'title'		 => __( 'Home Page', APP_TD ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'ads_welcome_msg',
					'extra'		 => array(
						'rows'	 => 10,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => __( 'Appears in the sidebar of your home page. (HTML is allowed)', APP_TD ),
				),
				array(
					'title'		 => __( 'New Ad', APP_TD ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'ads_form_msg',
					'extra'		 => array(
						'rows'	 => 10,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => __( 'Appears at the top of the classified ads listing page. (HTML is allowed)', APP_TD ),
				),
				array(
					'title'		 => __( 'Membership', APP_TD ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'membership_form_msg',
					'extra'		 => array(
						'rows'	 => 10,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => __( 'Appears at the top of the membership package purchase page. (HTML is allowed)', APP_TD ),
				),
				array(
					'title'		 => __( 'Terms', APP_TD ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'ads_tou_msg',
					'extra'		 => array(
						'rows'	 => 10,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => __( 'Appears on the last step of your classified ad listing page. This is usually your legal disclaimer or rules for posting new ads. (HTML is allowed)', APP_TD ),
				),
			),
		);


		$this->tab_sections['listings']['configuration'] = array(
			'title' => __( 'General', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Editing', APP_TD ),
					'name'	 => 'ad_edit',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Allow users to edit and republish their ads', APP_TD ),
					'tip'	 => __( 'They can manage and edit ads from their dashboard.', APP_TD ),
				),
				array(
					'title'	 => __( 'Relisting', APP_TD ),
					'name'	 => 'allow_relist',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Allow users to relist and pay for their expired ads', APP_TD ),
					'tip'	 => __( 'An email will be sent with a link to relist their ad.', APP_TD ),
				),
				array(
					'title'	 => __( 'Listing Period', APP_TD ),
					'type'	 => 'number',
					'name'	 => 'prun_period',
					'desc'	 => __( 'Days each ad will be listed ', APP_TD ),
					'tip'	 => __( 'This option is overridden by ad packs if you are charging for ads and using the Fixed Price Per Ad option. ', APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
				array(
					'title'	 => __( 'Parent Posting', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'ad_parent_posting',
					'values' => array(
						'yes'		 => __( 'Yes', APP_TD ),
						'no'		 => __( 'No', APP_TD ),
						'whenEmpty'	 => __( 'When Empty', APP_TD ),
					),
					'desc'	 => __( "Allow users to post in top-level categories", APP_TD ),
					'tip'	 => __( "If set to 'When Empty', it allows posting to top-level categories only if they have no child categories.", APP_TD ),
				),
			),
		);

		$this->tab_sections['listings']['adpage'] = array(
			'title'	 => __( 'Ad Page', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Inquiry Form', APP_TD ),
					'name'	 => 'ad_inquiry_form',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Require visitors to be logged in before they can contact ad owners', APP_TD ),
					'tip'	 => __( 'In most cases you should keep this set to no to encourage visitors to ask questions without having to create an account.', APP_TD ),
				),
				array(
					'title'	 => __( 'Allow HTML', APP_TD ),
					'name'	 => 'allow_html',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Permit users to use HTML within their ad listings', APP_TD ),
					'tip'	 => __( 'Turns on the TinyMCE editor on text area fields and allows the ad owner to use html markup. Other fields do not allow html by default.', APP_TD ),
				),
				array(
					'title'	 => __( 'View Counter', APP_TD ),
					'name'	 => 'ad_stats_all',
					'type'	 => 'checkbox',
					'desc'	 => __( "Show a page views counter on each ad listing and blog post", APP_TD ),
					'tip'	 => __( "This will show a 'total views' and 'today's views' at the bottom of each ad listing and blog post.", APP_TD ),
				),
				array(
					'title'	 => __( 'Gravatar', APP_TD ),
					'name'	 => 'ad_gravatar_thumb',
					'type'	 => 'checkbox',
					'desc'	 => __( "Show a picture of the user on their ad listing", APP_TD ),
					'tip'	 => __( "A placeholder image will be used if they don't have a Gravatar setup.", APP_TD ),
				),
			),
		);

		$this->tab_sections['listings']['moderate'] = array(
			'title'	 => __( 'Moderate', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Ads', APP_TD ),
					'type'	 => 'checkbox',
					'name'	 => 'moderate_ads',
					'desc'	 => __( 'Manually approve and publish each new ad', APP_TD ),
					'tip'	 => __( 'Left unchecked, ads go live immediately without being moderated (unless it has not been paid for).', APP_TD ),
				),
				array(
					'title'	 => __( 'Edited Ads', APP_TD ),
					'type'	 => 'checkbox',
					'name'	 => 'moderate_edited_ads',
					'desc'	 => __( 'Manually approve and publish user edited ads', APP_TD ),
					'tip'	 => __( 'Left unchecked, edited ads stay live without being moderated.', APP_TD ),
				),
				array(
					'title'	 => __( 'Prune Ads', APP_TD ),
					'name'	 => 'post_prune',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Automatically remove expired listings', APP_TD ),
					'tip'	 => __( 'Left unchecked, ads will remain live but marked as expired. If enabled, ads will be set to draft (not deleted). Frequency can be set via the cron job option on the advanced tab.', APP_TD ),
				),
			),
		);

		$this->tab_sections['listings']['images'] = array(
			'title'	 => __( 'Ad Images', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Allow Images', APP_TD ),
					'name'	 => 'ad_images',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Allow the user to upload and display images in their ad', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Require Images', APP_TD ),
					'name'	 => 'require_images',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Require at least one image uploaded per ad', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Preview Image', APP_TD ),
					'name'	 => 'ad_image_preview',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Display a larger image when you mouse over the thumbnail', APP_TD ),
					'tip'	 => __( 'Affects the home, category, and search results pages.', APP_TD ),
				),
				array(
					'title'	 => __( 'Max Images', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'num_images',
					'values' => array(
						'1'	 => '1',
						'2'	 => '2',
						'3'	 => '3',
						'4'	 => '4',
						'5'	 => '5',
						'6'	 => '6',
						'7'	 => '7',
						'8'	 => '8',
						'9'	 => '9',
						'10' => '10',
					),
					'desc'	 => __( 'Images allowed per ad', APP_TD ),
				),
				array(
					'title'	 => __( 'Max Size', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'max_image_size',
					'values' => array(
						'100'	 => '100KB',
						'250'	 => '250KB',
						'500'	 => '500KB',
						'1024'	 => '1MB',
						'2048'	 => '2MB',
						'5120'	 => '5MB',
						'7168'	 => '7MB',
						'10240'	 => '10MB',
					),
					'desc'	 => __( 'Maximum size per image', APP_TD ),
				),
			),
		);


		$this->tab_sections['security']['settings'] = array(
			'title' => __( 'Access', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Backend Access', APP_TD ),
					'desc'	 => sprintf( __( "Restrict access by <a target='_blank' href='%s'>specific role</a>.", APP_TD ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
					'type'	 => 'select',
					'name'	 => 'admin_security',
					'values' => array(
						'manage_options'	 => __( 'Admins Only', APP_TD ),
						'edit_others_posts'	 => __( 'Admins, Editors', APP_TD ),
						'publish_posts'		 => __( 'Admins, Editors, Authors', APP_TD ),
						'edit_posts'		 => __( 'Admins, Editors, Authors, Contributors', APP_TD ),
						'read'				 => __( 'All Access', APP_TD ),
						'disable'			 => __( 'Disable', APP_TD ),
					),
					'tip'	 => '',
				),
			),
		);

		$this->tab_sections['security']['recaptcha'] = array(
			'title'	 => __( 'reCaptcha', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Enable', APP_TD ),
					'name'  => 'captcha_enable',
					'type'  => 'checkbox',
					'desc'  => sprintf( __( "A free <a target='_blank' href='%s'>anti-spam service</a> provided by Google", APP_TD ), esc_url( 'https://www.google.com/recaptcha/' ) ),
					'tip'   => __( 'Displays a verification box on your registration page to prevent your website from spam and abuse.', APP_TD ),
				),
				array(
					'title' => __( 'Public Key', APP_TD ),
					'desc'  => '',
					'type'  => 'text',
					'name'  => 'captcha_public_key',
					'desc'  => '',
				),
				array(
					'title' => __( 'Private Key', APP_TD ),
					'desc'  => '',
					'type'  => 'text',
					'name'  => 'captcha_private_key',
					'tip'   => '',
				),
				array(
					'title'	 => __( 'Theme', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'captcha_theme',
					'values' => array(
						'light' => __( 'Light', APP_TD ),
						'dark'  => __( 'Dark', APP_TD ),
					),
					'tip'	 => '',
				),
			),
		);


		$this->tab_sections['advertise']['header'] = array(
			'title'	 => __( 'Header Ad (468x60)', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Enable', APP_TD ),
					'name'	 => 'adcode_468x60_enable',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Displayed in the header', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'		 => __( 'Code', APP_TD ),
					'desc'		 => sprintf( __( 'Supports many popular providers such as <a target="_blank" href="%s">Google AdSense</a> and <a target="_blank" href="%s">BuySellAds</a>.', APP_TD ), 'https://www.google.com/adsense/', 'https://www.buysellads.com/' ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'adcode_468x60',
					'extra'		 => array(
						'rows'	 => 10,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => '',
				),
				array(
					'title'	 => __( 'Image', APP_TD ),
					'desc'	 => $this->wrap_upload( 'adcode_468x60_url', '<br />' . __( 'Enter the URL to your ad creative.', APP_TD ) ),
					'type'	 => 'text',
					'name'	 => 'adcode_468x60_url',
					'tip'	 => __( 'If you would rather use an image ad instead of code provided by your advertiser, use this field.', APP_TD ),
				),
				array(
					'title'	 => __( 'Destination', APP_TD ),
					'desc'	 => __( 'The URL of your landing page.', APP_TD ),
					'type'	 => 'text',
					'name'	 => 'adcode_468x60_dest',
					'tip'	 => __( 'When a visitor clicks on your ad image, this is the destination they will be sent to.', APP_TD ),
				),
			),
		);

		$this->tab_sections['advertise']['content'] = array(
			'title'	 => __( 'Content Ad (336x280)', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Enable', APP_TD ),
					'name'	 => 'adcode_336x280_enable',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Displayed on single ad, category, and search result pages', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'		 => __( 'Code', APP_TD ),
					'desc'		 => sprintf( __( 'Supports many popular providers such as <a target="_blank" href="%s">Google AdSense</a> and <a target="_blank" href="%s">BuySellAds</a>.', APP_TD ), 'https://www.google.com/adsense/', 'https://www.buysellads.com/' ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'adcode_336x280',
					'extra'		 => array(
						'rows'	 => 10,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => '',
				),
				array(
					'title'	 => __( 'Image', APP_TD ),
					'desc'	 => $this->wrap_upload( 'adcode_336x280_url', '<br />' . __( 'Enter the URL to your ad creative.', APP_TD ) ),
					'type'	 => 'text',
					'name'	 => 'adcode_336x280_url',
					'tip'	 => __( 'If you would rather use an image ad instead of code provided by your advertiser, use this field.', APP_TD ),
				),
				array(
					'title'	 => __( 'Destination', APP_TD ),
					'desc'	 => __( 'The URL of your landing page.', APP_TD ),
					'type'	 => 'text',
					'name'	 => 'adcode_336x280_dest',
					'tip'	 => __( 'When a visitor clicks on your ad image, this is the destination they will be sent to.', APP_TD ),
				),
			),
		);


		$this->tab_sections['advanced']['settings'] = array(
			'title'	 => __( 'Maintenance', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Prune Ads', APP_TD ),
					'name'	 => '_blank',
					'type'	 => '',
					'desc'	 => sprintf( __( '<a href="%s">Prune expired ads</a>', APP_TD ), 'admin.php?page=app-settings&pruneads=1' ),
					'extra'	 => array(
						'style' => 'display: none;'
					),
					'tip'	 => __( 'Click the link to manually run the function that checks all ads expiration and prunes any ads that are expired. This event will run only one time.', APP_TD ),
				),
				array(
					'title'	 => __( 'Reset Stats', APP_TD ),
					'name'	 => '_blank',
					'type'	 => '',
					'desc'	 => sprintf( __( '<a href="%s">Reset stats counters</a>', APP_TD ), 'admin.php?page=app-settings&resetstats=1' ),
					'extra'	 => array(
						'style' => 'display: none;'
					),
					'tip'	 => __( 'Click the link to run the function that reset the stats counters for all ads and posts.', APP_TD ),
				),
			),
		);

		$this->tab_sections['advanced']['user'] = array(
			'title'	 => __( 'User', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Set Password', APP_TD ),
					'name'	 => 'allow_registration_password',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Let the user create their own password vs a system generated one', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Disable Toolbar', APP_TD ),
					'name'	 => 'remove_admin_bar',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Hide the WordPress toolbar for logged in users', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Disable Embeds', APP_TD ),
					'name'	 => 'disable_embeds',
					'type'	 => 'checkbox',
					'desc'	 => __( "Don't allow users to embed videos, images, or any other media", APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Disable Login Page', APP_TD ),
					'name'	 => 'disable_wp_login',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Prevents users from accessing <code>wp-login.php</code> directly', APP_TD ),
					'tip'	 => '',
				),
			),
		);

		$this->tab_sections['advanced']['developer'] = array(
			'title'	 => __( 'Developer', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Disable Stylesheets', APP_TD ),
					'name'	 => 'disable_stylesheet',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Turn off all styles (advanced users only)', APP_TD ),
					'tip'	 => __( "If you are interested in creating a child theme or just want to completely disable the core theme styles, enable this option.", APP_TD ),
				),
				array(
					'title'	 => __( 'Enable Debug Mode', APP_TD ),
					'name'	 => 'debug_mode',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Print out the <code>$wp_query->query_vars</code> array at the top of your website', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Use Google CDN', APP_TD ),
					'name'	 => 'google_jquery',
					'type'	 => 'checkbox',
					'desc'	 => __( "Speed up your website and save bandwidth by using their hosted jQuery", APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Hide Version', APP_TD ),
					'name'	 => 'remove_wp_generator',
					'type'	 => 'checkbox',
					'desc'	 => __( "Remove the WordPress version meta tag from your website", APP_TD ),
					'tip'	 => __( "An added security measure so snoopers won't be able to tell what version of WordPress you're running.", APP_TD ),
				),
				array(
					'title'	 => __( 'Cache Expires', APP_TD ),
					'desc'	 => __( 'This number is in seconds so one day equals 86400.', APP_TD ),
					'type'	 => 'text',
					'name'	 => 'cache_expires',
					'tip'	 => __( 'To speed up page loading on your site, ClassiPress uses a caching mechanism on certain features (i.e. category drop-down, home page). The cache automatically gets flushed whenever a category has been added/modified, however this value sets the frequency your cache is regularly emptied. We recommend keeping this at the default (every hour = 3600 seconds).', APP_TD ),
					'extra'	 => array(
						'class' => 'regular-text'
					),
				),
				array(
					'title'	 => __( 'Cron Schedule', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'ad_expired_check_recurrance',
					'desc'	 => __( 'Frequency to check for and take offline expired ads', APP_TD ),
					'values' => array(
						'none'		 => __( 'None', APP_TD ),
						'hourly'	 => __( 'Hourly', APP_TD ),
						'twicedaily' => __( 'Twice Daily', APP_TD ),
						'daily'		 => __( 'Daily', APP_TD ),
					),
					'tip'	 => __( 'Twice daily is recommended. Hourly may cause performance issues if you have a lot of ads. Note: This feature only works if you have enabled the Prune Ads option. ', APP_TD ),
				),
			),
		);

		$this->tab_sections['advanced']['permalinks'] = array(
			'title'	 => __( 'Custom URLs', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Permalinks', APP_TD ),
					'type'	 => '',
					'name'	 => '_blank',
					'desc'	 => sprintf( __( '<a href="%s">Setup permalinks</a>', APP_TD ), 'options-permalink.php' ),
					'extra'	 => array(
						'style' => 'display: none;'
					),
				),
			),
		);

		$this->tab_sections['advanced']['legacy'] = array(
			'title'	 => __( 'Legacy', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Ad Box Right Side', APP_TD ),
					'type'	 => 'select',
					'name'	 => 'ad_right_class',
					'values' => array(
						'full'	 => __( 'Normal Full Width', APP_TD ),
						''		 => __( 'Legacy Ads Width', APP_TD ),
					),
					'tip'	 => __( 'Sometimes the main ad listings box is too narrow or it wraps due to legacy ad sizes.', APP_TD ),
				),
			),
		);

	}

	private function categories_options( $prefix ) {
		$options = array(
			array(
				'title'	 => __( 'Show Count', APP_TD ),
				'type'	 => 'checkbox',
				'name'	 => $prefix . '_count',
				'desc'	 => __( 'Display the number of ads next to the category name', APP_TD ),
				'tip'	 => '',
			),
			array(
				'title'	 => __( 'Hide Empty', APP_TD ),
				'type'	 => 'checkbox',
				'name'	 => $prefix . '_hide_empty',
				'desc'	 => __( "Don't show the category if it has no ads", APP_TD ),
				'tip'	 => '',
			),
			array(
				'title'	 => __( 'Category Depth', APP_TD ),
				'type'	 => 'select',
				'name'	 => $prefix . '_depth',
				'values' => array(
					'999'	 => __( 'Show All', APP_TD ),
					'0'		 => '0',
					'1'		 => '1',
					'2'		 => '2',
					'3'		 => '3',
					'4'		 => '4',
					'5'		 => '5',
					'6'		 => '6',
					'7'		 => '7',
					'8'		 => '8',
					'9'		 => '9',
					'10'	 => '10',
				),
				'desc'	 => __( 'The number of levels deep the category should display', APP_TD ),
			),
			array(
				'title'	 => __( 'Number of Sub-Categories', APP_TD ),
				'type'	 => 'select',
				'name'	 => $prefix . '_sub_num',
				'values' => array(
					'999'	 => __( 'Show All', APP_TD ),
					'0'		 => '0',
					'1'		 => '1',
					'2'		 => '2',
					'3'		 => '3',
					'4'		 => '4',
					'5'		 => '5',
					'6'		 => '6',
					'7'		 => '7',
					'8'		 => '8',
					'9'		 => '9',
					'10'	 => '10',
				),
				'desc'	 => __( 'The number of sub-categories each parent category should display', APP_TD ),
			),
		);

		if ( $prefix == 'cat_dir' ) {
			$options[] = array(
				'title'	 => __( 'Number of Columns', APP_TD ),
				'type'	 => 'select',
				'name'	 => $prefix . '_cols',
				'values' => array(
					'2'	 => '2',
					'3'	 => '3',
				),
				'desc'	 => __( 'The number of columns on the directory-style layout that should display', APP_TD ),
			);
		}

		return $options;
	}

	private function wrap_upload( $field_name, $desc ) {
		$upload_button	 = html( 'input', array( 'class' => 'upload_button button', 'rel' => $field_name, 'type' => 'button', 'value' => __( 'Upload Image', APP_TD ) ) );
		$clear_button	 = html( 'input', array( 'class' => 'delete_button button', 'rel' => $field_name, 'type' => 'button', 'value' => __( 'Clear Image', APP_TD ) ) );
		$preview		 = html( 'div', array( 'id' => $field_name . '_image', 'class' => 'upload_image_preview' ), html( 'img', array( 'src' => scbForms::get_value( $field_name, $this->options->get() ) ) ) );

		return $upload_button . ' ' . $clear_button . $desc . $preview;
	}

	function page_footer() {
		parent::page_footer();
?>
<script type="text/javascript">
	jQuery(document).ready(function () {
		/* upload logo and images */
		jQuery('.upload_button').click(function () {
			formfield = jQuery(this).attr('rel');
			tb_show('', 'media-upload.php?type=image&amp;post_id=0&amp;TB_iframe=true');
			return false;
		});

		/* send the uploaded image url to the field */
		window.send_to_editor = function (html) {
			imgurl = jQuery('img', html).attr('src'); // get the image url
			imgoutput = '<img src="' + imgurl + '" />'; //get the html to output for the image preview
			jQuery('#' + formfield).val(imgurl);
			jQuery('#' + formfield + '_image').html(imgoutput);
			tb_remove();
		};
	});
</script>
<?php

	}

	/**
	 * Display additional section on the permalinks page.
	 */
	public function init_integrated_options() {
		$this->permalink_sections();
	}

	### Permalinks

	protected function permalink_sections() {

		$option_page = 'permalink';
		$new_section = 'cp_options'; // store permalink options on global 'cp_options'

		$this->permalink_sections = array(
			'ads' => __( 'Ads Custom Post Type & Taxonomy URLs', APP_TD ),
		);

		$this->permalink_options['ads'] = array(
			'post_type_permalink'  => __( 'Ad Listing Base URL', APP_TD ),
			'ad_cat_tax_permalink' => __( 'Ad Category Base URL', APP_TD ),
			'ad_tag_tax_permalink' => __( 'Ad Tag Base URL', APP_TD ),
		);

		register_setting(
			$option_page, $new_section, array( $this, 'permalink_options_validate' )
		);

		foreach ( $this->permalink_sections as $section => $title ) {

			add_settings_section( $section, $title, '__return_false', $option_page );

			foreach ( $this->permalink_options[ $section ] as $id => $title ) {

				add_settings_field(
					$new_section . '_' . $id, $title, array( $this, 'permalink_section_add_option' ), // callback to output the new options
					$option_page, // options page
					$section, // section
					array( 'id' => $id ) // callback args [ database option, option id ]
				);

			}
		}
	}

	function permalink_section_add_option( $option ) {
		global $cp_options;

		echo scbForms::input( array(
			'type' => 'text',
			'name' => 'cp_options[' . $option['id'] . ']',
			'extra' => array( 'size' => 53 ),
			'value' => $cp_options->{$option['id']},
		) );
	}

	/**
	 * Validate/sanitize permalinks.
	 */
	function permalink_options_validate( $input ) {
		global $cp_options;

		$error_html_id = '';

		foreach ( $this->permalink_sections as $section => $title ) {

			foreach ( $this->permalink_options[ $section ] as $key => $value ) {

				if ( empty( $input[$key] ) ) {
					$error_html_id = $key;
					// set option to previous value
					$input[$key] = $cp_options->$key;
				} else {
					if ( ! is_array( $input[$key] ) ) {
						$input[ $key ] = trim( $input[ $key ] );
					}
					$input[ $key ] = stripslashes_deep( $input[ $key ] );
				}
			}
		}

		if ( $error_html_id ) {
			add_settings_error(
				'cp_options', $error_html_id, __( 'Custom post types and taxonomy URLs cannot be empty. Empty options will default to previous value.', APP_TD ), 'error'
			);
		}
		return $input;
	}

}

/**
 * Emails Settings Page.
 */
class CP_Theme_Settings_Emails extends APP_Tabs_Page {

	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title'			 => __( 'ClassiPress Emails', APP_TD ),
			'menu_title'			 => __( 'Emails', APP_TD ),
			'page_slug'				 => 'app-emails',
			'parent'				 => 'app-dashboard',
			'screen_icon'			 => 'options-general',
			'admin_action_priority'	 => 10,
		);
	}

	protected function init_tabs() {
		$this->tabs->add( 'general', __( 'General', APP_TD ) );
		$this->tabs->add( 'new_user', __( 'New User', APP_TD ) );

		$this->tab_sections['general']['notifications'] = array(
			'title'	 => __( 'Admin', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Recipient', APP_TD ),
					'name'	 => '_blank',
					'type'	 => '',
					'desc'	 => sprintf( __( '%1$s (<a href="%2$s">change</a>)', APP_TD ), get_option( 'admin_email' ), 'options-general.php' ),
					'extra'	 => array(
						'style' => 'display: none;'
					),
				),
				array(
					'title'	 => __( 'New Ad', APP_TD ),
					'name'	 => 'new_ad_email',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send an email on new ad submissions.', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Pruned Ad', APP_TD ),
					'name'	 => 'prune_ads_email',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send an email every time the system prunes expired ads.', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'New User', APP_TD ),
					'name'	 => 'nu_admin_email',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send the default WordPress new user notification email.', APP_TD ),
					'tip'	 => '',
				),
			),
		);

		$this->tab_sections['general']['user'] = array(
			'title'	 => __( 'User', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Approved Ad', APP_TD ),
					'name'	 => 'new_ad_email_owner',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send an email once their ad is approved.', APP_TD ),
					'tip'	 => __( 'This is triggered when post status changes from pending to published.', APP_TD ),
				),
				array(
					'title'	 => __( 'Expired Ad', APP_TD ),
					'name'	 => 'expired_ad_email_owner',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send an email once their ad has expired.', APP_TD ),
					'tip'	 => __( 'This is triggered when post status changes from published to draft.', APP_TD ),
				),
				array(
					'title'	 => __( 'Membership Activated', APP_TD ),
					'name'	 => 'membership_activated_email_owner',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send an email once their membership is activated.', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Membership Reminder', APP_TD ),
					'name'	 => 'membership_ending_reminder_email',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send a reminder email prior to the expiration date.', APP_TD ),
					'tip'	 => '',
				),
			),
		);


		$this->tab_sections['new_user']['settings'] = array(
			'title'	 => '',
			'fields' => array(
				array(
					'title'	 => __( 'Enable', APP_TD ),
					'name'	 => 'nu_custom_email',
					'type'	 => 'checkbox',
					'desc'	 => __( 'Send a custom new user notification email instead of the WordPress default one.', APP_TD ),
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Name', APP_TD ),
					'type'	 => 'text',
					'name'	 => 'nu_from_name',
					'tip'	 => __( 'This is what your users will see as the &quot;from&quot; name.', APP_TD ),
				),
				array(
					'title'	 => __( 'Email', APP_TD ),
					'type'	 => 'text',
					'name'	 => 'nu_from_email',
					'tip'	 => __( 'This is what your users will see as the &quot;from&quot; email address.', APP_TD ),
				),
				array(
					'title'	 => __( 'Subject', APP_TD ),
					'type'	 => 'text',
					'name'	 => 'nu_email_subject',
					'tip'	 => '',
				),
				array(
					'title'	 => __( 'Allow HTML', APP_TD ),
					'name'	 => 'nu_email_type',
					'type'	 => 'select',
					'values' => array(
						'text/HTML'	 => __( 'Yes', APP_TD ),
						'text/plain' => __( 'No', APP_TD ),
					),
					'tip'	 => __( 'Allow html markup in the email body below. If you have delivery problems, keep this option disabled.', APP_TD ),
				),
				array(
					'title'		 => __( 'Body', APP_TD ),
					'desc'		 => __( 'You may use the following variables within the email body and/or subject line.', APP_TD )
						. '<br />' . sprintf( __( '%s - prints out the username', APP_TD ), '<code>%username%</code>' )
						. '<br />' . sprintf( __( '%s - prints out the users email address', APP_TD ), '<code>%useremail%</code>' )
						. '<br />' . sprintf( __( '%s - prints out the users text password', APP_TD ), '<code>%password%</code>' )
						. '<br />' . sprintf( __( '%s - prints out your website url', APP_TD ), '<code>%siteurl%</code>' )
						. '<br />' . sprintf( __( '%s - prints out your site name', APP_TD ), '<code>%blogname%</code>' )
						. '<br />' . sprintf( __( '%s - prints out your sites login url', APP_TD ), '<code>%loginurl%</code>' )
						. '<br /><br />' . __( 'Each variable MUST have the percentage signs wrapped around it with no spaces.', APP_TD )
						. '<br />' . __( 'Always test your new email after making any changes (register) to make sure it is working and formatted correctly. If you do not receive an email, chances are something is wrong with your email body.', APP_TD ),
					'type'		 => 'textarea',
					'sanitize'	 => 'appthemes_clean',
					'name'		 => 'nu_email_body',
					'extra'		 => array(
						'rows'	 => 20,
						'cols'	 => 50,
						'class'	 => 'large-text code'
					),
					'tip'		 => '',
				),
			),
		);
	}

}

/**
 * Pricing Settings Page.
 */
class CP_Theme_Settings_Pricing extends APP_Tabs_Page {

	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title'			 => __( 'ClassiPress Pricing', APP_TD ),
			'menu_title'			 => __( 'Pricing', APP_TD ),
			'page_slug'				 => 'app-pricing',
			'parent'				 => 'app-dashboard',
			'screen_icon'			 => 'options-general',
			'admin_action_priority'	 => 10,
		);
	}

	/**
	 * Displays notice about disabled listings charge.
	 *
	 * @return void
	 */
	public function disabled_listings_charge_warning() {
		global $cp_options;

		if ( ! isset( $_GET['tab'] ) || $_GET['tab'] != 'membership' ) {
			return;
		}

		if ( ! $cp_options->charge_ads && $cp_options->enable_membership_packs ) {
			$message = __( 'Charge for Listing Ads option is currently <strong>disabled</strong>. ', APP_TD );
			if ( $cp_options->required_membership_type ) {
				$message .= ' ' . __( 'Membership will not affect ad listing purchase price, however purchasing membership still will be required to create or renew ad listings.', APP_TD );
			} else {
				$message .= ' ' . __( 'Membership will not affect ad listing purchase price.', APP_TD );
			}
			$this->admin_msg( $message );
		}
	}

	protected function init_tabs() {
		global $cp_options;

		add_action( 'admin_notices', array( $this, 'disabled_listings_charge_warning' ) );

		$this->tabs->add( 'general', __( 'General', APP_TD ) );
		$this->tabs->add( 'membership', __( 'Memberships', APP_TD ) );

		if ( $cp_options->price_scheme == 'category' || ( isset( $_POST['price_scheme'] ) && $_POST['price_scheme'] == 'category' ) ) {
			$this->tabs->add( 'category_price', __( 'Price Per Category', APP_TD ) );
		}

		if ( $cp_options->required_membership_type == 'category' || ( isset( $_POST['required_membership_type'] ) && $_POST['required_membership_type'] == 'category' ) ) {
			$this->tabs->add( 'membership_category', __( 'Membership by Category', APP_TD ) );
		}

		$this->tab_sections['general']['configuration'] = array(
			'title'	 => '',
			'fields' => array(
				array(
					'title'	 => __( 'Charge for Listing', APP_TD ),
					'name'	 => 'charge_ads',
					'type'	 => 'checkbox',
					'desc'	 => '',
					'tip'	 => __( 'This option activates the payment system so you can start charging for ad listings on your site.', APP_TD ),
				),
				array(
					'title'	 => __( 'Show Featured Slider', APP_TD ),
					'name'	 => 'enable_featured',
					'type'	 => 'checkbox',
					'desc'	 => '',
					'tip'	 => __( 'This option turns on the home page featured ads slider. Usually you charge extra for this space but it is not required. To manually make an ad appear here, check the &quot;stick this post to the front page&quot; box on the WordPress edit post page under &quot;Visibility&quot;.', APP_TD ),
				),
				array(
					'title'	 => __( 'Featured Title Length', APP_TD ),
					'name'	 => 'featured_trim',
					'type'	 => 'text',
					'tip'	 => __( 'This number controls the length of your featured ad titles to this many characters (i.e. if you changed this value to 5, &quot;My Title&quot; would turn into &quot;My Ti...&quot;. Spaces are included in the count.)', APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
				array(
					'title'	 => __( 'Featured Price', APP_TD ),
					'desc'	 => __( 'Only enter numeric values or decimal points. Do not include a currency symbol or commas.', APP_TD ),
					'type'	 => 'text',
					'name'	 => 'sys_feat_price',
					'tip'	 => __( 'This is the additional amount you will charge visitors to post a featured ad on your site. A featured ad appears in the slider on home page. Leave this blank if you do not want to offer featured ads.', APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
				array(
					'title'	 => __( 'Clean Price Field', APP_TD ),
					'name'	 => 'clean_price_field',
					'type'	 => 'checkbox',
					'desc'	 => __( 'This option should be enabled in order to store valid price values.', APP_TD ),
					'tip'	 => __( 'This will remove any letters and special characters from the price field leaving only numbers and periods. Disable this if you prefer to allow visitors to enter text such as TBD, OBO or other contextual phrases.', APP_TD ),
				),
				array(
					'title'	 => __( 'Empty Prices', APP_TD ),
					'name'	 => 'force_zeroprice',
					'type'	 => 'checkbox',
					'desc'	 => '',
					'tip'	 => __( 'This will force any ad without a price to display a currency of zero for the price.', APP_TD ),
				),
				array(
					'title'	 => __( 'Hide Decimals', APP_TD ),
					'name'	 => 'hide_decimals',
					'type'	 => 'checkbox',
					'desc'	 => '',
					'tip'	 => __( 'This will hide decimals for prices displayed on your site. Enable this option if your currency does not use decimals (i.e. Yen).', APP_TD ),
				),
				array(
					'title'	 => __( 'Currency Symbol', APP_TD ),
					'name'	 => 'curr_symbol',
					'type'	 => 'text',
					'tip'	 => __( 'Enter the currency symbol you want to appear next to prices on your classified ads (i.e. $, &euro;, &pound;, &yen;)', APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
			),
		);

		$this->tab_sections['general']['model'] = array(
			'title'	 => __( 'Pricing Model', APP_TD ),
			'fields' => array(
				array(
					'title'	 => __( 'Price Model', APP_TD ),
					'name'	 => 'price_scheme',
					'type'	 => 'select',
					'desc'	 => '<br />' . sprintf( __( 'If you select the &quot;Fixed Price Per Ad&quot; option, you must have at least one active <a href="%s">ad pack</a> setup.', APP_TD ), 'edit.php?post_type=package-listing' ),
					'values' => array(
						'single'	 => __( 'Fixed Price Per Ad', APP_TD ),
						'category'	 => __( 'Price Per Category', APP_TD ),
						'percentage' => __( '% of Sellers Ad Price', APP_TD ),
						'featured'	 => __( 'Only Charge for Featured Ads', APP_TD ),
					),
					'tip'	 => __( 'This option defines the pricing model for selling ads on your site. If you want to provide free and paid ads then select the &quot;Price Per Category&quot; option.', APP_TD ),
				),
				array(
					'title'	 => __( '% of Sellers Ad Price', APP_TD ),
					'name'	 => 'percent_per_ad',
					'type'	 => 'text',
					'tip'	 => __( 'If you selected the &quot;% of Sellers Ad Price&quot; price model, enter your percentage here. Numbers only. No percentage symbol or commas.', APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
			),
		);


		$this->tab_sections['membership']['configuration'] = array(
			'title'	 => '',
			'fields' => array(
				array(
					'title'	 => __( 'Enable Packs', APP_TD ),
					'name'	 => 'enable_membership_packs',
					'type'	 => 'checkbox',
					'desc'	 => sprintf( __( 'Manage your <a href="%s">membership packs</a>.', APP_TD ), 'edit.php?post_type=package-membership' ),
					'tip'	 => __( 'This option activates Membership Packs and their respective discounts. Disabling this does not disable the membership system, but simply stops the discounts from activating during the posting process.', APP_TD ),
				),
				array(
					'title'	 => __( 'Reminders', APP_TD ),
					'name'	 => 'membership_ending_reminder_days',
					'type'	 => 'number',
					'desc'	 => __( 'Affects both email and website notifications.', APP_TD ),
					'tip'	 => __( 'Number of days you would like to send renewal reminders before their subscription expires. Numeric values only.', APP_TD ),
					'extra'	 => array(
						'class' => 'small-text'
					),
				),
				array(
					'title'	 => __( 'Membership to Buy Ads', APP_TD ),
					'name'	 => 'required_membership_type',
					'type'	 => 'select',
					'values' => array(
						''			 => __( 'Not Required', APP_TD ),
						'all'		 => __( 'Required for All', APP_TD ),
						'category'	 => __( 'Required by Category', APP_TD ),
					),
					'tip'	 =>
						__( "<strong>Not Required</strong> - a membership isn't needed to list an ad.", APP_TD ) . '<br />' .
						__( '<strong>Required for All</strong> - must have an active membership to list an ad.', APP_TD ) . '<br />' .
						__( '<strong>Required by Category</strong> - limits users with memberships to list ads in certain categories.', APP_TD ),
				),
			),
		);


		$this->tab_sections['category_price']['price'] = array(
			'title'	 => '',
			'fields' => $this->price_per_category(),
		);

		$this->tab_sections['membership_category']['required'] = array(
			'title'	 => __( 'Membership by Category', APP_TD ),
			'fields' => $this->membership_by_category(),
		);
	}

	private function price_per_category() {
		$options	 = array();
		$cats		 = array();
		$subcats	 = array();
		$categories	 = (array) get_terms( APP_TAX_CAT, array( 'orderby' => 'name', 'order' => 'asc', 'hide_empty' => false ) );

		// separate categories from subcategories
		foreach ( $categories as $key => $category ) {
			if ( $category->parent == 0 ) {
				$cats[ $key ] = $categories[ $key ];
			} else {
				$subcats[ $key ] = $categories[ $key ];
			}

			unset( $categories[ $key ] );
		}

		// loop through all the cats
		foreach ( $cats as $cat ) {
			$options = $this->price_per_category_option( $options, $cat, 0 );
			$options = $this->price_per_category_walk( $options, $subcats, $cat->term_id, 0 );
		}

		return $options;
	}

	private function price_per_category_walk( $options, $subcats, $parent, $depth = 0 ) {
		$depth++;

		foreach ( $subcats as $subcat ) {
			if ( $subcat->parent != $parent ) {
				continue;
			}

			$options = $this->price_per_category_option( $options, $subcat, $depth );
			$options = $this->price_per_category_walk( $options, $subcats, $subcat->term_id, $depth );
		}

		return $options;
	}

	private function price_per_category_option( $options, $category, $depth = 0 ) {
		global $cp_options;

		$pad = str_repeat( ' - ', $depth );

		$options[] = array(
			'title'		 => $pad . $category->name,
			'type'		 => 'text',
			'extra'		 => array( 'class' => 'small-text' ),
			'name'		 => array( 'price_per_cat', $category->term_id ),
			'desc'		 => $cp_options->currency_code,
			'default'	 => 0,
		);

		return $options;
	}

	private function membership_by_category() {
		$options	 = array();
		$cats		 = array();
		$subcats	 = array();
		$categories	 = (array) get_terms( APP_TAX_CAT, array( 'orderby' => 'name', 'order' => 'asc', 'hide_empty' => false ) );

		// separate categories from subcategories
		foreach ( $categories as $key => $category ) {
			if ( $category->parent == 0 ) {
				$cats[ $key ] = $categories[ $key ];
			} else {
				$subcats[ $key ] = $categories[ $key ];
			}

			unset( $categories[ $key ] );
		}

		// loop through all the cats
		foreach ( $cats as $cat ) {
			$options = $this->membership_by_category_option( $options, $cat, 0 );
			$options = $this->membership_by_category_walk( $options, $subcats, $cat->term_id, 0 );
		}

		return $options;
	}

	private function membership_by_category_walk( $options, $subcats, $parent, $depth = 0 ) {
		$depth++;

		foreach ( $subcats as $subcat ) {
			if ( $subcat->parent != $parent ) {
				continue;
			}

			$options = $this->membership_by_category_option( $options, $subcat, $depth );
			$options = $this->membership_by_category_walk( $options, $subcats, $subcat->term_id, $depth );
		}

		return $options;
	}

	private function membership_by_category_option( $options, $category, $depth = 0 ) {
		$pad = str_repeat( ' - ', $depth );

		$options[] = array(
			'title'	 => $pad . $category->name,
			'type'	 => 'checkbox',
			'name'	 => array( 'required_categories', $category->term_id ),
			'desc'	 => '',
		);

		return $options;
	}

}
