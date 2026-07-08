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
        $notification = $this->notificationModel->find($id);

        if (! $notification) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Notification not found.',
            ]);
        }

        $this->notificationModel->markRead($id);

        return $this->response->setJSON([
            'success' => true,
            'unreadCount' => $this->notificationModel->countUnread(),
        ]);
    }

    public function markAllRead()
    {
        $this->notificationModel->markAllRead();

        return $this->response->setJSON([
            'success' => true,
            'unreadCount' => 0,
        ]);
    }
}
