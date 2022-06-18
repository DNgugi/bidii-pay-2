<?php
function init_custom_gateway_class(){   
    class Bidii_Pay_WC_Custom_Gateway extends WC_Payment_Gateway {

        public $domain;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'bidii_pay';

            $this->id                 = 'bidii_pay_mpesa';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = true;
            $this->method_title       = __( 'Bidii Pay M-Pesa Gateway', $this->domain );
            $this->method_description = __( 'Allows payments with M-Pesa Express.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Save Settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            
            //Add to thankyou page
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Add instructions entred in settings before the order summary table in customer emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

            //M-Pesa sends GET to this callback if payment was successful(triggered by quering transaction status api)
            add_action( 'woocommerce_api_success', array( $this, 'success' ) );

            add_action( 'woocommerce_api_timeout', array( $this, 'timeout' ) );

        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Bidii Pay M-Pesa Gateway', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'Bidii Pay M-Pesa Gateway', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page($order_id) {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        // public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        //     if ( $this->instructions && ! $sent_to_admin && 'bidii_pay_mpesa' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
        //         echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
        //     }
        // }

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            ?>
            <div id="custom_input">
                <p class="form-row form-row-wide">
                    <label for="mobile" class=""><?php _e('Mobile Number', $this->domain); ?></label>
                    <input type="text" class="" name="mobile" id="mobile" placeholder="0700123000" value="">
                </p>
            </div>
            <?php
        }

        public function success() {
 
            $order = wc_get_order( $_GET['id'] );
            $order->payment_complete();
        }

        public function timeout() {
 
            echo 'payment time out';
        }

        
        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        function process_payment( $order_id ) {
    global $woocommerce;
    $order = new WC_Order( $order_id );

    // Mark as on-hold (we're awaiting the cheque)
    bidii_pay_process_payment($order_id);
    $order->update_status('pending-payment', __( 'Awaiting M-Pesa payment', 'kary' ));
            

    // Return thankyou redirect
    return array(
        'result' => 'success',
        'redirect' => site_url().'/confirm-payment/'
    );
}
    }
}
add_action('plugins_loaded', 'init_custom_gateway_class');


function add_custom_gateway_class( $methods ) {
    $methods[] = 'Bidii_Pay_WC_Custom_Gateway'; 
    return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );

/**
 * Update the order meta with field value
 */
function custom_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'bidii_pay_mpesa')
        return;

    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";
    // exit();

    update_post_meta( $order_id, 'mobile', $_POST['mobile'] );
}
add_action( 'woocommerce_checkout_update_order_meta', 'custom_payment_update_order_meta' );


/**
 * Display field value on the order edit page
 */
function custom_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'bidii_pay_mpesa')
        return;

    $mobile = get_post_meta( $order->id, 'mobile', true );

    echo '<p><strong>'.__( 'Mobile Number' ).':</strong> ' . $mobile . '</p>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'custom_checkout_field_display_admin_order_meta', 10, 1 );

include('custom/send-request.php');