<?php

	namespace Puggan\CashID;

	use PhpDoc\parse_url_result;
	use Puggan\CashID\Exceptions\InvalidRequest;
	use Puggan\CashID\Exceptions\InvalidSignature;

	/**
	 * Class Request
	 * @package Puggan\CashID
	 *
	 * @property string $api_paths Api paths
	 * @property string $nonce
	 */
	class Request
	{
		public $api_paths;
		public $nonce;

		/**
		 * @return Request
		 * @throws \Exception from random_bytes
		 */
		public static function create() : Request
		{
			$request = new self();
			$request->nonce = Common::nonnce();
			return $request;
		}

		/**
		 * Get the url pars of the request
		 *
		 * @return parse_url_result
		 */
		public function url_parts() : object
		{
			/** @var parse_url_result $path_parts */
			$path_parts = (object) parse_url($this->api_paths);
			$path_parts->scheme = 'cashid';
			$path_parts->query = ($path_parts->query ? $path_parts->query . '&x=': 'x=') . $this->nonce;
			return $path_parts;
		}

		/**
		 * Get the url of the request
		 *
		 * @return string
		 */
		public function url() : string
		{
			$path_parts = $this->url_parts();
			$url = $path_parts->scheme . ':';
			if($path_parts->user)
			{
				$url .= $path_parts->user;
				if($path_parts->pass)
				{
					$url .= ':' . $path_parts->pass;
				}
				$url .= '@';
			}
			$url .= $path_parts->host;
			if($path_parts->port)
			{
				$url .= ':' . $path_parts->port;
			}

			return $url . $path_parts->path . '?' . $path_parts->query;
		}

		/**
		 * @param string $json_string
		 *
		 * @return Response
		 * @throws InvalidRequest
		 */
		public function validate_string($json_string) : Response
		{
			return $this->validate(json_decode($json_string));
		}

		/**
		 * @param \PhpDoc\authenticatoin_json|object $json
		 *
		 * @return Response
		 * @throws InvalidRequest
		 * @throws InvalidSignature
		 */
		public function validate($json) : Response
		{
			if(!$json)
			{
				throw new InvalidRequest('Failed to parse JSON');
			}

			if(empty($json->request))
			{
				throw new InvalidRequest('Failed to parse JSON->request');
			}

			if(empty($json->address))
			{
				throw new InvalidRequest('Failed to parse JSON->address');
			}

			if(empty($json->signature))
			{
				throw new InvalidRequest('Failed to parse JSON->signature');
			}

			if($json->request !== $this->url())
			{
				throw new InvalidRequest('Invalid JSON->request');
			}

			if(!Common::valid_adress($json->address))
			{
				throw new InvalidRequest('Invalid JSON->address');
			}

			if(!Common::valid_signature($json->address, $json->request, $json->signature))
			{
				throw new InvalidSignature('Invalid JSON->address');
			}

			return new Response($json->address, $json->metadata ?? null);
		}
	}
