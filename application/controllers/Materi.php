<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Materi extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Materi_model');
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

        if ($method === 'GET') {
            $kode_modul = $this->input->get('kode_modul');

            $materi = $this->Materi_model->get_all_with_modul($kode_modul);

            return $this->response_json(200, [
                'status' => true,
                'message' => 'Data materi berhasil diambil',
                'data' => $materi
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

    public function detail($kode_materi)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            return $this->show($kode_materi);
        }

        if ($method === 'PUT') {
            return $this->update($kode_materi);
        }

        if ($method === 'DELETE') {
            return $this->delete($kode_materi);
        }

        return $this->response_json(405, [
            'status' => false,
            'message' => 'Method tidak diizinkan'
        ]);
    }

    private function show($kode_materi)
    {
        $materi = $this->Materi_model->get_by_id($kode_materi);

        if (!$materi) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Materi tidak ditemukan'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Detail materi berhasil diambil',
            'data' => $materi
        ]);
    }

    private function store()
    {
        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }

        if (
            empty($input['judul']) ||
            empty($input['kode_modul'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'judul dan kode_modul wajib diisi'
            ]);
        }

        if (!$this->Materi_model->modul_exists($input['kode_modul'])) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode modul tidak ditemukan'
            ]);
        }

        $data = [
            'kode_materi' => $this->Materi_model->generate_kode_materi(),
            'judul'       => $input['judul'],
            'deskripsi'   => $input['deskripsi'] ?? null,
            'kode_modul'  => $input['kode_modul']
        ];

        $insert = $this->Materi_model->create($data);

        if (!$insert) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menambahkan materi'
            ]);
        }

        return $this->response_json(201, [
            'status' => true,
            'message' => 'Materi berhasil ditambahkan',
            'data' => $data
        ]);
    }

    private function update($kode_materi)
    {
        if (!$this->Materi_model->exists($kode_materi)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Materi tidak ditemukan'
            ]);
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }

        if (
            empty($input['judul']) ||
            empty($input['kode_modul'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'judul dan kode_modul wajib diisi'
            ]);
        }

        if (!$this->Materi_model->modul_exists($input['kode_modul'])) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode modul tidak ditemukan'
            ]);
        }

        $data = [
            'judul'      => $input['judul'],
            'deskripsi'  => $input['deskripsi'] ?? null,
            'kode_modul' => $input['kode_modul']
        ];

        $update = $this->Materi_model->update($kode_materi, $data);

        if (!$update) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal memperbarui materi'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Materi berhasil diperbarui',
            'data' => array_merge(['kode_materi' => $kode_materi], $data)
        ]);
    }

    private function delete($kode_materi)
    {
        if (!$this->Materi_model->exists($kode_materi)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Materi tidak ditemukan'
            ]);
        }

        $delete = $this->Materi_model->delete($kode_materi);

        if (!$delete) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menghapus materi'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Materi berhasil dihapus'
        ]);
    }
}
