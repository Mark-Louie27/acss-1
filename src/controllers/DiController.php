<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';

class DiController
{
    public $db;
    private $userModel;

    public function __construct()
    {
        error_log("DeanController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in DeanController");
            die("Database connection failed. Please try again later.");
        }
        $this->userModel = new UserModel();
        $this->restrictToDi();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
    }

    private function restrictToDi()
    {
        error_log("restrictToDi: Checking session - user_id: " . ($_SESSION['user_id'] ?? 'none') . ", role_id: " . ($_SESSION['role_id'] ?? 'none'));
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
            error_log("restrictToDi: Redirecting to login due to unauthorized access");
            header('Location: /login?error=Unauthorized access');
            exit;
        }
    }

    private function getUserData(){

    }

    public function dashboard() {

    }

    public function profile(){
        
    }
}
?>