<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-10-27
	 * Time: 01:15
	 */

	namespace Puggan\CashID;

	use Mdanter\Ecc\Crypto\Signature\SignatureInterface;

	class Signature implements SignatureInterface
	{
		public $data;

		/**
		 * Signature constructor.
		 *
		 * @param string $s
		 */
		public function __construct($s)
		{
			$this->data = $s;
		}

		/**
		 * Returns the r parameter of the signature.
		 *
		 * @return \GMP
		 */
		public function getR() : \GMP
		{
			// TODO: Implement getR() method.
		}

		/**
		 * Returns the s parameter of the signature.
		 *
		 * @return \GMP
		 */
		public function getS() : \GMP
		{
			// TODO: Implement getS() method.
		}
	}
