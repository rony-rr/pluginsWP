<?php
function get_mybol($idproduct){
  global $wpdb;
	$prefix = $wpdb->prefix;
  $tabla = $wpdb->prefix . 'mybol_kb';

  // return array data 

  $get_data = $wpdb->get_row("SELECT * FROM {$tabla} WHERE `entry_id` = ".$idproduct."");

  return $get_data;
}
?>