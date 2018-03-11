<?php
namespace oteixido\rest\http;

use yii\base\Component;

class CurlFacade extends Component {
    private $_curl = null;

    public function open($url)
    {
        Yii::trace("CurlFacade::init($url)");
        $this->_curl = curl_init($url);
    }

    public function errno()
    {
        Yii::trace("CurlFacade::errno()");
        return curl_errno($this->_curl);
    }

    public function error()
    {
        Yii::trace("CurlFacade::error()");
        return curl_error($this->_curl);
    }

    public function getinfo($info)
    {
        Yii::trace("CurlFacade::getinfo($info)");
        return curl_getinfo($this->_curl, $info);
    }

    public function setopt($option, $value)
    {
        Yii::trace("CurlFacade::setopt($option)");
        curl_setopt($this->_curl, $option, $value);
    }

    public function exec()
    {
        Yii::trace("CurlFacade::exec()");
        return curl_exec($this->_curl);
    }

    public function close()
    {
        Yii::trace("CurlFacade::close()");
        curl_close($this->_curl);
    }
}
