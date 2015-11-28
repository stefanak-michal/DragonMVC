<?php

namespace helpers;

/**
 * Different array functions
 */
class ArrayUtil
{

    /**
      verticalSlice
      1. For an array of assoc rays, return an array of values for a particular key
      2. if $keyfield is given, same as above but use that hash key as the key in new array
     */
    public static function verticalSlice($array, $field, $keyfield = null)
    {
        $array = (array) $array;

        $R = array();
        foreach ( $array as $obj ) {
            if ( !array_key_exists($field, $obj) ) {
                trigger_error("verticalSlice: array doesn't have requested field\n");
            }

            if ( $keyfield ) {
                if ( !array_key_exists($keyfield, $obj) ) {
                    trigger_error("verticalSlice: array doesn't have requested field\n");
                } else {
                    $R[$obj[$keyfield]] = $obj[$field];
                }
            } else {
                $R[] = $obj[$field];
            }
        }
        return $R;
    }

    /**
      reIndex
      For an array of assoc rays, return a new array of assoc rays using a certain field for keys
     */
    public static function reIndex()
    {
        $fields = func_get_args();
        $array = array_shift($fields);
        $array = (array) $array;

        $R = array();
        if ( !empty($array) ) {
            foreach ( $array as $obj ) {
                $target = & $R;

                foreach ( $fields as $field ) {
                    if ( !array_key_exists($field, $obj) ) {
                        trigger_error("reIndex: array doesn't have requested field\n");
                    } else {
                        $nextkey = $obj[$field];
                        $target = & $target[$nextkey];
                    }
                }
                $target = $obj;
            }
        }
        return $R;
    }

    /**
     * Return filtered multidimensional array by column and value in it
     * 
     * @param array $arr
     * @param mixed $column Key in second level
     * @param mixed $value Value for filtering in that key
     * @param boolean $equal
     * @return array
     */
    public static function filterByColumn($arr, $column, $value, $equal = true)
    {
        $output = array();

        if ( is_array($arr) && !empty($arr) ) {
            foreach ( $arr AS $row ) {
                if ( $equal ) {
                    if ( $row[$column] == $value ) {
                        $output[] = $row;
                    }
                } else {
                    if ( $row[$column] != $value ) {
                        $output[] = $row;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Shuffle array with keeping keys
     * 
     * @param array $array
     * @return boolean
     */
    public static function shuffleAssoc(&$array)
    {
        $keys = array_keys($array);

        shuffle($keys);

        foreach ( $keys as $key ) {
            $new[$key] = $array[$key];
        }

        $array = $new;

        return true;
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

        if ( is_array($data) ) {
            if ( array_key_exists($key, $data) ) {
                $out[] = $data[$key];
            }

            foreach ( $data AS $value ) {
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
    public static function arrayMapRecursive(callable $func, array $arr)
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
    public static function closest($search, $arr)
    {
        $closest = null;
        foreach ( $arr as $item ) {
            if ( $closest == null || abs($search - $closest) > abs($item - $search) ) {
                $closest = $item;
            }
        }

        return $closest;
    }

}
