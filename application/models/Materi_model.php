<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Materi_model extends CI_Model
{
    private $table = 'materi';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_with_modul($kode_modul = null)
    {
        $this->db
            ->select('
                m.kode_materi,
                m.judul,
                m.deskripsi,
                m.kode_modul,
                mo.judul AS judul_modul,
                mo.kode_mapel,
                mp.nama_mapel,
                mo.kode_kelas,
                k.nama_kelas
            ')
            ->from('materi m')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul', 'left')
            ->join('mata_pelajaran mp', 'mp.kode_mapel = mo.kode_mapel', 'left')
            ->join('kelas k', 'k.kode_kelas = mo.kode_kelas', 'left');

        if ($kode_modul) {
            $this->db->where('m.kode_modul', $kode_modul);
        }

        return $this->db
            ->order_by('m.kode_materi', 'DESC')
            ->get()
            ->result();
    }

    public function get_by_id($kode_materi)
    {
        return $this->db
            ->select('
                m.kode_materi,
                m.judul,
                m.deskripsi,
                m.kode_modul,
                mo.judul AS judul_modul,
                mo.kode_mapel,
                mp.nama_mapel,
                mo.kode_kelas,
                k.nama_kelas
            ')
            ->from('materi m')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul', 'left')
            ->join('mata_pelajaran mp', 'mp.kode_mapel = mo.kode_mapel', 'left')
            ->join('kelas k', 'k.kode_kelas = mo.kode_kelas', 'left')
            ->where('m.kode_materi', $kode_materi)
            ->get()
            ->row();
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    public function update($kode_materi, $data)
    {
        $this->db->where('kode_materi', $kode_materi);
        $this->db->update($this->table, $data);

        return $this->db->affected_rows() >= 0;
    }

    public function delete($kode_materi)
    {
        $this->db->where('kode_materi', $kode_materi);
        $this->db->delete($this->table);

        return $this->db->affected_rows() > 0;
    }

    public function exists($kode_materi)
    {
        return $this->db
            ->where('kode_materi', $kode_materi)
            ->count_all_results($this->table) > 0;
    }

    public function modul_exists($kode_modul)
    {
        return $this->db
            ->where('kode_modul', $kode_modul)
            ->count_all_results('modul') > 0;
    }

    public function generate_kode_materi()
    {
        $last = $this->db
            ->select('kode_materi')
            ->order_by('kode_materi', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();

        if (!$last) {
            return 'MAT-001';
        }

        $angka = (int) substr($last->kode_materi, 4) + 1;

        return 'MAT-' . str_pad($angka, 3, '0', STR_PAD_LEFT);
    }
}