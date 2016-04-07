<?php

namespace InfyOm\Generator\Generators;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\TableFieldsGenerator;
use InfyOm\Generator\Utils\TemplateUtil;

class ModelGenerator
{
    /** @var  CommandData */
    private $commandData;

    /** @var string */
    private $path;

    public function __construct($commandData)
    {
        $this->commandData = $commandData;
        $this->path = config('infyom.laravel_generator.path.model', app_path('Models/'));
    }

    public function generate()
    {
        $templateData = TemplateUtil::getTemplate('model', 'laravel-generator');

        $templateData = $this->fillTemplate($templateData);

        $fileName = $this->commandData->modelName.'.php';

        FileUtil::createFile($this->path, $fileName, $templateData);

        $this->commandData->commandComment("\nModel created: ");
        $this->commandData->commandInfo($fileName);
    }

    private function fillTemplate($templateData)
    {
        $templateData = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $templateData);

        $templateData = $this->fillSoftDeletes($templateData);

        $fillables = [];

        foreach ($this->commandData->inputFields as $field) {
            if ($field['fillable']) {
                $fillables[] = '"'.$field['fieldName'].'"';
            }
        }

        $templateData = $this->fillDocs($templateData);

        $templateData = $this->fillTimestamps($templateData);

        $templateData = $this->fillRelations($templateData);

        if ($this->commandData->getOption('primary')) {
            $primary = str_repeat(' ', 4)."protected \$primaryKey = '".$this->commandData->getOption('primary')."';\n";
        } else {
            $primary = '';
        }

        $templateData = str_replace('$PRIMARY$', $primary, $templateData);

        $templateData = str_replace('$FIELDS$', implode(',' . PHP_EOL.str_repeat(' ', 8), $fillables), $templateData);

        $templateData = str_replace('$CREATE_RULES$', implode(',' . PHP_EOL.str_repeat(' ', 8), $this->generateCreateRules()), $templateData);

        $templateData = str_replace('$UPDATE_RULES$', implode(',' . PHP_EOL.str_repeat(' ', 8), $this->generateUpdateRules()), $templateData);

        $templateData = str_replace('$CAST$', implode(',' . PHP_EOL.str_repeat(' ', 8), $this->generateCasts()), $templateData);

        $templateData = str_replace('$SAMPLE_VALUES$', implode(',' . PHP_EOL.str_repeat(' ', 8), $this->generateSampleValues()), $templateData);

        return $templateData;
    }

    private function fillSoftDeletes($templateData)
    {
        if (!$this->commandData->getOption('softDelete')) {
            $templateData = str_replace('$SOFT_DELETE_IMPORT$', '', $templateData);
            $templateData = str_replace('$SOFT_DELETE$', '', $templateData);
            $templateData = str_replace('$SOFT_DELETE_DATES$', '', $templateData);
        } else {
            $templateData = str_replace('$SOFT_DELETE_IMPORT$', "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n",
                $templateData);
            $templateData = str_replace('$SOFT_DELETE$', str_repeat(' ', 4)."use SoftDeletes;\n", $templateData);
            $templateData = str_replace('$SOFT_DELETE_DATES$', PHP_EOL.str_repeat(' ', 4)."protected \$dates = ['deleted_at'];\n",
                $templateData);
        }

        return $templateData;
    }

    private function fillDocs($templateData)
    {
        if ($this->commandData->getAddOn('swagger')) {
            $templateData = $this->generateSwagger($templateData);
        } else {
            $docsTemplate = TemplateUtil::getTemplate('docs.model', 'laravel-generator');
            $docsTemplate = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $docsTemplate);
            $templateData = str_replace('$DOCS$', $docsTemplate, $templateData);
        }

        return $templateData;
    }

    private function fillTimestamps($templateData)
    {
        $timestamps = TableFieldsGenerator::getTimestampFieldNames();

        $replace = '';

        #if ($this->commandData->getOption('fromTable')) {
            if (empty($timestamps)) {
                $replace = "\n    public \$timestamps = false;\n";
            } else {
                list($created_at, $updated_at, $deleted_at) = collect($timestamps)->map(function ($field) {
                    return !empty($field) ? "'$field'" : 'null';
                });

                $replace .= "\n    const CREATED_AT = $created_at;";
                $replace .= "\n    const UPDATED_AT = $updated_at;\n";
                $replace .= "\n    const DELETED_AT = $deleted_at;\n";
            }
        #}

        return str_replace('$TIMESTAMPS$', $replace, $templateData);
    }

    private function fillRelations($templateData)
    {
        $relations = [];

        foreach ($this->commandData->inputFields as $field) {
            if ( ! empty($field['relation'])) {
                /**
                 * Example: "relation": "userStatus,belongsTo,UserStatus,UserStatusId,Id"
                 *
                 * 0: relation name
                 * 1: relation type
                 * 2: related model name
                 * 3: foreign key
                 * 4: referenced key
                 */

                $relation_parts = explode(',', $field['relation']);

                if (count($relation_parts) != 5) {
                    dd('Relation of ' . $field['fieldName'] . ' is incorrect!');
                }

                $models_namespace = config('infyom.laravel_generator.namespace.model', 'App\Models') . '\\';

$relations[] = <<<RELATION
    public function {$relation_parts[0]}()
    {
        return \$this->{$relation_parts[1]}({$models_namespace}{$relation_parts[2]}::class, '{$relation_parts[3]}', '{$relation_parts[4]}');
    }
RELATION;
            }
        }

        return str_replace('$RELATIONS$', implode(PHP_EOL . PHP_EOL, $relations), $templateData);
    }

    public function generateSwagger($templateData)
    {
        $fieldTypes = SwaggerGenerator::generateTypes($this->commandData->inputFields);

        $template = TemplateUtil::getTemplate('model.model', 'swagger-generator');

        $template = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $template);

        $template = str_replace('$REQUIRED_FIELDS$', implode(', ', $this->generateRequiredFields()), $template);

        $propertyTemplate = TemplateUtil::getTemplate('model.property', 'swagger-generator');

        $properties = SwaggerGenerator::preparePropertyFields($propertyTemplate, $fieldTypes);

        $template = str_replace('$PROPERTIES$', implode(",\n", $properties), $template);

        $templateData = str_replace('$DOCS$', $template, $templateData);

        return $templateData;
    }

    private function generateRequiredFields()
    {
        $requiredFields = [];

        foreach ($this->commandData->inputFields as $field) {
            if (!empty($field['validations'])) {
                if (str_contains($field['validations'], 'required')) {
                    $requiredFields[] = $field['fieldName'];
                }
            }
        }

        return $requiredFields;
    }

    private function generateSampleValues()
    {
        $sample_values = [];

        foreach ($this->commandData->inputFields as $field) {
            if ( ! empty($field['sample']) or isset($field['sample'])) {
                $sample_value = '"'.$field['fieldName'].'" => "'.$field['sample'].'"';
                $sample_values[] = $sample_value;
            }
        }

        return $sample_values;
    }

    private function generateCreateRules()
    {
        $rules = [];

        foreach ($this->commandData->inputFields as $field) {
            if (!empty($field['create_validations'])) {
                $rule = '"'.$field['fieldName'].'" => "'.$field['create_validations'].'"';
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    private function generateUpdateRules()
    {
        $rules = [];

        foreach ($this->commandData->inputFields as $field) {
            if (!empty($field['update_validations'])) {
                $rule = '"'.$field['fieldName'].'" => "'.$field['update_validations'].'"';
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    public function generateCasts()
    {
        $casts = [];

        $timestamps = TableFieldsGenerator::getTimestampFieldNames();

        foreach ($this->commandData->inputFields as $field) {
            if (in_array($field['fieldName'], $timestamps)) {
                continue;
            }

            switch ($field['fieldType']) {
                case 'integer':
                    $rule = '"'.$field['fieldName'].'" => "integer"';
                    break;
                case 'double':
                    $rule = '"'.$field['fieldName'].'" => "double"';
                    break;
                case 'float':
                    $rule = '"'.$field['fieldName'].'" => "float"';
                    break;
                case 'boolean':
                    $rule = '"'.$field['fieldName'].'" => "boolean"';
                    break;
                case 'dateTime':
                case 'dateTimeTz':
                    $rule = '"'.$field['fieldName'].'" => "datetime"';
                    break;
                case 'date':
                    $rule = '"'.$field['fieldName'].'" => "date"';
                    break;
                case 'enum':
                case 'string':
                case 'char':
                case 'text':
                    $rule = '"'.$field['fieldName'].'" => "string"';
                    break;
                default:
                    $rule = '';
                    break;
            }

            if (!empty($rule)) {
                $casts[] = $rule;
            }
        }

        return $casts;
    }
}
