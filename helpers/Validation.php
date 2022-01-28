<?php

namespace helpers;

/**
 * Validation helper functions
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 * @package helpers
 */
class Validation
{
    /**
     * Check email
     *
     * @param string $email
     * @return boolean
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check url
     *
     * @param string $url
     * @return boolean
     */
    public static function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Sanitize values to proper data types
     * @param array $data
     */
    public static function sanitize(array &$data)
    {
        foreach ($data as &$entry) {
            if (is_string($entry)) {
                if (strlen($entry) == 0)
                    $entry = null;
                elseif ($entry == 'true')
                    $entry = true;
                elseif ($entry == 'false')
                    $entry = false;
                elseif (preg_match("/^(\d|[1-9]\d+)$/", $entry))
                    $entry = intval($entry);
                elseif (preg_match("/^\d*\.\d+$/", $entry))
                    $entry = floatval($entry);
            } elseif (is_array($entry)) {
                self::sanitize($entry);
            }
        }
    }

    /**
     * Check if all values are not empty
     * @param array $data
     * @param array $keys Whitelist, left empty if you want to check all values
     * @return bool
     */
    public static function filled(array &$data, array $keys = []): bool
    {
        if (!empty($keys)) {
            $data = array_intersect_key($data, array_flip($keys));
            if (count($data) != count($keys))
                return false;
        }

        foreach ($data as $value) {
            if (empty($value))
                return false;
        }

        return true;
    }
    
}
