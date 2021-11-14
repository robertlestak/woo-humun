<?php

/**
 * Plugin Name:  woo-humun
 * Description:  Integrate humun with WooCommerce
 * Version:      0.0.1
 * Author:       humun
 * Author URI:   https://humun.us
 * Text Domain:  humun
 */

$humunAPI = $_ENV["HUMUN_API"];

if ($humunAPI == "") {
    $humunAPI = "https://humun.us/api/v1";
}

add_action('admin_menu', 'humun_admin');

function humun_admin(){
        add_menu_page( 'humun', 'humun', 'manage_options', 'humun', 'display_humun_admin_page' );
}

function display_humun_admin_page() {
  include('views/admin.php');
}

register_setting('humun', 'tenant');


function createHumunOrderFromWooOrder($order_id, $humun_ids) {
    $order = wc_get_order( $order_id );
    $billing_email  = $order->get_billing_email();

    // Get the Customer billing phone
    $billing_phone  = $order->get_billing_phone();

    // Customer billing information details
    $billing_first_name = $order->get_billing_first_name();
    $billing_last_name  = $order->get_billing_last_name();
    $billing_company    = $order->get_billing_company();
    $billing_address_1  = $order->get_billing_address_1();
    $billing_address_2  = $order->get_billing_address_2();
    $billing_city       = $order->get_billing_city();
    $billing_state      = $order->get_billing_state();
    $billing_postcode   = $order->get_billing_postcode();
    $billing_country    = $order->get_billing_country();

    // Customer shipping information details
    $shipping_first_name = $order->get_shipping_first_name();
    $shipping_last_name  = $order->get_shipping_last_name();
    $shipping_company    = $order->get_shipping_company();
    $shipping_address_1  = $order->get_shipping_address_1();
    $shipping_address_2  = $order->get_shipping_address_2();
    $shipping_city       = $order->get_shipping_city();
    $shipping_state      = $order->get_shipping_state();
    $shipping_postcode   = $order->get_shipping_postcode();
    $shipping_country    = $order->get_shipping_country();

    $name = "";
    $address1 = "";
    $address2 = "";
    $city = "";
    $state = "";
    $postcode = "";
    $country = "";

    if ($billing_first_name) {
        $name = $billing_first_name . " " . $billing_last_name;
    } else {
        $name = $shipping_first_name . " " . $shipping_last_name;
    }
    if ($billing_address_1) {
        $address1 = $billing_address_1;
    } else {
        $address1 = $shipping_address_1;
    }
    if ($billing_address_2) {
        $address2 = $billing_address_2;
    } else {
        $address2 = $shipping_address_2;
    }
    if ($billing_city) {
        $city = $billing_city;
    } else {
        $city = $shipping_city;
    }
    if ($billing_state) {
        $state = $billing_state;
    } else {
        $state = $shipping_state;
    }
    if ($billing_postcode) {
        $postcode = $billing_postcode;
    } else {
        $postcode = $shipping_postcode;
    }
    if ($billing_country) {
        $country = $billing_country;
    } else {
        $country = $shipping_country;
    }
    $parse = parse_url(get_site_url());
    $shop = $parse['host'];
    $shop = get_site_url();
    $post_data = json_encode(
        array(
            'product_ids' => array_map('intval', $humun_ids),
            'payment_id' => $shop . ":" . $order_id,
            'payment_type' => 'woocommerce',
            'total' => intval($order->get_total()),
            'customer' => array(
                'Name' => $name,
                'Address1' => $address1,
                'Address2' => $address2,
                'City' => $city,
                'State' => $state,
                'Zip' => $postcode,
                'Country' => $country,
                'Email' => $billing_email,
            ),
            'metadata' => array(
                'eth_address' => get_post_meta( $order_id, 'humun_customer_crypto_address', true ),
            )
        ));
    return $post_data;
}

function humun_createOrder($post_data) {
    global $humunAPI;
    $ch = curl_init($humunAPI . '/order/create');
    settings_fields('humun');
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_VERBOSE => TRUE,
        CURLOPT_FAILONERROR => FALSE,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'x-tenant-id: ' . get_option('tenant'),
        ),
        CURLOPT_POSTFIELDS => $post_data
    ));
    $streamVerboseHandle = fopen('php:///dev/stdout', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);
    $response = curl_exec($ch);
    if ($response === FALSE) {
        die(curl_error($ch));
    }
    $responseData = json_decode($response, TRUE);
    curl_close($ch);
    return $responseData;
}

function humun_handle_woo_order($order_id) {
    $order = wc_get_order( $order_id );
    $humun_ids = array();
    foreach ($order->get_items() as $item_id => $item ) {
        $humun_id = get_post_meta($item->get_product_id(), 'humun_id', true);
        if (!$humun_id) {
            continue;   
        }
        $humun_ids[] = $humun_id;
    }
    if (count($humun_ids) == 0) {
        return;
    }
    $post_data = createHumunOrderFromWooOrder($order_id, $humun_ids);
    $orderData = humun_createOrder($post_data);
    print_r($orderData);
}

add_action('woocommerce_payment_complete', 'humun_handle_woo_order', 1000, 2);

add_action('woocommerce_after_order_notes', 'humun_crypto_address');

function has_humun_id() {
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $has_humun_id = false;
    foreach($items as $item => $values) {
        $product_id = $values['product_id'];
        $humun_id = get_post_meta($product_id, 'humun_id', true);
        if ($humun_id) {
            $has_humun_id = true;
            break;
        }
    }
    return $has_humun_id;
}

function humun_crypto_address($checkout) {
    //echo '<div id="custom_checkout_field"><h2>' . __('Crypto Address') . '</h2>';
    // for each item in order, check if it has a humun_id
    // if any item has a humun_id, display the crypto address field
    
    if (!has_humun_id()) {
        return;
    }

    woocommerce_form_field('humun_customer_crypto_address', array(
        'type' => 'text',
        'class' => array(
            'humun-crypto-address form-row-wide'
        ) ,

        'label' => __('Crypto Address') ,
        'required' => true,
        'placeholder' => __('0x00...') ,
    ) , $checkout->get_value('humun_customer_crypto_address'));
}

add_action('woocommerce_checkout_process', 'humun_validate_crypto_address');

function humun_validate_crypto_address() {
    // Check if set, if its not set add an error.

    if (!has_humun_id()) {
        return;
    }

    if ( ! $_POST['humun_customer_crypto_address'] )
        wc_add_notice( __( 'Crypto address required for digital products' ), 'error' );
}

add_action( 'woocommerce_checkout_create_order', 'humun_add_crypto_address_to_order', 20, 2 );
function humun_add_crypto_address_to_order( $order, $data ) {
    if ( isset( $_POST['humun_customer_crypto_address'] ) ) {
        $order->update_meta_data( 'humun_customer_crypto_address', esc_attr( $_POST['humun_customer_crypto_address'] ) );

        if( $order->get_customer_id() ) {
            update_user_meta( $order->get_customer_id(), 'humun_customer_crypto_address', esc_attr( $_POST['humun_customer_crypto_address'] ) );
        }
    }
}