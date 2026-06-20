<?php
// defined('BASEPATH') or exit('No direct script access allowed');

class Users extends \CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['User_model', 'Mapel_model', 'Kelas_model']);
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

    private function validate_insert(&$input)
    {
        $errors = [];
        $role = $input['role'] ?? '';

        if (empty($role)) {
            $errors['role'] = "Role is required!";
        }

        if ($role == 'Guru' && empty($input['id_pengguna'])) {
            $errors['id_pengguna'] = "NIP field is required";
        } elseif ($role == 'Siswa' && empty($input['id_pengguna'])) {
            $errors['id_pengguna'] = "NISN field is required";
        } elseif ($role == 'Staff TU') {
            $input['id_pengguna'] = $this->User_model->generate_id_pengguna('STF');
        } elseif ($role == 'Orang Tua') {
            $input['id_pengguna'] = $this->User_model->generate_id_pengguna('ORT');
        }

        if ($role == 'Guru' && empty($input['kode_mapel'])) {
            $errors['kode_mapel'] = "Kode Mata Pelajaran is required for Guru";
        }

        if ($role == 'Siswa' && empty($input['kode_kelas'])) {
            $errors['kode_kelas'] = "Kode Kelas is required for Siswa";
        }

        if ($role == 'Guru' && !empty($input['kode_mapel'])) {
            if (!$this->Mapel_model->exists($input['kode_mapel'])) {
                $errors['kode_mapel'] = "Kode Mata Pelajaran not found";
            }
        }

        if ($role == 'Siswa' && !empty($input['kode_kelas'])) {
            if (!$this->Kelas_model->exists($input['kode_kelas'])) {
                $errors['kode_kelas'] = "Kode Kelas not found";
            }
        }
        if (empty($input['nama'])) $errors['nama'] = "Nama is required!";

        if (empty($input['email'])) {
            $errors['email'] = "Email is required!";
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format!";
        } elseif ($this->User_model->email_exists($input['email'])) {
            $errors['email'] = "Email already registered!";
        }

        if (empty($input['password'])) {
            $errors['password'] = "Password is required!";
        } elseif (strlen($input['password']) < 6) {
            $errors['password'] = "Password must be at least 6 characters";
        }

        if (empty($input['tempat_lahir'])) $errors['tempat_lahir'] = "Tempat Lahir is required!";
        if (empty($input['tanggal_lahir'])) $errors['tanggal_lahir'] = "Tanggal Lahir is required!";
        if (empty($input['jenis_kelamin'])) $errors['jenis_kelamin'] = "Jenis Kelamin is required!";
        if (empty($input['alamat'])) $errors['alamat'] = "Alamat is required!";

        return $errors;
    }

    private function validate_update($input, $id_pengguna)
    {
        $errors = [];

        $role = $input['role'] ?? '';
        if (empty($input['nama'])) $errors['nama'] = "Nama is required!";

        if (empty($input['email'])) {
            $errors['email'] = "Email is required!";
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format!";
        } elseif ($this->User_model->email_exists_except($input['email'], $id_pengguna)) {
            $errors['email'] = "Email already registered!";
        }

        if ($role == 'Guru' && empty($input['kode_mapel'])) {
            $errors['kode_mapel'] = "Kode Mata Pelajaran is required for Guru";
        }

        if ($role == 'Siswa' && empty($input['kode_kelas'])) {
            $errors['kode_kelas'] = "Kode Kelas is required for Siswa";
        }

        if ($role == 'Guru' && !empty($input['kode_mapel'])) {
            if (!$this->Mapel_model->exists($input['kode_mapel'])) {
                $errors['kode_mapel'] = "Kode Mata Pelajaran not found";
            }
        }

        if ($role == 'Siswa' && !empty($input['kode_kelas'])) {
            if (!$this->Kelas_model->exists($input['kode_kelas'])) {
                $errors['kode_kelas'] = "Kode Kelas not found";
            }
        }

        if (!empty($input['password']) && strlen($input['password']) < 6) {
            $errors['password'] = "Password must be at least 6 characters";
        }

        if (empty($input['tempat_lahir'])) $errors['tempat_lahir'] = "Tempat Lahir is required!";
        if (empty($input['tanggal_lahir'])) $errors['tanggal_lahir'] = "Tanggal Lahir is required!";
        if (empty($input['jenis_kelamin'])) $errors['jenis_kelamin'] = "Jenis Kelamin is required!";
        if (empty($input['alamat'])) $errors['alamat'] = "Alamat is required!";

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
     * Get all users data (no auth needed)
     * GET /api/users
     */
    public function index(){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try{
            $decoded = $this->authenticate();

            // Get pagination params
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
            $offset = ($page - 1) * $per_page;

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }
                    
            $users = $this->User_model->get_all($per_page, $offset);
            $total = $this->User_model->count_all();

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users,
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
     * Get single user (no auth needed)
     * GET /api/users/{id}
     */
    public function show($id){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $user = $this->User_model->get_by_id($id);
         
            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
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
     * Create new Users (requires auth)
     * POST /api/users
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

            $user_data = [
                'id_pengguna' => $input['id_pengguna'],
                'nama' => htmlspecialchars($input['nama']),
                'email' => strtolower($input['email']),
                'tempat_lahir' => htmlspecialchars($input['tempat_lahir']),
                'tanggal_lahir' => $input['tanggal_lahir'],
                'jenis_kelamin' => $input['jenis_kelamin'],
                'password' => password_hash($input['password'], PASSWORD_BCRYPT),
                'alamat' => htmlspecialchars($input['alamat']),
                'role' => $input['role'],
                'kode_mapel' => $input['role'] == 'Guru' ? $input['kode_mapel'] : null,
                'kode_kelas' => $input['role'] == 'Siswa' ? $input['kode_kelas'] : null
            ];

            $insert = $this->User_model->create($user_data);

            if (!$insert) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to create user'
                ]);
                exit;
            }

            http_response_code(201);
            echo json_encode([
                'status' => true,
                'message' => 'User created successfully',
                'data' => [
                    'id_pengguna' => $input['id_pengguna'],
                    'nama' => $input['nama'],
                    'email' => $input['email'],
                    'tempat_lahir' => $input['tempat_lahir'],
                    'tanggal_lahir' => $input['tanggal_lahir'],
                    'jenis_kelamin' => $input['jenis_kelamin'],
                    'alamat' => $input['alamat'],
                    'role' => $input['role'],
                    'kode_mapel' => $input['role'] == 'Guru' ? ($input['kode_mapel'] ?? null) : null,
                    'kode_kelas' => $input['role'] == 'Siswa' ? ($input['kode_kelas'] ?? null) : null,
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
     * Update users (requires auth)
     * PUT /api/users/{id}
     */
    public function update($id){
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try{
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $old_user = $this->User_model->get_by_id($id);

            if(!$old_user){
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'User not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input && !empty($_POST)) {
                $input = $_POST;
            }

            // Validate input
            $errors = $this->validate_update($input, $id);

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
                exit;
            }

            $user_data = [
                'nama' => htmlspecialchars($input['nama']),
                'email' => strtolower($input['email']),
                'tempat_lahir' => htmlspecialchars($input['tempat_lahir']),
                'tanggal_lahir' => $input['tanggal_lahir'],
                'jenis_kelamin' => $input['jenis_kelamin'],
                'password' => password_hash($input['password'], PASSWORD_BCRYPT),
                'alamat' => htmlspecialchars($input['alamat']),
                'role' => $input['role'],
                'kode_mapel' => $input['role'] == 'Guru' ? $input['kode_mapel'] : null,
                'kode_kelas' => $input['role'] == 'Siswa' ? $input['kode_kelas'] : null
            ];

            $update = $this->User_model->update($id, $user_data);
            
            if(!$update){
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to update user'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id_pengguna' => $id,
                    'nama' => $input['nama'],
                    'email' => $input['email'],
                    'tempat_lahir' => $input['tempat_lahir'],
                    'tanggal_lahir' => $input['tanggal_lahir'],
                    'jenis_kelamin' => $input['jenis_kelamin'],
                    'alamat' => $input['alamat'],
                    'role' => $input['role'],
                    'kode_mapel' => $input['role'] == 'Guru' ? ($input['kode_mapel'] ?? null) : null,
                    'kode_kelas' => $input['role'] == 'Siswa' ? ($input['kode_kelas'] ?? null) : null,
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
     * Delete user (requires auth)
     * DELETE /api/users/{id}
     */
    public function delete($id) {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $decoded = $this->authenticate();

            if(!$decoded){
                $this->unauthorized('Unauthorized: Invalid or missing token');
            }

            $user = $this->User_model->get_by_id($id);

            if(!$user){
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'User not found'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $delete = $this->User_model->delete($id);

            if (!$delete) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'message' => 'Failed to delete user'
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'User deleted successfully'
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