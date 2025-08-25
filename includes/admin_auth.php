<?php
class AdminAuth {
    private $conn;
    private $session_lifetime = 3600; // 1 hour
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Authenticate admin user
     */
    public function login($username, $password, $remember_me = false) {
        try {
            // Get user from database
            $stmt = $this->conn->prepare("SELECT id, username, email, password_hash, full_name, role, is_active FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Update last login
                    $this->updateLastLogin($user['id']);
                    
                    // Create session
                    $session_id = $this->createSession($user['id'], $remember_me);
                    
                    // Set session variables
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['session_id'] = $session_id;
                    $_SESSION['login_time'] = time();
                    
                    // Set remember me cookie if requested
                    if ($remember_me) {
                        $cookie_value = base64_encode($user['id'] . ':' . $session_id);
                        setcookie('admin_remember', $cookie_value, time() + (30 * 24 * 3600), '/', '', false, true); // 30 days
                    }
                    
                    return ['success' => true, 'user' => $user];
                } else {
                    return ['success' => false, 'message' => 'Invalid credentials'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found or inactive'];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        // Check session
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
            // Validate session in database if admin_sessions table exists
            if ($this->tableExists('admin_sessions') && isset($_SESSION['session_id'])) {
                return $this->validateSession($_SESSION['session_id'], $_SESSION['admin_id']);
            }
            // Fallback to simple session check
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['admin_remember'])) {
            return $this->validateRememberToken();
        }
        
        return false;
    }
    
    /**
     * Check if table exists (FIXED)
     */
    private function tableExists($table) {
        try {
            // Use INFORMATION_SCHEMA to check if table exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
            $stmt->bind_param("s", $table);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['count'] > 0;
        } catch (Exception $e) {
            // If INFORMATION_SCHEMA fails, try direct query
            try {
                $escaped_table = $this->conn->real_escape_string($table);
                $result = $this->conn->query("SHOW TABLES LIKE '$escaped_table'");
                return $result && $result->num_rows > 0;
            } catch (Exception $e2) {
                // If all fails, assume table doesn't exist
                return false;
            }
        }
    }
    
    /**
     * Create session in database
     */
    private function createSession($admin_id, $remember_me = false) {
        $session_id = bin2hex(random_bytes(32));
        
        // Only create session in DB if table exists
        if ($this->tableExists('admin_sessions')) {
            $expires_at = date('Y-m-d H:i:s', time() + ($remember_me ? 30 * 24 * 3600 : $this->session_lifetime));
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->conn->prepare("INSERT INTO admin_sessions (id, admin_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $session_id, $admin_id, $ip_address, $user_agent, $expires_at);
            $stmt->execute();
        }
        
        return $session_id;
    }
    
    /**
     * Validate session
     */
    private function validateSession($session_id, $admin_id) {
        if (!$this->tableExists('admin_sessions')) {
            return true; // Fallback if table doesn't exist
        }
        
        $stmt = $this->conn->prepare("SELECT id FROM admin_sessions WHERE id = ? AND admin_id = ? AND expires_at > NOW()");
        $stmt->bind_param("si", $session_id, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Validate remember me token
     */
    private function validateRememberToken() {
        try {
            $cookie_data = base64_decode($_COOKIE['admin_remember']);
            list($admin_id, $session_id) = explode(':', $cookie_data);
            
            if ($this->validateSession($session_id, $admin_id)) {
                // Check if admin_users table exists
                if ($this->tableExists('admin_users')) {
                    $stmt = $this->conn->prepare("SELECT username, email, full_name, role FROM admin_users WHERE id = ? AND is_active = 1");
                    $stmt->bind_param("i", $admin_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($user = $result->fetch_assoc()) {
                        session_regenerate_id(true);
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $admin_id;
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_name'] = $user['full_name'];
                        $_SESSION['admin_role'] = $user['role'];
                        $_SESSION['session_id'] = $session_id;
                        $_SESSION['login_time'] = time();
                        
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Remember token validation error: " . $e->getMessage());
        }
        
        // Invalid token, clear cookie
        setcookie('admin_remember', '', time() - 3600, '/');
        return false;
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($admin_id) {
        if ($this->tableExists('admin_users')) {
            $stmt = $this->conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Remove session from database
        if (isset($_SESSION['session_id']) && $this->tableExists('admin_sessions')) {
            $stmt = $this->conn->prepare("DELETE FROM admin_sessions WHERE id = ?");
            $stmt->bind_param("s", $_SESSION['session_id']);
            $stmt->execute();
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Clear remember me cookie
        if (isset($_COOKIE['admin_remember'])) {
            setcookie('admin_remember', '', time() - 3600, '/');
        }
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($required_role) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $user_role = $_SESSION['admin_role'] ?? 'admin';
        
        // Admin has access to everything
        if ($user_role === 'admin') {
            return true;
        }
        
        // Manager can access staff functions
        if ($user_role === 'manager' && $required_role === 'staff') {
            return true;
        }
        
        return $user_role === $required_role;
    }
    
    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions() {
        if ($this->tableExists('admin_sessions')) {
            $stmt = $this->conn->prepare("DELETE FROM admin_sessions WHERE expires_at < NOW()");
            $stmt->execute();
        }
    }
    
    /**
     * Get user info
     */
    public function getUserInfo() {
        if ($this->isAuthenticated()) {
            return [
                'id' => $_SESSION['admin_id'] ?? 1,
                'username' => $_SESSION['admin_username'] ?? 'admin',
                'email' => $_SESSION['admin_email'] ?? 'admin@localhost',
                'name' => $_SESSION['admin_name'] ?? 'Administrator',
                'role' => $_SESSION['admin_role'] ?? 'admin',
                'login_time' => $_SESSION['login_time'] ?? time()
            ];
        }
        return null;
    }
    
    /**
     * Change password
     */
    public function changePassword($admin_id, $current_password, $new_password) {
        try {
            if (!$this->tableExists('admin_users')) {
                return ['success' => false, 'message' => 'Admin users table not found'];
            }
            
            // Verify current password
            $stmt = $this->conn->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($current_password, $user['password_hash'])) {
                    // Update password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $this->conn->prepare("UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("si", $new_hash, $admin_id);
                    $stmt->execute();
                    
                    return ['success' => true, 'message' => 'Password updated successfully'];
                } else {
                    return ['success' => false, 'message' => 'Current password is incorrect'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update password'];
        }
    }
}
?>