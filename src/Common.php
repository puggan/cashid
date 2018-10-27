<?php

	namespace Puggan\CashID;

	use Puggan\CashID\Exceptions\Message;

	class Common
	{
		/**
		 * base64url encode string RFC 4648 §5
		 * @param string $s
		 *
		 * @return string
		 */
		public static function base64url_encode(string $s) : string
		{
			return strtr(
				base64_encode($s),
				[
					'+' => '-',
					'/' => '_',
				]
			);
		}

		/**
		 * base64url decode string RFC 4648 §5
		 * @param string $s
		 *
		 * @return string
		 */
		public static function base64url_decode(string $s) : string
		{
			return strtr(
				base64_decode($s),
				[
					'-' => '+',
					'_' => '/',
				]
			);
		}

		/**
		 * @param int $length
		 *
		 * @return string base64url encoeded random data
		 * @throws \Exception from random_bytes()
		 */
		public static function nonnce(int $length = 32) : string
		{
			return substr(random_bytes(ceil($length*2/3)), 0, $length);
		}
	}
