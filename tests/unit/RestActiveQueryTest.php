<?php

use oteixido\yii2\rest\RestActiveQuery;
use oteixido\yii2\rest\http\HttpClient;
use oteixido\yii2\rest\http\HttpResponse;
use Codeception\Util\Stub;

class RestActiveQueryObjectMock extends \yii\base\Component
{
    public static $httpClient;
    public $attribute1;
    public $attribute2;

    static public function getDb()
    {
        return self::$httpClient;
    }

    public static function populateRecord($model, $elem)
    {
        $model->attribute1 = $elem['attribute1'];
        $model->attribute2 = $elem['attribute2'];
    }
}

class RestActiveQueryTest extends \Codeception\Test\Unit
{
    public $restActiveQuery;

    private function createModel($values)
    {
        $model = new RestActiveQueryObjectMock();
        RestActiveQueryObjectMock::populateRecord($model, $values);
        return $model;
    }

    private function setHttpClientModels($httpCode, $models)
    {
        RestActiveQueryObjectMock::$httpClient = Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => function () use ($httpCode, $models) {
                return new HttpResponse($httpCode, json_encode($models), [ 'X-Total-Count' => count($models) ]);
            },
        ]);
    }

    protected function _before()
    {
        RestActiveQueryObjectMock::$httpClient = Stub::makeEmpty(HttpClient::className());
        $this->restActiveQuery = new restActiveQuery(RestActiveQueryObjectMock::className(), 'http://api.model.test');
    }

    public function testAllEmpty()
    {
        $this->setHttpClientModels(200, []);
        $this->tester->assertEquals([], $this->restActiveQuery->all());
        $this->tester->assertEquals(0, $this->restActiveQuery->count());
    }

    public function testAllNotEmpty()
    {
        $model1 = $this->createModel(['attribute1' => 'value11', 'attribute2' => 'value12']);
        $model2 = $this->createModel(['attribute1' => 'value21', 'attribute2' => 'value22']);
        $models = [ $model1, $model2 ];
        $this->setHttpClientModels(200, $models);
        $this->tester->assertEquals($models, $this->restActiveQuery->all());
        $this->tester->assertEquals(count($models), $this->restActiveQuery->count());
    }

    public function testOneEmpty()
    {
        $this->setHttpClientModels(200, []);
        $this->tester->assertEquals(null, $this->restActiveQuery->one());
    }

    public function testOneNotEmpty()
    {
        $model1 = $this->createModel(['attribute1' => 'value11', 'attribute2' => 'value12']);
        $model2 = $this->createModel(['attribute1' => 'value21', 'attribute2' => 'value22']);
        $this->setHttpClientModels(200, [ $model1, $model2 ]);

        $this->tester->assertEquals($model1, $this->restActiveQuery->one());
    }

    public function testExistsEmpty()
    {
        $this->setHttpClientModels(200, []);
        $this->tester->assertFalse($this->restActiveQuery->exists());
    }

    public function testExists()
    {
        $model1 = $this->createModel(['attribute1' => 'value11', 'attribute2' => 'value12']);
        $model2 = $this->createModel(['attribute1' => 'value21', 'attribute2' => 'value22']);
        $this->setHttpClientModels(200, [ $model1, $model2 ]);

        $this->tester->assertTrue($this->restActiveQuery->exists());
    }

    public function testIndexBy()
    {
        $model1 = $this->createModel(['attribute1' => 'value11', 'attribute2' => 'value12']);
        $model2 = $this->createModel(['attribute1' => 'value21', 'attribute2' => 'value22']);
        $models = [ $model1, $model2 ];
        $this->setHttpClientModels(200, $models);
        $this->restActiveQuery->indexBy('attribute1');
        $this->tester->assertEquals([
            'value11' => $model1,
            'value21' => $model2,
        ], $this->restActiveQuery->all());
    }

    public function testWhereEmpty()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where([]);
        $this->restActiveQuery->all();
    }

    public function testWhere()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where(['attribute' => 'value']);
        $this->restActiveQuery->all();
    }

    public function testWhereNull()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute=', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where([ 'attribute' => '' ]);
        $this->restActiveQuery->all();
    }

    public function testAndWhere()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute1=value1&attribute2=value2&attribute3=value3', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where(['attribute1' => 'value1']);
        $this->restActiveQuery->andWhere(['attribute2' => 'value2']);
        $this->restActiveQuery->andWhere(['attribute3' => 'value3']);
        $this->restActiveQuery->all();
    }

    public function testWhereEquals()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where(['=', 'attribute', 'value']);
        $this->restActiveQuery->all();
    }

    public function testWhereNotEquals()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute_ne=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where(['!=', 'attribute', 'value']);
        $this->restActiveQuery->all();
    }

    public function testWhereGreaterThan()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute_gte=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where(['>=', 'attribute', 'value']);
        $this->restActiveQuery->all();
    }

    public function testWhereLessThan()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute_lte=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where(['<=', 'attribute', 'value']);
        $this->restActiveQuery->all();
    }

    public function testWhereLike()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute_like=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->where(['like', 'attribute', 'value']);
        $this->restActiveQuery->all();
    }

    public function testFilterWhere()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute=value', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->filterWhere(['attribute' => 'value']);
        $this->restActiveQuery->all();
    }

    public function testAndFilterWhere()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute1=value1&attribute2=value2', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->filterWhere(['attribute1' => 'value1']);
        $this->restActiveQuery->andFilterWhere(['attribute2' => 'value2']);
        $this->restActiveQuery->all();
    }

    public function testFilterWhereEmpty()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->filterWhere(['attribute' => '']);
        $this->restActiveQuery->all();
    }

    public function testAndFilterWhereEmpty()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertEquals('http://api.model.test?attribute1=value1', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->filterWhere(['attribute1' => 'value1']);
        $this->restActiveQuery->andFilterWhere(['attribute2' => '']);
        $this->restActiveQuery->all();
    }

    public function testOrderBy()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://api.model.test?_sort=attribute1,attribute2&_order=asc,desc', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->orderBy(['attribute1' => SORT_ASC, 'attribute2' => SORT_DESC]);
        $this->restActiveQuery->all();
    }

    public function testAddOrderBy()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://api.model.test?_sort=attribute1,attribute2&_order=asc,asc', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->orderBy('attribute1');
        $this->restActiveQuery->addOrderBy('attribute2');
        $this->restActiveQuery->all();
    }

    public function testOffset()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://api.model.test?_page=2&_limit=5', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->limit(5);
        $this->restActiveQuery->offset(7);
        $this->restActiveQuery->all();
    }

    public function testOffsetWithoutLimit()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://api.model.test?_page=4&_limit=10', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->offset(30);
        $this->restActiveQuery->all();
    }

    public function testLimit()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::atLeastOnce(function($url, $valideCodes) {
                $this->tester->assertUrlEquals('http://api.model.test?_limit=5', $url);
                return new HttpResponse(200, '[]', [ 'X-Total-Count' => 0 ]);
            })
        ]);
        $this->restActiveQuery->limit(5);
        $this->restActiveQuery->all();
    }

    public function testAllEmulated()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::never(),
        ]);
        $this->restActiveQuery->emulateExecution();
        $this->tester->assertEquals([], $this->restActiveQuery->all());
        $this->tester->assertEquals(0, $this->restActiveQuery->count());
    }

    public function testCountEmulated()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::never(),
        ]);
        $this->restActiveQuery->emulateExecution();
        $this->tester->assertEquals(0, $this->restActiveQuery->count());
    }

    public function testOneEmulated()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::never(),
        ]);
        $this->restActiveQuery->emulateExecution();
        $this->tester->assertEquals(null, $this->restActiveQuery->one());
    }

    public function testExistsEmulated()
    {
        Stub::update(RestActiveQueryObjectMock::$httpClient, [
            'get' => Stub::never(),
        ]);
        $this->restActiveQuery->emulateExecution();
        $this->tester->assertEquals(false, $this->restActiveQuery->exists());
    }
}
