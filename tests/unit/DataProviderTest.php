<?php
use yii\data\Pagination;
use yii\data\Sort;
use Codeception\Util\Stub;

use oteixido\rest\DataProvider;
use oteixido\rest\ActiveQuery;

class Model extends \yii\base\Model
{
    public $id;
    public $name;
    public static function primaryKey()
    {
        return [ 'id' ];
    }
}

class RestDataProviderTest extends \Codeception\Test\Unit
{
    public $dataProvider;

    protected function _before()
    {
        $this->dataProvider = Stub::make(DataProvider::className(), ['sort' => false, 'pagination' => false]);
    }

    public function testPrepareModelsWithoutPagination()
    {
        $models = $this->_createModels();
        $this->dataProvider->query = Stub::makeEmpty(ActiveQuery::className(), [
            'limit' => \Codeception\Stub\Expected::never(),
            'offset' => \Codeception\Stub\Expected::never(),
            'orderBy' => \Codeception\Stub\Expected::never(),
            'all' => $models,
            'count' => count($models),
        ]);
        $this->tester->assertEquals($models, $this->tester->invokeMethod($this->dataProvider, 'prepareModels'));
        $this->tester->assertEquals(count($models), $this->tester->invokeMethod($this->dataProvider, 'prepareTotalCount'));
    }

    public function testPrepareModelsEmptyWithPagination()
    {
        $models = $this->_createModels();
        $this->dataProvider->query = Stub::makeEmpty(ActiveQuery::className(), [
            'limit' => \Codeception\Stub\Expected::atLeastOnce(function($limit) {
                $this->tester->assertEquals(10, $limit);
                return $this;
            }),
            'offset' => \Codeception\Stub\Expected::atLeastOnce(function($offset) {
                $this->tester->assertEquals(5, $offset);
                return $this;
            }),
            'orderBy' => \Codeception\Stub\Expected::never(),
            'all' => $models,
            'count' => count($models),
        ]);
        $this->dataProvider->pagination = Stub::makeEmpty(Pagination::className(), [
            'getPageSize' => 10,
            'getLimit' => 10,
            'getOffset' => 5,
        ], $this);
        $this->tester->assertEquals($models, $this->tester->invokeMethod($this->dataProvider, 'prepareModels'));
        $this->tester->assertEquals(count($models), $this->tester->invokeMethod($this->dataProvider, 'prepareTotalCount'));
    }

    public function testPrepareModelsWithNoResultsDuePagination()
    {
        $models = $this->_createModels();
        $this->dataProvider->query = Stub::makeEmpty(ActiveQuery::className(), [
            'limit' => \Codeception\Stub\Expected::atLeastOnce(function($limit) {
                $this->tester->assertEquals(10, $limit);
                return $this;
            }),
            'offset' => \Codeception\Stub\Expected::atLeastOnce(function($offset) {
                static $first = true;
                $this->tester->assertEquals($first ? 5 : -1, $offset);
                $first = false;
                return $this;
            }),
            'orderBy' => \Codeception\Stub\Expected::never(),
            'all' => function () use ($models) {
                static $first = true;
                $result = $first ? [] : $models;
                $first = false;
                return $result;
            },
            'count' => count($models),
        ]);
        $this->dataProvider->pagination = Stub::makeEmpty(Pagination::className(), [
            'getPageSize' => 10,
            'getLimit' => 10,
            'getOffset' => 5,
        ], $this);
        $this->tester->assertEquals($models, $this->tester->invokeMethod($this->dataProvider, 'prepareModels'));
        $this->tester->assertEquals(count($models), $this->tester->invokeMethod($this->dataProvider, 'prepareTotalCount'));
    }

    public function testPrepareModelsWithSort()
    {
        $models = $this->_createModels();
        $this->dataProvider->query = Stub::makeEmpty(ActiveQuery::className(), [
            'limit' => \Codeception\Stub\Expected::never(),
            'offset' => \Codeception\Stub\Expected::never(),
            'orderBy' => \Codeception\Stub\Expected::atLeastOnce(function($order) {
                $this->tester->assertEquals([ 'id' => 'asc' ], $order);
                return $this;
            }),
            'all' => $models,
            'count' => count($models),
        ]);
        $this->dataProvider->sort = Stub::makeEmpty(Sort::className(), [
            'getOrders' => [ 'id' => 'asc' ]
        ], $this);
        $this->tester->assertEquals($models, $this->tester->invokeMethod($this->dataProvider, 'prepareModels'));
        $this->tester->assertEquals(count($models), $this->tester->invokeMethod($this->dataProvider, 'prepareTotalCount'));
    }

    public function testPrepareKeys()
    {
        $models = $this->_createModels();
        $keys = array_map(function($o) { return $o->id; }, $models);
        $this->tester->assertEquals($keys, $this->tester->invokeMethod($this->dataProvider, 'prepareKeys', [$models]));
    }

    private function _createModels($count = 1, $prefix = 'name-')
    {
        $models = [];
        for ($i=1; $i<=$count; $i++) {
            $models[] = new Model(['id' => $i, 'name' => $prefix.$i]);
        }
        return $models;
    }
}
