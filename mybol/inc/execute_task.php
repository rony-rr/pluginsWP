<?php 

    // Esquema de action del Hook
    function execute_task_mybol_save( ) {

        global $wpdb;
        $tabla = $wpdb->prefix . 'mybol_kb';

        $items = $wpdb->get_results("SELECT * FROM `".$tabla."`");  

        foreach( $items as $item ){

            if( is_int($item->entry_id) ){

                get_mybol_save( $item->entry_id );
                
            }

        }
        
    }
    add_action( 'execute_task_mybol_save', 'execute_task_mybol_save' );

    // Linea que ejecuta la accion programada y desencadena las operaciones diariamente
    
    // para borrar el cron
    // wp_clear_scheduled_hook('execute_task_mybol_save');      

    // función de arranque al gancho init para ejecutar el evento
    function custom_cron_job_task_mybol_save() {
        
        if ( !wp_next_scheduled( 'execute_task_mybol_save' ) ) {
    
            wp_schedule_event( current_time('timestamp'), 'daily', 'execute_task_mybol_save', $args );
            
        }

    }
    add_action( 'init', 'custom_cron_job_task_mybol_save' );




    register_deactivation_hook( __FILE__, 'desactivate_execute_task_mybol_save' ); 
 
    function desactivate_execute_task_mybol_save() {
        wp_clear_scheduled_hook('execute_task_mybol_save');
    }

?>