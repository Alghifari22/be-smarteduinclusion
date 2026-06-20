<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mapel_model extends \CI_Model {
    private $table = 'mata_pelajaran';

    public function exists($kode_mapel)
    {
        return $this->db
            ->where('kode_mapel', $kode_mapel)
            ->count_all_results($this->table) > 0;
    }
}
?>