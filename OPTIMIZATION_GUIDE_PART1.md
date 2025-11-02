# PHPWeave Performance Optimization Guide - Part 1

## 8 Quick Wins - Implementation Guide

### Optimization 1: Hook System Debug Flag Caching
**File:** coreapp/hooks.php | **Impact:** 2-3ms | **Effort:** 10 min

Add property at line 66:
```php
private static $debugEnabled = null;
```

In Hook::trigger(), replace lines 343-349:
```php
// Cache debug flag once (fast path)
if (self::$debugEnabled === null) {
    self::$debugEnabled = isset($GLOBALS['configs']['DEBUG']) && $GLOBALS['configs']['DEBUG'];
}

// Log hook execution only if debug truly enabled
if (self::$debugEnabled) {
    self::$executionLog[] = [
        'hook' => $hookName,
        'time' => microtime(true),
        'callbacks' => count(self::$hooks[$hookName])
    ];
}
```

---

### Optimization 2: Router Request Method/URI Caching
**File:** coreapp/router.php | **Impact:** 0.3-0.8ms | **Effort:** 15 min

Add properties at line 118:
```php
private static $cachedMethod = null;
private static $cachedUri = null;
```

In Router::match(), replace lines 462-463:
```php
$requestMethod = self::$cachedMethod ??= self::getRequestMethod();
$requestUri = self::$cachedUri ??= self::getRequestUri();
```

In handle404(), update Hook::trigger call:
```php
Hook::trigger('on_404', [
    'uri' => self::$cachedUri ?? self::getRequestUri(),
    'method' => self::$cachedMethod ?? self::getRequestMethod()
]);
```

---

### Optimization 3: Parameter Extraction Early Exit
**File:** coreapp/router.php | **Impact:** 0.2-0.5ms | **Effort:** 5 min

Replace extractParamNames() method (lines 435-439):
```php
private static function extractParamNames($pattern) {
    // Fast path: if no params, skip regex entirely
    if (strpos($pattern, ':') === false) {
        return [];
    }
    
    preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*):/', $pattern, $matches);
    return $matches[1];
}
```

---

### Optimization 4: Array Shift Elimination
**File:** coreapp/router.php | **Impact:** 0.1-0.3ms | **Effort:** 10 min

In Router::match(), replace lines 479-487:
```php
if (preg_match($route['regex'], $requestUri, $matches)) {
    // Don't shift - just offset index by 1
    $params = [];
    foreach ($route['params'] as $index => $paramName) {
        if (isset($matches[$index + 1])) {
            $params[$paramName] = $matches[$index + 1];
        }
    }
}
```

---

### Optimization 5: View Hook Early Exit
**File:** coreapp/controller.php | **Impact:** 0.3-0.8ms | **Effort:** 10 min

In Controller::show(), wrap hook calls:
```php
// Skip hook array creation if no hooks registered
if (Hook::has('before_view_render')) {
    $hookData = Hook::trigger('before_view_render', [
        'template' => $__template,
        'data' => $__data,
        'path' => "$__dir/views/$__template.php"
    ]);

    if (isset($hookData['data'])) {
        $__data = $hookData['data'];
    }
}
```

Similarly for after_view_render at end of method.

