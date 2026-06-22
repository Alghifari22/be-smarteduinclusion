<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Laporan_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_laporan_siswa($kode_kelas = null, $kode_mapel = null, $keyword = null)
    {
        $this->db
            ->select('
                u.id_pengguna,
                u.nama,
                u.email,
                u.kode_kelas,
                k.nama_kelas,

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
                ) AS progress_persen,

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
            ->join('kelas k', 'k.kode_kelas = u.kode_kelas', 'left')
            ->join('modul mo', 'mo.kode_kelas = u.kode_kelas', 'left')
            ->join('materi m', 'm.kode_modul = mo.kode_modul', 'left')
            ->join('progres_materi_siswa pms', 'pms.id_pengguna = u.id_pengguna AND pms.kode_materi = m.kode_materi', 'left')
            ->join('soal s', 's.kode_materi = m.kode_materi', 'left')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal', 'left')
            ->join('jawaban_siswa js', 'js.id_pengguna = u.id_pengguna AND js.kode_soal = s.kode_soal', 'left')
            ->where('u.role', 'Siswa');

        if ($kode_kelas) {
            $this->db->where('u.kode_kelas', $kode_kelas);
        }

        if ($kode_mapel) {
            $this->db->where('mo.kode_mapel', $kode_mapel);
        }

        if ($keyword) {
            $this->db->like('u.nama', $keyword);
        }

        return $this->db
            ->group_by('u.id_pengguna')
            ->order_by('u.nama', 'ASC')
            ->get()
            ->result();
    }

    public function get_detail_laporan_siswa($id_pengguna)
    {
        $siswa = $this->db
            ->select('u.id_pengguna, u.nama, u.email, u.kode_kelas, k.nama_kelas')
            ->from('users u')
            ->join('kelas k', 'k.kode_kelas = u.kode_kelas', 'left')
            ->where('u.id_pengguna', $id_pengguna)
            ->where('u.role', 'Siswa')
            ->get()
            ->row();

        if (!$siswa) {
            return null;
        }

        $ringkasan = $this->db
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
                ) AS progress_persen,

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
            ->where('u.id_pengguna', $id_pengguna)
            ->get()
            ->row();

        $detail_materi = $this->db
            ->select('
                mo.kode_modul,
                mo.judul AS judul_modul,
                m.kode_materi,
                m.judul AS judul_materi,
                IFNULL(pms.status, "Belum Mulai") AS status,
                IFNULL(pms.halaman_terakhir, 0) AS halaman_terakhir,

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
                ) AS nilai
            ', false)
            ->from('users u')
            ->join('modul mo', 'mo.kode_kelas = u.kode_kelas', 'left')
            ->join('materi m', 'm.kode_modul = mo.kode_modul', 'left')
            ->join('progres_materi_siswa pms', 'pms.id_pengguna = u.id_pengguna AND pms.kode_materi = m.kode_materi', 'left')
            ->join('soal s', 's.kode_materi = m.kode_materi', 'left')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal', 'left')
            ->join('jawaban_siswa js', 'js.id_pengguna = u.id_pengguna AND js.kode_soal = s.kode_soal', 'left')
            ->where('u.id_pengguna', $id_pengguna)
            ->group_by('m.kode_materi')
            ->order_by('mo.kode_modul', 'ASC')
            ->order_by('m.kode_materi', 'ASC')
            ->get()
            ->result();

        return [
            'siswa' => $siswa,
            'ringkasan' => $ringkasan,
            'detail_materi' => $detail_materi
        ];
    }

    public function get_siswa($id_pengguna)
    {
        return $this->db
            ->select('
            u.id_pengguna,
            u.nama,
            u.email,
            k.kode_kelas,
            k.nama_kelas
        ')
            ->from('users u')
            ->join('kelas k', 'k.kode_kelas = u.kode_kelas', 'left')
            ->where('u.id_pengguna', $id_pengguna)
            ->get()
            ->row();
    }

    public function get_progress_materi($id_pengguna)
    {
        return $this->db
            ->select('
            m.kode_materi,
            m.judul,
            pms.status,
            pms.halaman_terakhir,
            pms.tanggal_mulai,
            pms.tanggal_selesai
        ')
            ->from('progres_materi_siswa pms')
            ->join('materi m', 'm.kode_materi = pms.kode_materi')
            ->where('pms.id_pengguna', $id_pengguna)
            ->order_by('m.kode_materi')
            ->get()
            ->result();
    }

    public function get_hasil_soal($id_pengguna)
    {
        return $this->db
            ->select('
            s.kode_soal,
            s.pertanyaan,
            js.jawaban AS jawaban_siswa,
            j.jawaban_benar,
            CASE
                WHEN js.jawaban = j.jawaban_benar
                THEN "Benar"
                ELSE "Salah"
            END AS status
        ', false)
            ->from('jawaban_siswa js')
            ->join('soal s', 's.kode_soal = js.kode_soal')
            ->join('jawaban j', 'j.kode_soal = s.kode_soal')
            ->where('js.id_pengguna', $id_pengguna)
            ->get()
            ->result();
    }

    public function get_kode_mapel_guru($id_pengguna)
    {
        $guru = $this->db->select('kode_mapel')->where('id_pengguna', $id_pengguna)->where('role', 'Guru')->get('users')->row();
        return $guru ? $guru->kode_mapel : null;
    }

    public function get_laporan_guru($id_pengguna, $kode_kelas = null)
    {
        $kode_mapel = $this->get_kode_mapel_guru($id_pengguna);
        if (!$kode_mapel) return null;

        $kelas = $this->db
            ->distinct()
            ->select('k.kode_kelas, k.nama_kelas')
            ->from('kelas k')
            ->join('modul mo', 'mo.kode_kelas = k.kode_kelas')
            ->where('mo.kode_mapel', $kode_mapel)
            ->order_by('k.nama_kelas', 'ASC')
            ->get()
            ->result();

        $performa_kelas = [];
        foreach ($kelas as $item) {
            if ($kode_kelas && $item->kode_kelas !== $kode_kelas) continue;

            $total_siswa = $this->db->where('role', 'Siswa')->where('kode_kelas', $item->kode_kelas)->count_all_results('users');
            $total_materi = $this->db
                ->from('materi m')->join('modul mo', 'mo.kode_modul = m.kode_modul')
                ->where('mo.kode_mapel', $kode_mapel)->where('mo.kode_kelas', $item->kode_kelas)
                ->count_all_results();
            $peserta = $this->db
                ->distinct()->select('pms.id_pengguna')->from('progres_materi_siswa pms')
                ->join('users u', 'u.id_pengguna = pms.id_pengguna')
                ->join('materi m', 'm.kode_materi = pms.kode_materi')
                ->join('modul mo', 'mo.kode_modul = m.kode_modul')
                ->where('u.kode_kelas', $item->kode_kelas)->where('mo.kode_mapel', $kode_mapel)
                ->get()->num_rows();
            $selesai = $this->db
                ->from('progres_materi_siswa pms')->join('users u', 'u.id_pengguna = pms.id_pengguna')
                ->join('materi m', 'm.kode_materi = pms.kode_materi')->join('modul mo', 'mo.kode_modul = m.kode_modul')
                ->where('u.kode_kelas', $item->kode_kelas)->where('mo.kode_mapel', $kode_mapel)->where('pms.status', 'Selesai')
                ->count_all_results();
            $nilai = $this->db
                ->select('ROUND(AVG(ns.nilai), 1) AS rata_rata', false)->from('nilai_siswa ns')
                ->join('users u', 'u.id_pengguna = ns.id_pengguna')->join('materi m', 'm.kode_materi = ns.kode_materi')
                ->join('modul mo', 'mo.kode_modul = m.kode_modul')
                ->where('u.kode_kelas', $item->kode_kelas)->where('mo.kode_mapel', $kode_mapel)->get()->row();

            $partisipasi = $total_siswa > 0 ? round($peserta / $total_siswa * 100) : 0;
            $penyelesaian = $total_siswa > 0 && $total_materi > 0 ? round($selesai / ($total_siswa * $total_materi) * 100) : 0;
            $rata_rata = (float) ($nilai->rata_rata ?? 0);
            $status = $rata_rata >= 80 && $penyelesaian >= 75 ? 'Optimal' : ($rata_rata < 60 || $penyelesaian < 50 ? 'Perlu Perhatian' : 'Normal');

            $performa_kelas[] = [
                'kode_kelas' => $item->kode_kelas, 'nama_kelas' => $item->nama_kelas,
                'total_siswa' => $total_siswa, 'partisipasi' => $partisipasi,
                'penyelesaian' => $penyelesaian, 'rata_rata_nilai' => $rata_rata, 'status' => $status
            ];
        }

        $summary = $this->get_ringkasan_guru($kode_mapel, $kode_kelas);
        return [
            'kode_mapel' => $kode_mapel,
            'kelas' => $kelas,
            'ringkasan' => $summary,
            'tren_mingguan' => $this->get_tren_mingguan($kode_mapel, $kode_kelas),
            'distribusi_nilai' => $this->get_distribusi_nilai($kode_mapel, $kode_kelas),
            'performa_kelas' => $performa_kelas
        ];
    }

    private function get_ringkasan_guru($kode_mapel, $kode_kelas = null)
    {
        $total_siswa_query = $this->db->distinct()->select('u.id_pengguna')->from('users u')
            ->join('modul mo', 'mo.kode_kelas = u.kode_kelas')->where('u.role', 'Siswa')->where('mo.kode_mapel', $kode_mapel);
        if ($kode_kelas) $total_siswa_query->where('u.kode_kelas', $kode_kelas);
        $total_siswa = $total_siswa_query->get()->num_rows();

        $target_query = $this->db->from('users u')->join('modul mo', 'mo.kode_kelas = u.kode_kelas')
            ->join('materi m', 'm.kode_modul = mo.kode_modul')->where('u.role', 'Siswa')->where('mo.kode_mapel', $kode_mapel);
        if ($kode_kelas) $target_query->where('u.kode_kelas', $kode_kelas);
        $total_target = $target_query->count_all_results();

        $selesai_query = $this->db->from('progres_materi_siswa pms')->join('users u', 'u.id_pengguna = pms.id_pengguna')
            ->join('materi m', 'm.kode_materi = pms.kode_materi')->join('modul mo', 'mo.kode_modul = m.kode_modul')
            ->where('mo.kode_mapel', $kode_mapel)->where('mo.kode_kelas = u.kode_kelas', null, false)->where('pms.status', 'Selesai');
        if ($kode_kelas) $selesai_query->where('u.kode_kelas', $kode_kelas);
        $selesai = $selesai_query->count_all_results();

        $nilai_query = $this->db->select('ROUND(AVG(ns.nilai), 1) AS rata_rata', false)->from('nilai_siswa ns')
            ->join('users u', 'u.id_pengguna = ns.id_pengguna')->join('materi m', 'm.kode_materi = ns.kode_materi')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul')->where('mo.kode_mapel', $kode_mapel);
        if ($kode_kelas) $nilai_query->where('u.kode_kelas', $kode_kelas);
        $nilai = $nilai_query->get()->row();

        $risiko_query = $this->db->select('ns.id_pengguna, AVG(ns.nilai) AS rata_rata', false)->from('nilai_siswa ns')
            ->join('users u', 'u.id_pengguna = ns.id_pengguna')->join('materi m', 'm.kode_materi = ns.kode_materi')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul')->where('mo.kode_mapel', $kode_mapel)->group_by('ns.id_pengguna')->having('AVG(ns.nilai) <', 60);
        if ($kode_kelas) $risiko_query->where('u.kode_kelas', $kode_kelas);
        $berisiko = $risiko_query->get()->num_rows();

        return [
            'penyelesaian_materi' => $total_target > 0 ? round($selesai / $total_target * 100) : 0,
            'rata_rata_nilai' => (float) ($nilai->rata_rata ?? 0),
            'siswa_berisiko' => $berisiko,
            'total_siswa' => $total_siswa
        ];
    }

    private function get_distribusi_nilai($kode_mapel, $kode_kelas = null)
    {
        $query = $this->db->select('ns.nilai')->from('nilai_siswa ns')->join('users u', 'u.id_pengguna = ns.id_pengguna')
            ->join('materi m', 'm.kode_materi = ns.kode_materi')->join('modul mo', 'mo.kode_modul = m.kode_modul')->where('mo.kode_mapel', $kode_mapel);
        if ($kode_kelas) $query->where('u.kode_kelas', $kode_kelas);
        $rows = $query->get()->result();
        $hasil = ['sangat_baik' => 0, 'baik' => 0, 'cukup' => 0, 'kurang' => 0];
        foreach ($rows as $row) {
            $nilai = (float) $row->nilai;
            if ($nilai >= 85) $hasil['sangat_baik']++;
            elseif ($nilai >= 70) $hasil['baik']++;
            elseif ($nilai >= 60) $hasil['cukup']++;
            else $hasil['kurang']++;
        }
        $total = count($rows);
        foreach ($hasil as $key => $jumlah) $hasil[$key] = $total > 0 ? round($jumlah / $total * 100) : 0;
        return $hasil;
    }

    private function get_tren_mingguan($kode_mapel, $kode_kelas = null)
    {
        $hasil = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = date('Y-m-d 00:00:00', strtotime('monday this week -' . $i . ' weeks'));
            $end = date('Y-m-d 23:59:59', strtotime($start . ' +6 days'));
            $query = $this->db->from('progres_materi_siswa pms')->join('users u', 'u.id_pengguna = pms.id_pengguna')
                ->join('materi m', 'm.kode_materi = pms.kode_materi')->join('modul mo', 'mo.kode_modul = m.kode_modul')
                ->where('mo.kode_mapel', $kode_mapel)->where('pms.status', 'Selesai')
                ->where('pms.tanggal_selesai >=', $start)->where('pms.tanggal_selesai <=', $end);
            if ($kode_kelas) $query->where('u.kode_kelas', $kode_kelas);
            $hasil[] = ['label' => 'M' . (6 - $i), 'jumlah' => $query->count_all_results()];
        }
        $maksimum = max(array_column($hasil, 'jumlah')) ?: 1;
        foreach ($hasil as &$item) $item['persentase'] = round($item['jumlah'] / $maksimum * 100);
        return $hasil;
    }
}
