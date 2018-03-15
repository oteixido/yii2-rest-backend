<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class UnitTester extends \Codeception\Actor
{
    use _generated\UnitTesterActions;

    /**
     * Define custom actions here
     */
     public function assertUrlEquals($expected, $actual, $message = '')
     {
         if ($this->_assertUrlKey('scheme', $expected, $actual, $message))
             if ($this->_assertUrlKey('host', $expected, $actual, $message))
                 if ($this->_assertUrlKey('port', $expected, $actual, $message))
                     if ($this->_assertUrlKey('user', $expected, $actual, $message))
                         if ($this->_assertUrlKey('pass', $expected, $actual, $message))
                             if ($this->_assertUrlKey('path', $expected, $actual, $message))
                                 if ($this->_assertUrlKey('query', $expected, $actual, $message, $query = true))
                                     if ($this->_assertUrlKey('fragment', $expected, $actual, $message))
                                         $this->assertTrue(true, $message);
     }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    private function _assertUrlKey($key, $expected, $actual, $message, $query = false)
    {
        $expectedParsed = parse_url($expected);
        $actualParsed = parse_url($actual);

        if (array_key_exists($key, $expectedParsed) && array_key_exists($key, $actualParsed)) {
            if (urldecode($expectedParsed[$key]) == urldecode($actualParsed[$key]))
                return true;
        }
        if (!array_key_exists($key, $expectedParsed) && !array_key_exists($key, $actualParsed)) {
            return true;
        }
        $this->assertEquals($expected, $actual, $message);
        return false;
    }
}
