<?php
/*
    Plugin Name: RelatedPopularPostsKB Plugin
    Plugin URI: 
    Description: Plugin Related y Popular Posts.
    Version: 1.0
    Author: Daniel Gomez, Rony Santos.
    Author URI: 
    License: GPL2
    Text Domain: pluginRelatedPopularPostsKB
*/
// 
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

//register_activation_hook
function pluginrelatedpopularpostskb_install() {

    global $wpdb;
    global $jal_db_version;
  
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    add_option( 'jal_db_version', $jal_db_version );   


}
register_activation_hook( __FILE__, 'pluginrelatedpopularpostskb_install' );
  
// Menu
add_action('admin_menu', 'pluginrelatedpopularpostskb_menu');
function pluginrelatedpopularpostskb_menu()
{
    add_menu_page('pluginRelatedPopularPostsKB', 'RPPKB', 'manage_options', 'pluginrelatedpopularpostskb_script', 'pluginrelatedpopularpostskb_script', 'dashicons-menu-alt');
    // add_submenu_page( 'pluginrelatedpopularpostskb_script', 'Options', 'Options', 'manage_options', 'saving_first_data', 'saving_first_data');
    // add_submenu_page( 'pluginrelatedpopularpostskb_script', 'Button Text', 'Button Text', 'manage_options', 'button_text', 'button_text');
}


function pluginrelatedpopularpostskb_script(){

    // declaración de variables de contenido
    $var_html_content = '';
    $var_css_styles = '';

    // Lista de categorías 
    $categories = get_categories( array(
        'orderby' => 'name',
        'order'   => 'ASC'
    ) );


    $counter_loop = 0; // contador de posts totales
    $counter_total_views_all_posts = 0; // contador de vistas de todos los posts

    // Encabezado de salida HTML para tabla de conteo general
    $html_output =  '
                        <table>
                            <tr>
                                <th class="title">Post ID</th>
                                <th class="title">Post title</th>
                                <th class="title">Post Status</th>
                                <th class="title">Views</th>
                            </tr>
                    ';


    $html_output_by_cat = ''; // outputs por categoría 
    // loop de categorías 
    foreach( $categories as $category ) {
         
        // echo '<p> ID: '. $category->term_id .'</p>';
        // echo '<p> Categoría:'. $category->name .'</p>';
        // echo '<p> Desc.: '. $category->description .'</p>';
        // echo '<p> N° posts: '. $category->count .'</p>';

        $html_output_by_cat .=  '
                                    <div class="category_element">
                                        <hr />
                                        <h2 class="title_cat">'. $category->name .'</h2>
                                        <hr />
                                        <h5>N° posts en publicados en esta categoría: '. $category->count .'</h5>
                                        <hr />
                                        <table>
                                            <tr>
                                                <th class="title">Post ID</th>
                                                <th class="title">Post title</th>
                                                <th class="title">Post Status</th>
                                                <th class="title">Views</th>
                                                <th class="title">Day</th>
                                                <th class="title">Week</th>
                                                <th class="title">Month</th>
                                                <th class="title">Year</th>
                                            </tr>
                                ';

        // variables de objetos prepradas para WP_QUERY
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => 'post',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => $category->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => array( $category->term_id ),
                    'operator' => 'IN',
                ),
            ),
        );
        $query = new WP_Query( $args ); // query de posts por categoría 

        if( $query->have_posts()):
            // loop de posts por categoría 
            while ( $query->have_posts() ) {
                
                $query->the_post(); 
                $postID = get_the_ID();

                $counter_total_views_all_posts = $counter_total_views_all_posts + (int)getPostViewsAll($postID); // incrementador de conteo de vistas totales a los posts

                $html_output .= '
                                    <tr>
                                        <td class="center_text">'. $postID .'</td>
                                        <td class="center_text"><a href="'. get_permalink( $postID ) .'" target="_blank" rel="noopener noreferrer">'. get_the_title($postID) .'</a></td>
                                        <td class="center_text">'. get_post_status ( $postID ) .'</td>
                                        <td class="center_text">'. (int)getPostViewsAll($postID) .' Views</td>
                                    </tr>
                                ';

                $html_output_by_cat .=  '
                                            <tr>
                                                <td class="center_text">'. $postID .'</td>
                                                <td class="center_text"><a href="'. get_permalink( $postID ) .'" target="_blank" rel="noopener noreferrer">'. get_the_title($postID) .'</a></td>
                                                <td class="center_text">'. get_post_status ( $postID ) .'</td>
                                                <td class="center_text">'. (int)getPostViewsAll($postID) .' Views</td>
                                                <td class="center_text">'. (int)getPostViewsDay($postID) .' Views</td>
                                                <td class="center_text">'. (int)getPostViewsWeek($postID) .' Views</td>
                                                <td class="center_text">'. (int)getPostViewsMonth($postID) .' Views</td>
                                                <td class="center_text">'. (int)getPostViewsYear($postID) .' Views</td>
                                            </tr>
                                        ';

                $counter_loop++; // incrementador del número de posts

            }
        else:
            echo 'ND'; // Salida si no hay posts
        endif;

        wp_reset_postdata(); // reset de Query

        $html_output_by_cat .= '</table></div>';

    } 


    
    // Pie de salida HTML tabla de vistas general
    $html_output .= ' </table> ';


    // Vista de Main Panel del plugin
    $var_html_content = '
                    <div class="root_main_dash">
                        <h1 class="title">Vistas por posts</h1>
                        <hr />
                        
                        <div class="container_grid">
                            <div class="card_alusive">
                                <div class="content_div" style="">
                                    <h3>Total Posts: '. $counter_loop .'</h3>
                                </div>
                                <div class="content_div" style="">
                                    <h3>Total views: '. $counter_total_views_all_posts .'</h3>
                                </div>
                            </div>
                            <div class="table_container">
                                '. $html_output .'
                            </div>
                        </div>

                        <br /><br />

                        <div class="container_grid_categories">
                            <h3>Vistas y posts por categorías</h3>
                            '. $html_output_by_cat .'
                        </div>
                    </div>
                ';

    // CSS Estilos de la vista
    $var_css_styles =  '
                    <style>

                        .root_main_dash{
                            width: 100%;
                            height: 1000px;
                            overflow-y: scroll;
                            padding-top: 40px;
                        }

                        .root_main_dash h1.title{
                            text-align: center;
                            font-size: 35px;
                            font-weight: 800;
                            color: #34495E;
                        }

                        .root_main_dash .container_grid{
                            width: 100%;
                            height: 300px;
                            padding: 25px 0;
                            display: flex;
                            flex-direction: row;
                            justify-content: center;
                            align-items: center;
                            align-content: center;
                        }

                        .root_main_dash .card_alusive{
                            width: 30%;
                            height: 100%;
                            margin: 0 30px 0 10px;
                            border-radius: 5px;
                            box-shadow: 3px 2px 9px rgba(0, 0, 0, 0.2); 
                        }

                        .root_main_dash .table_container{
                            width: calc(70% - 60px);
                            height: 100%;
                            margin: 0 10px 0 10px; 
                            overflow-y: scroll;
                        }

                        .root_main_dash .card_alusive .content_div{
                            width: 100%; 
                            height: 40px; 
                            padding: 2px 10px;
                        }

                        .root_main_dash .table_container table{
                            width: 100%;
                            height: 100%;
                        }

                        .root_main_dash .table_container table tr:nth-child(even) {
                            background-color: #dddddd;
                        }

                        .root_main_dash .table_container table th.title{
                            color: #34495E;
                            font-weight: 800;
                            border-left: solid 0.6px #ddd;
                        }

                        .root_main_dash .table_container table td.center_text{
                            text-align: center;
                            align-self: center;
                        }

                        .root_main_dash .container_grid_categories{
                            width: 100%;
                            height: 500px;
                            padding: 25px 0;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            overflow-y: scroll;
                            border-top: solid 0.6px #ddd;
                        }

                        .root_main_dash .container_grid_categories > h3{
                            text-align: center;
                            font-size: 28px;
                            font-weight: 800;
                            color: #34495E;
                        }

                        .root_main_dash .container_grid_categories .category_element{
                            width: 100%;
                            padding: 15px 0px;
                            border-bottom: solid 0.6px #ddd;
                        }

                        .root_main_dash .container_grid_categories .category_element h2.title_cat{
                            text-align: center;
                            font-size: 24px;
                            font-weight: 800;
                            color: #34495E;
                        }

                        .root_main_dash .container_grid_categories .category_element h5{
                            text-align: center;
                            font-size: 18px;
                            font-weight: 800;
                            color: #34495E;
                        }

                        .root_main_dash .container_grid_categories .category_element table{
                            width: 100%;
                            height: 100px;
                            overflow-y: scroll;
                        }

                        .root_main_dash .container_grid_categories .category_element table tr:nth-child(even) {
                            background-color: #dddddd;
                        }

                        .root_main_dash .container_grid_categories .category_element table th.title{
                            color: #34495E;
                            font-weight: 800;
                            border-left: solid 0.6px #ddd;
                        }

                        .root_main_dash .container_grid_categories .category_element table td.center_text{
                            text-align: center;
                            align-self: center;
                        }

                        .root_main_dash::-webkit-scrollbar{
                            width: 4px;
                        }
                        
                        .root_main_dash::-webkit-scrollbar-track {
                            background: #f1f1f1; 
                        }
                        
                        .root_main_dash::-webkit-scrollbar-thumb {
                            background: #888; 
                        }
                        
                        .root_main_dash .container_grid_categories::-webkit-scrollbar-thumb:hover {
                            background: #555; 
                        }

                        .root_main_dash .container_grid_categories::-webkit-scrollbar{
                            width: 4px;
                        }
                        
                        .root_main_dash .container_grid_categories::-webkit-scrollbar-track {
                            background: #f1f1f1; 
                        }
                        
                        .root_main_dash .container_grid_categories::-webkit-scrollbar-thumb {
                            background: #888; 
                        }
                        
                        .root_main_dash .container_grid_categories::-webkit-scrollbar-thumb:hover {
                            background: #555; 
                        }
                    </style>
                ';
    // $count = get_post_meta( 21495, 'post_views_count', true );
    // var_dump( $count );
    // echo "$count views";


    $today = new DateTime();
    // var_dump( $today );


    // variable de retorno a la salida
    $return_content = $var_html_content . $var_css_styles;

    // display de la vista
    echo $return_content;

}

// función de conteo de vistas totales
function getPostViewsAll($postID){

    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);

    if( $count == '' ){
        
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return 0;

    }

    return $count;

}

// función de conteo de vistas diarias
function getPostViewsDay($postID){

    $count_key = 'post_views_count_per_day';
    $count = get_post_meta($postID, $count_key, true);

    if( $count == '' ){
        
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return 0;

    }

    return $count;

}

// función de conteo de vistas semanales
function getPostViewsWeek($postID){

    $count_key = 'post_views_count_per_week';
    $count = get_post_meta($postID, $count_key, true);

    if( $count == '' ){
        
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return 0;

    }

    return $count;

}

// función de conteo de vistas mensuales
function getPostViewsMonth($postID){

    $count_key = 'post_views_count_per_month';
    $count = get_post_meta($postID, $count_key, true);

    if( $count == '' ){
        
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return 0;

    }

    return $count;

}

// función de conteo de vistas anuales
function getPostViewsYear($postID){

    $count_key = 'post_views_count_per_year';
    $count = get_post_meta($postID, $count_key, true);

    if( $count == '' ){
        
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return 0;

    }

    return $count;

}

// función de incremento de vistas cada vez que se cargue un post
function setPostViews($postID){

    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count = $count + 1;
        update_post_meta($postID, $count_key, $count);
    }

    $count_key = 'post_views_count_per_day';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count = $count + 1;
        update_post_meta($postID, $count_key, $count);
    }

    $count_key = 'post_views_count_per_week';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count = $count + 1;
        update_post_meta($postID, $count_key, $count);
    }

    $count_key = 'post_views_count_per_month';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count = $count + 1;
        update_post_meta($postID, $count_key, $count);
    }

    $count_key = 'post_views_count_per_year';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count = $count + 1;
        update_post_meta($postID, $count_key, $count);
    }

}


function resetPostViews($postID){

}


//custom RPPKB related post.
function RPPKB_custom_related($atts){
    
    $post_objects = array();
    $full_URL_site = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'];
    $related_post = __("related", "paperback");
    $content = '
			<?php	$ratingthis = __("rating", "paperback"); ?>
		    <div id="jp-relatedposts" class="jp-relatedposts" style="display: block;">
                <h2 class="h2">' . $related_post . '</h2>';
                
    // categorías del post actual
    $categories = get_the_category();
    // argumentos de Query
    $args = array(
        'posts_per_page'    => 3,
        'meta_key'      => 'post_views_count',
        'orderby'       => 'meta_value_num',
        'order'         => 'DESC',
        'category__in' => array(
            $categories[0]->cat_ID
        ),
        'post_type' => 'post',
        'post_status' => 'publish',
        'post__not_in' => array( get_the_ID( ) ),
    );

    /** Get related posts */
    // Consulta a los posts
    $related = array();
    $queriable = new WP_Query( $args );
 
    if ( $queriable->have_posts() ) :
 
        while ( $queriable->have_posts() ) : 
            $queriable->the_post();
            array_push( $related, array( "id"=>get_the_ID(  ) ) );
        endwhile;
    else : 
        null;
    endif;
    wp_reset_postdata();

    if ($related) {
        foreach ($related as $result) {

            // Get the related post IDs
            $related_post = get_post($result['id']);
            $categories = get_the_category($result['id']);
            $content .= '<div class="related">
                            <div class="related__body">
            <a class="related__category"  href="' . $full_URL_site . '/category/' . $categories[0]->slug . '/">';
            if (!empty($categories)) {
                $content .= $categories[0]->cat_name;
            }
            $content .= '</a>
            <span class="related__date">' .   date_i18n("d. F Y", strtotime($related_post->post_date)) . '</span>
            <span class="related__title"><a  href="' . $full_URL_site . "/" . $related_post->post_name . '">
                                                ' . do_shortcode($related_post->post_title) . '
                                            </a></span></div><div class="related__img">';
            $content .='<a  href="' . $full_URL_site . "/" . $related_post->post_name . '">';
            if (get_field('alternate_thumbnail', $result['id'])['sizes']['child_size']) {
                $content .= '<img src="'. get_field('alternate_thumbnail', $result['id'])['sizes']['child_size'] .'" alt="">';
            } elseif (has_post_thumbnail($related_post->ID, 'child_size')) {
                $content .= get_the_post_thumbnail($related_post->ID, 'child_size');
            } elseif (get_site_icon_url()) {
                $content .= '<div class="post_img_icon"><img src="' . get_site_icon_url() . '" alt=""></div>';
            } else {
                $content .= '<img  src="' . get_stylesheet_directory_uri() . '/images/default_post_image.png" class="attachment-child_size size-child_size wp-post-image" alt="">';
            }

            $content .= '</a>';
            $content .= '</div></div>';
        }
    }
    
    $content .= '</div>';
    return $content;
}

// add_shortcode('jprel', 'jetpackme_custom_related');
add_shortcode('RPPKBrel', 'RPPKB_custom_related');


?>