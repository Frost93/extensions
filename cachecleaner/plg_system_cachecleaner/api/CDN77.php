<?php

/*
 * Library for the KeyCDN API
 *
 * @author Tobias Moser
 * @version 0.1
 *
 */

class CDN77
{
	public $login;
	public $passwd;
	public $api = 'https://api.cdn77.com/v2.0/data/purge-all';

	public function __construct($login, $passwd)
	{
		$this->login  = $login;
		$this->passwd = $passwd;
	}

	public function purge($id)
	{
		$params = array(
			'login'  => $this->login,
			'passwd' => $this->passwd,
			'cdn_id' => $id,
		);

		// start with curl and prepare accordingly
		$ch = curl_init();

		// send query-str within url or in post-fields
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

		// url
		curl_setopt($ch, CURLOPT_URL, $this->api);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// retrieve headers
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

		// set curl timeout
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);

		// make the request
		$result     = curl_exec($ch);
		$headers    = curl_getinfo($ch);
		$curl_error = curl_error($ch);

		curl_close($ch);

		// get json_output out of result (remove headers)
		$json_output = substr($result, $headers['header_size']);

		// error catching
		if (!empty($curl_error) || empty($json_output))
		{
			return 'CDN77-Error: ' . $curl_error . ', Output: ' . $json_output;
		}

		return $json_output;
	}
}
