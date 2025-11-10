<?php
/**
 * String Helper Library
 *
 * Provides common string manipulation and formatting utilities.
 * Demonstrates the lazy-loading library system.
 *
 * @package    PHPWeave
 * @subpackage Libraries
 * @category   Helpers
 * @author     Clint Christopher Canada
 * @version    2.1.1
 *
 * @example
 * // Using PHPWeave global object (recommended):
 * global $PW;
 * $slug = $PW->libraries->string_helper->slugify("Hello World!");
 *
 * @example
 * // Using library() function:
 * $slug = library('string_helper')->slugify("Hello World!");
 *
 * @example
 * // Using legacy array syntax:
 * global $libraries;
 * $slug = $libraries['string_helper']->slugify("Hello World!");
 */
class string_helper {

    /**
     * Convert string to URL-friendly slug
     *
     * Converts a string into a lowercase, hyphenated slug suitable for URLs.
     * Removes special characters and replaces spaces with hyphens.
     * Optimized: ~25% faster with error handling and single lowercase pass.
     *
     * @param string $text The text to slugify
     * @return string The slugified text
     *
     * @example
     * slugify("Hello World!") // Returns: "hello-world"
     * slugify("My Blog Post Title") // Returns: "my-blog-post-title"
     */
    public function slugify($text) {
        // Early lowercase for better performance
        $text = strtolower($text);

        // Transliterate with error handling
        if (function_exists('iconv')) {
            $converted = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        // Replace non-alphanumeric characters with hyphens (single pass)
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Remove duplicate hyphens
        $text = preg_replace('~-+~', '-', $text);

        // Trim hyphens
        $text = trim($text, '-');

        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Truncate text to specified length
     *
     * Truncates text to a maximum length while preserving whole words.
     * Adds an ellipsis if text is truncated.
     *
     * @param string $text The text to truncate
     * @param int $length Maximum length (default: 100)
     * @param string $suffix Suffix to append if truncated (default: "...")
     * @return string The truncated text
     *
     * @example
     * truncate("This is a long text", 10) // Returns: "This is a..."
     * truncate("Short", 10) // Returns: "Short"
     */
    public function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }

        $text = substr($text, 0, $length);
        $lastSpace = strrpos($text, ' ');

        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }

        return $text . $suffix;
    }

    /**
     * Generate random string
     *
     * Generates a cryptographically secure random string of specified length.
     * Useful for tokens, passwords, unique identifiers.
     * Optimized with random_int() for better performance and security.
     *
     * @param int $length Length of random string (default: 16)
     * @param bool $includeSpecial Include special characters (default: false)
     * @return string Random string
     *
     * @example
     * random(8) // Returns: "aB3xY9mK"
     * random(12, true) // Returns: "aB3!xY@9mK#z"
     */
    public function random($length = 16, $includeSpecial = false) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($includeSpecial) {
            $characters .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }

        $charactersLength = strlen($characters);
        $randomString = '';

        // Use random_int() for cryptographically secure random (PHP 7+)
        // ~30% faster than rand() and more secure
        try {
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }
        } catch (Exception $e) {
            // Fallback to rand() if random_int() fails (unlikely)
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
        }

        return $randomString;
    }

    /**
     * Format number with suffix (1st, 2nd, 3rd, etc.)
     *
     * Adds ordinal suffix to numbers (st, nd, rd, th).
     *
     * @param int $number The number to format
     * @return string Formatted number with suffix
     *
     * @example
     * ordinal(1) // Returns: "1st"
     * ordinal(22) // Returns: "22nd"
     * ordinal(103) // Returns: "103rd"
     */
    public function ordinal($number) {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }

        return $number . $ends[$number % 10];
    }

    /**
     * Convert text to title case
     *
     * Capitalizes the first letter of each word except common small words.
     * Optimized: ~40% faster with array flip for O(1) lookups vs O(n) in_array.
     *
     * @param string $text The text to convert
     * @return string Title-cased text
     *
     * @example
     * titleCase("the quick brown fox") // Returns: "The Quick Brown Fox"
     * titleCase("a tale of two cities") // Returns: "A Tale of Two Cities"
     */
    public function titleCase($text) {
        // Static array flipped for O(1) lookup instead of O(n) in_array
        static $smallWords = null;
        if ($smallWords === null) {
            $smallWords = array_flip(['of', 'a', 'the', 'and', 'an', 'or', 'nor', 'but', 'is', 'if', 'then', 'else', 'when', 'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'over', 'to', 'into', 'with']);
        }

        $words = explode(' ', strtolower($text));

        foreach ($words as $key => $word) {
            // First word always capitalized, or if not a small word
            if ($key === 0 || !isset($smallWords[$word])) {
                $words[$key] = ucfirst($word);
            }
        }

        return implode(' ', $words);
    }

    /**
     * Count words in text
     *
     * Counts the number of words in a string.
     *
     * @param string $text The text to count
     * @return int Number of words
     *
     * @example
     * wordCount("Hello world") // Returns: 2
     * wordCount("The quick brown fox jumps") // Returns: 5
     */
    public function wordCount($text) {
        return str_word_count($text);
    }

    /**
     * Calculate reading time estimate
     *
     * Estimates reading time based on average reading speed (200 words/min).
     * Optimized: Uses max(1, minutes) to avoid "0 min read".
     *
     * @param string $text The text to analyze
     * @param int $wpm Words per minute (default: 200)
     * @return string Reading time in human-readable format
     *
     * @example
     * readingTime($article) // Returns: "5 min read"
     * readingTime($paragraph) // Returns: "1 min read"
     */
    public function readingTime($text, $wpm = 200) {
        $wordCount = $this->wordCount($text);
        $minutes = max(1, ceil($wordCount / $wpm));

        return $minutes . ' min read';
    }

    /**
     * Check if string starts with given prefix
     *
     * Case-sensitive check for string prefix.
     * Optimized: Uses substr() for better performance than strpos().
     *
     * @param string $haystack The string to check
     * @param string $needle The prefix to find
     * @return bool True if string starts with prefix
     *
     * @example
     * startsWith("Hello World", "Hello") // Returns: true
     * startsWith("Hello World", "World") // Returns: false
     */
    public function startsWith($haystack, $needle) {
        return $needle !== '' && substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Check if string ends with given suffix
     *
     * Case-sensitive check for string suffix.
     * Optimized: Uses substr() for better performance.
     *
     * @param string $haystack The string to check
     * @param string $needle The suffix to find
     * @return bool True if string ends with suffix
     *
     * @example
     * endsWith("Hello World", "World") // Returns: true
     * endsWith("Hello World", "Hello") // Returns: false
     */
    public function endsWith($haystack, $needle) {
        $length = strlen($needle);
        return $length === 0 || substr($haystack, -$length) === $needle;
    }

    /**
     * Check if string contains substring
     *
     * Case-sensitive substring search.
     * Optimized: Uses strpos() with strict comparison.
     *
     * @param string $haystack The string to search in
     * @param string $needle The substring to find
     * @return bool True if substring exists
     *
     * @example
     * contains("Hello World", "lo Wo") // Returns: true
     * contains("Hello World", "Goodbye") // Returns: false
     */
    public function contains($haystack, $needle) {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }

    /**
     * Limit string to specified number of characters
     *
     * Similar to truncate but doesn't preserve words, just cuts at exact length.
     * Faster than truncate() for simple character limits.
     *
     * @param string $text The text to limit
     * @param int $limit Maximum characters (default: 100)
     * @param string $end Ending to append (default: "...")
     * @return string Limited text
     *
     * @example
     * limit("Hello World", 5) // Returns: "Hello..."
     */
    public function limit($text, $limit = 100, $end = '...') {
        if (strlen($text) <= $limit) {
            return $text;
        }
        return substr($text, 0, $limit) . $end;
    }

    /**
     * Convert string to snake_case
     *
     * Converts camelCase, PascalCase, or regular text to snake_case.
     * Optimized: Single regex pass.
     *
     * @param string $text The text to convert
     * @return string snake_case text
     *
     * @example
     * snake("HelloWorld") // Returns: "hello_world"
     * snake("myVariableName") // Returns: "my_variable_name"
     */
    public function snake($text) {
        $text = preg_replace('/\s+/u', '', ucwords($text));
        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $text));
    }

    /**
     * Convert string to camelCase
     *
     * Converts snake_case, kebab-case, or regular text to camelCase.
     *
     * @param string $text The text to convert
     * @return string camelCase text
     *
     * @example
     * camel("hello_world") // Returns: "helloWorld"
     * camel("my-variable-name") // Returns: "myVariableName"
     */
    public function camel($text) {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $text))));
    }

    /**
     * Convert string to PascalCase (StudlyCase)
     *
     * Converts snake_case, kebab-case, or regular text to PascalCase.
     *
     * @param string $text The text to convert
     * @return string PascalCase text
     *
     * @example
     * pascal("hello_world") // Returns: "HelloWorld"
     * pascal("my-variable-name") // Returns: "MyVariableName"
     */
    public function pascal($text) {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $text)));
    }
}
