(function( $ ) {
    'use strict';

    $(function() {

        $(document).ready(function () {

            $('.city-search-form form[name=search-form]').on('submit', function (e) {

                e.preventDefault();

            });

            $('#city_search').on('keyup', function () {

                var search = $(this).val();

                // If typing value 3 and more
                if ((search != '') && (search.length > 3)) {

                    $.ajax({
                        type: 'POST',
                        url: '/wp-admin/admin-ajax.php',
                        data: {
                            'action': 'search',
                            'request': search
                        },
                        success: function (data) {
                            $('.city-search-form #search_result tbody').html(data);
                        }
                    });
                }

                // Clear
                if (search.length == 0) {
                    $('.city-search-form #search_result tbody').html('');
                }

            });

        });

    });

})( jQuery );
