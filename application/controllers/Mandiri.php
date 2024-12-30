<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mandiri extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set('Asia/Jakarta');

        $this->load->model('Datava_model', 'mva');
        $this->load->model('Model_mandiri', 'mdr');
        $this->load->model('Model_crud', 'mcrud');
        $this->load->library('mandiriencrypt');
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

    public function inquiry()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        // get headers request 
        $headers = apache_request_headers();

        // get body request
        $request = $this->input->raw_input_stream;
        // $request = file_get_contents("php://input");
        $request_arr = json_decode($request, true);
        $request_json = json_encode($request_arr);

        if($headers['cid'] != $this->config->item('client_id_Mandiri')){
            $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Client ID"}}}';
            $this->saveLog($request_json, $response);
            return $response;
        }
        if($headers['signature'] != $this->mandiriencrypt->checksum_inquiry($request_json)){
            $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Signature"}}}';
            $this->saveLog($request_json, $response);
            return $response;
        }
        if(!is_array($request_arr['InquiryRequest'])){
            $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Format"}}}';
            $this->saveLog($request_json, $response);
            return $response;
        }
        if(!$this->mdr->check_connection()){
            $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"91","statusDescription":"Link Down"}}}';
            $this->saveLog($request_json, $response);
            return $response;
        }

        $start = microtime(true);
        $record = $this->mdr->get_billing_data($request_arr['InquiryRequest']['billKey1']);
        if(count($record) == 0){
            $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B5","statusDescription":"Bill Not Found"}}}';
            $this->saveLog($request_json, $response);
            return $response;
        }
        if($record['payment_flag'] != 0){
            $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B8","statusDescription":"Bill Already Paid"}}}';
            $this->saveLog($request_json, $response);
            return $response;
        }

        if ($record['trx_id'] . '402' == substr($request_arr['InquiryRequest']['billKey1'], -9)) {
            $param['id_payment_bank'] = $record['id_payment_bank'];

            $data['json_billing_request'] = $request_json;
            $data['date_updated'] = date('Y-m-d H:i:s');

            $finish = microtime(true);
            if (($finish - $start) * 1000 > 14) {
                $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
            } else {
                if ($this->mdr->update_billing_data($data, $param)) {
                    $response = $record['json_billing_response'];
                } else {
                    $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
                }
            }

            $this->saveLog($request_json, $response);
            return $response;
        } else {
            if ($request_arr['InquiryRequest']['billKey2'] == $record['trx_amount']) {
                $param['id_payment_bank'] = $record['id_payment_bank'];

                $data['json_billing_request'] = $request_json;
                $data['date_updated'] = date('Y-m-d H:i:s');

                $finish = microtime(true);
                if (($finish - $start) * 1000 > 14) {
                    $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
                } else {
                    if ($this->mdr->update_billing_data($data, $param)) {
                        $response = $record['json_billing_response'];
                    } else {
                        $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
                    }
                }
            } else {
                $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Bill Amount Does Not Match"}}}';
            }

            $this->saveLog($request_json, $response);
            return $response;
        }

    }
    
    // old
    // public function inquiry()
    // {
    //     header("Access-Control-Allow-Origin: *");
    //     header("Content-Type: application/json; charset=UTF-8");
    //     header("Access-Control-Allow-Methods: POST");
    //     header("Access-Control-Max-Age: 3600");
    //     header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    //     // get headers request 
    //     $headers = apache_request_headers();

    //     // get body request
    //     $request = $this->input->raw_input_stream;
    //     // $request = file_get_contents("php://input");
    //     $request_arr = json_decode($request, true);
    //     $request_json = json_encode($request_arr);

    //     if ($headers['cid'] == $this->config->item('client_id_Mandiri')) {
    //         if ($headers['signature'] == $this->mandiriencrypt->checksum_inquiry($request_json)) {
    //             if (is_array($request_arr['InquiryRequest'])) {
    //                 if ($this->mdr->check_connection() == TRUE) {
    //                     $start = microtime(true);
    //                     $record = $this->mdr->get_billing_data($request_arr['InquiryRequest']['billKey1']);
    //                     if (count($record) > 0) {
    //                         if ($record['payment_flag'] == 0) {
    //                             if ($record['trx_id'] . '402' == substr($request_arr['InquiryRequest']['billKey1'], -9)) {
    //                                 $param['id_payment_bank'] = $record['id_payment_bank'];

    //                                 $data['json_billing_request'] = $request_json;
    //                                 $data['date_updated'] = date('Y-m-d H:i:s');

    //                                 $finish = microtime(true);
    //                                 if (($finish - $start) * 1000 > 14) {
    //                                     $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
    //                                 } else {
    //                                     if ($this->mdr->update_billing_data($data, $param)) {
    //                                         $response = $record['json_billing_response'];
    //                                     } else {
    //                                         $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
    //                                     }
    //                                 }
    //                             } else {
    //                                 if ($request_arr['InquiryRequest']['billKey2'] == $record['trx_amount']) {
    //                                     $param['id_payment_bank'] = $record['id_payment_bank'];

    //                                     $data['json_billing_request'] = $request_json;
    //                                     $data['date_updated'] = date('Y-m-d H:i:s');

    //                                     $finish = microtime(true);
    //                                     if (($finish - $start) * 1000 > 14) {
    //                                         $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
    //                                     } else {
    //                                         if ($this->mdr->update_billing_data($data, $param)) {
    //                                             $response = $record['json_billing_response'];
    //                                         } else {
    //                                             $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
    //                                         }
    //                                     }
    //                                 } else {
    //                                     $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Bill Amount Does Not Match"}}}';
    //                                 }
    //                             }
    //                         } else {
    //                             $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B8","statusDescription":"Bill Already Paid"}}}';
    //                         }
    //                     } else {
    //                         $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B5","statusDescription":"Bill Not Found"}}}';
    //                     }
    //                 } else {
    //                     $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"91","statusDescription":"Link Down"}}}';
    //                 }
    //             } else {
    //                 $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Format"}}}';
    //             }
    //         } else {
    //             $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Signature"}}}';
    //         }
    //     } else {
    //         $response = '{"InquiryResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Client ID"}}}';
    //     }

    //     echo $response;

    //     $log_arr = array(
    //         'type'          => 'INQ',
    //         'phase'         => 'PENDAFTARAN',
    //         'request'       => $request_json,
    //         'response'      => $response,
    //         'insert_date'   => date('Y-m-d H:i:s')
    //     );

    //     $this->mcrud->input($log_arr, 'log_mandiri');
    // }

    public function payment()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        // get headers request 
        $headers = apache_request_headers();

        // get body request
        $request = $this->input->raw_input_stream; //file_get_contents("php://input");
        $request_arr = json_decode($request, true);
        $request_json = json_encode($request_arr);

        if($headers['cid'] != $this->config->item('client_id_Mandiri')){
            $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Client ID"}}}';
            $this->saveLogPay($request_json, $response);
            return $response;
        }
        if($headers['signature'] != $this->mandiriencrypt->checksum_inquiry($request_json)){
            $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Signature"}}}';
            $this->saveLogPay($request_json, $response);
            return $response;
        }
        if(!is_array($request_arr['paymentRequest'])){
            $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Format"}}}';
            $this->saveLogPay($request_json, $response);
            return $response;
        }
        if(!$this->mdr->check_connection()){
            $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"91","statusDescription":"Link Down"}}}';
            $this->saveLogPay($request_json, $response);
            return $response;
        }

        $start = microtime(true);
        $record = $this->mdr->get_billing_data($request_arr['paymentRequest']['billKey1']);
        if(count($record) == 0){
            $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B5","statusDescription":"Bill Not Found"}}}';
            $this->saveLogPay($request_json, $response);
            return $response;
        }
        if($record['payment_flag'] != 0){
            $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B8","statusDescription":"Bill Already Paid"}}}';
            $this->saveLogPay($request_json, $response);
            return $response;
        }

        if ($record['trx_id'] . '402' == substr($request_arr['paymentRequest']['billKey1'], -9)) {
            $finish = microtime(true);
            if (($finish - $start) * 1000 > 26) {
                $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
            } else {
                $param['id_payment_bank'] = $record['id_payment_bank'];

                $data['json_payment_request'] = $request_json;
                $data['json_payment_response'] = $record['json_payment_response'];
                $data['date_updated'] = date('Y-m-d H:i:s');

                if ($record['trx_amount'] > $request_arr['paymentRequest']['paymentAmount']) {
                    $data['payment_flag'] = 2;

                    $record['trx_amount'] = $record['trx_amount'] - $request_arr['paymentRequest']['paymentAmount'];
                    $billing_new = $record;
                    unset($billing_new['id_payment_bank']);

                    if ($this->mcrud->input($billing_new, 'payment_bank')) {
                        $this->mdr->update_billing_data($data, $param);
                        $response = $record['json_payment_response'];
                    } else {
                        $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
                    }
                } else {
                    $data['payment_flag'] = 1;

                    if ($this->mdr->update_billing_data($data, $param)) {
                        $response = $record['json_payment_response'];
                    } else {
                        $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
                    }
                }
            }

            $this->saveLogPay($request_json, $response);
            return $response;
        } else {
            if ($request_arr['paymentRequest']['paymentAmount'] == $record['trx_amount']) {
                $maxno     = $this->mva->get_max_no();
                $idgel     = $this->mva->get_id_gel();
                $finish = microtime(true);
                if (($finish - $start) * 1000 > 26) {
                    $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
                } else {
                    if ($this->mva->update_no_pendaftaran_by_id($record['id_u_pmb'], $maxno['no_pendaftaran'], $idgel['id_m_gel'])) {
                        $param['id_payment_bank'] = $record['id_payment_bank'];

                        $data['payment_flag'] = 1;
                        $data['json_payment_request'] = $request_json;
                        $data['date_updated'] = date('Y-m-d H:i:s');

                        $this->mdr->update_billing_data($data, $param);
                        $response = $record['json_payment_response'];
                    } else {
                        $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
                    }
                }
            } else {
                $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Bill Amount Does Not Match"}}}';
            }

            $this->saveLogPay($request_json, $response);
            return $response;
        }

    }
    
    //old
    // public function payment()
    // {
    //     header("Access-Control-Allow-Origin: *");
    //     header("Content-Type: application/json; charset=UTF-8");
    //     header("Access-Control-Allow-Methods: POST");
    //     header("Access-Control-Max-Age: 3600");
    //     header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    //     // get headers request 
    //     $headers = apache_request_headers();

    //     // get body request
    //     $request = $this->input->raw_input_stream; //file_get_contents("php://input");
    //     $request_arr = json_decode($request, true);
    //     $request_json = json_encode($request_arr);

    //     if ($headers['cid'] == $this->config->item('client_id_Mandiri')) {
    //         if ($headers['signature'] == $this->mandiriencrypt->checksum_inquiry($request_json)) {
    //             if (is_array($request_arr['paymentRequest'])) {
    //                 if ($this->mdr->check_connection() == TRUE) {
    //                     $start = microtime(true);
    //                     $record = $this->mdr->get_billing_data($request_arr['paymentRequest']['billKey1']);
    //                     if (count($record) > 0) {
    //                         if ($record['payment_flag'] == 0) {
    //                             if ($record['trx_id'] . '402' == substr($request_arr['paymentRequest']['billKey1'], -9)) {
    //                                 $finish = microtime(true);
    //                                 if (($finish - $start) * 1000 > 26) {
    //                                     $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
    //                                 } else {
    //                                     $param['id_payment_bank'] = $record['id_payment_bank'];

    //                                     $data['json_payment_request'] = $request_json;
    //                                     $data['json_payment_response'] = $record['json_payment_response'];
    //                                     $data['date_updated'] = date('Y-m-d H:i:s');

    //                                     if ($record['trx_amount'] > $request_arr['paymentRequest']['paymentAmount']) {
    //                                         $data['payment_flag'] = 2;

    //                                         $record['trx_amount'] = $record['trx_amount'] - $request_arr['paymentRequest']['paymentAmount'];
    //                                         $billing_new = $record;
    //                                         unset($billing_new['id_payment_bank']);

    //                                         if ($this->mcrud->input($billing_new, 'payment_bank')) {
    //                                             $this->mdr->update_billing_data($data, $param);
    //                                             $response = $record['json_payment_response'];
    //                                         } else {
    //                                             $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
    //                                         }
    //                                     } else {
    //                                         $data['payment_flag'] = 1;

    //                                         if ($this->mdr->update_billing_data($data, $param)) {
    //                                             $response = $record['json_payment_response'];
    //                                         } else {
    //                                             $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
    //                                         }
    //                                     }
    //                                 }
    //                             } else {
    //                                 if ($request_arr['paymentRequest']['paymentAmount'] == $record['trx_amount']) {
    //                                     $maxno     = $this->mva->get_max_no();
    //                                     $idgel     = $this->mva->get_id_gel();
    //                                     $finish = microtime(true);
    //                                     if (($finish - $start) * 1000 > 26) {
    //                                         $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"89","statusDescription":"Time Out"}}}';
    //                                     } else {
    //                                         if ($this->mva->update_no_pendaftaran_by_id($record['id_u_pmb'], $maxno['no_pendaftaran'], $idgel['id_m_gel'])) {
    //                                             $param['id_payment_bank'] = $record['id_payment_bank'];

    //                                             $data['payment_flag'] = 1;
    //                                             $data['json_payment_request'] = $request_json;
    //                                             $data['date_updated'] = date('Y-m-d H:i:s');

    //                                             $this->mdr->update_billing_data($data, $param);
    //                                             $response = $record['json_payment_response'];
    //                                         } else {
    //                                             $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"87","statusDescription":"Provider Database Problem"}}}';
    //                                         }
    //                                     }
    //                                 } else {
    //                                     $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Bill Amount Does Not Match"}}}';
    //                                 }
    //                             }
    //                         } else {
    //                             $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B8","statusDescription":"Bill Already Paid"}}}';
    //                         }
    //                     } else {
    //                         $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"B5","statusDescription":"Bill Not Found"}}}';
    //                     }
    //                 } else {
    //                     $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"91","statusDescription":"Link Down"}}}';
    //                 }
    //             } else {
    //                 $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Format"}}}';
    //             }
    //         } else {
    //             $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Signature"}}}';
    //         }
    //     } else {
    //         $response = '{"paymentResponse":{"currency":"360","billInfo1":null,"billInfo2":null,"billInfo3":null,"billInfo4":null,"billInfo5":null,"billInfo6":null,"billInfo7":null,"billInfo8":null,"billInfo9":null,"billInfo10":null,"billInfo11":null,"billInfo12":null,"billInfo13":null,"billInfo14":null,"billInfo15":null,"billInfo16":null,"billInfo17":null,"billInfo18":null,"billInfo19":null,"billInfo21":null,"billInfo22":null,"billInfo23":null,"billInfo24":null,"billInfo25":null,"status":{"isError":"1","errorCode":"01","statusDescription":"Invalid Client ID"}}}';
    //     }

    //     echo $response;

    //     $log_arr = array(
    //         'type'          => 'PAY',
    //         'phase'         => 'PENDAFTARAN',
    //         'request'       => $request_json,
    //         'response'      => $response,
    //         'insert_date'   => date('Y-m-d H:i:s')
    //     );

    //     $this->mcrud->input($log_arr, 'log_mandiri');
    // }
}
