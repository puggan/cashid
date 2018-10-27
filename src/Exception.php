<?php

	namespace Puggan\CashID;

	use Throwable;

	abstract class Exception extends \RuntimeException
	{
		public $http_code;

		public function __construct(string $message, int $http_code = 500, Throwable $previous = NULL)
		{
			$this->http_code = $http_code;
			parent::__construct($message, $http_code, $previous);
		}
	}
