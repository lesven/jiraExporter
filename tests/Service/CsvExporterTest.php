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
        $this->assertStringContainsString('Key', $lines[0]); // Case-sensitive: "Key" not "key"
        
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

        public function testExportMultipleIssues(): void
        {
            $issues = [
                [
                    'key' => 'TEST-1',
                    'fields' => [
                        'summary' => 'Summary 1',
                        'description' => 'Description 1',
                    ]
                ],
                [
                    'key' => 'TEST-2',
                    'fields' => [
                        'summary' => 'Summary 2',
                        'description' => 'Description 2',
                    ]
                ]
            ];
            $filePath = $this->csvExporter->exportToCsv($issues, '/tmp', 'test_multiple.csv');
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $lines = explode("\n", trim($content));
            $this->assertCount(3, $lines); // Header + 2 Issues
            $this->assertStringContainsString('TEST-1', $content);
            $this->assertStringContainsString('TEST-2', $content);
            unlink($filePath);
        }

        public function testExportWithMissingFields(): void
        {
            $issues = [
                [
                    'key' => 'TEST-3',
                    'fields' => [
                        'summary' => '',
                        'description' => null,
                    ]
                ]
            ];
            $filePath = $this->csvExporter->exportToCsv($issues, '/tmp', 'test_missing_fields.csv');
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertStringContainsString('TEST-3', $content);
        $this->assertStringContainsString('TEST-3,,', $content); // Leere Summary und Description
            unlink($filePath);
        }

        public function testExportWithSpecialCharacters(): void
        {
            $issues = [
                [
                    'key' => 'TEST-4',
                    'fields' => [
                        'summary' => "Sonderzeichen ;\tÜmläut",
                        'description' => "Tab\tSemikolon;Unicode✓",
                    ]
                ]
            ];
            $filePath = $this->csvExporter->exportToCsv($issues, '/tmp', 'test_special_chars.csv');
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertStringContainsString('Sonderzeichen ;', $content);
            $this->assertStringContainsString('Unicode✓', $content);
            unlink($filePath);
        }

        public function testExportToInvalidDirectory(): void
        {
            $issues = [
                [
                    'key' => 'TEST-5',
                    'fields' => [
                        'summary' => 'Summary',
                        'description' => 'Description',
                    ]
                ]
            ];
            
            // Erstelle eine Datei mit dem Namen des gewünschten Verzeichnisses
            // Dies verhindert, dass mkdir das Verzeichnis erstellen kann
            $tmpBase = sys_get_temp_dir();
            $conflictPath = $tmpBase . DIRECTORY_SEPARATOR . 'csv_export_conflict';
            
            // Erstelle eine Datei statt eines Verzeichnisses
            file_put_contents($conflictPath, 'dummy content');
            
            try {
                $this->expectException(\RuntimeException::class);
                $this->csvExporter->exportToCsv($issues, $conflictPath, 'test_invalid_dir.csv');
            } finally {
                // Aufräumen
                if (file_exists($conflictPath)) {
                    @unlink($conflictPath);
                }
            }
        }        public function testExportWithLongText(): void
        {
            $longText = str_repeat('A', 10000);
            $issues = [
                [
                    'key' => 'TEST-6',
                    'fields' => [
                        'summary' => $longText,
                        'description' => $longText,
                    ]
                ]
            ];
            $filePath = $this->csvExporter->exportToCsv($issues, '/tmp', 'test_long_text.csv');
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertStringContainsString($longText, $content);
            unlink($filePath);
        }
}
