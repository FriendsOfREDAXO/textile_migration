<?php

namespace TextileMigration\Finder;


use rex;

class MarkitupProfiles
{
    /**
     * @return bool
     * @author Joachim Doerr
     */
    public static function isMarkitUpExist()
    {
        return \rex_addon::exists('markitup');
    }

    /**
     * @param string $type
     * @return array
     * @author Joachim Doerr
     */
    public static function getProfilesClassNames($type = null)
    {
        try {
            $result = \rex_sql::factory()
                ->getArray("SELECT CONCAT('markitupEditor-',`name`) as `class`, type FROM `" . rex::getTablePrefix() . "markitup_profiles` ORDER BY `name` ASC");

            $arr = array();

            foreach ($result as $value) {
                if (!isset($arr[$value['type']])) {
                    $arr[$value['type']] = array();
                }
                $arr[$value['type']][] = $value['class'];
            }

            if (is_null($type)) {
                return $arr;
            }

            if (isset($arr[$type])) {
                return $arr[$type];
            } else {
                // TODO error msg.
                return array();
            }

        } catch (\rex_sql_exception $e) {
            \rex_logger::logException($e);
            return array();
        }
    }
}