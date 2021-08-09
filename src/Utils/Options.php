<?php


namespace Swift\Framework\Utils;


class Options
{
    /**
     * Merge options recursively
     *
     * @param  array $array1
     * @param  array $array2
     * @return array
     */
    public static function merge(array $array1, $array2 = null)
    {
        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    $array1[$key] = (array_key_exists($key, $array1) && is_array($array1[$key])) ?
                        self::merge($array1[$key], $array2[$key]) :
                        $array2[$key];
                } else {
                    $array1[$key] = $val;
                }
            }
        }

        return $array1;
    }
}
