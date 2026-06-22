<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Materi extends \CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Materi_model');
        $this->load->library('jwt');
    }

    private function authenticate()
    {
        $token = $this->jwt->get_token_from_request();

        if (!$token) {
            return false;
        }

        return $this->jwt->verify($token);
    }

    private function authenticated_user()
    {
        $decoded = $this->authenticate();
        if (!$decoded) {
            $this->unauthorized('Unauthorized: Invalid or missing token');
        }
        return $decoded;
    }

    private function unauthorized($message = 'Unauthorized')
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => false,
            'message' => $message
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function materi_siswa()
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        try {
           $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            // Get pagination params
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
            $offset = ($page - 1) * $per_page;

            $kode_mapel = $this->input->get('kode_mapel');
            $total = $this->Materi_model->count_all_materi($decoded->id_pengguna);

            $data = $this->Materi_model->get_materi_siswa(
                $decoded->id_pengguna,
                $kode_mapel,
                $per_page,
                $offset
            );

            echo json_encode([
                'status' => true,
                'message' => 'Materi retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'last_page' => ceil($total / $per_page)
                ]
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function soal_siswa(){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
           $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            // Get pagination params
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
            $offset = ($page - 1) * $per_page;

            $kode_mapel = $this->input->get('kode_mapel');
            $total = $this->Materi_model->count_materi_yang_punya_soal($decoded->id_pengguna);

            $data = $this->Materi_model->get_materi_yang_punya_soal(
                $decoded->id_pengguna,
                $kode_mapel,
                $per_page,
                $offset
            );

            echo json_encode([
                'status' => true,
                'message' => 'Latihan soal retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'last_page' => ceil($total / $per_page)
                ]
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function detail_materi($kode_materi){
        header('Content-Type: application/json; charset=utf-8');

        try {
            $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $id_siswa = $decoded->id_pengguna;

            $data = $this->Materi_model->get_detail_materi($kode_materi, $id_siswa);

            if (!$data) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Materi not found'
                ]);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Detail materi retrieved successfully',
                'data' => $data
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function update_progress($kode_materi)
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $halaman_terakhir = (int)($input['halaman_terakhir'] ?? 1);

            $total_halaman = $this->Materi_model->count_detail_materi($kode_materi);

            if($halaman_terakhir > $total_halaman){
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => "Invalid page number. Total halaman hanya $total_halaman.",
                    'total_halaman' => $total_halaman,
                    'halaman_dikirim' => $halaman_terakhir
                ]);
                exit;
            }

            $update = $this->Materi_model->update_progress_materi(
                $decoded->id_pengguna,
                $kode_materi,
                $halaman_terakhir,
                $total_halaman
            );

            echo json_encode([
                'status' => $update ? true : false,
                'message' => $update ? 'Progress updated successfully' : 'Failed to update progress'
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
            exit;
        }
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
        $decoded = $this->authenticated_user();

        if (!$this->Materi_model->get_kode_mapel_guru($decoded->id_pengguna)) {
            return $this->response_json(403, ['status' => false, 'message' => 'Akun guru belum memiliki mata pelajaran']);
        }

        if ($method === 'GET') {
            $kode_modul = $this->input->get('kode_modul');
            $materi = $this->Materi_model->get_all_for_guru($decoded->id_pengguna, $kode_modul);

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
        $decoded = $this->authenticated_user();

        if (!$this->Materi_model->materi_belongs_to_guru($kode_materi, $decoded->id_pengguna)) {
            return $this->response_json(404, ['status' => false, 'message' => 'Materi tidak ditemukan']);
        }

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

        $decoded = $this->authenticated_user();

        if (
            empty($input['judul']) ||
            empty($input['kode_modul'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'judul dan kode_modul wajib diisi'
            ]);
        }

        if (!$this->Materi_model->modul_belongs_to_guru($input['kode_modul'], $decoded->id_pengguna)) {
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

        $decoded = $this->authenticated_user();

        if (
            empty($input['judul']) ||
            empty($input['kode_modul'])
        ) {
            return $this->response_json(400, [
                'status' => false,
                'message' => 'judul dan kode_modul wajib diisi'
            ]);
        }

        if (!$this->Materi_model->modul_belongs_to_guru($input['kode_modul'], $decoded->id_pengguna)) {
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

    public function pages($kode_materi)
    {
        $decoded = $this->authenticated_user();
        if (!$this->Materi_model->materi_belongs_to_guru($kode_materi, $decoded->id_pengguna)) {
            return $this->response_json(404, ['status' => false, 'message' => 'Materi tidak ditemukan']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->response_json(200, [
                'status' => true,
                'message' => 'Detail materi berhasil diambil',
                'data' => $this->Materi_model->get_detail_pages($kode_materi)
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            if (empty($input['judul']) || empty($input['isi']) || empty($input['urutan'])) {
                return $this->response_json(400, ['status' => false, 'message' => 'judul, isi, dan urutan wajib diisi']);
            }

            $data = [
                'kode_detail_materi' => $this->Materi_model->generate_kode_detail_materi(),
                'kode_materi' => $kode_materi,
                'urutan' => (int) $input['urutan'],
                'judul' => trim($input['judul']),
                'isi' => $input['isi'],
                'gambar' => isset($input['gambar']) && $input['gambar'] !== '' ? $input['gambar'] : null
            ];

            if (!$this->Materi_model->create_detail($data)) {
                return $this->response_json(500, ['status' => false, 'message' => 'Gagal menambahkan detail materi']);
            }
            return $this->response_json(201, ['status' => true, 'message' => 'Detail materi berhasil ditambahkan', 'data' => $data]);
        }

        return $this->response_json(405, ['status' => false, 'message' => 'Method tidak diizinkan']);
    }

    public function page_detail($kode_detail)
    {
        $decoded = $this->authenticated_user();
        $detail = $this->Materi_model->get_detail_page($kode_detail);
        if (!$detail || !$this->Materi_model->materi_belongs_to_guru($detail->kode_materi, $decoded->id_pengguna)) {
            return $this->response_json(404, ['status' => false, 'message' => 'Detail materi tidak ditemukan']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            if (empty($input['judul']) || empty($input['isi']) || empty($input['urutan'])) {
                return $this->response_json(400, ['status' => false, 'message' => 'judul, isi, dan urutan wajib diisi']);
            }
            $data = [
                'urutan' => (int) $input['urutan'],
                'judul' => trim($input['judul']),
                'isi' => $input['isi'],
                'gambar' => isset($input['gambar']) && $input['gambar'] !== '' ? $input['gambar'] : null
            ];
            $this->Materi_model->update_detail($kode_detail, $data);
            return $this->response_json(200, ['status' => true, 'message' => 'Detail materi berhasil diperbarui']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $this->Materi_model->delete_detail($kode_detail);
            return $this->response_json(200, ['status' => true, 'message' => 'Detail materi berhasil dihapus']);
        }

        return $this->response_json(405, ['status' => false, 'message' => 'Method tidak diizinkan']);
    }
}
