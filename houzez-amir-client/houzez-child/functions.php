<?php
require_once( get_stylesheet_directory() . '/framework/classes/houzez_data_source.php' );
require_once( get_stylesheet_directory() . '/framework/functions/property_functions.php' );

function start_output_buffer() {
    ob_start('change_title_html');
}
add_action('dynamic_sidebar_before', 'start_output_buffer');
add_action('wp_head', 'start_output_buffer');


function end_output_buffer() {
    ob_end_flush();
}
add_action('dynamic_sidebar_after', 'end_output_buffer');
add_action('wp_footer', 'end_output_buffer');


function change_title_html($html) {

    $html = preg_replace('/<h[1-6](.*?)class="widget-title"(.*?)>(.*?)<\/h[1-6]>/', '<div$1class="widget-title"$2>$3</div>', $html);

    $html = preg_replace('/<h[1-6](.*?)class="modal-title"(.*?)>(.*?)<\/h[1-6]>/', '<div$1class="modal-title"$2>$3</div>', $html);

    return $html;
}


function exclude_houzez_agent_from_sitemap( $value, $post_type ) {
    if ( $post_type == 'houzez_agent' ) return true;
    return $value;
}
add_filter( 'wpseo_sitemap_exclude_post_type', 'exclude_houzez_agent_from_sitemap', 10, 2 );



add_filter( 'weglot_get_dom_checkers', 'custom_weglot_dom_check' );
function custom_weglot_dom_check( $dom_checkers  ) { //$dom_checkers contains the list of all the class we are checking by default

	class Div_Slide_Title extends Weglot\Parser\Check\Dom\AbstractDomChecker {
		const DOM       = 'select'; //Type of tag you want to detect // CSS Selector
		const PROPERTY  = 'title'; //Name of the attribute in that tag uou want to detect
		const WORD_TYPE = Weglot\Client\Api\Enum\WordType::TEXT; //Do not change unless it's not text but a media URL like a .pdf file for example.
	}
	$dom_checkers[] = '\Div_Slide_Title'; //You add your class to the list because you want the parser to also detect it
	return $dom_checkers ;
}


function create_news_post_type() {
    $labels = array(
        'name'                  => _x( 'News', 'Post type general name', 'textdomain' ),
        'singular_name'         => _x( 'News', 'Post type singular name', 'textdomain' ),
        'menu_name'             => _x( 'News', 'Admin Menu text', 'textdomain' ),
        'name_admin_bar'        => _x( 'News', 'Add New on Toolbar', 'textdomain' ),
        'add_new'               => __( 'Add New', 'textdomain' ),
        'add_new_item'          => __( 'Add New News', 'textdomain' ),
        'new_item'              => __( 'New News', 'textdomain' ),
        'edit_item'             => __( 'Edit News', 'textdomain' ),
        'view_item'             => __( 'View News', 'textdomain' ),
        'all_items'             => __( 'All News', 'textdomain' ),
        'search_items'          => __( 'Search News', 'textdomain' ),
        'parent_item_colon'     => __( 'Parent News:', 'textdomain' ),
        'not_found'             => __( 'No news found.', 'textdomain' ),
        'not_found_in_trash'    => __( 'No news found in Trash.', 'textdomain' ),
        'featured_image'        => _x( 'News Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'archives'              => _x( 'News archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'textdomain' ),
        'insert_into_item'      => _x( 'Insert into news', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'textdomain' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this news', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'textdomain' ),
        'filter_items_list'     => _x( 'Filter news list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'textdomain' ),
        'items_list_navigation' => _x( 'News list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'textdomain' ),
        'items_list'            => _x( 'News list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'textdomain' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'news' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
    );

    register_post_type( 'news', $args );
}

add_action( 'init', 'create_news_post_type' );


// load more js file

function enqueue_child_theme_scripts() {
    // Enqueue the modified child theme's custom JS
    wp_enqueue_script('custom-child-js', get_stylesheet_directory_uri() . '/js/custom-child.js', array('jquery'), rand(), true);
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_scripts', 20);

// Add the child theme's modified load more properties function
add_action('wp_ajax_nopriv_houzez_loadmore_properties', 'houzez_loadmore_properties');
add_action('wp_ajax_houzez_loadmore_properties', 'houzez_loadmore_properties');

if (!function_exists('houzez_loadmore_properties')) {
    function houzez_loadmore_properties() {
        global $houzez_local;

        $houzez_local = houzez_get_localization();
        $fake_loop_offset = 0; 

        $tax_query = array();
        $card_version = sanitize_text_field($_POST['card_version']);
        $property_type = houzez_traverse_comma_string($_POST['type']);
        $property_status = houzez_traverse_comma_string($_POST['status']);
        $property_building_name = houzez_traverse_comma_string($_POST['building_name']);
        $property_state = houzez_traverse_comma_string($_POST['state']);
        $property_city = houzez_traverse_comma_string($_POST['city']);
        $property_country = houzez_traverse_comma_string($_POST['country']);
        $property_area = houzez_traverse_comma_string($_POST['area']);
        $property_label = houzez_traverse_comma_string($_POST['label']);
        $houzez_user_role = sanitize_text_field($_POST['user_role']);
        $featured_prop = sanitize_text_field($_POST['featured_prop']);
        $posts_limit = intval($_POST['prop_limit']);
        $sort_by = sanitize_text_field($_POST['sort_by']);
        $offset = intval($_POST['offset']);
        $paged = intval($_POST['paged']);

        $wp_query_args = array(
            'ignore_sticky_posts' => 1
        );

        if (!empty($houzez_user_role)) {
            $role_ids = houzez_author_ids_by_role($houzez_user_role);
            if (!empty($role_ids)) {
                $wp_query_args['author__in'] = $role_ids;
            }
        }

        if (!empty($property_type)) {
            $tax_query[] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $property_type
            );
        }

        if (!empty($property_status)) {
            $tax_query[] = array(
                'taxonomy' => 'property_status',
                'field' => 'slug',
                'terms' => $property_status
            );
        }

        if (!empty($property_building_name)) {
            $tax_query[] = array(
                'taxonomy' => 'property_building_name',
                'field' => 'slug',
                'terms' => $property_building_name
            );
        }
        if (!empty($property_country)) {
            $tax_query[] = array(
                'taxonomy' => 'property_country',
                'field' => 'slug',
                'terms' => $property_country
            );
        }
        if (!empty($property_state)) {
            $tax_query[] = array(
                'taxonomy' => 'property_state',
                'field' => 'slug',
                'terms' => $property_state
            );
        }
        if (!empty($property_city)) {
            $tax_query[] = array(
                'taxonomy' => 'property_city',
                'field' => 'slug',
                'terms' => $property_city
            );
        }
        if (!empty($property_area)) {
            $tax_query[] = array(
                'taxonomy' => 'property_area',
                'field' => 'slug',
                'terms' => $property_area
            );
        }

        if (!empty($property_label)) {
            $tax_query[] = array(
                'taxonomy' => 'property_label',
                'field' => 'slug',
                'terms' => $property_label
            );
        }

        // Custom modifications to the sorting logic can go here
        if ($sort_by == 'a_title') {
            $wp_query_args['orderby'] = 'title';
            $wp_query_args['order'] = 'ASC';
        } else if ($sort_by == 'd_title') {
            $wp_query_args['orderby'] = 'title';
            $wp_query_args['order'] = 'DESC';
        } else if ($sort_by == 'a_price') {
            $wp_query_args['orderby'] = 'meta_value_num';
            $wp_query_args['meta_key'] = 'fave_property_price';
            $wp_query_args['order'] = 'ASC';
        } else if ($sort_by == 'd_price') {
            $wp_query_args['orderby'] = 'meta_value_num';
            $wp_query_args['meta_key'] = 'fave_property_price';
            $wp_query_args['order'] = 'DESC';
        } else if ($sort_by == 'a_date') {
            $wp_query_args['orderby'] = 'date';
            $wp_query_args['order'] = 'ASC';
        } else if ($sort_by == 'd_date') {
            $wp_query_args['orderby'] = 'date';
            $wp_query_args['order'] = 'DESC';
        } else if ($sort_by == 'featured_top') {
            $wp_query_args['orderby'] = 'meta_value';
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['order'] = 'DESC';
        } else if ($sort_by == 'featured_first') {
            $wp_query_args['orderby'] = 'meta_value';
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['order'] = 'DESC';
        } else if ($sort_by == 'featured_first_random') {
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['orderby'] = 'meta_value DESC rand';
        } else if ($sort_by == 'featured_random') {
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['meta_value'] = '1';
            $wp_query_args['orderby'] = 'meta_value DESC rand';
        } else if ($sort_by == 'random') {
            $wp_query_args['orderby'] = 'rand';
            $wp_query_args['order'] = 'DESC';
        }

        if (!empty($featured_prop)) {
            if ($featured_prop == "yes") {
                $wp_query_args['meta_key'] = 'fave_featured';
                $wp_query_args['meta_value'] = '1';
            } else {
                $wp_query_args['meta_key'] = 'fave_featured';
                $wp_query_args['meta_value'] = '0';
            }
        }

        $tax_count = count($tax_query);

        if ($tax_count > 1) {
            $tax_query['relation'] = 'AND';
        }
        if ($tax_count > 0) {
            $wp_query_args['tax_query'] = $tax_query;
        }

        $wp_query_args['post_status'] = 'publish';

        if (empty($posts_limit)) {
            $posts_limit = get_option('posts_per_page');
        }
        $wp_query_args['posts_per_page'] = $posts_limit;

        if (!empty($paged)) {
            $wp_query_args['paged'] = $paged;
        } else {
            $wp_query_args['paged'] = 1;
        }

        if (!empty($offset) and $paged > 1) {
            $wp_query_args['offset'] = $offset + (($paged - 1) * $posts_limit);
        } else {
            $wp_query_args['offset'] = $offset;
        }

        $fake_loop_offset = $offset;

        $wp_query_args['post_type'] = 'property';

        $the_query = new WP_Query($wp_query_args);
        //print_r($the_query);
        if ($the_query->have_posts()) :
            while ($the_query->have_posts()) : $the_query->the_post();

                get_template_part('template-parts/listing/' . $card_version);

            endwhile;
            wp_reset_postdata();
        else:
            echo 'no_result';
        endif;

        wp_die();
    }
}



// search multiple bedroom function 

if (!function_exists('houzez_search_bedrooms')) {
    function houzez_search_bedrooms($meta_query) {
        $beds_baths_search = houzez_option('beds_baths_search', 'equal');
        $search_criteria = 'LIKE'; // Use LIKE for comma-separated values
        $type = 'CHAR';

        if (isset($_GET['bedrooms']) && $_GET['bedrooms'] != "" && $_GET['bedrooms'] != 'any') {
            $bedrooms = sanitize_text_field($_GET['bedrooms']); // Sanitize input

            // Explode the input into an array of bedroom values
            $bedroom_values = explode(',', $bedrooms);
            foreach ($bedroom_values as $value) {
                $meta_query[] = array(
                    'key' => 'fave_property_bedrooms',
                    'value' => trim($value), // Trim any whitespace
                    'compare' => 'LIKE', // Use LIKE to match the value within the string
                );
            }
        }

        return $meta_query;
    }

    add_filter('houzez_meta_search_filter', 'houzez_search_bedrooms');
}

// multiple bathrooms search function

if (!function_exists('houzez_search_bathrooms')) {
    function houzez_search_bathrooms($meta_query) {
        $beds_baths_search = houzez_option('beds_baths_search', 'equal');
        $search_criteria = 'LIKE'; // Use LIKE for comma-separated values
        $type = 'CHAR';

        if (isset($_GET['bathrooms']) && $_GET['bathrooms'] != "" && $_GET['bathrooms'] != 'any') {
            $bathrooms = sanitize_text_field($_GET['bathrooms']); // Sanitize input

            // Explode the input into an array of bathroom values
            $bathroom_values = explode(',', $bathrooms);
            foreach ($bathroom_values as $value) {
                $meta_query[] = array(
                    'key' => 'fave_property_bathrooms',
                    'value' => trim($value), // Trim any whitespace
                    'compare' => 'LIKE', // Use LIKE to match the value within the string
                );
            }
        }

        return $meta_query;
    }

    add_filter('houzez_meta_search_filter', 'houzez_search_bathrooms');
}


// multiple garage search function


if (!function_exists('houzez_search_garage')) {
    function houzez_search_garage($meta_query) {
        // Check if the 'garage' parameter is set in the URL and is not empty
        if (isset($_GET['garage']) && !empty($_GET['garage']) && $_GET['garage'] != 'any') {
            $garage = $_GET['garage'];

            // Modify the meta_query to search for garages in the comma-separated value
            $meta_query[] = array(
                'key' => 'fave_property_garage',
                'value' => $garage,
                'compare' => 'LIKE', // Use LIKE to search for the value within the comma-separated list
            );
        }

        return $meta_query;
    }

    add_filter('houzez_meta_search_filter', 'houzez_search_garage');
}

//wp_enqueue_script( 'houzez-custom', get_template_directory_uri() . '/js/custom.js', array('jquery'), time(), true );
//
//// new code from dev
function create_wpent_property_building_name_taxonomy() {
    // Labels for the taxonomy
    $labels = array(
        'name'              => _x( 'Property Building Names', 'taxonomy general name' ),
        'singular_name'     => _x( 'Building Name', 'taxonomy singular name' ),
        'search_items'      => __( 'Search Building Names' ),
        'all_items'         => __( 'All Building Names' ),
        'parent_item'       => __( 'Parent Building Name' ),
        'parent_item_colon' => __( 'Parent Building Name:' ),
        'edit_item'         => __( 'Edit Building Name' ),
        'update_item'       => __( 'Update Building Name' ),
        'add_new_item'      => __( 'Add New Building Name' ),
        'new_item_name'     => __( 'New Building Name' ),
        'menu_name'         => __( 'Building Name' ),
    );

    // Register the taxonomy
    $post_type = apply_filters('houzez_property_status_post_type_filter', array('property'));
    register_taxonomy( 'property_building_name', $post_type, array(
		'labels' => $labels,
        'hierarchical'  => true,
                'query_var'     => true,
                'show_in_rest'          => true,
                'rest_base'             => 'property_building_name',
                'rest_controller_class' => 'WP_REST_Terms_Controller',
                'rewrite'       => array( 'slug' => 'property_building_name', 'with_front' => false )
            
    ));
}
add_action( 'init', 'create_wpent_property_building_name_taxonomy', 0 );
