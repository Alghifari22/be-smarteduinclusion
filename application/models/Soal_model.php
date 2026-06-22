<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal_model extends CI_Model
{
    private $table_soal = 'soal';
    private $table_jawaban = 'jawaban';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_with_jawaban($kode_materi = null)
    {
        $this->db
            ->select('
                s.kode_soal,
                s.pertanyaan,
                s.gambar,
                s.kode_materi,
                m.judul AS judul_materi,
                j.kode_jawaban,
                j.jawaban_benar
            ')
            ->from('soal s')
            ->join('materi m', 'm.kode_materi = s.kode_materi', 'left')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal', 'left');

        if ($kode_materi) {
            $this->db->where('s.kode_materi', $kode_materi);
        }

        return $this->db
            ->order_by('s.kode_soal', 'DESC')
            ->get()
            ->result();
    }

    public function get_by_id_with_jawaban($kode_soal)
    {
        return $this->db
            ->select('
                s.kode_soal,
                s.pertanyaan,
                s.gambar,
                s.kode_materi,
                m.judul AS judul_materi,
                j.kode_jawaban,
                j.opsi_a,
                j.opsi_b,
                j.opsi_c,
                j.jawaban_benar
            ')
            ->from('soal s')
            ->join('materi m', 'm.kode_materi = s.kode_materi', 'left')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal', 'left')
            ->where('s.kode_soal', $kode_soal)
            ->get()
            ->row();
    }

    public function create_soal($data)
    {
        $this->db->insert($this->table_soal, $data);
        return $this->db->affected_rows() > 0;
    }

    public function update_soal($kode_soal, $data)
    {
        $this->db->where('kode_soal', $kode_soal);
        $this->db->update($this->table_soal, $data);

        return $this->db->affected_rows() >= 0;
    }

    public function delete_soal($kode_soal)
    {
        $this->db->where('kode_soal', $kode_soal);
        $this->db->delete($this->table_soal);

        return $this->db->affected_rows() > 0;
    }

    public function soal_exists($kode_soal)
    {
        return $this->db
            ->where('kode_soal', $kode_soal)
            ->count_all_results($this->table_soal) > 0;
    }

    public function materi_exists($kode_materi)
    {
        return $this->db
            ->where('kode_materi', $kode_materi)
            ->count_all_results('materi') > 0;
    }

    public function generate_kode_soal()
    {
        $last = $this->db
            ->select('kode_soal')
            ->order_by('kode_soal', 'DESC')
            ->limit(1)
            ->get($this->table_soal)
            ->row();

        if (!$last) {
            return 'SOAL-001';
        }

        $angka = (int) substr($last->kode_soal, 5) + 1;

        return 'SOAL-' . str_pad($angka, 3, '0', STR_PAD_LEFT);
    }

    public function get_jawaban_by_soal($kode_soal)
    {
        return $this->db
            ->where('kode_soal', $kode_soal)
            ->get($this->table_jawaban)
            ->row();
    }

    public function create_jawaban($data)
    {
        $this->db->insert($this->table_jawaban, $data);
        return $this->db->affected_rows() > 0;
    }

    public function update_jawaban_by_soal($kode_soal, $data)
    {
        $this->db->where('kode_soal', $kode_soal);
        $this->db->update($this->table_jawaban, $data);

        return $this->db->affected_rows() >= 0;
    }

    public function delete_jawaban_by_soal($kode_soal)
    {
        $this->db->where('kode_soal', $kode_soal);
        $this->db->delete($this->table_jawaban);

        return $this->db->affected_rows() > 0;
    }

    public function jawaban_exists_by_soal($kode_soal)
    {
        return $this->db
            ->where('kode_soal', $kode_soal)
            ->count_all_results($this->table_jawaban) > 0;
    }

    public function generate_kode_jawaban()
    {
        $last = $this->db
            ->select('kode_jawaban')
            ->order_by('kode_jawaban', 'DESC')
            ->limit(1)
            ->get($this->table_jawaban)
            ->row();

        if (!$last) {
            return 'JWB-001';
        }

        $angka = (int) substr($last->kode_jawaban, 4) + 1;

        return 'JWB-' . str_pad($angka, 3, '0', STR_PAD_LEFT);
    }
}