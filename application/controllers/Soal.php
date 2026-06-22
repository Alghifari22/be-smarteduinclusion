<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Soal_model');
        $this->load->library('jwt');
    }

    private function authenticated_guru()
    {
        $token = $this->jwt->get_token_from_request();
        $decoded = $token ? $this->jwt->verify($token) : false;
        if (!$decoded) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if (!$this->Soal_model->get_kode_mapel_guru($decoded->id_pengguna)) {
            $this->response_json(403, ['status' => false, 'message' => 'Akun guru belum memiliki mata pelajaran'])->_display();
            exit;
        }
        return $decoded;
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
        $decoded = $this->authenticated_guru();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $kode_materi = $this->input->get('kode_materi');

            return $this->response_json(200, [
                'status' => true,
                'message' => 'Data soal berhasil diambil',
                'data' => $this->Soal_model->get_all_for_guru($decoded->id_pengguna, $kode_materi)
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }

        return $this->response_json(405, [
            'status' => false,
            'message' => 'Method tidak diizinkan'
        ]);
    }

    public function detail($kode_soal)
    {
        $decoded = $this->authenticated_guru();
        if (!$this->Soal_model->soal_belongs_to_guru($kode_soal, $decoded->id_pengguna)) {
            return $this->response_json(404, ['status' => false, 'message' => 'Soal tidak ditemukan']);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->show($kode_soal);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            return $this->update($kode_soal);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            return $this->delete($kode_soal);
        }

        return $this->response_json(405, [
            'status' => false,
            'message' => 'Method tidak diizinkan'
        ]);
    }

    private function show($kode_soal)
    {
        $soal = $this->Soal_model->get_by_id_with_jawaban($kode_soal);

        if (!$soal) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Soal tidak ditemukan'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Detail soal berhasil diambil',
            'data' => $soal
        ]);
    }

    private function store()
    {
        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }
        $decoded = $this->authenticated_guru();

        if (empty($input['pertanyaan']) || empty($input['kode_materi'])) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'pertanyaan dan kode_materi wajib diisi'
            ]);
        }

        if (!$this->Soal_model->materi_belongs_to_guru($input['kode_materi'], $decoded->id_pengguna)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode materi tidak ditemukan'
            ]);
        }

        $data_soal = [
            'kode_soal'   => $this->Soal_model->generate_kode_soal(),
            'pertanyaan'  => $input['pertanyaan'],
            'gambar'      => $input['gambar'] ?? null,
            'kode_materi' => $input['kode_materi']
        ];

        $has_jawaban = isset($input['opsi_a']) || isset($input['opsi_b']) || isset($input['opsi_c']) || isset($input['jawaban_benar']);
        if ($has_jawaban) {
            if (empty($input['opsi_a']) || empty($input['opsi_b']) || empty($input['opsi_c']) || !in_array(strtoupper($input['jawaban_benar'] ?? ''), ['A', 'B', 'C'])) {
                return $this->response_json(400, ['status' => false, 'message' => 'Opsi A, B, C, dan jawaban benar wajib diisi']);
            }
            $data_jawaban = [
                'kode_jawaban' => $this->Soal_model->generate_kode_jawaban(),
                'opsi_a' => $input['opsi_a'], 'opsi_b' => $input['opsi_b'], 'opsi_c' => $input['opsi_c'],
                'jawaban_benar' => strtoupper($input['jawaban_benar']), 'kode_soal' => $data_soal['kode_soal']
            ];
            $insert_soal = $this->Soal_model->create_complete($data_soal, $data_jawaban);
        } else {
            $insert_soal = $this->Soal_model->create_soal($data_soal);
        }

        if (!$insert_soal) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menambahkan soal'
            ]);
        }

        return $this->response_json(201, [
            'status' => true,
            'message' => 'Soal berhasil ditambahkan',
            'data' => array_merge($data_soal, $has_jawaban ? $data_jawaban : [])
        ]);
    }

    private function update($kode_soal)
    {
        if (!$this->Soal_model->soal_exists($kode_soal)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Soal tidak ditemukan'
            ]);
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }
        $decoded = $this->authenticated_guru();

        if (empty($input['pertanyaan']) || empty($input['kode_materi'])) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'pertanyaan dan kode_materi wajib diisi'
            ]);
        }

        if (!$this->Soal_model->materi_belongs_to_guru($input['kode_materi'], $decoded->id_pengguna)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Kode materi tidak ditemukan'
            ]);
        }

        $data_soal = [
            'pertanyaan'  => $input['pertanyaan'],
            'gambar'      => $input['gambar'] ?? null,
            'kode_materi' => $input['kode_materi']
        ];

        $has_jawaban = isset($input['opsi_a']) || isset($input['opsi_b']) || isset($input['opsi_c']) || isset($input['jawaban_benar']);
        if ($has_jawaban) {
            if (empty($input['opsi_a']) || empty($input['opsi_b']) || empty($input['opsi_c']) || !in_array(strtoupper($input['jawaban_benar'] ?? ''), ['A', 'B', 'C'])) {
                return $this->response_json(400, ['status' => false, 'message' => 'Opsi A, B, C, dan jawaban benar wajib diisi']);
            }
            $data_jawaban = [
                'opsi_a' => $input['opsi_a'], 'opsi_b' => $input['opsi_b'], 'opsi_c' => $input['opsi_c'],
                'jawaban_benar' => strtoupper($input['jawaban_benar'])
            ];
            $update = $this->Soal_model->update_complete($kode_soal, $data_soal, $data_jawaban);
        } else {
            $update = $this->Soal_model->update_soal($kode_soal, $data_soal);
        }

        if (!$update) {
            return $this->response_json(500, ['status' => false, 'message' => 'Gagal memperbarui soal']);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Soal berhasil diperbarui',
            'data' => array_merge(['kode_soal' => $kode_soal], $data_soal, $has_jawaban ? $data_jawaban : [])
        ]);
    }

    private function delete($kode_soal)
    {
        if (!$this->Soal_model->soal_exists($kode_soal)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Soal tidak ditemukan'
            ]);
        }

        $delete = $this->Soal_model->delete_soal($kode_soal);

        if (!$delete) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menghapus soal'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Soal berhasil dihapus'
        ]);
    }

    public function jawaban($kode_soal)
    {
        $decoded = $this->authenticated_guru();
        if (!$this->Soal_model->soal_belongs_to_guru($kode_soal, $decoded->id_pengguna)) {
            return $this->response_json(404, ['status' => false, 'message' => 'Soal tidak ditemukan']);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $jawaban = $this->Soal_model->get_jawaban_by_soal($kode_soal);

            if (!$jawaban) {
                return $this->response_json(404, [
                    'status' => false,
                    'message' => 'Jawaban untuk soal ini belum tersedia'
                ]);
            }

            return $this->response_json(200, [
                'status' => true,
                'message' => 'Detail jawaban berhasil diambil',
                'data' => $jawaban
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store_jawaban($kode_soal);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            return $this->update_jawaban($kode_soal);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            return $this->delete_jawaban($kode_soal);
        }

        return $this->response_json(405, [
            'status' => false,
            'message' => 'Method tidak diizinkan'
        ]);
    }

    public function gambar($kode_soal)
    {
        $decoded = $this->authenticated_guru();
        if (!$this->Soal_model->soal_belongs_to_guru($kode_soal, $decoded->id_pengguna)) {
            return $this->response_json(404, ['status' => false, 'message' => 'Soal tidak ditemukan']);
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->response_json(405, ['status' => false, 'message' => 'Method tidak diizinkan']);
        }
        if (empty($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            return $this->response_json(400, ['status' => false, 'message' => 'File gambar wajib dipilih']);
        }

        $file = $_FILES['gambar'];
        if ($file['size'] > 5 * 1024 * 1024) {
            return $this->response_json(400, ['status' => false, 'message' => 'Ukuran gambar maksimal 5 MB']);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            return $this->response_json(400, ['status' => false, 'message' => 'Format gambar harus JPG, PNG, atau WEBP']);
        }

        $directory = FCPATH . 'uploads/soal/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            return $this->response_json(500, ['status' => false, 'message' => 'Folder upload tidak dapat dibuat']);
        }
        $filename = strtolower($kode_soal) . '-' . uniqid() . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . $filename)) {
            return $this->response_json(500, ['status' => false, 'message' => 'Gagal mengunggah gambar']);
        }

        $soal = $this->Soal_model->get_by_id_with_jawaban($kode_soal);
        $path = 'uploads/soal/' . $filename;
        $this->Soal_model->update_soal($kode_soal, ['gambar' => $path]);
        if (!empty($soal->gambar) && strpos($soal->gambar, 'uploads/soal/') === 0) {
            $old_file = FCPATH . $soal->gambar;
            if (is_file($old_file)) unlink($old_file);
        }

        return $this->response_json(200, ['status' => true, 'message' => 'Gambar soal berhasil disimpan', 'data' => ['gambar' => $path]]);
    }

    private function store_jawaban($kode_soal)
    {
        if (!$this->Soal_model->soal_exists($kode_soal)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Soal tidak ditemukan'
            ]);
        }

        if ($this->Soal_model->jawaban_exists_by_soal($kode_soal)) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'Soal ini sudah memiliki jawaban'
            ]);
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }

        if (
            empty($input['opsi_a']) ||
            empty($input['opsi_b']) ||
            empty($input['opsi_c']) ||
            empty($input['jawaban_benar'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'opsi_a, opsi_b, opsi_c, dan jawaban_benar wajib diisi'
            ]);
        }

        $jawaban_benar = strtoupper($input['jawaban_benar']);

        if (!in_array($jawaban_benar, ['A', 'B', 'C'])) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'jawaban_benar hanya boleh A, B, atau C'
            ]);
        }

        $data = [
            'kode_jawaban'  => $this->Soal_model->generate_kode_jawaban(),
            'opsi_a'        => $input['opsi_a'],
            'opsi_b'        => $input['opsi_b'],
            'opsi_c'        => $input['opsi_c'],
            'jawaban_benar' => $jawaban_benar,
            'kode_soal'     => $kode_soal
        ];

        $insert = $this->Soal_model->create_jawaban($data);

        if (!$insert) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menambahkan jawaban'
            ]);
        }

        return $this->response_json(201, [
            'status' => true,
            'message' => 'Jawaban berhasil ditambahkan',
            'data' => $data
        ]);
    }

    private function update_jawaban($kode_soal)
    {
        if (!$this->Soal_model->soal_exists($kode_soal)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Soal tidak ditemukan'
            ]);
        }

        if (!$this->Soal_model->jawaban_exists_by_soal($kode_soal)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Jawaban untuk soal ini belum tersedia'
            ]);
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            $input = $this->input->post();
        }

        if (
            empty($input['opsi_a']) ||
            empty($input['opsi_b']) ||
            empty($input['opsi_c']) ||
            empty($input['jawaban_benar'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'opsi_a, opsi_b, opsi_c, dan jawaban_benar wajib diisi'
            ]);
        }

        $jawaban_benar = strtoupper($input['jawaban_benar']);

        if (!in_array($jawaban_benar, ['A', 'B', 'C'])) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'jawaban_benar hanya boleh A, B, atau C'
            ]);
        }

        $data = [
            'opsi_a'        => $input['opsi_a'],
            'opsi_b'        => $input['opsi_b'],
            'opsi_c'        => $input['opsi_c'],
            'jawaban_benar' => $jawaban_benar
        ];

        $update = $this->Soal_model->update_jawaban_by_soal($kode_soal, $data);

        if (!$update) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal memperbarui jawaban'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Jawaban berhasil diperbarui',
            'data' => array_merge(['kode_soal' => $kode_soal], $data)
        ]);
    }

    private function delete_jawaban($kode_soal)
    {
        if (!$this->Soal_model->jawaban_exists_by_soal($kode_soal)) {
            return $this->response_json(404, [
                'status' => false,
                'message' => 'Jawaban untuk soal ini belum tersedia'
            ]);
        }

        $delete = $this->Soal_model->delete_jawaban_by_soal($kode_soal);

        if (!$delete) {
            return $this->response_json(500, [
                'status' => false,
                'message' => 'Gagal menghapus jawaban'
            ]);
        }

        return $this->response_json(200, [
            'status' => true,
            'message' => 'Jawaban berhasil dihapus'
        ]);
    }
}
