<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Throwable;

class Deployment extends Controller
{
    public function runMigrations()
    {
        $enabled = filter_var(env('deploymentMigrations.enabled', false), FILTER_VALIDATE_BOOL);
        $expectedToken = trim((string) env('deploymentMigrations.token', ''));
        $providedToken = trim((string) ($this->request->getGet('token') ?? ''));

        if (! $enabled || $expectedToken === '' || $providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return $this->response
                ->setStatusCode(404)
                ->setContentType('text/plain')
                ->setBody("Not found.\n");
        }

        $runner = service('migrations');
        $runner->clearCliMessages();

        try {
            // Runs pending up() migrations only, matching php spark migrate.
            if (! $runner->latest()) {
                return $this->response
                    ->setStatusCode(500)
                    ->setContentType('text/plain')
                    ->setBody("Migration runner returned a failure.\n");
            }

            $messages = $runner->getCliMessages();
            $body = "Migrations completed successfully.\n";
            if ($messages !== []) {
                $body .= "\nMessages:\n" . implode("\n", $messages) . "\n";
            }

            return $this->response
                ->setContentType('text/plain')
                ->setBody($body);
        } catch (Throwable $e) {
            log_message('error', 'Deployment migration runner failed: ' . $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setContentType('text/plain')
                ->setBody("Migration failed. Check writable/logs for details.\n");
        }
    }
}
