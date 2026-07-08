<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\Database;

// Eto yung models
use App\Models\PostModel;
use App\Models\IncubateeModel;
use App\Models\IncubateeApplicationModel;
use App\Models\ContactMessageModel;
use App\Models\AdminModel;
use App\Models\CohortModel;
use App\Models\AdminNotificationModel;

// yung libraries
use App\Libraries\ImageUpload;
use App\Libraries\GuessStartupGame;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    // Preload Models
    protected $postModel;
    protected $incubateeModel;
    protected $applicationModel;
    protected $contactModel;
    protected $adminModel;
    protected $cohortModel;
    protected $adminNotificationModel;
    protected $db;

    // Libraries
    protected $guessStartupGame;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Instantiate models
        $this->postModel        = new PostModel();
        $this->incubateeModel   = new IncubateeModel();
        $this->applicationModel = new IncubateeApplicationModel();
        $this->contactModel     = new ContactMessageModel();
        $this->adminModel       = new AdminModel();
        $this->cohortModel      = new CohortModel();
        $this->adminNotificationModel = new AdminNotificationModel();
        $this->db               = Database::connect();

        // Instantiate libraries
        $this->guessStartupGame = new GuessStartupGame();

        // Share cohort list globally so header nav can render dynamic dropdowns
        $renderer = \Config\Services::renderer();
        $sharedData = [
            'navCohorts' => $this->cohortModel->getActiveNames(),
        ];

        $seg1 = strtolower((string) service('uri')->getSegment(1, ''));
        if ($seg1 === 'admin') {
            $sharedData['adminUnreadMessageCount'] = $this->contactModel->countUnread();
            $role = (string) session()->get('admin_role');
            if (in_array($role, ['admin', 'superadmin'], true)) {
                try {
                    $sharedData['adminUnreadNotificationCount'] = $this->adminNotificationModel->countUnread();
                    $sharedData['adminNotifications'] = $this->adminNotificationModel->getLatest(8);
                } catch (\Throwable $e) {
                    $sharedData['adminUnreadNotificationCount'] = 0;
                    $sharedData['adminNotifications'] = [];
                }
            }
        }

        $renderer->setData($sharedData);
    }
}
