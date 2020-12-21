<?php

namespace letist\i18n;

use Yii;
use yii\i18n\PhpMessageSource;
use yii\log\Logger;

class I18N extends \yii\i18n\I18N {

    public $autoGenerate = false;

    public function init()
    {
        parent::init();

    }

    public function translate($category, $message, $params, $language)
    {

        $result = parent::translate($category, $message, $params, $language);

        if($this->autoGenerate){
            $this->checkTranslate($category, $message, $language);
        }

        return $result;
    }

    public function checkTranslate($category, $message, $language){
        /** @var $messageSource PhpMessageSource */
        $messageSource = $this->getMessageSource($category);
        if(!($messageSource instanceof PhpMessageSource)){
            return;
        }

        $translation = $messageSource->translate($category, $message, $language);
        if ($translation === false) {
            $basePath = $messageSource->basePath;
            $fileMap = $messageSource->fileMap;
            if( isset($fileMap[$category]) ){
                $file = $fileMap[$category];
            } else {
                $file = explode("/", $category)[0] . ".php";
            }

            $baseDir = Yii::getAlias($basePath);
            $langDir = $baseDir.DIRECTORY_SEPARATOR.$language;
            $fileSrc = $langDir.DIRECTORY_SEPARATOR.$file;

            if( ! is_dir($baseDir) ) {
                if(!mkdir($baseDir, 0777)) {
                    Yii::getLogger()->log("letist/i18n, can`t create base directory {$basePath}!", Logger::LEVEL_WARNING);
                    return;
                }
            }
            if( ! is_dir($langDir) ) {
                if(!mkdir($langDir, 0777)) {
                    Yii::getLogger()->log("letist/i18n, can`t create language directory {$basePath}/{$language}!", Logger::LEVEL_WARNING);
                    return;
                }
            }


            $message = str_replace('"', '\\"', $message);

            if( !file_exists($fileSrc) ){
                file_put_contents($fileSrc, "<? \nreturn [\n];");
            }

            $translations = file($fileSrc, FILE_SKIP_EMPTY_LINES);
            foreach($translations as $line){
                if( strpos($line, $message) !== false){
                    return;
                }
            }

            $size = count($translations);
            if( strpos($translations[$size-1], "];") !== false){
                $translations[$size-1] = '     "'.$message.'" => "'.$message.'",'."\n";
                $translations[$size] = '];';
                file_put_contents( $fileSrc, $translations );
            } else {
                Yii::getLogger()->log("letist/i18n, file {$file} - damaged!", Logger::LEVEL_WARNING);
            }
        }
    }

} 
