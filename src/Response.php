<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-10-26
	 * Time: 21:33
	 */

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
