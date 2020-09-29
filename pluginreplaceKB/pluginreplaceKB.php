<?php
/*
    Plugin Name: PluginReplaceKB
    Plugin URI: 
    Description: Plugin by Daniel Gomez, Rony Santos.
    Version: 1.1
    Author: Daniel Gomez, Rony Santos.
    Author URI: http://example.com
    License: GPL2
    Text Domain: pluginreplaceKB
*/
// 
  include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

  function pluginreplacekb_install() {
    
      global $wpdb;
      global $jal_db_version;

      $charset_collate = $wpdb->get_charset_collate();

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      add_option( 'jal_db_version', $jal_db_version );   

  }
  register_activation_hook( __FILE__, 'pluginreplacekb_install' );

  // creación del Menú y componentes.
  add_action('admin_menu', 'pluginreplacekb_menu');
  function pluginreplacekb_menu()
  {
    add_menu_page('PluginReplaceKB', 'PluginReplaceKB', 'manage_options', 'pluginreplacekb_script', 'pluginreplacekb_script', 'dashicons-editor-spellcheck');
    // add_submenu_page( 'pluginreplace_script', 'Options', 'Options', 'manage_options', 'saving_first_data', 'saving_first_data');
    
  }

  // función main del plugin
  function pluginreplacekb_script(){

    // variables de renderizado de vista
    $var_html_content = '';
    $var_css_styles = '';
    $var_script_js = '';

    $arr_ids_update = array();
    $countable = 0; //contador inicializado en 0

    global $wpdb;
    $tabla = $wpdb->prefix . 'posts';


    $var_html_content .=  ' 

                            <div class="content_replace_plugin">
                              <h1 style="text-align: center;">Reemplazar contenido</h1>
                              <hr />
                          ';

    // Las variables de display muestran o ocultan el contenido 
    $display_buscar = '';
    $display_reemplazar = '';
    if( isset($_POST["fbuscar"]) && $_POST["fbuscar"] != '' ){
      $display_buscar = "none";
      $display_reemplazar = 'block';
    } else{
      $display_buscar = "block";
      $display_reemplazar = 'none';
    }

    if( isset($_POST["lreplace"]) && $_POST["lreplace"] != '' ){
      $display_buscar = "block";
      $display_reemplazar = 'none';
    }
    
    // formularios de cambios y entradas de texto
    $text_replace .=  '
                        <div style="display: flex; flex-direction: row; width: 100%; height: 50px; ">
                          <form method="post" style="display: flex; flex-direction: row; height: 100%; width: 100%; justify-content: center;
                                                      align-items: center; align-content: center;">
                            <label style="margin: auto 15px; color: #000;
                                          font-size: 18px;
                                          font-weight: 800;" for="fbuscar">Buscar:</label><br>
                            <input style="margin-right: 25px; border: solid 1px #2980B9;" type="text" id="fbuscar" name="fbuscar" value="' . $_POST["fbuscar"] . '"><br>
                            <label style="margin: auto 15px; color: #000;
                                          font-size: 18px; display: '. $display_reemplazar .';
                                          font-weight: 800;" for="lreplace">Reemplazar por:</label><br>
                            <input style="margin-right: 25px; border: solid 1px #2980B9; display: '. $display_reemplazar .';" type="text" id="lreplace" name="lreplace"><br><br>
                            <input  id="btn_buscar"
                                    style="  border: solid 2px #5499C7;
                                            display: '. $display_buscar .';
                                            cursor: pointer;
                                            width: 120px;
                                            background-color: #FFF;
                                            border-radius: 20px;
                                            color: #000;
                                            font-weight: 700;" type="submit" value="Buscar">
                            <input  id="btn_reemplazar"
                                    style="  border: solid 2px #5499C7;
                                            display: '. $display_reemplazar .';
                                            cursor: pointer;
                                            width: 120px;
                                            background-color: #FFF;
                                            border-radius: 20px;
                                            color: #000;
                                            font-weight: 700;" type="submit" value="Reemplazar">
                          </form> 
                        </div>
                      ';

    $content .= $text_replace. '<div style="height: 400px; overflow-y: scroll;">';
    $content .= '<table class="" id="customers"><tbody style="">';
    $content .= ' <tr style="">
                    <th style="">ID</th>
                    <th style="">Date</th>
                ';
    
    if( isset($_POST) ){

      $arr_ids_update = array();

      if( isset($_POST["fbuscar"]) && $_POST["fbuscar"] != '' && isset($_POST["lreplace"]) && $_POST["lreplace"] != '' ){

        $content .= '
                        <th style="">Estatus</th>
                        <th style="">Title</th>
                      </tr>
                    ';
        
        $find_str = $_POST["fbuscar"]; // variable que se esta buscando 
        $replace_str = $_POST["lreplace"]; // nuevo texto

        // Query que obtiene los posts con la variable de busqueda
        $sql_query = "SELECT * FROM `".$tabla."` where `post_content` like '%". $find_str ."%';";
        $items = $wpdb->get_results($sql_query);  

        foreach( $items as $item ){

          $countable++;
          $content .= '<tr style="">';

          $content .= '<td style="">' . $item->ID . '</td>';
          $content .= '<td style="">' . $item->post_date . '</td>';

          // Si existen resultadoss se actualiza en cada iteración
          $sql_replace = "UPDATE `{$tabla}` SET `post_content` = REPLACE(`post_content`, '". esc_sql($find_str) ."', '". esc_sql($replace_str) ."') WHERE ID = ". esc_sql($item->ID) .";";
          $validate_result = $wpdb->query($sql_replace);
          $counting_query = ( $validate_result == 1) ? '<p style="color: #229954;">Actualizado</p>' : '<p style="color: #C0392B;">Error</p>';

          $content .= '<td style="">' . $counting_query . '</td>';
          $content .= '<td style=""><a href="' . esc_url( get_permalink( $item->ID ) ) . '">' . $item->post_title. '</a></td>';
          $content .= '</tr>';

          if( $validate_result == 1 ){

            // Si cada iteración que se actualiza es efectiva se guarda un registro de lo que se hizo
            
            $arr_tmp = array(
              "id_post" => $item->ID,
              "titulo_post" => $item->post_title,
              "fecha_actualización" => date("Y-m-d H:i:s"),
              "texto_buscado" => $find_str,
              "texto_actualizado" => $replace_str
            );
  
            array_push($arr_ids_update, $arr_tmp);

          }

        }

        // se actualiza la opción con el nuevo resultado
        $anterior_arr = get_option( 'set_history_replace_plugin' ) != false ? get_option( 'set_history_replace_plugin' ) : array();
        array_push( $anterior_arr, $arr_ids_update);
        update_option( 'set_history_replace_plugin', $anterior_arr );

      } elseif( isset($_POST["fbuscar"]) && $_POST["fbuscar"] != '' ){

        $content .= '<th style="">Title</th></tr>';

        $sql_query = "SELECT * FROM `".$tabla."` where `post_content` like '%". $_POST["fbuscar"] ."%';";
        $items = $wpdb->get_results($sql_query);  

        foreach( $items as $item ){

          $countable++;
          $content .= '<tr style="">';
          $content .= '<td style="">' . $item->ID . '</td>';
          $content .= '<td style="">' . $item->post_date . '</td>';
          $content .= '<td style=""><a href="' . esc_url( get_permalink( $item->ID ) ) . '" target="_blank" rel="noopener noreferrer">' . $item->post_title. '</a></td>';
          $content .= '</tr>';

        }

      }

    }

    $content .= '</tbody></table>';
    $content .= '</div>';

    if( isset($_POST) ){

      if( isset($_POST["fbuscar"]) && $_POST["fbuscar"] ){
        $content .= '<h4 style="color: #229954;">Resultados: '. $countable .'</h4>';
      }
    
    }

    $var_html_content .=  $content . '</div>';

    $var_css_styles .=  '
                          <style>
                            
                            .content_replace_plugin{
                              width: 100%;
                              height: 600px;
                              display: block;
                              flex-direction: column;
                              justify-content: center;
                              align-items: center;
                              align-content: center;
                            }

                            .content_replace_plugin h1{
                              color: #000;
                              font-weight: bold;
                              font-size: 40px;
                            }

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

    $var_script_js =  '
                            <script>
                              jQuery( document ).ready(function() {

                                var count_tr = jQuery("#customers tbody tr").length;
                                if( count_tr && count_tr > 1 ){

                                  jQuery("#btn_reemplazar").show();
                                  jQuery("#btn_buscar").hide();

                                }else{

                                  jQuery("#btn_reemplazar").hide();
                                  jQuery("#btn_buscar").show();

                                }

                                let log = console.log;
                                log( count_tr );

                              });
                            </script>
                      ';

    $return_content = $var_html_content . $var_css_styles . $var_script_js;
    echo $return_content;
    
    // var_dump( get_option( 'set_history_replace_plugin' ) );

  }



?>