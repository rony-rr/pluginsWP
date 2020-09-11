<?php
function mybolkb_install() {
    global $wpdb;
    global $jal_db_version;
  
    $tabla = $wpdb->prefix . 'mybol_kb';
  
  
        $charset_collate = $wpdb->get_charset_collate();
  
        $sql = "DROP TABLE IF EXISTS {$tabla};
                CREATE TABLE {$tabla} (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    entry_id INT NOT NULL,
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
  }
  register_activation_hook( __FILE__, 'mybolkb_install' );
?>