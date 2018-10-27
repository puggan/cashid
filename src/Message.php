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

		/**
		 * @param Signature $signature
		 */
		public function get_public_key($signature)
		{
			/*
			 * /* jshint maxstatements: 25 * /
			var i = this.sig.i;
			$.checkArgument(i === 0 || i === 1 || i === 2 || i === 3, new Error('i must be equal to 0, 1, 2, or 3'));

			var e = BN.fromBuffer(this.hashbuf);
			var r = this.sig.r;
			var s = this.sig.s;

			// A set LSB signifies that the y-coordinate is odd
			var isYOdd = i & 1;

			// The more significant bit specifies whether we should use the
			// first or second candidate key.
			var isSecondKey = i >> 1;

			var n = Point.getN();
			var G = Point.getG();

			// 1.1 Let x = r + jn
			var x = isSecondKey ? r.add(n) : r;
			var R = Point.fromX(isYOdd, x);

			// 1.4 Check that nR is at infinity
			var nR = R.mul(n);

			if (!nR.isInfinity()) {
				throw new Error('nR is not a valid curve point');
			}

			// Compute -e from e
			var eNeg = e.neg().mod(n);

			// 1.6.1 Compute Q = r^-1 (sR - eG)
			// Q = r^-1 (sR + -eG)
			var rInv = r.invm(n);

			//var Q = R.multiplyTwo(s, G, eNeg).mul(rInv);
			var Q = R.mul(s).add(G.mul(eNeg)).mul(rInv);

			var pubkey = PublicKey.fromPoint(Q, this.sig.compressed);

			return pubkey;
			 */
		}
	}
