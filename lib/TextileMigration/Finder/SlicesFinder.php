<?php

namespace TextileMigration\Finder;


use TextileMigration\Processor\MigrationProcessor;
use TextileMigration\Replacer\SliceValueReplacer;
use rex;

class SlicesFinder
{
    /**
     * @param $definition
     * @param null $step
     * @param int $limit
     * @param string $type
     * @return array
     * @author Joachim Doerr
     */
    public static function findSlicesByDefinition($definition, $step = null, $limit = 50, $type = MigrationProcessor::DEFAULT_TYPE)
    {
        $slices = array();
        $count = 0;

        if (is_array($definition) && sizeof($definition) > 0 && isset($definition['modules']) && is_array($definition['modules']) && sizeof($definition['modules']) > 0) {
            $ids = array();
            foreach ($definition['modules'] as $item) {
                $ids[] = $item['id'];
                $slices[$item['id']] = array(
                    'module' => null,
                    'values' => null,
                    'values_keys' => array(),
                    'slices' => array(),
                    'type' => $type,
                );

                if (isset($item['values']) && is_array($item['values']) && sizeof($item['values']) >0) {
                    foreach ($item['values'] as $value) {
                        $result = array();
                        if (isset($value['value'])) {
                            $result['key'] = $value['value'];
                        }
                        if (isset($value['mblock_key'])) {
                            $subKey = explode('.', $value['mblock_key']);
                            unset($subKey[0]);
                            $result['sub_keys'] = array(array_values($subKey));
                        }
                        if (isset($value['mblock_keys']) && is_array($value['mblock_keys']) && sizeof($value['mblock_keys']) > 0) {
                            if (!isset($result['sub_keys']) || !is_array($result['sub_keys'])) {
                                $result['sub_keys'] = array();
                            }
                            foreach ($value['mblock_keys'] as $mblock_key) {
                                $subKey = explode('.', $mblock_key);
                                unset($subKey[0]);
                                $result['sub_keys'][] = array_values($subKey);
                            }
                        }
                        if (!empty($result)) {
                            $slices[$item['id']]['values_keys'][] = $result;
                        }
                    }
                }
            }

            if ($limit > 0) {
                if (!is_null($step) && is_int($step)) {
                    $limit = ' LIMIT ' . ($step * $limit) . ', ' . $limit;
                } else {
                    $limit = ' LIMIT ' . $limit;
                }
            }

            $limit = ($limit === 0) ? '' : $limit;

            $sql = \rex_sql::factory();
            try {
                if (is_array($ids)) {
                    $result = $sql->getArray("SELECT * FROM " . rex::getTablePrefix() . "article_slice WHERE module_id IN (" . implode(',', $ids) . ")" . $limit);
                    $count = $sql->getArray("SELECT count(*) as con FROM " . rex::getTablePrefix() . "article_slice WHERE module_id IN (" . implode(',', $ids) . ")");
                    $count = $count[0]['con'];
                    // decorate slices
                    if (sizeof($slices) > 0 && sizeof($result) > 0) {
                        foreach ($result as $item) {
                            $slices[$item['module_id']]['slices'][] = $item;
                        }
                    } else {
                        return array('slices' => array(), 'count' => 0);
                    }
                }
            } catch (\rex_sql_exception $e) {
                \rex_logger::logException($e);
                return array('slices' => array(), 'count' => 0);
            }

        }

        return array('slices' => $slices, 'count' => $count);
    }

    /**
     * @param null $step
     * @param int $limit
     * @param string $type
     * @return array
     * @author Joachim Doerr
     */
    public static function findMarkitupSlices($step = null, $limit = 50, $type = 'textile')
    {
        if (!MarkitupProfiles::isMarkitUpExist()) {
            // TODO error msg.
            return array();
        }

        $modules = ModuleFinder::getMarkitupModules($type);
        $ids = null;

        if (sizeof($modules) > 0) {
            $ids = array_keys($modules);
        }

        if ($limit > 0) {
            if (!is_null($step) && is_int($step)) {
                $limit = ' LIMIT ' . ($step * $limit) . ', ' . $limit;
            } else {
                $limit = ' LIMIT ' . $limit;
            }
        }

        $limit = ($limit === 0) ? '' : $limit;

        $sql = \rex_sql::factory();
        try {
            if (is_array($ids)) {
                $result = $sql->getArray("SELECT * FROM " . rex::getTablePrefix() . "article_slice WHERE module_id IN (" . implode(',', $ids) . ")" . $limit);
                $count = $sql->getArray("SELECT count(*) as con FROM " . rex::getTablePrefix() . "article_slice WHERE module_id IN (" . implode(',', $ids) . ")");
                $count = $count[0]['con'];
                $slices = self::decorateSliceResult($result, $type, $modules);

                return array('slices' => $slices, 'count' => $count);
            }
            return array();
        } catch (\rex_sql_exception $e) {
            \rex_logger::logException($e);
            return array('slices' => array(), 'count' => 0);
        }
    }

    /**
     * @param $result
     * @param string $type
     * @param null $modules
     * @return array
     * @author Joachim Doerr
     */
    protected static function decorateSliceResult($result, $type = 'textile', $modules = null)
    {
        $slices = array();
        $modules = (!is_null($modules)) ? $modules : ModuleFinder::getMarkitupModules($type);

        if (sizeof($result) > 0) {
            foreach ($result as $slice) {
                if (!isset($slices[$slice['module_id']])) {
                    if (!isset($modules[$slice['module_id']])) {
                        continue;
                    }
                    $module = $modules[$slice['module_id']];
                    $slices[$module['id']] = array(
                            'module' => $module,
                            'values' => self::findValuesByInput($module['input'], $type),
                            'values_keys' => array(),
                            'slices' => array(),
                            'type' => $type,
                    );
                    if (sizeof($slices[$module['id']]['values']) > 0) {
                        foreach ($slices[$module['id']]['values'] as $valueKey) {
                            $slices[$module['id']]['values_keys'][] = SliceValueReplacer::getSliceValueName($valueKey);
                        }
                    }
                }
                $slices[$slice['module_id']]['slices'][] = $slice;
            }
        }

        return $slices;
    }

    /**
     * @param array $sliceIds
     * @param string $type
     * @return array
     * @author Joachim Doerr
     */
    public static function findSlicesByIds(array $sliceIds, $type = 'textile')
    {
        if (!MarkitupProfiles::isMarkitUpExist()) {
            // TODO error msg.
            return array();
        }

        $sql = \rex_sql::factory();
        try {
            $result = $sql->getArray("SELECT * FROM " . rex::getTablePrefix() . "article_slice WHERE id IN (" . implode(',', $sliceIds) . ")");

            return self::decorateSliceResult($result);

        } catch (\rex_sql_exception $e) {
            \rex_logger::logException($e);
            return array();
        }
    }

    /**
     * @param $input
     * @param string $type
     * @return array
     * @author Joachim Doerr
     */
    public static function findValuesByInput($input, $type = 'textile')
    {
        $values = array();
        $classes = MarkitupProfiles::getProfilesClassNames($type);

        // mform way
        if (strpos(strtolower($input), 'mform') !== false) {

            $pattern = '/(.*?(\baddTextAreaField\b))(.*?((\b' . implode('\b)|(\b', $classes) . '\b)).*)/m';
            preg_match_all($pattern, $input, $matches, PREG_SET_ORDER, 0);

            if ($matches && sizeof($matches) > 0) {

                foreach ($matches as $match) {

                    $v = explode('->', $match[0]);
                    $str = '';
                    eval("{$v[0]} = new MForm(); {$match[0]} \$str = {$v[0]}->show();");

                    preg_match('/name="(.*?)"/m', $str, $mt);

                    if ($mt && sizeof($mt) > 0) {
                        $values[] = $mt[1];
                    }
                }
            }
        } else {

            $pattern = '/(.*?((\b' . implode('\b)|(\b', $classes) . '\b)).*)/m';
            preg_match_all($pattern, $input, $matches, PREG_SET_ORDER, 0);

            if ($matches && sizeof($matches) > 0) {

                foreach ($matches as $match) {

                    preg_match('/name="(.*?)"/m', $match[0], $mt);

                    if ($mt && sizeof($mt) > 0) {
                        $values[] = $mt[1];
                    }
                }
            }
        }

        return $values;
    }
}