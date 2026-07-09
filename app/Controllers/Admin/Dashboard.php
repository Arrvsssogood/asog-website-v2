<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    /**
     * Render the admin dashboard overview.
     *
     * Aggregates post, application, and message metrics for display.
     */
     
    public function index()
    {
        $role       = (string) session()->get('admin_role');
        $postCounts = $this->postModel->getCounts();

        $data = [
            'pageTitle'      => 'Dashboard',
            'activePage'     => 'dashboard',
            'role'           => $role,
            'totalPosts'     => $postCounts['total'],
            'publishedPosts' => $postCounts['published'],
            'draftPosts'     => $postCounts['drafts'],
            'featuredPosts'  => $postCounts['featured'],
            'recentPosts'    => $this->postModel->orderBy('createdAt', 'DESC')->findAll(5),
        ];

        if (in_array($role, ['admin', 'superadmin'], true)) {
            $appCounts                = $this->applicationModel->getCounts();
            $data['totalApps']        = $appCounts['total'];
            $data['pendingApps']      = $appCounts['pending'];
            $data['acceptedApps']     = $appCounts['accepted'];
            $data['rejectedApps']     = $appCounts['rejected'];
            $data['recentApps']       = $this->applicationModel->getAll(5);
            $data['unreadMessages']   = $this->contactModel->countUnread();
            $data['recentIncubatees'] = $this->incubateeModel->orderBy('createdAt', 'DESC')->findAll(5);
        }

        if ($role === 'superadmin') {
            $data['recentAdmins'] = $this->adminModel->orderBy('createdAt', 'DESC')->findAll(5);
        }

        return view('admin/layout/header', $data)
             . view('admin/dashboard', $data)
             . view('admin/layout/footer');
    }

    public function sidebarStatus()
    {
        $role = (string) session()->get('admin_role');
        $adminId = (int) session()->get('admin_id');
        $roleLabel = [
            'superadmin' => 'Super Admin',
            'admin'      => 'Admin',
            'editor'     => 'Editor',
        ][$role] ?? ucfirst($role ?: 'User');

        $unreadMessages = in_array($role, ['admin', 'superadmin'], true)
            ? $this->contactModel->countUnread()
            : 0;
        $unreadNotifications = 0;
        $notifications = [];

        if ($adminId > 0 && in_array($role, ['editor', 'admin', 'superadmin'], true)) {
            try {
                $unreadNotifications = $this->adminNotificationModel->countUnreadForAdmin($adminId, $role);
                $notifications = array_map(static function (array $notification): array {
                    $notificationId = (int) ($notification['id'] ?? 0);
                    $created = (string) ($notification['createdAt'] ?? '');

                    return [
                        'id'        => $notificationId,
                        'type'      => (string) ($notification['type'] ?? 'system_update'),
                        'title'     => (string) ($notification['title'] ?? 'Notification'),
                        'body'      => (string) ($notification['body'] ?? ''),
                        'link'      => (string) ($notification['link'] ?? site_url('admin')),
                        'isRead'    => ! empty($notification['userReadAt']),
                        'timeLabel' => $created !== '' && strtotime($created) !== false
                            ? date('M j, g:i A', strtotime($created))
                            : '',
                        'readUrl'   => site_url('admin/notifications/' . $notificationId . '/read'),
                    ];
                }, $this->adminNotificationModel->getLatestForAdmin($adminId, $role, 8));
            } catch (\Throwable $e) {
                $unreadNotifications = 0;
                $notifications = [];
            }
        }

        $nav = ['dashboard', 'posts', 'incubatees', 'faqs', 'settings'];
        if (in_array($role, ['admin', 'superadmin'], true)) {
            $nav[] = 'applications';
            $nav[] = 'organization';
            $nav[] = 'messages';
        }
        if ($role === 'superadmin') {
            $nav[] = 'admins';
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setJSON([
                'ok' => true,
                'user' => [
                    'name'      => (string) session()->get('admin_name'),
                    'email'     => (string) session()->get('admin_email'),
                    'role'      => $role,
                    'roleLabel' => $roleLabel,
                ],
                'counts' => [
                    'unreadMessages'      => $unreadMessages,
                    'unreadNotifications' => $unreadNotifications,
                ],
                'notifications' => [
                    'items'       => $notifications,
                    'unreadCount' => $unreadNotifications,
                ],
                'nav' => [
                    'allowed' => $nav,
                ],
            ]);
    }
}
