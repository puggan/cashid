<?php

	namespace Tests\Puggan\CashID;

	use Puggan\CashID\Message;
	use PHPUnit\Framework\TestCase;

	class MessageTest extends TestCase
	{
		public function testMagic_hash()
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
	}
