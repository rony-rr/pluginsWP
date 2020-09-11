<?php

function get_mybol_save($idproduct){
    global $wpdb;
	$prefix = $wpdb->prefix;
    $tabla = $wpdb->prefix . 'mybol_kb';
    $partnerlink_prefix = "https://partnerprogramma.bol.com/click/click?p=1&amp;t=url&amp;s=";
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.bol.com/catalog/v4/products/".$idproduct."?apikey=CC511E5D04E844F2935A2046D882A11C&format=xml&includeattributes=true&country=nl",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    

    //// get data by xml file 
    $phpobject = simplexml_load_string($response);

    $options_params = get_option( 'configurating_options' ) ? get_option( 'configurating_options' ) : 'No Available' ;

    if( $options_params !== null ){

        if( $options_params["siteid"] !== NULL ){

            foreach ($phpobject->Products as $item) {
                //useful data   
                $item_title = $item -> Title;
                $item_subtitle = $item -> Subtitle;
                $item_externalurl = $item -> Urls[0] -> Value;
                //$item_afflink
                $item_xlthumb = preg_replace("/^http:/i", "https:", $item -> Images[4]-> Url);
                $item_lthumb = preg_replace("/^http:/i", "https:", $item -> Images[3]-> Url);
                $item_mthumb = preg_replace("/^http:/i", "https:", $item -> Images[2]-> Url);
                $item_sthumb = preg_replace("/^http:/i", "https:", $item -> Images[1]-> Url);
                $item_xsthumb = preg_replace("/^http:/i", "https:", $item -> Images[0]-> Url);
                $item_price = doubleval($item -> OfferData -> Offers[0] -> Price);
                $item_listprice = doubleval($item -> OfferData -> Offers[0] -> ListPrice);
                $item_availability = $item -> OfferData -> Offers[0] -> AvailabilityDescription;
                $item_availabilitycode = $item -> OfferData -> Offers[0] -> AvailabilityCode;
                $item_rating = $item -> Rating;
                //$item_ratingspan
                $time = time();
                
                if (@GetImageSize($item_sthumb)) { 
        
                } else { $item_sthumb = "https://www.bol.com/nl/static/images/main/noimage_124x100default.gif"; }
                    
                    if ($item_rating != "") {
                        $nicerating = (int) $item_rating/10;                    
                        $altrating = $nicerating;
                        $nicerating = round($nicerating * 2) / 2;
                        if (strlen($nicerating) < 2) { $nicerating .= ".0"; } 
                        $nicerating = str_replace(".", "_", $nicerating);                    
                        $item_ratingspan = '<span class="rating"><img alt="'.sprintf(__('Score %1$.1f out of 5 stars.', 'yabp'), $altrating).'" title="'.sprintf(__('Score %1$.1f out of 5 stars.', 'yabp'), $altrating).'" src="'.preg_replace("/^http:/i", "https:", plugin_dir_url( __FILE__ )).'img/icons/'. $nicerating . '.png"></span>';
                    } 
                    else { $item_ratingspan = ''; }
                }
        
                $item_afflink = $partnerlink_prefix . $options_params["siteid"] . "&amp;f=TXL&amp;url=".urlencode($item_externalurl)."&amp;name=".urlencode(strtolower($item_title));
                
                $exist = $wpdb->get_results("SELECT entry_id FROM {$tabla} where entry_id =".$idproduct."");
                // var_dump( count($exist) );
               
                if( count($exist) == 0 ){

                    $a = $wpdb->query("INSERT INTO `{$tabla}` (id, entry_id,  item_title, item_subtitle, item_externalurl, item_afflink, item_xlthumb, item_lthumb, item_mthumb, item_sthumb, item_xsthumb, item_price, item_listprice, item_availability, item_availabilitycode, item_rating, item_ratingspan, time) VALUES (NULL, '".$idproduct."', '".esc_sql($item_title)."', '".esc_sql($item_subtitle)."', '".esc_sql($item_externalurl)."', '".esc_sql($item_afflink)."', '".esc_sql($item_xlthumb)."', '".esc_sql($item_lthumb)."', '".esc_sql($item_mthumb)."', '".esc_sql($item_sthumb)."', '".esc_sql($item_xsthumb)."', '".esc_sql($item_price)."', '".esc_sql($item_listprice)."', '".esc_sql($item_availability)."', '".esc_sql($item_availabilitycode)."', '".esc_sql($item_rating)."', '".esc_sql($item_ratingspan)."', '".esc_sql($time)."')");   
                    $a = $a . ' creado';
                }else{
                    // var_dump( $item_afflink );
                    // echo '<br /><br />';
                    $a = $wpdb->query("UPDATE `{$tabla}` SET item_title = '".esc_sql($item_title)."', item_subtitle = '".esc_sql($item_subtitle)."', item_externalurl = '".esc_sql($item_externalurl)."', item_afflink = '".esc_sql($item_afflink)."', item_xlthumb = '".esc_sql($item_xlthumb)."', item_lthumb = '".esc_sql($item_lthumb)."', item_mthumb = '".esc_sql($item_mthumb)."', item_sthumb = '".esc_sql($item_sthumb)."', item_xsthumb = '".esc_sql($item_xsthumb)."', item_price = '".esc_sql($item_price)."', item_listprice = '".esc_sql($item_listprice)."', item_availability = '".esc_sql($item_availability)."', item_availabilitycode = '".esc_sql($item_availabilitycode)."', item_rating = '".esc_sql($item_rating)."', item_ratingspan = '".esc_sql($item_ratingspan)."', time = '".esc_sql($time)."' WHERE entry_id = '".esc_sql($idproduct)."'");                        
                    $a = $a . ' actualizado';
                    // var_dump( $wpdb->last_query );
                    // echo '<br /><br />';
                }
        
                return $a;
            }

        }

    }
    
?>