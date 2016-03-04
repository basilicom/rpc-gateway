<?php

	namespace RpcGateway;

	class Exception extends \Exception
	{
		private $data = null;

		public function __construct($message = '', $code = 0, \Exception $previous = null, $data = null)
		{
			$this->data = $data;
			parent::__construct($message, $code, $previous);
		}

		public function getData()
		{
			return $this->data;
		}
	}
