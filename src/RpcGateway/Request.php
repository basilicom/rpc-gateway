<?php

namespace RpcGateway;

class Request
{
    /**
     * the dots in "local.project.doMethod"
     */
    const METHOD_DELIMITER = '.';

    /**
     * @var string
     */
    public $method = '';

    /**
     * @var array
     */
    public $params = array();

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return string the classname
     */
    public function getClassName()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($className, $methodName) = $this->getListOfClassNameAndMethod();

        return $className;
    }

    /**
     * @return string the classname
     */
    public function getMethodName()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($className, $methodName) = $this->getListOfClassNameAndMethod();

        return $methodName;
    }

    /**
     * @return array two elements, first is classname, second methodname
     */
    protected function getListOfClassNameAndMethod()
    {
        $methodParts = explode(
            Request::METHOD_DELIMITER,
            $this->getMethod()
        );

        $methodName = array_pop($methodParts);

        $className = implode(Request::METHOD_DELIMITER, $methodParts);

        return array(
            $className, $methodName
        );
    }
}