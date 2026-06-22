<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Latihan extends \CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Soal_model', 'Materi_model']);
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

    public function detail($kode_materi)
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $data = $this->Soal_model->get_detail_latihan(
                $kode_materi,
                $decoded->id_pengguna
            );

            if (!$data) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Latihan not found'
                ]);
                exit;
            }

            echo json_encode([
                'status' => true,
                'message' => 'Detail latihan retrieved successfully',
                'data' => $data
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

    public function jawab()
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['kode_soal']) || empty($input['jawaban'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Kode soal dan jawaban wajib diisi'
                ]);
                exit;
            }

            $this->Soal_model->simpan_jawaban(
                $decoded->id_pengguna,
                $input['kode_soal'],
                $input['jawaban']
            );

            echo json_encode([
                'status' => true,
                'message' => 'Jawaban berhasil disimpan'
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

    public function progress_latihan($kode_materi)
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $progress = $this->Soal_model->get_progress_latihan(
                $decoded->id_pengguna,
                $kode_materi
            );

            echo json_encode([
                'status' => true,
                'message' => $progress
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

    public function update_progress_latihan($kode_materi)
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

            $total_halaman = $this->Soal_model->count_total_soal($kode_materi);

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

    public function selesai($kode_materi)
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if (!$decoded) {
                $this->unauthorized('Unauthorized');
            }

            $hasil = $this->Soal_model->hitung_nilai(
                $decoded->id_pengguna,
                $kode_materi
            );

            $this->Soal_model->simpan_nilai(
                $kode_materi,
                $hasil['nilai'],
                $decoded->id_pengguna
            );

            echo json_encode([
                'status' => true,
                'message' => 'Latihan selesai, nilai berhasil dihitung',
                'data' => $hasil
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
}

