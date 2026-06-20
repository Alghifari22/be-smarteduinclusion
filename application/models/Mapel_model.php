<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mapel_model extends \CI_Model {
    private $table = 'mata_pelajaran';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all mapel
     */
    public function get_all($limit = 10, $offset = 0)
    {
        $this->db->limit($limit, $offset);

        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Get mapel by ID
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, array('kode_mapel' => $id));
        return $query->row();
    }

    /**
     * Create new mapel
     */
    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update mapel
     */
    public function update($id, $data)
    {
        $this->db->where('kode_mapel', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Delete mapel
     */
    public function delete($id)
    {
        $this->db->where('kode_mapel', $id);
        return $this->db->delete($this->table);
    }
    
    /**
     * Check exists or not
     */
    public function exists($kode_mapel)
    {
        return $this->db
            ->where('kode_mapel', $kode_mapel)
            ->count_all_results($this->table) > 0;
    }

    /**
    * Get total mapel
    */
    public function count_all()
    {
        return $this->db->count_all($this->table);
    }
}
?>