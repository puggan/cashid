<?php

	namespace Tests\Puggan\CashID;

	use PHPUnit\Framework\TestCase;
	use Puggan\CashID\Address;
	use Puggan\CashID\Exceptions\InvalidAddress;
	use Puggan\CashID\Message;

	class AddressTest extends TestCase
	{
		public const test_address = 'bitcoincash:qzysvu7h4knpwnmej2wc255mh99m4l9fev5lzg02vj';

		public function testBase32_decode() : void
		{
			$this->assertEquals(
				'Hello',
				Message::i8s2bin(
					Address::base32_decode('fpjkcmr0')

				)
			);
		}

		public function testBase32_encode() : void
		{
			$i8s = array_map(
				'ord',
				str_split('Hello')
			);

			$this->assertEquals(
				'fpjkcmr0',
				Address::base32_encode($i8s)
			);
		}

		public function testValidate_b32_checksum() : void
		{
			$valid_pairs = [
				'' => 'qqqqqqqq',
				'q' => 'qqqqqqpp',
				'p' => 'qqqqqqpq',
				'qq' => 'qqqqqpqp',
				'pq' => 'qqqqqppp',
				'qqq' => 'qqqqpqqp',
				'pqq' => 'qqqqppqp',
				'qqqq' => 'qqqpqqqp',
				'pqqq' => 'qqqppqqp',
				'qqqqq' => 'qqpqqqqp',
				'pqqqq' => 'qqppqqqp',
				'qqqqqq' => 'qpqqqqqp',
				'pqqqqq' => 'qppqqqqp',
				'qqqqqqq' => 'pqqqqqqp',
				'pqqqqqq' => 'ppqqqqqp',
			];

			foreach($valid_pairs as $data => $chercksum)
			{
				$this->assertEquals($chercksum, Address::b32_checksum($data));
				$this->assertTrue(Address::validate_b32_checksum($data . $chercksum));
			}
		}

		public function testValidate_bitcoincash_checksum() : void
		{
			$valid_data = [
				'qpm2qsznhks23z7629mms6s4cwef74vcwvy22gdx6a',
				'qr95sy3j9xwd2ap32xkykttr4cvcu7as4y0qverfuy',
				'qqq3728yw0y47sqn6l2na30mcw6zm78dzqre909m2r',
				'ppm2qsznhks23z7629mms6s4cwef74vcwvn0h829pq',
				'pr95sy3j9xwd2ap32xkykttr4cvcu7as4yc93ky28e',
				'pqq3728yw0y47sqn6l2na30mcw6zm78dzq5ucqzc37',
				'qzysvu7h4knpwnmej2wc255mh99m4l9fev5lzg02vj',
			];

			foreach($valid_data as $current)
			{
				$data = substr($current, 0, -8);
				$checksum = substr($current, -8);
				$this->assertEquals($checksum, Address::bitcoincash_checksum($data), 'Checksum of ' . $checksum . ':' . $data);
				$this->assertTrue(Address::validate_bitcoincash_checksum($current), 'Checksum of ' . $current);
			}
		}

		public function testFromCashAddr() : void
		{
			$this->expectException(InvalidAddress::class);
			Address::fromCashAddr('test');
		}

		public function testToCashAddr() : void
		{
			$address = Address::fromCashAddr(self::test_address);
			$this->assertEquals(self::test_address, $address->toCashAddr());
		}
	}
