<?php

namespace oteixido\yii2\rest;

use yii\data\BaseDataProvider;
use yii\data\DataProviderInterface;
use Yii;

/**
 * RestDataProvider implements a data provider based on a HTTP REST service.
 *
 * RestDataProvider will provide the data after sorting and/or pagination.
 * You may configure the [[sort]] and [[pagination]] properties to
 * customize the sorting and pagination behaviors.
 *
 * RestDataProvider may be used in the following way:
 *
 * ```php
 * $provider = new RestDataProvider([
 *     'query' => $query,
 *     'sort' => [
 *         'attributes' => ['username', 'description'],
 *     ],
 *     'pagination' => [
 *         'pageSize' => 10,
 *     ],
 * ]);
 * ```
 *
 * @author Oriol Teixidó <oriol.teixido@gmail.com>
 */
class RestDataProvider extends BaseDataProvider implements DataProviderInterface
{
    /**
     * @var RestQueryInterface the query that is used to fetch data models.
     */
    public $query = null;

    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        if (($sort = $this->getSort()) !== false) {
            $this->query->orderBy($sort->getOrders());
        }

        if (($pagination = $this->getPagination()) === false) {
            return $this->query->all();
        }

        $pagination->validatePage = false;
        if ($pagination->getPageSize() > 0) {
            $this->query->limit($pagination->getLimit());
            $this->query->offset($pagination->getOffset());
        }
        $models = $this->query->all();

        if ($this->query->count() > 0 && count($models) == 0) {
            $this->query->offset(-1);
            $models = $this->query->all();
        }
        $pagination->totalCount = $this->prepareTotalCount();
        return $models;
    }

    /**
     * @inheritdoc
     */
    protected function prepareKeys($models)
    {
        if (count($models) == 0) {
            return [];
        }
        $primaryKey = $models[0]::primaryKey();
        if ($primaryKey == null || count($primaryKey) != 1) {
            return array_keys($models);
        }
        $key = $primaryKey[0];
        $keys = [];
        foreach ($models as $model) {
            $keys[] = $model->$key;
        }
        return $keys;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        return $this->query->count();
    }
}
