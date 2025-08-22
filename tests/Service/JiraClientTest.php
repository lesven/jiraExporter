<?php

namespace App\Tests\Service;

use App\Service\JiraClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

class JiraClientTest extends TestCase
{
    private JiraClient $jiraClient;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $mockHttpClient = new Client(['handler' => $handlerStack]);

        $this->jiraClient = new JiraClient(
            'https://jira.example.com/',
            'testuser',
            'testpass',
            true,
            new NullLogger()
        );

        // Replace the internal HTTP client with our mock
        $reflection = new ReflectionClass($this->jiraClient);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->jiraClient, $mockHttpClient);
    }

    public function testConstructorTrimsBaseUrl(): void
    {
        $client = new JiraClient(
            'https://jira.example.com////',
            'user',
            'pass',
            true,
            new NullLogger()
        );

        $reflection = new ReflectionClass($client);
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        
        $this->assertEquals('https://jira.example.com', $baseUrlProperty->getValue($client));
    }

    public function testSearchIssuesSinglePage(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'issues' => [
                ['key' => 'TEST-1', 'fields' => ['summary' => 'Test Issue 1']],
                ['key' => 'TEST-2', 'fields' => ['summary' => 'Test Issue 2']]
            ],
            'total' => 2,
            'startAt' => 0,
            'maxResults' => 50
        ]));

        $this->mockHandler->append($mockResponse);

        $result = $this->jiraClient->searchIssues('project = TEST');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['issues']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals('TEST-1', $result['issues'][0]['key']);
    }

    public function testSearchIssuesMultiplePages(): void
    {
        // First page - use maxResults=50 to match actual implementation
        $firstPageResponse = new Response(200, [], json_encode([
            'issues' => [
                ['key' => 'TEST-1', 'fields' => ['summary' => 'Test Issue 1']],
                ['key' => 'TEST-2', 'fields' => ['summary' => 'Test Issue 2']]
            ],
            'total' => 75,
            'startAt' => 0,
            'maxResults' => 50
        ]));

        // Second page - simulating pagination with 50 item batches
        $secondPageResponse = new Response(200, [], json_encode([
            'issues' => [
                ['key' => 'TEST-3', 'fields' => ['summary' => 'Test Issue 3']]
            ],
            'total' => 75,
            'startAt' => 50,
            'maxResults' => 50
        ]));

        $this->mockHandler->append($firstPageResponse, $secondPageResponse);

        $result = $this->jiraClient->searchIssues('project = TEST');

        $this->assertCount(3, $result['issues']);
        $this->assertEquals(75, $result['total']);
        $this->assertEquals('TEST-1', $result['issues'][0]['key']);
        $this->assertEquals('TEST-2', $result['issues'][1]['key']);
        $this->assertEquals('TEST-3', $result['issues'][2]['key']);
    }

    public function testSearchIssuesThrowsExceptionOnError(): void
    {
        $this->mockHandler->append(
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch issues from Jira');

        $this->jiraClient->searchIssues('invalid jql');
    }

    public function testValidateJqlSuccess(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"total": 0}'));

        $result = $this->jiraClient->validateJql('project = TEST');

        $this->assertTrue($result);
    }

    public function testValidateJqlFailure(): void
    {
        $this->mockHandler->append(
            new RequestException('Bad Request', new Request('GET', 'test'))
        );

        $result = $this->jiraClient->validateJql('invalid jql syntax');

        $this->assertFalse($result);
    }

    public function testUpdateConfig(): void
    {
        $this->jiraClient->updateConfig(
            'https://new-jira.example.com/',
            'newuser',
            'newpass',
            false
        );

        $reflection = new ReflectionClass($this->jiraClient);
        
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $this->assertEquals('https://new-jira.example.com', $baseUrlProperty->getValue($this->jiraClient));

        $usernameProperty = $reflection->getProperty('username');
        $usernameProperty->setAccessible(true);
        $this->assertEquals('newuser', $usernameProperty->getValue($this->jiraClient));

        $passwordProperty = $reflection->getProperty('password');
        $passwordProperty->setAccessible(true);
        $this->assertEquals('newpass', $passwordProperty->getValue($this->jiraClient));
    }

    public function testSearchIssuesEmptyResult(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'issues' => [],
            'total' => 0,
            'startAt' => 0,
            'maxResults' => 50
        ]));

        $this->mockHandler->append($mockResponse);

        $result = $this->jiraClient->searchIssues('project = NONEXISTENT');

        $this->assertIsArray($result);
        $this->assertEmpty($result['issues']);
        $this->assertEquals(0, $result['total']);
    }

    public function testSearchIssuesInvalidJson(): void
    {
        $this->mockHandler->append(new Response(200, [], 'invalid json'));

        $this->expectException(\TypeError::class);

        $this->jiraClient->searchIssues('project = TEST');
    }
}