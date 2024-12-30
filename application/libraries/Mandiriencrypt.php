<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mandiriencrypt
{
    private $client_id;
    private $secret_key;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->client_id = $this->ci->config->item('client_id_Mandiri');
        $this->secret_key = $this->ci->config->item('secret_key_Mandiri');
    }
    
    public function checksum_inquiry($payload)
    {
        $data = $this->client_id.":".$payload.":".$this->secret_key;
        
        $result = strtoupper(hash_hmac('sha512',$data,$this->secret_key));

        return $result;
    }

    public function checksum_payment($payload)
    {
        $data = $this->client_id.":".$payload.":".$this->secret_key;
        
        $result = strtoupper(hash_hmac('sha512',$data,$this->secret_key));

        return $result;
    }
}