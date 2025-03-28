<?php
class Visit {
    private $conn;
    private $table = 'visits';

    // Constructor y métodos anteriores ya implementados

    public function create($data) {
        $query = 'INSERT INTO ' . $this->table . ' 
                 (client_name, contact_name, contact_phone, address, reference, 
                  location_url, visit_date, visit_time, service_type, technician_id, notes) 
                 VALUES 
                 (:client_name, :contact_name, :contact_phone, :address, :reference,
                  :location_url, :visit_date, :visit_time, :service_type, :technician_id, :notes)';

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $data = array_map('htmlspecialchars', $data);

        // Bind de los parámetros
        $stmt->bindParam(':client_name', $data['client_name']);
        $stmt->bindParam(':contact_name', $data['contact_name']);
        $stmt->bindParam(':contact_phone', $data['contact_phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':reference', $data['reference']);
        $stmt->bindParam(':location_url', $data['location_url']);
        $stmt->bindParam(':visit_date', $data['visit_date']);
        $stmt->bindParam(':visit_time', $data['visit_time']);
        $stmt->bindParam(':service_type', $data['service_type']);
        $stmt->bindParam(':technician_id', $data['technician_id']);
        $stmt->bindParam(':notes', $data['notes']);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}