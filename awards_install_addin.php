<?php
/**
 ***********************************************************************************************
 * Addin installation routine for the Admidio plugin Awards
 *
 * https://github.com/sistlind/awards
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Exception;

try {
    require_once (__DIR__ . '/../../system/common.php');

    // only administrators are allowed to start this module
    if (! $gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // für die Anzeige von Awards-Daten im Profil eines Mitglieds müssen original Admidio-Dateien geändert werden
   
    $zeilenumbruch = "\r\n";
    
    // ADMIDIO_URL auch möglich, aber dann wird 'allow_url_open' (PHP.ini) benötigt
    $templateFile = ADMIDIO_PATH . FOLDER_THEMES . '/simple/templates/modules/profile.view';
    try {
        if (! file_exists($templateFile . '_awards_save.tpl')) {
            //zur Sicherheit wird eine Kopie der Originaldatei erzeugt (bei der Deinstallation wird sie wieder gelöscht)
            FileSystemUtils::copyFile($templateFile . '.tpl', $templateFile . '_awards_save.tpl');

            // Template-Datei einlesen
            $templateString = file_get_contents($templateFile . '.tpl');

            // diese Texte in die profile.view.tpl einfügen ($needle => $subst)
            $substArray = array(
                '{if $showRelations}' => '{include file="../../../..' . FOLDER_PLUGINS . '/awards/templates/profile.view.include.button.plugin.awards.tpl"}'.$zeilenumbruch,
                '<!-- User Relations Tab -->' => '{include file="../../../..' . FOLDER_PLUGINS . '/awards/templates/profile.view.include.awards.tab.plugin.awards.tpl"}'.$zeilenumbruch,
                '<!-- User Relations Accordion -->' => '{include file="../../../..' . FOLDER_PLUGINS . '/awards/templates/profile.view.include.awards.accordion.plugin.awards.tpl"}'.$zeilenumbruch
            );
            foreach ($substArray as $needle => $subst) {
                $templateString = substr_replace($templateString, $subst, strpos($templateString, $needle), 0);
            }
            
            // Template-Datei wieder schreiben
            file_put_contents($templateFile . '.tpl', $templateString);
        } else {
            // es gibt bereits eine save-Datei, d.h. die Änderungen sind bereits eingetragen; irgendein 'Superchecker' führt die Install-Routine ein zweites mal aus
        }
    } catch (\RuntimeException $exception) {
        $gMessage->show($exception->getMessage());
        // => EXIT
    } catch (\UnexpectedValueException $exception) {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }

    $profileFile = ADMIDIO_PATH . FOLDER_MODULES . '/profile/profile';
    try {
        if (! file_exists($profileFile . '_awards_save.php')) {
            //zur Sicherheit wird eine Kopie der Originaldatei erzeugt (bei der Deinstallation wird sie wieder gelöscht)
            FileSystemUtils::copyFile($profileFile . '.php', $profileFile . '_awards_save.php');

            // PHP-Datei einlesen
            $profileString = file_get_contents($profileFile . '.php');

            // diesen Text in die profile.view.tpl einfügen
            $needle = '$page->show();';
            $subst = "require_once(ADMIDIO_PATH . FOLDER_PLUGINS .'/awards/awards_profile_addin.php');";
            $profileString = substr_replace($profileString, $subst . $zeilenumbruch, strpos($profileString, $needle), 0);
            
            // PHP-Datei wieder schreiben
            file_put_contents($profileFile . '.php', $profileString);
        } else {
            // es gibt bereits eine save-Datei, d.h. die Änderungen sind bereits eingetragen; irgendein 'Superchecker' führt die Install-Routine ein zweites mal aus
        }
    } catch (\RuntimeException $exception) {
        $gMessage->show($exception->getMessage());
        // => EXIT
    } catch (\UnexpectedValueException $exception) {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }

    $gMessage->show($gL10n->get('AWA_SUCCESS'));
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}


