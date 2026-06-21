<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Materi_model extends \CI_Model {
    private $table = 'materi';    

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_materi_siswa($id_siswa, $kode_mapel = null, $limit = 10, $offset = 0)
    {
        $siswa = $this->db
            ->select('kode_kelas')
            ->where('id_pengguna', $id_siswa)
            ->get('users')
            ->row();

        $this->db->select('
            materi.kode_materi,
            materi.judul,
            materi.deskripsi,
            mapel.nama_mapel,
            IFNULL(progres.status, "Belum Mulai") AS status,
            CASE
                WHEN progres.status = "Selesai" THEN 100
                WHEN progres.status = "Belum Selesai" THEN 50
                ELSE 0
            END AS progress
        ', FALSE);

        $this->db->from('materi');
        $this->db->join('modul', 'modul.kode_modul = materi.kode_modul');
        $this->db->join('mata_pelajaran mapel', 'mapel.kode_mapel = modul.kode_mapel');
        $this->db->join(
            'progres_materi_siswa progres',
            'progres.kode_materi = materi.kode_materi 
            AND progres.id_pengguna = ' . $this->db->escape($id_siswa),
            'left'
        );
        $this->db->where('modul.kode_kelas', $siswa->kode_kelas);

        if (!empty($kode_mapel)) {
            $this->db->where('mapel.kode_mapel', $kode_mapel);
        }

        return $this->db
            ->order_by('materi.kode_materi', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->result();
    }

    public function count_all_materi($id_siswa)
    {
        $siswa = $this->db
            ->select('kode_kelas')
            ->where('id_pengguna', $id_siswa)
            ->get('users')
            ->row();

        return $this->db
            ->from('materi')
            ->join('modul', 'modul.kode_modul = materi.kode_modul')
            ->where('modul.kode_kelas', $siswa->kode_kelas)
            ->count_all_results();
    }

    public function get_materi_yang_punya_soal($id_siswa, $kode_mapel = null, $limit = 10, $offset = 0)
    {
        $siswa = $this->db
            ->select('kode_kelas')
            ->where('id_pengguna', $id_siswa)
            ->get('users')
            ->row();

        $this->db->select('
            materi.kode_materi,
            materi.judul,
            materi.deskripsi,
            mapel.nama_mapel,
            COUNT(soal.kode_soal) AS jumlah_soal
        ', false);

        $this->db->from('soal');
        $this->db->join('materi', 'materi.kode_materi = soal.kode_materi');
        $this->db->join('modul', 'modul.kode_modul = materi.kode_modul');
        $this->db->join('mata_pelajaran mapel', 'mapel.kode_mapel = modul.kode_mapel');

        $this->db->where('modul.kode_kelas', $siswa->kode_kelas);

        if (!empty($kode_mapel)) {
            $this->db->where('mapel.kode_mapel', $kode_mapel);
        }

        $this->db->group_by('materi.kode_materi')
            ->order_by('materi.kode_materi', 'ASC')
            ->limit($limit, $offset);

        return $this->db->get()->result();
    }

    public function count_materi_yang_punya_soal($id_siswa)
    {
        $siswa = $this->db
            ->select('kode_kelas')
            ->where('id_pengguna', $id_siswa)
            ->get('users')
            ->row();

        return $this->db
            ->select('soal.kode_materi')
            ->from('soal')
            ->join('materi', 'materi.kode_materi = soal.kode_materi')
            ->join('modul', 'modul.kode_modul = materi.kode_modul')
            ->where('modul.kode_kelas', $siswa->kode_kelas)
            ->group_by('soal.kode_materi')
            ->get()
            ->num_rows();
    }

    public function get_detail_materi($kode_materi, $id_siswa)
    {
        $materi = $this->db
            ->select('
                materi.kode_materi,
                materi.judul,
                materi.deskripsi,
                mapel.nama_mapel,
                IFNULL(progres.status, "Belum Mulai") AS status,
                IFNULL(progres.halaman_terakhir, 1) AS halaman_terakhir
            ', false)
            ->from('materi')
            ->join('modul', 'modul.kode_modul = materi.kode_modul')
            ->join('mata_pelajaran mapel', 'mapel.kode_mapel = modul.kode_mapel')
            ->join(
                'progres_materi_siswa progres',
                'progres.kode_materi = materi.kode_materi
                AND progres.id_pengguna = ' . $this->db->escape($id_siswa),
                'left'
            )
            ->where('materi.kode_materi', $kode_materi)
            ->get()
            ->row();

        if (!$materi) {
            return null;
        }

        $halaman = $this->db
            ->select('
                kode_detail_materi,
                kode_materi,
                urutan,
                judul,
                isi,
                gambar
            ')
            ->where('kode_materi', $kode_materi)
            ->order_by('urutan', 'ASC')
            ->get('detail_materi')
            ->result();

        return [
            'materi' => $materi,
            'total_halaman' => count($halaman),
            'halaman' => $halaman
        ];
    }

    public function update_progress_materi($id_siswa, $kode_materi, $halaman_terakhir, $total_halaman)
    {
        $progress = $this->db
            ->where('id_pengguna', $id_siswa)
            ->where('kode_materi', $kode_materi)
            ->get('progres_materi_siswa')
            ->row();

        $status = $halaman_terakhir >= $total_halaman ? 'Selesai' : 'Belum Selesai';

        if ($progress) {
            return $this->db
                ->where('id_pengguna', $id_siswa)
                ->where('kode_materi', $kode_materi)
                ->update('progres_materi_siswa', [
                    'halaman_terakhir' => $halaman_terakhir,
                    'status' => $status,
                    'tanggal_selesai' => $status == 'Selesai' ? date('Y-m-d H:i:s') : null
                ]);
        }

        return $this->db->insert('progres_materi_siswa', [
            'id_pengguna' => $id_siswa,
            'kode_materi' => $kode_materi,
            'status' => $status,
            'halaman_terakhir' => $halaman_terakhir,
            'tanggal_mulai' => date('Y-m-d H:i:s'),
            'tanggal_selesai' => $status == 'Selesai' ? date('Y-m-d H:i:s') : null
        ]);
    }

    public function count_detail_materi($kode_materi)
    {
        return $this->db
            ->where('kode_materi', $kode_materi)
            ->count_all_results('detail_materi');
    }
}