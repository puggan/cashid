<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-10-27
	 * Time: 12:09
	 */

	namespace Tests\Puggan\CashID;

	use PHPUnit\Framework\TestCase;
	use Puggan\CashID\Address;
	use Puggan\CashID\Exceptions\InvalidAddress;
	use Puggan\CashID\Message;

	class AddressTest extends TestCase
	{
		public function testBase32_decode()
		{
			$this->assertEquals(
				'Hello',
				Message::i8s2bin(
					Address::base32_decode('fpjkcmr0')

				)
			);
		}

		public function testBase32_encode()
		{
			return;
			$this->assertEquals(
				'fpjkcmr0',
				Address::base32_encode('Hello')
			);
		}

		public function testFromCashAddr()
		{
			$this->expectException(InvalidAddress::class);
			Address::fromCashAddr('test');
		}

		public function testToCashAddr()
		{
			return;
			$address_string = 'bitcoincash:qzysvu7h4knpwnmej2wc255mh99m4l9fev5lzg02vj';
			$address = Address::fromCashAddr($address_string);
			$this->assertEquals($address_string, $address->toCashAddr());
		}
	}
