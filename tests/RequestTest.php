<?php

	namespace Tests\Puggan\CashID;

	use PHPUnit\Framework\TestCase;
	use Puggan\CashID\Request;

	class RequestTest extends TestCase
	{

		public function testValidate_string()
		{
			$json_string = '{"request":"cashid://ssl.puggan.se/echo/log.php?x=puggan","address":"qzysvu7h4knpwnmej2wc255mh99m4l9fev5lzg02vj","signature":"IH/n/GjNtS/BFM2acFvVFcDSPrAWVptDlirlLAjvdszgL5wqVD2JbojBObyA28S6KQy5abfGuqRVtR0Z8xLXVHs=","metadata":{}}';
			/** @var \PhpDoc\authenticatoin_json $json_object */
			$json_object = json_decode($json_string);
			$url = strstr($json_object->request, '?', true);
			$request = Request::create($url, 'puggan');
			$this->assertEquals($json_object->request, $request->url(true));

			$response = $request->validate($json_object);
			$this->assertEquals($json_object->address, $response->address);

			$response = $request->validate_string($json_string);
			$this->assertEquals($json_object->address, $response->address);
		}

		public function testCreate()
		{
			$domain = 'domain.tld';
			$path = '/path?a=b';
			$api_path = '//' . $domain . $path;
			$request = Request::create($api_path);
			$expected = 'cashid:' . $domain . $path . '&x='. $request->nonce;
			$this->assertEquals($expected, $request->url());

			$domain = 'a.domain.tld';
			$path = '/path';
			$api_path = 'https://' . $domain . $path;
			$request = Request::create($api_path);
			$expected = 'cashid:' . $domain . $path . '?x='. $request->nonce;
			$this->assertEquals($expected, $request->url());
		}
	}
