<?php

namespace App\Tests\Integration\Service;

use App\Service\CsvExporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CsvExporterIntegrationTest extends TestCase
{
    private CsvExporter $csvExporter;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/csv_integration_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        $this->csvExporter = new CsvExporter($this->tempDir, new NullLogger());
    }

    protected function tearDown(): void
    {
        // Clean up temp directory recursively
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testCompleteJiraWorkflowIntegration(): void
    {
        // Simulate realistic Jira data structure
        $jiraIssues = [
            [
                'expand' => 'operations,versionedRepresentations',
                'id' => '12345',
                'self' => 'https://jira.example.com/rest/api/2/issue/12345',
                'key' => 'PROJ-123',
                'fields' => [
                    'summary' => 'Implement user authentication',
                    'description' => 'Add OAuth2 authentication to the application with proper error handling.',
                    'status' => [
                        'name' => 'In Progress',
                        'id' => '3',
                        'statusCategory' => [
                            'name' => 'In Progress'
                        ]
                    ],
                    'priority' => [
                        'name' => 'High',
                        'id' => '2'
                    ],
                    'assignee' => [
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com'
                    ],
                    'reporter' => [
                        'displayName' => 'Jane Smith',
                        'emailAddress' => 'jane.smith@example.com'
                    ],
                    'created' => '2024-01-15T10:30:00.000+0100',
                    'updated' => '2024-01-20T15:45:00.000+0100',
                    'labels' => ['backend', 'security', 'urgent'],
                    'components' => [
                        ['name' => 'Authentication'],
                        ['name' => 'API']
                    ],
                    'fixVersions' => [
                        ['name' => 'v2.1.0']
                    ]
                ]
            ],
            [
                'expand' => 'operations,versionedRepresentations',
                'id' => '12346',
                'self' => 'https://jira.example.com/rest/api/2/issue/12346',
                'key' => 'PROJ-124',
                'fields' => [
                    'summary' => 'Fix database connection timeout',
                    'description' => null,
                    'status' => [
                        'name' => 'Done',
                        'id' => '10001'
                    ],
                    'priority' => [
                        'name' => 'Critical',
                        'id' => '1'
                    ],
                    'assignee' => null,
                    'reporter' => [
                        'displayName' => 'Bob Wilson',
                        'emailAddress' => 'bob.wilson@example.com'
                    ],
                    'created' => '2024-01-10T08:15:00.000+0100',
                    'updated' => '2024-01-12T12:30:00.000+0100',
                    'labels' => [],
                    'components' => [],
                    'fixVersions' => []
                ]
            ]
        ];

        $filePath = $this->csvExporter->exportToCsv($jiraIssues, null, 'integration_test.csv');

        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('integration_test.csv', $filePath);

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));

        // Should have header + 2 data lines
        $this->assertGreaterThanOrEqual(3, count($lines));

        // Check header contains expected columns
        $header = $lines[0];
        $this->assertStringContainsString('Key', $header);
        $this->assertStringContainsString('Summary', $header);
        $this->assertStringContainsString('Description', $header);

        // Check data integrity
        $this->assertStringContainsString('PROJ-123', $content);
        $this->assertStringContainsString('PROJ-124', $content);
        $this->assertStringContainsString('Implement user authentication', $content);
        $this->assertStringContainsString('Fix database connection timeout', $content);
    }

    public function testLargeDatasetIntegration(): void
    {
        // Generate large dataset (1000 issues)
        $largeDataset = [];
        for ($i = 1; $i <= 1000; $i++) {
            $largeDataset[] = [
                'key' => sprintf('BULK-%04d', $i),
                'fields' => [
                    'summary' => "Bulk test issue #$i",
                    'description' => str_repeat("This is test content for issue $i. ", 20),
                    'status' => ['name' => $i % 2 ? 'Open' : 'Closed'],
                    'priority' => ['name' => ['Low', 'Medium', 'High', 'Critical'][$i % 4]],
                    'assignee' => $i % 3 ? ['displayName' => "User $i"] : null,
                    'created' => '2024-01-01T00:00:00.000+0100'
                ]
            ];
        }

        $filePath = $this->csvExporter->exportToCsv($largeDataset, null, 'large_dataset.csv');

        $this->assertFileExists($filePath);
        
        // Verify file is not empty and has expected size
        $fileSize = filesize($filePath);
        $this->assertGreaterThan(50000, $fileSize); // Should be substantial size

        // Count lines to verify all issues were exported
        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));
        $this->assertEquals(1001, count($lines)); // Header + 1000 issues

        // Spot check some data
        $this->assertStringContainsString('BULK-0001', $content);
        $this->assertStringContainsString('BULK-1000', $content);
        $this->assertStringContainsString('Bulk test issue #500', $content);
    }

    public function testSpecialCharactersIntegration(): void
    {
        $specialCharIssues = [
            [
                'key' => 'SPECIAL-1',
                'fields' => [
                    'summary' => 'Unicode test: ñáéíóú ñÑ áÁ éÉ íÍ óÓ úÚ',
                    'description' => "Multi-line content:\nLine 1\nLine 2\n\nEmpty line above",
                    'status' => ['name' => 'Status with "quotes"'],
                    'assignee' => ['displayName' => 'Müller, Hans-Peter']
                ]
            ],
            [
                'key' => 'SPECIAL-2',
                'fields' => [
                    'summary' => 'CSV delimiter test: comma, semicolon; tab	here',
                    'description' => 'Quotes test: "quoted text" and \'single quotes\'',
                    'status' => ['name' => 'Status, with; delimiters	here']
                ]
            ],
            [
                'key' => 'SPECIAL-3',
                'fields' => [
                    'summary' => 'Emoji test: ✅ ❌ 🚀 💻 🔥 ⚡',
                    'description' => 'Symbol test: © ® ™ € $ £ ¥ • → ←'
                ]
            ]
        ];

        $filePath = $this->csvExporter->exportToCsv($specialCharIssues, null, 'special_chars.csv');

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Verify content is properly UTF-8 encoded
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'));

        // Check that special characters are preserved
        $this->assertStringContainsString('ñáéíóú', $content);
        $this->assertStringContainsString('Müller', $content);
        $this->assertStringContainsString('✅', $content);
        $this->assertStringContainsString('€', $content);

        // Check that CSV escaping works for quotes and delimiters
        $this->assertStringContainsString('""quoted text""', $content);
        $this->assertStringContainsString('comma, semicolon', $content);
    }

    public function testDirectoryCreationIntegration(): void
    {
        $nestedPath = $this->tempDir . '/level1/level2/level3';
        
        // Should create nested directories automatically
        $filePath = $this->csvExporter->exportToCsv(
            [['key' => 'TEST-1', 'fields' => ['summary' => 'Test']]],
            $nestedPath,
            'nested_test.csv'
        );

        $this->assertFileExists($filePath);
        $this->assertTrue(is_dir($nestedPath));
        $this->assertStringEndsWith('/level3/nested_test.csv', $filePath);
    }

    public function testConcurrentExports(): void
    {
        $issues1 = [['key' => 'CONCURRENT-1', 'fields' => ['summary' => 'First export']]];
        $issues2 = [['key' => 'CONCURRENT-2', 'fields' => ['summary' => 'Second export']]];
        $issues3 = [['key' => 'CONCURRENT-3', 'fields' => ['summary' => 'Third export']]];

        // Simulate concurrent exports
        $file1 = $this->csvExporter->exportToCsv($issues1, null, 'concurrent_1.csv');
        $file2 = $this->csvExporter->exportToCsv($issues2, null, 'concurrent_2.csv');
        $file3 = $this->csvExporter->exportToCsv($issues3, null, 'concurrent_3.csv');

        // All files should exist and be different
        $this->assertFileExists($file1);
        $this->assertFileExists($file2);
        $this->assertFileExists($file3);

        $content1 = file_get_contents($file1);
        $content2 = file_get_contents($file2);
        $content3 = file_get_contents($file3);

        $this->assertStringContainsString('CONCURRENT-1', $content1);
        $this->assertStringContainsString('CONCURRENT-2', $content2);
        $this->assertStringContainsString('CONCURRENT-3', $content3);

        // Cross-contamination check
        $this->assertStringNotContainsString('CONCURRENT-2', $content1);
        $this->assertStringNotContainsString('CONCURRENT-1', $content2);
    }

    public function testFilePermissionsIntegration(): void
    {
        $filePath = $this->csvExporter->exportToCsv(
            [['key' => 'PERM-1', 'fields' => ['summary' => 'Permission test']]],
            null,
            'permissions.csv'
        );

        $this->assertFileExists($filePath);
        $this->assertTrue(is_readable($filePath));
        $this->assertTrue(is_writable($filePath));

        // Check file permissions (should be readable and writable by owner)
        $perms = fileperms($filePath);
        $this->assertTrue(($perms & 0x0100) !== 0); // Owner read
        $this->assertTrue(($perms & 0x0080) !== 0); // Owner write
    }

    public function testEmptyAndNullFieldsIntegration(): void
    {
        $issuesWithNulls = [
            [
                'key' => 'NULL-1',
                'fields' => [
                    'summary' => '',
                    'description' => null,
                    'status' => null,
                    'assignee' => null,
                    'labels' => [],
                    'components' => null
                ]
            ],
            [
                'key' => 'NULL-2',
                'fields' => [
                    'summary' => 'Valid summary',
                    'description' => '',
                    'status' => ['name' => ''],
                    'assignee' => ['displayName' => null]
                ]
            ]
        ];

        $filePath = $this->csvExporter->exportToCsv($issuesWithNulls, null, 'nulls.csv');

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));

        // Should handle null values gracefully without causing errors
        $this->assertGreaterThanOrEqual(2, count($lines));
        $this->assertStringContainsString('NULL-1', $content);
        $this->assertStringContainsString('NULL-2', $content);
        $this->assertStringContainsString('Valid summary', $content);
    }
}