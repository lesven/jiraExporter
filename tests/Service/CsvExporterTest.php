<?php

namespace App\Tests\Service;

use App\Service\CsvExporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CsvExporterTest extends TestCase
{
    private CsvExporter $csvExporter;

    protected function setUp(): void
    {
        $this->csvExporter = new CsvExporter('/tmp', new NullLogger());
    }

    public function testExportEmptyIssues(): void
    {
        $issues = [];
        $filePath = $this->csvExporter->exportToCsv($issues, '/tmp', 'test_empty.csv');
        
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));
        
        // Should have header only
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('key', $lines[0]);
        
        unlink($filePath);
    }

    public function testCsvEscaping(): void
    {
        $issues = [
            [
                'key' => 'TEST-1',
                'fields' => [
                    'summary' => 'Test with "quotes" and, commas',
                    'description' => "Line 1\nLine 2",
                ]
            ]
        ];
        
        $filePath = $this->csvExporter->exportToCsv($issues, '/tmp', 'test_escaping.csv');
        
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('"Test with ""quotes"" and, commas"', $content);
        
        unlink($filePath);
    }
}
