<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Modul_model extends CI_Model
{
    private $table = 'modul';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_with_relasi($kode_kelas = null, $kode_mapel = null)
    {
        $this->db
            ->select('
                mo.kode_modul,
                mo.judul,
                mo.deskripsi,
                mo.tanggal_dibuat,
                mo.kode_mapel,
                mp.nama_mapel,
                mo.kode_kelas,
                k.nama_kelas
            ')
            ->from('modul mo')
            ->join('mata_pelajaran mp', 'mp.kode_mapel = mo.kode_mapel', 'left')
            ->join('kelas k', 'k.kode_kelas = mo.kode_kelas', 'left');

        if ($kode_kelas) {
            $this->db->where('mo.kode_kelas', $kode_kelas);
        }

        if ($kode_mapel) {
            $this->db->where('mo.kode_mapel', $kode_mapel);
        }

        return $this->db
            ->order_by('mo.tanggal_dibuat', 'DESC')
            ->get()
            ->result();
    }

    public function get_kode_mapel_guru($id_pengguna)
    {
        $guru = $this->db
            ->select('kode_mapel')
            ->where('id_pengguna', $id_pengguna)
            ->where('role', 'Guru')
            ->get('users')
            ->row();

        return $guru ? $guru->kode_mapel : null;
    }

    public function belongs_to_mapel($kode_modul, $kode_mapel)
    {
        return $this->db
            ->where('kode_modul', $kode_modul)
            ->where('kode_mapel', $kode_mapel)
            ->count_all_results($this->table) > 0;
    }

    public function get_by_id($kode_modul)
    {
        return $this->db
            ->select('
                mo.kode_modul,
                mo.judul,
                mo.deskripsi,
                mo.tanggal_dibuat,
                mo.kode_mapel,
                mp.nama_mapel,
                mo.kode_kelas,
                k.nama_kelas
            ')
            ->from('modul mo')
            ->join('mata_pelajaran mp', 'mp.kode_mapel = mo.kode_mapel', 'left')
            ->join('kelas k', 'k.kode_kelas = mo.kode_kelas', 'left')
            ->where('mo.kode_modul', $kode_modul)
            ->get()
            ->row();
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    public function update($kode_modul, $data)
    {
        $this->db->where('kode_modul', $kode_modul);
        $this->db->update($this->table, $data);

        return $this->db->affected_rows() >= 0;
    }

    public function delete($kode_modul)
    {
        $this->db->where('kode_modul', $kode_modul);
        $this->db->delete($this->table);

        return $this->db->affected_rows() > 0;
    }

    public function exists($kode_modul)
    {
        return $this->db
            ->where('kode_modul', $kode_modul)
            ->count_all_results($this->table) > 0;
    }

    public function mapel_exists($kode_mapel)
    {
        return $this->db
            ->where('kode_mapel', $kode_mapel)
            ->count_all_results('mata_pelajaran') > 0;
    }

    public function kelas_exists($kode_kelas)
    {
        return $this->db
            ->where('kode_kelas', $kode_kelas)
            ->count_all_results('kelas') > 0;
    }

    public function generate_kode_modul()
    {
        $last = $this->db
            ->select('kode_modul')
            ->order_by('kode_modul', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();

        if (!$last) {
            return 'MOD-001';
        }

        $angka = (int) substr($last->kode_modul, 4) + 1;

        return 'MOD-' . str_pad($angka, 3, '0', STR_PAD_LEFT);
    }
}
