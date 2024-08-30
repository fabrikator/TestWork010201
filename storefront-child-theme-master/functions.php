<?php

// Include custom widget class
require_once( get_theme_file_path() . '/inc/cities_weather_widget.php' );

// Include scripts
function scripts() {

    wp_enqueue_script( 'jquery', get_stylesheet_directory_uri() . '/assets/js/jquery.min.js', array(), null, false );
    wp_enqueue_script( 'cities-search', get_stylesheet_directory_uri() . '/assets/js/cities_search.js', array(), null, true );

}

add_action( 'wp_enqueue_scripts', 'scripts' );

// Create a new custom post type cities
add_action( 'init', function() {

    // Register cities
    register_post_type( 'cities', [
        'label' => __('Cities', 'storefront'),
        'public' => true,
        'menu_position' => 20,
        'menu_icon'     => 'dashicons-admin-home',
        'supports' => ['title', 'editor', 'thumbnail', 'author', 'revisions', 'comments'],
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'cities'],
        'labels' => [
            'singular_name' => __('City', 'storefront'),
            'add_new'            => __( 'Add City', 'storefront' ),
            'add_new_item' => __('Add new City', 'storefront'),
            'new_item' => __('New City', 'storefront'),
            'view_item' => __('View City', 'storefront'),
            'not_found' => __('No Cities found', 'storefront'),
            'not_found_in_trash' => __('No Cities found in trash', 'storefront'),
            'all_items' => __('All Cities', 'storefront'),
            'insert_into_item' => __('Insert into cities', 'storefront')
        ],
    ]);

    // Register countries
    register_taxonomy( 'cities_countries', ['cities'], [
        'label' => __('Countries', 'storefront'),
        'hierarchical' => true,
        'rewrite' => ['slug' => 'cities-countries'],
        'show_admin_column' => true,
        'show_in_rest' => true,
        'labels' => [
            'singular_name' => __('Country', 'storefront'),
            'all_items' => __('All Countries', 'storefront'),
            'edit_item' => __('Edit Country', 'storefront'),
            'view_item' => __('View Country', 'storefront'),
            'update_item' => __('Update Country', 'storefront'),
            'add_new_item' => __('Add New Country', 'storefront'),
            'new_item_name' => __('New Country', 'storefront'),
            'search_items' => __('Search Country', 'storefront'),
            'not_found' => __('No Country found', 'storefront'),
        ]
    ]);

    register_taxonomy_for_object_type( 'cities_countries', 'cities' );

});

// Create a new custom meta box for temperature api
function cities_temperature_api_meta_box() {

    add_meta_box(
        'cities_temperature_api',
        'Cities temperature',
        'build_meta_boxes',
        array( 'post', 'cities' ),
        'normal',
        'high'
    );

}

add_action( 'add_meta_boxes', 'cities_temperature_api_meta_box' );

// Build html fields latitude and longitude
function build_meta_boxes( $post ) {

    wp_nonce_field( 'cities_temperature_api_meta_box_nonce', 'meta_box_nonce' );

    $html = '';
    $city_latitude = get_post_meta( $post->ID, '_city_latitude', true );
    $city_longitude = get_post_meta( $post->ID, '_city_longitude', true );

    $html .= '<p>';
        $html .= '<label class="post-attributes-label">City latitude</label><br/>';
        $html .= '<input type="text" name="city_latitude" value="'. $city_latitude .'">';
    $html .= '</p>';

    $html .= '<p>';
        $html .= '<label class="post-attributes-label">City longitude</label><br/>';
        $html .= '<input type="text" name="city_longitude" value="'. $city_longitude . '">';
    $html .= '</p>';

    echo $html;

}

// Save meta box data
function cities_temperature_api_save_postdata( $post_id ) {

    // Return if autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Verify taxonomies meta box nonce
    if ( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'cities_temperature_api_meta_box_nonce' ) ) return;

    // Check the user's permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Update field latitude
    if ( isset( $_REQUEST['city_latitude'] ) ) {
        update_post_meta($post_id, '_city_latitude', sanitize_text_field( $_POST['city_latitude'] ) );
    }

    // Update field longitude
    if ( isset( $_REQUEST['city_longitude'] ) ) {
        update_post_meta($post_id, '_city_longitude', sanitize_text_field( $_POST['city_longitude'] ) );
    }

}

add_action( 'save_post', 'cities_temperature_api_save_postdata' );

// Get all cities and countries from DB
function get_all_cities() {

    global $wpdb;

    // Create arrays City and Country
    $countries = array();
    $cities = array();

    // Get data from BD and group Cities with Countries if page publish
    $query = $wpdb->get_results("
        SELECT t.term_id AS id, t.name AS country_name, p.post_title AS city_name, p.ID AS post_id
        FROM $wpdb->terms t
        JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
        JOIN $wpdb->term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        JOIN $wpdb->posts p ON tr.object_id = p.ID
        WHERE p.post_type = 'cities' AND p.post_status = 'publish'
        ORDER BY t.name, p.post_title;
    ");

    if( $query ) {

        // Create and structure the general array with Countries and Cities
        foreach ( $query as $val ) {
            $countries[$val->id] = $val->country_name;
            $cities[] = array(
                'country_id' => $val->id,
                'name' => $val->city_name,
                'post_id' => $val->post_id
            );
        }

    } else {
        return __( 'No city found', 'storefront' );
    }

    return array( $countries, $cities );
}

// Get current weather from API OpenWeatherMap
function get_current_weather( $post_id ) {

    $api_key = 'e65e5d6d472b40b3b4fd02054704cc2e';
    $city_lat = get_post_meta( $post_id, '_city_latitude', true );
    $city_long = get_post_meta( $post_id, '_city_longitude', true );

    if( $city_lat && $city_long ) {

        $url = "https://api.openweathermap.org/data/3.0/onecall?lat=$city_lat&lon=$city_long&appid=$api_key&units=metric&exclude=hourly,daily,minutely";

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {

            return __( 'Something wrong', 'storefront' );

        } else {

            $weather = json_decode(wp_remote_retrieve_body( $response ));

            return $weather->current->temp.' &#176; ' . $weather->current->weather[0]->description;

        }

    } else {
        return __( 'No enter city latitude and longitude', 'storefront' );
    }

}

// Register and load the cities weather widget
function cities_weather_load_widget() {
    register_widget( 'cities_weather_widget' );
}

add_action( 'widgets_init', 'cities_weather_load_widget' );

// Ajax city search
function cities_search() {

    global $wpdb;

    $search_request = $_POST['request'];

    $query = $wpdb->get_results("
        SELECT p.post_title AS city_name, p.ID AS post_id
        FROM $wpdb->posts p
        WHERE p.post_type = 'cities' AND p.post_status = 'publish' AND p.post_title = '$search_request'
        ORDER BY p.post_title;
    ");

    if( $query ) {

        $html ='<tr><td colspan="2"><h4><b>Search result:</b></h4></td></tr>';

        foreach ( $query as $val ) {

            $html .= '<tr>';
                $html .= '<td>'.$val->city_name.'</td>';
                $html .= '<td>'.get_current_weather( $val->post_id ).'</td>';
            $html .= '</tr>';

        }

        echo $html;

    } else {
        echo __( 'No city found', 'storefront' );
    }

    wp_reset_postdata();
    die();

}

add_action( 'wp_ajax_search', 'cities_search' );
add_action( 'wp_ajax_nopriv_search', 'cities_search' );
