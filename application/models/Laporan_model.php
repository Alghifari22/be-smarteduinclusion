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
}
