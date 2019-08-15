<?php

namespace TextileMigration\Finder;


use rex;

class ModuleFinder
{
    /**
     * @return array
     * @author Joachim Doerr
     */
    public static function getInstalledModules()
    {
        $sql = \rex_sql::factory();
        $modules = array();
        try {
            $modules = $sql->getArray("SELECT m.*, COUNT(s.module_id) as occurrence
                    FROM " . rex::getTablePrefix() . "module as m
                    LEFT JOIN " . rex::getTablePrefix() . "article_slice as s
                    ON (s.module_id=m.id)
                    GROUP BY m.id ORDER by occurrence DESC");
        } catch (\rex_sql_exception $e) {
            \rex_logger::logException($e);
        }

        if (is_array($modules) && sizeof($modules) > 0) {
            $new = array();
            foreach ($modules as $module) {
                $new[$module['id']] = $module;
            }
            $modules = $new;
        }
        return $modules;
    }

    /**
     * @param null $id
     * @return array
     * @author Joachim Doerr
     */
    public static function getInstalledModule($id = null)
    {
        if (!is_null($id)) {
            $sql = \rex_sql::factory();
            try {
                return $sql->getArray("SELECT m.*, COUNT(s.module_id) as occurrence
                    FROM " . rex::getTablePrefix() . "module as m
                    LEFT JOIN " . rex::getTablePrefix() . "article_slice as s
                    ON (s.module_id=m.id)
                    WHERE m.id = '" . $id . "'
                    GROUP BY m.id ORDER by occurrence DESC");
            } catch (\rex_sql_exception $e) {
                \rex_logger::logException($e);
                return array();
            }
        }
    }

    /**
     * @param null $type
     * @return array
     * @author Joachim Doerr
     */
    public static function getMarkitupModules($type = null)
    {
        $markitupModules = array('markdown'=>array(),'textile'=>array());

        if (MarkitupProfiles::isMarkitUpExist()) {

            $classes = MarkitupProfiles::getProfilesClassNames();
            $modules = ModuleFinder::getInstalledModules();

            if (sizeof($modules) > 0) {
                foreach ($modules as $key => $module) {

                    foreach (array('markdown', 'textile') as $type) {
                        if (isset($classes[$type])) {
                            foreach ($classes[$type] as $class) {

                                if (strpos($module['input'], $class) !== false) {
                                    $markitupModules[$type][$key] = $module;
                                    break;
                                }
                            }
                        }

                        // TODO find by type
                        # if (!isset($markitupModules[$key]) && strpos($module['output'], 'markitup::parseOutput') !== false) {
                            // try to find in output
                        #    $markitupModules[$key] = $module;
                        #}
                    }
                }
            }
        }

        if (is_null($type)) {
            return $markitupModules;
        }

        if (isset($markitupModules[$type])) {
            return $markitupModules[$type];
        } else {
            // TODO error msg.
            return array();
        }
    }
}