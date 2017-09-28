<?php

use yii\data\Pagination;
use yii\data\Sort;
use Codeception\Util\Stub;
use oteixido\yii2\rest\RestDataProvider;
use oteixido\yii2\rest\RestActiveQuery;

class RestDataProviderMockObject {
    public $id;
    public $name;

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public static function primaryKey() {
        return [ 'id' ];
    }
}

class RestDataProviderMock extends RestDataProvider
{
    public $sort = false;
    public $pagination = false;

    public function getSort()
    {
        return $this->sort;
    }

    public function getPagination()
    {
        return $this->pagination;
    }

    public function prepareModels()
    {
        return parent::prepareModels();
    }

    public function prepareKeys($models)
    {
        return parent::prepareKeys($models);
    }

    public function prepareTotalCount()
    {
        return parent::prepareTotalCount();
    }
}


class RestDataProviderTest extends \Codeception\Test\Unit
{
    public $RestDataProvider;

    protected function _before()
    {
        $this->restDataProvider = new RestDataProviderMock();
    }

    public function testPrepareModelsWithoutPagination()
    {
        $models = $this->_createModels();
        $this->restDataProvider->query = Stub::makeEmpty(restActiveQuery::className(), [
            'limit' => Stub::never(),
            'offset' => Stub::never(),
            'orderBy' => Stub::never(),
            'all' => $models,
            'count' => count($models),
        ]);
        $this->tester->assertEquals($models, $this->restDataProvider->prepareModels());
        $this->tester->assertEquals(count($models), $this->restDataProvider->prepareTotalCount());
    }

    public function testPrepareModelsEmptyWithPagination()
    {
        $models = $this->_createModels();
        $this->restDataProvider->query = Stub::makeEmpty(restActiveQuery::className(), [
            'limit' => Stub::atLeastOnce(function($limit) {
                $this->tester->assertEquals(10, $limit);
                return $this;
            }),
            'offset' => Stub::atLeastOnce(function($offset) {
                $this->tester->assertEquals(5, $offset);
                return $this;
            }),
            'orderBy' => Stub::never(),
            'all' => $models,
            'count' => count($models),
        ]);
        $this->restDataProvider->pagination = Stub::makeEmpty(Pagination::className(), [
            'getPageSize' => 10,
            'getLimit' => 10,
            'getOffset' => 5,
        ], $this);
        $this->tester->assertEquals($models, $this->restDataProvider->prepareModels());
        $this->tester->assertEquals(count($models), $this->restDataProvider->prepareTotalCount());
    }

    public function testPrepareModelsWithNoResultsDuePagination()
    {
        $models = $this->_createModels();
        $this->restDataProvider->query = Stub::makeEmpty(restActiveQuery::className(), [
            'limit' => Stub::atLeastOnce(function($limit) {
                $this->tester->assertEquals(10, $limit);
                return $this;
            }),
            'offset' => Stub::atLeastOnce(function($offset) {
                static $first = true;
                $this->tester->assertEquals($first ? 5 : -1, $offset);
                $first = false;
                return $this;
            }),
            'orderBy' => Stub::never(),
            'all' => function () use ($models) {
                static $first = true;
                $result = $first ? [] : $models;
                $first = false;
                return $result;
            },
            'count' => count($models),
        ]);
        $this->restDataProvider->pagination = Stub::makeEmpty(Pagination::className(), [
            'getPageSize' => 10,
            'getLimit' => 10,
            'getOffset' => 5,
        ], $this);
        $this->tester->assertEquals($models, $this->restDataProvider->prepareModels());
        $this->tester->assertEquals(count($models), $this->restDataProvider->prepareTotalCount());
    }

    public function testPrepareModelsWithSort()
    {
        $models = $this->_createModels();
        $this->restDataProvider->query = Stub::makeEmpty(restActiveQuery::className(), [
            'limit' => Stub::never(),
            'offset' => Stub::never(),
            'orderBy' => Stub::atLeastOnce(function($order) {
                $this->tester->assertEquals([ 'id' => 'asc' ], $order);
                return $this;
            }),
            'all' => $models,
            'count' => count($models),
        ]);
        $this->restDataProvider->sort = Stub::makeEmpty(Sort::className(), [
            'getOrders' => [ 'id' => 'asc' ]
        ], $this);
        $this->tester->assertEquals($models, $this->restDataProvider->prepareModels());
        $this->tester->assertEquals(count($models), $this->restDataProvider->prepareTotalCount());
    }

    public function testPrepareKeys()
    {
        $models = $this->_createModels();
        $keys = array_map(function($o) { return $o->id; }, $models);
        $this->tester->assertEquals($keys, $this->restDataProvider->prepareKeys($models));
    }

    private function _createModels($count = 1, $prefix = 'name-')
    {
        $models = [];
        for ($i=1; $i<=$count; $i++) {
            $models[] = new RestDataProviderMockObject($i, $prefix.$i);
        }
        return $models;
    }
}
