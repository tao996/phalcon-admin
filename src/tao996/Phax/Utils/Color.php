<?php

namespace Phax\Utils;

// copy form phalcon migration
class Color
{
    public const int FG_BLACK = 1;
    public const int FG_DARK_GRAY = 2;
    public const int FG_BLUE = 3;
    public const int FG_LIGHT_BLUE = 4;
    public const int FG_GREEN = 5;
    public const int FG_LIGHT_GREEN = 6;
    public const int FG_CYAN = 7;
    public const int FG_LIGHT_CYAN = 8;
    public const int FG_RED = 9;
    public const int FG_LIGHT_RED = 10;
    public const int FG_PURPLE = 11;
    public const int FG_LIGHT_PURPLE = 12;
    public const int FG_BROWN = 13;
    public const int FG_YELLOW = 14;
    public const int FG_LIGHT_GRAY = 15;
    public const int FG_WHITE = 16;

    public const int BG_BLACK = 1;
    public const int BG_RED = 2;
    public const int BG_GREEN = 3;
    public const int BG_YELLOW = 4;
    public const int BG_BLUE = 5;
    public const int BG_MAGENTA = 6;
    public const int BG_CYAN = 7;
    public const int BG_LIGHT_GRAY = 8;

    public const int AT_NORMAL = 1;
    public const int AT_BOLD = 2;
    public const int AT_ITALIC = 3;
    public const int AT_UNDERLINE = 4;
    public const int AT_BLINK = 5;
    public const int AT_OUTLINE = 6;
    public const int AT_REVERSE = 7;
    public const int AT_NONDISP = 8;
    public const int AT_STRIKE = 9;

    /**
     * Map of supported foreground colors
     */
    private static array $fg = [
        self::FG_BLACK => '0;30',
        self::FG_DARK_GRAY => '1;30',
        self::FG_RED => '0;31',
        self::FG_LIGHT_RED => '1;31',
        self::FG_GREEN => '0;32',
        self::FG_LIGHT_GREEN => '1;32',
        self::FG_BROWN => '0;33',
        self::FG_YELLOW => '1;33',
        self::FG_BLUE => '0;34',
        self::FG_LIGHT_BLUE => '1;34',
        self::FG_PURPLE => '0;35',
        self::FG_LIGHT_PURPLE => '1;35',
        self::FG_CYAN => '0;36',
        self::FG_LIGHT_CYAN => '1;36',
        self::FG_LIGHT_GRAY => '0;37',
        self::FG_WHITE => '1;37',
    ];

    /**
     * Map of supported background colors
     */
    private static array $bg = [
        self::BG_BLACK => '40',
        self::BG_RED => '41',
        self::BG_GREEN => '42',
        self::BG_YELLOW => '43',
        self::BG_BLUE => '44',
        self::BG_MAGENTA => '45',
        self::BG_CYAN => '46',
        self::BG_LIGHT_GRAY => '47',
    ];

    /**
     * Map of supported attributes
     */
    private static array $at = [
        self::AT_NORMAL => '0',
        self::AT_BOLD => '1',
        self::AT_ITALIC => '3',
        self::AT_UNDERLINE => '4',
        self::AT_BLINK => '5',
        self::AT_OUTLINE => '6',
        self::AT_REVERSE => '7',
        self::AT_NONDISP => '8',
        self::AT_STRIKE => '9',
    ];

    /**
     * Identify if console supports colors
     */
    public static function isSupportedShell(): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI') || 'xterm' === getenv('TERM');
        }

        return defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    /**
     * Colorizes the string using provided colors.
     */
    public static function colorize(string $string, int $fg = null, int $at = null, int $bg = null): string
    {
        // Shell not supported, exit early
        if (!static::isSupportedShell()) {
            return $string;
        }

        $colored = '';

        // Check if given foreground color is supported
        if (isset(static::$fg[$fg])) {
            $colored .= "\033[" . static::$fg[$fg] . "m";
        }

        // Check if given background color is supported
        if (isset(static::$bg[$bg])) {
            $colored .= "\033[" . static::$bg[$bg] . "m";
        }

        // Check if given attribute is supported
        if (isset(static::$at[$at])) {
            $colored .= "\033[" . static::$at[$at] . "m";
        }

        // Add string and end coloring
        $colored .= $string . "\033[0m";

        return $colored;
    }

    public static function head(string $msg): string
    {
        return Color::colorize($msg, Color::FG_BROWN, Color::AT_BOLD);
    }

    /**
     * Color style for error messages.
     */
    public static function error(string $msg, string $prefix = 'Error: '): string
    {
        return self::message($prefix . $msg, Color::BG_RED);
    }

    /**
     * Color style for fatal error messages.
     */
    public static function fatal(string $msg, string $prefix = 'Fatal Error: '): string
    {
        return self::message($prefix . $msg, Color::BG_RED);
    }

    /**
     * Color style for success messages.
     */
    public static function success(string $msg, string $prefix = 'Success: '): string
    {
        return self::message($prefix . $msg, Color::BG_GREEN);
    }

    /**
     * Color style for info messages.
     */
    public static function info(string $msg, string $prefix = 'Info: '): string
    {
        return self::message($prefix . $msg, Color::BG_BLUE);
    }

    public static function warning(string $msg, string $prefix = 'Warning: '): string
    {
        return self::message($prefix . $msg, Color::BG_YELLOW);
    }

    public static function message(string $msg, string $bg = Color::BG_BLUE): string
    {
        return static::colorize(' ' . $msg . ' ', Color::FG_WHITE, Color::AT_BOLD, $bg);
    }

    /**
     * Output tab space
     *
     * Depending on length of string.
     */
    public static function spacesPrint(string $string, int $size = 30): string
    {
        return sprintf('%-' . $size . 's', $string);
    }
}
