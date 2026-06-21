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
}