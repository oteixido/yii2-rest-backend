<?php
namespace oteixido\rest\http;

use yii\base\Component;

class CurlProxy extends Component {
    private $_curl = null;

    public function open($url)
    {
        Yii::trace("CurlProxy::init($url)");
        $this->_curl = curl_init($url);
    }

    public function errno()
    {
        Yii::trace("CurlProxy::errno()");
        return curl_errno($this->_curl);
    }

    public function error()
    {
        Yii::trace("CurlProxy::error()");
        return curl_error($this->_curl);
    }

    public function getinfo($info)
    {
        Yii::trace("CurlProxy::getinfo($info)");
        return curl_getinfo($this->_curl, $info);
    }

    public function setopt($option, $value)
    {
        Yii::trace("CurlProxy::setopt($option)");
        curl_setopt($this->_curl, $option, $value);
    }

    public function exec()
    {
        Yii::trace("CurlProxy::exec()");
        return curl_exec($this->_curl);
    }

    public function close()
    {
        Yii::trace("CurlProxy::close()");
        curl_close($this->_curl);
    }
}
