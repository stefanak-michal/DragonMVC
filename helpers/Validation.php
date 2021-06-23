<?php

namespace helpers;

/**
 * Validation helper functions
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
     * Replace all empty strings with null
     * @param array $data
     */
    public static function nullify(array &$data)
    {
        foreach ($data as &$value) {
            if ($value === '')
                $value = null;
            elseif ($value === 'true' || $value === 'false')
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
