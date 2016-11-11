<?php
/**
 * Framework API
 *
 * @package Framework\Functions
 */

/**
 * Merges the main translation file ( textdomain APP_TD ) with plugin specific translation file
 *
 * @param string $domain          Textdomain to merge with APP_TD
 * @param string $plugin_rel_path Optional. Relative path to WP_PLUGIN_DIR where the .mo file resides.
 *                                Default false.
 *
 * @return bool True when textdomain is successfully loaded and merged, false otherwise.
 */
function appthemes_merge_plugin_textdomain( $domain = '', $plugin_rel_path = false ) {

	if ( class_exists( 'APP_Core' ) ) {
		return APP_Core::merge_textdomain( $domain, $plugin_rel_path );
	} else {
		_doing_it_wrong( __FUNCTION__, __( 'APP_Core class required.', APP_TD ), null );
	}
}

/**
 * A version of load_template() with support for passing arbitrary values.
 *
 * @param string|array Template name(s) to pass to locate_template()
 * @param array Additional data
 */
function appthemes_load_template( $templates, $data = array() ) {
	$located = appthemes_locate_template( $templates );

	if ( ! $located ) {
		return;
	}

	global $posts, $post, $wp_query, $wp_rewrite, $wpdb, $comment;

	extract( $data, EXTR_SKIP );

	if ( is_array( $wp_query->query_vars ) ) {
		extract( $wp_query->query_vars, EXTR_SKIP );
	}

	require $located;
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * @uses appthemes_get_template_directories() to get all registered template locations
 * @todo deprecate it in future, {@link https://core.trac.wordpress.org/attachment/ticket/22355/22355.4.patch}
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function appthemes_locate_template( $template_names, $load = false, $require_once = true ) {

	$located   = '';
	$directories = array_keys( appthemes_get_template_directories() );

	foreach ( (array) $template_names as $template_name ) {
		if ( ! $template_name ) {
			continue;
		}

		foreach ( $directories as $directory ) {
			if ( file_exists( rtrim( $directory ) . '/' . $template_name ) ) {
				$located = rtrim( $directory ) . '/' . $template_name;
				break 2;
			}
		}
	}

	if ( $load && '' != $located ) {
		load_template( $located, $require_once );
	}

	return $located;
}

/**
 * Load a template part into a template
 *
 * Modified version of WP `get_template_part()` function
 * with the only difference, that function uses `appthemes_locate_template()`
 * instead of `locate_template()`. What allows to locate templates in the
 * custom folder or framework folder
 *
 * @uses appthemes_locate_template()
 * @uses do_action()
 *
 * @todo deprecate it in future, {@link https://core.trac.wordpress.org/attachment/ticket/22355/22355.4.patch}
 *
 * @param string $slug The slug name for the generic template.
 * @param string $name The name of the specialised template.
 */
function appthemes_get_template_part( $slug, $name = null ) {
	/**
	 * Fires before the specified template part file is loaded.
	 *
	 * The dynamic portion of the hook name, $slug, refers to the slug name
	 * for the generic template part.
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string $name The name of the specialized template.
	 */
	do_action( "get_template_part_{$slug}", $slug, $name );

	$templates = array();
	$name = (string) $name;
	if ( '' !== $name ) {
		$templates[] = "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	appthemes_locate_template( $templates, true, false );
}

/**
 * Retrieve registered template stack locations
 *
 * Gives a map of theme base directories to theme base URLs, allowing searching
 * themes for a certain file in order.
 *
 * @link https://core.trac.wordpress.org/attachment/ticket/22355/22355.4.patch
 * @todo deprecate in future, {@link get_template_directories()}
 * @return array Map of base directory to base URL
 */
function appthemes_get_template_directories() {

	// highest priority locations
	$directories = array(
		get_stylesheet_directory() => get_stylesheet_directory_uri(),
		get_template_directory()   => get_template_directory_uri(),
	);

	// customizable part of stack
	$directories = apply_filters( 'appthemes_get_template_directories', $directories );

	// strict lowest priority location
	$directories[ APP_FRAMEWORK_DIR ] = APP_FRAMEWORK_URI;
	$directories[ APP_FRAMEWORK_DIR . '/templates' ] = APP_FRAMEWORK_URI . '/templates';

	return $directories;
}

/**
 * Loads a set of files using the given path. Used for iterating over lots of files
 *
 * @param string $path The base path to load files from
 * @param array $files An array of files to load
 *
 * @return void
 */
function appthemes_load_files( $path, $files = array() ) {
	foreach ( $files as $file_path ) {
		require_once $path . $file_path;
	}
}

/**
 * Adds a single key/value pair or list of key/value pairs to WP_Query to be used on a template file.
 *
 * @param string|array $query_var  A key/value pair array list or the key to be assigned with $value
 * @param mixed $value (optional)  The value to be stored
 *
 * @return void
 */
function appthemes_add_template_var( $query_var, $value = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) || empty( $wp_query->query_vars ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Query vars cannot be added before WP_Query is instantiated and default query vars has not been pre-filled yet.', APP_TD ), null );
		return false;
	}

	if ( is_array( $query_var ) ) {
		foreach ( $query_var as $key => $value ) {
			if ( ! array_key_exists( $key, $wp_query->query_vars ) ) {
				$wp_query->set( $key, $value );
			}
		}
	} else {
		if ( ! array_key_exists( $query_var, $wp_query->query_vars ) ) {
			$wp_query->set( $query_var, $value );
		}
	}

}

/**
 * Instantiates a class using an array of arguments positionally.
 *
 * @param string The class name to instantiate a object type of
 * @param array (optional) An array of class arguments
 *
 * @return obj The instantiated object of the $class type
 */
function appthemes_instantiate_class( $class, $args = array() ) {
	if ( count( $args ) == 0 ) {
		return new $class();
	} else {
		$reflection = new ReflectionClass( $class );
		return $reflection->newInstanceArgs( $args );
	}
}

/**
 * Adds a class instance into global variable $app_instances that holds an array of instances.
 *
 * @param string|object|array $class A class name, class object, or array of class names or objects.
 * @param array An array of arguments to be used positionally when instantiating the object. Only used
 * 	if $class is a string
 *
 * @return bool Boolean True on success, False on failure
 */
function appthemes_add_instance( $class, $args = array() ) {
	global $app_instances;

	if ( ! isset( $app_instances ) ) {
		$app_instances = array();
	}

	if ( is_array( $class ) ) {
		foreach ( $class as $class_name => $class_args ) {
			if ( is_int( $class_name ) ) {
				appthemes_add_instance( $class_args );
			} else {
				appthemes_add_instance( $class_name, $class_args );
			}
		}
	} else if ( is_object( $class ) ) {
		$app_instances[ get_class( $class ) ] = $class;
	} else if ( is_string( $class ) && class_exists( $class ) ) {
		$args = ( ! is_array( $args ) ) ? array( $args ) : $args;
		$app_instances[ $class ] = appthemes_instantiate_class( $class, $args );
	} else {
		return false;
	}

	return true;
}

/**
 * Returns a class instance from global variable $app_instances that holds an array of instances.
 *
 * @param string $class_name A class name.
 *
 * @return object|bool Class object on success, Boolean False on failure
 */
function appthemes_get_instance( $class_name ) {
	global $app_instances;

	if ( ! is_string( $class_name ) || ! isset( $app_instances[ $class_name ] ) ) {
		return false;
	}

	return $app_instances[ $class_name ];
}

/**
 * Checks if a user is logged in, if not redirect them to the login page.
 */
function appthemes_auth_redirect_login() {
	if ( ! is_user_logged_in() ) {
		nocache_headers();
		wp_redirect( wp_login_url( scbUtil::get_current_url() ) );
		exit();
	}
}

function appthemes_require_login( $args = array() ) {

	if ( is_user_logged_in() ) {
		return;
	}

	$page_url = scbUtil::get_current_url();

	$args = wp_parse_args( $args, array(
		'login_text' => __( 'You must first login.', APP_TD ),
		'login_register_text' => __( 'You must first login or <a href="%s">register</a>.', APP_TD ),
	) );

	if ( get_option( 'users_can_register' ) ) {
		$register_url = appthemes_get_registration_url();
		$register_url = add_query_arg( 'redirect_to', $page_url, $register_url );

		$message = sprintf( $args['login_register_text'], $register_url );
	} else {
		$message = $args['login_text'];
	}

	appthemes_set_visitor_transient( 'login_notice', array( 'error', $message ), 300 );

	appthemes_auth_redirect_login();
	exit;

}

/**
 * Generates a login form that goes in the admin bar.
 */
function appthemes_admin_bar_login_form( $wp_admin_bar ) {
	if ( is_user_logged_in() ) {
		return;
	}

	$form = wp_login_form( array(
		'form_id' => 'adminloginform',
		'echo' => false,
		'value_remember' => true,
	) );

	$wp_admin_bar->add_menu( array(
		'id'     => 'login',
		'title'  => $form,
	) );

	$wp_admin_bar->add_menu( array(
		'id'     => 'lostpassword',
		'title'  => __( 'Lost password?', APP_TD ),
		'href'   => wp_lostpassword_url(),
	) );

	if ( get_option( 'users_can_register' ) ) {
		$wp_admin_bar->add_menu( array(
			'id'     => 'register',
			'title'  => __( 'Register', APP_TD ),
			'href'   => site_url( 'wp-login.php?action=register', 'login' ),
		) );
	}
}

/**
 * Generates pagination links.
 */
function appthemes_pagenavi( $wp_query = null, $query_var = 'paged', $args = array() ) {
	if ( is_null( $wp_query ) ) {
		$wp_query = $GLOBALS['wp_query'];
	}

	if ( is_object( $wp_query ) ) {
		$params = array(
			'total' => $wp_query->max_num_pages,
			'current' => $wp_query->get( $query_var )
		);
	} else {
		$params = $wp_query;
	}

	$big = 999999999;
	$base = str_replace( $big, '%#%', html_entity_decode( get_pagenum_link( $big ) ) );
	$pages_text = '';

	$default_args = array(
		'base' => $base,
		'format' => '?' . $query_var . '=%#%',
		'current' => max( 1, $params['current'] ),
		'total' => $params['total'],
		'echo' => true,
		'pages_text' => false,
		'type' => 'plain',
	);
	$args = wp_parse_args( $args, $default_args );

	$args = apply_filters( 'appthemes_pagenavi_args', $args );

	if ( $args['total'] < 2 ) {
		return false;
	}

	if ( $args['pages_text'] ) {
		$pages_text = '<span class="total">' . sprintf( __( 'Page %s of %s', APP_TD ), $args['current'], $args['total'] ) . '</span>';
	}

	if ( 'array' === $args['type'] ) {
		$paginate_links = array();
		if ( $pages_text ) {
			$paginate_links[] = $pages_text;
		}
		$paginate_links = array_merge( $paginate_links, paginate_links( $args ) );

	} else {
		$paginate_links = $pages_text . paginate_links( $args );
	}

	if ( $args['echo'] && ! is_array( $paginate_links ) ) {
		echo $paginate_links;
	} else {
		return $paginate_links;
	}
}

/**
 * Generates and prints pagination links.
 *
 * @param string $before (optional) HTML code to be added before output
 * @param string $after (optional) HTML code to be added after output
 * @param object $wp_query (optional) A WP_Query object
 *
 * @return void
 */
function appthemes_pagination( $before = '', $after = '', $wp_query = null ) {
	if ( is_single() ) {
		return;
	}

	$args = array(
		'echo' => false,
		'pages_text' => true,
		'prev_text' => '&lsaquo;&lsaquo;',
		'next_text' => '&rsaquo;&rsaquo;',
	);

	$paginate_links = appthemes_pagenavi( $wp_query, 'paged', $args );

	if ( $paginate_links ) {
		echo $before . '<div class="paging"><div class="pages">';
		echo $paginate_links;
		echo '</div><div class="clr"></div></div>' . $after;
	}
}

/**
 * Retrieve the URI of the highest priority file that exists.
 *
 * @uses appthemes_get_template_directories() to get all registered template locations
 * @link http://core.trac.wordpress.org/attachment/ticket/18302/18302.2.2.patch
 * @param string|array $template_names Template file(s) to search for, in order.
 *
 * @return string The file URI if one is located.
 */
function appthemes_locate_template_uri( $template_names ) {
	$located = '';
	$directories = appthemes_get_template_directories();

	foreach ( (array) $template_names as $template_name ) {
		if ( ! $template_name ) {
			continue;
		}

		foreach ( $directories as $directory => $uri ) {
			if ( file_exists( rtrim( $directory ) . '/' . $template_name ) ) {
				$located = rtrim( $uri ) . '/' . $template_name;
				break 2;
			}
		}
	}

	return $located;
}

/**
 * Simple wrapper for adding straight rewrite rules,
 * but with the matched rule as an associative array.
 *
 * @see http://core.trac.wordpress.org/ticket/16840
 *
 * @param string $regex The rewrite regex
 * @param array $args The mapped args
 * @param string $position Where to stick this rule in the rules array. Can be 'top' or 'bottom'
 */
function appthemes_add_rewrite_rule( $regex, $args, $position = 'top' ) {
	add_rewrite_rule( $regex, add_query_arg( $args, 'index.php' ), $position );
}

/**
 * Utility to create an auto-draft post, to be used on front-end forms.
 *
 * @param string $post_type
 * @return object
 */
function appthemes_get_draft_post( $post_type ) {
	$key = 'draft_' . $post_type . '_id';

	$draft_post_id = (int) get_user_option( $key );

	if ( $draft_post_id ) {
		$draft = get_post( $draft_post_id );

		if ( ! empty( $draft ) && $draft->post_status == 'auto-draft' ) {
			return $draft;
		}
	}

	require_once ABSPATH . '/wp-admin/includes/post.php';

	$draft = get_default_post_to_edit( $post_type, true );

	update_user_option( get_current_user_id(), $key, $draft->ID );

	return $draft;
}

/**
 * Sets a transient
 *
 * Sets transient through set_transient while appending the users IP
 * Using appthemes_get_ip()
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @param mixed $value Transient value. Expected to not be SQL-escaped.
 * @param int $expiration Time until expiration in seconds, default 0
 * @return bool false if value was not set and true if value was set.
 */
function appthemes_set_visitor_transient( $transient, $value, $expiration = 0 ) {
	$transient = $transient . '-' . appthemes_get_ip();
	return set_transient( $transient, $value, $expiration );
}

/**
 * Gets a transient
 *
 * Gets a transient that was set through appthemes_set_transient,
 * calls get_transient() after reconstructing transient appending users
 * IP using appthemes_get_ip()
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped
 * @return mixed Value of transient
 */
function appthemes_get_visitor_transient( $transient ) {
	$transient = $transient . '-' . appthemes_get_ip();
	return get_transient( $transient );
}

/**
 * Deletes a transient
 *
 * Deletes a transient that was set through appthemes_set_transient,
 * calls delete_transient() after reconstructing transient appending users
 * IP using appthemes_get_ip()
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return bool true if successful, false otherwise
 */
function appthemes_delete_visitor_transient( $transient ) {
	$transient = $transient . '-' . appthemes_get_ip();
	return delete_transient( $transient );
}

/**
 * Create categories list.
 *
 * @param array $args
 * @param array $terms_args
 *
 * @return string
 */
function appthemes_categories_list( $args, $terms_args = array() ) {

	$defaults = array(
		'menu_cols' => 2,
		'menu_depth' => 3,
		'menu_sub_num' => 3,
		'cat_parent_count' => false,
		'cat_child_count' => false,
		'cat_hide_empty' => false,
		'cat_nocatstext' => true,
		'taxonomy' => 'category',
	);

	$options = wp_parse_args( (array) $args, $defaults );

	$terms_defaults = array(
		'hide_empty' => false,
		'hierarchical' => true,
		'pad_counts' => true,
		'show_count' => true,
		'orderby' => 'name',
		'order' => 'ASC',
		'child_of' => 0,
	);

	$terms_args = wp_parse_args( (array) $terms_args, $terms_defaults );

	// get all terms for the taxonomy
	$terms = get_terms( $options['taxonomy'], $terms_args );
	$cats = array();
	$subcats = array();
	$cat_menu = '';

	if ( empty( $terms ) ) {
		return '';
	}

	// separate into cats and subcats arrays
	foreach ( $terms as $key => $value ) {
		if ( $value->parent == $terms_args['child_of'] ) {
			$cats[ $key ] = $terms[ $key ];
		} else {
			$subcats[ $key ] = $terms[ $key ];
		}
		unset( $terms[ $key ] );
	}

	$i = 0;
	$cat_cols = $options['menu_cols']; // menu columns
	$total_main_cats = count( $cats ); // total number of parent cats
	$cats_per_col = ceil( $total_main_cats / $cat_cols ); // parent cats per column
	$first = ' first';

	// loop through all the cats
	foreach ( $cats as $cat ) {

		if ( ( $i % $cats_per_col ) == 0 ) {
			$cat_menu .= '<div class="catcol' . $first . '">';
			$cat_menu .= '<ul class="maincat-list">';
		}

		$first = '';

		// only show the total count if option is set
		$show_count = $options['cat_parent_count'] ? '<span class="cat-item-count">(' . $cat->count . ')</span>' : '';

		$cat_menu .= '<li class="maincat cat-item-' . $cat->term_id . '"><a href="' . get_term_link( $cat, $options['taxonomy'] ) . '" title="' . esc_attr( $cat->description ) . '">' . $cat->name . '</a> ' . $show_count . ' ';
		if ( $options['menu_sub_num'] > 0 ) {
			// create child tree
			$temp_menu = appthemes_create_child_list( $subcats, $options['taxonomy'], $cat->term_id, 0, $options['menu_depth'], $options['menu_sub_num'], $options['cat_child_count'], $options['cat_hide_empty'] );
			if ( $temp_menu ) {
				$cat_menu .= $temp_menu;
			}
			if ( ! $temp_menu && ! $options['cat_nocatstext'] ) {
				$cat_menu .= '<ul class="subcat-list"><li class="cat-item">' . __( 'No categories', APP_TD ) . "</li>\r\n</ul>\r\n";
			}
		}
		$cat_menu .= "</li>\r\n";

		$i++;

		if ( ( $i % $cats_per_col ) == 0 || $i >= $total_main_cats ) {
			$cat_menu .= "</ul>\r\n";
			$cat_menu .= "</div><!-- /catcol -->\r\n";
		}

	}

	return $cat_menu;
}


/**
 * Creates child list, helper function for appthemes_categories_list().
 *
 * @param array $subcats
 * @param string $taxonomy
 * @param int $parent
 * @param int $curr_depth
 * @param int $max_depth
 * @param int $max_subcats
 * @param bool $child_count
 * @param bool $hide_empty
 *
 * @return string|bool
 */
function appthemes_create_child_list( $subcats = array(), $taxonomy = 'category', $parent = 0, $curr_depth = 0, $max_depth = 3, $max_subcats = 3, $child_count = true, $hide_empty = false ) {
	$child_menu = '';
	$curr_subcats = 0;

	// limit depth of subcategories
	if ( $curr_depth >= $max_depth ) {
		return false;
	}
	$curr_depth++;

	foreach ( $subcats as $subcat ) {
		if ( $subcat->parent == $parent ) {
			// hide empty sub cats if option is set
			if ( $hide_empty && $subcat->count == 0 ) {
				continue;
			}
			// limit quantity of subcategories
			if ( $curr_subcats >= $max_subcats ) {
				continue;
			}
			$curr_subcats++;

			// only show the total count if option is set
			$show_count = $child_count ? '<span class="cat-item-count">(' . $subcat->count . ')</span>' : '';

			$child_menu .= '<li class="cat-item cat-item-' . $subcat->term_id . '"><a href="' . get_term_link( $subcat, $taxonomy ) . '" title="' . esc_attr( $subcat->description ) . '">' . $subcat->name . '</a> ' . $show_count . ' ';
			$temp_menu = appthemes_create_child_list( $subcats, $taxonomy, $subcat->term_id, $curr_depth, $max_depth, $max_subcats, $child_count, $hide_empty );
			if ( $temp_menu ) {
				$child_menu .= $temp_menu;
			}
			$child_menu .= '</li>';

		}
	}

	if ( ! empty( $child_menu ) ) {
		return '<ul class="subcat-list">' . $child_menu . '</ul>';
	} else {
		return false;
	}
}

/**
 * Insert a term if it doesn't already exist
 *
 * @param string $name The term name
 * @param string $tax The taxonomy
 *
 * @return int/WP_Error The term id
 */
function appthemes_maybe_insert_term( $name, $tax ) {
	$term_id = term_exists( $name, $tax );
	if ( ! $term_id ) {
		$term_id = wp_insert_term( $name, $tax );
	}

	return $term_id;
}

/**
 * Returns term data specified in arguments
 *
 * @param int $post_id Post ID
 * @param string $taxonomy The taxonomy
 * @param string $tax_arg The term data to retrieve
 *
 * @return string|bool The term data specified by $tax_arg or bool false if post has no terms
 */
function appthemes_get_custom_taxonomy( $post_id, $taxonomy, $tax_arg ) {
	$tax_array = get_terms( $taxonomy, array( 'hide_empty' => '0' ) );

	if ( empty( $tax_array ) ) {
		return false;
	}

	if ( ! is_object_in_term( $post_id, $taxonomy ) ) {
		return false;
	}

	foreach ( $tax_array as $tax_val ) {
		if ( ! is_object_in_term( $post_id, $taxonomy, array( $tax_val->term_id ) ) ) {
			continue;
		}

		switch ( $tax_arg ) {
			case 'slug':
				$link = get_term_link( $tax_val, $taxonomy );
				return $link;
				break;
			case 'slug_name':
				return $tax_val->slug;
				break;
			case 'name':
				return $tax_val->name;
				break;
			case 'term_id':
				return $tax_val->term_id;
				break;
			default:
				return false;
				break;
		}
	}

}

/**
 * Prints random terms for specified taxonomy
 *
 * @param string $taxonomy The taxonomy
 * @param int $limit The limit of results
 */
function appthemes_get_rand_taxonomy( $taxonomy, $limit ) {
	global $wpdb;

	$sql = "SELECT t.name, t.slug FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND tt.count > 0 ORDER BY RAND() LIMIT %d";
	$tax_array = $wpdb->get_results( $wpdb->prepare( $sql, $taxonomy, $limit ) );

	if ( empty( $tax_array ) ) {
		return;
	}

	foreach ( $tax_array as $tax_val ) {
		$link = get_term_link( $tax_val->slug, $taxonomy );
		echo '<a class="tax-link" href="' . $link . '">' . $tax_val->name . '</a>';
	}

}

/**
 * Prints most popular terms for specified taxonomy
 *
 * @param string $taxonomy The taxonomy
 * @param int $limit The limit of results
 */
function appthemes_get_pop_taxonomy( $taxonomy, $limit ) {
	global $wpdb;

	$sql = "SELECT t.name, t.slug, tt.count FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND tt.count > 0 GROUP BY tt.count DESC ORDER BY RAND() LIMIT %d";
	$tax_array = $wpdb->get_results( $wpdb->prepare( $sql, $taxonomy, $limit ) );

	if ( empty( $tax_array ) ) {
		return;
	}

	foreach ( $tax_array as $tax_val ) {
		$link = get_term_link( $tax_val->slug, $taxonomy );
		echo '<a class="tax-link" href="' . $link . '">' . $tax_val->name . '</a>';
	}

}

/**
 * Returns terms list for specified taxonomy
 *
 * @param int $id The term ID
 * @param string $taxonomy The taxonomy
 * @param string $before HTML code to be added before list
 * @param string $sep Separator between terms
 * @param string $after HTML code to be added after list
 * @return string|bool|WP_Error Formatted tems list, false if no terms, WP_Error if taxonomy does not exist
 */
function appthemes_get_all_taxonomy( $id = 0, $taxonomy, $before = '', $sep = '', $after = '' ) {
	$terms = get_the_terms( $id, $taxonomy );

	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	if ( empty( $terms ) ) {
		return false;
	}

	foreach ( $terms as $term ) {
		$link = get_term_link( $term, $taxonomy );
		if ( is_wp_error( $link ) ) {
			return $link;
		}
		$term_links[] = $term->name;
	}

	$term_links = apply_filters( "term_links-$taxonomy", $term_links );

	return $before . join( $sep, $term_links ) . $after;
}

/**
 * Retrieve edit post URL.
 * Does the same as get_edit_post_link() but not checking capabilities.
 *
 * @param int $post_id (optional) Post ID.
 * @param string $context (optional) Defaults to display.
 *
 * @return string|bool The edit post url for the given post. A boolean false on failure.
 */
function appthemes_get_edit_post_url( $post_id = 0, $context = 'display' ) {
	if ( ! $post = get_post( $post_id ) ) {
		return false;
	}

	if ( ! $post_type_object = get_post_type_object( $post->post_type ) ) {
		return false;
	}

	$args = array();

	if ( 'revision' !== $post->post_type ) {
		$args['action'] = 'edit';
	}

	$edit_link = sprintf( $post_type_object->_edit_link, $post->ID );

	$url = add_query_arg( $args, admin_url( $edit_link ) );
	$url = esc_url( $url, null, $context );

	return apply_filters( 'appthemes_get_edit_post_url', $url, $post->ID, $context );
}

/**
 * Retrieve edit term URL.
 * Does the same as get_edit_term_link() but not checking capabilities.
 *
 * @param int $term_id Term ID.
 * @param string $taxonomy Taxonomy.
 * @param string $object_type (optional) The object type.
 *
 * @return string|bool The edit term URL for the given term. A boolean false on failure.
 */
function appthemes_get_edit_term_url( $term_id, $taxonomy, $object_type = '' ) {
	if ( ! $tax = get_taxonomy( $taxonomy ) ) {
		return false;
	}

	if ( ! $term = get_term( $term_id, $taxonomy ) ) {
		return false;
	}

	$args = array(
		'action' => 'edit',
		'taxonomy' => $taxonomy,
		'tag_ID' => $term->term_id,
	);

	if ( $object_type ) {
		$args['post_type'] = $object_type;
	}

	$url = add_query_arg( $args, admin_url( 'edit-tags.php' ) );
	$url = esc_url( $url );

	return apply_filters( 'appthemes_get_edit_term_url', $url, $term_id, $taxonomy, $object_type );
}

/**
 * Retrieve edit comment URL.
 * Does the same as get_edit_comment_link() but not checking capabilities.
 *
 * @param int $comment_id (optional) Comment ID.
 * @param string $context (optional) Defaults to display.
 *
 * @return string|bool The edit comment URL for the given comment. A boolean false on failure.
 */
function appthemes_get_edit_comment_url( $comment_id = 0, $context = 'display' ) {
	if ( ! $comment = get_comment( $comment_id ) ) {
		return false;
	}

	$args = array(
		'action' => 'editcomment',
		'c' => $comment->comment_ID,
	);

	$url = add_query_arg( $args, admin_url( 'comment.php' ) );
	$url = esc_url( $url, null, $context );

	return apply_filters( 'appthemes_get_edit_comment_url', $url, $comment->comment_ID, $context );
}

/**
 * Return url of login page
 *
 * @param string $context
 *
 * @return string
 */
function appthemes_get_login_url( $context = 'display', $redirect_to = '' ) {
	$args = array();

	if ( ! empty( $redirect_to ) ) {
		$args['redirect_to'] = urlencode( $redirect_to );
	}

	if ( current_theme_supports( 'app-login' ) && ( $page_id = APP_Login::get_id() ) ) {
		$url = get_permalink( $page_id );
	} else {
		$url = site_url( 'wp-login.php' );
	}

	$url = add_query_arg( $args, $url );

	return esc_url( $url, null, $context );
}

/**
 * Return url of registration page
 *
 * @param string $context
 *
 * @return string
 */
function appthemes_get_registration_url( $context = 'display' ) {
	if ( current_theme_supports( 'app-login' ) && ( $page_id = APP_Registration::get_id() ) ) {
		$url = get_permalink( $page_id );
	} else {
		$url = site_url( 'wp-login.php?action=register' );
	}

	if ( ! empty( $_REQUEST['redirect_to'] ) ) {
		$url = add_query_arg( 'redirect_to', urlencode( $_REQUEST['redirect_to'] ), $url );
	}

	return esc_url( $url, null, $context );
}

/**
 * Return url of password recovery page
 *
 * @param string $context
 *
 * @return string
 */
function appthemes_get_password_recovery_url( $context = 'display' ) {
	if ( current_theme_supports( 'app-login' ) && ( $page_id = APP_Password_Recovery::get_id() ) ) {
		$url = get_permalink( $page_id );
	} else {
		$url = site_url( 'wp-login.php?action=lostpassword' );
	}

	if ( ! empty( $_GET['action'] ) && empty( $_GET['key'] ) ) {
		$url = add_query_arg( 'action', $_GET['action'], $url );
	}

	return esc_url( $url, null, $context );
}

/**
 * Return url of password reset page
 *
 * @param string $context
 *
 * @return string
 */
function appthemes_get_password_reset_url( $context = 'display' ) {
	$args = array();

	if ( ! empty( $_GET['action'] ) && 'rp' == $_GET['action'] && ! empty( $_GET['key'] ) && ! empty( $_GET['login'] ) ) {
		$args = array( 'action' => $_GET['action'], 'key' => $_GET['key'], 'login' => rawurlencode( $_GET['login'] ) );
	}

	if ( current_theme_supports( 'app-login' ) && ( $page_id = APP_Password_Reset::get_id() ) ) {
		$url = get_permalink( $page_id );
		$url = add_query_arg( $args, $url );
	} else {
		$url = add_query_arg( $args, site_url( 'wp-login.php' ) );
	}

	return esc_url( $url, null, $context );
}

function appthemes_framework_image( $name ) {
	return APP_FRAMEWORK_URI . '/images/' . $name;
}

/**
 * Return feed url related to currently browsed page
 *
 * @return string
 */
function appthemes_get_feed_url() {

	if ( ! current_theme_supports( 'app-feed' ) ) {
		return get_bloginfo_rss( 'rss2_url' );
	}

	list( $options ) = get_theme_support( 'app-feed' );

	if ( _appthemes_is_post_page( $options['blog_template'] ) ) {
		return add_query_arg( 'post_type', 'post', get_bloginfo_rss( 'rss2_url' ) );
	}

	if ( empty($options['alternate_feed_url']) ) {
		return add_query_arg( 'post_type', $options['post_type'], get_bloginfo_rss( 'rss2_url' ) );
	}

	return $options['alternate_feed_url'];
}

function _appthemes_is_post_page( $blog_template ) {
	if ( is_singular( 'post' ) || is_category() || is_tag() ) {
		return true;
	}

	if ( is_page_template( $blog_template ) ) {
		return true;
	}

	if ( get_queried_object_id() == get_option( 'page_for_posts' ) && in_array( $blog_template, array( 'home.php', 'index.php' ) ) ) {
		return true;
	}

	return false;
}

function appthemes_absfloat( $maybefloat ) {
	return abs( floatval( $maybefloat ) );
}

/**
 * Preserve a REQUEST variable by generating a hidden input for it
 */
function appthemes_pass_request_var( $keys ) {
	foreach ( (array) $keys as $key ) {
		if ( isset( $_REQUEST[ $key ] ) ) {
			_appthemes_form_serialize( $_REQUEST[ $key ], array( $key ) );
		}
	}
}

function _appthemes_form_serialize( $data, $name ) {
	if ( ! is_array( $data ) ) {
		echo html( 'input', array(
			'type' => 'hidden',
			'name' => scbForms::get_name( $name ),
			'value' => $data
		) ) . "\n";
		return;
	}

	foreach ( $data as $key => $value ) {
		_appthemes_form_serialize( $value, array_merge( $name, array( $key ) ) );
	}

}

/**
 * Sends email with standardized headers
 *
 */
function appthemes_send_email( $address, $subject, $content ) {

	// Strip 'www.' from URL
	$domain = preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$headers = array(
		'from' => sprintf( 'From: %1$s <%2$s>', $blogname, "wordpress@$domain" ),
		'mime' => 'MIME-Version: 1.0',
		'type' => 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"',
		'reply_to' => "Reply-To: noreply@$domain",
	);

	ob_start();
	appthemes_load_template( array( 'email-template.php', APP_FRAMEWORK_DIR_NAME . '/templates/email-template.php' ), array( 'address' => $address, 'subject' => $subject, 'content' => $content ) );
	$body = ob_get_clean();

	wp_mail( $address, $subject, $body, $headers );

}

/**
 * Creates URL to Facebook profile by ID or username
 *
 * @param int|string A Facebook user id, a username or a full URL
 * @return string A full Facebook URL
 */
function appthemes_make_fb_profile_url( $id, $context = 'display' ) {

	$base_url = 'https://www.facebook.com/';

	if ( empty( $id ) ) {
		$url = $base_url;
	} elseif ( is_numeric( $id ) ) {
		$base_url = $base_url . 'profile.php';
		$url = add_query_arg( array( 'id' => $id ), $base_url );
	} elseif ( preg_match( '/^(http|https):\/\/(.*?)$/i', $id ) ) {
		$url = $id;
	} else {
		$url = $base_url . $id;
	}

	return esc_url( $url, null, $context );
}

/**
 * Checks whether string begins with given string
 *
 * @param string $string String to search in
 * @param string $search String to search for
 * @return bool
 */
function appthemes_str_starts_with( $string, $search ) {
	return ( strncmp( $string, $search, strlen( $search ) ) == 0 );
}

/**
 * Strips out everything except numbers
 *
 * @param string
 * @return string
 */
function appthemes_numbers_only( $string ) {
	$string = preg_replace( '/[^0-9]/', '', $string );
	return $string;
}

/**
 * Strips out everything except letters
 *
 * @param string
 * @return string
 */
function appthemes_letters_only( $string ) {
	$string = preg_replace( '/[^a-z]/i', '', $string );
	return $string;
}

/**
 * Strips out everything except numbers and letters
 *
 * @param string
 * @return string
 */
function appthemes_numbers_letters_only( $string ) {
	$string = preg_replace( '/[^a-z0-9]/i', '', $string );
	return $string;
}

/**
 * Cleanes string from slashes and whitespaces
 *
 * @param string
 * @return string
 */
function appthemes_clean( $string ) {
	$string = stripslashes( $string );
	$string = trim( $string );
	return $string;
}

/**
 * Removes any invalid characters from tags
 *
 * @param string
 * @return string
 */
function appthemes_clean_tags( $string ) {
	$string = preg_replace( '/\s*,\s*/', ',', rtrim( trim( $string ), ' ,' ) );
	return $string;
}

/**
 * Strips tags and limit characters to 5,000
 *
 * @param string
 * @return string
 */
function appthemes_filter( $string ) {
	$string = strip_tags( $string );
	$string = trim( $string );
	$char_limit = 5000;
	if ( strlen( $string ) > $char_limit ) {
		$string = substr( $string, 0, $char_limit );
	}

	return $string;
}

/**
 * Returns extension of passed file name
 *
 * @param string A file name
 * @return string Extension of file
 */
function appthemes_find_ext( $filename ) {
	$filename = strtolower( $filename );
	$exts = preg_split( "[/\\.]", $filename );
	$n = count( $exts ) - 1;
	$exts = $exts[ $n ];
	return $exts;
}

/**
 * Returns visitor IP address
 *
 * @return string
 */
function appthemes_get_ip() {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP']; // ip from share internet
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR']; // ip from proxy
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	} else {
		$ip = '';
	}
	return $ip;
}

/**
 * Returns total count of posts based on post type and status
 *
 * @param string $post_type Post type
 * @param string|array $post_status Post status
 * @return int Total count of posts
 */
function appthemes_count_posts( $post_type = 'post', $post_status = 'publish' ) {
	$count_total = 0;
	$count_posts = wp_count_posts( $post_type );
	foreach ( (array) $post_status as $status ) {
		$count_total += $count_posts->$status;
	}

	return (int) $count_total;
}

/**
 * Returns translated date in format specified by user in WP options.
 *
 * @param string|int $date_time Date in standarized format or unix timestamp
 * @param string $format Date parts to return, date with time, date, or just time
 * @param bool $gmt_offset Whether to apply GMT offset to passed date
 * @return string Localized date
 */
function appthemes_display_date( $date_time, $format = 'datetime', $gmt_offset = false ) {
	if ( is_string( $date_time ) ) {
		$date_time = strtotime( $date_time );
	}

	if ( $gmt_offset ) {
		$date_time = $date_time + ( get_option( 'gmt_offset' ) * 3600 );
	}

	if ( $format == 'date' ) {
		$date_format = get_option( 'date_format' );
	} elseif ( $format == 'time' ) {
		$date_format = get_option( 'time_format' );
	} else {
		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	return date_i18n( $date_format, $date_time );
}

/**
 * Prints date of post or time ago if less than 24h, use in loop
 *
 * @param string Date in standarized format
 * @return string Localized date or time ago text
 */
function appthemes_date_posted( $date ) {
	$time = get_post_time( 'G', true );
	$time_diff = time() - $time;

	if ( $time_diff > 0 && $time_diff < 24*60*60 ) {
		printf( __( '%s ago', APP_TD ), human_time_diff( $time ) );
	} else {
		echo mysql2date( get_option( 'date_format' ), $date );
	}

}

/**
 * Convert date to mysql date format, to add/remove days from date use second parameter.
 *
 * @param string $date Date in standarized format
 * @param int $days Days to add or remove
 * @return string Date in mysql format
 */
function appthemes_mysql_date( $date, $days = 0 ) {
	$seconds = 60 * 60 * 24 * $days;
	$unix_time = strtotime( $date ) + $seconds;
	$mysqldate = date( 'Y-m-d H:i:s', $unix_time );

	return $mysqldate;
}

/**
 * Convert seconds to quantity of days.
 *
 * @param int Quantity of seconds
 * @return float Quantity of days
 */
function appthemes_seconds_to_days( $seconds ) {
	$days = $seconds / 24 / 60 / 60;
	return $days;
}

/**
 * Count days between passed dates.
 *
 * @param string A date for compare
 * @param string A date for compare
 * @param int Precision of results
 * @return float|bool Quantity of days or false if passed incorrect dates
 */
function appthemes_days_between_dates( $date1, $date2 = '', $precision = 1 ) {
	if ( empty( $date2 ) ) {
		$date2 = current_time( 'mysql' );
	}

	if ( ! is_string( $date1 ) || ! is_string( $date2 ) ) {
		return false;
	}

	$date1 = strtotime( $date1 );
	$date2 = strtotime( $date2 );

	$days = round( appthemes_seconds_to_days( $date1 - $date2 ), $precision );
	return $days;
}

/**
 * Convert plaintext URI to HTML links.
 *
 * @param string $text Content to convert URIs
 * @return string Content with converted URIs
 */
function appthemes_make_clickable( $text ) {
	$text = make_clickable( $text );
	// open links in new window
	$text = preg_replace( '/(<a href=[\'|\"](http|https|ftp)[^<>]+)>/is', '\\1 target="_blank">', $text );
	return $text;
}

/**
 * Throws exception in testing environment instead of normally terminate
 * script.
 *
 * @param string     $message The exception message. Might be useful for
 *                            differentiate behaviour inside the catch block.
 * @param int|string $status  Exit status.
 * @throws Exception
 */
function appthemes_exit( $message = 'appthemes_exit', $status = 0 ) {
	if ( defined( 'APP_TESTS_LIB' ) ) {
		throw new Exception( $message );
	} else {
		exit( $status );
	}
}
