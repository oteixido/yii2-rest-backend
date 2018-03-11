<?php
use oteixido\rest\http\HttpResponse;

class ResponseTest extends \Codeception\Test\Unit
{
    public $httpResponse;

    protected function _before()
    {
        $this->httpResponse = new HttpResponse($code = 200, $content = 'Content', $headers = [ 'header1' => 'Value1', 'header2' => 'Value2' ]);
    }

    public function testGetCode()
    {
        $this->tester->assertEquals(200, $this->httpResponse->getCode());
    }

    public function testGetContent()
    {
        $this->tester->assertEquals('Content', $this->httpResponse->getContent());
    }

    public function testGetHeader()
    {
        $this->tester->assertEquals('Value1', $this->httpResponse->getHeader('header1'));
        $this->tester->assertEquals('Value2', $this->httpResponse->getHeader('header2'));
    }
}
