<?php
namespace oteixido\rest;

use yii\web\ServerErrorHttpException;
use Yii;

use oteixido\rest\ActiveQuery;
use oteixido\rest\QueryInterface;
use oteixido\rest\http\HttpResponse;
use oteixido\rest\helpers\UrlHelper;

abstract class ActiveRecord extends \yii\db\BaseActiveRecord
{
    /**
     * Returns the base URI for this model (ex. https://localhost.localdomain/api/v1/posts)
     * @return string
     */
    public abstract static function getBaseUri();

    /**
     * Returns the list URI. By default is the same as getBaseUri()
     * @return string
     */
    public function getListUri()
    {
        return static::getBaseUri();
    }

    /**
     * Returns the create URI. By default is the same as getBaseUri()
     * @return string
     */
    public function getCreateUri()
    {
        return static::getBaseUri();
    }

    /**
     * Returns the view URI. By default is the getBaseUri() followed by a slash and the primary key
     * value of the model.
     * @return string
     */
    public function getViewUri()
    {
        return UrlHelper::join([static::getBaseUri(), $this->getPrimaryKey()]);
    }

    /**
     * Returns the update URI. By default is the getBaseUri() followed by a slash and the primary key
     * value of the model.
     * @return string
     */
    public function getUpdateUri()
    {
        return UrlHelper::join([static::getBaseUri(), $this->getPrimaryKey()]);
    }

    /**
     * Returns the delete URI. By default is the getBaseUri() followed by a slash and the primary key
     * value of the model.
     * @return string
     */
    public function getDeleteUri()
    {
        return UrlHelper::join([static::getBaseUri(), $this->getPrimaryKey()]);
    }

    /**
     * Returns the connection used by this AR class.
     * By default, the "httpClient" application component is used as the connection.
     * You may override this method if you want to use a different connection.
     * @return HttpClient the http client used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->httpClient;
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new ActiveQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function updateAll($attributes, $condition = null)
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @inheritdoc
     */
    public static function deleteAll($condition = null)
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function insert($runValidation = true, $attributes = null)
    {
        return $this->insertOrUpdate($insert = true, $runValidation, $attributes);
    }

    /**
     * @inheritdoc
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        return $this->insertOrUpdate($insert = false, $runValidation, $attributeNames);
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        if (!$this->beforeDelete()) {
            return false;
        }
        $response = self::getDb()->delete($this->getDeleteUri());
        if (!in_array($response->getCode(), [HttpResponse::HTTP_OK, HttpResponse::HTTP_NOT_FOUND])) {
            throw new ServerErrorHttpException("Model not deleted due to http code not valid (".$response->getCode().").");
        }
        if ($response->getCode() == HttpResponse::HTTP_NOT_FOUND) {
            Yii::info("Model not deleted due to http code not valid (".$response->getCode().").", __METHOD__);
        }
        $this->afterDelete();
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function populateRecord($record, $row)
    {
        $attributes = $record->attributes();
        foreach ($row as $name => $value) {
            if (in_array($name, $attributes)) {
                $record->$name = $value;
            }
        }
        parent::populateRecord($record, $row);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        $value = parent::__get($name);
        if ($value instanceof RestQueryInterface) {
            $value = $value->findFor($name, $this);
            $this->populateRelation($name, $value);
            return $value;
        }
        return $value;
    }

    private function insertOrUpdate($insert, $runValidation = true, $attributes = null)
    {
        $message = $insert ? 'inserted' : 'updated';
        $code = $insert ? HttpResponse::HTTP_CREATED : HttpResponse::HTTP_OK;

        if ($runValidation && !$this->validate($attributes)) {
            Yii::info("Model not $message due to validation error.", __METHOD__);
            return false;
        }
        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        $response = $insert ?
            self::getDb()->post($this->getCreateUri(), $values) :
            self::getDb()->put($this->getUpdateUri(), $values);

        if ($response->getCode() != $code) {
            throw new ServerErrorHttpException("Model not $message due to http code not valid (".$response->getCode().").");
        }
        $record = json_decode($response->getContent(), true);
        self::populateRecord($this, $record);
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);
        return true;
    }
}
