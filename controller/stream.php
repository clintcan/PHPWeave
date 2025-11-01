<?php
/**
 * Stream Controller
 *
 * Examples of streaming responses that require disabling output buffering.
 *
 * @package    PHPWeave
 * @subpackage Controllers
 * @category   Controllers
 * @author     PHPWeave Framework
 * @version    2.2.2
 */
class Stream extends Controller
{
    /**
     * Server-Sent Events (SSE) Example
     *
     * Streams real-time updates to the browser.
     * Route: Route::get('/stream/sse', 'Stream@sse')
     *
     * @return void
     */
    function sse() {
        // Disable output buffering for this route
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        // Close any existing buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable Nginx buffering

        // Send updates every second
        for ($i = 1; $i <= 10; $i++) {
            echo "data: Update #$i at " . date('H:i:s') . "\n\n";

            // Flush output immediately
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            sleep(1);
        }

        echo "data: Stream complete\n\n";
        flush();
        exit;
    }

    /**
     * Progress Bar Example
     *
     * Streams progress updates for long-running tasks.
     * Route: Route::get('/stream/progress', 'Stream@progress')
     *
     * @return void
     */
    function progress() {
        // Disable output buffering
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Send HTML with inline JavaScript
        header('Content-Type: text/html; charset=utf-8');

        echo '<!DOCTYPE html><html><head><title>Progress</title></head><body>';
        echo '<h1>Processing...</h1><div id="progress"></div><script>';
        flush();

        // Simulate long task with progress updates
        for ($i = 0; $i <= 100; $i += 10) {
            echo "document.getElementById('progress').innerHTML = 'Progress: $i%<br>';";
            flush();
            usleep(500000); // 0.5 seconds
        }

        echo "document.getElementById('progress').innerHTML += '<br><strong>Complete!</strong>';";
        echo '</script></body></html>';
        flush();
        exit;
    }

    /**
     * Large File Download with Progress
     *
     * Streams file download with chunked transfer encoding.
     * Route: Route::get('/stream/download/:filename:', 'Stream@download')
     *
     * @param string $filename File to download
     * @return void
     */
    function download($filename = 'example.txt') {
        // Disable output buffering
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set download headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Cache-Control: no-cache');

        // Simulate large file download in chunks
        $chunkSize = 1024 * 8; // 8KB chunks
        $totalChunks = 100;

        for ($i = 0; $i < $totalChunks; $i++) {
            // Generate chunk data
            echo str_repeat('X', $chunkSize);

            // Flush to browser
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            usleep(50000); // 50ms delay per chunk
        }

        exit;
    }

    /**
     * JSON Streaming Example
     *
     * Streams JSON data line-by-line (NDJSON format).
     * Route: Route::get('/stream/json', 'Stream@json')
     *
     * @return void
     */
    function json() {
        // Disable output buffering
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for NDJSON (newline-delimited JSON)
        header('Content-Type: application/x-ndjson');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');

        // Stream 10 JSON objects
        for ($i = 1; $i <= 10; $i++) {
            $data = [
                'id' => $i,
                'message' => "Item number $i",
                'timestamp' => time(),
                'random' => rand(1, 1000)
            ];

            echo json_encode($data) . "\n";
            flush();

            usleep(300000); // 0.3 seconds
        }

        exit;
    }

    /**
     * Regular Response (with buffering)
     *
     * Normal route that uses output buffering for error handling.
     * Route: Route::get('/stream/normal', 'Stream@normal')
     *
     * @return void
     */
    function normal() {
        // Output buffering is ENABLED for this route (default behavior)
        // Headers can be sent even after view starts rendering

        $this->show('blog', [
            'title' => 'Normal Buffered Response',
            'message' => 'This route uses output buffering for proper error handling'
        ]);
    }
}
