<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminNotificationModel extends Model
{
    protected $table            = 'admin_notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'type',
        'title',
        'body',
        'link',
        'sourceType',
        'sourceId',
        'targetRole',
        'targetAdminId',
        'actorAdminId',
        'priority',
        'isRead',
        'readAt',
    ];

    public const TYPE_NEW_APPLICATION = 'new_application';
    public const TYPE_REVALIDATION_RESUBMITTED = 'revalidation_resubmitted';
    public const TYPE_SYSTEM_UPDATE = 'system_update';
    public const TYPE_CONTACT_MESSAGE = 'contact_message';
    public const TYPE_ACCOUNT_UPDATE = 'account_update';

    private const ROLE_LEVELS = [
        'editor' => 1,
        'admin' => 2,
        'superadmin' => 3,
    ];

    public function getLatestForAdmin(int $adminId, string $role, int $limit = 8): array
    {
        return $this->select($this->table . '.*, notification_reads.readAt AS userReadAt')
            ->join(
                'admin_notification_reads notification_reads',
                'notification_reads.notificationId = ' . $this->table . '.id'
                    . ' AND notification_reads.adminId = ' . (int) $adminId,
                'left'
            )
            ->groupStart()
                ->where($this->table . '.targetAdminId', $adminId)
                ->orGroupStart()
                    ->where($this->table . '.targetAdminId', null)
                    ->groupStart()
                        ->where($this->table . '.targetRole', null)
                        ->orWhereIn($this->table . '.targetRole', $this->visibleRoleTargets($role))
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->orderBy($this->table . '.createdAt', 'DESC')
            ->findAll($limit);
    }

    public function countUnreadForAdmin(int $adminId, string $role): int
    {
        return $this->join(
                'admin_notification_reads notification_reads',
                'notification_reads.notificationId = ' . $this->table . '.id'
                    . ' AND notification_reads.adminId = ' . (int) $adminId,
                'left'
            )
            ->where('notification_reads.id', null)
            ->where($this->table . '.isRead', 0)
            ->groupStart()
                ->where($this->table . '.targetAdminId', $adminId)
                ->orGroupStart()
                    ->where($this->table . '.targetAdminId', null)
                    ->groupStart()
                        ->where($this->table . '.targetRole', null)
                        ->orWhereIn($this->table . '.targetRole', $this->visibleRoleTargets($role))
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->countAllResults();
    }

    public function canAdminSee(int $id, int $adminId, string $role): bool
    {
        return $this->where('id', $id)
            ->groupStart()
                ->where('targetAdminId', $adminId)
                ->orGroupStart()
                    ->where('targetAdminId', null)
                    ->groupStart()
                        ->where('targetRole', null)
                        ->orWhereIn('targetRole', $this->visibleRoleTargets($role))
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->countAllResults() > 0;
    }

    public function markReadForAdmin(int $id, int $adminId): bool
    {
        $reads = $this->db->table('admin_notification_reads');
        $exists = $reads
            ->where('notificationId', $id)
            ->where('adminId', $adminId)
            ->countAllResults() > 0;

        if ($exists) {
            return true;
        }

        return (bool) $this->db->table('admin_notification_reads')->insert([
            'notificationId' => $id,
            'adminId' => $adminId,
            'readAt' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markAllReadForAdmin(int $adminId, string $role): bool
    {
        $notifications = $this->select($this->table . '.id')
            ->join(
                'admin_notification_reads notification_reads',
                'notification_reads.notificationId = ' . $this->table . '.id'
                    . ' AND notification_reads.adminId = ' . (int) $adminId,
                'left'
            )
            ->where('notification_reads.id', null)
            ->where($this->table . '.isRead', 0)
            ->groupStart()
                ->where($this->table . '.targetAdminId', $adminId)
                ->orGroupStart()
                    ->where($this->table . '.targetAdminId', null)
                    ->groupStart()
                        ->where($this->table . '.targetRole', null)
                        ->orWhereIn($this->table . '.targetRole', $this->visibleRoleTargets($role))
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->findAll();

        foreach ($notifications as $notification) {
            $this->markReadForAdmin((int) $notification['id'], $adminId);
        }

        return true;
    }

    public function createNewApplication(array $application): ?int
    {
        $startupName = trim((string) ($application['startupName'] ?? 'New startup'));
        $applicantName = trim((string) ($application['applicantName'] ?? 'An applicant'));

        return $this->createNotification([
            'type' => self::TYPE_NEW_APPLICATION,
            'title' => 'New application received',
            'body' => $startupName . ' was submitted by ' . $applicantName . '.',
            'link' => site_url('admin/applications'),
            'sourceType' => 'incubatee_application',
            'sourceId' => isset($application['id']) ? (int) $application['id'] : null,
            'targetRole' => 'admin',
            'priority' => 'normal',
        ]);
    }

    public function createRevalidationResubmitted(array $application): ?int
    {
        $startupName = trim((string) ($application['startupName'] ?? 'An application'));
        $applicantName = trim((string) ($application['applicantName'] ?? 'The applicant'));

        return $this->createNotification([
            'type' => self::TYPE_REVALIDATION_RESUBMITTED,
            'title' => 'Application resubmitted',
            'body' => $applicantName . ' updated ' . $startupName . ' for revalidation.',
            'link' => site_url('admin/applications?status=pending'),
            'sourceType' => 'incubatee_application',
            'sourceId' => isset($application['id']) ? (int) $application['id'] : null,
            'targetRole' => 'admin',
            'priority' => 'high',
        ]);
    }

    public function createContactMessage(array $message): ?int
    {
        $name = trim((string) ($message['name'] ?? 'Website visitor'));

        return $this->createNotification([
            'type' => self::TYPE_CONTACT_MESSAGE,
            'title' => 'New contact message',
            'body' => $name . ' sent a message through the website.',
            'link' => site_url('admin/messages'),
            'sourceType' => 'contact_message',
            'sourceId' => isset($message['id']) ? (int) $message['id'] : null,
            'targetRole' => 'admin',
            'priority' => 'normal',
        ]);
    }

    public function createAccountAccessChanged(array $admin, array $changes, ?int $actorAdminId = null): ?int
    {
        $parts = [];
        if (isset($changes['role'])) {
            $parts[] = 'role changed from ' . $this->roleLabel((string) $changes['role'][0]) . ' to ' . $this->roleLabel((string) $changes['role'][1]);
        }
        if (isset($changes['active'])) {
            $parts[] = ! empty($changes['active'][1]) ? 'access activated' : 'access deactivated';
        }

        if ($parts === []) {
            return null;
        }

        return $this->createNotification([
            'type' => self::TYPE_ACCOUNT_UPDATE,
            'title' => 'Your account access changed',
            'body' => ucfirst(implode('; ', $parts)) . '.',
            'link' => site_url('admin/settings'),
            'sourceType' => 'admin_account',
            'sourceId' => isset($admin['id']) ? (int) $admin['id'] : null,
            'targetAdminId' => isset($admin['id']) ? (int) $admin['id'] : null,
            'actorAdminId' => $actorAdminId,
            'priority' => 'high',
        ]);
    }

    public function createAccountManaged(string $title, string $body, ?int $sourceId = null, ?int $actorAdminId = null): ?int
    {
        return $this->createNotification([
            'type' => self::TYPE_ACCOUNT_UPDATE,
            'title' => $title,
            'body' => $body,
            'link' => site_url('admin/accounts'),
            'sourceType' => 'admin_account',
            'sourceId' => $sourceId,
            'targetRole' => 'superadmin',
            'actorAdminId' => $actorAdminId,
            'priority' => 'high',
        ]);
    }

    public function createSystemUpdate(
        string $title,
        string $body,
        ?string $link = null,
        string $priority = 'normal',
        ?string $targetRole = 'superadmin',
        ?int $actorAdminId = null
    ): ?int {
        return $this->createNotification([
            'type' => self::TYPE_SYSTEM_UPDATE,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'sourceType' => 'system',
            'sourceId' => null,
            'targetRole' => $targetRole,
            'actorAdminId' => $actorAdminId,
            'priority' => $priority,
        ]);
    }

    private function createNotification(array $data): ?int
    {
        $id = $this->insert($data, true);

        return $id === false ? null : (int) $id;
    }

    private function visibleRoleTargets(string $role): array
    {
        $role = strtolower(trim($role));
        $level = self::ROLE_LEVELS[$role] ?? 0;
        $targets = [];

        foreach (self::ROLE_LEVELS as $targetRole => $targetLevel) {
            if ($level >= $targetLevel) {
                $targets[] = $targetRole;
            }
        }

        return $targets !== [] ? $targets : [''];
    }

    private function roleLabel(string $role): string
    {
        return [
            'superadmin' => 'Super Admin',
            'admin' => 'Admin',
            'editor' => 'Editor',
        ][$role] ?? ucfirst($role);
    }
}
