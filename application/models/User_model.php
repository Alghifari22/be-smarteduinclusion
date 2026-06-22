<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends \CI_Model {
    private $table = 'users';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all users
     */
    public function get_all($limit = 10, $offset = 0)
    {
        $this->db->limit($limit, $offset);

        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Get user by ID
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, array('id_pengguna' => $id));
        return $query->row();
    }

    /**
     * Get user by email
     */
    public function get_by_email($email)
    {
        $query = $this->db->get_where($this->table, array('email' => $email));
        return $query->row();
    }

    /**
     * Create new user
     */
    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update user
     */
    public function update($id, $data)
    {
        $this->db->where('id_pengguna', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        $this->db->where('id_pengguna', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Check if user exists by email
     */
    public function email_exists($email)
    {
        $query = $this->db->get_where($this->table, array('email' => $email));
        return $query->num_rows() > 0;
    }


    /**
     * Check if email exists except
     */
    public function email_exists_except($email, $id_pengguna)
    {
        return $this->db
            ->where('email', $email)
            ->where('id_pengguna !=', $id_pengguna)
            ->count_all_results($this->table) > 0;
    }

    /**
     * Check if user exists by ID
     */
    public function exists($id)
    {
        $query = $this->db->get_where($this->table, array('id_pengguna' => $id));
        return $query->num_rows() > 0;
    }

    /**
     * Get total user
     */
    public function count_all()
    {
        return $this->db->count_all($this->table);
    }

    public function generate_id_pengguna($prefix)
    {
        $this->db->select('id_pengguna');
        $this->db->like('id_pengguna', $prefix, 'after');
        $this->db->order_by('id_pengguna', 'DESC');
        $this->db->limit(1);

        $last = $this->db->get('users')->row();

        if (!$last) {
            return $prefix . '001';
        }

        $last_number = (int) substr($last->id_pengguna, strlen($prefix));

        $new_number = $last_number + 1;

        return $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    }
}
?>