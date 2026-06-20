<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mapel extends \CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Mapel_model');
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
        if(empty($input['kode_mapel'])){
            $errors['kode_mapel'] = "Kode Mapel field is required";
        }

        if(empty($input['nama_mapel'])){
            $errors['nama_mapel'] = "Nama Mapel field is required";
        }
        return $errors;
    }

    private function validate_update($input)
    {
        $errors = [];
        if(empty($input['nama_mapel'])){
            $errors['nama_mapel'] = "Nama Mapel field is required";
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
     * Get all mapel data (no auth needed)
     * GET /api/mapel
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
                    
            $mapel = $this->Mapel_model->get_all($per_page, $offset);
            $total = $this->Mapel_model->count_all();

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Mata Pelajaran retrieved successfully',
                'data' => $mapel,
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
     * Get single mapel (no auth needed)
     * GET /api/mapel/{id}
     */
    public function show($id){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $mapel = $this->Mapel_model->get_by_id($id);
         
            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Mata Pelajaran retrieved successfully',
                'data' => $mapel
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
     * Create new Mapel (requires auth)
     * POST /api/mapel
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

            $mapel_data = [
                'kode_mapel' => htmlspecialchars($input['kode_mapel']),
                'nama_mapel' => htmlspecialchars($input['nama_mapel']),
            ];

            $insert = $this->Mapel_model->create($mapel_data);

            if (!$insert) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to create Mata Pelajaran'
                ]);
                exit;
            }

            http_response_code(201);
            echo json_encode([
                'status' => true,
                'message' => 'Mata Pelajaran created successfully',
                'data' => [
                    'kode_mapel' => $input['kode_mapel'],
                    'nama_mapel' => $input['nama_mapel'],
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
     * Update mapel (requires auth)
     * PUT /api/mapel/{id}
     */
    public function update($id){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try{
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $old_mapel = $this->Mapel_model->get_by_id($id);

            if(!$old_mapel){
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Mata Pelajaran not found'
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

            $mapel_data = [
                'nama_mapel' => htmlspecialchars($input['nama_mapel']),
            ];

            $update = $this->Mapel_model->update($id, $mapel_data);
            
            if(!$update){
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to update Mata Pelajaran'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Mata Pelajaran updated successfully',
                'data' => [
                    'kode_mapel' => $id,
                    'nama_mapel' => $input['nama_mapel'],
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
     * Delete mapel (requires auth)
     * DELETE /api/mapel/{id}
     */
    public function delete($id) {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $mapel = $this->Mapel_model->get_by_id($id);

            if(!$mapel){
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'Mata pelajaran not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $delete = $this->Mapel_model->delete($id);

            if (!$delete) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to delete Mata Pelajaran'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Mata Pelajaran deleted successfully'
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
?>