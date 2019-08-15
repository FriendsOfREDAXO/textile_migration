<?php
/**
 * Author: Joachim Doerr
 * Date: 2019-04-10
 * Time: 09:12
 */

use TextileMigration\Processor\MigrationProcessor;

$page = rex_request::get('page');
$size = rex_request::get('size', 'int', 1);
$step = rex_request::get('step', 'int', 0);
$count = rex_request::get('count', 'int', 0);
$definition = rex_request::get('definition', 'string', '');

$calls = explode(',', rex_request::get('calls', 'string', 0));

$yaml = '
Example Module:
--------------
modules:
    - id: 122
      values:
        - value: value2
';
$yaml .= '
Example MBlock Module:
---------------------
modules:
    - id: 122
      values: 
        - value: value2
          mblock_keys: 
            - 2.0.text
';
$yaml .= '
Example DB only:
---------------
tables:
    - table: rex_test
      columns:
        - column: textile
        - column: textile2
';

if (rex_request::get('textile_migration_ajax', 'bool', false) == true) {

    if (!empty($definition)) {
        $definition = \Symfony\Component\Yaml\Yaml::parse($definition);
    }

    rex_response::cleanOutputBuffers();

    $processor = new MigrationProcessor();
    $content = array();
    $result = array();

    if (in_array('article_automatic', $calls)) {
        $result = $processor->migrateSliceAuto($size, $step);
    }
    if (in_array('article_definition', $calls)) {
        $result = $processor->migrateSliceDefinition($definition, $size, $step);
    }
    if (in_array('table_definition', $calls)) {
        $result = $processor->migrateTableDefinition($definition, $size, $step);
    }

    if (empty($result)) {
        $result = array(
            'step' => $step,
            'size' => $size,
            'count' => $count,
            'steps' => ceil($count / $size),
            'content' => implode("\n", $content)
        );
    }

    rex_response::sendContent(json_encode($result), 'application/json');
    die;
}

$contentAction = '
    <p>'.rex_i18n::msg('textile_migration_actions').'</p>
    <div class="call_types">
        <label class="radio-inline">
            <input type="radio" name="call" value="article_automatic">'.rex_i18n::msg('textile_migration_action_article_automatic').'  
        </label><br>
        <label class="radio-inline">
            <input type="radio" name="call" value="article_definition">'.rex_i18n::msg('textile_migration_action_article_definition').'  
        </label><br>
        <label class="radio-inline">
            <input type="radio" name="call" value="table_definition">'.rex_i18n::msg('textile_migration_action_table_definition').'
        </label><br>
        <textarea class="action_definition form-control" name="definition" style="width: 100%;" rows="13">'.$definition.'</textarea>
        <textarea class="action_definition help-block" style="width: 100%;" rows="5" readonly>'.$yaml.'</textarea>
    </div>
    <p><a href="#" class="btn btn-warning btn-md textile_migration-action-button">'. rex_i18n::msg('textile_migration_action_button') .'</a></p>';

$contentProcess = '<div class="textile_migration_processbar_empty"><span class="counting">0</span></div>';
$contentProcess .= '<div class="textile_migration_result"><pre class="result" style="height:400px;overflow-x:scroll"></pre></div>';

echo rex_view::title(rex_i18n::msg('textile_migration'));

$fragment = new rex_fragment();
$fragment->setVar('class', 'info textile_migration_action_wrapper', false);
$fragment->setVar('title', rex_i18n::msg('system_textile_migration'));
$fragment->setVar('body', $contentAction, false);
echo $fragment->parse('core/page/section.php');

echo '<div class="textile_migration_process_success_msg">' . rex_view::success(rex_i18n::msg('textile_migration_step_process_successful') . ' <a href="'.rex_url::backendPage($page).'">'.rex_i18n::msg('textile_migration_back').'</a>') . '</div>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'info textile_migration_process_wrapper', false);
$fragment->setVar('title', rex_i18n::msg('textile_migration_action'));
$fragment->setVar('body', $contentProcess, false);
echo $fragment->parse('core/page/section.php');

