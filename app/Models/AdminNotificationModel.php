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
        'priority',
        'isRead',
        'readAt',
    ];

    public const TYPE_NEW_APPLICATION = 'new_application';
    public const TYPE_REVALIDATION_RESUBMITTED = 'revalidation_resubmitted';
    public const TYPE_SYSTEM_UPDATE = 'system_update';

    public function getLatest(int $limit = 8): array
    {
        return $this->orderBy('createdAt', 'DESC')->findAll($limit);
    }

    public function countUnread(): int
    {
        return $this->where('isRead', 0)->countAllResults();
    }

    public function markRead(int $id): bool
    {
        return $this->update($id, [
            'isRead' => 1,
            'readAt' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markAllRead(): bool
    {
        return (bool) $this->builder()->where('isRead', 0)->update([
            'isRead' => 1,
            'readAt' => date('Y-m-d H:i:s'),
        ]);
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
            'priority' => 'high',
        ]);
    }

    public function createSystemUpdate(string $title, string $body, ?string $link = null, string $priority = 'normal'): ?int
    {
        return $this->createNotification([
            'type' => self::TYPE_SYSTEM_UPDATE,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'sourceType' => 'system',
            'sourceId' => null,
            'priority' => $priority,
        ]);
    }

    private function createNotification(array $data): ?int
    {
        $id = $this->insert($data, true);

        return $id === false ? null : (int) $id;
    }
}
