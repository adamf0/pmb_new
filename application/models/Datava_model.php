<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Datava_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    public function get_user($id_u_pmb)
    {
        $this->db->select('u_pmb.no_reg, u_pmb.no_pendaftaran, u_pribadi.npm, user.username as customer_email, u_pribadi.nama_lengkap as customer_name, u_pribadi.no_hp as customer_phone');
        $this->db->from('u_pmb');
        $this->db->join('user', 'user.id_user = u_pmb.id_user');
        $this->db->join('u_pribadi', 'u_pribadi.id_u_pmb = u_pmb.id_u_pmb');
        $this->db->where('u_pmb.id_u_pmb', $id_u_pmb);
        $query = $this->db->get();

        return $query->row_array();
    }

    public function get_mapping($kode_prodi)
    {
        $this->db->select('m_program_studi_mapping.*,m_prodi.kode_fak,m_prodi.jenjang');
        $this->db->from('m_program_studi_mapping');
        $this->db->join('m_prodi', 'm_program_studi_mapping.kode_prodi = m_prodi.kode_prodi');
        $this->db->where('m_program_studi_mapping.kode_prodi', $kode_prodi);
        $query = $this->db->get();

        return $query->row_array();
    }

    public function get_biaya($kode_prodi, $id_jalur, $kode_trx = '', $id_gel = '', $kode_shift = '', $ta = "")
    {
        $this->db->select(
            'm_biaya.pembayaran as trx_amount, 
           m_biaya.kode_transaksi as trx_code, 
           case when m_biaya.kode_transaksi = "401" then "Daftar" else m_ket.keterangan end as trx_desc, 
           m_biaya.tahun_ta,
           m_gel.gel_tutup as exp_date,
           m_gel.registrasi,
           m_gel.akhir_diploma,
           m_gel.akhir_pasca,
           m_gel.akhir_spp,
           m_gel.akhir_sks,
           m_prodi_inisial.nama_inisial',
            FALSE
        );
        $this->db->from('m_biaya');
        $this->db->join('m_ket', 'm_ket.kode_transaksi = m_biaya.kode_transaksi');
        $this->db->join('m_ta', 'm_ta.tahun_ta = m_biaya.tahun_ta');
        $this->db->join('m_gel', 'm_gel.id_m_gel = m_biaya.id_m_gel');
        $this->db->join('m_prodi_inisial', 'm_prodi_inisial.kode_prodi = m_biaya.kode_prodi');
        $this->db->where('m_biaya.kode_prodi', $kode_prodi);
        $this->db->where('m_biaya.id_jalur', $id_jalur);
        if ($ta == "") {
            $this->db->where('m_ta.status', "BUKA");
        }
        // if(!in_array($this->session->level, ["superadmin", "admin"])){
        //     $this->db->where('m_ta.status', "BUKA");
        // }
        // if($this->session->id_user == 45641){
        //     $this->db->where('m_ta.status', "BUKA");
        // }
        if ($kode_trx != '')
            $this->db->where('m_biaya.kode_transaksi', $kode_trx);
        if ($kode_shift != '')
            $this->db->where('m_biaya.shift_biaya', $kode_shift);

        // if (in_array($id_jalur, ['1', '6', '7', '5', '4'])) {
        if ($id_gel != 0) {
            $this->db->where('m_gel.id_m_gel', $id_gel);
        } else {
            $this->db->where('m_gel.gel_buka <=', 'curdate()', FALSE);
            $this->db->where('m_gel.gel_tutup >=', 'curdate()', FALSE);
        }
        // } else {
        //     $this->db->where('m_gel.nama_gel', '1');
        // }
        $query = $this->db->get();

        // if ($this->session->id_user == 84887) {
        //     print_r($this->db->last_query());
        //     print_r($query->row_array());
        // }
        return $query->row_array();
    }

    public function get_biaya_multiple($kode_prodi, $id_jalur, $kode_trx = array(), $id_gel = NULL, $id_ta = "")
    {
        $this->db->select(
            'm_biaya.pembayaran as trx_amount, 
            m_biaya.kode_transaksi as trx_code, 
            case when m_biaya.kode_transaksi = "401" then "Daftar" else m_ket.keterangan end as trx_desc, 
            m_biaya.tahun_ta,
            m_gel.gel_tutup as exp_date,
            m_gel.registrasi,
            m_gel.akhir_diploma,
            m_gel.akhir_pasca,
            m_gel.akhir_spp,
            m_gel.akhir_sks,
            m_prodi_inisial.nama_inisial',
            FALSE
        );
        $this->db->from('m_biaya');
        $this->db->join('m_ket', 'm_ket.kode_transaksi = m_biaya.kode_transaksi');
        $this->db->join('m_ta', 'm_ta.tahun_ta = m_biaya.tahun_ta');
        $this->db->join('m_gel', 'm_gel.id_m_gel = m_biaya.id_m_gel');
        $this->db->join('m_prodi_inisial', 'm_prodi_inisial.kode_prodi = m_biaya.kode_prodi');
        $this->db->where('m_biaya.kode_prodi', $kode_prodi);
        $this->db->where('m_biaya.id_jalur', $id_jalur);
        if (empty($id_gel)) {
            if (!in_array($this->session->level, ["superadmin", "admin"])) {
                $this->db->where('m_ta.status', "BUKA");
            }
        }
        if (!empty($kode_trx)) {
            $this->db->where_in('m_biaya.kode_transaksi', $kode_trx);
        }
        if (!empty($id_gel)) {
            $this->db->where('m_gel.id_m_gel', $id_gel);
        } else if ($id_jalur == '1' or $id_jalur == '6') {
            $this->db->where('m_gel.gel_buka <=', 'curdate()', FALSE);
            $this->db->where('m_gel.gel_tutup >=', 'curdate()', FALSE);
        } else {
            $this->db->where('m_ta.status', "BUKA");
            $this->db->where('m_gel.nama_gel', '1');
        }
        $query = $this->db->get();

        return $query->result_array();
    }

    public function get_biaya_sks($kode_prodi, $id_jalur, $kode_shift, $id_gel = null)
    {
        $this->db->select(
            '(m_sks.jml_sks*m_sks.satuan_biaya) AS trx_amount,
            "101" AS trx_code,
            "SKS" AS trx_desc,
            m_sks.tahun_ta,
            m_gel.gel_tutup as exp_date,
            m_gel.registrasi,
            m_gel.akhir_diploma,
            m_gel.akhir_pasca,
            m_gel.akhir_spp,
            m_gel.akhir_sks,
            m_prodi_inisial.nama_inisial',
            FALSE
        );
        $this->db->from('m_sks');
        $this->db->join('m_ta', 'm_ta.tahun_ta = m_sks.tahun_ta');
        $this->db->join('m_gel', 'm_gel.id_ta = m_ta.id_m_ta');
        $this->db->join('m_prodi_inisial', 'm_prodi_inisial.kode_prodi = m_sks.kode_prodi');
        $this->db->where('m_sks.kode_prodi', $kode_prodi);
        $this->db->where('m_sks.shift', $kode_shift);
        if ($id_gel == null) {
            $this->db->where('m_ta.status', "BUKA");
        } else {
            $this->db->where('m_gel.id_m_gel', $id_gel);
        }
        // if (!in_array($this->session->level, ["superadmin", "admin"])) {
        //     $this->db->where('m_ta.status', "BUKA");
        // }
        // if ($id_jalur == '1' or $id_jalur == '6') {
        //     $this->db->where('m_gel.gel_buka <=', 'curdate()', FALSE);
        //     $this->db->where('m_gel.gel_tutup >=', 'curdate()', FALSE);
        // } else {
        // }
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_profile_by_id($param)
    {
        $this->db->select('*');
        $this->db->from('vw_mahasiswa_profile');
        if (strlen($param) == 8)
            $this->db->where('no_reg', $param);
        else
            $this->db->where('no_pendaftaran', $param);

        $query = $this->db->get();

        return $query->row_array();
    }

    public function get_billing_data($virtual_account)
    {
        $query = $this->db->get_where('payment_bank', array('student_va' => $virtual_account, 'payment_flag' => 0));

        $result = $query->row_array();

        return $result;
    }

    public function get_npm($id_u_pmb)
    {
        $this->db->where('id_u_pmb', $id_u_pmb);
        return $this->db->get('u_pribadi')->row_array();
    }

    public function get_max_no()
    {
        $this->db->select('CONCAT(DATE_FORMAT(CURDATE(),"%y"),LPAD(RIGHT((MAX(no_pendaftaran)+1),4),4,0)) as no_pendaftaran', FALSE);
        $this->db->from('u_pmb');
        $query = $this->db->get();

        return $query->row_array();
    }

    public function get_id_gel()
    {
        $this->db->select('id_m_gel');
        $this->db->from('m_gel');
        $this->db->where('gel_buka <=', 'curdate()', FALSE);
        $this->db->where('gel_tutup >=', 'curdate()', FALSE);

        $query = $this->db->get();

        return $query->row_array();
    }

    public function get_max_npm($billKey1)
    {
        $sql = " SELECT 
        case 
        when c.npm is null then concat(b.npm,RIGHT(YEAR(NOW()),2),'001')
        else c.npm
            end as npm
        FROM `hasil_ujian` a 
        INNER JOIN m_npm b 
        on a.kode_prodi = b.kode_prodi 
        CROSS JOIN (
            SELECT
            case 
            when mid(max(X.npm),5,2) <> right(YEAR(NOW()),2) then concat(left(X.npm,4),RIGHT(YEAR(NOW()),2),'001') 
            else lpad(convert(max(X.npm)+1, char(9)),9,'0000') 
                end as npm 
            FROM `u_pribadi`X
            WHERE left(X.npm,4) = (
                SELECT b.npm 
                FROM `hasil_ujian` a 
                INNER JOIN m_npm b 
                on a.kode_prodi = b.kode_prodi 
                WHERE no_pendaf = '" . $billKey1 . "'
                AND status_baca = 'READ'
                )
                ) c
                WHERE no_pendaf = '" . $billKey1 . "'
                ";
        $query = $this->db->query($sql);

        return $query->row_array();
    }

    public function insert_payment_status($param)
    {
        if (strlen($param['billKey1']) == 8) {
            unset($param['billKey1']);
            $this->db->insert('val_pendaftaran', $param);
            return ($this->db->affected_rows() > 0);
        } else {
            unset($param['billKey1']);
            $this->db->insert('val_registrasi', $param);
            return ($this->db->affected_rows() > 0);
        }
    }

    public function insert_payment_va($param)
    {
        return $this->db->insert('u_va_payment', $param);
    }

    public function insert_payment_pendaftaran($param)
    {
        return $this->db->insert('bni_payment_billing_pendaftaran', $param);
    }

    public function insert_payment_registrasi($param)
    {
        return $this->db->insert('bni_payment_billing_registrasi', $param);
    }

    public function update_npm_by_id($id_u_pmb, $npm)
    {
        $this->db->set('npm', $npm);
        $this->db->where('id_u_pmb', $id_u_pmb);
        $this->db->update('u_pribadi');

        return ($this->db->affected_rows() > 0);
    }

    public function update_no_pendaftaran_by_id($id_u_pmb, $no_pendaftaran, $id_gel)
    {
        //$no_registrasi = substr($id_u_pmb, 7);
        $this->db->set('no_pendaftaran', $no_pendaftaran);
        $this->db->set('id_gel', $id_gel);
        $this->db->where('id_u_pmb', $id_u_pmb);
        $this->db->update('u_pmb');

        return ($this->db->affected_rows() > 0);
    }

    public function update_billing_data($data, $param)
    {
        $this->db->set($data);
        $this->db->where($param);
        $this->db->update('payment_bank');

        return ($this->db->affected_rows() > 0);
    }

    public function delete_payment_status($id_u_pmb, $param)
    {
        if (strlen($param['billKey1']) == 8) {
            $this->db->where('id_u_pmb', $id_u_pmb);
            $this->db->update('val_pendaftaran');
        } else {
            $this->db->where('id_u_pmb', $id_u_pmb);
            $this->db->update('val_spp');
        }

        return ($this->db->affected_rows() > 0);
    }
}
