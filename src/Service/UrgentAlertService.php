<?php

namespace App\Service;

use App\Repository\JobRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UrgentAlertService
{
    public function __construct(
        private JobRepository   $jobRepository,
        private MailerInterface $mailer,
        private string $recipientEmail,
        private string $senderEmail,
    ) {}
    public function checkAndAlert(): int
    {
        $expiringJobs = $this->jobRepository->findExpiringWithinDays(3);

        if (empty($expiringJobs)) {
            return 0;
        }

        $today = (new \DateTime())->format('d/m/Y');

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($this->recipientEmail)
            ->subject("⚠️ AltFinder — " . count($expiringJobs) . " offre(s) expirent dans moins de 3 jours !")
            ->html($this->buildAlertHtml($expiringJobs, $today));

        $this->mailer->send($email);

        return count($expiringJobs);
    }
    private function buildAlertHtml(array $jobs, string $today): string
    {
        $rows = '';
        foreach ($jobs as $job) {
            $expiresAt  = $job->getExpiresAt()->format('d/m/Y');
            $daysLeft   = (new \DateTime())->diff($job->getExpiresAt())->days;
            $urgency    = $daysLeft === 0 ? '🔴 Aujourd\'hui !' : ($daysLeft === 1 ? '🟠 Demain' : '🟡 ' . $daysLeft . ' jours');
            $url        = $job->getUrl() ?? '#';
            $company    = $job->getCompany() ?? 'Entreprise inconnue';
            $title      = $job->getTitle();

            $rows .= "
            <tr>
                <td style='padding:10px;border-bottom:1px solid #eee;'>{$title}</td>
                <td style='padding:10px;border-bottom:1px solid #eee;'>{$company}</td>
                <td style='padding:10px;border-bottom:1px solid #eee;text-align:center;'>{$expiresAt}</td>
                <td style='padding:10px;border-bottom:1px solid #eee;text-align:center;'>{$urgency}</td>
                <td style='padding:10px;border-bottom:1px solid #eee;text-align:center;'>
                    <a href='{$url}' style='background:#1A1A2E;color:white;padding:6px 12px;border-radius:4px;text-decoration:none;font-size:12px;'>Voir</a>
                </td>
            </tr>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
          <meta charset="UTF-8">
          <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 700px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
            .header { background: #c0392b; color: white; padding: 28px 32px; }
            .header h1 { margin: 0; font-size: 22px; }
            .header p  { margin: 6px 0 0; color: #f5b7b1; font-size: 14px; }
            .body { padding: 32px; }
            table { width: 100%; border-collapse: collapse; font-size: 13px; }
            th { background: #1A1A2E; color: white; padding: 10px; text-align: left; }
            .footer { background: #f5f5f5; padding: 16px 32px; font-size: 12px; color: #999; text-align: center; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>⚠️ Offres qui expirent bientôt !</h1>
              <p>Alerte du {$today} — Agis vite !</p>
            </div>
            <div class="body">
              <p>Les offres suivantes expirent dans moins de <strong>3 jours</strong>. Postule maintenant !</p>
              <table>
                <thead>
                  <tr>
                    <th>Poste</th>
                    <th>Entreprise</th>
                    <th style="text-align:center">Expire le</th>
                    <th style="text-align:center">Urgence</th>
                    <th style="text-align:center">Lien</th>
                  </tr>
                </thead>
                <tbody>
                  {$rows}
                </tbody>
              </table>
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