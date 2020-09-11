<?php
// Add Shortcode
function custom_shortcode( $atts ) {

	// Attributes
	$atts = shortcode_atts(
		array(
            'bol' => 'bol'
		),
		$atts
	);
    $idproduct = $atts['bol'];
 
 
    $data_product = get_mybol($idproduct);
    if($data_product == NULL){

        if( is_int($idproduct) ){
            get_mybol_save($idproduct);
            $data_product = get_mybol($idproduct);
        }
        
    }

 $text_button_product_box = ( get_option( 'texto_en_boton_compra_bol' ) ) ? get_option( 'texto_en_boton_compra_bol' ) : 'Comprar en Bol.com';

 $content_product = "<div class='aawp'>
 <div class='kb_aawp aawp-box' >
     <div class='kb_aawp_image'>
         <figure>
             <a href='".$data_product->item_afflink."' rel='nofollow' target='_blank' class='no-underline'>
                 <img src='".$data_product->item_xlthumb."' alt='".$data_product->item_title."' >
                 <noscript><img src='".$data_product->item_xlthumb."' alt='".$data_product->item_title."' /></noscript>
             </a>
         </figure>
         <p></p>
     </div>
     <hr>
     <div class='kb_aawp_body'>
         <div class='kb_aawp_body_1'>
             <div class='kb_aawp_tags'>
                 <div class='kb_aawp_affiliate'>
                     AffiliateLink </div>
                 <p></p>
             </div>
             <p>
                 <a href='".$data_product->item_afflink."' title='".$data_product->item_afflink."' rel='nofollow' target='_blank'>
                     <br> ".$data_product->item_title." </a>
                 <br>
             </p>
         </div>
         <div class='kb_aawp_body_2'>
             <div class='kb_aawp_price'>
                 <div class='kb_aawp_price_1'>".number_format($data_product->item_price, 2, ',', ',')." EUR
                 </div>
                 <div class='kb_aawp_price_2'>
                 </div>
                 <p></p>
             </div>
             <div class='kb_aawp_btn'>
                 <a href='".$data_product->item_afflink."' target='_blank' rel='nofollow'>
                     <br>
                     <svg>
                         <use xlink:href='#shopping-cart'></use>
                     </svg>". $text_button_product_box ."</a>
             </div>
             <p></p>
         </div>
         <p></p>
     </div>
 </div>";

 return $content_product;
}
add_shortcode( 'bolcom', 'custom_shortcode' );
?>