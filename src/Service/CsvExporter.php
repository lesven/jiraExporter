<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class CsvExporter
{
    public function __construct(
        private string $exportBaseDir,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Export issues to CSV format according to specifications
     */
    public function exportToCsv(array $issues, ?string $exportDir = null, ?string $filename = null): string
    {
        $directory = $exportDir ?? $this->exportBaseDir;
        
        // Ensure export directory exists
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException("Export-Verzeichnis konnte nicht erstellt werden: $directory");
            }
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException("Export-Verzeichnis ist nicht beschreibbar: $directory");
        }

        // Generate filename if not provided
        if (!$filename) {
            $filename = 'jira_export_' . date('Y-m-d_H-i-s') . '.csv';
        }

        // Ensure .csv extension
        if (!str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;

        // Prepare CSV content
        $csvContent = $this->generateCsvContent($issues);

        // Write to file with proper encoding (UTF-8 without BOM)
        if (file_put_contents($filePath, $csvContent) === false) {
            throw new \RuntimeException("CSV-Datei konnte nicht geschrieben werden: $filePath");
        }

        $this->logger->info('CSV export completed', [
            'file_path' => $filePath,
            'issue_count' => count($issues),
            'file_size' => filesize($filePath),
        ]);

        return $filePath;
    }

    /**
     * Generate CSV content according to specifications
     */
    private function generateCsvContent(array $issues): string
    {
        if (empty($issues)) {
            // For 0 results, return empty CSV with header only
            // We'll determine headers from Jira field definitions or use common ones
            $headers = [
                'key', 'summary', 'status', 'assignee', 'reporter', 
                'created', 'updated', 'priority', 'issuetype'
            ];
            return $this->escapeHeaders($headers) . "\n";
        }

        // Extract all unique field names from all issues
        $allFields = [];
        foreach ($issues as $issue) {
            $fields = $issue['fields'] ?? [];
            $allFields = array_merge($allFields, array_keys($fields));
        }
        $allFields = array_unique($allFields);

        // Add key field (not in fields object)
        $headers = ['key'];
        
        // Add all other fields in the order they appear
        foreach ($allFields as $field) {
            $headers[] = $field;
        }

        $csvLines = [];
        
        // Add header line
        $csvLines[] = $this->escapeHeaders($headers);

        // Add data lines
        foreach ($issues as $issue) {
            $row = [];
            
            foreach ($headers as $header) {
                if ($header === 'key') {
                    $row[] = $issue['key'] ?? '';
                } else {
                    $fieldValue = $issue['fields'][$header] ?? null;
                    $row[] = $this->formatFieldValue($fieldValue);
                }
            }
            
            $csvLines[] = $this->escapeCsvRow($row);
        }

        return implode("\n", $csvLines) . "\n";
    }

    /**
     * Format field value according to specifications
     */
    private function formatFieldValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            // Handle multiple values (e.g., labels, components)
            $items = [];
            foreach ($value as $item) {
                if (is_array($item) && isset($item['name'])) {
                    $items[] = $item['name'];
                } elseif (is_array($item) && isset($item['value'])) {
                    $items[] = $item['value'];
                } elseif (is_string($item)) {
                    $items[] = $item;
                }
            }
            return implode('|', $items);
        }

        if (is_object($value)) {
            // Handle objects (e.g., user, status, priority)
            if (isset($value->displayName)) {
                return $value->displayName;
            } elseif (isset($value->name)) {
                return $value->name;
            } elseif (isset($value->value)) {
                return $value->value;
            }
        }

        // Convert to string and clean up rich text
        $stringValue = (string) $value;
        
        // Simple plaintext conversion - remove common rich text markers
        $stringValue = strip_tags($stringValue);
        $stringValue = html_entity_decode($stringValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $stringValue;
    }

    /**
     * Escape CSV headers (Custom Fields as IDs, Standard fields as display names)
     */
    private function escapeHeaders(array $headers): string
    {
        $escapedHeaders = [];
        
        foreach ($headers as $header) {
            // Custom fields keep their IDs, standard fields get display names
            if (str_starts_with($header, 'customfield_')) {
                $displayName = $header; // Keep custom field ID
            } else {
                $displayName = $this->getStandardFieldDisplayName($header);
            }
            
            $escapedHeaders[] = $this->escapeCsvValue($displayName);
        }
        
        return implode(',', $escapedHeaders);
    }

    /**
     * Get display name for standard Jira fields
     */
    private function getStandardFieldDisplayName(string $fieldKey): string
    {
        $displayNames = [
            'key' => 'Key',
            'summary' => 'Summary',
            'description' => 'Description',
            'status' => 'Status',
            'assignee' => 'Assignee',
            'reporter' => 'Reporter',
            'created' => 'Created',
            'updated' => 'Updated',
            'priority' => 'Priority',
            'issuetype' => 'Issue Type',
            'project' => 'Project',
            'labels' => 'Labels',
            'components' => 'Components',
            'fixVersions' => 'Fix Version/s',
            'versions' => 'Affects Version/s',
            'resolution' => 'Resolution',
            'resolutiondate' => 'Resolved',
            'duedate' => 'Due Date',
            'environment' => 'Environment',
            'attachment' => 'Attachment',
            'comment' => 'Comment',
            'worklog' => 'Log Work',
        ];

        return $displayNames[$fieldKey] ?? $fieldKey;
    }

    /**
     * Escape CSV row
     */
    private function escapeCsvRow(array $row): string
    {
        $escapedValues = [];
        
        foreach ($row as $value) {
            $escapedValues[] = $this->escapeCsvValue($value);
        }
        
        return implode(',', $escapedValues);
    }

    /**
     * Escape individual CSV value according to RFC4180-like rules
     */
    private function escapeCsvValue(string $value): string
    {
        // If value contains comma, quote, or newline, wrap in quotes and escape quotes
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n") || str_contains($value, "\r")) {
            // Escape quotes by doubling them
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }
        
        return $value;
    }
}
