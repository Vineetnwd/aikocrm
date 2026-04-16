<?php
namespace Core;

class Lead {
    private $db;
    private $company_id;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->company_id = Auth::companyId();
    }

    public function all($filters = []) {
        $sql = "SELECT * FROM leads WHERE company_id = ?";
        $params = [$this->company_id];

        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function find($id) {
        return $this->db->fetchOne("SELECT * FROM leads WHERE id = ? AND company_id = ?", [$id, $this->company_id]);
    }

    public function create($data) {
        $data['company_id'] = $this->company_id;
        return $this->db->insert('leads', $data);
    }

    public function update($id, $data) {
        $keys = array_keys($data);
        $set = "";
        foreach ($keys as $key) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ", ");
        
        $sql = "UPDATE leads SET $set WHERE id = :id AND company_id = :company_id";
        $data['id'] = $id;
        $data['company_id'] = $this->company_id;
        
        return $this->db->query($sql, $data);
    }

    public function delete($id) {
        return $this->db->query("DELETE FROM leads WHERE id = ? AND company_id = ?", [$id, $this->company_id]);
    }
}
?>
