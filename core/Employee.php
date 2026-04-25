<?php
namespace Core;

class Employee
{
    private $db;
    private $company_id;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->company_id = Auth::companyId();
    }

    public function all($filters = [])
    {
        $sql = "SELECT * FROM employees WHERE company_id = ?";
        $params = [$this->company_id];

        if (!empty($filters['department'])) {
            $sql .= " AND department = ?";
            $params[] = $filters['department'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR mobile LIKE ? OR employee_id LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function find($id)
    {
        return $this->db->fetchOne(
            "SELECT e.*,
                    COUNT(l.id)                    AS total_leads,
                    SUM(l.status = 'won')           AS won_leads,
                    SUM(l.deal_value)               AS total_deal_value
             FROM employees e
             LEFT JOIN leads l ON l.assigned_employee_id = e.id AND l.company_id = e.company_id
             WHERE e.id = ? AND e.company_id = ?
             GROUP BY e.id",
            [$id, $this->company_id]
        );
    }

    public function create($data)
    {
        $data['company_id'] = $this->company_id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $empId = $this->db->insert('employees', $data);

        // Auto-create user for the employee
        try {
            $userEmail = !empty($data['email']) ? $data['email'] : 'emp_' . $empId . '@aikaacrm.com';
            $this->db->insert('users', [
                'company_id' => $this->company_id,
                'name' => $data['name'],
                'email' => $userEmail,
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'role' => 'staff',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            // Ignore duplicate email or other constraints to not block employee creation
        }

        return $empId;
    }

    public function update($id, $data)
    {
        $keys = array_keys($data);
        $set = implode(', ', array_map(fn($k) => "$k = :$k", $keys));

        $sql = "UPDATE employees SET $set WHERE id = :id AND company_id = :company_id";
        $data['id'] = $id;
        $data['company_id'] = $this->company_id;

        return $this->db->query($sql, $data);
    }

    public function delete($id)
    {
        return $this->db->query(
            "DELETE FROM employees WHERE id = ? AND company_id = ?",
            [$id, $this->company_id]
        );
    }

    public function departments()
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT department FROM employees WHERE company_id = ? AND department IS NOT NULL AND department != '' ORDER BY department",
            [$this->company_id]
        );
    }

    public function stats()
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*)                                          AS total,
                SUM(CASE WHEN status = 'active'   THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) AS on_leave,
                SUM(total_commission_earned)                      AS total_commission
             FROM employees WHERE company_id = ?",
            [$this->company_id]
        );
    }
}
?>