<?php
namespace oteixido\rest\http;

use Yii;
use yii\base\Exception;
use yii\base\Component;

use oteixido\rest\helpers\UrlHelper;

class HttpClient extends Component {
    const METHOD_POST   = 'post';
    const METHOD_GET    = 'get';
    const METHOD_PUT    = 'put';
    const METHOD_DELETE = 'delete';

    public $curl = null;
    public $baseUrl = '';
    public $username = '';
    public $password = '';
    public $timeout = 10;
    public $sslVerify = true;
    public $postParametersAsHttpQuery = true;

    public function __construct($config = [])
    {
        $this->curl = new CurlFacade();
        parent::__construct($config);
    }

    public function get($url)
    {
        return $this->method(self::METHOD_GET, $url);
    }

    public function post($url, $values)
    {
        return $this->method(self::METHOD_POST, $url, $values);
    }

    public function put($url, $parameters)
    {
        return $this->method(self::METHOD_PUT, $url, $parameters);
    }

    public function delete($url)
    {
        return $this->method(self::METHOD_DELETE, $url);
    }

    private function method($method, $url, $parameters = [])
    {
        Yii::trace("HttpClient::method($method, $url)");

        $this->curl->open(UrlHelper::join([$this->baseUrl, $url]));
        if (!empty($this->username) || !empty($this->password))
            $this->curl->setopt(CURLOPT_USERPWD, $this->username.':'.$this->password);
        $this->curl->setopt(CURLOPT_TIMEOUT, $this->timeout);
        $this->curl->setopt(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setopt(CURLOPT_VERBOSE, true);
        $this->curl->setopt(CURLOPT_HEADER, true);
        $this->curl->setopt(CURLOPT_SSL_VERIFYHOST, ($this->sslVerify ? 2 : 0));
        $this->curl->setopt(CURLOPT_SSL_VERIFYPEER, ($this->sslVerify ? 1 : 0));
        if ($method == self::METHOD_POST) {
            $this->curl->setopt(CURLOPT_POST, true);
            $this->curl->setopt(CURLOPT_POSTFIELDS, $this->postParametersAsHttpQuery ? http_build_query($parameters) : $parameters);
        }
        if ($method == self::METHOD_PUT) {
            $this->curl->setopt(CURLOPT_CUSTOMREQUEST, 'PUT');
            $this->curl->setopt(CURLOPT_POSTFIELDS, $this->postParametersAsHttpQuery ? http_build_query($parameters) : $parameters);
        }
        if ($method == self::METHOD_DELETE) {
            $this->curl->setopt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        $result = $this->curl->exec();
        $curl_errno = $this->curl->errno();
        $curl_error = $this->curl->error();
        $curl_httpCode = $this->curl->getinfo(CURLINFO_HTTP_CODE);

        $header_size = $this->curl->getinfo(CURLINFO_HEADER_SIZE);
        $header_text = substr($result, 0, $header_size);
        $body = substr($result, $header_size);

        $headers = [];
        foreach(explode("\n", $header_text) as $header) {
            $middle = explode(":",$header);
            if (count($middle) == 2)
                $headers[trim($middle[0])] = trim($middle[1]);
        }
        $this->curl->close();
        if ($curl_errno > 0) {
            throw new Exception($curl_error, $curl_errno);
        }
        Yii::trace("HttpClient::method($method, $url) => httpCode=$curl_httpCode");
        return new HttpResponse($code = $curl_httpCode, $body, $headers);
    }
}
