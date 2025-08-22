<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class JiraClient
{
    private Client $httpClient;
    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct(
        string $baseUrl,
        string $username,
        string $password,
        bool $verifyTls,
        private LoggerInterface $logger
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'connect_timeout' => 5,
            'verify' => $verifyTls,
            'auth' => [$this->username, $this->password],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Search issues using JQL with pagination
     */
    public function searchIssues(string $jql): array
    {
        $allIssues = [];
        $startAt = 0;
        $maxResults = 50; // Jira default
        $total = null;

        do {
            try {
                $response = $this->httpClient->get('/rest/api/2/search', [
                    'query' => [
                        'jql' => $jql,
                        'startAt' => $startAt,
                        'maxResults' => $maxResults,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                
                if ($total === null) {
                    $total = $data['total'];
                }

                $allIssues = array_merge($allIssues, $data['issues']);
                $startAt += $maxResults;

                $this->logger->info('Fetched page of Jira issues', [
                    'jql' => $jql,
                    'startAt' => $startAt - $maxResults,
                    'maxResults' => $maxResults,
                    'pageSize' => count($data['issues']),
                    'total' => $total,
                ]);

            } catch (GuzzleException $e) {
                $this->logger->error('Failed to fetch Jira issues', [
                    'jql' => $jql,
                    'startAt' => $startAt,
                    'error' => $e->getMessage(),
                ]);
                throw new \RuntimeException('Failed to fetch issues from Jira: ' . $e->getMessage(), 0, $e);
            }

        } while ($startAt < $total);

        return [
            'issues' => $allIssues,
            'total' => $total,
        ];
    }

    /**
     * Validate JQL syntax by performing a search with maxResults=0
     */
    public function validateJql(string $jql): bool
    {
        try {
            $response = $this->httpClient->get('/rest/api/2/search', [
                'query' => [
                    'jql' => $jql,
                    'maxResults' => 0,
                ],
            ]);

            return $response->getStatusCode() === 200;

        } catch (GuzzleException $e) {
            $this->logger->warning('JQL validation failed', [
                'jql' => $jql,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update configuration
     */
    public function updateConfig(string $baseUrl, string $username, string $password, bool $verifyTls): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'connect_timeout' => 5,
            'verify' => $verifyTls,
            'auth' => [$this->username, $this->password],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
