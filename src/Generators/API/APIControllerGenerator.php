<?php

namespace InfyOm\Generator\Generators\API;

use Illuminate\Support\Facades\App;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\TemplateUtil;

class APIControllerGenerator
{
    /** @var  CommandData */
    private $commandData;

    /** @var string */
    private $path;

    public function __construct($commandData)
    {
        $this->commandData = $commandData;
        $this->path = config('infyom.laravel_generator.path.api_controller', app_path('Http/Controllers/API/'));
    }

    public function generate()
    {
        $templateData = TemplateUtil::getTemplate('api.controller.api_controller', 'laravel-generator');

        $templateData = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $templateData);
        $templateData = $this->fillDocs($templateData);

        $fileName = $this->commandData->modelName.'APIController.php';

        FileUtil::createFile($this->path, $fileName, $templateData);

        $this->commandData->commandComment("\nAPI Controller created: ");
        $this->commandData->commandInfo($fileName);
    }

    private function fillDocs($templateData)
    {
        $methods = ['controller', 'index', 'store', 'store', 'show', 'update', 'destroy'];

        if ($this->commandData->getAddOn('api_blueprint')) {
            $templatePrefix = 'api.docs.controller';
            $templateType = 'laravel-generator';
        } else if ($this->commandData->getAddOn('swagger')) {
            $templatePrefix = 'controller';
            $templateType = 'swagger-generator';
        } else {
            $templatePrefix = 'api.docs.controller';
            $templateType = 'laravel-generator';
        }

        ###################### API BLUEPRINT START
        $model = '\App\\' . $this->commandData->modelName;

        $model = new $model();

        ################################$SAMPLE_VALUES_JSON_PER_LINE$
        $SAMPLE_VALUES_JSON_PER_LINE = [];
        foreach ($model->sample_values as $field => $sample_value) {
            if ($sample_value == '[relation]') {
                $sample_value = '1';
            }

            $sample_value = (isset($model->casts[$field]) && in_array($model->casts[$field], ['integer', 'int', 'bool', 'boolean']))
                ? "{$sample_value}": "\"{$sample_value}\"";
            $SAMPLE_VALUES_JSON_PER_LINE[] = "\"{$field}\": {$sample_value}";
        }
        $this->commandData->addDynamicVariable('$SAMPLE_VALUES_JSON_PER_LINE$', implode(',' . PHP_EOL . '     *      ', $SAMPLE_VALUES_JSON_PER_LINE));
        ################################$SAMPLE_VALUES_JSON_PER_LINE$

        ################################$CREATE_ATTRIBUTES$
        $CREATE_ATTRIBUTES = [];
        foreach ($model::$createValidatorRules as $field => $validation) {
            $validation_types = explode('|', $validation);

            # $is_required = in_array('required', $validation_types) ? 'required' : 'optional';
            $is_required = in_array('required', $validation_types) ? 'true' : 'false';
            $type = (isset($model->casts[$field]) and in_array($model->casts[$field], ['integer', 'int', 'bool', 'boolean']))
                ? 'number' : 'string';
            # $sample = isset($model->sample_values[$field]) ? " `{$model->sample_values[$field]}`" : '';
            $sample = isset($model->sample_values[$field]) ? $model->sample_values[$field] : '';

            if ($sample == '" `[relation]`"') {
                $sample = '" `1`"';
            } else if ($sample == '[relation]') {
                $sample = '1';
            }

            $CREATE_ATTRIBUTES[] = "@Attribute(\"{$field}\", type=\"{$type}\", description=\"\", sample=\"{$sample}\", required={$is_required}),";
            # $CREATE_ATTRIBUTES[] = "+ {$field}:{$sample} ({$type}, {$is_required})";
        }
        $this->commandData->addDynamicVariable('$CREATE_ATTRIBUTES$', implode(PHP_EOL . '     *          ', $CREATE_ATTRIBUTES));
        ################################$CREATE_ATTRIBUTES$

        ################################$UPDATE_ATTRIBUTES$
        $UPDATE_ATTRIBUTES = [];
        foreach ($model::$updateValidatorRules as $field => $validation) {
            $validation_types = explode('|', $validation);

            # $is_required = in_array('required', $validation_types) ? 'required' : 'optional';
            $is_required = in_array('required', $validation_types) ? 'true' : 'false';
            $type = (isset($model->casts[$field]) and in_array($model->casts[$field], ['integer', 'int', 'bool', 'boolean']))
                ? 'number' : 'string';
            # $sample = isset($model->sample_values[$field]) ? " `{$model->sample_values[$field]}`" : '';
            $sample = isset($model->sample_values[$field]) ? $model->sample_values[$field] : '';

            if ($sample == '" `[relation]`"') {
                $sample = '" `1`"';
            } else if ($sample == '[relation]') {
                $sample = '1';
            }

            $UPDATE_ATTRIBUTES[] = "@Attribute(\"{$field}\", type=\"{$type}\", description=\"\", sample=\"{$sample}\", required={$is_required}),";
            # $UPDATE_ATTRIBUTES[] = "+ {$field}:{$sample} ({$type}, {$is_required})";
        }
        $this->commandData->addDynamicVariable('$UPDATE_ATTRIBUTES$', implode(PHP_EOL . '     *          ', $UPDATE_ATTRIBUTES));
        ################################$UPDATE_ATTRIBUTES$

        foreach ($methods as $method) {
            $key = '$DOC_'.strtoupper($method).'$';
            $docTemplate = TemplateUtil::getTemplate($templatePrefix.'.'.$method, $templateType);

            $docTemplate = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $docTemplate);
            $templateData = str_replace($key, $docTemplate, $templateData);
        }
        ###################### API BLUEPRINT END

        return $templateData;
    }
}
