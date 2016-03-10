<?php

namespace InfyOm\Generator\Utils;

use Illuminate\Support\Str;
use RuntimeException;

class GeneratorFieldsInputUtil
{
    public static function validateFieldsFile($fields)
    {
        $fieldsArr = [];

        foreach ($fields as $field) {
            if (!self::validateFieldInput($field['fieldInput'])) {
                throw new RuntimeException('Invalid Input '.$field['fieldInput']);
            }

            if (isset($field['htmlType'])) {
                $htmlType = $field['htmlType'];
            } else {
                $htmlType = 'text';
            }

            if (isset($field['update_validations'])) {
                $update_validations = $field['update_validations'];
            } else {
                $update_validations = '';
            }

            if (isset($field['create_validations'])) {
                $create_validations = $field['create_validations'];
            } else {
                $create_validations = '';
            }

            if (isset($field['sample'])) {
                $sample = $field['sample'];
            } else {
                $sample = '';
            }

            if (isset($field['relation'])) {
                $relation = $field['relation'];
            } else {
                $relation = '';
            }

            if (isset($field['searchable'])) {
                $searchable = $field['searchable'];
            } else {
                $searchable = false;
            }

            if (isset($field['searchable'])) {
                $fillable = $field['fillable'];
            } else {
                $fillable = false;
            }

            if (isset($field['primary'])) {
                $primary = $field['primary'];
            } else {
                $primary = false;
            }

            $fieldsArr[] = self::processFieldInput($field['fieldInput'], $htmlType, $create_validations, $update_validations, $sample, $relation, $searchable, $fillable, $primary);
        }

        return $fieldsArr;
    }

    public static function validateFieldInput($fieldInputStr)
    {
        $fieldInputs = explode(':', $fieldInputStr);

        if (count($fieldInputs) < 2) {
            return false;
        }

        return true;
    }

    public static function processFieldInput($fieldInput, $htmlType, $create_validations, $update_validations, $sample = false, $relation = false, $searchable = false, $fillable = true, $primary = false)
    {
        $fieldInputs = explode(':', $fieldInput);

        $fieldName = array_shift($fieldInputs);
        $databaseInputs = implode(':', $fieldInputs);
        $fieldType = explode(',', $fieldInputs[0])[0];

        $htmlTypeInputs = explode(':', $htmlType);
        $htmlType = array_shift($htmlTypeInputs);

        if (count($htmlTypeInputs) > 0) {
            $htmlTypeInputs = array_shift($htmlTypeInputs);
        }

        return [
            'fieldInput'         => $fieldInput,
            'fieldTitle'         => Str::title(str_replace('_', ' ', $fieldName)),
            'fieldType'          => $fieldType,
            'fieldName'          => $fieldName,
            'databaseInputs'     => $databaseInputs,
            'htmlType'           => $htmlType,
            'htmlTypeInputs'     => $htmlTypeInputs,
            'create_validations' => $create_validations,
            'update_validations' => $update_validations,
            'sample'             => $sample,
            'relation'           => $relation,
            'searchable'         => $searchable,
            'fillable'           => $fillable,
            'primary'            => $primary,
        ];
    }

    public static function prepareKeyValueArrayStr($arr)
    {
        $arrStr = '[';
        foreach ($arr as $item) {
            $arrStr .= "'$item' => '$item', ";
        }

        $arrStr = substr($arrStr, 0, strlen($arrStr) - 2);

        $arrStr .= ']';

        return $arrStr;
    }

    public static function prepareValuesArrayStr($arr)
    {
        $arrStr = '[';
        foreach ($arr as $item) {
            $arrStr .= "'$item', ";
        }

        $arrStr = substr($arrStr, 0, strlen($arrStr) - 2);

        $arrStr .= ']';

        return $arrStr;
    }
}
