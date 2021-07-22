<?php

namespace helpers;

/**
 * Different array functions
 */
class ArrayUtils
{

    /**
     * reIndex
     * For an array of assoc rays, return a new array of assoc rays using a certain field for keys
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function reIndex(array $array, string $key): array
    {
        return array_combine(array_column($array, $key), array_values($array));
    }

    /**
     * Shuffle array with keeping keys
     * 
     * @param array $array
     */
    public static function shuffleAssoc(array &$array)
    {
        $keys = array_keys($array);
        shuffle($keys);

        $new = [];
        foreach ($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;
    }

    /**
     * Recursive fnc walking through multidimensional array
     * Return all values by key in all array levels
     *
     * @code
     * Example: getAllValuesByKey($arr, '_id')
     * $arr =>>     Array
     *              (
     *                  [responses] => Array
     *                  (
     *                      [0] => Array
     *                      (
     *                          [took] => 13
     *                          [_shards] => Array
     *                          (
     *                              [total] => 5
     *                              [successful] => 5
     *                              [failed] => 0
     *                          )
     *                      [total] => 1
     *                      [matches] => Array
     *                      (
     *                          [0] => Array
     *                          (
     *                              [_index] => bazar
     *                              [_id] => 3
     *                          )
     *                      )
     *                      [1] => Array
     *                      (
     *                          [took] => 13
     *                          [_shards] => Array
     *                          (
     *                              [total] => 5
     *                              [successful] => 5
     *                              [failed] => 0
     *                          )
     *                      [total] => 1
     *                      [matches] => Array
     *                      (
     *                          [0] => Array
     *                          (
     *                              [_index] => bazar
     *                              [_id] => 4
     *                          )
     *                      )
     *                      [2] => Array
     *                      (
     *                          [took] => 13
     *                          [_shards] => Array
     *                          (
     *                              [total] => 5
     *                              [successful] => 5
     *                              [failed] => 0
     *                          )
     *                      [total] => 1
     *                      [matches] => Array
     *                      (
     *                          [0] => Array
     *                          (
     *                              [_index] => bazar
     *                              [_id] => 3
     *                          )
     *                      )
     *                  )
     *              )
     * $out =>> Array
     *          (
     *              [0] => 3
     *              [1] => 4
     *              [2] => 3
     *          )
     * @encode
     *
     * @param array $data
     * @param mixed $key
     * @return array
     */
    public static function getAllValuesByKey($data, $key)
    {
        $out = array();

        if (is_array($data)) {
            if (array_key_exists($key, $data)) {
                $out[] = $data[$key];
            }

            foreach ($data AS $value) {
                $out = array_merge($out, self::getAllValuesByKey($value, $key));
            }
        }

        return $out;
    }

    /**
     * Recursive array_map
     * 
     * @param callable $func
     * @param array $arr
     * @return array
     */
    public static function arrayMapRecursive(callable $func, array $arr): array
    {
        array_walk_recursive($arr, function(&$v) use ($func) {
            $v = $func($v);
        });

        return $arr;
    }

    /**
     * Find nearest number in array of numbers
     * 
     * @param int $search
     * @param array $arr
     * @return int
     */
    public static function closest(int $search, array $arr): int
    {
        $closest = null;
        foreach ($arr as $item) {
            if ($closest == null || abs($search - $closest) > abs($item - $search)) {
                $closest = $item;
            }
        }

        return $closest;
    }

}
