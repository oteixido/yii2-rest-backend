<?php
use oteixido\rest\http\HttpClient;
use oteixido\rest\http\CurlFacade;

class HttpClientTest extends \Codeception\Test\Unit
{
    public $client;
    public $curl;

    protected function _before()
    {
        $headers = [
            'header-1: value-header-1',
        ];
        $content = 'Body Content';
        $this->curl =  $this->makeEmpty(CurlFacade::className(), [
            'open' => \Codeception\Stub\Expected::atLeastOnce(function ($url) {
            }),
            'exec' => function () use ($headers, $content) {
                return implode("\n", $headers)."\n".$content;
            },
            'getinfo' => function ($option) use ($headers, $content) {
                if ($option == CURLINFO_HTTP_CODE) return 200;
                if ($option == CURLINFO_HEADER_SIZE) return strlen(implode("\n", $headers)."\n");
            },
        ]);
        $this->client = new HttpClient(['curl' => $this->curl]);
    }

    public function testGet_ok()
    {
        \Codeception\Stub::update($this->curl, [
            'errno' => function () {
                return 0;
            },
            'error' => function() {
                return '';
            },
        ]);
        $httpResponse = $this->client->get('url');
        $this->tester->assertEquals(200, $httpResponse->getCode());
        $this->tester->assertEquals('Body Content', $httpResponse->getContent());
        $this->tester->assertEquals('value-header-1', $httpResponse->getHeader('header-1'));
    }

    public function testGet_error()
    {
        \Codeception\Stub::update($this->curl, [
            'errno' => function () { return 500; },
            'error' => function() { return 'Error 500'; }
        ]);
        $this->tester->expectException(new Exception('Error 500', 500), function() {
            $this->client->get('url');
        });
    }
}
