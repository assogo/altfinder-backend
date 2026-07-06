<?php

namespace App\Service;

class HunterContactService
{
    private const API_URL = 'https://api.hunter.io/v2/domain-search';

    public function __construct(private string $apiKey) {}

    public function findContact(string $companyName): ?string
    {
        $domain = $this->guessDomain($companyName);
        if (!$domain) {
            return null;
        }

        $url = self::API_URL . '?' . http_build_query([
            'domain'  => $domain,
            'api_key' => $this->apiKey,
            'limit'   => 1,
        ]);

        $response = @file_get_contents($url);
        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        $emails = $data['data']['emails'] ?? [];
        if (empty($emails)) {
            return null;
        }

        return $emails[0]['value'] ?? null;
    }

    private function guessDomain(string $companyName): ?string
    {
        // Nettoie le nom : minuscules, sans accents, sans caractères spéciaux
        $domain = strtolower($companyName);
        $domain = strtr($domain, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ç' => 'c',
        ]);

        // Supprime les mots génériques inutiles
        $stopWords = ['sarl', 'sas', 'sa', 'sasu', 'eurl', 'sci', 'group', 'groupe',
                      'france', 'solutions', 'services', 'consulting', '&', '-'];
        foreach ($stopWords as $word) {
            $domain = str_replace($word, '', $domain);
        }

        // Garde uniquement les lettres et chiffres
        $domain = preg_replace('/[^a-z0-9]/', '', $domain);

        if (strlen($domain) < 3) {
            return null;
        }

        return $domain . '.fr';
    }
}