<?php
class ModelCrud extends CI_Model {

	public function is_nik_registered($nik) {
        $this->db->where('nik_egent', $nik);
        $query = $this->db->get('pmbnew_referral');
        return $query->num_rows() > 0;
    }

    public function is_wa_registered($no_wa) {
        $this->db->where('no_wa_egent', $no_wa);
        $query = $this->db->get('pmbnew_referral');
        return $query->num_rows() > 0;
    }

	public function getData($filter = array())
	{
		if(!empty($filter['orderBy'])){
			$this->db->order_by($filter['where']);
		}
		if(!empty($filter['groupBy'])){
			$this->db->group_by($filter['where']);
		}
		if(!empty($filter['where'])){
			$this->db->where($filter['where']);
		}
		if(!empty($filter['join'])){
			$this->db->join($filter['join'][0], $filter['join'][1], $filter['join'][2]);
		}
		$query = $this->db->get($filter['table']);
		return $query;
	}

	public function setData($filter = array())
	{
		$table 	= $filter['table'];
		$input 	= $filter['input'];
		$act 	= $filter['act'];
		if($act == "insert"){
			$query 	= $this->db->insert($table, $input);
		}
		if($act == "update"){
			$where 	= $filter['where'];
			$query 	= $this->db->update($table, $input, $where);
		}
		return $query;
	}


	public function input($data,$tabel){
		return $this->db->insert($tabel,$data);
	}

	public function update2($where,$data,$table){
		$this->db->where($where);
		$this->db->update($table,$data);
	}

	public function update3($where2,$data2,$table2){
		$this->db->where($where2);
		$this->db->update($table2,$data2);
	}

	public function delete2($where,$table){
		$this->db->where($where);
		$this->db->delete($table);
	}

	public function simpan($table=NULL, $input=NULL)
	{
		$filter = array(
			"hasil_uji" => $input['hasil_uji'],
			"keterangan" => $input['keterangan'],
			"id_fps" => $input['id_fps']
		);

		$cekabsen = $this->db->get_where($table, $filter)->num_rows();
		if($cekabsen == 1){
			$query 	= $this->db->update($table, $input, $filter);
		} else {
			$query 	= $this->db->insert($table, $input);
		}
		return $query;
	}

	public function simpan2($table=NULL, $input=NULL)
	{
		$filter = array(
			"id_fps" => $input['id_fps'],
		);


		$cekabsen = $this->db->get_where($table, $filter)->num_rows();
		if($cekabsen == 1){
			$query 	= $this->db->update($table, $input, $filter);
		} else {
			$query 	= $this->db->insert($table, $input);
		}
		return $query;
	}

	public function get_data($id)
	{
		$this->db->where('id_file', $id);
		return $this->db->get('edahpus_file')->row();
	}


	public function read($table)
	{
		$result = $this->db->get($table);
		return $result;
	}


	public function kodetiket(){

		$data['user'] = $this->db->get_where('edahpus_user', ['id_profil' => $this->session->userdata('id_profil')])->row_array();

		$id_profil = $data['user']['id_profil'];

		$data['profil'] = $this->db->get_where('edahpus_profil', ['id_profil' => $id_profil])->row_array();

		$id_satker = $data['profil']['id_satker'];

		$data['satker'] = $this->db->get_where('edahpus_satker', ['id_satker ' => $id_satker])->row_array();

		$kode_satker = $data['satker']['kode_satker'];

		$this->db->select('RIGHT(edahpus_tiket.no_tiket,2) as no_tiket', FALSE);
		$this->db->order_by('no_tiket','DESC');    
		$this->db->limit(1);    
		  $query = $this->db->get('edahpus_tiket');  //cek dulu apakah ada sudah ada kode di tabel.    
		  if($query->num_rows() <> 0){      
			   //cek kode jika telah tersedia    
		  	$data = $query->row();      
		  	$kode = intval($data->no_tiket) + 1; 
		  }
		  else{      
			   $kode = 1;  //cek jika kode belum terdapat pada table
			}
			$tgl=date('dmy'); 
			$batas = str_pad($kode, 4, "0", STR_PAD_LEFT);    
			$kodetampil = $tgl.'/'.$kode_satker.'/'.$batas;
			return $kodetampil;  
		}
		

		
	}
?>