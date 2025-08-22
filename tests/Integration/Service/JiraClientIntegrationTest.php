<?php

namespace App\Tests\Integration\Service;

use App\Service\JiraClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class JiraClientIntegrationTest extends TestCase
{
    private JiraClient $jiraClient;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $mockHttpClient = new Client(['handler' => $handlerStack]);

        $this->jiraClient = new JiraClient(
            'https://test-jira.example.com',
            'test@example.com',
            'test-token',
            true,
            new NullLogger()
        );

        // Replace the HTTP client using reflection
        $reflection = new \ReflectionClass($this->jiraClient);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->jiraClient, $mockHttpClient);
    }

    public function testCompleteWorkflowWithRealJiraResponse(): void
    {
        // Mock realistic Jira API response structure
        $jiraResponse = [
            'expand' => 'schema,names',
            'startAt' => 0,
            'maxResults' => 50,
            'total' => 2,
            'issues' => [
                [
                    'expand' => 'operations,versionedRepresentations',
                    'id' => '12345',
                    'self' => 'https://test-jira.example.com/rest/api/2/issue/12345',
                    'key' => 'PROJ-123',
                    'fields' => [
                        'summary' => 'Example issue summary',
                        'description' => 'This is a test issue description with some content.',
                        'status' => [
                            'self' => 'https://test-jira.example.com/rest/api/2/status/1',
                            'description' => '',
                            'iconUrl' => 'https://test-jira.example.com/images/icons/statuses/open.png',
                            'name' => 'Open',
                            'id' => '1',
                            'statusCategory' => [
                                'self' => 'https://test-jira.example.com/rest/api/2/statuscategory/2',
                                'id' => 2,
                                'key' => 'new',
                                'colorName' => 'blue-gray',
                                'name' => 'To Do'
                            ]
                        ],
                        'priority' => [
                            'self' => 'https://test-jira.example.com/rest/api/2/priority/3',
                            'iconUrl' => 'https://test-jira.example.com/images/icons/priorities/medium.svg',
                            'name' => 'Medium',
                            'id' => '3'
                        ],
                        'assignee' => [
                            'self' => 'https://test-jira.example.com/rest/api/2/user?accountId=123',
                            'accountId' => '123',
                            'emailAddress' => 'assignee@example.com',
                            'displayName' => 'John Doe',
                            'active' => true
                        ]
                    ]
                ],
                [
                    'expand' => 'operations,versionedRepresentations',
                    'id' => '12346',
                    'self' => 'https://test-jira.example.com/rest/api/2/issue/12346',
                    'key' => 'PROJ-124',
                    'fields' => [
                        'summary' => 'Another test issue',
                        'description' => null,
                        'status' => [
                            'self' => 'https://test-jira.example.com/rest/api/2/status/3',
                            'description' => '',
                            'iconUrl' => 'https://test-jira.example.com/images/icons/statuses/inprogress.png',
                            'name' => 'In Progress',
                            'id' => '3'
                        ],
                        'priority' => [
                            'self' => 'https://test-jira.example.com/rest/api/2/priority/2',
                            'iconUrl' => 'https://test-jira.example.com/images/icons/priorities/high.svg',
                            'name' => 'High',
                            'id' => '2'
                        ],
                        'assignee' => null
                    ]
                ]
            ]
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($jiraResponse)));

        $result = $this->jiraClient->searchIssues('project = PROJ AND status != Closed');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['issues']);
        $this->assertEquals(2, $result['total']);

        // Test first issue structure
        $firstIssue = $result['issues'][0];
        $this->assertEquals('PROJ-123', $firstIssue['key']);
        $this->assertEquals('Example issue summary', $firstIssue['fields']['summary']);
        $this->assertArrayHasKey('status', $firstIssue['fields']);
        $this->assertArrayHasKey('priority', $firstIssue['fields']);
        $this->assertArrayHasKey('assignee', $firstIssue['fields']);

        // Test second issue structure
        $secondIssue = $result['issues'][1];
        $this->assertEquals('PROJ-124', $secondIssue['key']);
        $this->assertEquals('Another test issue', $secondIssue['fields']['summary']);
        $this->assertNull($secondIssue['fields']['description']);
        $this->assertNull($secondIssue['fields']['assignee']);
    }

    public function testPaginationIntegration(): void
    {
        // First page with large dataset
        $firstPage = [
            'startAt' => 0,
            'maxResults' => 50,
            'total' => 125,
            'issues' => array_fill(0, 50, [
                'key' => 'PROJ-001',
                'fields' => ['summary' => 'Test issue']
            ])
        ];

        // Second page
        $secondPage = [
            'startAt' => 50,
            'maxResults' => 50,
            'total' => 125,
            'issues' => array_fill(0, 50, [
                'key' => 'PROJ-051',
                'fields' => ['summary' => 'Test issue']
            ])
        ];

        // Third page (partial)
        $thirdPage = [
            'startAt' => 100,
            'maxResults' => 50,
            'total' => 125,
            'issues' => array_fill(0, 25, [
                'key' => 'PROJ-101',
                'fields' => ['summary' => 'Test issue']
            ])
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($firstPage)),
            new Response(200, [], json_encode($secondPage)),
            new Response(200, [], json_encode($thirdPage))
        );

        $result = $this->jiraClient->searchIssues('project = PROJ');

        $this->assertEquals(125, $result['total']);
        $this->assertCount(125, $result['issues']);
    }

    public function testJqlValidationIntegration(): void
    {
        // Valid JQL response
        $validResponse = new Response(200, [], json_encode([
            'startAt' => 0,
            'maxResults' => 0,
            'total' => 42,
            'issues' => []
        ]));

        $this->mockHandler->append($validResponse);

        $isValid = $this->jiraClient->validateJql('project = VALID');

        $this->assertTrue($isValid);
    }

    public function testConfigurationUpdateIntegration(): void
    {
        // Mock responses for validation calls
        $this->mockHandler->append(
            new Response(200, [], json_encode(['total' => 5, 'issues' => []])),
            new Response(200, [], json_encode(['total' => 10, 'issues' => []]))
        );

        // Test with initial config
        $result1 = $this->jiraClient->validateJql('project = TEST');
        $this->assertTrue($result1);

        // Update configuration - this creates a new HTTP client internally
        $this->jiraClient->updateConfig(
            'https://new-jira.example.com',
            'new-user@example.com',
            'new-token',
            false
        );

        // We need to set up a new mock client after updateConfig since it replaces the internal client
        $newMockHandler = new MockHandler([new Response(200, [], json_encode(['total' => 10, 'issues' => []]))]);
        $newHandlerStack = HandlerStack::create($newMockHandler);
        $newMockHttpClient = new Client(['handler' => $newHandlerStack]);
        
        $reflection = new \ReflectionClass($this->jiraClient);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->jiraClient, $newMockHttpClient);

        // Test with updated config
        $result2 = $this->jiraClient->validateJql('project = NEW');
        $this->assertTrue($result2);
    }

    public function testErrorHandlingIntegration(): void
    {
        // Test various HTTP error responses
        $responses = [
            new Response(401, [], json_encode(['errorMessages' => ['Unauthorized']])),
            new Response(400, [], json_encode(['errorMessages' => ['Bad Request']])),
            new Response(503, [], json_encode(['errorMessages' => ['Service Unavailable']]))
        ];

        foreach ($responses as $response) {
            $this->mockHandler->append($response);
            
            $this->expectException(\RuntimeException::class);
            $this->jiraClient->searchIssues('project = TEST');
        }
    }

    public function testLargeDatasetIntegration(): void
    {
        // Simulate large dataset with multiple pages
        $totalIssues = 500;
        $pageSize = 50;
        $pages = ceil($totalIssues / $pageSize);

        $responses = [];
        for ($page = 0; $page < $pages; $page++) {
            $startAt = $page * $pageSize;
            $remainingIssues = min($pageSize, $totalIssues - $startAt);
            
            $issues = [];
            for ($i = 0; $i < $remainingIssues; $i++) {
                $issueNum = $startAt + $i + 1;
                $issues[] = [
                    'key' => sprintf('LARGE-%04d', $issueNum),
                    'fields' => [
                        'summary' => "Large dataset issue #$issueNum",
                        'description' => str_repeat("Content for issue $issueNum. ", 10)
                    ]
                ];
            }

            $responses[] = new Response(200, [], json_encode([
                'startAt' => $startAt,
                'maxResults' => $pageSize,
                'total' => $totalIssues,
                'issues' => $issues
            ]));
        }

        $this->mockHandler->append(...$responses);

        $result = $this->jiraClient->searchIssues('project = LARGE');

        $this->assertEquals($totalIssues, $result['total']);
        $this->assertCount($totalIssues, $result['issues']);
        $this->assertEquals('LARGE-0001', $result['issues'][0]['key']);
        $this->assertEquals('LARGE-0500', $result['issues'][$totalIssues - 1]['key']);
    }
}