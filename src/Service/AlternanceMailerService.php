<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AlternanceMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $recipientEmail,
        private string $senderEmail,
    ) {}

    public function sendDailyReport(string $excelPath, int $newJobsCount): void
    {
        $today = (new \DateTime())->format('d/m/Y');

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($this->recipientEmail)
            ->subject("🎓 AltFinder — {$newJobsCount} nouvelle(s) offre(s) d'alternance – {$today}")
            ->html($this->buildEmailHtml($newJobsCount, $today))
            ->attachFromPath(
                $excelPath,
                "alternance_tracker_{$today}.xlsx",
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        $this->mailer->send($email);
    }

    private function buildEmailHtml(int $count, string $today): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
          <meta charset="UTF-8">
          <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
            .header { background: #1A1A2E; color: white; padding: 28px 32px; }
            .header h1 { margin: 0; font-size: 22px; }
            .header p  { margin: 6px 0 0; color: #a0aec0; font-size: 14px; }
            .body { padding: 32px; }
            .badge { display: inline-block; background: #4CAF50; color: white; border-radius: 20px; padding: 6px 18px; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
            .info { background: #f0f4ff; border-left: 4px solid #1A1A2E; padding: 14px 18px; border-radius: 4px; margin: 20px 0; font-size: 14px; color: #444; }
            .footer { background: #f5f5f5; padding: 16px 32px; font-size: 12px; color: #999; text-align: center; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>🎓 AltFinder – Rapport Quotidien</h1>
              <p>Mise à jour du {$today}</p>
            </div>
            <div class="body">
              <div class="badge">+{$count} nouvelle(s) offre(s)</div>
              <p>Bonjour Maurice,</p>
              <p>Ton tracker d'alternance a été mis à jour automatiquement. Tu trouveras en pièce jointe le fichier Excel avec les nouvelles offres ajoutées aujourd'hui.</p>
              <div class="info">
                📎 Le fichier contient <strong>{$count} nouvelle(s) offre(s)</strong> ajoutée(s) ce jour.<br>
                Les offres précédentes sont conservées.
              </div>
              <p><strong>Colonnes à remplir manuellement :</strong></p>
              ✅ <strong>Contact entreprise</strong> — email ou téléphone du recruteur<br>
              ✅ <strong>Entretien fait ?</strong> — Oui / Non<br>
              ✅ <strong>Entretien obtenu ?</strong> — Résultat<br>
              📝 <strong>Notes</strong> — dates de relance, impressions...
            </div>
            <div class="footer">
              Email généré automatiquement par AltFinder • Ne pas répondre
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}