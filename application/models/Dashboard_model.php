<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends \CI_Model {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_dashboard_staffTU(){
        $data = [];

        $data['total_siswa'] = $this->db->where('role', 'Siswa')->count_all_results('users');
        $data['total_guru'] = $this->db->where('role', 'Guru')->count_all_results('users');
        $data['total_kelas'] = $this->db->count_all('kelas');

        return $data;
    }

    public function get_recent_activities($limit = 3){
        return $this->db
            ->select('u.nama, u.role, a.aktivitas, a.created_at')
            ->from('activity_logs a')
            ->join('users u', 'u.id_pengguna = a.id_pengguna')
            ->order_by('a.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }
}
?>