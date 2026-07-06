<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\GuessStartupGame;
use App\Models\GamePlayerModel;
use App\Models\GameWordlePlayModel;
use App\Models\LandingSettingModel;

class Games extends BaseController
{
    private const SESSION_PREFIX = 'guess_startup_';
    private const ACTIVE_SESSION_PREFIX = 'guess_startup_active_';
    private const SUBMIT_THROTTLE_MS = 50;
    private const ROUND_COUNT = 5;

    private GuessStartupGame $engine;
    private GamePlayerModel $playerModel;
    private GameWordlePlayModel $playModel;

    public function __construct()
    {
        $this->engine = new GuessStartupGame();
        $this->playerModel = new GamePlayerModel();
        $this->playModel = new GameWordlePlayModel();
    }

    public function leaderboard()
    {
        if (! $this->isGuessStartupVisible()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is not available.',
            ], 404);
        }

        if (! $this->isGuessStartupEnabled()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is currently unavailable.',
            ], 403);
        }

        $rawDate = trim((string) $this->request->getGet('date'));
        $playDate = $this->validatedDateOrToday($rawDate);

        $rows = $this->playModel->getTopByDate($playDate, 10);

        return $this->respondJson([
            'date' => $playDate,
            'rows' => $this->normalizeLeaderboardRows($rows),
        ]);
    }

    public function start()
    {
        if (! $this->isGuessStartupVisible()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is not available.',
            ], 404);
        }

        if (! $this->isGuessStartupEnabled()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is currently unavailable.',
            ], 403);
        }

        $player = $this->resolvePlayer();
        if ($player === null) {
            return $this->respondJson([
                'error' => 'Sign in first to play today\'s round.',
            ], 401);
        }

        if (! $this->playerModel->isProfileComplete($player)) {
            return $this->respondJson([
                'error' => 'Complete your profile before playing.',
            ], 403);
        }

        $payload = $this->payload();
        $requestedSessionId = trim((string) ($payload['session_id'] ?? ''));
        $activeSessionId = $requestedSessionId !== ''
            ? $requestedSessionId
            : trim((string) session()->get($this->activeSessionKey((int) $player['id'])));

        if ($activeSessionId !== '') {
            $resumeKey = self::SESSION_PREFIX . $activeSessionId;
            $resumeState = session()->get($resumeKey);

            if (
                is_array($resumeState)
                && (int) ($resumeState['player_id'] ?? 0) === (int) $player['id']
                && empty($resumeState['finished'])
            ) {
                $existingPlay = $this->playModel->findByPlayerAndDate((int) $player['id'], date('Y-m-d'));
                if (is_array($existingPlay) && (int) ($existingPlay['id'] ?? 0) === (int) ($resumeState['play_id'] ?? 0)) {
                    return $this->respondJson($this->buildSessionPayload($activeSessionId, $resumeState, true));
                }
            }

            session()->remove($resumeKey);
            session()->remove($this->activeSessionKey((int) $player['id']));
        }

        $today = date('Y-m-d');
        $existingPlay = $this->playModel->findByPlayerAndDate((int) $player['id'], $today);
        if (is_array($existingPlay)) {
            return $this->respondJson([
                'already_played' => true,
                'error' => 'You already have an active or finished Startup Hunt run for today.',
                'play' => $this->normalizePlaySummary($existingPlay),
                'leaderboard' => $this->normalizeLeaderboardRows($this->playModel->getTopByDate($today, 10)),
            ], 409);
        }

        $rounds = $this->engine->buildSession(self::ROUND_COUNT);
        $round = $rounds[0] ?? null;
        if (! is_array($round)) {
            return $this->respondJson([
                'error' => 'Unable to create a game round right now.',
            ], 500);
        }

        $playId = $this->playModel->insert([
            'playerId' => (int) $player['id'],
            'playDate' => $today,
            'answerWord' => null,
            'status' => $this->playModel->statusInProgress(),
            'attemptsUsed' => 0,
            'elapsedMs' => 0,
            'score' => 0,
            'startedAt' => date('Y-m-d H:i:s'),
        ], true);

        if (! is_int($playId) || $playId <= 0) {
            $conflictPlay = $this->playModel->findByPlayerAndDate((int) $player['id'], $today);
            if (is_array($conflictPlay)) {
                return $this->respondJson([
                    'already_played' => true,
                    'error' => 'You already used today\'s Startup Hunt trial. Come back tomorrow.',
                    'play' => $this->normalizePlaySummary($conflictPlay),
                    'leaderboard' => $this->normalizeLeaderboardRows($this->playModel->getTopByDate($today, 10)),
                ], 409);
            }

            return $this->respondJson([
                'error' => 'Could not initialize your play record.',
            ], 500);
        }

        $sessionId = bin2hex(random_bytes(8));

        $state = [
            'player_id' => (int) $player['id'],
            'play_id' => $playId,
            'rounds' => $rounds,
            'answered' => [],
            'guess_feedback' => [],
            'started_at_ms' => (int) round(microtime(true) * 1000),
            'last_submit_at_ms' => 0,
            'score' => 0,
            'finished' => false,
        ];

        session()->set(self::SESSION_PREFIX . $sessionId, $state);
        session()->set($this->activeSessionKey((int) $player['id']), $sessionId);

        return $this->respondJson($this->buildSessionPayload($sessionId, $state, false));
    }

    public function abandon()
    {
        if (! $this->isGuessStartupVisible()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is not available.',
            ], 404);
        }

        if (! $this->isGuessStartupEnabled()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is currently unavailable.',
            ], 403);
        }

        $payload = $this->payload();

        $sessionId = trim((string) ($payload['session_id'] ?? ''));
        if ($sessionId === '') {
            return $this->respondJson([
                'error' => 'Session ID is required.',
            ], 400);
        }

        $key = self::SESSION_PREFIX . $sessionId;
        $state = session()->get($key);

        if (is_array($state) && empty($state['finished'])) {
            $this->forfeitSession($state, false);
            session()->set($key, $state);
            session()->remove($this->activeSessionKey((int) ($state['player_id'] ?? 0)));
        }

        session()->remove($key);

        return $this->respondJson([
            'abandoned' => true,
        ]);
    }

    public function submit()
    {
        if (! $this->isGuessStartupVisible()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is not available.',
            ], 404);
        }

        if (! $this->isGuessStartupEnabled()) {
            return $this->respondJson([
                'error' => 'Guess The Startup is currently unavailable.',
            ], 403);
        }

        $player = $this->resolvePlayer();
        if ($player === null) {
            return $this->respondJson([
                'error' => 'Sign in first to submit answers.',
            ], 401);
        }

        $payload = $this->payload();

        $sessionId = trim((string) ($payload['session_id'] ?? ''));
        $roundId = (int) ($payload['round_id'] ?? 0);
        $answer = $payload['answer'] ?? '';
        $forceForfeit = (bool) ($payload['forfeit'] ?? false);

        if ($sessionId === '' || $roundId <= 0) {
            return $this->respondJson([
                'error' => 'Invalid request payload.',
            ], 400);
        }

        $key = self::SESSION_PREFIX . $sessionId;
        $state = session()->get($key);

        if (! is_array($state) || empty($state['rounds'])) {
            return $this->respondJson([
                'error' => 'Session not found or expired.',
            ], 404);
        }

        if ((int) ($state['player_id'] ?? 0) !== (int) $player['id']) {
            return $this->respondJson([
                'error' => 'Session ownership mismatch.',
            ], 403);
        }

        if (! empty($state['finished'])) {
            return $this->respondJson([
                'finished' => true,
                'total_score' => (int) ($state['score'] ?? 0),
            ]);
        }

        $nowMs = (int) round(microtime(true) * 1000);
        $lastSubmitMs = (int) ($state['last_submit_at_ms'] ?? 0);
        if (($nowMs - $lastSubmitMs) < self::SUBMIT_THROTTLE_MS) {
            return $this->respondJson([
                'error' => 'Please wait a moment before submitting again.',
            ], 429);
        }
        $state['last_submit_at_ms'] = $nowMs;

        $round = $this->findRoundById($state['rounds'], $roundId);
        if (! is_array($round)) {
            return $this->respondJson([
                'error' => 'Round not found.',
            ], 404);
        }

        if (isset($state['answered'][$roundId])) {
            return $this->respondJson([
                'already_answered' => true,
                'result' => $state['answered'][$roundId],
                'total_score' => (int) ($state['score'] ?? 0),
            ]);
        }

        $currentAttempts = (int) ($state['attempts'][$roundId] ?? 0);
        $maxGuesses = max(1, (int) ($round['max_guesses'] ?? $this->engine->maxAttempts()));

        if ($forceForfeit) {
            $response = $this->forfeitRound($state, $round, $roundId, $key, false);
            return $this->respondJson($response);
        }

        $attemptFeedback = $this->engine->attemptFeedback($round, $answer);
        if (! empty($attemptFeedback['error'])) {
            session()->set($key, $state);
            return $this->respondJson([
                'correct' => false,
                'message' => $attemptFeedback['error'],
                'attempt_feedback' => $attemptFeedback,
                'remaining_guesses' => max(0, $maxGuesses - $currentAttempts),
                'total_score' => (int) ($state['score'] ?? 0),
            ]);
        }

        $currentAttempts++;
        $state['attempts'][$roundId] = $currentAttempts;
        $tilesForHistory = is_array($attemptFeedback['tiles'] ?? null) ? $attemptFeedback['tiles'] : [];
        if ($tilesForHistory !== []) {
            if (! isset($state['guess_feedback'][$roundId]) || ! is_array($state['guess_feedback'][$roundId])) {
                $state['guess_feedback'][$roundId] = [];
            }
            $state['guess_feedback'][$roundId][] = $tilesForHistory;
        }

        $isCorrect = $this->engine->evaluateAnswer($round, $answer);

        if (! $isCorrect) {
            if ($currentAttempts >= $maxGuesses) {
                $response = $this->forfeitRound($state, $round, $roundId, $key, true);
                $response['attempt_feedback'] = $attemptFeedback;
                $response['message'] = 'No guesses left. Round lost.';
                return $this->respondJson($response);
            }

            session()->set($key, $state);

            return $this->respondJson([
                'correct' => false,
                'message' => 'Not quite. Try again.',
                'attempt_feedback' => $attemptFeedback,
                'remaining_guesses' => max(0, $maxGuesses - $currentAttempts),
                'total_score' => (int) ($state['score'] ?? 0),
            ]);
        }

        $elapsedMs = $this->elapsedFromState($state);
        $wrongGuesses = max(0, $currentAttempts - 1);
        $scoreBreakdown = $this->engine->scoreRound($round, $elapsedMs, $wrongGuesses, 0);

        $state['score'] = (int) ($state['score'] ?? 0) + (int) $scoreBreakdown['round_score'];
        $state['answered'][$roundId] = [
            'correct' => true,
            'score_breakdown' => $scoreBreakdown,
            'reward' => $this->engine->rewardPayload($round),
        ];

        if (count($state['answered']) >= count($state['rounds'])) {
            $state['finished'] = true;
        }

        if (! empty($state['finished'])) {
            $this->playModel->markAsSolved(
                (int) ($state['play_id'] ?? 0),
                $this->correctGuessesFromState($state),
                $elapsedMs,
                (int) $state['score']
            );
            session()->remove($this->activeSessionKey((int) ($state['player_id'] ?? 0)));
        }

        session()->set($key, $state);

        return $this->respondJson([
            'correct' => true,
            'score_breakdown' => $scoreBreakdown,
            'reward' => $this->engine->rewardPayload($round),
            'attempt_feedback' => $attemptFeedback,
            'total_score' => (int) $state['score'],
            'answered_count' => count($state['answered']),
            'round_count' => count($state['rounds']),
            'finished' => (bool) $state['finished'],
            'leaderboard' => $this->normalizeLeaderboardRows($this->playModel->getTopByDate(date('Y-m-d'), 10)),
        ]);
    }

    private function forfeitRound(array &$state, array $round, int $roundId, string $key, bool $autoForfeit): array
    {
        $elapsedMs = $this->elapsedFromState($state);
        $attemptsUsed = (int) ($state['attempts'][$roundId] ?? 0);

        $state['answered'][$roundId] = [
            'correct' => false,
            'forfeited' => true,
            'auto_forfeit' => $autoForfeit,
            'lost' => $autoForfeit,
            'score_breakdown' => [
                'base_points' => (int) ($round['base_points'] ?? 50),
                'time_bonus' => 0,
                'wrong_guess_penalty' => min(50, $attemptsUsed * 5),
                'hint_reduction_pct' => 0,
                'round_score' => 0,
                'max_score_per_round' => (int) ($round['max_score_per_round'] ?? $round['max_score'] ?? 0),
                'elapsed_seconds' => (int) floor($elapsedMs / 1000),
                'attempts_used' => $attemptsUsed,
            ],
            'reward' => array_merge($this->engine->rewardPayload($round), ['answer' => '']),
        ];

        if (count($state['answered']) >= count($state['rounds'])) {
            $state['finished'] = true;
        }

        if (! empty($state['finished'])) {
            $this->playModel->markAsSolved(
                (int) ($state['play_id'] ?? 0),
                $this->correctGuessesFromState($state),
                $elapsedMs,
                (int) ($state['score'] ?? 0)
            );
            session()->remove($this->activeSessionKey((int) ($state['player_id'] ?? 0)));
        }

        session()->set($key, $state);

        return [
            'correct' => false,
            'forfeited' => false,
            'round_closed' => true,
            'auto_forfeit' => $autoForfeit,
            'lost' => $autoForfeit,
            'reward' => array_merge($this->engine->rewardPayload($round), ['answer' => '']),
            'total_score' => (int) ($state['score'] ?? 0),
            'answered_count' => count($state['answered']),
            'round_count' => count($state['rounds']),
            'finished' => (bool) $state['finished'],
            'leaderboard' => $this->normalizeLeaderboardRows($this->playModel->getTopByDate(date('Y-m-d'), 10)),
        ];
    }

    private function forfeitSession(array &$state, bool $autoForfeit): void
    {
        if (! empty($state['finished'])) {
            return;
        }

        $rounds = $state['rounds'] ?? [];
        if ($rounds === []) {
            return;
        }
        $elapsedMs = $this->elapsedFromState($state);
        $attemptsUsed = $this->correctGuessesFromState($state);

        $this->playModel->markAsForfeited((int) ($state['play_id'] ?? 0), $attemptsUsed, $elapsedMs);
        $state['finished'] = true;
        session()->remove($this->activeSessionKey((int) ($state['player_id'] ?? 0)));
    }

    private function correctGuessesFromState(array $state): int
    {
        $answeredMap = is_array($state['answered'] ?? null) ? $state['answered'] : [];
        $total = 0;
        foreach ($answeredMap as $entry) {
            if (is_array($entry) && ! empty($entry['correct'])) {
                $total++;
            }
        }

        return max(0, min(self::ROUND_COUNT, $total));
    }

    private function findRoundById(array $rounds, int $roundId): ?array
    {
        foreach ($rounds as $entry) {
            if ((int) ($entry['id'] ?? 0) === $roundId) {
                return $entry;
            }
        }

        return null;
    }

    private function payload(): array
    {
        $payload = $this->request->getJSON(true);
        if (is_array($payload)) {
            return $payload;
        }

        $post = $this->request->getPost();
        if (is_array($post) && $post !== []) {
            return $post;
        }

        $decoded = json_decode((string) $this->request->getBody(), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolvePlayer(): ?array
    {
        $playerId = (int) session()->get('gsp_player_id');
        if ($playerId <= 0) {
            return null;
        }

        return $this->playerModel->findActiveById($playerId);
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

    private function elapsedFromState(array $state): int
    {
        $startedAtMs = (int) ($state['started_at_ms'] ?? 0);
        $nowMs = (int) round(microtime(true) * 1000);

        if ($startedAtMs <= 0) {
            return 0;
        }

        return max(0, $nowMs - $startedAtMs);
    }

    private function respondJson(array $payload, int $statusCode = 200)
    {
        $csrfHash = csrf_hash();
        $payload['csrf_hash'] = $csrfHash;

        return $this->response
            ->setStatusCode($statusCode)
            ->setHeader('X-CSRF-TOKEN', $csrfHash)
            ->setJSON($payload);
    }

    private function normalizeLeaderboardRows(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [
                'rank' => (int) ($row['rank'] ?? 0),
                'name' => (string) ($row['fullName'] ?? 'Player'),
                'school' => (string) ($row['school'] ?? ''),
                'attempts_used' => (int) ($row['attemptsUsed'] ?? 0),
                'elapsed_ms' => (int) ($row['elapsedMs'] ?? 0),
                'score' => (int) ($row['score'] ?? 0),
                'finished_at' => (string) ($row['finishedAt'] ?? ''),
            ];
        }, $rows);
    }

    private function normalizePlaySummary(array $play): array
    {
        return [
            'status' => (string) ($play['status'] ?? ''),
            'attempts_used' => (int) ($play['attemptsUsed'] ?? 0),
            'elapsed_ms' => (int) ($play['elapsedMs'] ?? 0),
            'score' => (int) ($play['score'] ?? 0),
            'finished_at' => (string) ($play['finishedAt'] ?? ''),
        ];
    }

    private function validatedDateOrToday(string $candidate): string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $candidate)) {
            return date('Y-m-d');
        }

        [$year, $month, $day] = array_map('intval', explode('-', $candidate));
        if (! checkdate($month, $day, $year)) {
            return date('Y-m-d');
        }

        return $candidate;
    }

    private function activeSessionKey(int $playerId): string
    {
        return self::ACTIVE_SESSION_PREFIX . max(0, $playerId);
    }

    private function buildSessionPayload(string $sessionId, array $state, bool $resumed): array
    {
        $rounds = is_array($state['rounds'] ?? null) ? $state['rounds'] : [];
        $clientRounds = array_map(fn(array $entry): array => $this->engine->sanitizeForClient($entry), $rounds);
        $answered = is_array($state['answered'] ?? null) ? $state['answered'] : [];
        $attemptMap = is_array($state['attempts'] ?? null) ? $state['attempts'] : [];
        $attemptHistory = is_array($state['guess_feedback'] ?? null) ? $state['guess_feedback'] : [];

        $roundIndex = 0;
        foreach ($clientRounds as $index => $round) {
            $rid = (int) ($round['id'] ?? 0);
            if (! isset($answered[$rid])) {
                $roundIndex = $index;
                break;
            }
            $roundIndex = $index + 1;
        }

        if ($roundIndex >= count($clientRounds)) {
            $roundIndex = max(0, count($clientRounds) - 1);
        }

        return [
            'session_id' => $sessionId,
            'resumed' => $resumed,
            'round_count' => count($clientRounds),
            'round_index' => $roundIndex,
            'answered_count' => count($answered),
            'total_score' => (int) ($state['score'] ?? 0),
            'elapsed_seconds' => (int) floor($this->elapsedFromState($state) / 1000),
            'attempts_map' => $attemptMap,
            'attempt_history' => $attemptHistory,
            'rules' => [
                'word_length' => 5,
                'max_attempts' => $this->engine->maxAttempts(),
                'daily_limit' => 1,
                'attempt_policy' => 'You have up to five attempts for each word.',
                'round_policy' => self::ROUND_COUNT . ' startup-related words per trial.',
                'forfeit_policy' => 'Refresh keeps your active run in this browser. Press Exit to forfeit today\'s run.',
                'scoring_policy' => 'Round score = 50 base points + up to 50 time bonus points. Maximum score per round is 100.',
                'base_points_policy' => 'Base points are 50.',
                'time_bonus_policy' => 'Time bonus starts at 50 and drops by 1 point about every 2.4 seconds.',
                'attempt_bonus_policy' => 'Wrong guesses reduce the score by 5 points each, up to 50 points total.',
                'ranking_policy' => 'Top 10 players are ranked by speed, accuracy, and finish order.',
            ],
            'rounds' => $clientRounds,
        ];
    }
}
