<?php
/**
 * User: joachimdoerr
 * Date: 26.07.18
 * Time: 19:54
 */

namespace TextileMigration\Replacer;


use Netcarver\Textile\Parser;
use TextileMigration\Finder\SlicesFinder;
use TextileMigration\Processor\MigrationProcessor;

class SliceValueReplacer
{
    /**
     * @param $name
     * @return array|null
     * @author Joachim Doerr
     */
    public static function getSliceValueName($name)
    {
        $key = null;
        $arr = explode('][', $name);
        $value = null;

        if (strpos(strtolower($arr[0]), 'value') !== false) {
            $ar = explode('_', $arr[0]);
            foreach ($ar as $val) {
                if (strpos(strtolower($val), 'value') !== false) {
                    $value = str_replace(array('[',']'), '', strtolower($val));
                }
            }
        }

        if (is_null($value)) {
            return null;
        }

        unset($arr[0]);
        $array = array('key' => $value);

        if (sizeof($arr) > 0) {
            $array['sub_keys'] = array();
            foreach ($arr as $val) {
                $array['sub_keys'][] = str_replace(array('[',']'), '', $val);
            }
        }

        return $array;
    }

    /**
     * @param array $slices
     * @param string $type
     * @return array|mixed
     * @author Joachim Doerr
     */
    public static function replaceSlicesValues(array $slices)
    {
        if (sizeof($slices) > 0) {
            foreach ($slices as $skey => $moduleSlices) {
                if (isset($moduleSlices['slices']) && sizeof($moduleSlices['slices']) > 0 &&
                    isset($moduleSlices['values_keys']) && sizeof($moduleSlices['values_keys'])) {
                    foreach ($moduleSlices['slices'] as $key => $slice) {
                        $slices[$skey]['slices'][$key] = SliceValueReplacer::replaceSliceValues(null, $slice, $moduleSlices['values_keys'], $moduleSlices['type']);
                    }
                }
            }
        }

        return $slices;
    }

    /**
     * @param null $sliceId
     * @param null $slice
     * @param array $valueKeys
     * @param string $type
     * @return null
     * @author Joachim Doerr
     */
    public static function replaceSliceValues($sliceId = null, $slice = null, array $valueKeys, $type = MigrationProcessor::DEFAULT_TYPE)
    {
        if (is_null($slice)) {
            $slices = SlicesFinder::findSlicesByIds(array($sliceId), $type);
            $slice = (sizeof($slices) > 0) ? $slices[0] : null;
        }

        if (is_null($slice)) {
            return null;
        }

        foreach ($valueKeys as $valueKey) {
            if (isset($valueKey['key'])) {
                if (!isset($valueKey['sub_keys'])) {
                    if (isset($slice[$valueKey['key']])) {
                        $slice[$valueKey['key']] = self::parse($type, $slice[$valueKey['key']]);
                    }
                } else {
                    $value = \rex_var::toArray($slice[$valueKey['key']]);

                    foreach ($valueKey['sub_keys'] as $sub_key) {

                        if (sizeof($sub_key) > 6) {
                            dump('WTF -> this stupid programmer use more than 6 levels for a rex_json_value! Kill ME!');
                            dump($value);
                            die;
                        }

                        switch (sizeof($sub_key)) {
                            case 1:
                                $value[$sub_key[0]] = self::parse($type, $value[$sub_key[0]]);
                                break;
                            case 2:
                                $value[$sub_key[0]][$sub_key[1]] = self::parse($type, $value[$sub_key[0]][$sub_key[1]]);
                                break;
                            case 3:
                                $value[$sub_key[0]][$sub_key[1]][$sub_key[2]] = self::parse($type, $value[$sub_key[0]][$sub_key[1]][$sub_key[2]]);
                                break;
                            case 4:
                                $value[$sub_key[0]][$sub_key[1]][$sub_key[2]][$sub_key[3]] = self::parse($type, $value[$sub_key[0]][$sub_key[1]][$sub_key[2]][$sub_key[3]]);
                                break;
                            case 5:
                                $value[$sub_key[0]][$sub_key[1]][$sub_key[2]][$sub_key[3]][$sub_key[4]] = self::parse($type, $value[$sub_key[0]][$sub_key[1]][$sub_key[2]][$sub_key[3]][$sub_key[4]]);
                                break;
                            case 6:
                                $value[$sub_key[0]][$sub_key[1]][$sub_key[2]][$sub_key[3]][$sub_key[4]][$sub_key[5]] = self::parse($type, $value[$sub_key[0]][$sub_key[1]][$sub_key[2]][$sub_key[3]][$sub_key[4]][$sub_key[5]]);
                                break;
                        }
                    }

                    $slice[$valueKey['key']] = json_encode($value);
                }
            }
        }

        return $slice;
    }

    /**
     * @param $type
     * @param $value
     * @return string
     * @author Joachim Doerr
     */
    public static function parse($type, $value)
    {
        switch ($type) {
            case 'markdown':
                break;
            case 'textile':
                $textile = new \Textile();
                $value = $textile->textileRestricted($value);
                break;
            default:
                $parser = new Parser();
                $value = $parser->parse($value);
        }
        return $value;
    }
}