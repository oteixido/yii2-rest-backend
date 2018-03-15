<?php
class Model extends \yii\base\Model
{
    public static $httpClient;
    public $id;
    public $name;

    public static function primaryKey()
    {
        return [ 'id' ];
    }

    static public function getDb()
    {
        return self::$httpClient;
    }

    public static function populateRecord($model, $elem)
    {
        $model->id = $elem['id'];
        $model->name = $elem['name'];
    }
}
