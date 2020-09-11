<?php
// Menu
add_action('admin_menu', 'mybol_plugin_menu');
function mybol_plugin_menu()
{
  add_menu_page('Bolcom', 'Bolcom', 'manage_options', 'mybol_script', 'mybol_script', 'dashicons-store');
  add_submenu_page( 'mybol_script', 'Options', 'Options', 'manage_options', 'saving_first_data', 'saving_first_data');
  add_submenu_page( 'mybol_script', 'Button Text', 'Button Text', 'manage_options', 'button_text', 'button_text');
  // add_submenu_page( 'hiperloop_script', 'HiperLog', 'Hiperloop - Log', 'manage_options', 'hiperloop_log', 'hiperloop_log');
  
}
?>