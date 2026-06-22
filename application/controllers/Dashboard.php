<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends \CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Dashboard_model');
        $this->load->library('jwt');
    }

    /**
     * Dashboard Role Staff TU
     * POST /api/dashboard/staffTU
     * Need Token
     */
    public function staffTU()
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            // Get token from header
            $token = $this->jwt->get_token_from_request();

            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'No token provided'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Verify token
            $decoded = $this->jwt->verify($token);

            if (!$decoded) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Invalid or expired token'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $data = $this->Dashboard_model->get_dashboard_staffTU();
            $recent_activities = $this->Dashboard_model->get_recent_activities();
            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Dashboard Staff TU retrieved successfully',
                'data' => [
                    'statistics' => $data,
                    'recent_activities' => $recent_activities
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

    /**
     * Dashboard Role Siswa
     * POST /api/dashboard/siswa
     * Need Token
     */
    public function siswa(){
         header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        
        try{
            // Get token from header
            $token = $this->jwt->get_token_from_request();

            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'No token provided'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Verify token
            $decoded = $this->jwt->verify($token);

            if (!$decoded) {
                http_response_code(401);
                echo json_encode([
                    'status' => false,
                    'message' => 'Invalid or expired token'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $data = $this->Dashboard_model->get_dashboard_siswa($decoded->id_pengguna);
            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Dashboard Siswa retrieved successfully',
                'data' => $data,
            ]);
            exit;
        }catch(Exception $e){
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function guru()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (ob_get_length()) {
            ob_clean();
        }

        $id_pengguna = $this->input->get('id_pengguna');

        if (!$id_pengguna) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'id_pengguna wajib dikirim'
            ]);
            return;
        }

        $data = [
            'kode_mapel' => $this->Dashboard_model->get_guru_mapel($id_pengguna),
            'progress' => $this->Dashboard_model->get_progress_rata_kelas($id_pengguna),
            'siswa_tertinggal' => $this->Dashboard_model->get_total_siswa_tertinggal($id_pengguna),
            'kuis_dinilai' => $this->Dashboard_model->get_total_kuis_dinilai($id_pengguna),
            'daftar_siswa' => $this->Dashboard_model->get_daftar_siswa_progress($id_pengguna),
            'activities' => $this->Dashboard_model->get_recent_activities(5)
        ];

        echo json_encode([
            'status' => true,
            'message' => 'Dashboard Guru retrieved successfully',
            'data' => $data
        ]);
    }

    public function cari_siswa()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id_pengguna = $this->input->get('id_pengguna');
        $keyword = $this->input->get('keyword');

        if (!$id_pengguna) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'id_pengguna wajib dikirim'
            ]);
            return;
        }

        $data = $this->Dashboard_model->get_daftar_siswa_progress($id_pengguna, $keyword);

        http_response_code(200);
        echo json_encode([
            'status' => true,
            'message' => 'Data siswa berhasil dicari',
            'data' => $data
        ]);
    }
}
