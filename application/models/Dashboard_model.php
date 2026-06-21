<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends \CI_Model {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_dashboard_staffTU(){
        $data = [];

        $data['total_siswa'] = $this->db->where('role', 'Siswa')->count_all_results('users');
        $data['total_guru'] = $this->db->where('role', 'Guru')->count_all_results('users');
        $data['total_kelas'] = $this->db->count_all('kelas');

        return $data;
    }

    public function get_recent_activities($limit = 3){
        return $this->db
            ->select('u.nama, u.role, a.aktivitas, a.created_at')
            ->from('activity_logs a')
            ->join('users u', 'u.id_pengguna = a.id_pengguna')
            ->order_by('a.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    public function get_progress_siswa($id_siswa){
        $siswa = $this->db
            ->select('kode_kelas')
            ->where('id_pengguna', $id_siswa)
            ->get('users')
            ->row();

        $total_materi = $this->db
            ->from('materi m')
            ->join('modul md', 'md.kode_modul = m.kode_modul')
            ->where('md.kode_kelas', $siswa->kode_kelas)
            ->count_all_results();

        $materi_selesai = $this->db
            ->from('progres_materi_siswa p')
            ->join('materi m', 'm.kode_materi = p.kode_materi')
            ->join('modul md', 'md.kode_modul = m.kode_modul')
            ->where('p.id_pengguna', $id_siswa)
            ->where('p.status', 'Selesai')
            ->where('md.kode_kelas', $siswa->kode_kelas)
            ->count_all_results();

        return [
            'total_materi' => $total_materi,
            'materi_selesai' => $materi_selesai,
            'persentase' => $total_materi > 0 ? round(($materi_selesai / $total_materi) * 100) : 0
        ];
    }

    public function get_latest_quiz()
    {
        return $this->db
            ->select('
                gamifikasi.kode_gamifikasi,
                gamifikasi.judul,
                COUNT(soal_gam.kode_soalgam) as jumlah_soal
            ')
            ->from('gamifikasi')
            ->join(
                'soal_gam',
                'soal_gam.kode_gamifikasi = gamifikasi.kode_gamifikasi',
                'left'
            )
            ->group_by('gamifikasi.kode_gamifikasi')
            ->order_by('gamifikasi.tanggal_dibuat', 'DESC')
            ->limit(1)
            ->get()
            ->row();
    }

    private function get_materi_hari_ini($id_siswa)
    {
        $siswa = $this->db
            ->select('kode_kelas')
            ->where('id_pengguna', $id_siswa)
            ->get('users')
            ->row();

        return $this->db
            ->select('
                materi.kode_materi,
                materi.judul,
                materi.deskripsi,
                mapel.nama_mapel,
                IFNULL(progres.status, "Belum Dibaca") AS status
            ')
            ->from('materi')
            ->join('modul', 'modul.kode_modul = materi.kode_modul')
            ->join('mata_pelajaran mapel', 'mapel.kode_mapel = modul.kode_mapel')
            ->join(
                'progres_materi_siswa progres',
                'progres.kode_materi = materi.kode_materi 
                AND progres.id_pengguna = ' . $this->db->escape($id_siswa),
                'left'
            )
            ->where('modul.kode_kelas', $siswa->kode_kelas)
            ->order_by('materi.kode_materi', 'ASC')
            ->limit(3)
            ->get()
            ->result();
    }

    public function get_dashboard_siswa($id_siswa)
    {
        $siswa = $this->db
            ->select('kode_kelas')
            ->where('id_pengguna', $id_siswa)
            ->get('users')
            ->row();

        return [
            'progress_hari_ini' => $this->get_progress_siswa($id_siswa),
            'materi_hari_ini' => $this->get_materi_hari_ini($id_siswa),
            'kuis_harian' => $this->get_latest_quiz($siswa->kode_kelas)
        ];
    }
}
?>