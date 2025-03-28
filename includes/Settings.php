
<?php
class Settings {
    private $db;
    private static $instance = null;
    private $settings = [];

    private function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
    }

    public static function getInstance($db) {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    private function loadSettings() {
        $stmt = $this->db->query("SELECT * FROM system_settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set($key, $value, $userId) {
        $stmt = $this->db->prepare("
            UPDATE system_settings 
            SET setting_value = ?, updated_by = ? 
            WHERE setting_key = ?
        ");
        return $stmt->execute([$value, $userId, $key]);
    }

    public function getLogoUrl() {
        $logo = $this->get('company_logo');
        return $logo ? '/uploads/logo/' . $logo : '/assets/images/default-logo.png';
    }
}