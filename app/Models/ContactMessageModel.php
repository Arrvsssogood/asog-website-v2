<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactMessageModel extends Model
{
    protected $table            = 'contact_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'name',
        'email',
        'message',
        'isRead',
        'isArchived',
    ];

    protected $validationRules = [
        'name'    => 'required|min_length[2]|max_length[100]',
        'email'   => 'required|valid_email|max_length[150]',
        'message' => 'required',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Your name is required.',
            'min_length' => 'Name must be at least 2 characters.',
            'max_length' => 'Name cannot exceed 100 characters.',
        ],
        'email' => [
            'required'    => 'Email is required.',
            'valid_email' => 'Please enter a valid email address.',
            'max_length'  => 'Email cannot exceed 150 characters.',
        ],
        'message' => [
            'required'   => 'Message is required.',
        ],
    ];

    public function getCounts(): array
    {
        $inbox    = $this->where('isArchived', 0)->countAllResults();
        $unread   = $this->where('isRead', 0)->where('isArchived', 0)->countAllResults();
        $read     = $this->where('isRead', 1)->where('isArchived', 0)->countAllResults();
        $archived = $this->where('isArchived', 1)->countAllResults();

        return ['total' => $inbox, 'unread' => $unread, 'read' => $read, 'archived' => $archived];
    }

    public function getAll(): array
    {
        return $this->orderBy('createdAt', 'DESC')->findAll();
    }

    public function getInbox(): array
    {
        return $this->where('isArchived', 0)->orderBy('createdAt', 'DESC')->findAll();
    }

    public function getArchived(): array
    {
        return $this->where('isArchived', 1)->orderBy('createdAt', 'DESC')->findAll();
    }

    public function getUnread(): array
    {
        return $this->where('isRead', 0)->where('isArchived', 0)
                    ->orderBy('createdAt', 'DESC')
                    ->findAll();
    }

    public function countUnread(): int
    {
        return $this->where('isRead', 0)->where('isArchived', 0)->countAllResults();
    }

    public function markRead(int $id): bool
    {
        return $this->update($id, ['isRead' => 1]);
    }

    public function getFiltered(int $isArchived, string $search = '', string $dateFilter = 'all', int $page = 1, int $perPage = 10): array
    {
        $this->where('isArchived', $isArchived);

        if ($search !== '') {
            $this->groupStart()
                 ->like('name', $search)
                 ->orLike('email', $search)
                 ->orLike('message', $search)
                 ->groupEnd();
        }

        if ($dateFilter === 'today') {
            $this->where('createdAt >=', date('Y-m-d') . ' 00:00:00')
                 ->where('createdAt <=', date('Y-m-d') . ' 23:59:59');
        } elseif ($dateFilter === 'week') {
            $this->where('createdAt >=', date('Y-m-d H:i:s', strtotime('-7 days')));
        } elseif ($dateFilter === 'month') {
            $this->where('createdAt >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        }

        $total   = $this->countAllResults(false);
        $results = $this->orderBy('createdAt', 'DESC')->findAll($perPage, ($page - 1) * $perPage);

        return [
            'messages'   => $results,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    public function bulkMarkRead(array $ids): void
    {
        if (empty($ids)) return;
        $this->db->table($this->table)->whereIn('id', $ids)->update(['isRead' => 1]);
    }

    public function bulkMarkUnread(array $ids): void
    {
        if (empty($ids)) return;
        $this->db->table($this->table)->whereIn('id', $ids)->update(['isRead' => 0]);
    }

    public function bulkArchive(array $ids): void
    {
        if (empty($ids)) return;
        $this->db->table($this->table)->whereIn('id', $ids)->update(['isArchived' => 1]);
    }

    public function bulkUnarchive(array $ids): void
    {
        if (empty($ids)) return;
        $this->db->table($this->table)->whereIn('id', $ids)->update(['isArchived' => 0]);
    }

    public function bulkDelete(array $ids): void
    {
        if (empty($ids)) return;
        $this->db->table($this->table)->whereIn('id', $ids)->delete();
    }
}
