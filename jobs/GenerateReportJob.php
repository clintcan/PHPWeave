<?php
/**
 * Generate Report Job
 *
 * Background job for generating reports that might take a long time.
 * Useful for CSV exports, PDF generation, or data aggregation.
 *
 * @package    PHPWeave
 * @subpackage Jobs
 * @category   Jobs
 * @author     Clint Christopher Canada
 * @version    2.0.0
 *
 * @example
 * Async::queue('GenerateReportJob', [
 *     'type' => 'sales',
 *     'start_date' => '2024-01-01',
 *     'end_date' => '2024-12-31',
 *     'user_id' => 123
 * ]);
 */
class GenerateReportJob extends Job
{
    /**
     * Handle the job
     *
     * Generates a report based on specified parameters.
     *
     * @param array $data Report parameters
     * @return void
     * @throws Exception If report generation fails
     */
    public function handle($data)
    {
        $type = $data['type'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $userId = $data['user_id'] ?? null;

        error_log("[" . date('Y-m-d H:i:s') . "] Starting report generation: $type");

        // Simulate long-running task
        $this->generateReport($type, $startDate, $endDate);

        // Notify user when complete (could queue another SendEmailJob)
        if ($userId) {
            Async::queue('SendEmailJob', [
                'to' => $this->getUserEmail($userId),
                'subject' => 'Your Report is Ready',
                'message' => "Your $type report for $startDate to $endDate is ready for download."
            ]);
        }

        error_log("[" . date('Y-m-d H:i:s') . "] Report generation complete: $type");
    }

    /**
     * Generate the actual report
     *
     * @param string $type      Report type
     * @param string $startDate Start date
     * @param string $endDate   End date
     * @return void
     */
    private function generateReport($type, $startDate, $endDate)
    {
        // Actual report generation logic here
        // Could involve database queries, CSV writing, PDF generation, etc.

        // Placeholder implementation
        sleep(2); // Simulate time-consuming operation

        $reportPath = dirname(__FILE__, 2) . "/storage/reports/{$type}_" . date('YmdHis') . ".csv";

        // Ensure reports directory exists
        $reportsDir = dirname(__FILE__, 2) . "/storage/reports";
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }

        // Generate CSV (placeholder)
        $csv = "Date,Amount,Description\n";
        $csv .= "$startDate,1000,Sample Data\n";
        $csv .= "$endDate,2000,Sample Data\n";

        file_put_contents($reportPath, $csv);
    }

    /**
     * Get user email
     *
     * @param int $userId User ID
     * @return string User email
     */
    private function getUserEmail($userId)
    {
        // Fetch from database
        global $models;
        $user = $models['user_model']->getUser($userId);
        return $user['email'] ?? 'unknown@example.com';
    }
}
