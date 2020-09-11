<?php

// funcion que imprime el form de guardado
function saving_first_data(){


    $preload_values = get_option( 'configurating_options' );
    // var_dump( $preload_values );

    $keyid = $_POST['keyid'] ? $_POST['keyid'] : null;
    $siteid = $_POST['siteid'] ? $_POST['siteid'] : null;
    $pais_selected = $_POST['pais_selected'] ? $_POST['pais_selected'] : null;
    $activate_mybol = $_POST['activate_mybol'] ? $_POST['activate_mybol'] : null;

    // echo "<br /><br />";
    // var_dump( $activate_mybol );
    

    ?>


    <div class="admin_manage_panel">
        <h1>Configuration options</h1>
        <hr />

        <form id="form-retrieved-saving" name="form-retrieved-saving" method="POST" style=" display: <?php echo ( !empty($_POST) ) ? 'none' : ''; ?> ">
            <section class="container">
                <label for="keyid"><p>API key (API Access Key):</p></label>
                <input type="text" id="keyid" name="keyid" placeholder="Insert Key ID" value="<?php echo $preload_values['keyid']; ?>" >
            </section>

            <section class="container">
                <label for="siteid"><p>Partner Program siteid:</p></label>
                <input type="text" id="siteid" name="siteid" placeholder="Partner program" value="<?php echo $preload_values['siteid']; ?>">
            </section>

            <section class="container">
                <label for="pais_selected1" class="container-radius">Dutch catalog
                    <input type="radio" <?php echo ( $preload_values['pais_selected'] == 'nl' ) ? 'checked' : ''; ?> name="pais_selected" id="pais_selected1" value="nl">
                    <span class="checkmark"></span>
                </label>

                <label for="pais_selected2" class="container-radius">Belgium catalog
                    <input type="radio" <?php echo ( $preload_values['pais_selected'] == 'de' ) ? 'checked' : ''; ?> name="pais_selected" id="pais_selected2" value="de">
                    <span class="checkmark"></span>
                </label>
            </section>

            <section class="container">
                <h5 style="width: 80px; display: inline; margin: 0 25px 0 0;">Activate Mybol</h5>
                <label class="switch" for="activate_mybol">
                    <input type="checkbox" name="activate_mybol" id="activate_mybol" <?php echo ( $preload_values['activate_mybol'] == 'on' ) ? 'checked' : ''; ?> >
                    <span class="slider round"></span>
                </label>
            </section>

            <input class="btn btn-send" type="submit" name="save_config" value="Save">
        
        </form>


        <?php
           
            if ( !empty($_POST) ){
                // var_dump( get_option( 'configurating_options' ) );
                if ( $_SERVER['REQUEST_METHOD'] == 'POST' ){
                    
                    if( $keyid !== null && $siteid !== null && $pais_selected !== null ){
                        $arr_tmp = array();
                        $arr_tmp = array(
                            "keyid"=>$keyid,
                            "siteid"=>$siteid,
                            "pais_selected"=>$pais_selected,
                            "activate_mybol"=>$activate_mybol
                        );
                        $var_print_done =   '
                                    <h2>Se guardo correctamente la configuracion</h2>
                                    <div style="height: 50px;">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 50px; color: #46b450; transform: translateX(70px);"></span>
                                    </div>
                                ';

                        
                        if( get_option( 'configurating_options' ) ){
                            update_option( 'configurating_options', $arr_tmp );
                            // var_dump( get_option( 'configurating_options' ) );
                            echo $var_print_done;
                        }else{
                            add_option( 'configurating_options', $arr_tmp );
                            // var_dump( get_option( 'configurating_options' ) );
                            echo $var_print_done;
                        }
                        
                    }else{
                        $var_print_error =   '
                                    <h2>Hay campos sin completar</h2>
                                    <div style="height: 50px;">
                                        <span class="dashicons dashicons-no" style="font-size: 50px; color: #A93226; transform: translateX(70px);"></span>
                                    </div>
                                ';

                        echo $var_print_error;
                    }

                }
                else{
                    null;
                }
            }else{
                null;
            }
        
        ?>


    </div>


    <Style>

        .admin_manage_panel{
            width: 98%;
            display: block;
            margin-left : 20px
        }

        section.container{
            margin: 10px auto;
        }

        section.container label p{
            width: 150px;
            display: inline-block;
        }

        section.container input[type=text]{
            width: 400px;
        }

        .container-radius{
            display: inline-block;
            position: relative;
            padding-left: 35px;
            margin-bottom: 12px;
            cursor: pointer;
            width: 175px;
            text-align: left;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .container-radius input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 25px;
            width: 25px;
            background-color: #eee;
            border-radius: 50%;
        }

        .container-radius:hover input ~ .checkmark {
            background-color: #ccc;
        }

        .container-radius input:checked ~ .checkmark {
            background-color: #2196F3;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .container-radius input:checked ~ .checkmark:after {
            display: block;
        }

        .container-radius .checkmark:after {
            top: 9px;
            left: 9px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
        }

        .btn-send {
            display: flex;
            justify-content: center;
            align-content: center;
            align-items: center;
            height: 40px;
            width: 200px;
            border-radius: 25px;
            background: #FFF;  
            border: solid 1px #00a0d2;
            font-weight: 900;
            letter-spacing: 1px;
            transition: all 150ms linear;
            cursor: pointer;
        }

        .btn-send:hover {
            background: #d6dbdf ;
            text-decoration: none;
            transition: all 250ms linear;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        /* Hide default HTML checkbox */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* The slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

    </Style>


    <?php

}






// funcion que imprime el input de texto del botón
function button_text(){

    $preload_val_button_text = get_option( 'texto_en_boton_compra_bol' ) ? get_option( 'texto_en_boton_compra_bol' ) : null;

    $new_text = $_POST["new_text"] ? $_POST["new_text"] : null;


    ?>


    <div class="admin_manage_panel">
        <h1>Configuration button text</h1>
        <hr />

        <form id="form-retrieved-saving" name="form-retrieved-saving" method="POST" style=" display: <?php echo ( !empty($_POST) ) ? 'none' : ''; ?> ">
            <section class="container">
                <label for="new_text"><p>Button text (Product Box):</p></label>
                <input type="text" id="new_text" name="new_text" placeholder="Insert text button" value="<?php echo $preload_val_button_text; ?>" >
            </section>

            <input class="btn btn-send" type="submit" name="save_config" value="Save">
        
        </form>


    </div>




    <style>
        .admin_manage_panel{
            width: 98%;
            display: block;
            margin-left : 20px
        }

        section.container{
            margin: 10px auto;
        }

        section.container label p{
            width: 190px;
            display: inline-block;
        }

        section.container input[type=text]{
            width: 400px;
        }

        .btn-send {
            display: flex;
            justify-content: center;
            align-content: center;
            align-items: center;
            height: 40px;
            width: 200px;
            border-radius: 25px;
            background: #FFF;  
            border: solid 1px #00a0d2;
            font-weight: 900;
            letter-spacing: 1px;
            transition: all 150ms linear;
            cursor: pointer;
        }

        .btn-send:hover {
            background: #d6dbdf ;
            text-decoration: none;
            transition: all 250ms linear;
        }
    </style>




    <?php


        if ( !empty($_POST) ){
            // var_dump( get_option( 'configurating_options' ) );
            if ( $_SERVER['REQUEST_METHOD'] == 'POST' ){
                
                if( $new_text !== null ){
                    $var_print_done =   '
                                <h2>Se guardo correctamente la configuracion</h2>
                                <div style="height: 50px;">
                                    <span class="dashicons dashicons-yes-alt" style="font-size: 50px; color: #46b450; transform: translateX(70px);"></span>
                                </div>
                            ';

                    
                    if( get_option( 'texto_en_boton_compra_bol' ) ){
                        update_option( 'texto_en_boton_compra_bol', $new_text );
                        // var_dump( get_option( 'texto_en_boton_compra_bol' ) );
                        echo $var_print_done;
                    }else{
                        add_option( 'texto_en_boton_compra_bol', $new_text );
                        // var_dump( get_option( 'texto_en_boton_compra_bol' ) );
                        echo $var_print_done;
                    }
                    
                }else{
                    $var_print_error =   '
                                <h2>Hay campos sin completar</h2>
                                <div style="height: 50px;">
                                    <span class="dashicons dashicons-no" style="font-size: 50px; color: #A93226; transform: translateX(70px);"></span>
                                </div>
                            ';

                    echo $var_print_error;
                }

            }
            else{
                null;
            }
        }else{
            null;
        }



}




?>