<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');

/*
 * ────────────────────────────────────────────────────────────────────────────
 * Landing Page Routes
 * ────────────────────────────────────────────────────────────────────────────
 */

$routes->get('/', 'Landing::index');
$routes->get('/landing', 'Landing::index');
$routes->match(['GET', 'HEAD'], '/sitemap.xml', 'Sitemap::index');
$routes->get('/about', 'About::index');
$routes->get('/about/logo', 'About::logo');

/*
 * ────────────────────────────────────────────────────────────────────────────
 * Page Routes per Section
 * ────────────────────────────────────────────────────────────────────────────
 */
$routes->get('/programs', 'Programs::index');
$routes->get('/services', 'Programs::services');
$routes->get('/facilities', 'Facilities::index');
$routes->get('/incubatees', 'Incubatees::index');
$routes->get('/incubatees/cohort-(:num)', 'Incubatees::cohort/$1');
$routes->get('/apply', 'Incubatees::apply');
$routes->get('/apply/form', 'Incubatees::applyForm');
$routes->post('/apply/form', 'Incubatees::applyFormStore');
$routes->get('/apply/form/check-email', 'Incubatees::checkEmail');
$routes->get('/apply/form/thank-you', 'Incubatees::applyFormThankYou');
$routes->get('/apply/revalidate/(:segment)', 'Incubatees::revalidateForm/$1');
$routes->post('/apply/revalidate/(:segment)', 'Incubatees::revalidateFormStore/$1');

// Legacy apply paths: keep working but redirect to canonical /apply URLs
$routes->addRedirect('/incubatees/apply', '/apply', 301);
$routes->addRedirect('/incubatees/apply/form', '/apply/form', 301);
$routes->addRedirect('/incubatees/apply/form/check-email', '/apply/form/check-email', 301);
$routes->addRedirect('/incubatees/apply/form/thank-you', '/apply/form/thank-you', 301);
$routes->get('/news', 'News::index');
$routes->get('/news/(:segment)', 'News::show/$1');
$routes->get('/organization', 'Organization::index');
$routes->get('/contact', 'Contact::index');
$routes->post('/contact/send', 'Contact::send');
$routes->get('/games/guess-the-startup', 'Games::guessStartup');
$routes->get('/games/guess-the-startup/play', 'Games::guessStartupPlay');
$routes->get('/games/guess-the-startup/leaderboard', 'Games::guessStartupLeaderboard');
$routes->get('/games/guess-the-startup/google', 'Games::google');
$routes->get('/games/guess-the-startup/google/callback', 'Games::googleCallback');
$routes->match(['GET', 'POST'], '/games/guess-the-startup/profile', 'Games::guessStartupProfile', ['filter' => 'csrf']);
$routes->get('/games/guess-the-startup/sign-out', 'Games::signOut');

// Lightweight API endpoints used by frontend components
$routes->get('/api/sdgs', 'Api\Sdgs::index');
$routes->get('/api/incubatees', 'Api\Incubatees::index');
$routes->get('/api/games/guess-startup/leaderboard', 'Api\Games::leaderboard');
$routes->post('/api/games/guess-startup/start', 'Api\Games::start');
$routes->post('/api/games/guess-startup/submit', 'Api\Games::submit');
$routes->post('/api/games/guess-startup/abandon', 'Api\Games::abandon');

/*
 * ────────────────────────────────────────────────────────────────────────────
 * Uploaded File Serving (writable/uploads → /uploads/...)
 * Only handles application files stored in writable/uploads/applications/.
 * Post images live in public/uploads/posts/ and are served directly.
 * ────────────────────────────────────────────────────────────────────────────
 */
$routes->get('uploads/applications/(.+)', 'Uploads::serve/$1');

/*
 * ────────────────────────────────────────────────────────────────────────────
 * ADMIN AUTHENTICATION ROUTES (Hidden Login)
 * ────────────────────────────────────────────────────────────────────────────
 */
$routes->get('/asog-admin', 'Auth::login');
$routes->post('/asog-admin', 'Auth::authenticate');
$routes->get('/asog-admin/google', 'Auth::google');
$routes->get('/asog-admin/google/callback', 'Auth::googleCallback');
$routes->group('asog-admin/gmail-api', ['filter' => 'auth'], function ($routes) {
    $routes->group('', ['filter' => 'role:superadmin'], function ($routes) {
        $routes->get('connect', 'Admin\GmailApiSetup::connect');
        $routes->get('callback', 'Admin\GmailApiSetup::callback');
    });
});
$routes->get('/asog-admin/logout', 'Auth::logout');
$routes->get('/asog-admin/forgot-password', 'Auth::forgotPassword');
$routes->post('/asog-admin/forgot-password', 'Auth::sendResetLink');
$routes->get('/asog-admin/reset-password/(:segment)', 'Auth::resetPassword/$1');
$routes->post('/asog-admin/reset-password/(:segment)', 'Auth::updateForgottenPassword/$1');

/*
 * ────────────────────────────────────────────────────────────────────────────
 * ADMIN DASHBOARD & CONTENT MANAGEMENT ROUTES (Protected by Auth Middleware)
 * ────────────────────────────────────────────────────────────────────────────
 */
$routes->group('admin', ['filter' => 'auth'], function ($routes) {

    // ── editor + admin + superadmin ──────────────────────────────────────
    $routes->group('', ['filter' => 'role:editor'], function ($routes) {
        $routes->get('/', 'Admin\Dashboard::index');

        // Posts / Blog Management
        $routes->get('posts', 'Admin\PostsAdmin::index');
        $routes->get('posts/create', 'Admin\PostsAdmin::create');
        $routes->post('posts', 'Admin\PostsAdmin::store');
        $routes->post('posts/upload-image', 'Admin\PostsAdmin::uploadImage');
        $routes->post('posts/featured-order', 'Admin\PostsAdmin::saveFeaturedOrder');
        $routes->get('posts/(:num)/edit', 'Admin\PostsAdmin::edit/$1');
        $routes->put('posts/(:num)', 'Admin\PostsAdmin::update/$1');
        $routes->delete('posts/(:num)', 'Admin\PostsAdmin::delete/$1');
    });

    // ── admin + superadmin ───────────────────────────────────────────────
    $routes->group('', ['filter' => 'role:admin'], function ($routes) {

        // Incubatee Applications
        $routes->get('applications', 'Admin\ApplicationsAdmin::index');
        $routes->get('applications/(:num)', 'Admin\ApplicationsAdmin::show/$1');
        $routes->post('applications/settings', 'Admin\ApplicationsAdmin::updateSettings');
        $routes->put('applications/(:num)/status', 'Admin\ApplicationsAdmin::updateStatus/$1');
        $routes->put('applications/(:num)/toggle-archive', 'Admin\ApplicationsAdmin::toggleArchive/$1');
        $routes->post('applications/bulk', 'Admin\ApplicationsAdmin::bulk');

        // Contact Messages
        $routes->get('messages', 'Admin\MessagesAdmin::index');
        $routes->get('messages/(:num)', 'Admin\MessagesAdmin::show/$1');
        $routes->put('messages/(:num)/read', 'Admin\MessagesAdmin::toggleRead/$1');
        $routes->delete('messages/(:num)', 'Admin\MessagesAdmin::delete/$1');
        $routes->post('messages/bulk', 'Admin\MessagesAdmin::bulkAction');

        // Incubatees Management
        $routes->get('incubatees', 'Admin\IncubateesAdmin::index');
        $routes->get('incubatees/create', 'Admin\IncubateesAdmin::create');
        $routes->post('incubatees', 'Admin\IncubateesAdmin::store');
        $routes->post('incubatees/reorder', 'Admin\IncubateesAdmin::saveOrder');
        $routes->get('incubatees/(:num)/edit', 'Admin\IncubateesAdmin::edit/$1');
        $routes->post('incubatees/(:num)/update', 'Admin\IncubateesAdmin::update/$1');
        $routes->post('incubatees/(:num)/delete', 'Admin\IncubateesAdmin::delete/$1');

        // Apply Page FAQ Management
        $routes->get('faqs', 'Admin\FaqsAdmin::index');
        $routes->post('faqs', 'Admin\FaqsAdmin::store');
        $routes->post('faqs/section', 'Admin\FaqsAdmin::updateSection');
        $routes->post('faqs/(:num)/update', 'Admin\FaqsAdmin::update/$1');
        $routes->post('faqs/(:num)/move/(:alpha)', 'Admin\FaqsAdmin::move/$1/$2');
        $routes->post('faqs/(:num)/delete', 'Admin\FaqsAdmin::delete/$1');

        // Organization
        $routes->get('organization', 'Admin\OrganizationAdmin::index');
        $routes->get('organization/modal', 'Admin\OrganizationAdmin::modalCreate');
        $routes->get('organization/modal/(:num)', 'Admin\OrganizationAdmin::modalEdit/$1');
        $routes->post('organization/modal', 'Admin\OrganizationAdmin::modalStore');
        $routes->post('organization/modal/(:num)', 'Admin\OrganizationAdmin::modalUpdate/$1');
        $routes->post('organization/reorder', 'Admin\OrganizationAdmin::saveOrder');
        $routes->get('organization/members/create', 'Admin\OrganizationAdmin::create');
        $routes->post('organization/members', 'Admin\OrganizationAdmin::store');
        $routes->get('organization/members/(:num)/edit', 'Admin\OrganizationAdmin::edit/$1');
        $routes->post('organization/members/(:num)/update', 'Admin\OrganizationAdmin::update/$1');
        $routes->post('organization/members/(:num)/delete', 'Admin\OrganizationAdmin::delete/$1');
        $routes->post('organization/members/(:num)/move/(:alpha)', 'Admin\OrganizationAdmin::move/$1/$2');

        // Cohort Management (AJAX)
        $routes->post('cohorts/add', 'Admin\IncubateesAdmin::addCohort');
        $routes->post('cohorts/(:num)/delete', 'Admin\IncubateesAdmin::deleteCohort/$1');

        // Legacy redirect for old bookmarks
        $routes->addRedirect('games', 'admin/settings');
    });

    // ── superadmin only ──────────────────────────────────────────────────
    $routes->group('', ['filter' => 'role:superadmin'], function ($routes) {

        // Site Settings
        $routes->get('settings', 'Admin\SettingsAdmin::index');
        $routes->post('settings/guess-startup/availability', 'Admin\SettingsAdmin::updateGuessStartupAvailability');
        $routes->post('settings/interns-visibility', 'Admin\SettingsAdmin::updateInternsVisibility');
        $routes->post('settings/homepage-incubatees-filter', 'Admin\SettingsAdmin::updateLandingFilter');

        // Account Management
        $routes->get('accounts', 'Admin\AdminsManagement::index');
        $routes->get('accounts/create', 'Admin\AdminsManagement::create');
        $routes->post('accounts', 'Admin\AdminsManagement::store');
        $routes->get('accounts/(:num)/edit', 'Admin\AdminsManagement::edit/$1');
        $routes->put('accounts/(:num)', 'Admin\AdminsManagement::update/$1');
        $routes->delete('accounts/(:num)', 'Admin\AdminsManagement::delete/$1');
    });
});
