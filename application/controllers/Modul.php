<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Modul extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Modul_model');
        $this->load->library('jwt');
    }

    private function guru_mapel()
    {
        $token = $this->jwt->get_token_from_request();
        $decoded = $token ? $this->jwt->verify($token) : false;
        if (!$decoded) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            exit;
        }

        return $this->Modul_model->get_kode_mapel_guru($decoded->id_pengguna);
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
        $method = $_SERVER['REQUEST_METHOD'];
        $kode_mapel_guru = $this->guru_mapel();

        if (!$kode_mapel_guru) {
            return $this->response_json(403, ['status' => false, 'message' => 'Akun guru belum memiliki mata pelajaran']);
        }

        if ($method === 'GET') {
            $kode_kelas = $this->input->get('kode_kelas');
            $modul = $this->Modul_model->get_all_with_relasi($kode_kelas, $kode_mapel_guru);

            return $this->response_json(200, [
                'status' => true,
                'message' => 'Data modul berhasil diambil',
                'data' => $modul
            ]);
        }

        if ($method === 'POST') {
            return $this->store();
        }

        return $this->response_json(405, [
            'status' => false,
            'message' => 'Method tidak diizinkan'
        ]);
    }

    public function detail($kode_modul)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $kode_mapel_guru = $this->guru_mapel();

        if (!$kode_mapel_guru || !$this->Modul_model->belongs_to_mapel($kode_modul, $kode_mapel_guru)) {
            return $this->response_json(404, ['status' => false, 'message' => 'Modul tidak ditemukan']);
        }

        if ($method === 'GET') {
            return $this->show($kode_modul);
        }

        if ($method === 'PUT') {
            return $this->update($kode_modul);
        }

        if ($method === 'DELETE') {
            return $this->delete($kode_modul);
        }

        return $this->response_json(405, [
            'status' => false,
            'message' => 'Method tidak diizinkan'
        ]);
    }

    private function show($kode_modul)
    {
        $modul = $this->Modul_model->get_by_id($kode_modul);

        if (!$modul) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Modul tidak ditemukan'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Detail modul berhasil diambil',
            'data' => $modul
        ]);
    }

    private function store()
    {
        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }

        $kode_mapel_guru = $this->guru_mapel();
        $input['kode_mapel'] = $kode_mapel_guru;

        if (
            empty($input['judul']) ||
            empty($input['deskripsi']) ||
            empty($input['kode_mapel']) ||
            empty($input['kode_kelas'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'judul, deskripsi, kode_mapel, dan kode_kelas wajib diisi'
            ]);
        }

        if (!$this->Modul_model->mapel_exists($input['kode_mapel'])) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode mapel tidak ditemukan'
            ]);
        }

        if (!$this->Modul_model->kelas_exists($input['kode_kelas'])) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode kelas tidak ditemukan'
            ]);
        }

        $data = [
            'kode_modul'     => $this->Modul_model->generate_kode_modul(),
            'judul'          => $input['judul'],
            'deskripsi'      => $input['deskripsi'],
            'tanggal_dibuat' => date('Y-m-d'),
            'kode_mapel'     => $input['kode_mapel'],
            'kode_kelas'     => $input['kode_kelas']
        ];

        $insert = $this->Modul_model->create($data);

        if (!$insert) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menambahkan modul'
            ]);
        }

        return $this->response_json(201, [
            'status' => true,
            'message' => 'Modul berhasil ditambahkan',
            'data' => $data
        ]);
    }

    private function update($kode_modul)
    {
        if (!$this->Modul_model->exists($kode_modul)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Modul tidak ditemukan'
            ]);
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }

        $input['kode_mapel'] = $this->guru_mapel();

        if (
            empty($input['judul']) ||
            empty($input['deskripsi']) ||
            empty($input['kode_mapel']) ||
            empty($input['kode_kelas'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'judul, deskripsi, kode_mapel, dan kode_kelas wajib diisi'
            ]);
        }

        if (!$this->Modul_model->mapel_exists($input['kode_mapel'])) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode mapel tidak ditemukan'
            ]);
        }

        if (!$this->Modul_model->kelas_exists($input['kode_kelas'])) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode kelas tidak ditemukan'
            ]);
        }

        $data = [
            'judul'      => $input['judul'],
            'deskripsi'  => $input['deskripsi'],
            'kode_mapel' => $input['kode_mapel'],
            'kode_kelas' => $input['kode_kelas']
        ];

        $update = $this->Modul_model->update($kode_modul, $data);

        if (!$update) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal memperbarui modul'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Modul berhasil diperbarui',
            'data' => array_merge(['kode_modul' => $kode_modul], $data)
        ]);
    }

    private function delete($kode_modul)
    {
        if (!$this->Modul_model->exists($kode_modul)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Modul tidak ditemukan'
            ]);
        }

        $delete = $this->Modul_model->delete($kode_modul);

        if (!$delete) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menghapus modul'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Modul berhasil dihapus'
        ]);
    }
}
