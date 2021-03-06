<?php
use Codeception\Util\Stub;

use oteixido\rest\ActiveQuery;
use oteixido\rest\http\HttpClient;
use oteixido\rest\http\HttpResponse;

class ActiveQueryTest extends \Codeception\Test\Unit
{
    public $activeQuery;

    private function createModel($values)
    {
        $model = new StubModel();
        StubModel::populateRecord($model, $values);
        return $model;
    }

    private function setHttpClientModels($httpCode, $models)
    {
        StubModel::$httpClient = Stub::update(StubModel::$httpClient, [
            'get' => function () use ($httpCode, $models) {
                return new HttpResponse($httpCode, json_encode($models), [ 'X-Total-Count' => count($models) ]);
            },
        ]);
    }

    protected function _before()
    {
        StubModel::setBaseUri('http://localhost.localdomain');
        StubModel::$httpClient = Stub::makeEmpty(HttpClient::className());
        $this->activeQuery = new ActiveQuery(StubModel::className());
    }

    public function testAllEmpty()
    {
        $this->setHttpClientModels(200, []);
        $this->tester->assertEquals([], $this->activeQuery->all());
        $this->tester->assertEquals(0, $this->activeQuery->count());
    }

    public function testAllNotEmpty()
    {
        $model1 = $this->createModel(['id' => 'value11', 'name' => 'value12']);
        $model2 = $this->createModel(['id' => 'value21', 'name' => 'value22']);
        $models = [ $model1, $model2 ];
        $this->setHttpClientModels(200, $models);
        $this->tester->assertEquals($models, $this->activeQuery->all());
        $this->tester->assertEquals(count($models), $this->activeQuery->count());
    }

    public function testOneEmpty()
    {
        $this->setHttpClientModels(200, []);
        $this->tester->assertEquals(null, $this->activeQuery->one());
    }

    public function testOneNotEmpty()
    {
        $model1 = $this->createModel(['id' => 'value11', 'name' => 'value12']);
        $model2 = $this->createModel(['id' => 'value21', 'name' => 'value22']);
        $this->setHttpClientModels(200, [ $model1, $model2 ]);

        $this->tester->assertEquals($model1, $this->activeQuery->one());
    }

    public function testExistsEmpty()
    {
        $this->setHttpClientModels(200, []);
        $this->tester->assertFalse($this->activeQuery->exists());
    }

    public function testExists()
    {
        $model1 = $this->createModel(['id' => 'value11', 'name' => 'value12']);
        $model2 = $this->createModel(['id' => 'value21', 'name' => 'value22']);
        $this->setHttpClientModels(200, [ $model1, $model2 ]);

        $this->tester->assertTrue($this->activeQuery->exists());
    }

    public function testIndexBy()
    {
        $model1 = $this->createModel(['id' => 'value11', 'name' => 'value12']);
        $model2 = $this->createModel(['id' => 'value21', 'name' => 'value22']);
        $models = [ $model1, $model2 ];
        $this->setHttpClientModels(200, $models);
        $this->activeQuery->indexBy('id');
        $this->tester->assertEquals([
            'value11' => $model1,
            'value21' => $model2,
        ], $this->activeQuery->all());
    }

    public function testWhereEmpty()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where([]);
        $this->activeQuery->all();
    }

    public function testWhere()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where(['attribute' => 'value']);
        $this->activeQuery->all();
    }

    public function testWhereNull()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute=', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where([ 'attribute' => '' ]);
        $this->activeQuery->all();
    }

    public function testAndWhere()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?id=value1&name=value2&attribute3=value3', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where(['id' => 'value1']);
        $this->activeQuery->andWhere(['name' => 'value2']);
        $this->activeQuery->andWhere(['attribute3' => 'value3']);
        $this->activeQuery->all();
    }

    public function testWhereEquals()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where(['=', 'attribute', 'value']);
        $this->activeQuery->all();
    }

    public function testWhereNotEquals()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute_ne=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where(['!=', 'attribute', 'value']);
        $this->activeQuery->all();
    }

    public function testWhereGreaterThan()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute_gte=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where(['>=', 'attribute', 'value']);
        $this->activeQuery->all();
    }

    public function testWhereLessThan()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute_lte=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where(['<=', 'attribute', 'value']);
        $this->activeQuery->all();
    }

    public function testWhereLike()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute_like=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->where(['like', 'attribute', 'value']);
        $this->activeQuery->all();
    }

    public function testFilterWhere()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?attribute=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->filterWhere(['attribute' => 'value']);
        $this->activeQuery->all();
    }

    public function testAndFilterWhere()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?id=value1&name=value2', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->filterWhere(['id' => 'value1']);
        $this->activeQuery->andFilterWhere(['name' => 'value2']);
        $this->activeQuery->all();
    }

    public function testFilterWhereEmpty()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->filterWhere(['attribute' => '']);
        $this->activeQuery->all();
    }

    public function testAndFilterWhereEmpty()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://localhost.localdomain?id=value1', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->filterWhere(['id' => 'value1']);
        $this->activeQuery->andFilterWhere(['name' => '']);
        $this->activeQuery->all();
    }

    public function testOrderBy()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://localhost.localdomain?_sort=id,name&_order=asc,desc', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC]);
        $this->activeQuery->all();
    }

    public function testAddOrderBy()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://localhost.localdomain?_sort=id,name&_order=asc,asc', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->orderBy('id');
        $this->activeQuery->addOrderBy('name');
        $this->activeQuery->all();
    }

    public function testOffset()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://localhost.localdomain?_page=2&_limit=5', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->limit(5);
        $this->activeQuery->offset(7);
        $this->activeQuery->all();
    }

    public function testOffsetWithoutLimit()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://localhost.localdomain?_page=4&_limit=10', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->offset(30);
        $this->activeQuery->all();
    }

    public function testLimit()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://localhost.localdomain?_limit=5', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->activeQuery->limit(5);
        $this->activeQuery->all();
    }

    public function testAllEmulated()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::never(),
        ]);
        $this->activeQuery->emulateExecution();
        $this->tester->assertEquals([], $this->activeQuery->all());
        $this->tester->assertEquals(0, $this->activeQuery->count());
    }

    public function testCountEmulated()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::never(),
        ]);
        $this->activeQuery->emulateExecution();
        $this->tester->assertEquals(0, $this->activeQuery->count());
    }

    public function testOneEmulated()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::never(),
        ]);
        $this->activeQuery->emulateExecution();
        $this->tester->assertEquals(null, $this->activeQuery->one());
    }

    public function testExistsEmulated()
    {
        Stub::update(StubModel::$httpClient, [
            'get' => \Codeception\Stub\Expected::never(),
        ]);
        $this->activeQuery->emulateExecution();
        $this->tester->assertEquals(false, $this->activeQuery->exists());
    }
}
