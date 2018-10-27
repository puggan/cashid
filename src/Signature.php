<?php

	namespace Puggan\CashID;

	use Mdanter\Ecc\Crypto\Signature\SignatureInterface;

	/**
	 * Class Signature
	 * @package Puggan\CashID
	 * @property bool compressed
	 * @property int i
	 * @property \GMP r
	 * @property \GMP s
	 */
	class Signature implements SignatureInterface
	{
		public $compressed;
		public $i;
		public $r;
		public $s;

		/**
		 * Signature constructor.
		 *
		 * @param string $base64_string
		 */
		public function __construct($base64_string)
		{
			$data = base64_decode($base64_string);
			$first = \ord($data[0]);
			$i = $first - 27;
			if($i < 4)
			{
				$this->i = $i;
				$this->compressed = false;
			}
			else
			{
				$this->i = $i - 4;
				$this->compressed = true;
			}

			$this->r = gmp_import(substr($data, 1, 32));
			$this->s = gmp_import(substr($data, 33, 32));
		}

		/**
		 * Returns the r parameter of the signature.
		 *
		 * @return \GMP
		 */
		public function getR() : \GMP
		{
			return $this->r;
		}

		/**
		 * Returns the s parameter of the signature.
		 *
		 * @return \GMP
		 */
		public function getS() : \GMP
		{
			return $this->s;
		}
	}
