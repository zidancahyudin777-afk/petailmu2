<?php
require_once 'config/database.php';

class StudentManager {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }
    
    /**
     * Get student by username
     * @param string $username
     * @return array|false Student data or false if not found
     */
    public function getStudentByUsername($username) {
        try {
            $query = "SELECT id, username, nama, email, password, jenjang, kelas FROM students WHERE username = :username";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Student Query Error: ' . $e->getMessage());
            throw new Exception('Failed to get student: ' . $e->getMessage());
        }
    }
    
    /**
     * Get student by ID
     * @param int $id
     * @return array|false Student data or false if not found
     */
    public function getStudentById($id) {
        try {
            $query = "SELECT id, username, nama, email, jenjang, kelas FROM students WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Student Query Error: ' . $e->getMessage());
            throw new Exception('Failed to get student: ' . $e->getMessage());
        }
    }
    
    /**
     * Authenticate student login using username and password
     * @param string $username
     * @param string $password
     * @return array|false Student data (without password) or false if authentication fails
     */
    public function authenticateStudent($username, $password) {
        try {
            $student = $this->getStudentByUsername($username);
            
            if ($student && password_verify($password, $student['password'])) {
                // Return student data without password
                unset($student['password']);
                return $student;
            }
            return false;
        } catch (Exception $e) {
            error_log('Student Authentication Error: ' . $e->getMessage());
            throw new Exception('Failed to authenticate student: ' . $e->getMessage());
        }
    }
}
?>
