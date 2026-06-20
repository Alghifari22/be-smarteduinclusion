<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends \CI_Controller {
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
    public function staffTU(){
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
        }catch(Exception $e){   
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
?>