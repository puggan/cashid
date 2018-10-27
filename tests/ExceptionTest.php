<?php

	namespace Tests\Puggan\CashID;

	use PHPUnit\Framework\TestCase;
	use Puggan\CashID\Exception;
	use Puggan\CashID\Exceptions\InvalidAddress;
	use Puggan\CashID\Exceptions\InvalidRequest;
	use Puggan\CashID\Exceptions\InvalidSignature;

	class ExceptionTest extends TestCase
	{
		/**
		 * @throws \PHPUnit\Framework\ExpectationFailedException
		 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
		 */
		function testThrowable()
		{
			$classes = [
				InvalidAddress::class,
				InvalidRequest::class,
				InvalidSignature::class,
			];

			foreach($classes as $exception_class)
			{
				$e = NULL;
				try
				{
					throw new $exception_class('phpunit test');
				}
				catch(Exception $e)
				{

				}
				$this->assertInstanceOf(\Throwable::class, $e);
			}
		}
	}
