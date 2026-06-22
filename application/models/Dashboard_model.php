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

    public function get_orangtua($id_pengguna)
    {
        return $this->db
            ->select('id_pengguna, nama, email, role')
            ->where('id_pengguna', $id_pengguna)
            ->where('role', 'Orang Tua')
            ->get('users')
            ->row();
    }

    public function get_anak($id_pengguna)
    {
        return $this->db
            ->select('
                u.id_pengguna,
                u.nama,
                u.email,
                u.kode_kelas,
                k.nama_kelas
            ')
            ->from('users u')
            ->join('kelas k', 'k.kode_kelas = u.kode_kelas', 'left')
            ->where('u.NISN', $id_pengguna)
            ->where('u.role', 'Siswa')
            ->get()
            ->row();
    }

    public function get_ringkasan($id_siswa)
    {
        return $this->db
            ->select('
                COUNT(DISTINCT m.kode_materi) AS total_materi,
                COUNT(DISTINCT CASE 
                    WHEN pms.status = "Selesai" THEN pms.kode_materi 
                END) AS materi_selesai,
                ROUND(
                    IFNULL(
                        COUNT(DISTINCT CASE 
                            WHEN pms.status = "Selesai" THEN pms.kode_materi 
                        END) / NULLIF(COUNT(DISTINCT m.kode_materi), 0) * 100,
                    0)
                ) AS persentase_progress,
                COUNT(DISTINCT js.kode_jawabansis) AS total_jawaban,
                SUM(CASE 
                    WHEN js.jawaban = j.jawaban_benar THEN 1 
                    ELSE 0 
                END) AS jawaban_benar,
                ROUND(
                    IFNULL(
                        SUM(CASE 
                            WHEN js.jawaban = j.jawaban_benar THEN 1 
                            ELSE 0 
                        END) / NULLIF(COUNT(DISTINCT js.kode_jawabansis), 0) * 100,
                    0)
                ) AS rata_rata_nilai
            ', false)
            ->from('users u')
            ->join('modul mo', 'mo.kode_kelas = u.kode_kelas', 'left')
            ->join('materi m', 'm.kode_modul = mo.kode_modul', 'left')
            ->join('progres_materi_siswa pms', 'pms.id_pengguna = u.id_pengguna AND pms.kode_materi = m.kode_materi', 'left')
            ->join('soal s', 's.kode_materi = m.kode_materi', 'left')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal', 'left')
            ->join('jawaban_siswa js', 'js.id_pengguna = u.id_pengguna AND js.kode_soal = s.kode_soal', 'left')
            ->where('u.id_pengguna', $id_siswa)
            ->get()
            ->row();
    }

    public function get_progress_mingguan($id_siswa)
    {
        return $this->db
            ->select('
            DATE(pms.tanggal_mulai) AS tanggal,
            COUNT(*) AS total_aktivitas
        ', false)
            ->from('progres_materi_siswa pms')
            ->where('pms.id_pengguna', $id_siswa)
            ->where('pms.tanggal_mulai >=', date('Y-m-d 00:00:00', strtotime('-7 days')))
            ->group_by('DATE(pms.tanggal_mulai)')
            ->order_by('tanggal', 'ASC')
            ->get()
            ->result();
    }

    public function get_fokus_minggu_ini($id_siswa)
    {
        $materi = $this->db
            ->select('m.judul AS judul_materi, mo.judul AS judul_modul')
            ->from('users u')
            ->join('modul mo', 'mo.kode_kelas = u.kode_kelas')
            ->join('materi m', 'm.kode_modul = mo.kode_modul')
            ->join('progres_materi_siswa pms', 'pms.id_pengguna = u.id_pengguna AND pms.kode_materi = m.kode_materi', 'left')
            ->where('u.id_pengguna', $id_siswa)
            ->where('(pms.status IS NULL OR pms.status != "Selesai")', null, false)
            ->order_by('m.kode_materi', 'ASC')
            ->limit(1)
            ->get()
            ->row();

        if (!$materi) {
            return [
                'judul' => 'Pertahankan progres belajar',
                'deskripsi' => 'Anak sudah menyelesaikan materi yang tersedia. Tetap dampingi agar konsisten belajar.'
            ];
        }

        return [
            'judul' => 'Fokus pada ' . $materi->judul_materi,
            'deskripsi' => 'Bantu anak memahami materi ' . $materi->judul_materi . ' pada modul ' . $materi->judul_modul . '.'
        ];
    }

    public function get_tugas_belum_selesai($id_siswa)
    {
        return $this->db
            ->select('
                m.kode_materi,
                m.judul AS judul_materi,
                mo.judul AS judul_modul,
                IFNULL(pms.status, "Belum Mulai") AS status
            ', false)
            ->from('users u')
            ->join('modul mo', 'mo.kode_kelas = u.kode_kelas', 'left')
            ->join('materi m', 'm.kode_modul = mo.kode_modul', 'left')
            ->join('progres_materi_siswa pms', 'pms.id_pengguna = u.id_pengguna AND pms.kode_materi = m.kode_materi', 'left')
            ->where('u.id_pengguna', $id_siswa)
            ->where('(pms.status IS NULL OR pms.status != "Selesai")', null, false)
            ->where('m.kode_materi IS NOT NULL')
            ->order_by('m.kode_materi', 'ASC')
            ->limit(5)
            ->get()
            ->result();
    }

    public function get_perkembangan_terbaru($id_siswa)
    {
        return $this->db
            ->select('
            pms.kode_materi,
            m.judul AS judul_materi,
            pms.status,
            pms.halaman_terakhir,
            pms.tanggal_mulai,
            pms.tanggal_selesai
        ')
            ->from('progres_materi_siswa pms')
            ->join('materi m', 'm.kode_materi = pms.kode_materi')
            ->where('pms.id_pengguna', $id_siswa)
            ->order_by('pms.tanggal_mulai', 'DESC')
            ->limit(5)
            ->get()
            ->result();
    }

    public function get_siswa($id_pengguna)
    {
        return $this->db
            ->select('
            u.id_pengguna,
            u.nama,
            u.email,
            u.kode_kelas,
            k.nama_kelas
        ')
            ->from('users u')
            ->join('kelas k', 'k.kode_kelas = u.kode_kelas', 'left')
            ->where('u.id_pengguna', $id_pengguna)
            ->where('u.role', 'Siswa')
            ->get()
            ->row();
    }

    public function get_progress_materi($id_pengguna)
    {
        return $this->db
            ->select('
            m.kode_materi,
            m.judul AS judul_materi,
            mo.judul AS judul_modul,
            IFNULL(pms.status, "Belum Mulai") AS status,
            IFNULL(pms.halaman_terakhir, 0) AS halaman_terakhir,
            pms.tanggal_mulai,
            pms.tanggal_selesai
        ', false)
            ->from('users u')
            ->join('modul mo', 'mo.kode_kelas = u.kode_kelas', 'left')
            ->join('materi m', 'm.kode_modul = mo.kode_modul', 'left')
            ->join('progres_materi_siswa pms', 'pms.id_pengguna = u.id_pengguna AND pms.kode_materi = m.kode_materi', 'left')
            ->where('u.id_pengguna', $id_pengguna)
            ->where('m.kode_materi IS NOT NULL')
            ->order_by('mo.kode_modul', 'ASC')
            ->order_by('m.kode_materi', 'ASC')
            ->get()
            ->result();
    }

    public function get_hasil_soal($id_pengguna)
    {
        return $this->db
            ->select('
            js.kode_jawabansis,
            s.kode_soal,
            s.pertanyaan,
            m.kode_materi,
            m.judul AS judul_materi,
            js.jawaban AS jawaban_siswa,
            j.jawaban_benar,
            CASE
                WHEN js.jawaban = j.jawaban_benar THEN "Benar"
                ELSE "Salah"
            END AS status_jawaban
        ', false)
            ->from('jawaban_siswa js')
            ->join('soal s', 's.kode_soal = js.kode_soal')
            ->join('materi m', 'm.kode_materi = s.kode_materi')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal')
            ->where('js.id_pengguna', $id_pengguna)
            ->order_by('js.kode_jawabansis', 'DESC')
            ->get()
            ->result();
    }
}
