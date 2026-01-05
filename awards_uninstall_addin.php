<?php
/**
 ***********************************************************************************************
 * Addin uninstallation of the Admidio plugin Awards
 *
 * https://github.com/sistlind/awards
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\FileSystemUtils;

try {
    require_once (__DIR__ . '/../../system/common.php');

    // only administrators are allowed to start this module
    if (! $gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    try {
        // Dateiänderungen in profile.view.tpl und profile.php wieder rückgängig machen

        // ADMIDIO_PATH funktioniert ohne allow_url_open (PHP.ini)
        $templateFile = ADMIDIO_PATH . FOLDER_THEMES . '/simple/templates/modules/profile.view';
        $profileFile = ADMIDIO_PATH . FOLDER_MODULES . '/profile/profile';
        $zeilenumbruch = "\r\n";

        $templateString = file_get_contents($templateFile . '.tpl');

        // diese Texte wurden bei der Installation in die profile.view.tpl eingefügt
        $substArray = array(
            '{include file="../../../..' . FOLDER_PLUGINS . '/awards/templates/profile.view.include.button.plugin.awards.tpl"}' . $zeilenumbruch,
            '{include file="../../../..' . FOLDER_PLUGINS . '/awards/templates/profile.view.include.awards.tab.plugin.awards.tpl"}' . $zeilenumbruch,
            '{include file="../../../..' . FOLDER_PLUGINS . '/awards/templates/profile.view.include.awards.accordion.plugin.awards.tpl"}' . $zeilenumbruch
        );

        // eingefügte Texte durch '' ersetzen
        foreach ($substArray as $subst) {
            $templateString = str_replace($subst, '', $templateString);
        }
        file_put_contents($templateFile . '.tpl', $templateString);

        $profileString = file_get_contents($profileFile . '.php');

        // dieser Text wurde bei der Installation in die profile.view.tpl eingefügt
        $subst = "require_once(ADMIDIO_PATH . FOLDER_PLUGINS .'/awards/awards_profile_addin.php');" . $zeilenumbruch;

        // eingefügten Text durch '' ersetzen
        $profileString = str_replace($subst, '', $profileString);

        file_put_contents($profileFile . '.php', $profileString);

        // die bei der Installation angelegten Sicherungsdateien wieder löschen
        FileSystemUtils::deleteFileIfExists($templateFile . '_awards_save.tpl');
        FileSystemUtils::deleteFileIfExists($profileFile . '_awards_save.php');
    } catch (\RuntimeException $exception) {
        $gMessage->show($exception->getMessage());
        // => EXIT
    } catch (\UnexpectedValueException $exception) {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }
    $gMessage->show($gL10n->get('AWA_SUCCESS'));
    // }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}