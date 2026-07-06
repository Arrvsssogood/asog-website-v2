<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LandingSettingModel;
use App\Models\OrganizationMemberModel;
use App\Models\PostModel;

class Landing extends BaseController
{
    public function index(): string
    {
        $postModel = new PostModel();
        $landingSettingModel = new LandingSettingModel();

        $cohortFilter = trim((string) $landingSettingModel->getValue(
            LandingSettingModel::KEY_INCUBATEES_FILTER,
            'all'
        ));

        $activeCohorts = $this->cohortModel->getActiveNames();
        if ($cohortFilter === '' || ($cohortFilter !== 'all' && ! in_array($cohortFilter, $activeCohorts, true))) {
            $cohortFilter = 'all';
        }

        $landingIncubatees = $cohortFilter === 'all'
            ? $this->incubateeModel->getPublished()
            : $this->incubateeModel->getPublishedByCohort($cohortFilter);

        $orgMemberModel = new OrganizationMemberModel();
        $coreTeamMembers = $orgMemberModel->getPublishedBySection(OrganizationMemberModel::SECTION_CORE_TEAM);

        $data = [
            'title'              => 'ASOG Technology Business Incubator (ASOG TBI) | CSPC',
            'metaDescription'    => 'ASOG TBI helps startups grow through incubation, mentorship, facilities, and innovation programs in Camarines Sur.',
            'isLanding'          => true,
            'isGuessStartupEnabled' => trim((string) $landingSettingModel->getValue(
                LandingSettingModel::KEY_GUESS_STARTUP_ENABLED,
                '1'
            )) !== '0',
            'isGuessStartupVisible' => trim((string) $landingSettingModel->getValue(
                LandingSettingModel::KEY_GUESS_STARTUP_VISIBLE,
                '1'
            )) !== '0',
            'heroSlides'         => $postModel->getFeaturedSlides(5),
            'heroPreloadImage'   => '',
            'featuredPost'       => $postModel->getFeatured(),
            'latestPosts'        => $postModel->getPublished(5),
            'featuredIncubatee'  => $this->incubateeModel->getFeatured(),
            'incubatees'         => $landingIncubatees,
            'landingIncubateesFilter' => $cohortFilter,
            'coreTeamMembers'    => $coreTeamMembers,
        ];

        if (! empty($data['heroSlides'][0]['imagePath'])) {
            $data['heroPreloadImage'] = base_url($data['heroSlides'][0]['imagePath']);
        }

        $content = view('templates/header', $data)
            . view('landing/hero', $data)
            . view('landing/about', $data)
            . view('landing/programs', $data)
            . view('landing/incubatees', $data)
            . view('landing/news', $data)
            . view('landing/organization', $data)
            . view('landing/cta', $data);

        if (! empty($data['isGuessStartupVisible'])) {
            $content .= view('landing/games', $data);
        }

        return $content
            . view('landing/contact', $data)
            . view('templates/footer');
    }
}
