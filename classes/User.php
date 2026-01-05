<?php

require_once __DIR__ . '../config/database.php';

abstract class User {

    protected $id;
    protected $nom;
    protected $prenom;
    protected $email;
    protected $passwordHash;
    protected $status;
    protected $role;

    public function __construct($nom, $prenom, $email, $password, $status) {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->status = $status;
    }

    abstract public function getPermission();

    public function login($email, $password) {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM users WHERE email = :email AND status = 'actif' LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([':email' => $email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $this->id = $user['id'];
                $this->nom = $user['nom'];
                $this->prenom = $user['prenom'];
                $this->email = $user['email'];
                $this->passwordHash = $user['password'];
                $this->role = $user['role'];
                $this->status = $user['status'];

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $this->id;
                $_SESSION['user_role'] = $this->role;
                $_SESSION['user_email'] = $this->email;
                $_SESSION['user_nom'] = $this->nom . '' . $this->prenom;

                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("error de cnx : " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = array();
        session_destroy();
    }

    public function updateProfile(array $data) {
        try {
            $db = Database::getInstance()->getConnection();

            $fields = [];
            $params = [':id' => $this->id];

            if (!empty($data['nom'])) {
                $fields[] = 'nom = :nom';
                $params[':nom'] = $data['nom'];
                $this->nom = $data['nom'];
            }

            if (!empty($data['prenom'])) {
                $fields[] = 'prenom = :prenom';
                $params[':prenom'] = $data['prenom'];
                $this->prenom = $data['prenom'];
            }

            if (!empty($data['email'])) {
                $checkQuery = 'SELECT id FROM users WHERE email = :email AND id != :id';
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([
                    ':email' => $data['email'],
                    ':id'    => $this->id
                ]);

                if ($checkStmt->rowCount() > 0) {
                    throw new Exception('cette email deja utilise');
                }

                $fields[] = 'email = :email';
                $params[':email'] = $data['email'];
                $this->email = $data['email'];
            }

            if (!empty($data['password'])) {
                $fields[] = 'password = :password';
                $params[':password'] = password_hash($data['password'],PASSWORD_BCRYPT,['cost' => 12]);
            }

            if (empty($fields)) {
                return false;
            }

            $query = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $db->prepare($query);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('error update profile : ' . $e->getMessage());
            return false;
        }
    }

    public function save() {
        try {
            $db = Database::getInstance()->getConnection();
            $query = "INSERT INTO users (nom, prenom, email, password, role, statut) 
                    VALUES (:nom, :prenom, :email, :password, :role, :statut)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':prenom', $this->prenom);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password', $this->passwordHash);
            $stmt->bindParam(':role', $this->role);
            $stmt->bindParam(':statut', $this->status);

            if ($stmt->execute()) {
                $this->id = $db->lastInsertId();
                return true;
            }

            return false;

        } catch (PDOException $e) {
            error_log("error ". $e->getMessage());
            return false;
        }
    }

}
