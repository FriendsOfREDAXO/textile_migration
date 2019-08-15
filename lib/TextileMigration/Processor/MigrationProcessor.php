<?php
/**
 * User: joachimdoerr
 * Date: 2019-06-16
 * Time: 18:34
 */

namespace TextileMigration\Processor;


use markitup;
use rex;
use rex_sql;
use TextileMigration\Finder\SlicesFinder;
use TextileMigration\Replacer\SliceValueReplacer;

class MigrationProcessor
{
    const DEFAULT_TYPE = 'textile_r4';

    /**
     * @param int $size
     * @param int $step
     * @return array
     * @author Joachim Doerr
     * @throws \rex_sql_exception
     */
    public function migrateSliceAuto($size = 1, $step = 0)
    {
        // find slices and values for rex_article by definition
        $result = SlicesFinder::findMarkitupSlices($step, $size, 'textile');
        return $this->executeSlicesMigrate($result, $size, $step);
    }

    /**
     * @param $definition
     * @param int $size
     * @param int $step
     * @return array
     * @author Joachim Doerr
     * @throws \rex_sql_exception
     */
    public function migrateSliceDefinition($definition, $size = 1, $step = 0)
    {
        // find slices and values for rex_article by definition
        $result = SlicesFinder::findSlicesByDefinition($definition, $step, $size, self::DEFAULT_TYPE);
        return $this->executeSlicesMigrate($result, $size, $step);
    }

    /**
     * @param array $result
     * @param $size
     * @param $step
     * @return array
     * @throws \rex_sql_exception
     * @author Joachim Doerr
     */
    private function executeSlicesMigrate(array $result, $size, $step)
    {
        if (count($result) <= 0) {
            return array(
                'step' => 1,
                'size' => 0,
                'count' => 0,
                'steps' => 0,
                'content' => 'something is wrong'
            );
        }

        $slices = $result['slices'];
        $count = $result['count'];

        // replacing
        $slices = SliceValueReplacer::replaceSlicesValues($slices);

        // save slices
        $content = $this->saveSlices($slices);

        return array(
            'step' => $step,
            'size' => $size,
            'count' => $count,
            'steps' => ceil($count / $size),
            'content' => implode("\n", $content)
        );
    }

    /**
     * @param $definition
     * @param int $size
     * @param int $step
     * @return array
     * @author Joachim Doerr
     * @throws \rex_sql_exception
     */
    public function migrateTableDefinition($definition, $size = 1, $step = 0)
    {
        $count = 0;
        $content = array();

        if (is_array($definition) && sizeof($definition) > 0 && isset($definition['tables']) && is_array($definition['tables']) && sizeof($definition['tables']) > 0) {
            foreach ($definition['tables'] as $item) {
                $table = $item['table'];
                $select = array();
                if (isset($item['columns']) && is_array($item['columns']) && sizeof($item['columns']) > 0) {
                    foreach ($item['columns'] as $column) {
                        $select[] = $column['column'];
                        #if (isset($column['mblock_keys'])) {
                            // later
                        #}
                    }
                }

                $limit = $size;

                if ($limit > 0) {
                    if (!is_null($step) && is_int($step)) {
                        $limit = ' LIMIT ' . ($step * $limit) . ', ' . $limit;
                    } else {
                        $limit = ' LIMIT ' . $limit;
                    }
                }

                $limit = ($limit === 0) ? '' : $limit;
                $select = implode(', ', $select);
                $id = 'id';

                $sql = rex_sql::factory();
                $result = $sql->getArray("select $id, $select from $table $limit");
                $count = $sql->getArray("select count(*) as cun from $table");
                $count = $count[0]['cun'];

                if (is_array($result) && sizeof($result) > 0) {
                    foreach ($result as $key => $columns) {

                        $sql = rex_sql::factory();
                        $sql->setTable($table);
                        $sql->setWhere($id.'=:id', ['id' => $columns['id']]);

                        foreach ($columns as $cKey => $value) {
                            if ($cKey != $id) {
                                $value = SliceValueReplacer::parse('textile', $value);
                                $result[$key][$cKey] = $value;
                                $sql->setValue($cKey, $value);
                                $content[] = "table $table dataset $id $key column $cKey";
                            }
                        }

                        $sql->update();
                    }
                }
            }
        }

        return array(
            'step' => $step,
            'size' => $size,
            'count' => $count,
            'steps' => ceil($count / $size),
            'content' => implode("\n", $content)
        );
    }

    /**
     * @param array $slices
     * @return array
     * @author Joachim Doerr
     * @throws \rex_sql_exception
     */
    private function saveSlices(array $slices)
    {
        $content = array();

        if (is_array($slices) && sizeof($slices) > 0) {
            foreach ($slices as $moduleSlice) {
                if (is_array($moduleSlice['slices']) && sizeof($moduleSlice['slices']) > 0) {
                    foreach ($moduleSlice['slices'] as $slice) {
                        $sql = rex_sql::factory();
                        $sql->setTable(rex::getTablePrefix() . "article_slice");
                        $sql->setWhere('id=:id', ['id' => $slice['id']]);
                        for ($i = 1; $i <= 20; $i++) {
                            $sql->setValue("value$i", $slice["value$i"]);
                        }
                        $content[] = "article {$slice['article_id']} module {$slice['module_id']} slice {$slice['id']}";
                        $sql->update();
                    }
                }
            }
        }

        return $content;
    }
}