<?php

namespace RpcGateway;

class Gateway
{
    /**
     * @var \Zend_Controller_Response_Abstract
     */
    protected $response;

    /**
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * @var string
     */
    protected $serviceClassNamespace = '\\App\\Rpc\\Service\\';

    /**
     * @param \Zend_Controller_Request_Http $request
     * @return array
     * @throws \Exception
     */
    protected function getJsonRequestFromRequestRawBody(\Zend_Controller_Request_Http $request)
    {
        $requestText = $request->getRawBody();
        $rpcData = json_decode($requestText, true);

        if (!is_array($rpcData)) {
            throw new \Exception("Invalid request at " . __METHOD__);
        }

        return $rpcData;
    }

    /**
     * @param $jsonData array
     * @throws \Exception
     * @return \RpcGateway\Request
     */
    protected function getRpcRequestFromJsonRequest($jsonData)
    {
        $rpcRequest = new Request();

        if (!array_key_exists('method', $jsonData)) {
            throw new \Exception("Invalid method at " . __METHOD__);
        }

        if (
            ! (
                array_key_exists('params', $jsonData)
                && is_array($jsonData['params'])
            )
        ) {
            throw new \Exception("Invalid params at " . __METHOD__);
        }

        $rpcRequest->method = (string)$jsonData['method'];
        $rpcRequest->params = $jsonData['params'];

        return $rpcRequest;
    }

    /**
     * @param $rpcRequest \RpcGateway\Request
     * @throws \Exception
     */
    protected function throwErrorIfRpcRequestIsInvalid($rpcRequest)
    {
        $serviceMethodName = $rpcRequest->getMethodName();

        // create a PHP compatible version of the requested service class
        $serviceClassName   =
            $this->getServiceClassNamespace()
            . str_replace(
                Request::METHOD_DELIMITER,
                '_',
                $rpcRequest->getClassName()
            );

        try {
            class_exists($serviceClassName);
        } catch (\Exception $exception) {

            throw new \Exception(
                "Invalid rpc.method ["
                . $rpcRequest->getMethod()
                . "] (not found) at " . __METHOD__
            );
        }

        $serviceClassReflection = new \ReflectionClass($serviceClassName);
        if (
            ($serviceClassReflection->isAbstract())
            || ($serviceClassReflection->isInterface())
            || ($serviceClassReflection->isInternal())
        ) {
            throw new \Exception(
                "Invalid rpc.class ["
                . $rpcRequest->getMethod()
                . "] at " . __METHOD__
            );
        }

        if ($serviceClassReflection->hasMethod($serviceMethodName) !== true) {
            throw new \Exception(
                "Invalid rpc.method ["
                . $rpcRequest->getMethod()
                . "] (not found) at " . __METHOD__
            );
        }

        $serviceMethodReflection = $serviceClassReflection->getMethod(
            $serviceMethodName
        );

        if (
            ($serviceMethodReflection->isAbstract())
            || ($serviceMethodReflection->isConstructor())
            || ($serviceMethodReflection->isStatic())
            || (!$serviceMethodReflection->isPublic())
        ) {
            throw new \Exception(
                "Invalid rpc.method ["
                . $rpcRequest->getMethod()
                . "] (not invokable) at " . __METHOD__
            );
        }

        $argsExpectedMin = $serviceMethodReflection->getNumberOfRequiredParameters();
        $argsExpectedMax = $serviceMethodReflection->getNumberOfParameters();

        $argsOptional = $argsExpectedMax - $argsExpectedMin;
        $argsGiven = count($rpcRequest->getParams());

        if (
            ($argsGiven < $argsExpectedMin)
            || ($argsGiven > $argsExpectedMax)
        ) {
            $msg =
                'Invalid rpc.method ['
                    . $rpcRequest->getMethod()
                    . ']'
                    . " Wrong number of parameters:"
                    . " " . $argsGiven . " given"
                    . " (required: " . $argsExpectedMin
                    . " optional: " . $argsOptional . ")"
                    . " expectedMin: " . $argsExpectedMin
                    . " expectedMax: " . $argsExpectedMax;

            throw new Exception($msg);
        }
    }

    /**
     * @param $rpcRequest \RpcGateway\Request
     * @return mixed
     * @throws \Exception
     */
    protected function invokeRpcRequest($rpcRequest)
    {
        $this->throwErrorIfRpcRequestIsInvalid($rpcRequest);

        // create a PHP compatible version of the requested service class
        $serviceClassName   =
            $this->getServiceClassNamespace()
                . str_replace(
                Request::METHOD_DELIMITER,
                '_',
                $rpcRequest->getClassName()
            );

        $serviceClass = new $serviceClassName();
        $serviceClassReflection = new \ReflectionClass($serviceClassName);
        $serviceMethodName  = $rpcRequest->getMethodName();

        $serviceMethodReflection = $serviceClassReflection->getMethod(
            $serviceMethodName
        );

        $serviceCallResult = $serviceMethodReflection->invokeArgs(
            $serviceClass,
            $rpcRequest->getParams()
        );

        return $serviceCallResult;
    }

    /**
     * @param $result array
     * @param $response \Zend_Controller_Response_Abstract
     */
    protected function putResultAsJsonRpcIntoResponse($result, $response)
    {
        $response->setHeader(
            "Content-Type",
            "application/json; charset=utf-8",
            true // replace header if we must!
        );

        $responseData = array(
            'error'     => null,
            'result'    => $result
        );

        $response->setBody(
            json_encode($responseData)
        );
    }

    /**
     * @param $result array
     * @param $response \Zend_Controller_Response_Abstract
     */
    protected function putErrorAsJsonRpcIntoResponse($result, $response)
    {
        $response->setHeader(
            "Content-Type",
            "application/json; charset=utf-8",
            true // replace header if we must!
        );

        $responseData = array(
            'error'     => $result,
            'result'    => null
        );

        $response->setBody(
            json_encode($responseData)
        );
    }

    /**
     * @return void
     */
    public function dispatch()
    {
        /** @var $jsonRequest array */
        $jsonRequest = $this->getJsonRequestFromRequestRawBody(
            $this->getRequest()
        );

        /** @var $rpcRequest \RpcGateway\Request */
        $rpcRequest = $this->getRpcRequestFromJsonRequest(
            $jsonRequest
        );

        try {

            $result = $this->invokeRpcRequest($rpcRequest);

        } catch (Exception $exception) {

            $result = array(
                'code'      => $exception->getCode(),
                'message'   => $exception->getMessage(),
            );

            $this->putErrorAsJsonRpcIntoResponse(
                $result,
                $this->response
            );

            return;
        }

        $this->putResultAsJsonRpcIntoResponse(
            $result,
            $this->response
        );
    }

    /**
     * @return string
     */
    public function getServiceClassNamespace()
    {
        return $this->serviceClassNamespace;
    }

    /**
     * @param string $serviceClassNamespace
     */
    public function setServiceClassNamespace($serviceClassNamespace)
    {
        $this->serviceClassNamespace = $serviceClassNamespace;
    }

    /**
     * @return \Zend_Controller_Response_Abstract
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param \Zend_Controller_Response_Abstract $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return \Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
}