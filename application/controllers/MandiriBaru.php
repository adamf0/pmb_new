<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MandiriBaru extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set('Asia/Jakarta');
        $this->load->library('mandiriencrypt');
        $this->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
        $this->ci = & get_instance();
    }

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
    
    
    public function auth(){
        try {
            // if(){ //panggil cache auth

            // } else{
                // Client ID dan Timestamp
                $clientId = "c61dd0b38c8c482f9fd3a2d5937a7854";
                $timestamp = gmdate("Y-m-d H:i:s") . "+07:00"; // Sesuaikan dengan format yyyy-mm-dd HH:mm:ssTZD
            
                // Path ke file private key dan passwordnya
                $privateKeyPath = FCPATH . 'private.key'; // Ganti dengan path file private key Anda
                $privateKeyPassword = 'm4nd1r1'; // Ganti dengan password kunci privat
            
                // Hasilkan tanda tangan
                $signature = $this->generateAccessTokenSignature($clientId, $timestamp, $privateKeyPath, $privateKeyPassword);

                // URL endpoint
                $url = 'https://api.bankmandiri.co.id/v1.0.8/access-token/b2b';

                // Header yang diperlukan
                $headers = [
                    "X-CLIENT-KEY: $clientId",
                    "X-TIMESTAMP: $timestamp",
                    "X-SIGNATURE: $signature",
                    "Content-Type: application/json",
                    "Accept: application/json"
                ];

                // Data untuk permintaan POST
                $data = [
                    "grantType" => "client_credentials"
                ];

                // Inisialisasi cURL
                $ch = curl_init($url);

                // Mengonfigurasi cURL dengan metode POST dan data JSON
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                // Menjalankan permintaan dan mendapatkan respons
                $response = curl_exec($ch);

                // Mengecek error pada cURL
                if (curl_errno($ch)) {
                    throw new Exception("[curl auth] ".curl_error($ch));
                } else {
                    echo $response;
                }

                // Menutup cURL
                curl_close($ch);
            // }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function inquiry(){
        try {
            $auth = $this->auth();

            // Input data
            $httpMethod = "POST";
            $endpointUrl = "/transfer-va/inquiry";
            $accessToken = $auth["accessToken"];
            $timestamp = gmdate("Y-m-d H:i:s") . "+07:00"; // Sesuaikan dengan format yyyy-mm-dd HH:mm:ssTZD
            $requestBody = json_encode([
                "partnerServiceId" => "   ".$this->ci->config->item('client_id_Mandiri'),
                "customerNo" => "81808087811437538",
                "virtualAccountNo" => "   "."81808087811437538",
                "trxDateInit" => $timestamp,
                "channelCode" => $this->ci->config->item('biller_code_open_Mandiri'),
                "language" => "ID",
                "amount" => [
                    "value" => "50000.00",
                    "currency" => "IDR",
                ],
                "inquiryRequestId" => "1027664600255163315933",
            ]);
            $clientSecret = "xabNYv8OUWJEmLgEmtQ0i8AvheNaao/Z5NEm+bawKwo=";

            // Hasilkan signature
            $signature = $this->generateTransactionSignature($httpMethod, $endpointUrl, $accessToken, $requestBody, $timestamp, $clientSecret);

            // Kirimkan header HTTP dalam permintaan
            $headers = [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json",
                "X-TIMESTAMP: $timestamp",
                "X-SIGNATURE: $signature",
                "X-PARTNER-ID: ".$this->ci->config->item('secret_key_Mandiri'),
                "X-EXTERNAL-ID: 7532300325114907378",
                "CHANNEL-ID: ".$this->ci->config->item('biller_code_open_Mandiri'),
            ];

            // Contoh penggunaan dengan cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.bankmandiri.co.id/v1.0/transfer-va/inquiry"); // Ganti dengan endpoint yang benar
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);

            // Kirim permintaan
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception("[curl inquiry] ".curl_error($ch));
            } else{
                echo $response;
                //proses simpan response
            }

            // Tutup cURL
            curl_close($ch);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function payment(){
        try {
            $auth = $this->auth();

            // Input data
            $httpMethod = "POST";
            $endpointUrl = "/transfer-va/payment";
            $accessToken = $auth["accessToken"];
            $timestamp = gmdate("Y-m-d H:i:s") . "+07:00"; // Sesuaikan dengan format yyyy-mm-dd HH:mm:ssTZD
            $requestBody = json_encode([
                "partnerServiceId" => "   ".$this->ci->config->item('client_id_Mandiri'),
                "customerNo" => "81808087811437538",
                "virtualAccountNo" => "81808087811437538",
                "virtualAccountName" => "ABCD 081278661234",
                "trxDateTime" => $timestamp,
                "channelCode" => $this->ci->config->item('biller_code_open_Mandiri'),
                "referenceNo" => "22032399164960011365",
                "hashedSourceAccountNo" => "90492f66133276d890873a68fc91e56f",
                "paidAmount" => [
                    "value" => "50000.00",
                    "currency" => "IDR"
                ],
                "paymentRequestId" => "1027664600255163315933",
                "paidBills" => "FFFFFF",
                "flagAdvise" => "N"
            ]);
            $clientSecret = "xabNYv8OUWJEmLgEmtQ0i8AvheNaao/Z5NEm+bawKwo=";

            // Hasilkan signature
            $signature = $this->generateTransactionSignature($httpMethod, $endpointUrl, $accessToken, $requestBody, $timestamp, $clientSecret);

            // Kirimkan header HTTP dalam permintaan
            $headers = [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json",
                "X-TIMESTAMP: $timestamp",
                "X-SIGNATURE: $signature",
                "X-PARTNER-ID: ".$this->ci->config->item('secret_key_Mandiri'),
                "X-EXTERNAL-ID: 7532300325114907378",
                "CHANNEL-ID: ".$this->ci->config->item('biller_code_open_Mandiri'),
            ];

            // Contoh penggunaan dengan cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.bankmandiri.co.id/v1.0/transfer-va/payment"); // Ganti dengan endpoint yang benar
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);

            // Kirim permintaan
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception("[curl payment] ".curl_error($ch));
            } else{
                echo $response;
                //proses simpan response
            }

            // Tutup cURL
            curl_close($ch);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
