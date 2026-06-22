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

    public function get_all_for_guru($id_pengguna, $kode_modul = null)
    {
        $kode_mapel = $this->get_kode_mapel_guru($id_pengguna);
        if (!$kode_mapel) return [];

        $this->db
            ->select('m.kode_materi, m.judul, m.deskripsi, m.kode_modul, mo.judul AS judul_modul, mo.kode_mapel, mp.nama_mapel, mo.kode_kelas, k.nama_kelas, COUNT(dm.kode_detail_materi) AS jumlah_halaman')
            ->from('materi m')
            ->join('modul mo', 'mo.kode_modul = m.kode_modul')
            ->join('mata_pelajaran mp', 'mp.kode_mapel = mo.kode_mapel', 'left')
            ->join('kelas k', 'k.kode_kelas = mo.kode_kelas', 'left')
            ->join('detail_materi dm', 'dm.kode_materi = m.kode_materi', 'left')
            ->where('mo.kode_mapel', $kode_mapel)
            ->group_by('m.kode_materi');

        if ($kode_modul) $this->db->where('m.kode_modul', $kode_modul);
        return $this->db->order_by('m.kode_materi', 'DESC')->get()->result();
    }

    public function get_kode_mapel_guru($id_pengguna)
    {
        $guru = $this->db->select('kode_mapel')->where('id_pengguna', $id_pengguna)->where('role', 'Guru')->get('users')->row();
        return $guru ? $guru->kode_mapel : null;
    }

    public function modul_belongs_to_guru($kode_modul, $id_pengguna)
    {
        $kode_mapel = $this->get_kode_mapel_guru($id_pengguna);
        return $kode_mapel && $this->db->where('kode_modul', $kode_modul)->where('kode_mapel', $kode_mapel)->count_all_results('modul') > 0;
    }

    public function materi_belongs_to_guru($kode_materi, $id_pengguna)
    {
        $kode_mapel = $this->get_kode_mapel_guru($id_pengguna);
        return $kode_mapel && $this->db->from('materi m')->join('modul mo', 'mo.kode_modul = m.kode_modul')->where('m.kode_materi', $kode_materi)->where('mo.kode_mapel', $kode_mapel)->count_all_results() > 0;
    }

    public function get_detail_pages($kode_materi)
    {
        return $this->db->where('kode_materi', $kode_materi)->order_by('urutan', 'ASC')->get('detail_materi')->result();
    }

    public function get_detail_page($kode_detail)
    {
        return $this->db->where('kode_detail_materi', $kode_detail)->get('detail_materi')->row();
    }

    public function create_detail($data)
    {
        return $this->db->insert('detail_materi', $data);
    }

    public function update_detail($kode_detail, $data)
    {
        return $this->db->where('kode_detail_materi', $kode_detail)->update('detail_materi', $data);
    }

    public function delete_detail($kode_detail)
    {
        return $this->db->where('kode_detail_materi', $kode_detail)->delete('detail_materi');
    }

    public function generate_kode_detail_materi()
    {
        $last = $this->db->select('kode_detail_materi')->order_by('kode_detail_materi', 'DESC')->limit(1)->get('detail_materi')->row();
        $angka = $last ? (int) substr($last->kode_detail_materi, 4) + 1 : 1;
        return 'DTL-' . str_pad($angka, 3, '0', STR_PAD_LEFT);
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
