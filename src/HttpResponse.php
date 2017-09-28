<?php
namespace oteixido\yii2\rest;

use yii\base\Component;

/**
 * HttpResponse represents a HTTP response.
 *
 * @author Oriol Teixidó <oriol.teixido@gmail.com>
 */
class HttpResponse extends Component
{
    const HTTP_OK          = 200;
    const HTTP_CREATED     = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_NOT_FOUND   = 404;

    /**
     * @var int the response HTTP code of the response.
     */
    private $_code = null;

    /**
     * @var string the response content of the HTTP response.
     */
    private $_content = null;

    /**
     * @var string the response headers of the HTTP response.
     */
    private $_headers = null;

    /**
     * Constructor.
     * @param int $code HTTP code of the response.
     * @param string $content content of the response.
     * @param string[] $headers headers of the response.
     */
    public function __construct($code = HTTP_OK, $content = '', $headers = [])
    {
        $this->_code = $code;
        $this->_content = $content;
        $this->_headers = $headers;
    }

    /**
     * Returns the code of the response.
     * @return int the code of the response.
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Returns the content of the response.
     * @return string the content of the response.
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Returns a header of the response.
     * @param string $name the name of the header.
     * @return string the header $name of the response.
     */
    public function getHeader($name)
    {
        return (isset($this->_headers[$name]) ? $this->_headers[$name] : null);
    }
}
