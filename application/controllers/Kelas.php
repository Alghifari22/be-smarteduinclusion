<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kelas extends \CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Kelas_model');
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

    private function validate_insert($input)
    {
        $errors = [];
        if (!is_array($input)) {
            return [
                'input' => 'Request body tidak terbaca'
            ];
        }
        if(empty($input['kode_kelas'])){
            $errors['kode_kelas'] = "Kode Kelas field is required";
        }

        if(empty($input['nama_kelas'])){
            $errors['nama_kelas'] = "Nama Kelas field is required";
        }
        return $errors;
    }

    private function validate_update($input)
    {
        $errors = [];
        if(empty($input['nama_kelas'])){
            $errors['nama_kelas'] = "Nama Kelas field is required";
        }
        return $errors;
    }

    /**
     * Route handler - dispatches to appropriate method based on HTTP verb
     */
    public function handle($id = null)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            if ($id) {
                $this->show($id);
            } else {
                $this->index();
            }
        } elseif ($method === 'POST') {
            $this->store();
        } elseif ($method === 'PUT') {
            $this->update($id);
        } elseif ($method === 'DELETE') {
            $this->delete($id);
        } else {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => false,
                'message' => 'Method not allowed'
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Get all Kelas data (no auth needed)
     * GET /api/kelas
     */
    public function index(){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try{
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            // Get pagination params
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
            $offset = ($page - 1) * $per_page;
                    
            $Kelas = $this->Kelas_model->get_all($per_page, $offset);
            $total = $this->Kelas_model->count_all();

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Kelas retrieved successfully',
                'data' => $Kelas,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'last_page' => ceil($total / $per_page)
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

    /**
     * Get single Kelas (no auth needed)
     * GET /api/kelas/{id}
     */
    public function show($id){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $Kelas = $this->Kelas_model->get_by_id($id);
         
            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Kelas retrieved successfully',
                'data' => $Kelas
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
     * Create new Kelas (requires auth)
     * POST /api/kelas
     */
    public function store(){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        
        try {
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input && !empty($_POST)) {
                $input = $_POST;
            }

            // Validate input
            $errors = $this->validate_insert($input);

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
                exit;
            }

            $kelas_data = [
                'kode_kelas' => htmlspecialchars($input['kode_kelas']),
                'nama_kelas' => htmlspecialchars($input['nama_kelas']),
            ];

            $insert = $this->Kelas_model->create($kelas_data);

            if (!$insert) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to create Kelas'
                ]);
                exit;
            }

            http_response_code(201);
            echo json_encode([
                'status' => true,
                'message' => 'Kelas created successfully',
                'data' => [
                    'kode_kelas' => $input['kode_kelas'],
                    'nama_kelas' => $input['nama_kelas'],
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
     * Update Kelas (requires auth)
     * PUT /api/kelas/{id}
     */
    public function update($id){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try{
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $old_Kelas = $this->Kelas_model->get_by_id($id);

            if(!$old_Kelas){
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Kelas not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input && !empty($_POST)) {
                $input = $_POST;
            }

            // Validate input
            $errors = $this->validate_update($input);

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
                exit;
            }

            $kelas_data = [
                'nama_kelas' => htmlspecialchars($input['nama_kelas']),
            ];

            $update = $this->Kelas_model->update($id, $kelas_data);
            
            if(!$update){
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to update Kelas'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Kelas updated successfully',
                'data' => [
                    'kode_kelas' => $id,
                    'nama_kelas' => $input['nama_kelas'],
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

    /**
     * Delete Kelas (requires auth)
     * DELETE /api/kelas/{id}
     */
    public function delete($id) {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $Kelas = $this->Kelas_model->get_by_id($id);

            if(!$Kelas){
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Kelas not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $delete = $this->Kelas_model->delete($id);

            if (!$delete) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to delete Kelas'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Kelas deleted successfully'
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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