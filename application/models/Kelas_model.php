<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kelas_model extends \CI_Model {
    private $table = 'kelas';

    public function exists($kode_kelas)
    {
        return $this->db
            ->where('kode_kelas', $kode_kelas)
            ->count_all_results($this->table) > 0;
    }
}
?>