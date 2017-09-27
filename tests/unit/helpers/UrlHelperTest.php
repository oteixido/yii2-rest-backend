<?php

use oteixido\yii2\rest\helpers\UrlHelper;

class UrlHelperTest extends \Codeception\Test\Unit
{
    public function testJoinEmpty()
    {
        $this->tester->assertUrlEquals('', UrlHelper::join([]));
    }

    public function testJoinHostname()
    {
        $this->tester->assertUrlEquals('http://example.com', UrlHelper::join(['http://example.com/']));
    }

    public function testJoinHostnameWithRoute()
    {
        $this->tester->assertUrlEquals('http://example.com/users/1/show', UrlHelper::join(['http://example.com/', '/users/', 1, 'show']));
    }

    public function testJoinHostnameWithoutProtocol()
    {
        $this->tester->assertUrlEquals('//example.com/users/1/show', UrlHelper::join(['//example.com/', '/users/', 1, '/show/']));
    }
}
