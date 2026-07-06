<?php

namespace App\Controllers;

use App\Models\LandingSettingModel;
use App\Models\GamePlayerModel;
use App\Models\GameWordlePlayModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;

class Games extends BaseController
{
    private const OFFICIAL_SCHOOLS = [
        'Camarines Sur Polytechnic Colleges',
        'Naga College Foundation, Inc.',
        'Sorsogon State University',
        'STI College Legazpi',
        'University of Saint Anthony',
    ];

    private GamePlayerModel $playerModel;
    private GameWordlePlayModel $playModel;

    public function __construct()
    {
        $this->playerModel = new GamePlayerModel();
        $this->playModel = new GameWordlePlayModel();
    }

    public function guessStartup()
    {
        $this->guardGuessStartupVisible();

        $player = $this->currentPlayer();
        $today = date('Y-m-d');
        $todayPlay = null;
        $todayRank = null;
        $isTopThreeWinner = false;

        if ($player !== null) {
            $todayPlay = $this->playModel->findByPlayerAndDate((int) $player['id'], $today);
            $isTopThreeWinner = $this->playModel->hasNameAlreadyWonTopThreeForDate((string) ($player['fullName'] ?? ''), $today);

            if (is_array($todayPlay) && in_array((string) ($todayPlay['status'] ?? ''), [$this->playModel->statusSolved(), $this->playModel->statusForfeited()], true)) {
                $todayRank = $this->playModel->getRankByDateAndPlay((int) $player['id'], $today);
            }
        }

        $data = [
            'title' => 'Startup Hunt Lobby - ASOG TBI',
            'metaDescription' => 'Startup Hunt Edition: complete your name and school profile, play once per day, and compete on the daily startup leaderboard.',
            'player' => $player,
            'isGuessStartupEnabled' => $this->isGuessStartupEnabled(),
            'isProfileComplete' => $player !== null ? $this->playerModel->isProfileComplete($player) : false,
            'todayPlay' => $todayPlay,
            'todayRank' => $todayRank,
            'isTopThreeWinner' => $isTopThreeWinner,
            'leaderboardDate' => $today,
            'leaderboardRows' => $this->playModel->getTopByDate($today, 10),
        ];

        return view('templates/header', $data)
            . view('games/guess_startup', $data)
            . view('templates/footer', ['hideSiteFooter' => true]);
    }

    public function guessStartupLeaderboard()
    {
        $this->guardGuessStartupVisible();

        $player = $this->currentPlayer();
        $today = date('Y-m-d');
        $rawDate = trim((string) $this->request->getGet('date'));
        $playDate = $this->validatedDateOrToday($rawDate);
        $leaderboardRows = $this->playModel->getTopByDate($playDate, 10);
        $playerRank = null;
        $playerPlay = null;

        if ($player !== null) {
            $playerPlay = $this->playModel->findByPlayerAndDate((int) $player['id'], $playDate);
            if (is_array($playerPlay)) {
                $playerRank = $this->playModel->getRankByDateAndPlay((int) $player['id'], $playDate);
            }
        }

        $data = [
            'title' => 'Startup Hunt Leaderboard - ASOG TBI',
            'metaDescription' => 'Top 10 Startup Hunt players by day, with your current rank if you are outside the top 10.',
            'player' => $player,
            'isGuessStartupEnabled' => $this->isGuessStartupEnabled(),
            'todayDate' => $today,
            'leaderboardDate' => $playDate,
            'leaderboardRows' => $leaderboardRows,
            'playerRank' => $playerRank,
            'playerPlay' => $playerPlay,
        ];

        return view('templates/header', $data)
            . view('games/guess_startup_leaderboard', $data)
            . view('templates/footer', ['hideSiteFooter' => true]);
    }

    public function guessStartupPlay()
    {
        $this->guardGuessStartupVisible();

        if (! $this->isGuessStartupEnabled()) {
            return $this->redirectWhenGameDisabled();
        }

        $player = $this->currentPlayer();
        if ($player === null) {
            return redirect()->to('/games/guess-the-startup/profile')
                ->with('gs_notice', 'Enter your profile details before playing.');
        }

        if (! $this->playerModel->isProfileComplete($player)) {
            return redirect()->to('/games/guess-the-startup/profile')
                ->with('gs_error', 'Complete your profile first.');
        }

        $today = date('Y-m-d');

        if ($this->playModel->hasNameAlreadyWonTopThreeForDate((string) ($player['fullName'] ?? ''), $today)) {
            return redirect()->to('/games/guess-the-startup')
                ->with('gs_error', 'You already won Top 3 in past games. Top 3 winners are no longer eligible to play.');
        }

        $todayPlay = $this->playModel->findByPlayerAndDate((int) $player['id'], $today);

        $completedStatuses = [$this->playModel->statusSolved(), $this->playModel->statusForfeited()];
        if (is_array($todayPlay) && in_array((string) ($todayPlay['status'] ?? ''), $completedStatuses, true)) {
            return redirect()->to('/games/guess-the-startup')
                ->with('gs_error', 'You already used today\'s play. Check the leaderboard and return tomorrow.');
        }

        $security = config('Security');
        $data = [
            'title' => 'Startup Hunt Play - ASOG TBI',
            'metaDescription' => 'Startup Hunt daily round: 5 letters, 5 attempts, one official play per day.',
            'player' => $player,
            'hideSiteHeader' => true,
            'csrfHeaderName' => $security->headerName,
            'csrfCookieName' => $security->cookieName,
            'bodyClass' => 'overflow-hidden',
        ];

        return view('templates/header', $data)
            . view('games/guess_startup_play', $data)
            . view('templates/footer', ['hideSiteFooter' => true]);
    }

    public function guessStartupProfile()
    {
        $this->guardGuessStartupVisible();

        if (! $this->isGuessStartupEnabled()) {
            return $this->redirectWhenGameDisabled();
        }

        $player = $this->currentPlayer();

        $errors = [];
        $profileMeta = $this->extractProfileMeta($player ?? []);

        if (strtolower($this->request->getMethod()) === 'post') {
            $input = [
                'first_name' => (string) $this->request->getPost('first_name'),
                'middle_name' => (string) $this->request->getPost('middle_name'),
                'last_name' => (string) $this->request->getPost('last_name'),
                'school' => (string) $this->request->getPost('school'),
                'school_other' => (string) $this->request->getPost('school_other'),
            ];

            $rules = [
                'first_name' => [
                    'label' => 'First name',
                    'rules' => 'required|min_length[2]|max_length[60]',
                    'errors' => [
                        'required' => 'First name is required.',
                        'min_length' => 'First name must be at least 2 characters.',
                        'max_length' => 'First name must not exceed 60 characters.',
                    ],
                ],
                'middle_name' => [
                    'label' => 'Middle name',
                    'rules' => 'permit_empty|max_length[60]',
                    'errors' => [
                        'max_length' => 'Middle name must not exceed 60 characters.',
                    ],
                ],
                'last_name' => [
                    'label' => 'Last name',
                    'rules' => 'required|min_length[2]|max_length[60]',
                    'errors' => [
                        'required' => 'Last name is required.',
                        'min_length' => 'Last name must be at least 2 characters.',
                        'max_length' => 'Last name must not exceed 60 characters.',
                    ],
                ],

                'school' => [
                    'label' => 'School',
                    'rules' => 'required|max_length[190]',
                    'errors' => [
                        'required' => 'School is required.',
                        'max_length' => 'School must not exceed 190 characters.',
                    ],
                ],
                'school_other' => [
                    'label' => 'Other school',
                    'rules' => 'permit_empty|max_length[190]',
                    'errors' => [
                        'max_length' => 'Other school must not exceed 190 characters.',
                    ],
                ],
            ];

            if (! $this->validateData($input, $rules)) {
                $errors = $this->validator ? $this->validator->getErrors() : [];
            } else {
                $firstName = $this->sanitizeNamePart($input['first_name']);
                $middleName = $this->sanitizeNamePart($input['middle_name']);
                $lastName = $this->sanitizeNamePart($input['last_name']);
                $schoolChoice = $this->sanitizeSchool($input['school']);
                $schoolOther = $this->sanitizeSchool($input['school_other']);

                $namePattern = "/^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s'.-]*$/u";
                $schoolPattern = "/^[A-Za-z0-9&()\/.\-',\s]+$/";
                $schoolSelections = array_merge(self::OFFICIAL_SCHOOLS, ['Others']);
                $school = '';

                if ($firstName === '' || preg_match($namePattern, $firstName) !== 1) {
                    $errors['first_name'] = 'Enter a valid first name (letters, spaces, apostrophe, dot, and dash only).';
                }

                if ($middleName !== '' && preg_match($namePattern, $middleName) !== 1) {
                    $errors['middle_name'] = 'Enter a valid middle name (letters, spaces, apostrophe, dot, and dash only).';
                }

                if ($lastName === '' || preg_match($namePattern, $lastName) !== 1) {
                    $errors['last_name'] = 'Enter a valid last name (letters, spaces, apostrophe, dot, and dash only).';
                }

                if ($schoolChoice === '' || ! in_array($schoolChoice, $schoolSelections, true)) {
                    $errors['school'] = 'Please select your school from the list.';
                } elseif ($schoolChoice === 'Others') {
                    if ($schoolOther === '' || preg_match($schoolPattern, $schoolOther) !== 1) {
                        $errors['school_other'] = 'Enter your school name.';
                    } else {
                        $school = $schoolOther;
                    }
                } else {
                    $school = $schoolChoice;
                }

                if ($errors === []) {
                    $fullName = $this->buildFullName($firstName, $middleName, $lastName);
                    $playerId = is_array($player) ? (int) ($player['id'] ?? 0) : 0;
                    $excludePlayerId = $playerId > 0 ? $playerId : 0;

                    $duplicatePlayer = $this->playerModel->findActiveByIdentity($firstName, $lastName, $school, $excludePlayerId);

                    if (is_array($duplicatePlayer)) {
                        $duplicateName = (string) ($duplicatePlayer['fullName'] ?? '');
                        
                        if ($this->playModel->hasNameAlreadyWonTopThreeForDate($duplicateName, date('Y-m-d'))) {
                            $errors['school'] = 'You already won Top 3 in past games. Top 3 winners are no longer eligible to play.';
                        } else {
                            $alreadyRegisteredToday = $this->playModel->hasNameRegistrationForDate($duplicateName, date('Y-m-d'));

                            if ($alreadyRegisteredToday) {
                                $errors['school'] = 'This name and school combination is already registered for today.';
                            } else {
                                $existingPlayer = $this->playerModel->findActiveById((int) ($duplicatePlayer['id'] ?? 0));
                                if (is_array($existingPlayer)) {
                                    $this->setPlayerSession($existingPlayer);

                                    return redirect()->to('/games/guess-the-startup')
                                        ->with('gs_success', 'Profile recognized. You can play for today.');
                                }

                                $errors['school'] = 'Unable to load your existing profile right now. Please try again.';
                            }
                        }
                    } else {
                        $suspiciousMatch = $this->detectSuspiciousNameVariation($firstName, $lastName, $school, $excludePlayerId, date('Y-m-d'));
                        if ($suspiciousMatch !== null) {
                            $errors['first_name'] = 'This name variation appears to match an existing registration at your school. Please use your official name.';
                        } else {
                            $profileMeta = [
                                'first_name' => $firstName,
                                'middle_name' => $middleName,
                                'last_name' => $lastName,
                                'school' => $school,
                                'school_other' => $schoolChoice === 'Others' ? $schoolOther : '',
                            ];

                            if ($this->playModel->hasNameAlreadyWonTopThreeForDate($fullName, date('Y-m-d'))) {
                                $errors['school'] = 'You already won Top 3 in past games. Top 3 winners are no longer eligible to play.';
                            } else {
                                $identity = $this->buildLocalIdentity();
                                $saveData = [
                                    'fullName' => $fullName,
                                    'firstName' => $firstName,
                                    'middleName' => $middleName !== '' ? $middleName : null,
                                    'lastName' => $lastName,
                                    'school' => $school,
                                    'isActive' => 1,
                                    'lastLoginAt' => date('Y-m-d H:i:s'),
                                ];

                                if ($playerId > 0) {
                                    $saved = $this->playerModel->update($playerId, $saveData);
                                } else {
                                    $saveData['email'] = $identity['email'];
                                    $saveData['googleSub'] = $identity['googleSub'];
                                    $saveData['avatarUrl'] = null;
                                    $newIdRaw = $this->playerModel->insert($saveData, true);
                                    $playerId = is_numeric($newIdRaw) ? (int) $newIdRaw : 0;
                                    $saved = $playerId > 0;
                                }

                                $reloaded = $playerId > 0 ? $this->playerModel->findActiveById($playerId) : null;
                                $persisted = is_array($reloaded)
                                    && trim((string) ($reloaded['fullName'] ?? '')) === $fullName
                                    && trim((string) ($reloaded['school'] ?? '')) === $school;

                                if (! $persisted) {
                                    $this->logPlayerPersistenceFailure('profile_save', [
                                        'playerId' => $playerId,
                                        'expectedFullName' => $fullName,
                                        'expectedSchool' => $school,
                                        'saveReturned' => $saved ? 'true' : 'false',
                                    ]);
                                    $errors['school'] = 'Unable to save your profile right now. Please try again.';
                                } elseif (is_array($reloaded)) {
                                    $this->setPlayerSession($reloaded);

                                    return redirect()->to('/games/guess-the-startup')
                                        ->with('gs_success', 'Profile saved. Tap Play when you are ready.');
                                } else {
                                    $errors['school'] = 'Unable to load your profile right now. Please try again.';
                                }
                            }
                        }
                    }
                }
            }

            if (is_array($player) && isset($player['id'])) {
                $player = $this->playerModel->findActiveById((int) $player['id']) ?? $player;
            }
            $profileMeta = $this->extractProfileMeta($player ?? []);
        }

        $data = [
            'title' => 'Player Profile - ASOG TBI',
            'metaDescription' => 'Complete your Startup Hunt player profile using your name and school to access the daily round.',
            'player' => $player,
            'profileMeta' => $profileMeta,
            'errors' => $errors,
            'hideSiteHeader' => true,
            'bodyClass' => 'overflow-hidden',
        ];

        return view('templates/header', $data)
            . view('games/guess_startup_profile', $data)
            . view('templates/footer', ['hideSiteFooter' => true]);
    }

    public function google()
    {
        $this->guardGuessStartupVisible();

        if (! $this->isGuessStartupEnabled()) {
            return $this->redirectWhenGameDisabled();
        }

        if ($this->currentPlayer() !== null) {
            return redirect()->to('/games/guess-the-startup');
        }

        $client = $this->buildGoogleClient();
        if ($client === null) {
            return redirect()->to('/games/guess-the-startup')
                ->with('gs_error', 'Google login is not configured.');
        }

        $state = bin2hex(random_bytes(16));
        session()->set('gsp_google_state', $state);
        $client->setState($state);

        return redirect()->to($client->createAuthUrl());
    }


    public function googleCallback()
    {
        $this->guardGuessStartupVisible();

        if (! $this->isGuessStartupEnabled()) {
            return $this->redirectWhenGameDisabled();
        }

        $requestState = (string) $this->request->getGet('state');
        $sessionState = (string) session()->get('gsp_google_state');
        session()->remove('gsp_google_state');

        if ($sessionState === '' || $requestState === '' || ! hash_equals($sessionState, $requestState)) {
            return redirect()->to('/games/guess-the-startup')->with('gs_error', 'Invalid Google login state.');
        }

        $code = (string) $this->request->getGet('code');
        if ($code === '') {
            return redirect()->to('/games/guess-the-startup')->with('gs_error', 'Google login was cancelled.');
        }

        $client = $this->buildGoogleClient();
        if ($client === null) {
            return redirect()->to('/games/guess-the-startup')->with('gs_error', 'Google login is not configured.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (! is_array($token) || isset($token['error'])) {
            return redirect()->to('/games/guess-the-startup')->with('gs_error', 'Google could not verify your account.');
        }

        $client->setAccessToken($token);
        $oauth2 = new Oauth2($client);
        $googleUser = $oauth2->userinfo->get();

        $email = strtolower(trim((string) ($googleUser->email ?? '')));
        $googleSub = trim((string) ($googleUser->id ?? $googleUser->sub ?? ''));
        $fullName = trim((string) ($googleUser->name ?? ''));
        $avatarUrl = trim((string) ($googleUser->picture ?? ''));
        $isVerified = (bool) ($googleUser->verifiedEmail ?? false);

        if ($email === '' || ! $isVerified) {
            return redirect()->to('/games/guess-the-startup')->with('gs_error', 'Google email must be verified.');
        }

        $player = $this->playerModel->findByGoogleAccount($email, $googleSub);
        $now = date('Y-m-d H:i:s');

        if ($player === null) {
            $insertData = [
                'fullName' => $fullName !== '' ? $fullName : $email,
                'email' => $email,
                'googleSub' => $googleSub,
                'avatarUrl' => $avatarUrl !== '' ? $avatarUrl : null,
                'lastLoginAt' => $now,
                'isActive' => 1,
            ];

            $newIdRaw = $this->playerModel->insert($insertData, true);
            $newId = is_numeric($newIdRaw) ? (int) $newIdRaw : 0;
            if ($newId <= 0) {
                $this->logPlayerPersistenceFailure('google_signup_insert', [
                    'email' => $email,
                    'googleSub' => $googleSub,
                ]);

                return redirect()->to('/games/guess-the-startup')
                    ->with('gs_error', 'Unable to save your account data right now. Please try signing in again.');
            }

            $player = $this->playerModel->findActiveById((int) $newId);
        } else {
            $updateData = [
                'googleSub' => $googleSub !== '' ? $googleSub : (string) ($player['googleSub'] ?? ''),
                'email' => $email,
                'lastLoginAt' => $now,
                'isActive' => 1,
            ];

            if ($avatarUrl !== '') {
                $updateData['avatarUrl'] = $avatarUrl;
            }

            if ($fullName !== '' && trim((string) ($player['fullName'] ?? '')) === '') {
                $updateData['fullName'] = $fullName;
            }

            $updated = $this->playerModel->update((int) $player['id'], $updateData);
            if (! $updated) {
                $this->logPlayerPersistenceFailure('google_signup_update', [
                    'playerId' => (int) ($player['id'] ?? 0),
                    'email' => $email,
                    'googleSub' => $googleSub,
                ]);

                return redirect()->to('/games/guess-the-startup')
                    ->with('gs_error', 'Unable to update your account data right now. Please try signing in again.');
            }

            $player = $this->playerModel->findActiveById((int) $player['id']);
        }

        if (! is_array($player)) {
            return redirect()->to('/games/guess-the-startup')->with('gs_error', 'Unable to create your player account.');
        }

        $this->setPlayerSession($player);

        if (! $this->playerModel->isProfileComplete($player)) {
            return redirect()->to('/games/guess-the-startup/profile')
                ->with('gs_notice', 'Complete your profile to unlock Startup Hunt gameplay.');
        }

        return redirect()->to('/games/guess-the-startup')
            ->with('gs_success', 'Signed in successfully.');
    }

    public function signOut()
    {
        $this->guardGuessStartupVisible();

        session()->remove([
            'gsp_player_id',
            'gsp_player_name',
            'gsp_player_email',
            'gsp_player_avatar',
        ]);

        return redirect()->to('/games/guess-the-startup')
            ->with('gs_notice', 'You have signed out.');
    }

    private function isGuessStartupEnabled(): bool
    {
        $settingModel = new LandingSettingModel();
        $value = trim((string) $settingModel->getValue(LandingSettingModel::KEY_GUESS_STARTUP_ENABLED, '1'));

        return $value !== '0';
    }

    private function isGuessStartupVisible(): bool
    {
        $settingModel = new LandingSettingModel();
        $value = trim((string) $settingModel->getValue(LandingSettingModel::KEY_GUESS_STARTUP_VISIBLE, '1'));

        return $value !== '0';
    }

    private function guardGuessStartupVisible(): void
    {
        if (! $this->isGuessStartupVisible()) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    private function redirectWhenGameDisabled()
    {
        return redirect()->to('/games/guess-the-startup')
            ->with('gs_notice', 'Guess The Startup is currently paused. You can still view the lobby and leaderboard, but play is disabled for now.');
    }

    private function extractProfileMeta(array $player): array
    {
        $firstName = trim((string) ($player['firstName'] ?? ''));
        $middleName = trim((string) ($player['middleName'] ?? ''));
        $lastName = trim((string) ($player['lastName'] ?? ''));

        if (($firstName === '' || $lastName === '') && trim((string) ($player['fullName'] ?? '')) !== '') {
            $parts = $this->splitProfileName((string) $player['fullName']);
            if (is_array($parts)) {
                $firstName = $firstName !== '' ? $firstName : trim((string) ($parts['first_name'] ?? ''));
                $middleName = $middleName !== '' ? $middleName : trim((string) ($parts['middle_name'] ?? ''));
                $lastName = $lastName !== '' ? $lastName : trim((string) ($parts['last_name'] ?? ''));
            }
        }

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'school' => trim((string) ($player['school'] ?? '')),
        ];
    }

    private function splitProfileName(string $fullName): ?array
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return null;
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = trim(implode(' ', $parts));

        if (! is_string($firstName) || ! is_string($lastName) || trim($firstName) === '' || trim($lastName) === '') {
            return null;
        }

        return [
            'first_name' => trim($firstName),
            'middle_name' => $middleName,
            'last_name' => trim($lastName),
        ];
    }

    private function buildLocalIdentity(): array
    {
        try {
            $token = bin2hex(random_bytes(12));
        } catch (\Throwable $e) {
            $token = str_replace('.', '', uniqid('local', true));
        }

        return [
            'email' => 'local+' . $token . '@startuphunt.local',
            'googleSub' => 'local_' . $token,
        ];
    }

    private function sanitizeNamePart(string $value): string
    {
        $clean = strip_tags($value);
        $clean = preg_replace('/\s+/u', ' ', trim($clean)) ?? '';
        $clean = preg_replace("/[^\\p{L}\\p{M}\\s'.-]/u", '', $clean) ?? '';
        return trim($clean);
    }

    private function sanitizeSchool(string $value): string
    {
        $clean = strip_tags($value);
        $clean = preg_replace('/\s+/u', ' ', trim($clean)) ?? '';
        $clean = preg_replace("/[^A-Za-z0-9&()\/.\-',\s]/", '', $clean) ?? '';
        return trim($clean);
    }

    private function buildFullName(string $firstName, string $middleName, string $lastName): string
    {
        $parts = array_filter([$firstName, $middleName, $lastName], static fn(string $part): bool => $part !== '');
        return implode(' ', $parts);
    }

    /**
     * Detects suspicious name variations to prevent manipulation attempts.
     * Examples: 
     *   - "Jan Andrew Barte" vs "Andrew Barte" (same last name + school)
     *   - Reordered name parts
     *   - Added/removed middle names to bypass duplication checks
     *   - debugged completely different names but sharing all significant parts (e.g. "Barte Jan Andrew" vs "Andrew Jan Barte")
     */
    private function detectSuspiciousNameVariation(string $firstName, string $lastName, string $school, int $excludePlayerId, string $playDate): ?array
    {
        // Get players at the same school that already have a record for the given day.
        $query = $this->playerModel->builder()
            ->select('game_players.id, game_players.firstName, game_players.lastName, game_players.fullName')
            ->join('game_wordle_plays', 'game_wordle_plays.playerId = game_players.id', 'inner')
            ->where('game_players.isActive', 1)
            ->where('game_players.id !=', $excludePlayerId)
            ->where('game_players.school', $school)
            ->where('game_wordle_plays.playDate', $playDate)
            ->groupBy('game_players.id, game_players.firstName, game_players.lastName, game_players.fullName')
            ->get()
            ->getResultArray();

        if (empty($query)) {
            return null;
        }

        $currentNameParts = array_merge(
            preg_split('/\s+/', strtolower(trim($firstName)), -1, PREG_SPLIT_NO_EMPTY) ?? [],
            preg_split('/\s+/', strtolower(trim($lastName)), -1, PREG_SPLIT_NO_EMPTY) ?? []
        );

        foreach ($query as $existing) {
            $existingFirstName = strtolower(trim((string) ($existing['firstName'] ?? '')));
            $existingLastName = strtolower(trim((string) ($existing['lastName'] ?? '')));
            $existingFullName = strtolower(trim((string) ($existing['fullName'] ?? '')));
            
            $existingNameParts = array_merge(
                preg_split('/\s+/', $existingFirstName, -1, PREG_SPLIT_NO_EMPTY) ?? [],
                preg_split('/\s+/', $existingLastName, -1, PREG_SPLIT_NO_EMPTY) ?? []
            );

            // Check if last names match exactly (core identifier)
            if ($existingLastName === strtolower(trim($lastName))) {
                // Check for suspicious patterns:
                // 1. Same last name, but different first name arrangement
                $currentFirstParts = preg_split('/\s+/', strtolower(trim($firstName)), -1, PREG_SPLIT_NO_EMPTY) ?? [];
                $existingFirstParts = preg_split('/\s+/', $existingFirstName, -1, PREG_SPLIT_NO_EMPTY) ?? [];
                
                // If one first name is contained in or shares parts with the other
                $sharedParts = array_intersect($currentFirstParts, $existingFirstParts);
                if (count($sharedParts) > 0 && $currentFirstParts !== $existingFirstParts) {
                    return $existing;
                }
                
                // 2. Check if names share all significant parts (reordered names)
                $intersection = array_intersect($currentNameParts, $existingNameParts);
                $currentMinusStopwords = array_diff($currentNameParts, ['de', 'da', 'le', 'la', 'van']);
                $existingMinusStopwords = array_diff($existingNameParts, ['de', 'da', 'le', 'la', 'van']);
                
                if (count($currentMinusStopwords) >= 2 && count($existingMinusStopwords) >= 2) {
                    if (count($intersection) === count($currentMinusStopwords)) {
                        return $existing;
                    }
                }
            }
        }

        return null;
    }

    private function buildGoogleClient(): ?GoogleClient
    {
        if (! class_exists(GoogleClient::class)) {
            return null;
        }

        $clientId = trim((string) env('googleOAuthClientId', ''));
        $clientSecret = trim((string) env('googleOAuthClientSecret', ''));

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);

        $redirectUri = trim((string) env('googleOAuthGameRedirectUri', ''));
        if ($redirectUri === '') {
            $redirectUri = site_url('games/guess-the-startup/google/callback');
        }
        $client->setRedirectUri($redirectUri);

        $client->setAccessType('online');
        $client->setPrompt('select_account');
        $client->setIncludeGrantedScopes(true);
        $client->addScope('openid');
        $client->addScope('email');
        $client->addScope('profile');

        return $client;
    }

    private function setPlayerSession(array $player): void
    {
        session()->regenerate(true);
        session()->set([
            'gsp_player_id' => (int) $player['id'],
            'gsp_player_name' => (string) ($player['fullName'] ?? ''),
            'gsp_player_email' => (string) ($player['email'] ?? ''),
            'gsp_player_avatar' => (string) ($player['avatarUrl'] ?? ''),
        ]);
    }

    private function currentPlayer(): ?array
    {
        $playerId = (int) session()->get('gsp_player_id');
        if ($playerId <= 0) {
            return null;
        }

        return $this->playerModel->findActiveById($playerId);
    }

    private function logPlayerPersistenceFailure(string $operation, array $context = []): void
    {
        $dbError = db_connect()->error();
        $modelErrors = $this->playerModel->errors();

        log_message(
            'error',
            'Game player persistence failed during {operation}. Model errors: {modelErrors}. DB error: {dbError}. Context: {context}',
            [
                'operation' => $operation,
                'modelErrors' => (string) json_encode($modelErrors, JSON_UNESCAPED_SLASHES),
                'dbError' => (string) json_encode($dbError, JSON_UNESCAPED_SLASHES),
                'context' => (string) json_encode($context, JSON_UNESCAPED_SLASHES),
            ]
        );
    }

    private function validatedDateOrToday(string $candidate): string
    {
        $today = date('Y-m-d');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $candidate)) {
            return $today;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $candidate));
        if (! checkdate($month, $day, $year)) {
            return $today;
        }

        $selected = sprintf('%04d-%02d-%02d', $year, $month, $day);
        if ($selected > $today) {
            return $today;
        }

        return $selected;
    }
}
