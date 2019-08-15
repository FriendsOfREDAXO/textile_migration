<?php

if (rex::isBackend() && rex::getUser() && rex_get('page') == 'textile_migration/index') {
    rex_view::addCssFile($this->getAssetsUrl('textile_migration.css'));
    rex_view::addJSFile($this->getAssetsUrl('textile_migration.js'));

    require $this->getPath('/vendor/autoload.php');
    require $this->getPath('/vendor/classTextile.php');
}
