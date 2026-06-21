<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal_model extends \CI_Model {
    private $table = 'soal';    

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
}