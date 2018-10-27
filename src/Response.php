<?php

	namespace Puggan\CashID;

	class Response
	{
		public $address;
		public $metadata;
		public function __construct($address, $metadata = null)
		{
			$this->address = $address;
			$this->metadata = $metadata;
		}
	}
