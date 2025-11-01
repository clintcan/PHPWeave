# Output Buffering & Streaming

**Version:** 2.2.2+
**Last Updated:** November 1, 2025

---

## Overview

PHPWeave uses **output buffering** by default to prevent "headers already sent" errors. However, output buffering conflicts with **streaming responses** (SSE, progress updates, large file downloads). This guide explains how both work together.

---

## How Output Buffering Works

### Default Behavior (Buffering Enabled)

```php
// public/index.php - Line 5-7
if (!isset($_SERVER['DISABLE_OUTPUT_BUFFER']) || !$_SERVER['DISABLE_OUTPUT_BUFFER']) {
    ob_start();
}
```

**Flow:**
1. Request starts → `ob_start()` activated
2. View renders → Output captured in buffer (not sent to browser)
3. Error occurs → Buffer cleared with `ob_clean()`
4. Headers sent → `header('HTTP/1.1 500...')` works!
5. Error displayed cleanly
6. Request ends → `ob_end_flush()` sends output

**Benefits:**
- ✅ Prevents "headers already sent" errors
- ✅ Clean error pages with proper HTTP status codes
- ✅ Security headers can always be sent
- ✅ Professional error handling

**Limitations:**
- ❌ Breaks streaming responses (SSE, chunked transfer)
- ❌ Can't send incremental output to browser

---

## When You Need Streaming

### Use Cases Requiring Streaming

1. **Server-Sent Events (SSE)** - Real-time updates
2. **Progress bars** - Long-running task updates
3. **Large file downloads** - Chunked transfer with progress
4. **Live data feeds** - Continuous data streaming
5. **WebSocket alternatives** - SSE for server→client push

### Problems with Buffering + Streaming

```php
// This WON'T work with buffering enabled!
ob_start(); // ← Buffer captures everything

echo "Progress: 10%\n";
flush(); // ← Browser doesn't see this yet!

echo "Progress: 50%\n";
flush(); // ← Still buffered!

ob_end_flush(); // ← Browser finally sees everything at once
```

---

## How to Disable Buffering for Streaming Routes

### Method: Set `$_SERVER['DISABLE_OUTPUT_BUFFER']`

```php
<?php
// controller/stream.php
class Stream extends Controller
{
    function sse() {
        // Disable output buffering for this route
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        // Close any existing buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Now streaming works!
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        for ($i = 1; $i <= 10; $i++) {
            echo "data: Update #$i\n\n";
            flush(); // ← Sent to browser immediately!
            sleep(1);
        }

        exit;
    }
}
```

---

## Examples

### Example 1: Server-Sent Events (SSE)

```php
<?php
class Stream extends Controller
{
    function sse() {
        // IMPORTANT: Disable buffering first
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable Nginx buffering

        // Stream updates
        for ($i = 1; $i <= 100; $i++) {
            echo "data: {\"progress\": $i, \"timestamp\": " . time() . "}\n\n";
            flush();
            usleep(100000); // 0.1 second
        }

        exit;
    }
}
```

**Client-side (JavaScript):**
```html
<script>
const eventSource = new EventSource('/stream/sse');

eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Progress:', data.progress + '%');
};
</script>
```

---

### Example 2: Progress Bar

```php
<?php
class Task extends Controller
{
    function processLargeFile() {
        // Disable buffering
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');

        echo '<!DOCTYPE html><html><head><title>Processing</title></head><body>';
        echo '<h1>Processing File</h1><div id="status"></div><script>';
        flush();

        $totalRecords = 1000;
        for ($i = 1; $i <= $totalRecords; $i++) {
            // Process record...
            usleep(5000);

            // Update progress every 10 records
            if ($i % 10 == 0) {
                $percent = round(($i / $totalRecords) * 100);
                echo "document.getElementById('status').innerHTML = 'Processed $i/$totalRecords ($percent%)<br>';";
                flush();
            }
        }

        echo "document.getElementById('status').innerHTML += '<strong>Complete!</strong>';";
        echo '</script></body></html>';
        exit;
    }
}
```

---

### Example 3: Large File Download

```php
<?php
class Download extends Controller
{
    function largeFile($filename) {
        // Disable buffering for large files
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filePath = PHPWEAVE_ROOT . '/storage/' . basename($filename);

        if (!file_exists($filePath)) {
            header('HTTP/1.0 404 Not Found');
            echo 'File not found';
            exit;
        }

        // Download headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');

        // Stream file in chunks
        $handle = fopen($filePath, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 1024 * 8); // 8KB chunks
            flush();
        }
        fclose($handle);

        exit;
    }
}
```

---

### Example 4: JSON Streaming (NDJSON)

```php
<?php
class Api extends Controller
{
    function streamData() {
        // Disable buffering
        $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // NDJSON headers (newline-delimited JSON)
        header('Content-Type: application/x-ndjson');
        header('Cache-Control: no-cache');

        // Stream database records one-by-one
        global $PW;
        $stmt = $PW->models->user_model->getAllUsersStream();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode($row) . "\n";
            flush();
        }

        exit;
    }
}
```

---

## Best Practices

### ✅ DO: Disable buffering for streaming routes

```php
function sse() {
    $_SERVER['DISABLE_OUTPUT_BUFFER'] = true;
    while (ob_get_level() > 0) ob_end_clean();

    // Stream content...
}
```

### ✅ DO: Always flush after echo

```php
echo "data: message\n\n";
flush(); // ← Required for immediate delivery
```

### ✅ DO: Set proper headers

```php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Nginx
```

### ✅ DO: Exit after streaming

```php
for ($i = 1; $i <= 10; $i++) {
    echo "data: $i\n\n";
    flush();
}
exit; // ← Important!
```

### ❌ DON'T: Use views with streaming

```php
// This won't work - views expect buffering
$this->show('template', $data);
```

### ❌ DON'T: Mix buffering and streaming

```php
// Bad - inconsistent behavior
echo "Start"; // buffered
$_SERVER['DISABLE_OUTPUT_BUFFER'] = true; // Too late!
echo "End"; // ???
```

---

## Comparison Table

| Feature | Buffering Enabled (Default) | Buffering Disabled (Streaming) |
|---------|----------------------------|--------------------------------|
| **Use Cases** | Normal HTML pages, JSON APIs | SSE, Progress bars, Large files |
| **Error Handling** | ✅ Clean error pages | ⚠️ Partial output possible |
| **Headers** | ✅ Can send anytime | ❌ Must send before output |
| **Performance** | ✅ Optimized | ✅ Optimized for streaming |
| **Real-time Updates** | ❌ No | ✅ Yes |
| **Views (`$this->show()`)** | ✅ Works | ❌ Don't use |

---

## Nginx Configuration for Streaming

If using Nginx as reverse proxy, disable buffering:

```nginx
location /stream/ {
    proxy_pass http://phpweave:80;
    proxy_buffering off;
    proxy_cache off;
    proxy_set_header X-Accel-Buffering no;
    proxy_read_timeout 3600s; # For long-running streams
}
```

---

## Testing Streaming Routes

### Test SSE

```bash
# Terminal 1: Start container
docker run -d -p 8080:80 phpweave:2.2.2-apache

# Terminal 2: Test SSE
curl -N http://localhost:8080/stream/sse

# Output:
data: Update #1 at 14:30:01

data: Update #2 at 14:30:02

data: Update #3 at 14:30:03
...
```

### Test Progress Bar

```bash
# Browser
http://localhost:8080/stream/progress

# Should see real-time progress updates
```

---

## FAQ

### Q: Do all routes need to disable buffering?

**A:** No! Only streaming routes need it. Regular routes benefit from buffering (better error handling).

### Q: What if I forget to disable buffering for SSE?

**A:** The browser won't receive updates in real-time. It will wait until the route completes and get everything at once.

### Q: Can I use views with streaming?

**A:** No. Views expect buffering. Echo HTML directly for streaming routes.

### Q: Does buffering slow down responses?

**A:** No. Buffering adds < 0.1ms overhead and uses minimal memory.

### Q: What about WebSockets?

**A:** WebSockets require a WebSocket server (not HTTP). Use SSE as a simpler alternative for server→client push.

---

## Summary

- **Default:** Output buffering is **ENABLED** (prevents header errors)
- **Streaming:** Set `$_SERVER['DISABLE_OUTPUT_BUFFER'] = true` per route
- **Always flush:** Call `flush()` after `echo` in streaming routes
- **Exit after streaming:** Use `exit` to prevent buffer issues
- **Normal routes:** Leave buffering enabled for error handling

---

**Last Updated:** November 1, 2025
**PHPWeave Version:** 2.2.2+
