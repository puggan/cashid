<?php

	namespace Tests\Puggan\CashID;

	use Mdanter\Ecc\Crypto\Signature\Signer;
	use Mdanter\Ecc\EccFactory;
	use Puggan\CashID\Address;
	use Puggan\CashID\Message;
	use PHPUnit\Framework\TestCase;
	use Puggan\CashID\Signature;

	class MessageTest extends TestCase
	{
		public const test_message = 'cashid://ssl.puggan.se/echo/log.php?x=puggan';
		public const test_address = 'qzysvu7h4knpwnmej2wc255mh99m4l9fev5lzg02vj';
		public const test_signature = 'IH/n/GjNtS/BFM2acFvVFcDSPrAWVptDlirlLAjvdszgL5wqVD2JbojBObyA28S6KQy5abfGuqRVtR0Z8xLXVHs=';

		public function testMagic_hash() : void
		{
			$expected = hex2bin(
				implode(
					'',
					[
						'a7',
						'af',
						'0b',
						'aa',
						'd5',
						'ae',
						'99',
						'b9',
						'7f',
						'c6',
						'9b',
						'3a',
						'0d',
						'1a',
						'bc',
						'f3',
						'ef',
						'17',
						'f1',
						'31',
						'cc',
						'47',
						'76',
						'e1',
						'bc',
						'11',
						'93',
						'3e',
						'c8',
						'55',
						'0f',
						'49',
					]
				)
			);
			$message = new Message('Hello World');
			$this->assertEquals($expected, $message->magic_hash());
		}

		/**
		 * @throws \LogicException
		 * @throws \PHPUnit\Framework\ExpectationFailedException
		 * @throws \Puggan\CashID\Exceptions\InvalidAddress
		 * @throws \Puggan\CashID\Exceptions\InvalidSignature
		 * @throws \RuntimeException
		 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
		 */
		public function testGet_public_key() : void
		{
			$message = new Message(self::test_message);
			$address = Address::fromCashAddr(self::test_address);
			$signature = new Signature(self::test_signature);

			$public_key = $message->get_public_key($signature);
			// $this->assertFalse(empty($public_key));

			$ecdsa = new Signer(EccFactory::getAdapter());
			$this->assertTrue($ecdsa->verify($public_key, $signature, $message->hash()));
		}
	}
