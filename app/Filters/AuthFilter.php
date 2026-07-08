<?php

namespace App\Filters;

use App\Models\AdminModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Verifies that a user is logged in before allowing access to protected routes.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if session exists and admin_id is set
        if (!session()->has('admin_id')) {
            return redirect()->to('/asog-admin');
        }

        $adminId = (int) session()->get('admin_id');
        $admin = $adminId > 0 ? (new AdminModel())->find($adminId) : null;

        if (! is_array($admin) || (int) ($admin['isActive'] ?? 0) !== 1) {
            session()->destroy();
            return redirect()->to('/asog-admin')->with('error', 'Your account access has changed. Please sign in again.');
        }

        session()->set([
            'admin_name'  => $admin['fullName'] ?? session()->get('admin_name'),
            'admin_email' => $admin['email'] ?? session()->get('admin_email'),
            'admin_role'  => $admin['role'] ?? session()->get('admin_role'),
            'logged_in'   => true,
        ]);

        return null;
    }

    /**
     * After request processing.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
