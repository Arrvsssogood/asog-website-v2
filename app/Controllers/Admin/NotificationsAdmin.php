<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminNotificationModel;

class NotificationsAdmin extends BaseController
{
    private AdminNotificationModel $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new AdminNotificationModel();
    }

    public function markRead(int $id)
    {
        $adminId = (int) session()->get('admin_id');
        $role = (string) session()->get('admin_role');
        $notification = $this->notificationModel->find($id);

        if (! $notification || $adminId <= 0 || ! $this->notificationModel->canAdminSee($id, $adminId, $role)) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Notification not found.',
            ]);
        }

        $this->notificationModel->markReadForAdmin($id, $adminId);

        return $this->response->setJSON([
            'success' => true,
            'unreadCount' => $this->notificationModel->countUnreadForAdmin($adminId, $role),
        ]);
    }

    public function markAllRead()
    {
        $adminId = (int) session()->get('admin_id');
        $role = (string) session()->get('admin_role');
        if ($adminId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'error' => 'Not signed in.',
            ]);
        }

        $this->notificationModel->markAllReadForAdmin($adminId, $role);

        return $this->response->setJSON([
            'success' => true,
            'unreadCount' => $this->notificationModel->countUnreadForAdmin($adminId, $role),
        ]);
    }
}
