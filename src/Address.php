<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-10-26
	 * Time: 23:27
	 */

	namespace Puggan\CashID;

	use Mdanter\Ecc\Curves\CurveFactory;
	use Mdanter\Ecc\Math\MathAdapterFactory;
	use Mdanter\Ecc\Primitives\CurveFpInterface;
	use Mdanter\Ecc\Primitives\GeneratorPoint;
	use Mdanter\Ecc\Primitives\PointInterface;
	use Puggan\CashID\Exceptions\InvalidAddress;
	use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;

	/**
	 * Class Address
	 * @package Puggan\CashID
	 *
	 * @property int[] binary_address address in 8bit integers
	 */
	class Address implements PublicKeyInterface
	{
		/** @var string base32 charset  */
		public const CachCharset = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
		/** @var int[] 32*(index+5)  */
		public const LengthTable = [160,192,224,256,320,384,448,512];
		/** @var int[] 4*(index+5)  */
		public const LengthTableByte = [20,24,28,32,40,48,56,64];
		public const Types = [0b0000 => 'P2KH', 0b1000 => 'P2SH'];

		public $binary_address;
		public $type;

		/**
		 * Address constructor.
		 *
		 * @param int[] $binary_address
		 *
		 * @throws InvalidAddress
		 */
		public function __construct($binary_address)
		{
			$this->binary_address = $binary_address;

			$this->validate();
		}

		/**
		 * @throws InvalidAddress
		 */
		public function validate() : void
		{
			if(!isset($this->binary_address[0])) {
				throw new InvalidAddress('Empty address');
			}

			if($this->binary_address[0] > 0x7F)
			{
				throw new InvalidAddress('Reserved first bit most be zero');
			}

			$type_index = $this->binary_address[0] >> 3;
			$length_index = $this->binary_address[0] & 0x07;
			$hash_length = self::LengthTableByte[$length_index];
			// 8bit + length + 40bit
			$total_length = 6 + $hash_length;

			if(\count($this->binary_address) !== $total_length)
			{
				throw new InvalidAddress('Invalid Lenght');
			}

			if(isset(self::Types[$type_index]))
			{
				$this->type = self::Types[$type_index];
			}
			else
			{
				throw new InvalidAddress('Unknown key type, only P2KH implemented');
			}

			if($this->type !== 'P2KH') {
				throw new InvalidAddress('Bad key type, only P2KH implemented');
			}

			// checksum PolyMod from https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md#checksum
			$c = 1;
			$data_length = $total_length - 5;
			$adress_checksum = 0;
			foreach($this->binary_address as $index => $d)
			{
				if($index >= $data_length) {
					$adress_checksum = ($adress_checksum << 8) + $d;
					continue;
				}

				$c0 = $c >> 35;
				$c = (($c & 0x07ffffffff) << 5) ^ $d;

				if($c0 & 0x01)
				{
					$c ^= 0x98f2bc8e61;
				}
				if($c0 & 0x02)
				{
					$c ^= 0x79b76d99e2;
				}
				if($c0 & 0x04)
				{
					$c ^= 0xf33e5fb3c4;
				}
				if($c0 & 0x08)
				{
					$c ^= 0xae2eabe2a8;
				}
				if($c0 & 0x10)
				{
					$c ^= 0x1e4f43e470;
				}
			}
			$c ^= 1;
			if($c !== $adress_checksum)
			{
				throw new InvalidAddress('Unknown key type, only P2KH and P2SH implemented');
			}
		}

		/**
		 * @param $s
		 *
		 * @return Address
		 * @throws InvalidAddress
		 */
		public static function fromCashAddr($s) : Address
		{
			$prefix = 'bitcoincash:';
			if(strpos($s, $prefix) === 0) {
				$s = substr($s, \strlen($prefix));
			}

			if(!preg_match('#^[' . self::CachCharset . ']$#', $s))
			{
				throw new InvalidAddress('Not base32-encoded');
			}

			return new self(self::base32_decode($s));
		}

		public function toCashAddr()
		{
			return 'bitcoincash:' . self::base32_encode($this->binary_address);

		}

		/**
		 * @param string $s base32 encoded string
		 *
		 * @return int[] i8 data
		 * @throws InvalidAddress
		 */
		public static function base32_decode($s) : array
		{
			$b32_data = [];
			foreach(str_split($s) as $char)
			{
				$pos = strpos(self::CachCharset, $char);
				if($pos === FALSE)
				{
					throw new InvalidAddress('Not base32-encoded');
				}
				$b32_data[] = $pos;
			}

			$bit_count = 0;
			$current = 0;
			$b256_data = [];
			foreach($b32_data as $i5)
			{
				$next_bit_count = $bit_count + 5;
				$missing_bits = 8 - $bit_count;
				$overflow_bits = $next_bit_count - 8;

				if($overflow_bits <= 0)
				{
					$current = ($current << 5) + $i5;
					$bit_count += 5;
					if($bit_count === 8)
					{
						$b256_data[] = $current;
						$current = 0;
						$bit_count = 0;
					}
					continue;
				}
				$b256_data[] = ($current << $missing_bits) + ($i5 >> $overflow_bits);
				$current = $i5 & ((1 << $overflow_bits) - 1);
				$bit_count = $overflow_bits;
			}

			return $b256_data;
		}

		public static function base32_encode($v)
		{
			// TODO
			return '';
		}

		/**
		 * @return CurveFpInterface
		 */
		public function getCurve() : CurveFpInterface
		{
			return (new \Mdanter\Ecc\Curves\SecgCurve(MathAdapterFactory::getAdapter()))->curve256k1();
		}

		/**
		 * @return PointInterface
		 */
		public function getPoint() : PointInterface
		{
			// TODO: Implement getPoint() method.
		}

		/**
		 * @return GeneratorPoint
		 * @throws \RuntimeException
		 */
		public function getGenerator() : GeneratorPoint
		{
			return CurveFactory::getGeneratorByName('secp256k1');
		}
	}