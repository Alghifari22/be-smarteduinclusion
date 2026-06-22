<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Laporan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Laporan_model');
        $this->load->library('jwt');
    }

    private function authenticate()
    {
        $token = $this->jwt->get_token_from_request();
        return $token ? $this->jwt->verify($token) : false;
    }

    private function response_json($status_code, $data)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_code)
            ->set_output(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->response_json(405, [
                'status' => false,
                'message' => 'Method tidak diizinkan'
            ]);
        }

        $kode_kelas = $this->input->get('kode_kelas');
        $kode_mapel = $this->input->get('kode_mapel');
        $keyword = $this->input->get('keyword');

        $data = $this->Laporan_model->get_laporan_siswa($kode_kelas, $kode_mapel, $keyword);

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Data laporan berhasil diambil',
            'data' => $data
        ]);
    }

    public function siswa($id_pengguna)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->response_json(405, [
                'status' => false,
                'message' => 'Method tidak diizinkan'
            ]);
        }

        $data = $this->Laporan_model->get_detail_laporan_siswa($id_pengguna);

        if (!$data) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Data laporan siswa tidak ditemukan'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Detail laporan siswa berhasil diambil',
            'data' => $data
        ]);
    }

    public function guru()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->response_json(405, ['status' => false, 'message' => 'Method tidak diizinkan']);
        }
        $decoded = $this->authenticate();
        if (!$decoded) {
            return $this->response_json(401, ['status' => false, 'message' => 'Unauthorized']);
        }

        $data = $this->Laporan_model->get_laporan_guru($decoded->id_pengguna, $this->input->get('kode_kelas'));
        if (!$data) {
            return $this->response_json(403, ['status' => false, 'message' => 'Akun guru belum memiliki mata pelajaran']);
        }
        return $this->response_json(200, ['status' => true, 'message' => 'Laporan performa berhasil diambil', 'data' => $data]);
    }

    public function laporan_anak()
    {
        $id_pengguna = $this->input->get('id_pengguna');

        if (!$id_pengguna) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'id_pengguna wajib dikirim'
            ]);
        }

        $data = [
            'siswa' => $this->Dashboard_model->get_siswa($id_pengguna),
            'ringkasan' => $this->Dashboard_model->get_ringkasan($id_pengguna),
            'progress_materi' => $this->Dashboard_model->get_progress_materi($id_pengguna),
            'hasil_soal' => $this->Dashboard_model->get_hasil_soal($id_pengguna)
        ];

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Laporan anak berhasil diambil',
            'data' => $data
        ]);
    }
}
