<?php

class Model_mandiri extends CI_Model
{
    public function __construct()
    {
		parent::__construct();
    }

    public function check_connection()
    {

        if ($this->load->database()) {
            return FALSE;
        }
        return TRUE;
    }

    public function get_billing_data($virtual_account)
    {
        $query = $this->db->select('id_payment_bank,id_u_pmb,payment_flag,trx_id,trx_amount,json_billing_response,student_va,student_name,json_billing_request,json_payment_request,json_payment_response')->get_where('payment_bank', array('selected_bank' => '02', 'student_va' => $virtual_account, 'payment_flag' => 0));
        //$query = $this->db->get_where('payment_bank', array('student_va' => $virtual_account, 'payment_flag' => 0));

        $result = $query->row_array();

        return $result;
    }

    public function update_billing_data($data,$param)
    {
        $this->db->set($data);
        $this->db->where($param);
        $this->db->update('payment_bank');

        return ($this->db->affected_rows() > 0);
    }

}