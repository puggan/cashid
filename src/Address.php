<?php

	namespace Puggan\CashID;

	use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;
	use Puggan\CashID\Exceptions\InvalidAddress;

	/**
	 * Class Address
	 * @package Puggan\CashID
	 *
	 * @property int[] binary_address address in 8bit integers
	 */
	class Address
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

			if(\count($this->binary_address) !== $hash_length + 1)
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
		}

		/**
		 * @see https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md#checksum
		 *
		 * @param string $s complete string with checksum
		 *
		 * @return bool
		 * @throws InvalidAddress
		 */
		public static function validate_b32_checksum($s) : bool
		{
			$data = substr($s, 0, -8);
			$checksum = substr($s, -8);

			return self::b32_checksum($data) === $checksum;
		}

		/**
		 * @see https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md#checksum
		 *
		 * @param string $s complete string to checksum
		 *
		 * @return string
		 * @throws InvalidAddress
		 */
		public static function b32_checksum($s) : string
		{
			$i5s = [];
			foreach($s ? str_split($s) : [] as $char)
			{
				$v = strpos(self::CachCharset, $char);
				if($v === FALSE)
				{
					throw new InvalidAddress('Not base32-encoded');
				}
				$i5s[] = $v;
			}

			// start with one
			$c = 1;
			foreach($i5s as $i5)
			{
				// shift off overflow (40-5=35)
				$c0 = $c >> 35;
				// shift in new bits
				$c = (($c & 0x07ffffffff) << 5) ^ $i5;

				// Apply xor with a constant for each of the 5 overflow bits
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

			// xor away the one we started with
			$c ^= 1;

			$checksum = '';
			foreach(range(0,7) as $position)
			{
				$current = $c & 0x1F;
				$c >>= 5;
				$checksum = self::CachCharset[$current] . $checksum;
			}

			return $checksum;
		}

		/**
		 * @see https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md#checksum
		 *
		 * @param string $s part after prefix:
		 *
		 * @return bool
		 * @throws InvalidAddress
		 */
		public static function validate_bitcoincash_checksum($s) : bool
		{
			$data = substr($s, 0, -8);
			$checksum = substr($s, -8);

			return self::bitcoincash_checksum($data) === $checksum;
		}

		/**
		 * @see https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md#checksum
		 *
		 * @param string $s part after prefix:
		 * @param string $prefix the base32 representation of the prfix, 'zf5r0fwrpngq' for 'bitcoincash:'
		 * @param string $sufix the zerofilled placeholder for the checksum
		 *
		 * @return string
		 * @throws InvalidAddress
		 */
		public static function bitcoincash_checksum($s, $prefix = 'zf5r0fwrpngq', $sufix = 'qqqqqqqq') : string
		{
			return self::b32_checksum($prefix . $s . $sufix);
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

			if(!preg_match('#^[' . self::CachCharset . ']+$#', $s))
			{
				throw new InvalidAddress('Not base32-encoded');
			}

			if(!self::validate_bitcoincash_checksum($s))
			{
				throw new InvalidAddress('Checksum error');
			}

			return new self(self::base32_decode(substr($s, 0, -8)));
		}

		/**
		 *
		 * @param PublicKeyInterface $key
		 *
		 * @return Address
		 * @throws InvalidAddress
		 */
		public static function fromPublicKey(PublicKeyInterface $key, $compressed = true) : Address
		{
			$point = $key->getPoint();
			$x = str_pad(gmp_export($point->getX()), 32, \chr(0));
			if($compressed)
			{
				if((int) gmp_mod($point->getY(), 2))
				{
					$pre_hash = \chr(3) . $x;
				}
				else
				{
					$pre_hash = \chr(2) . $x;
				}
			}
			else
			{
				$y = str_pad(gmp_export($point->getY()), 32, \chr(0));
				$pre_hash = \chr(4) . $x . $y;
			}
			$hash = hash('ripemd160', hash('sha256', $pre_hash, true), true);
			$data = [0];
			foreach(range(0, 19) as $pos)
			{
				$data[] = \ord($hash[$pos]);
			}
			return new self($data);
		}

		/**
		 * @return string
		 * @throws InvalidAddress
		 */
		public function toCashAddr() : string
		{
			$data = self::base32_encode($this->binary_address);
			return 'bitcoincash:' . $data . self::bitcoincash_checksum($data);
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

		/**
		 * @param int[] $v
		 *
		 * @return string
		 * @throws InvalidAddress
		 */
		public static function base32_encode($i8s) : string
		{
			$data = '';
			$current = 0;
			$current_bits = 0;
			foreach($i8s as $i8)
			{
				$current = ($current << 8) + $i8;
				$current_bits += 8;
				while($current_bits >= 5)
				{
					$current_bits -= 5;
					$wanted = $current >> $current_bits;
					$current -= $wanted << $current_bits;
					$data .= self::CachCharset[$wanted];
				}
			}

			if($current_bits > 0)
			{
				$current <<= (5 - $current_bits);
				$data .= self::CachCharset[$current];
			}

			return $data;
		}
	}
