<?php
class ModelFactory {
    public static function create($modelClass, $data) {
        $model = new $modelClass();
        foreach ($data as $key => $value) {
            if (property_exists($model, $key)) {
                $model->$key = $value;
            }
        }
        return $model;
    }
}