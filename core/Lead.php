<?php
namespace Core;

class Lead {
    private $db;
    private $company_id;

    private static $allowedColumns = [
        'name', 'mobile', 'email', 'requirement', 'category', 'source',
        'ref_person', 'referral_person', 'assigned_to', 'assigned_employee_id',
        'status', 'task_status', 'task_completed_at', 'deal_value', 'commission_percent', 'commission_amount',
        'closed_at', 'updated_at',
    ];

    private static $nullableInt     = ['assigned_to', 'assigned_employee_id'];
    private static $nullableDecimal = ['deal_value', 'commission_percent', 'commission_amount'];

    public function __construct() {
        $this->db         = Database::getInstance();
        $this->company_id = Auth::companyId();
    }

    // ── Sanitize ────────────────────────────────────────────────────────────
    private function sanitize(array $data): array {
        foreach (self::$nullableInt as $col) {
            if (isset($data[$col]) && $data[$col] === '') $data[$col] = null;
        }
        foreach (self::$nullableDecimal as $col) {
            if (isset($data[$col]) && $data[$col] === '') $data[$col] = 0;
        }
        return $data;
    }

    private function filterColumns(array $data): array {
        return array_filter($data, fn($k) => in_array($k, self::$allowedColumns), ARRAY_FILTER_USE_KEY);
    }

    // ── Queries ─────────────────────────────────────────────────────────────
    public function all(array $filters = []): array {
        $sql = "SELECT l.*,
                       u.name        AS assigned_to_name,
                       e.name        AS assigned_employee_name,
                       e.designation AS assigned_employee_designation
                FROM leads l
                LEFT JOIN users     u ON l.assigned_to          = u.id
                LEFT JOIN employees e ON l.assigned_employee_id = e.id
                WHERE l.company_id = ?";
        $params = [$this->company_id];

        if (!empty($filters['status'])) {
            $sql .= " AND l.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY l.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function find(int $id): ?array {
        $row = $this->db->fetchOne(
            "SELECT l.*,
                    u.name        AS assigned_to_name,
                    e.name        AS assigned_employee_name,
                    e.designation AS assigned_employee_designation,
                    e.commission_type AS emp_commission_type,
                    e.commission_rate AS emp_commission_rate
             FROM leads l
             LEFT JOIN users     u ON l.assigned_to          = u.id
             LEFT JOIN employees e ON l.assigned_employee_id = e.id
             WHERE l.id = ? AND l.company_id = ?",
            [$id, $this->company_id]
        );
        return $row ?: null;
    }

    public function findByMobile(string $mobile, ?int $excludeId = null, string $requirement = ''): ?array {
        $sql    = "SELECT id, name, mobile, status, requirement FROM leads WHERE company_id = ? AND mobile = ?";
        $params = [$this->company_id, $mobile];

        $req = trim($requirement);
        if ($req !== '') {
            $sql .= " AND LOWER(TRIM(requirement)) = ?";
            $params[] = strtolower($req);
        } else {
            $sql .= " AND (requirement IS NULL OR TRIM(requirement) = '')";
        }

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        return $this->db->fetchOne($sql, $params) ?: null;
    }

    // ── Commission ──────────────────────────────────────────────────────────
    private function calculateCommission(array &$data, bool $isUpdate = false, ?int $leadId = null): void {
        if (!isset($data['status']) || $data['status'] !== 'won') return;

        // When only partial data is sent (e.g. status-only update from followup modal),
        // fetch the missing fields from the existing lead row.
        if ($isUpdate && $leadId) {
            $existing = $this->db->fetchOne(
                "SELECT deal_value, commission_percent, assigned_employee_id FROM leads WHERE id = ?",
                [$leadId]
            );
            if ($existing) {
                if (!isset($data['deal_value'])           || floatval($data['deal_value']) == 0)
                    $data['deal_value']           = $existing['deal_value'];
                if (!isset($data['commission_percent'])   || floatval($data['commission_percent']) == 0)
                    $data['commission_percent']   = $existing['commission_percent'];
                if (!isset($data['assigned_employee_id']) || intval($data['assigned_employee_id']) == 0)
                    $data['assigned_employee_id'] = $existing['assigned_employee_id'];
            }
        }

        $dealValue   = floatval($data['deal_value']         ?? 0);
        $commPercent = floatval($data['commission_percent'] ?? 0);

        // Commission from lead's own percentage
        if ($dealValue > 0 && $commPercent > 0) {
            $data['commission_amount'] = round(($dealValue * $commPercent) / 100, 2);
        }

        // Commission from assigned employee's rate
        $empId = intval($data['assigned_employee_id'] ?? 0);
        if ($empId > 0) {
            try {
                $emp = $this->db->fetchOne(
                    "SELECT commission_type, commission_rate FROM employees WHERE id = ? AND company_id = ?",
                    [$empId, $this->company_id]
                );

                if ($emp && floatval($emp['commission_rate']) > 0) {
                    $rate = floatval($emp['commission_rate']);

                    $empCommission = ($emp['commission_type'] === 'fixed')
                        ? $rate
                        : round(($dealValue * $rate) / 100, 2);

                    // Use employee rate only if lead's own % produced nothing
                    if (empty($data['commission_amount']) || floatval($data['commission_amount']) == 0) {
                        $data['commission_amount'] = $empCommission;
                    }

                    // Credit employee total — only if status wasn't already 'won'
                    $creditAmount = floatval($data['commission_amount']);
                    if ($creditAmount > 0) {
                        $alreadyWon = false;
                        if ($isUpdate && $leadId) {
                            $prev = $this->db->fetchOne("SELECT status FROM leads WHERE id = ?", [$leadId]);
                            $alreadyWon = ($prev && $prev['status'] === 'won');
                        }
                        if (!$alreadyWon) {
                            $this->db->query(
                                "UPDATE employees SET total_commission_earned = total_commission_earned + ? WHERE id = ? AND company_id = ?",
                                [$creditAmount, $empId, $this->company_id]
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Commission errors must never block the lead save
            }
        }

        if (empty($data['closed_at'])) {
            $data['closed_at'] = date('Y-m-d H:i:s');
        }
    }

    private function handleTaskStatus(array &$data): void {
        if (isset($data['task_status'])) {
            if ($data['task_status'] === 'done') {
                if (empty($data['task_completed_at'])) {
                    $data['task_completed_at'] = date('Y-m-d H:i:s');
                }
            } else {
                $data['task_completed_at'] = null;
            }
        }
    }

    // ── CRUD ────────────────────────────────────────────────────────────────
    public function create(array $data): int {
        $data = $this->sanitize($data);
        $this->calculateCommission($data, false, null);
        $this->handleTaskStatus($data);
        $data = $this->filterColumns($data);
        $data['company_id'] = $this->company_id;
        return $this->db->insert('leads', $data);
    }

    public function update(int $id, array $data): void {
        $data = $this->sanitize($data);
        $this->calculateCommission($data, true, $id);
        $this->handleTaskStatus($data);
        $data = $this->filterColumns($data);

        if (empty($data)) return;

        $setParts = [];
        $params   = [];
        foreach ($data as $col => $val) {
            $setParts[]      = "`$col` = :$col";
            $params[":$col"] = $val;
        }

        $sql = "UPDATE leads SET " . implode(', ', $setParts) . " WHERE id = :id AND company_id = :company_id";
        $params[':id']         = $id;
        $params[':company_id'] = $this->company_id;

        $this->db->query($sql, $params);
    }

    public function delete(int $id): void {
        $this->db->query(
            "DELETE FROM leads WHERE id = ? AND company_id = ?",
            [$id, $this->company_id]
        );
    }
}
?>
