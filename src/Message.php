<?php

	namespace Puggan\CashID;

	use Mdanter\Ecc\Crypto\Key\PublicKey;
	use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;
	use Mdanter\Ecc\Crypto\Signature\SignatureInterface;
	use Mdanter\Ecc\Crypto\Signature\Signer;
	use Mdanter\Ecc\Curves\CurveFactory;
	use Mdanter\Ecc\EccFactory;
	use Puggan\CashID\Exceptions\InvalidSignature;

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
		 * @return \GMP
		 */
		public function hash() : \GMP
		{
			return gmp_import($this->magic_hash());
		}

		/**
		 * @param Address $address
		 * @param SignatureInterface $signature
		 *
		 * @return void
		 * @throws Exceptions\InvalidAddress
		 * @throws InvalidSignature
		 * @throws \LogicException
		 * @throws \RuntimeException
		 */
		public function verify(Address $address, SignatureInterface $signature) : void
		{
			$adapter = EccFactory::getAdapter();
			$public_key = $this->get_public_key($signature);
			$signature_address = Address::fromPublicKey($public_key);
			if($signature_address->binary_address !== $address->binary_address)
			{
				throw new InvalidSignature('Address missmatch');
			}

			if(!(new Signer($adapter))->verify($public_key, $signature, $this->hash()))
			{
				throw new InvalidSignature('Not signed by given address');
			}
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

		/**
		 * @param Signature|SignatureInterface $signature
		 *
		 * @return PublicKeyInterface
		 * @throws InvalidSignature
		 * @throws \LogicException
		 * @throws \RuntimeException
		 */
		public function get_public_key($signature) : PublicKeyInterface
		{
			$curve_definition = CurveFactory::getGeneratorByName('secp256k1');
			$curve_calculation = $curve_definition->getCurve();

			$order_n = $curve_definition->getOrder();
			$base_point_g = $curve_calculation->getPoint($curve_definition->getX(), $curve_definition->getY());

			// 2nd key?
			if($signature->i & 0x2)
			{
				$x = gmp_add($signature->r, $order_n);
			}
			else
			{
				$x = clone $signature->r;
			}

			$y = $curve_calculation->recoverYfromX($signature->i & 0x1, $x);
			$point = $curve_calculation->getPoint($x, $y);
			if(!$point->mul($order_n)->isInfinity())
			{
				throw new InvalidSignature('R * x not infinity ??');
			}

			/** @var \GMP $e_neg */
			$e_neg = gmp_mod(gmp_neg($this->hash()), $order_n);
			/** @var \GMP $r_inv */
			$r_inv = gmp_invert($signature->r, $order_n);
			$q = $point->mul($signature->s);
			$q = $q->add($base_point_g->mul($e_neg));
			$q = $q->mul($r_inv);

			return $curve_definition->getPublicKeyFrom($q->getX(), $q->getY());
		}
	}
