<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MandiriSnap extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set('Asia/Jakarta');
        $this->load->library('mandiriencrypt');
        $this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
        
        $this->load->model('DataVA', 'mva');
        $this->load->model('ModelMandiriBaru', 'mdr');
        $this->load->model('ModelCrud', 'mcrud');
        $this->ci = & get_instance();
    }

    // public function auth(){
    //     $cache_key = "auth_response";

    //     try {
    //         $cached_data = $this->cache->get($cache_key);

    //         if($cached_data){ //panggil cache auth
    //             $this->cache->delete($cache_key);
    //             return json_decode($cached_data,true);
    //         } else{
    //             // Client ID dan Timestamp
    //             $clientId = "c61dd0b38c8c482f9fd3a2d5937a7854";
    //             $timestamp = gmdate("Y-m-d H:i:s") . "+07:00"; // Sesuaikan dengan format yyyy-mm-dd HH:mm:ssTZD
            
    //             // Path ke file private key dan passwordnya
    //             $privateKeyPath = FCPATH . 'private.key'; // Ganti dengan path file private key Anda
    //             $privateKeyPassword = 'm4nd1r1'; // Ganti dengan password kunci privat
            
    //             // Hasilkan tanda tangan
    //             $signature = $this->generateAccessTokenSignature($clientId, $timestamp, $privateKeyPath, $privateKeyPassword);

    //             // URL endpoint
    //             $url = 'https://api.bankmandiri.co.id/v1.0.8/access-token/b2b';

    //             // Header yang diperlukan
    //             $headers = [
    //                 "X-CLIENT-KEY: $clientId",
    //                 "X-TIMESTAMP: $timestamp",
    //                 "X-SIGNATURE: $signature",
    //                 "Content-Type: application/json",
    //                 "Accept: application/json"
    //             ];

    //             // Data untuk permintaan POST
    //             $data = [
    //                 "grantType" => "client_credentials"
    //             ];

    //             // Inisialisasi cURL
    //             $ch = curl_init($url);

    //             // Mengonfigurasi cURL dengan metode POST dan data JSON
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             curl_setopt($ch, CURLOPT_POST, true);
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    //             curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    //             // Menjalankan permintaan dan mendapatkan respons
    //             $response = curl_exec($ch);

    //             // Mengecek error pada cURL
    //             if (curl_errno($ch)) {
    //                 throw new Exception("[curl auth] ".curl_error($ch));
    //             } else {
    //                 $this->cache->save($cache_key, $response, 900); //asumsi $response itu string json, timeout 15 menit
    //                 return json_decode($response, true);
    //             }

    //             // Menutup cURL
    //             curl_close($ch);
    //         }
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    function generateAccessTokenSignature($clientId, $timestamp, $privateKeyPath, $privateKeyPassword) {
        // Format data yang akan ditandatangani
        $dataToSign = "$clientId|$timestamp";
    
        // Baca kunci privat dari file
        $key = file_get_contents($privateKeyPath);
        
        // Dekripsi kunci privat menggunakan password
        $privateKey = openssl_pkey_get_private($key, $privateKeyPassword);
        if (!$privateKey) {
            throw new Exception("Gagal membaca kunci privat. Pastikan file dan password benar.");
        }
    
        // Tanda tangani data dengan algoritma SHA256withRSA
        $signature = '';
        openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
        // Hasilkan signature dalam format Base64
        $base64Signature = base64_encode($signature);
    
        // Bebaskan sumber daya kunci privat
        openssl_free_key($privateKey);
    
        return $base64Signature;
    }
    function generateTransactionSignature($httpMethod, $endpointUrl, $accessToken, $requestBody, $timestamp, $clientSecret) {
        // Minify Request Body
        $minifiedRequestBody = json_encode(json_decode($requestBody));
    
        // Lowercase Hex Encode SHA-256 dari Request Body yang sudah diminify
        $hashedRequestBody = strtolower(hash('sha256', $minifiedRequestBody));
    
        // Gabungkan data sesuai format: HTTPMethod+":"+EndpointUrl+":"+AccessToken+":"+HashedRequestBody+":"+TimeStamp
        $data = "$httpMethod:$endpointUrl:$accessToken:$hashedRequestBody:$timestamp";
    
        // Hash data menggunakan HMAC_SHA512 dengan Client Secret sebagai key
        $hmacHash = hash_hmac('sha512', $data, $clientSecret, true);
    
        // Encode hasil hash ke dalam format Base64
        $signature = base64_encode($hmacHash);
    
        return $signature;
    }
    // function generateUniqueIdentifier() {
    //     // MMDDHHmmssSSS (Month, Day, Hour, Minute, Second, Milliseconds)
    //     $timestamp = date('mdHis'); // MMDDHHmmss
    //     $milliseconds = round(microtime(true) * 1000) % 1000; // SSS
    //     $formattedMilliseconds = str_pad($milliseconds, 3, '0', STR_PAD_LEFT);
        
    //     // Random 9-digit number
    //     $randomNumber = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        
    //     return $timestamp . $formattedMilliseconds . $randomNumber;
    // }

    function defaultVirtualAccountDataInquiry(){
        return [
            "inquiryStatus" => null,
            "inquiryReason" => [
                "english" => null,
                "indonesia" => null
            ],
            "partnerServiceId" => null,
            "customerNo" => null,
            "virtualAccountNo" => null,
            "virtualAccountName" => null,
            "inquiryRequestId" => null,
            "totalAmount" => [
                "value" => null,
                "currency" => null
            ],
            "feeAmount" => [
                "value" => null,
                "currency" => null
            ],
            "billDetails" => [
                [
                    "billCode" => null,
                    "billName" => null,
                    "billAmount" => [
                        "value" => null,
                        "currency" => null
                    ]
                ]
            ],
            "freeTexts" => [
                [
                    "english" => null,
                    "indonesia" => null
                ],
                [
                    "english" => null,
                    "indonesia" => null
                ],
                [
                    "english" => null,
                    "indonesia" => null
                ]
            ]
        ];
    }
    function defaultVirtualAccountDataPayment(){
        return [
            "partnerServiceId" => null,
            "customerNo" => null,
            "virtualAccountNo" => null,
            "virtualAccountName" => null,
            "trxDateTime" => null,
            "referenceNo" => null, //dari Bank Mandiri
            "paidAmount" => [
                "value" => null,
                "currency" => null
            ],
            "paymentRequestId" => null,
            "paidBills" => null,
            "flagAdvise" => null,
            "paymentFlagStatus" => null,
            "paymentFlagReason" => [
                "english" => null
            ]
        ];
    }
    function outputJsonInquiry($httpStatus, $code, $message, $virtualAccountData=[], $type="string") {
        if($type!='string'){
            http_response_code($httpStatus);
        }

        return json_encode([
            "responseCode" => $httpStatus.$code,
            "responseMessage" => $message,
            "virtualAccountData" => $virtualAccountData
        ]);
    }
    function outputJsonPayment($httpStatus, $code, $message, $virtualAccountData=[], $type="string") {
        if($type!='string'){
            http_response_code($httpStatus);
        }

        return json_encode([
            "responseCode" => $httpStatus.$code,
            "responseMessage" => $message,
            "virtualAccountData" => $virtualAccountData,
            "additionalInfo" => [
                "hashedSourceAccountNo" => "7e2abdb908c885c5fee1b21d37915e02",
                "channelCode" => null //dari Bank Mandiri
            ]
        ]);
    }
    public function saveLog($request_json, $response){
        $log_arr = array(
            'type'          => 'INQ',
            'phase'         => 'PENDAFTARAN',
            'request'       => $request_json,
            'response'      => $response,
            'insert_date'   => date('Y-m-d H:i:s')
        );

        $this->mcrud->input($log_arr, 'log_mandiri');
    }
    public function saveLogPay($request_json, $response){
        $log_arr = array(
            'type'          => 'PAY',
            'phase'         => 'PENDAFTARAN',
            'request'       => $request_json,
            'response'      => $response,
            'insert_date'   => date('Y-m-d H:i:s')
        );

        $this->mcrud->input($log_arr, 'log_mandiri');
    }

    public function inquiry(){
        try {
            header("Access-Control-Allow-Origin: *");
            header("Content-Type: application/json; charset=UTF-8");
            header("Access-Control-Allow-Methods: POST");
            header("Access-Control-Max-Age: 3600");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

            $start = microtime(true);
            $headers = apache_request_headers();
            $request = $this->input->raw_input_stream;

            $clientSecret = "xabNYv8OUWJEmLgEmtQ0i8AvheNaao/Z5NEm+bawKwo=";
            $timestamp = gmdate("Y-m-d H:i:s") . "+07:00";

            $requestArray = json_decode($request, true);
            if(empty($headers['Authorization'])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field Authorization";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-TIMESTAMP'])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field X-TIMESTAMP";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-SIGNATURE'])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field X-SIGNATURE";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-PARTNER-ID'])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field X-PARTNER-ID";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-EXTERNAL-ID'])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field X-EXTERNAL-ID";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['CHANNEL-ID'])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field CHANNEL-ID";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["partnerServiceId"])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field partnerServiceId";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["customerNo"])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field customerNo";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["virtualAccountNo"])){
                $httpCode = 404;
                $code = 2402;
                $message = "Invalid Mandatory Field virtualAccountNo";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["trxDateInit"])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field trxDateInit";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["channelCode"])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field channelCode";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["amount"])){
                $httpCode = 400;
                $code = 2402;
                $message = "Invalid Mandatory Field amount";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(!is_array($requestArray["amount"])){
                $httpCode = 400;
                $code = 2401;
                $message = "Invalid Field Format amount";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            }

            $checkExternalId = $this->mdr->isExternalIdExist($headers['X-EXTERNAL-ID']);
            if($headers['Authorization'] != $this->generateAccessTokenSignature($this->ci->config->item('clientID'), $timestamp, FCPATH . 'private.key', 'm4nd1r1') ){
                $httpCode = 400;
                $code = 2401;
                $message = "Invalid Field Format Authorization";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(DateTime::createFromFormat("Y-m-d H:i:sP", $headers['X-TIMESTAMP'])){
                $httpCode = 400;
                $code = 2401;
                $message = "Invalid Field Format X-TIMESTAMP";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if($headers['X-SIGNATURE'] != $this->generateTransactionSignature('POST', uri_string(), $headers['Authorization'], $request, $timestamp, $clientSecret)){
                $httpCode = 401;
                $code = 2401;
                $message = "Invalid Token (B2B)";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json"); 
            } else if($checkExternalId){
                $httpCode = 409;
                $code = 2400;
                $message = "Conflict";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } 
            // else if($headers['CHANNEL-ID'] != null){
                
            // }

            $dataVa = $this->mdr->get_billing_data($requestArray["virtualAccountNo"]);
            $expiredVa = false;
            $end = microtime(true);

            if($requestArray["partnerServiceId"] != "   ".$this->ci->config->item('partnerServiceId')){
                $httpCode = 400;
                $code = 2401;
                $message = "Invalid Field Format partnerServiceId";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(count($dataVa) == 0){
                $httpCode = 404;
                $code = 2412;
                $message = "invalid bill/virtual account not found";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if($expiredVa){
                $httpCode = 404;
                $code = 2519;
                $message = "invalid bill/virtual account expired";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if($requestArray["amount"]["value"]!=$dataVa['trx_amount']){
                $httpCode = 404;
                $code = 2413;
                $message = "Invalid amount";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if($dataVa["payment_flag"]!="0"){
                $httpCode = 404;
                $code = 2414;
                $message = "Paid bill";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(($end - $start) * 1000 > 14){
                $httpCode = 504;
                $code = 2400;
                $message = "Time out";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else{
                $param['id_payment_bank'] = "02";
                $data['json_billing_request'] = $request;
                $data['date_updated'] = date('Y-m-d H:i:s');

                if ($this->mdr->update_billing_data($data, $param)) {
                    $httpCode = 200;
                    $code = 2400;
                    $message = "Successful";
                    $virtualAccountData = [
                        "inquiryStatus" => "00",
                        "inquiryReason" => [
                            "english" => "Successful",
                            "indonesia" => "Sukses"
                        ],
                        "partnerServiceId" => $requestArray["partnerServiceId"],
                        "customerNo" => $requestArray["customerNo"],
                        "virtualAccountNo" => $requestArray["virtualAccountNo"],
                        "virtualAccountName" => $requestArray["virtualAccountNo"],
                        "inquiryRequestId" => null,
                        "totalAmount" => [
                            "value" => $requestArray["amount"]["value"],
                            "currency" => "IDR"
                        ],
                        "feeAmount" => [
                            "value" => "0.00",
                            "currency" => "IDR"
                        ],
                        "billDetails" => [
                            [
                                "billCode" => "01",
                                "billName" => $requestArray["virtualAccountNo"],
                                "billAmount" => [
                                    "value" => $requestArray["amount"]["value"],
                                    "currency" => "IDR"
                                ]
                            ]
                        ],
                        "freeTexts" => [
                            [
                                "english" => null,
                                "indonesia" => null
                            ],
                            [
                                "english" => null,
                                "indonesia" => null
                            ],
                            [
                                "english" => null,
                                "indonesia" => null
                            ]
                        ]
                    ];

                    $responString = $this->outputJsonInquiry($httpCode, $code, $message, $virtualAccountData);
                    
                    $this->saveLog($request, $responString);
                    return $this->outputJsonInquiry($httpCode, $code, $message, $virtualAccountData,"json");
                } else {
                    $httpCode = 500;
                    $code = 2400;
                    $message = "General error";
                    $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                    
                    $this->saveLog($request, $responString);
                    return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
                }
            } 
        } catch (\Exception $e) {
            $httpCode = 500;
            $code = 2401;
            $message = "Internal Server Error";
            $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
            
            $this->saveLog($request, $responString);
            return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
        }
    }
    
    public function payment(){
        try {
            header("Access-Control-Allow-Origin: *");
            header("Content-Type: application/json; charset=UTF-8");
            header("Access-Control-Allow-Methods: POST");
            header("Access-Control-Max-Age: 3600");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

            $start = microtime(true);
            $headers = apache_request_headers();
            $request = $this->input->raw_input_stream;

            $clientSecret = "xabNYv8OUWJEmLgEmtQ0i8AvheNaao/Z5NEm+bawKwo=";
            $timestamp = gmdate("Y-m-d H:i:s") . "+07:00";

            $requestArray = json_decode($request, true);
            if(empty($headers['Authorization'])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field Authorization";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($headers['X-TIMESTAMP'])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field X-TIMESTAMP";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($headers['X-SIGNATURE'])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field X-SIGNATURE";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($headers['X-PARTNER-ID'])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field X-PARTNER-ID";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($headers['X-EXTERNAL-ID'])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field X-EXTERNAL-ID";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($headers['CHANNEL-ID'])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field CHANNEL-ID";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["partnerServiceId"])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field partnerServiceId";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["customerNo"])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field customerNo";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["virtualAccountNo"])){
                $httpCode = 404;
                $code = 2502;
                $message = "Invalid Mandatory Field virtualAccountNo";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["virtualAccountName"])){
                $httpCode = 404;
                $code = 2502;
                $message = "Invalid Mandatory Field virtualAccountName";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["trxDateTime"])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field trxDateTime";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["channelCode"])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field channelCode";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["referenceNo"])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field referenceNo";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(empty($requestArray["paidAmount"])){
                $httpCode = 400;
                $code = 2502;
                $message = "Invalid Mandatory Field paidAmount";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(!is_array($requestArray["paidAmount"])){
                $httpCode = 400;
                $code = 2501;
                $message = "Invalid Field Format paidAmount";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            }

            $checkExternalId = $this->mdr->isExternalIdExist($headers['X-EXTERNAL-ID']);
            if($headers['Authorization'] != $this->generateAccessTokenSignature($this->ci->config->item('clientID'), $timestamp, FCPATH . 'private.key', 'm4nd1r1') ){
                $httpCode = 400;
                $code = 2501;
                $message = "Invalid Field Format Authorization";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(DateTime::createFromFormat("Y-m-d H:i:sP", $headers['X-TIMESTAMP'])){
                $httpCode = 400;
                $code = 2501;
                $message = "Invalid Field Format X-TIMESTAMP";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if($headers['X-SIGNATURE'] != $this->generateTransactionSignature('POST', uri_string(), $headers['Authorization'], $request, $timestamp, $clientSecret)){
                $httpCode = 401;
                $code = 2501;
                $message = "Invalid Token (B2B)";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json"); 
            } else if($checkExternalId){
                $httpCode = 409;
                $code = 2500;
                $message = "Conflict";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } 
            // else if($headers['CHANNEL-ID'] != null){
                
            // }

            $dataVa = $this->mdr->get_billing_data($requestArray["virtualAccountNo"]);
            $expiredVa = false;
            $end = microtime(true);

            if($requestArray["partnerServiceId"] != "   ".$this->ci->config->item('partnerServiceId')){
                $httpCode = 400;
                $code = 2501;
                $message = "Invalid Field Format partnerServiceId";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(count($dataVa) == 0){
                $httpCode = 404;
                $code = 2512;
                $message = "invalid bill/virtual account not found";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if($expiredVa){
                $httpCode = 404;
                $code = 2519;
                $message = "invalid bill/virtual account expired";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if($requestArray["paidAmount"]["value"]!=$dataVa['trx_amount']){
                $httpCode = 404;
                $code = 2513;
                $message = "Invalid amount";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if($dataVa["payment_flag"]!="0"){
                $httpCode = 404;
                $code = 2514;
                $message = "Paid bill";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else if(($end - $start) * 1000 > 14){
                $httpCode = 504;
                $code = 2500;
                $message = "Time out";
                $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment());
                
                $this->saveLogPay($request, $responString);
                return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataPayment(), "json");
            } else{
                $param['id_payment_bank'] = $dataVa['id_payment_bank'];
                $data['json_payment_request'] = $request;
                $data['json_payment_response'] = $dataVa['json_payment_response'];
                $data['date_updated'] = date('Y-m-d H:i:s');

                if($dataVa['trx_amount'] > $requestArray["paidAmount"]["value"]){
                    $data['payment_flag'] = 2;
                    $dataVa['trx_amount'] = $dataVa['trx_amount'] - $requestArray["paidAmount"]["value"];
                    $billing_new = $dataVa;
                    unset($billing_new['id_payment_bank']);

                    if ($this->mcrud->input($billing_new, 'payment_bank_baru')) {
                        $this->mdr->update_billing_data($data, $param);
                        $response = $dataVa['json_payment_response'];

                        $httpCode = 200;
                        $code = 2400;
                        $message = "Successful";
                        $virtualAccountData = [
                            "partnerServiceId" => $requestArray["partnerServiceId"],
                            "customerNo" => $requestArray["customerNo"],
                            "virtualAccountNo" => $requestArray["virtualAccountNo"],
                            "virtualAccountName" => $requestArray["virtualAccountName"],
                            "trxDateTime" => $requestArray["trxDateTime"],
                            "referenceNo" => $requestArray["referenceNo"], //dari Bank Mandiri
                            "paidAmount" => [
                                "value" => $requestArray["paidAmount"]["value"],
                                "currency" => "IDR"
                            ],
                            "paymentRequestId" => "7514571400257223828106", //?
                            "paidBills" => "FFFFFF",
                            "flagAdvise" => "N",
                            "paymentFlagStatus" => "00",
                            "paymentFlagReason" => [
                                "english" => "Success payment flag"
                            ]
                        ];

                        // $responString = $this->outputJsonPayment($httpCode, $code, $message, $virtualAccountData);
                        
                        $this->saveLogPay($request, $response);
                        return $this->outputJsonPayment($httpCode, $code, $message, $virtualAccountData,"json");
                    } else {
                        $httpCode = 500;
                        $code = 2500;
                        $message = "General error";
                        $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                        
                        $this->saveLogPay($request, $responString);
                        return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
                    }
                } else{
                    $data['payment_flag'] = 1;
                    $maxno     = $this->mva->get_max_no();
                    $idgel     = $this->mva->get_id_gel();

                    if ($this->mva->update_no_pendaftaran_by_id($dataVa['id_u_pmb'], $maxno['no_pendaftaran'], $idgel['id_m_gel'])) {
                        if ($this->mdr->update_billing_data($data, $param)) {
                            $response = $record['json_payment_response'];
    
                            $httpCode = 200;
                            $code = 2400;
                            $message = "Successful";
                            $virtualAccountData = [
                                "partnerServiceId" => $requestArray["partnerServiceId"],
                                "customerNo" => $requestArray["customerNo"],
                                "virtualAccountNo" => $requestArray["virtualAccountNo"],
                                "virtualAccountName" => $requestArray["virtualAccountName"],
                                "trxDateTime" => $requestArray["trxDateTime"],
                                "referenceNo" => $requestArray["referenceNo"], //dari Bank Mandiri
                                "paidAmount" => [
                                    "value" => $requestArray["paidAmount"]["value"],
                                    "currency" => "IDR"
                                ],
                                "paymentRequestId" => "7514571400257223828106", //?
                                "paidBills" => "FFFFFF",
                                "flagAdvise" => "N",
                                "paymentFlagStatus" => "00",
                                "paymentFlagReason" => [
                                    "english" => "Success payment flag"
                                ]
                            ];
    
                            // $responString = $this->outputJsonPayment($httpCode, $code, $message, $virtualAccountData);
                            
                            $this->saveLogPay($request, $response);
                            return $this->outputJsonPayment($httpCode, $code, $message, $virtualAccountData,"json");
                        } else {
                            $httpCode = 500;
                            $code = 2500;
                            $message = "General error";
                            $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                            
                            $this->saveLogPay($request, $responString);
                            return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
                        }
                    } else{
                        $httpCode = 500;
                        $code = 2500;
                        $message = "General error";
                        $responString = $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                        
                        $this->saveLogPay($request, $responString);
                        return $this->outputJsonPayment($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
                    }
                }
            }
            
        } catch (\Exception $e) {
            $httpCode = 500;
            $code = 2501;
            $message = "Internal Server Error";
            $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
            
            $this->saveLog($request, $responString);
            return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
        }
    }

    public function status(){
        try {
            header("Access-Control-Allow-Origin: *");
            header("Content-Type: application/json; charset=UTF-8");
            header("Access-Control-Allow-Methods: POST");
            header("Access-Control-Max-Age: 3600");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

            $start = microtime(true);
            $headers = apache_request_headers();
            $request = $this->input->raw_input_stream;

            $clientSecret = "xabNYv8OUWJEmLgEmtQ0i8AvheNaao/Z5NEm+bawKwo=";
            $timestamp = gmdate("Y-m-d H:i:s") . "+07:00";

            $requestArray = json_decode($request, true);
            if(empty($headers['Authorization'])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field Authorization";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-TIMESTAMP'])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field X-TIMESTAMP";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-SIGNATURE'])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field X-SIGNATURE";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-PARTNER-ID'])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field X-PARTNER-ID";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['X-EXTERNAL-ID'])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field X-EXTERNAL-ID";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($headers['CHANNEL-ID'])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field CHANNEL-ID";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["partnerServiceId"])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field partnerServiceId";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["customerNo"])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field customerNo";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["virtualAccountNo"])){
                $httpCode = 404;
                $code = 2602;
                $message = "Invalid Mandatory Field virtualAccountNo";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(empty($requestArray["inquiryRequestId"])){
                $httpCode = 400;
                $code = 2602;
                $message = "Invalid Mandatory Field inquiryRequestId";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } 

            $checkExternalId = $this->mdr->isExternalIdExist($headers['X-EXTERNAL-ID']);
            if($headers['Authorization'] != $this->generateAccessTokenSignature($this->ci->config->item('clientID'), $timestamp, FCPATH . 'private.key', 'm4nd1r1') ){
                $httpCode = 400;
                $code = 2601;
                $message = "Invalid Field Format Authorization";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(DateTime::createFromFormat("Y-m-d H:i:sP", $headers['X-TIMESTAMP'])){
                $httpCode = 400;
                $code = 2601;
                $message = "Invalid Field Format X-TIMESTAMP";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if($headers['X-SIGNATURE'] != $this->generateTransactionSignature('POST', uri_string(), $headers['Authorization'], $request, $timestamp, $clientSecret)){
                $httpCode = 401;
                $code = 2601;
                $message = "Invalid Token (B2B)";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json"); 
            } else if($checkExternalId){
                $httpCode = 409;
                $code = 2600;
                $message = "Conflict";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } 
            // else if($headers['CHANNEL-ID'] != null){
                
            // }

            $dataVa = $this->mdr->get_billing($requestArray["virtualAccountNo"],$requestArray["inquiryRequestId"]);
            $end = microtime(true);

            if($requestArray["partnerServiceId"] != "   ".$this->ci->config->item('partnerServiceId')){
                $httpCode = 400;
                $code = 2601;
                $message = "Invalid Field Format partnerServiceId";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } else if(count($dataVa) == 0){
                $httpCode = 404;
                $code = 2601;
                $message = "Transaction not found";
                $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
                
                $this->saveLog($request, $responString);
                return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
            } 

            return json_encode($dataVa["json_payment_response"]??[]);
        } catch (\Exception $e) {
            $httpCode = 500;
            $code = 2601;
            $message = "Internal Server Error";
            $responString = $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry());
            
            $this->saveLog($request, $responString);
            return $this->outputJsonInquiry($httpCode, $code, $message, $this->defaultVirtualAccountDataInquiry(), "json");
        }
    }
}
