<?php

namespace InfyOm\Generator\Generators;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\TemplateUtil;

class ModelAbstractGenerator
{
    /** @var  CommandData */
    private $commandData;

    /** @var string */
    private $path;

    public function __construct($commandData)
    {
        $this->commandData = $commandData;
        $this->path = app_path('ModelAbstract/');
    }

    public function generate()
    {
        $templateData = TemplateUtil::getTemplate('modelabstract', 'laravel-generator');

        $templateData = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $templateData);

        $searchables = [];

        foreach ($this->commandData->inputFields as $field) {
            if ($field['searchable']) {
                $searchables[] = '"'.$field['fieldName'].'"';
            }
        }

        $templateData = str_replace('$FIELDS$', implode(','.PHP_EOL.str_repeat(' ', 8), $searchables), $templateData);

        $fileName = $this->commandData->modelName.'.php';

        FileUtil::createFile($this->path, $fileName, $templateData);

        $this->commandData->commandComment("\nModel abstract created: ");
        $this->commandData->commandInfo($fileName);
    }
}
