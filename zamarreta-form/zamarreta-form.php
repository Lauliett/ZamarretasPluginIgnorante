<?php

/*
 * Plugin Name: Zamarreta form
 * Description: Un formulario para la venta de nuestras camisetas. Usando el shortcode [zamarreta-form]
 * Version: 1.0
 * Author: Apoyo Mutuo Aragon
 * 
 */


 register_activation_hook(__FILE__, 'Zamarreta_Aspirante_init');

function Zamarreta_Aspirante_init(){
    global $wpdb;
    $tabla_aspirante = $wpdb->prefix . 'zamarreta_pedida';
    $charset_collate = $wpdb->get_charset_collate();
    //Prepara la consulta que vamos a lanzar para crear la tabla
    $query  = "CREATE TABLE IF NOT EXISTS $tabla_aspirante (
        id mediumint(9) AUTO_INCREMENT,
        persona varchar(80) not null,
        telefono varchar(10) not null,
        email_contacto varchar(100) not null,
        unidades int not null,
        tallas varchar(100) not null,
        direccion varchar(200) not null,
        observacion varchar(500) not null,        
        UNIQUE (id)
    ) $charset_collate";

    include_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($query);
}



function custom_scripts() {
    wp_register_script( 'main', plugin_dir_url(__FILE__).'js/custom.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'main' );
}
add_action( 'wp_enqueue_scripts', 'custom_scripts' );

//Define el shortcode que pinta el formulario
add_shortcode('zamarreta-form', 'zamarreta_form');

function zamarreta_form(){

    recoger_datos();
    ob_start();
    
    //En todo formulario que hagamos debemos tratar de evitar un ataque que se llama Cross Site forgery
    //introducen un campo hiden con un token. usamos un "nonce", wordpress nos da ya esta función"
    ?>
    <form action="<?php get_the_permalink() ?>" method="post" 
        class="compraZamarreta" id="zamarreta">
        <?php wp_nonce_field('graba_pedido', 'pedido_nonce'); ?>
        <div class="form-input">
            <label for="¿Cuántas zamarretas quieres?">Unidades</label>
            <input id="unidades" type="number" name="unidades" min="1" value="1" required>

            <div id="mostradorTallas">
                Dinos la talla de tus zamarretas <br/>
                <label for="tallas">Talla:</label>
                <select class="tallas" name="talla[]" form="zamarreta" required>
                <option value="s">S</option>
                <option value="m">M</option>
                <option value="l">L</option>
                </select>
            </div>
            Para hacerte llegar la camiseta, necesitamos tus datos de contacto.
            <label for="persona">Nombre y Apellidos</label>
            <input type="text" id="persona" name="persona" required>

            <label for="telephone">Numero de telefono</label>
            <input type="tel" id="phone" name="phone"
            pattern="[0-9]{9}" required>

            <label for="email">Email de contacto:</label>
            <input type="email" id="email" name="email">

            <label for="direccion">Si necesitas que te enviemos la camiseta a casa, tu direccion</label>
            <input type="text" id="direccion" name="direccion" require="required">

            <label for="comentarios">¿Tienes alguna sugerencia u observación? Te leemos</label>
            <textarea name="comment" form="zamarreta"></textarea>

            <input type="submit" value="Encargar zamarretas">            
        </div>
    </form>
    <?php
    return ob_get_clean();
}

function recoger_datos(){
    global $wpdb;

    if(!empty($_POST)
      AND $_POST['persona'] != ''
      AND $_POST['unidades'] > 0
      AND count($_POST['talla']) == $_POST['unidades']
      AND $_POST['phone'] != ''){

        //Tenemos que sanear las variables para prevenir la inyeccion sql o de cosas chunguis
        
        $persona = sanitize_text_field($_POST['persona']);
        $telefono = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $numeroCamisetas = (int)$_POST['unidades'];
        $direccion = sanitize_text_field($_POST['direccion']);
        $comentarios = sanitize_text_field($_POST['comment']);
        //Nos quedan las tallas, que vienen en un array!!!!.
        $tallas = "";
        if ( !empty($_POST["talla"]) && is_array($_POST["talla"]) ) { 
            foreach ( $_POST["talla"] as $talla ) { 
                    $tallas = $tallas . $talla . ", "; 
             }
             echo "</ul>";
        }
        $tallas = sanitize_text_field($tallas);
        
        $wpdb->insert($wpdb->prefix . 'zamarreta_pedida', array(
            'persona' => $persona,
            'telefono' => $telefono,
            'email_contacto' => $email,
            'unidades' => $numeroCamisetas,
            'tallas' => $tallas,
            'direccion' => $direccion,
            'observacion' => $comentarios,
        ));    
    }
}

// El hook "admin_menu" permite agregar un nuevo item al menú de administración
add_action("admin_menu", "Zamarreta_Pedido_menu");
 
/**
 * Agrega el menú del plugin al escritorio de WordPress
 *
 * @return void
 */
function Zamarreta_Pedido_menu() 
{
    add_menu_page(
        'Formulario zamarretas pedidas', 'Zamarretas', 'manage_options', 
        'zamarretas_pedidos_menu', 'Zamarreta_Form_admin', 'dashicons-feedback', 75
    );
}

function Zamarreta_Form_admin()
{
    global $wpdb;
    $tabla_zamarretas = $wpdb->prefix . 'zamarreta_pedida';
    $pedidos = $wpdb->get_results("SELECT * FROM $tabla_zamarretas");

    echo '<div class="wrap"><h1>Lista de zamarretas pedidas</h1>';
    echo '<p>Total de camisetas pedidas: ' . count($pedidos);
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Personas </th><th>telefono</th>
        <th>email_contacto</th><th>unidades</th><th>tallas</th>
        <th>direccion</th><th>observacion</th></tr></thead>';
    echo '<tbody id="the-list">';
    
    foreach ( $pedidos as $pedido ) {
        $nombre = esc_textarea($pedido->persona);
        $telefono = esc_textarea($pedido->telefono);
        $email = esc_textarea($pedido->email_contacto);
        $unidades = esc_textarea($pedido->unidades);
        $tallas = esc_textarea($pedido->tallas);
        $direccion = esc_textarea($pedido->direccion);
        $observacion = esc_textarea($pedido->observacion);
        //$motivacion = esc_textarea($aspirante->motivacion);
        //$nivel_html = (int)$aspirante->nivel_html;
        $nivel_css = "";
        $nivel_js = "";
        $nivel_php = "";
        $nivel_wp = "";
        //$total = $nivel_html + $nivel_css + $nivel_js + $nivel_php + $nivel_wp;
        echo "<tr><td><a href='#' title='pedido'>$nombre</a></td>
            <td>$telefono</td><td>$email</td><td>$unidades</td>
            <td>$tallas</td><td>$direccion</td><td>$observacion</td></tr>";
    }
    echo '</tbody></table></div>';
}