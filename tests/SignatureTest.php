<?php

	namespace Puggan\CashID;

	use Mdanter\Ecc\Crypto\Signature\SignatureInterface;
	use PHPUnit\Framework\TestCase;

	class SignatureTest extends TestCase
	{
		public const test_signature = 'IH/n/GjNtS/BFM2acFvVFcDSPrAWVptDlirlLAjvdszgL5wqVD2JbojBObyA28S6KQy5abfGuqRVtR0Z8xLXVHs=';
		public function test__construct()
		{
			$signature = new Signature(self::test_signature);
			$this->assertInstanceOf(SignatureInterface::class, $signature);
		}
	}
