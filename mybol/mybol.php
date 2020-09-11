<?php
/*
    Plugin Name: MyBol Another bol.com Plugin
    Plugin URI: http://tromit.nl/diensten/wordpress-plugins/
    Description: A powerful plugin to easily integrate bol.com products in your blog posts or at your pages to earn money with the bol.com Partner Program.
    Version: 1.0
    Author: Daniel Gomez, Rony Santos.
    Author URI: http://example.com
    License: GPL2
    Text Domain: mybol
*/
// 
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

//Create table in register_activation_hook
function mybolkb_install() {
    global $wpdb;
    global $jal_db_version;
  
    $tabla = $wpdb->prefix . 'mybol_kb';

        $charset_collate = $wpdb->get_charset_collate();
  
        $sql = "DROP TABLE IF EXISTS {$tabla};
                CREATE TABLE {$tabla} (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    entry_id(100) INT NOT NULL,
                    item_title VARCHAR(500) NOT NULL,
                    item_subtitle VARCHAR(500),
                    item_externalurl TEXT NOT NULL,
                    item_afflink TEXT NOT NULL,
                    item_xlthumb TEXT,
                    item_lthumb TEXT,
                    item_mthumb TEXT,
                    item_sthumb TEXT,
                    item_xsthumb TEXT,
                    item_price VARCHAR(10) NOT NULL,
                    item_listprice VARCHAR(10) NOT NULL,
                    item_availability TEXT NOT NULL,
                    item_availabilitycode INT NOT NULL,
                    item_rating INT NOT NULL,
                    item_ratingspan TEXT NOT NULL,
                    time INT NOT NULL,
                    PRIMARY KEY (`id`)
        ) {$charset_collate}; ";
  
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option( 'jal_db_version', $jal_db_version );   

        // Tarea Programada
        include "inc/execute_task.php";


}
register_activation_hook( __FILE__, 'mybolkb_install' );
  
// Global function 
require "inc/global_function.php";

// alll Function 
require 'function.php';

function mybol_script(){

        echo    '
                  
                        <h1>List Products</h1>
                        <hr />
                  
                '; 

        global $wpdb;
        $tabla = $wpdb->prefix . 'mybol_kb';

        $content .= '<table class="class-table-bol-products" id="customers"><tbody style="">';
        $content .= '<tr style="">
                        <th style="">ID</th>
                        <th style="">Product</th>
                        <th style="">Status</th>
                    </tr>';

        $items = $wpdb->get_results("SELECT * FROM `".$tabla."`");  

        foreach( $items as $item ){

            $content .= '<tr style="">';
            // Modify these to match the database structure
            $content .= '<td style="">' . $item->entry_id . '</td>';
            $content .= '<td style="">' . $item->item_title . '</td>';
            $content .= ( ($item->item_price) > 0.00 ) ? '<td style="">' . '<p style="color: #46b450;">Activo</p>' . '</td>' : '<td style="">' . '<p style="color: #c0392b;">Inactivo</p>' . '</td>';
            $content .= '</tr>';

        }

        $content .= '</tbody></table>';

        echo $content;

        echo '
        <style>
        #customers {
          font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
          border-collapse: collapse;
          width: 100%;
        }
        
        #customers td, #customers th {
          border: 1px solid #ddd;
          padding: 8px;
        }
        
        #customers tr:nth-child(even){background-color: #f2f2f2;}
        
        #customers tr:hover {background-color: #ddd;}
        
        #customers th {
          padding-top: 12px;
          padding-bottom: 12px;
          text-align: left;
          background-color: #23282D;
          color: white;
        }
        </style>
             ';

            //  echo "<br /><br />";
            //  $cron_jobs = get_option( 'cron' );
            //  var_dump($cron_jobs);

}

// creacion de vista de captura de datos
include "view/options_saving.php";
// var_dump( get_option( 'configurating_options' )['activate_mybol'] );
// Shortcode 
if ( get_option( 'configurating_options' )['activate_mybol'] == 'on' ) {
    //plugin is activated
    include "inc/shortcode.php";
}
?>