<?php
use Codeception\Util\Stub;

use oteixido\rest\ActiveRecord;
use oteixido\rest\ActiveQuery;
use oteixido\rest\http\HttpClient;
use oteixido\rest\http\HttpResponse;

class AR extends ActiveRecord {
    public static function getBaseUri() {
        return 'posts';
    }
    public static function primaryKey() {
        return ['id'];
    }
    public function attributes() {
        return ['id', 'title', 'email'];
    }
    public function rules() {
        return [
            ['title', 'required'],
            ['email', 'email'],
        ];
    }
}

class ActiveRecordTest extends \Codeception\Test\Unit
{
    public function testGetUri()
    {
        $model = new AR(['id' => 'id1']);
        $this->tester->assertEquals('posts', $model->getListUri());
        $this->tester->assertEquals('posts', $model->getCreateUri());
        $this->tester->assertEquals('posts/id1', $model->getViewUri());
        $this->tester->assertEquals('posts/id1', $model->getUpdateUri());
    }

    public function testGetDb()
    {
        Yii::$app->set('httpClient', Stub::makeEmpty(HttpClient::className()));
        $this->tester->assertEquals(Yii::$app->httpClient, (new AR())->getDb());
    }

    public function testFind()
    {
        $this->tester->assertInstanceOf(ActiveQuery::className(), (new AR())->find());
    }

    public function testInsert_validationOk()
    {
        $model = new AR(['title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'post' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                $this->tester->assertEquals('posts', $url);
                $this->tester->assertEquals('Title 1', $values['title']);
                $this->tester->assertEquals('name@foo.bar', $values['email']);
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_CREATED,
                        'getContent' => json_encode(array_merge($values, ['id' => 'id1'])),
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->assertTrue($model->insert());
        $this->tester->assertEquals('id1', $model->id);
    }

    public function testInsert_validationNok()
    {
        $model = new AR(['title' => 'Title 1', 'email' => 'name#foo.bar']);
        $this->tester->assertFalse($model->insert());
    }

    public function testInsert_exceptAttributes()
    {
        $model = new AR(['title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'post' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                $this->tester->assertEquals('posts', $url);
                $this->tester->assertEquals('Title 1', $values['title']);
                $this->tester->assertArrayNotHasKey('email', $values);
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_CREATED,
                        'getContent' => json_encode(array_merge($values, ['id' => 'id1'])),
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->assertTrue($model->insert($validation = true, $attributes = ['title']));
        $this->tester->assertEquals('id1', $model->id);
    }

    public function testInsert_httpError()
    {
        $model = new AR(['title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'post' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                throw new Exception();
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->expectException(Exception::class, function() use ($model) {
            $model->insert();
        });
    }

    public function testInsert_httpCodeInvalid()
    {
        $model = new AR(['title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'post' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_NOT_FOUND,
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->expectException(\yii\web\ServerErrorHttpException::class, function() use ($model) {
            $model->insert();
        });
    }

    public function testUpdate_validationOk()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'put' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                $this->tester->assertEquals('posts/id1', $url);
                $this->tester->assertEquals('Title 1', $values['title']);
                $this->tester->assertEquals('name@foo.bar', $values['email']);
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_OK,
                        'getContent' => json_encode(array_merge($values)),
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->assertTrue($model->update());
        $this->tester->assertEquals('id1', $model->id);
    }

    public function testUpdate_validationNok()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name#foo.bar']);
        $this->tester->assertFalse($model->update());
    }

    public function testUpdate_exceptAttributes()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'put' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                $this->tester->assertEquals('posts/id1', $url);
                $this->tester->assertEquals('Title 1', $values['title']);
                $this->tester->assertArrayNotHasKey('email', $values);
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_OK,
                        'getContent' => json_encode(array_merge($values)),
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->assertTrue($model->update($validation = true, $attributes = ['title']));
        $this->tester->assertEquals('id1', $model->id);
    }

    public function testUpdate_httpError()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'put' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                throw new Exception();
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->expectException(Exception::class, function() use ($model) {
            $model->update();
        });
    }

    public function testUpdate_httpCodeInvalid()
    {
    $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'put' => \Codeception\Stub\Expected::atLeastOnce(function($url, $values) {
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_NOT_FOUND,
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->expectException(\yii\web\ServerErrorHttpException::class, function() use ($model) {
            $model->update();
        });
    }

    public function testDelete()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'delete' => \Codeception\Stub\Expected::atLeastOnce(function($url) {
                $this->tester->assertEquals('posts/id1', $url);
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_OK,
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->assertTrue($model->delete());
    }

    public function testDelete_notFound()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'delete' => \Codeception\Stub\Expected::atLeastOnce(function($url) {
                $this->tester->assertEquals('posts/id1', $url);
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_NOT_FOUND,
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->assertTrue($model->delete());
    }

    public function testDelete_httpError()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'delete' => \Codeception\Stub\Expected::atLeastOnce(function($url) {
                throw new Exception();
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->expectException(Exception::class, function() use ($model) {
            $model->delete();
        });
    }

    public function testDelete_httpCodeInvalid()
    {
        $model = new AR(['id' => 'id1', 'title' => 'Title 1', 'email' => 'name@foo.bar']);
        $httpClient = Stub::makeEmpty(HttpClient::className(), [
            'delete' => \Codeception\Stub\Expected::atLeastOnce(function($url) {
                return Stub::makeEmpty(HttpResponse::className(), [
                        'getCode' => HttpResponse::HTTP_BAD_REQUEST,
                    ]);
            }),
        ]);
        Yii::$app->set('httpClient', $httpClient);
        $this->tester->expectException(\yii\web\ServerErrorHttpException::class, function() use ($model) {
            $model->delete();
        });
    }
}
