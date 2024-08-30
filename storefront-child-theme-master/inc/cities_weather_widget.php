<?php
// Creating the widget
class Cities_weather_widget extends WP_Widget {

    private $wpdb;

    function __construct() {

        global $wpdb;
        $this->wpdb = $wpdb;

        parent::__construct(
            // Base ID of your widget
            'cities_weather',

            // Widget name will appear in UI
            __( 'Cities weather', 'storefront' ),

            // Widget description
            [
                'description' => __( 'This widget shows the current weather in the selected city', 'storefront' ),
            ]
        );
    }

    // Creating widget front-end
    public function widget( $args, $instance ) {

        $title = apply_filters( 'widget_title', $instance['title'] );

        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Request to get all cities
        $query = $this->wpdb->get_results("
            SELECT p.post_title AS city_name, p.ID AS post_id
            FROM wp_posts p
            WHERE p.post_type = 'cities' AND p.post_status = 'publish'
            ORDER BY p.post_title;
        ");

        if( $query ) {

            // Display all of cities list
            $html ='<select class="">';
            $html .= '<option value="0" selected>Select city</option>';

            foreach ( $query as $val ) {
                $html .= '<option value="">';
                    $html .= $val->city_name . ' - ' . get_current_weather( $val->post_id );
                $html .= '</option>';
            }

            $html .='</select>';

            echo $html;

        } else {
            echo __('No city found', 'storefront');
        }

        echo $args['after_widget'];
    }

    // Widget Settings Form
    public function form( $instance ) {

        if ( isset( $instance['title'] ) ) {
            $title = $instance['title'];
        } else {
            $title = __( 'Select city', 'storefront' );
        }

        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">
                <?php _e( 'Title:', 'storefront' ); ?>
            </label>
            <input
                class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                name="<?php echo $this->get_field_name( 'title' ); ?>"
                type="text"
                value="<?php echo esc_attr( $title ); ?>"
            />
        </p>
        <?php

    }

    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {

        $instance          = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;

    }

}