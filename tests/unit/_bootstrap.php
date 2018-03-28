<?php
class StubModel extends \yii\base\Model
{
    private static $baseUri;
    public static $httpClient;
    public $id;
    public $name;

    public static function primaryKey()
    {
        return [ 'id' ];
    }

    public static function getListUri()
    {
        return self::$baseUri;
    }

    public static function setBaseUri($baseUri)
    {
        self::$baseUri = $baseUri;
    }

    public static function getDb()
    {
        return self::$httpClient;
    }

    public static function populateRecord($model, $elem)
    {
        $model->id = $elem['id'];
        $model->name = $elem['name'];
    }
}
