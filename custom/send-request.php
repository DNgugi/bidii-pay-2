<?php

function bidii_pay_process_payment($order_id){
    global $woocommerce;
    $order = new WC_Order( $order_id );
    if($_POST['payment_method'] != 'bidii_pay_mpesa'){
        return;
    }
    
    if( !isset($_POST['mobile']) || empty($_POST['mobile']) ){
        wc_add_notice( __( 'Please add your mobile number', 'bidii_pay' ), 'error' );
    }
    
    $_POST['mobile'] = '0713749580';
    if( isset($_POST['mobile'])){
        // exit('fdgsdfgsdgs');
        $mobile=$_POST['mobile'];
        $amount = '1';
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
        
        $ch = curl_init($access_token);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic '.$credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $json_response = json_decode($response);
        $token = isset( $json_response -> access_token) ?  $json_response -> access_token  : '';
        $timestamp = date("YmdHis");
        $password  = base64_encode($config['BusinessShortCode'] . "" . $config['passkey'] ."". $timestamp);
        
        
        //Start structuring call to express api
        $request_data = json_encode(array( 
            "BusinessShortCode" => $config['BusinessShortCode'],
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => $config['TransactionType'],
            "Amount" => $amount,
            "PartyA" => $mobile,
            "PartyB" => $config['BusinessShortCode'],
            "PhoneNumber" => $mobile,
            "CallBackURL" => $config['CallBackURL'],
            "AccountReference" => $config['AccountReference'],
            "TransactionDesc" => $config['TransactionDesc']
        )); 
        
        $endpoint = ($config['env'] == "live") ? "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest" : "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest"; 
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $mpesa_response     = curl_exec($ch);
        echo $mpesa_response;
        curl_close($ch);
        exit('reached mo');
        // $mpesa_json = json_decode($mpesa_response);
    
    echo $mpesa_response;
    $stk = $mpesa_json -> ResponseCode;
    
    
        if($stk === '0'){
            echo $mpesa_json -> CustomerMessage;
        } else {
            echo $mpesa_json -> errorMessage;
        }
   }
}

add_action('woocommerce_checkout_process', 'bidii_pay_process_payment');



function bidii_pay_check_transaction($fields, $entry, $form_data){

    //  if ( absint( $form_data[ 'id' ] ) !== 2014 ) {
    //     return $fields;
    // }
    // check the field ID 4 to see if it's empty and if it is, run the error    
    if(! empty( $fields[1][ 'value' ]) ) {
        /*Call function with these configurations*/
            $env="sandbox";
            $type = 4;
            $shortcode = '600996'; 
            $key     = "6fSidiQK1v1f9sJG9m8Tzbs3SVTPgYfW";//Enter your consumer key here
            $secret= "3hosoK1vkgnXv80u"; //Enter your consumer secret here
            $initiatorName = "testapi";
            $initiatorPassword = "Safaricom999!*!";
            $results_url = "http://localhost/wordpress/wc-api/success"; //Endpoint to receive results Body
            $timeout_url = "http://localhost/wordpress/wc-api/timeout"; //Endpoint to to go to on timeout
        /*End  configurations*/

        /*End transaction code validation*/

            $transactionID = $form_data['id'][1]; 
            //$transactionID = "OEI2AK4Q16";
            $command = "TransactionStatusQuery";
            $remarks = "Transaction Status Query"; 
            $occasion = "Transaction Status Query";
            $callback = null ;

    
            $access_token = ($env == "live") ? "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" : "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; 
            $credentials = base64_encode($key . ':' . $secret); 
            
            $ch = curl_init($access_token);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $credentials]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response); 

            echo $result->{'access_token'};
            
            $token = isset($result->access_token) ? $result->access_token : "N/A";

            $publicKey = file_get_contents(__DIR__ . "/mpesa_public_cert.cer"); 
            $isvalid = openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING); 
            $password = base64_encode($encrypted);

                //echo $token;


        $curl_post_data = array(   
            "Initiator" => $initiatorName, 
            "SecurityCredential" => $password, 
            "CommandID" => $command, 
            "TransactionID" => $transactionID, 
            "PartyA" => $shortcode, 
            "IdentifierType" => $type, 
            "ResultURL" => $results_url, 
            "QueueTimeOutURL" => $timeout_url, 
            "Remarks" => $remarks, 
            "Occasion" => $occasion,
        ); 

        $data_string = json_encode($curl_post_data);

        //echo $data_string;

        $endpoint = ($env == "live") ? "https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query" : "https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query"; 

        $ch2 = curl_init($endpoint);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer euRTfIk5MpTc9UoHb9Nv0YFYOn3H',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch2, CURLOPT_POST, 1);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        $response     = curl_exec($ch2);
        curl_close($ch2);
        echo $response;

        $result = json_decode($response); 
        
        $verified = $result -> ResponseCode;

        if($verified === "0"){
                echo $verified;
            } else {
            wpforms()->process->errors[ $form_data[ 'id' ] ] [ '1' ] = esc_html__( 'Some error occurred.', 'bidii_pay' );
        }
    }
}

add_action( 'wpforms_process', 'bidii_pay_check_transaction', 10, 3 );