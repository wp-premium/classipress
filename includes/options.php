<?php
/**
 * Default Options.
 *
 * @package ClassiPress\Options
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */

$GLOBALS['cp_options'] = new scbOptions( 'cp_options', false, array(

	// appearance
	'stylesheet'                  => 'red.css',
	'bgcolor'                     => '#EFEFEF',
	'header_bgcolor'              => '#EFEFEF',
	'top_nav_bgcolor'             => '',
	'top_nav_links_color'         => '',
	'top_nav_text_color'          => '',
	'main_nav_bgcolor'            => '',
	'buttons_bgcolor'             => '',
	'buttons_text_color'          => '',
	'links_color'                 => '',
	'footer_bgcolor'              => '#313131',
	'footer_text_color'           => '',
	'footer_links_color'          => '',
	'footer_titles_color'         => '',
	'home_layout'                 => 'directory',

	// configuration
	'allow_registration_password' => 0,
	'favicon_url'                 => '',
	'feedburner_url'              => '',
	'twitter_username'            => '',
	'facebook_id'                 => '',
	'google_analytics'            => '',

	// footer
	'footer_width'                => '940px',
	'footer_col_width'            => '200px',

	// Google Maps Settings
	'gmaps_region'                => 'US',
	'gmaps_lang'                  => 'en',
	'distance_unit'               => 'mi',
	'api_key'                     => '',

	// Search Settings
	'search_ex_pages'             => 1,
	'search_ex_blog'              => 1,
	'refine_price_slider'         => 1,
	'search_field_width'          => '',

	// Search Drop-down Options
	'search_depth'                => 0,
	'cat_hierarchy'               => 1,
	'cat_count'                   => 0,
	'cat_hide_empty'              => 0,

	// Categories Menu Item Options
	'cat_menu_count'              => 0,
	'cat_menu_hide_empty'         => 0,
	'cat_menu_depth'              => 3,
	'cat_menu_sub_num'            => 3,

	// Categories Page Options
	'cat_dir_count'               => 0,
	'cat_dir_hide_empty'          => 0,
	'cat_dir_depth'               => 3,
	'cat_dir_sub_num'             => 3,
	'cat_dir_cols'                => 2,

	// Classified Ads Messages
	'ads_welcome_msg' =>
		html( 'h2', array( 'class' => 'colour_top' ), __( 'Welcome to our Web site!', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'h1', html( 'span', array( 'class' => 'colour' ), __( 'List Your Classified Ads', APP_TD ) ) ) . PHP_EOL . PHP_EOL .
		html( 'p', __( 'We are your #1 classified ad listing site. Become a free member and start listing your classified ads within minutes. Manage all ads from your personalized dashboard.', APP_TD ) ),

	'ads_form_msg' =>
		html( 'p', __( 'Please fill in the fields below to post your classified ad. Required fields are denoted by a *. You will be given the opportunity to review your ad before it is posted.', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'p', 'Neque porro quisquam est qui dolorem rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit asp.' ) . PHP_EOL . PHP_EOL .
		html( 'p', html( 'em', html( 'span', array( 'class' => 'colour' ), 'Rui nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquaear diff, and optional ceramic brake rotors can now all be orchestrated al fresco.' ) ) ) . PHP_EOL . PHP_EOL .
		html( 'p', 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunsciunt. Neque porro quisquam est, qui dolorem ipsum.' ),

	'membership_form_msg' =>
		html( 'p', __( 'Please select a membership package that you would like to purchase for your account.', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'p', 'Neque porro quisquam est qui dolorem rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit asp.' ) . PHP_EOL . PHP_EOL .
		html( 'p', html( 'em', html( 'span', array( 'class' => 'colour' ), __( 'Please note that changing membership plans before your current membership expires will cancel your current membership with no refund.', APP_TD ) ) ) ),

	'ads_tou_msg' =>
		html( 'h3', __( 'RULES AND GUIDELINES', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'p', __( 'By posting your classified ad here, you agree that it is in compliance with our guidelines listed below.', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'p', __( 'We reserve the right to modify any ads in violation of our guidelines order to prevent abuse and keep the content appropriate for our general audience. This includes people of all ages, races, religions, and nationalities. Therefore, all ads that are in violation of our guidelines are subject to being removed immediately and without prior notice.', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'p', __( 'By posting an ad on our site, you agree to the following statement:', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'p', __( 'I agree that I will be solely responsible for the content of any classified ads that I post on this website. I will not hold the owner of this website responsible for any losses or damages to myself or to others that may result directly or indirectly from any ads that I post here.', APP_TD ) ) . PHP_EOL . PHP_EOL .
		html( 'p', __( 'By posting an ad on our site, you further agree to the following guidelines:', APP_TD ) ) . PHP_EOL . PHP_EOL .
		'<ol>' . PHP_EOL .
		html( 'li', __( 'No foul or otherwise inappropriate language will be tolerated. Ads in violation of this rule are subject to being removed immediately and without warning. If it was a paid ad, no refund will be issues.', APP_TD ) ) . PHP_EOL .
		html( 'li', __( 'No racist, hateful, or otherwise offensive comments will be tolerated.', APP_TD ) ) . PHP_EOL .
		html( 'li', __( 'No ad promoting activities that are illegal under the current laws of this state or country.', APP_TD ) ) . PHP_EOL .
		html( 'li', __( 'Any ad that appears to be merely a test posting, a joke, or otherwise insincere or non-serious is subject to removal.', APP_TD ) ) . PHP_EOL .
		html( 'li', __( 'We reserve the ultimate discretion as to which ads, if any, are in violation of these guidelines.', APP_TD ) ) . PHP_EOL .
		'</ol>' . PHP_EOL . PHP_EOL .
		html( 'p', __( 'Thank you for your understanding.', APP_TD ) ),


	// Classified Ads Configuration
	'ad_edit'                     => 1,
	'allow_relist'                => 1,
	'ad_parent_posting'           => 'yes',
	'ad_inquiry_form'             => 0,
	'allow_html'                  => 0,
	'ad_stats_all'                => 1,
	'ad_gravatar_thumb'           => 0,
	'moderate_ads'                => 0,
	'moderate_edited_ads'         => 0,
	'post_prune'                  => 0,
	'ad_expired_check_recurrance' => 'daily',
	'prun_period'                 => 90,

	// Ad Images Options
	'ad_images'                   => 1,
	'require_images'              => 0,
	'ad_image_preview'            => 1,
	'num_images'                  => 3,
	'max_image_size'              => 1024,

	// Security Settings
	'admin_security'              => 'read',

	// reCaptcha Settings
	'captcha_enable'              => 0,
	'captcha_public_key'          => '',
	'captcha_private_key'         => '',
	'captcha_theme'               => 'light',

	// Header Ad (468x60)
	'adcode_468x60_enable'        => 1,
	'adcode_468x60'               => '',
	'adcode_468x60_url'           => '',
	'adcode_468x60_dest'          => '',

	// Content Ad (336x280)
	'adcode_336x280_enable'       => 0,
	'adcode_336x280'              => '',
	'adcode_336x280_url'          => '',
	'adcode_336x280_dest'         => '',

	// Advanced Options
	'disable_stylesheet'          => 0,
	'debug_mode'                  => 0,
	'google_jquery'               => 0,
	'selectbox'                   => 0,
	'disable_wp_login'            => 0,
	'remove_wp_generator'         => 0,
	'remove_admin_bar'            => 0,
	'disable_embeds'              => 0,
	'display_website_time'        => 0,
	'cache_expires'               => 3600,
	'ad_right_class'              => 'full',

	// Custom Post Type & Taxonomy URLs
	'post_type_permalink'         => 'ads',
	'ad_cat_tax_permalink'        => 'ad-category',
	'ad_tag_tax_permalink'        => 'ad-tag',

	// Cufon Font Replacement
	'cufon_enable'                     => 0,
	'cufon_code'                       => "Cufon.replace('.content_right h2.dotted', { fontFamily: 'Liberation Serif', textShadow:'0 1px 0 #FFFFFF' });",

	// Email Notifications
	'new_ad_email'                     => 1,
	'prune_ads_email'                  => 0,
	'new_ad_email_owner'               => 1,
	'expired_ad_email_owner'           => 1,
	'nu_admin_email'                   => 1,
	'membership_activated_email_owner' => 1,
	'membership_ending_reminder_email' => 1,

	// New User Registration Email
	'nu_custom_email' => 0,
	'nu_from_name' => get_option( 'blogname' ),
	'nu_from_email' => get_option( 'admin_email' ),
	'nu_email_subject' => __( 'Thank you for registering, %username%', APP_TD ),
	'nu_email_type' => 'text/plain',
	'nu_email_body' =>
		sprintf( __( 'Hi %s,', APP_TD ), '%username%' ) . PHP_EOL . PHP_EOL .
		sprintf( __( 'Welcome to %s, your leader in online classified ad listings.', APP_TD ), '%blogname%' ) . PHP_EOL . PHP_EOL .
		__( 'Below you will find your username and password which allows you to login to your user account and begin posting classified ads.', APP_TD ) . PHP_EOL . PHP_EOL .
		'--------------------------' . PHP_EOL .
		sprintf( __( 'Username: %s', APP_TD ), '%username%' ) . PHP_EOL .
		sprintf( __( 'Password: %s', APP_TD ), '%password%' ) . PHP_EOL .
		'%loginurl%' . PHP_EOL .
		'--------------------------' . PHP_EOL . PHP_EOL .
		__( 'If you have any questions, please just let us know.', APP_TD ) . PHP_EOL . PHP_EOL .
		__( 'Best regards,', APP_TD ) . PHP_EOL .
		sprintf( __( 'Your %s Team', APP_TD ), '%blogname%' ) . PHP_EOL .
		'%siteurl%',

	// Pricing Configuration
	'charge_ads'                      => 0,
	'enable_featured'                 => 1,
	'featured_trim'                   => 30,
	'sys_feat_price'                  => 10,
	'clean_price_field'               => 1,
	'force_zeroprice'                 => 0,
	'hide_decimals'                   => 0,
	'curr_symbol'                     => '$',

	// Pricing Model
	'price_scheme'                    => 'single',
	'percent_per_ad'                  => 0,

	// Membership Options
	'enable_membership_packs'         => 1,
	'membership_ending_reminder_days' => 7,
	'required_membership_type'        => '',

	// Price Per Category
	'price_per_cat'                   => array(),

	// Membership by Category
	'required_categories'             => array(),

	// Report Ad Settings
	'reports' => array(
		'post_options' =>
			__( 'Offensive Content', APP_TD ) . "\n" .
			__( 'Invalid Offer', APP_TD ) . "\n" .
			__( 'Spam', APP_TD ) . "\n" .
			__( 'Other', APP_TD ),
		'user_options' => '',
		'users_only'   => 0,
		'send_email'   => 1,
	),

	// Payments & Gateways
	'allow_view_orders'   => false,
	'currency_code'       => 'USD',
	'currency_identifier' => 'symbol',
	'currency_position'   => 'left',
	'thousands_separator' => ',',
	'decimal_separator'   => '.',
	'tax_charge'          => 0,
	'gateways' => array(
		'enabled' => array(),
	),

	// Deprecated
	'use_logo' => 1,
	'logo'     => '',

) );
