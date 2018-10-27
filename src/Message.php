<?php

	namespace Puggan\CashID;

	use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;
	use Mdanter\Ecc\Crypto\Signature\SignatureInterface;
	use Mdanter\Ecc\Crypto\Signature\Signer;
	use Mdanter\Ecc\EccFactory;

	class Message
	{
		public const Magic_Bytes = "Bitcoin Signed Message:\n";

		public $message;

		public function __construct($s)
		{
			$this->message = $s;
		}

		/**
		 * Hash the message
		 * @return string
		 */
		public function magic_hash() : string
		{
			$data = self::i8s2bin(self::varintBufNum(\strlen(self::Magic_Bytes)));
			$data .= self::Magic_Bytes;
			$data .= self::i8s2bin(self::varintBufNum(\strlen($this->message)));
			$data .= $this->message;

			return self::sha256sha256($data);
		}

		/**
		 * @param PublicKeyInterface $address
		 * @param SignatureInterface $signature
		 *
		 * @return bool
		 * @throws \RuntimeException
		 */
		public function verify(PublicKeyInterface $address, SignatureInterface $signature) : bool
		{
			$hash = $this->magic_hash();
			$ecdsa = new Signer(EccFactory::getAdapter());
			return $ecdsa->verify($address, $signature, gmp_import($hash));
		}

		/**
		 * Encode length as 1, 3, 5 or 9 bytes
		 * @param int $n lenght
		 *
		 * @return int[] i8 data
		 */
		public static function varintBufNum(int $n) : array
		{
			if($n < 253)
			{
				return [$n];
			}
			if($n <= 0xffff)
			{
				return [253, $n >> 8, $n & 0xFF];
			}
			if($n <= 0xffffffff)
			{
				return [254, $n >> 24 & 0xFF, $n >> 16 & 0xFF, $n >> 8 & 0xFF, $n & 0xFF];
			}
			return [254, $n >> 56 & 0xFF, $n >> 48 & 0xFF, $n >> 40 & 0xFF, $n >> 32 & 0xFF, $n >> 24 & 0xFF, $n >> 16 & 0xFF, $n >> 8 & 0xFF, $n & 0xFF];
		}

		/**
		 * @param int[] $i8s
		 *
		 * @return string
		 */
		public static function i8s2bin(array $i8s) : string
		{
			return implode('', array_map('chr', $i8s));
		}

		/**
		 * @param string $data
		 *
		 * @return string
		 */
		public static function sha256sha256(string $data) : string
		{
			return hash('sha256', hash('sha256', $data, true), true);
		}

	}
