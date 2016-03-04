<?php

	namespace RpcGateway;

	class Exception extends \Exception
	{
		/** @var mixed|null */
		private $data = null;

		/**
		 * Exception constructor.
		 *
		 * @param string          $message
		 * @param int             $code
		 * @param \Exception|null $previous
		 * @param mixed|null      $data
		 */
		public function __construct($message = '', $code = 0, \Exception $previous = null, $data = null)
		{
			$this->data = $data;
			parent::__construct($message, $code, $previous);
		}

		/**
		 * @return mixed|null
		 */
		public function getData()
		{
			return $this->data;
		}

	}
