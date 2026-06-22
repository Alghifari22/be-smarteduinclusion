<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal_model extends \CI_Model {
    private $table = 'soal'; 
    private $table_soal = 'soal';
    private $table_jawaban = 'jawaban';   

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_detail_latihan($kode_materi, $id_pengguna)
    {
        $materi = $this->db
            ->select('materi.kode_materi, materi.judul, mapel.nama_mapel')
            ->from('materi')
            ->join('modul', 'modul.kode_modul = materi.kode_modul')
            ->join('mata_pelajaran mapel', 'mapel.kode_mapel = modul.kode_mapel')
            ->where('materi.kode_materi', $kode_materi)
            ->get()
            ->row();

        if (!$materi) {
            return null;
        }

        $soal = $this->db
            ->select('kode_soal, pertanyaan, gambar')
            ->where('kode_materi', $kode_materi)
            ->order_by('kode_soal', 'ASC')
            ->get('soal')
            ->result();

        foreach ($soal as $s) {
            $jawaban = $this->db
                ->select('kode_jawaban, opsi_a, opsi_b, opsi_c')
                ->where('kode_soal', $s->kode_soal)
                ->get('jawaban')
                ->row();

            $s->opsi = $jawaban;
        }

        $total_soal = count($soal);

        $sudah_dijawab = $this->db
            ->from('jawaban_siswa js')
            ->join('soal s', 's.kode_soal = js.kode_soal')
            ->where('js.id_pengguna', $id_pengguna)
            ->where('s.kode_materi', $kode_materi)
            ->count_all_results();

        return [
            'materi' => $materi,
            'total_soal' => $total_soal,
            'sudah_dijawab' => $sudah_dijawab,
            'progress' => $total_soal > 0 ? round(($sudah_dijawab / $total_soal) * 100) : 0,
            'soal' => $soal
        ];
    }

    public function simpan_jawaban($id_pengguna, $kode_soal, $jawaban)
    {
        $cek = $this->db
            ->where('id_pengguna', $id_pengguna)
            ->where('kode_soal', $kode_soal)
            ->get('jawaban_siswa')
            ->row();

        if ($cek) {
            return $this->db
                ->where('id_pengguna', $id_pengguna)
                ->where('kode_soal', $kode_soal)
                ->update('jawaban_siswa', [
                    'jawaban' => $jawaban
                ]);
        }

        return $this->db->insert('jawaban_siswa', [
            'kode_jawabansis' => 'JWS-' . time() . rand(10, 99),
            'jawaban' => $jawaban,
            'id_pengguna' => $id_pengguna,
            'kode_soal' => $kode_soal
        ]);
    }

    public function hitung_nilai($id_pengguna, $kode_materi)
    {
        $total_soal = $this->db
            ->where('kode_materi', $kode_materi)
            ->count_all_results('soal');

        $jawaban = $this->db
            ->select('js.jawaban, j.jawaban_benar')
            ->from('jawaban_siswa js')
            ->join('soal s', 's.kode_soal = js.kode_soal')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal')
            ->where('js.id_pengguna', $id_pengguna)
            ->where('s.kode_materi', $kode_materi)
            ->get()
            ->result();

        $benar = 0;

        foreach ($jawaban as $row) {
            if ($row->jawaban == $row->jawaban_benar) {
                $benar++;
            }
        }

        $nilai = $total_soal > 0 ? round(($benar / $total_soal) * 100) : 0;

        return [
            'total_soal' => $total_soal,
            'jumlah_benar' => $benar,
            'jumlah_salah' => $total_soal - $benar,
            'nilai' => $nilai
        ];
    }

    public function simpan_nilai($kode_materi, $nilai)
    {
        return $this->db->insert('nilai_siswa', [
            'kode_nilai' => 'NIL-' . time(),
            'nilai' => $nilai,
            'tanggal_nilai' => date('Y-m-d'),
            'kode_materi' => $kode_materi
        ]);
    }

    public function count_total_soal($kode_materi)
    {
        return $this->db
            ->where('kode_materi', $kode_materi)
            ->count_all_results('soal');
    }

    public function get_progress_latihan($id_pengguna, $kode_materi)
    {
        $total_soal = $this->count_total_soal($kode_materi);

        $sudah_dijawab = $this->db
            ->from('jawaban_siswa js')
            ->join('soal s', 's.kode_soal = js.kode_soal')
            ->where('js.id_pengguna', $id_pengguna)
            ->where('s.kode_materi', $kode_materi)
            ->count_all_results();

        return [
            'total_soal' => $total_soal,
            'sudah_dijawab' => $sudah_dijawab,
            'progress' => $total_soal > 0
                ? round(($sudah_dijawab / $total_soal) * 100)
                : 0
        ];
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
