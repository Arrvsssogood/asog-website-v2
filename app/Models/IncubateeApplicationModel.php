<?php

namespace App\Models;

use CodeIgniter\Model;

/**  
 * IncubateeApplicationModel — manages incubatee applications.
**/
class IncubateeApplicationModel extends Model
{
    protected $table            = 'incubatee_applications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'startupName',
        'startupDescription',
        'mainRisk',
        'shortTermGoals',
        'teamCvPath',
        'leanCanvasPath',
        'videoPresentationLink',
        'applicantName',
        'applicantEmail',
        'contactNumber',
        'applicationStatus',
        'statusRemark',
        'revalidationTokenHash',
        'revalidationTokenExpiresAt',
        'revalidationRequestedAt',
        'revalidatedAt',
        'isArchived',
    ];

    protected $validationRules = [
        'startupName'              => 'required|min_length[2]|max_length[255]',
        'startupDescription'       => 'required|min_length[10]|max_length[2000]',
        'mainRisk'                 => 'max_length[1000]',
        'shortTermGoals'           => 'max_length[1000]',
        'teamCvPath'               => 'permit_empty',
        'leanCanvasPath'           => 'max_length[500]',
        'videoPresentationLink'    => 'required|valid_url|max_length[500]',
        'applicantName'            => 'required|regex_match[/^[A-Za-z\s,\.]+$/]|max_length[255]',
        'applicantEmail'           => 'required|valid_email|max_length[255]',
        'contactNumber'            => 'required|regex_match[/^09[0-9]{9}$/]|max_length[11]',
        'statusRemark'             => 'permit_empty|max_length[2000]',
    ];

    protected $validationMessages = [
        'startupName' => [
            'required'     => 'Startup name is required.',
            'min_length'   => 'Startup name must be at least 2 characters.',
            'max_length'   => 'Startup name cannot exceed 255 characters.',
        ],
        'startupDescription' => [
            'required'     => 'Startup description is required.',
            'min_length'   => 'Description must be at least 10 characters.',
            'max_length'   => 'Description cannot exceed 2000 characters.',
        ],
        'videoPresentationLink' => [
            'required'     => 'Video presentation link is required.',
            'valid_url'    => 'Please provide a valid YouTube or Google Drive link.',
            'max_length'   => 'URL cannot exceed 500 characters.',
        ],
        'applicantName' => [
            'required'     => 'Full name is required.',
            'regex_match'  => 'Use format: Last Name, First Name MI',
            'max_length'   => 'Name cannot exceed 255 characters.',
        ],
        'applicantEmail' => [
            'required'     => 'Email is required.',
            'valid_email'  => 'Please enter a valid email address.',
            'max_length'   => 'Email cannot exceed 255 characters.',
        ],
        'contactNumber' => [
            'required'     => 'Contact number is required.',
            'regex_match'  => 'Please enter a valid contact number.',
            'max_length'   => 'Contact number cannot exceed 11 digits.',
        ],
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_FOR_REVALIDATION = 'for_revalidation';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'For Review',
        self::STATUS_FOR_REVALIDATION => 'For Revalidation',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_REJECTED => 'Rejected',
    ];

    public static function allowedStatuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    // ─── Query Helpers ────────────────────────────────────

    /**  
     * Return summary counts by status.
    **/
    public function getCounts(): array
    {
        $total    = $this->where('isArchived', 0)->countAllResults();
        $pending  = $this->where('isArchived', 0)->where('applicationStatus', self::STATUS_PENDING)->countAllResults();
        $forRevalidation = $this->where('isArchived', 0)->where('applicationStatus', self::STATUS_FOR_REVALIDATION)->countAllResults();
        $accepted = $this->where('isArchived', 0)->where('applicationStatus', self::STATUS_ACCEPTED)->countAllResults();
        $rejected = $this->where('isArchived', 0)->where('applicationStatus', self::STATUS_REJECTED)->countAllResults();
        $archived = $this->where('isArchived', 1)->countAllResults();

        return compact('total', 'pending', 'forRevalidation', 'accepted', 'rejected', 'archived');
    }

    /**  
     * Return filtered and sorted applications.
    **/
    public function getFilteredApplications(string $search = '', string $status = 'active', string $sort = 'createdAt', string $direction = 'DESC', int $limit = 0, int $offset = 0): array
    {
        $builder = $this->builder();

        if ($status === 'archived') {
            $builder->where('isArchived', 1);
        } else {
            $builder->where('isArchived', 0);
            if ($status !== 'active' && $status !== 'all') {
                $builder->where('applicationStatus', $status);
            }
        }

        if (! empty($search)) {
            $builder->groupStart()
                    ->like('applicantName', $search)
                    ->orLike('startupName', $search)
                    ->orLike('applicantEmail', $search)
                    ->groupEnd();
        }

        $allowed   = ['applicantName', 'startupName', 'applicantEmail', 'createdAt', 'applicationStatus'];
        $sort      = in_array($sort, $allowed, true) ? $sort : 'createdAt';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $builder->orderBy($sort, $direction);

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    public function countFilteredApplications(string $search = '', string $status = 'active'): int
    {
        $builder = $this->builder();

        if ($status === 'archived') {
            $builder->where('isArchived', 1);
        } else {
            $builder->where('isArchived', 0);
            if ($status !== 'active' && $status !== 'all') {
                $builder->where('applicationStatus', $status);
            }
        }

        if (! empty($search)) {
            $builder->groupStart()
                    ->like('applicantName', $search)
                    ->orLike('startupName', $search)
                    ->orLike('applicantEmail', $search)
                    ->groupEnd();
        }

        return (int) $builder->countAllResults();
    }

    /**  
     * Return every application, newest first.
    **/
    public function getAll(int $limit = 0): array
    {
        $builder = $this->orderBy('createdAt', 'DESC');

        return $limit > 0 ? $builder->findAll($limit) : $builder->findAll();
    }

    /**  
     * Return pending applications, newest first.
    **/
    public function getPending(int $limit = 0)
    {
        $builder = $this->where('applicationStatus', self::STATUS_PENDING)
                        ->orderBy('createdAt', 'DESC');

        return $limit > 0 ? $builder->findAll($limit) : $builder->findAll();
    }

    /**  
     * Find an application by the applicant's email.
    **/
    public function getByEmail(string $email)
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return $this->builder()
                    ->where('LOWER(applicantEmail)', $email)
                    ->limit(1)
                    ->get()
                    ->getRowArray();
    }

    public function emailExists(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        return $this->builder()
            ->select('id')
            ->where('LOWER(applicantEmail)', $email)
            ->where('applicationStatus !=', self::STATUS_REJECTED)
            ->limit(1)
            ->get()
            ->getRowArray() !== null;
    }

    public function emailExistsExcept(string $email, int $exceptId): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        return $this->builder()
            ->select('id')
            ->where('LOWER(applicantEmail)', $email)
            ->where('id !=', $exceptId)
            ->where('applicationStatus !=', self::STATUS_REJECTED)
            ->limit(1)
            ->get()
            ->getRowArray() !== null;
    }

    public function duplicateEmailMessage(): string
    {
        return 'This email has already been used in a previous application.';
    }

    public function getDbError(): array
    {
        return $this->db->error();
    }

    public function isDuplicateEmailDbError(): bool
    {
        $error = $this->getDbError();
        $message = strtolower((string) ($error['message'] ?? ''));

        if ($message === '') {
            return false;
        }

        return str_contains($message, 'duplicate')
            && str_contains($message, 'applicantemail');
    }

    /**  
     * Set the applicationStatus of a given record.
     * @param  int    $id     Primary-key ID
     * @param  string $status One of the STATUS_* constants.
     * @return bool
    **/
    public function updateStatus(int $id, string $status, ?string $remark = null): bool
    {
        if (! in_array($status, self::allowedStatuses(), true)) {
            return false;
        }

        return $this->update($id, [
            'applicationStatus' => $status,
            'statusRemark'      => $remark !== null && trim($remark) !== '' ? trim($remark) : null,
        ]);
    }

    public function findByRevalidationToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $hash = hash('sha256', $token);

        return $this->builder()
            ->where('revalidationTokenHash', $hash)
            ->where('applicationStatus', self::STATUS_FOR_REVALIDATION)
            ->where('isArchived', 0)
            ->limit(1)
            ->get()
            ->getRowArray() ?: null;
    }

    public function isRevalidationLinkUsable(array $app): bool
    {
        if (($app['applicationStatus'] ?? null) !== self::STATUS_FOR_REVALIDATION) {
            return false;
        }

        if (! empty($app['isArchived'])) {
            return false;
        }

        $expiresAt = (string) ($app['revalidationTokenExpiresAt'] ?? '');
        if ($expiresAt === '') {
            return false;
        }

        return strtotime($expiresAt) !== false && strtotime($expiresAt) >= time();
    }
}
