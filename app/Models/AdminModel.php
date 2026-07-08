<?php

namespace App\Models;

use CodeIgniter\Model;

/**  
 * AdminModel — handles authentication and admin user management.
**/
class AdminModel extends Model
{
    protected $table         = 'admins';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'createdAt';
    protected $updatedField  = 'updatedAt';

    protected $allowedFields = [
        'fullName',
        'email',
        'googleEmail',
        'googleSub',
        'password',
        'role',
        'isActive',
        'lastLoginAt',
        'resetToken',
        'resetTokenExpiresAt',
    ];

    protected $returnType = 'array';

    // ─── Validation ───────────────────────────────────────────
    protected $validationRules = [
        'fullName' => 'required|min_length[2]|max_length[150]',
        'email'    => 'required|valid_email|max_length[255]',
        'password' => 'required|min_length[8]',
    ];

    protected $validationMessages = [
        'fullName' => [
            'required' => 'Full name is required.',
        ],
        'email' => [
            'required'    => 'Email address is required.',
            'valid_email' => 'Please enter a valid email address.',
        ],
        'password' => [
            'required'   => 'Password is required.',
            'min_length' => 'Password must be at least 8 characters.',
        ],
    ];

    // ─── Callbacks ────────────────────────────────────────────
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash(
                $data['data']['password'],
                PASSWORD_BCRYPT
            );
        }

        return $data;
    }

    // ─── Auth Helpers ─────────────────────────────────────────

    /**  
     * Attempt login with email + plain-text password.
     * Returns the admin row on success, null on failure.
    **/
    public function attempt(string $email, string $password): ?array
    {
        $admin = $this->where('email', $email)
                      ->where('isActive', 1)
                      ->first();

        if ($admin === null) {
            return null;
        }

        if (! password_verify($password, $admin['password'])) {
            return null;
        }

        // Stamp last login
        $this->update($admin['id'], [
            'lastLoginAt' => date('Y-m-d H:i:s'),
        ]);

        return $admin;
    }

    /**  
     * Find an admin by email.
    **/
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find the admin authorized for a given Google account.
     *
     * Prefers the stable Google subject/id, then falls back to the email.
     */
    public function findByGoogleAccount(string $email, string $googleSub = ''): ?array
    {
        $email = strtolower(trim($email));
        $googleSub = trim($googleSub);

        $builder = $this->where('isActive', 1);

        if ($googleSub !== '') {
            return $builder
                ->groupStart()
                    ->where('googleSub', $googleSub)
                    ->orWhere('googleEmail', $email)
                    ->orWhere('email', $email)
                ->groupEnd()
                ->first();
        }

        return $builder
            ->groupStart()
                ->where('googleEmail', $email)
                ->orWhere('email', $email)
            ->groupEnd()
            ->first();
    }

    /**
     * Returns true when an email is already used by another admin.
     */
    public function isEmailTaken(string $email, ?int $excludeId = null): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        $builder = $this->builder()->where('LOWER(email)', $email);

        if ($excludeId !== null && $excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public function findByResetToken(string $token): ?array
    {
        return $this->where('resetToken', hash('sha256', $token))
                    ->where('resetTokenExpiresAt >=', date('Y-m-d H:i:s'))
                    ->first();
    }

    public function clearResetToken(int $id): void
    {
        $this->update($id, [
            'resetToken'          => null,
            'resetTokenExpiresAt' => null,
        ]);
    }

    /**
     * Return filtered, sorted and paginated admin accounts.
     */
    public function getFiltered(string $search = '', string $status = 'all', string $role = 'all', string $sort = 'fullName', string $direction = 'ASC', int $page = 1, int $perPage = 10): array
    {
        $builder = $this->builder();

        if ($status === 'active') {
            $builder->where('isActive', 1);
        } elseif ($status === 'inactive') {
            $builder->where('isActive', 0);
        }

        if (in_array($role, ['superadmin', 'admin', 'editor'], true)) {
            $builder->where('role', $role);
        }

        if (! empty($search)) {
            $builder->groupStart()
                    ->like('fullName', $search)
                    ->orLike('email', $search)
                    ->orLike('googleEmail', $search)
                    ->groupEnd();
        }

        $totalCount = $builder->countAllResults(false);

        $allowed   = ['fullName', 'email', 'role', 'isActive', 'lastLoginAt'];
        $sort      = in_array($sort, $allowed, true) ? $sort : 'fullName';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $builder->orderBy($sort, $direction);

        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);

        $results = $builder->get()->getResultArray();

        return [
            'admins'     => $results,
            'total'      => $totalCount,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1,
        ];
    }

    /**
     * Return aggregate counts of admin accounts.
     */
    public function getCounts(): array
    {
        return [
            'total'      => $this->db->table($this->table)->countAllResults(),
            'active'     => $this->db->table($this->table)->where('isActive', 1)->countAllResults(),
            'inactive'   => $this->db->table($this->table)->where('isActive', 0)->countAllResults(),
            'superadmin' => $this->db->table($this->table)->where('role', 'superadmin')->countAllResults(),
            'admin'      => $this->db->table($this->table)->where('role', 'admin')->countAllResults(),
            'editor'     => $this->db->table($this->table)->where('role', 'editor')->countAllResults(),
        ];
    }
}
