<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ReportMailer
{
    public function sendWeeklyReport(Report $report, array $recipientEmails, string $absoluteFilePath): bool
    {
        if (empty($recipientEmails)) {
            Log::warning('ReportMailer: aucun destinataire pour le rapport #'.$report->id);
            return false;
        }

        try {
            Mail::send([], [], function ($message) use ($report, $recipientEmails, $absoluteFilePath) {
                $message->to($recipientEmails)
                    ->subject("HeatAlert — Rapport hebdomadaire ({$report->period_start->format('d/m')} - {$report->period_end->format('d/m')})")
                    ->html(
                        '<p>Bonjour,</p>'
                        .'<p>Voici le rapport statistique de la semaine, en pièce jointe.</p>'
                        .'<p>— HeatAlert</p>'
                    )
                    ->attach($absoluteFilePath);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('ReportMailer::sendWeeklyReport failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function sendInvitation(User $user, string $temporaryLink): bool
    {
        try {
            Mail::send([], [], function ($message) use ($user, $temporaryLink) {
                $message->to($user->email)
                    ->subject('Bienvenue sur HeatAlert')
                    ->html(
                        "<p>Bonjour {$user->name},</p>"
                        ."<p>Ton compte a été créé. Connecte-toi ici : <a href=\"{$temporaryLink}\">{$temporaryLink}</a></p>"
                    );
            });

            return true;
        } catch (\Exception $e) {
            Log::error('ReportMailer::sendInvitation failed', ['message' => $e->getMessage()]);
            return false;
        }
    }
}