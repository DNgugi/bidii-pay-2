<?php

function bidii_pay_process_payment($order_id){
    $order = wc_get_order( $order_id );
    $total = (int)$order -> get_total();
    $mobile=$_POST['mobile'];
    // var_dump($total);
    // exit();
    
    if($_POST['payment_method'] != 'bidii_pay_mpesa'){
        return;
    }
    
    if( !isset($_POST['mobile']) || empty($_POST['mobile']) ){
        wc_add_notice( __( 'Please add your mobile number', 'bidii_pay' ), 'error' );
    }
    
    if( isset($mobile)){
        
        $mobile = (substr($mobile, 0, 1) == "+") ? str_replace("+", "", $mobile) : $mobile;
        $mobile = (substr($mobile, 0, 1) == "0") ? preg_replace("/^0/", "254", $mobile) : $mobile;
        $mobile = (substr($mobile, 0, 1) == "7") ? "254{$mobile}" : $mobile;
        
        $config = array(
            "env"              => "sandbox",
            "BusinessShortCode"=> "174379",
            "key"              => "6fSidiQK1v1f9sJG9m8Tzbs3SVTPgYfW", 
            "secret"           => "3hosoK1vkgnXv80u", 
            "username"         => "testapi",
            "TransactionType"  => "CustomerPayBillOnline",
            "passkey"          => "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919", 
            "CallBackURL"     => "https://charityfarm.co.ke/wc-api/timeout",
            "AccountReference" => "CompanyXLTD",
            "TransactionDesc"  => "Payment of X" ,
        );
        $access_token = ($config['env']  == "live") ? "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" : "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; 
        
        
        $credentials = base64_encode($config['key'] . ':' . $config['secret']); 
        $get_token = array(
            'headers' => array(
                'Authorization' => 'Basic '.$credentials
            ));
            
            
            $response = wp_remote_get( $access_token, $get_token );
            if ( ( !is_wp_error($response))) {
                $responseBody = wp_remote_retrieve_body( $response );
                $token_response = json_decode($responseBody);
                $token = isset( $token_response -> access_token) ?  $token_response -> access_token  : '';
                // echo 'Token: '.$token;
                
            }
            $timestamp = date("YmdHis");
            $password  = base64_encode($config['BusinessShortCode'] . "" . $config['passkey'] ."". $timestamp);
            
            
            //Start structuring call to express api
            $pay_request_data = array( 
                "BusinessShortCode" => $config['BusinessShortCode'],
                "Password" => $password,
                "Timestamp" => $timestamp,
                "TransactionType" => $config['TransactionType'],
                "Amount" => $total,
                "PartyA" => $mobile,
                "PartyB" => $config['BusinessShortCode'],
                "PhoneNumber" => $mobile,
                "CallBackURL" => $config['CallBackURL'],
                "AccountReference" => $config['AccountReference'],
                "TransactionDesc" => $config['TransactionDesc']
            ); 
            
            $request_payment_processing = array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$token
                ),
                'body' => json_encode($pay_request_data)
            );
            
            $endpoint = ($config['env'] == "live") ? "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest" : "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest"; 
            
            $queued = wp_remote_post($endpoint, $request_payment_processing);
            $queuedBody = wp_remote_retrieve_body( $queued );
            
            $queued_response = json_decode($queuedBody);
            $code= isset( $queued_response -> ResponseCode) ?  $queued_response -> ResponseCode  : '';
            
            if ( ( !is_wp_error($queued)) && $code === '0') {
                $message = isset( $queued_response -> CustomerMessage) ?  $queued_response -> CustomerMessage  : '';
                // exit();
            }
    }
}

add_action('woocommerce_checkout_process', 'bidii_pay_process_payment',10, 1);



function bidii_pay_check_transaction(){
        $transactionID = $_POST['txn-code']; 
        $order_id = $_POST['order-id'];
        $order = new WC_Order($order_id);
        $order_key = $order->get_order_key();

        $txn_config = array(
            "env"              => "sandbox",
            "type"              => 4,
            "shortcode"=> "600980",
            "key"              => "Azs2KejU1ARvIL5JdJsARbV2gDrWmpOB", 
            "secret"           => "hipGvFJbOxri330c", 
            "username"         => "testapi",
            "TransactionType"  => "CustomerPayBillOnline",
            "passkey"          => "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919", 
            "CallBackURL"     => "https://7ca6-105-48-214-48.eu.ngrok.io/wordpress/wc-api/timeout",
            "AccountReference" => "CompanyXLTD",
            "TransactionDesc"  => "Payment of X" ,
            "initiatorName" => "testapi",
            "initiatorPassword" => "Safaricom999!*!",
            "results_url" => "https://7ca6-105-48-214-48.eu.ngrok.io/wordpress/wc-api/success", //Endpoint to receive results Body
            "timeout_url" => "https://7ca6-105-48-214-48.eu.ngrok.io/wordpress/wc-api/timeout", //Endpoint to to go to on timeout
            "command" => "TransactionStatusQuery",
            "remarks" => "Transaction Status Query",
            "occasion" => "Transaction Status Query"
        );
        
        /*End  configurations*/
        $publicKey = file_get_contents(__DIR__ . "/mpesa_public_cert.cer"); 
        $isvalid = openssl_public_encrypt($txn_config['initiatorPassword'], $encrypted, $publicKey, OPENSSL_PKCS1_PADDING); 
        $password = base64_encode($encrypted);
        
        $access_tkn = ($txn_config["env"] == "live") ? "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" : "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; 
           
        $credential = base64_encode($txn_config['key'] . ':' . $txn_config['secret']); 
        $get_tkn = array(
            'headers' => array(
                'Authorization' => 'Basic '.$credential
            ));
        

        $res = wp_remote_get( $access_tkn, $get_tkn );
        if ( ( !is_wp_error($res))) {
            $resBody = wp_remote_retrieve_body( $res );
            $token_res = json_decode($resBody);
            $txn_token = isset( $token_res -> access_token) ?  $token_res -> access_token  : '';
            // echo 'Token: '.$token;
        
            $check_txn_data = array(   
                "Initiator" => $txn_config["initiatorName"], 
                "SecurityCredential" => $password, 
                "CommandID" => $txn_config["command"], 
                "TransactionID" => $transactionID, 
                "PartyA" => $txn_config["shortcode"], 
                "IdentifierType" => $txn_config["type"], 
                "ResultURL" => $txn_config["results_url"], 
                "QueueTimeOutURL" => $txn_config["timeout_url"], 
                "Remarks" => $txn_config["remarks"], 
                "Occasion" => $txn_config["occasion"],
            ); 
            

        $endpoint = ($txn_config["env"] == "live") ? "https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query" : "https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query"; 
        
         $request_payment_confirmation = array(
             'headers' => array(
                 'Content-Type' => 'application/json',
                 'Authorization' => 'Bearer '.$txn_token
                ),
                'body' => json_encode($check_txn_data)
            );
        

        $queued_txn = wp_remote_post($endpoint, $request_payment_confirmation);
        $queued_txn_body = wp_remote_retrieve_body( $queued_txn );
        $queued_txn_response = json_decode($queued_txn_body);
        $txn_code = isset( $queued_txn_response -> ResponseCode) ?  $queued_txn_response -> ResponseCode  : '';
        // exit();
        if ( ( !is_wp_error($queued_txn)) && ($txn_code === '0')) {
            $order = wc_get_order( $order_id );
            $order->payment_complete();
            // echo $queued_txn_response -> ResponseDescription;
            wp_safe_redirect( site_url().'/checkout/order-received/'.$order_id.'/?key='. $order_key);
            exit('Checked TXN');
        } else {
            var_dump($queued_txn_body);
        }
      }
    }
        

    

// add_action( 'wpforms_process', 'bidii_pay_check_transaction', 10, 3 );

add_action( 'admin_post_confirm_payment', 'bidii_pay_check_transaction' );

