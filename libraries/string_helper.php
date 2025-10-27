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
     *
     * @param string $text The text to slugify
     * @return string The slugified text
     *
     * @example
     * slugify("Hello World!") // Returns: "hello-world"
     * slugify("My Blog Post Title") // Returns: "my-blog-post-title"
     */
    public function slugify($text) {
        // Replace non-alphanumeric characters with spaces
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim and lowercase
        $text = trim($text, '-');
        $text = strtolower($text);

        // Remove duplicate hyphens
        $text = preg_replace('~-+~', '-', $text);

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
     * Generates a random string of specified length using alphanumeric characters.
     * Useful for tokens, passwords, unique identifiers.
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

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
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
     *
     * @param string $text The text to convert
     * @return string Title-cased text
     *
     * @example
     * titleCase("the quick brown fox") // Returns: "The Quick Brown Fox"
     * titleCase("a tale of two cities") // Returns: "A Tale of Two Cities"
     */
    public function titleCase($text) {
        $smallWords = ['of', 'a', 'the', 'and', 'an', 'or', 'nor', 'but', 'is', 'if', 'then', 'else', 'when', 'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'over', 'to', 'into', 'with'];

        $words = explode(' ', strtolower($text));

        foreach ($words as $key => $word) {
            if ($key == 0 || !in_array($word, $smallWords)) {
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
        $minutes = ceil($wordCount / $wpm);

        return $minutes . ' min read';
    }
}
