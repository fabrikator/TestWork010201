<?php
/**
 * Template Name: All Cities
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

// Get list of all countries and cities
$data = get_all_cities();

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php do_action( 'storefront_page_before' ); ?>

        <!-- Search city form -->
        <div class="city-search-form">
            <h4>Search city</h4>
            <form name="search-form">
                <input type="search" name="city_search" id="city_search">
            </form>

            <div id="search_result">
                <table>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- ./ -->

        <!-- Template display list of countries and cities -->
        <table>
            <tbody>

                <?php foreach ( $data[0] as $kay => $country ) : ?>

                    <tr>
                        <td colspan="2"><h4><b><?php echo $country ?></b></h4></td>
                    </tr>

                    <?php foreach ( $data[1] as $city ) : ?>

                        <?php if ( $city['country_id'] == $kay ) : ?>

                            <tr>
                                <td><?php echo $city['name'] ?></td>
                                <td><?php echo get_current_weather( $city['post_id'] ) ?></td>
                            </tr>

                        <?php endif; ?>

                    <?php endforeach; ?>

                <?php endforeach; ?>

            </tbody>
        </table>
        <!-- ./ -->

        <?php do_action( 'storefront_page_after' ); ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php

do_action( 'storefront_sidebar' );

get_footer();


