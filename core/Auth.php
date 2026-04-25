<?php
namespace Core;

class Auth {
    private static $currentUser = null;

    public static function login($email, $password) {
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            self::$currentUser = $user;
            return true;
        }
        return false;
    }

    public static function check() {
        if (isset($_SESSION['user'])) {
            self::$currentUser = $_SESSION['user'];
            return true;
        }
        return false;
    }

    public static function user() {
        return self::$currentUser;
    }

    public static function userId() {
        return self::$currentUser ? self::$currentUser['id'] : null;
    }

    public static function userName() {
        return self::$currentUser ? self::$currentUser['name'] : 'Admin User';
    }

    public static function companyId() {
        return self::$currentUser ? self::$currentUser['company_id'] : null;
    }

    public static function logout() {
        unset($_SESSION['user']);
        self::$currentUser = null;
        session_destroy();
    }
}
?>
