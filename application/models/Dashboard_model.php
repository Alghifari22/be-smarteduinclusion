<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard_model extends \CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_dashboard_staffTU()
    {
        $data = [];

        $data['total_siswa'] = $this->db->where('role', 'Siswa')->count_all_results('users');
        $data['total_guru'] = $this->db->where('role', 'Guru')->count_all_results('users');
        $data['total_kelas'] = $this->db->count_all('kelas');

        return $data;
    }

    public function get_recent_activities($limit = 3)
    {
        return $this->db
            ->select('u.nama, u.role, a.aktivitas, a.created_at')
            ->from('activity_logs a')
            ->join('users u', 'u.id_pengguna = a.id_pengguna')
            ->order_by('a.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    public function get_guru_mapel(string $id_pengguna): ?string
    {
        $guru = $this->db
            ->select('kode_mapel')
            ->where('id_pengguna', $id_pengguna)
            ->where('role', 'Guru')
            ->get('users')
            ->row();

        return $guru->kode_mapel ?? null;
    }

    public function get_progress_rata_kelas(string $id_pengguna, ?string $kode_materi = null): array
    {
        $kosong = ['judul_materi' => null, 'persentase' => 0, 'siswa_selesai' => 0, 'total_siswa' => 0];

        $kode_mapel = $this->get_guru_mapel($id_pengguna);
        if (!$kode_mapel) return $kosong;

        if (!$kode_materi) {
            $materi = $this->db
                ->select('m.kode_materi, m.judul')
                ->from('materi m')
                ->join('modul mo', 'mo.kode_modul = m.kode_modul')
                ->where('mo.kode_mapel', $kode_mapel)
                ->order_by('mo.tanggal_dibuat', 'DESC')
                ->limit(1)
                ->get()
                ->row();

            if (!$materi) return $kosong;
            $kode_materi  = $materi->kode_materi;
            $judul_materi = $materi->judul;
        } else {
            $judul_materi = $this->db->where('kode_materi', $kode_materi)->get('materi')->row()->judul ?? null;
        }

        $row = $this->db
            ->select('SUM(CASE WHEN status = "Selesai" THEN 1 ELSE 0 END) as selesai, COUNT(*) as total', false)
            ->where('kode_materi', $kode_materi)
            ->get('progres_materi_siswa')
            ->row();

        $total   = (int) ($row->total ?? 0);
        $selesai = (int) ($row->selesai ?? 0);

        return [
            'judul_materi'  => $judul_materi,
            'persentase'    => $total > 0 ? round($selesai / $total * 100) : 0,
            'siswa_selesai' => $selesai,
            'total_siswa'   => $total,
        ];
    }

    public function get_total_siswa_tertinggal(string $id_pengguna): int
    {
        $kode_mapel = $this->get_guru_mapel($id_pengguna);
        if (!$kode_mapel) return 0;

        return $this->db
            ->distinct()
            ->select('ps.id_pengguna')
            ->from('progres_materi_siswa ps')
            ->join('materi m', 'm.kode_materi = ps.kode_materi')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul')
            ->where('mo.kode_mapel', $kode_mapel)
            ->where('ps.status', 'Belum Selesai')
            ->count_all_results();
    }

    public function get_total_kuis_dinilai(string $id_pengguna): int
    {
        $kode_mapel = $this->get_guru_mapel($id_pengguna);
        if (!$kode_mapel) return 0;

        return $this->db
            ->select('js.kode_jawabansis')
            ->from('jawaban_siswa js')
            ->join('soal s', 's.kode_soal = js.kode_soal')
            ->join('materi m', 'm.kode_materi = s.kode_materi')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul')
            ->where('mo.kode_mapel', $kode_mapel)
            ->count_all_results();
    }

    public function get_daftar_siswa_progress(string $id_pengguna, ?string $keyword = null): array
    {
        $kode_mapel = $this->get_guru_mapel($id_pengguna);
        if (!$kode_mapel) return [];

        $total_materi = $this->db
            ->from('materi m')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul')
            ->where('mo.kode_mapel', $kode_mapel)
            ->count_all_results();

        if ($total_materi == 0) return [];

        $mapel_esc = $this->db->escape($kode_mapel);

        $this->db
            ->select("u.id_pengguna, u.nama,
                (SELECT COUNT(*) FROM progres_materi_siswa ps
                    JOIN materi m ON m.kode_materi = ps.kode_materi
                    JOIN modul mo ON mo.kode_modul = m.kode_modul
                    WHERE ps.id_pengguna = u.id_pengguna
                      AND mo.kode_mapel = $mapel_esc
                      AND ps.status = 'Selesai') as materi_selesai,
                (SELECT COUNT(*) FROM jawaban_siswa js
                    JOIN soal s ON s.kode_soal = js.kode_soal
                    JOIN materi m ON m.kode_materi = s.kode_materi
                    JOIN modul mo ON mo.kode_modul = m.kode_modul
                    WHERE js.id_pengguna = u.id_pengguna
                      AND mo.kode_mapel = $mapel_esc) as total_jawab,
                (SELECT COUNT(*) FROM jawaban_siswa js
                    JOIN soal s ON s.kode_soal = js.kode_soal
                    JOIN jawaban j ON j.kode_soal = s.kode_soal
                    JOIN materi m ON m.kode_materi = s.kode_materi
                    JOIN modul mo ON mo.kode_modul = m.kode_modul
                    WHERE js.id_pengguna = u.id_pengguna
                      AND js.jawaban = j.jawaban_benar
                      AND mo.kode_mapel = $mapel_esc) as jawaban_benar
            ", false)
            ->from('users u')
            ->where('u.role', 'Siswa');

        if ($keyword) {
            $this->db->like('u.nama', $keyword);
        }

        $this->db->order_by('u.nama', 'ASC');
        $rows = $this->db->get()->result();

        $hasil = [];
        foreach ($rows as $row) {
            $persentase = round(((int) $row->materi_selesai) / $total_materi * 100);
            $skor = $row->total_jawab > 0
                ? round($row->jawaban_benar / $row->total_jawab * 100)
                : null;

            $hasil[] = [
                'id_pengguna'   => $row->id_pengguna,
                'nama'          => $row->nama,
                'persentase'    => $persentase,
                'status'        => $persentase >= 100 ? 'Selesai' : $persentase . '%',
                'skor_terakhir' => $skor,
            ];
        }

        return $hasil;
    }
}
