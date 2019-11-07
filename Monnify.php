<?php

class Monnify
{
    private $apiEndpoint = 'https://sandbox.monnify.com/api/v1';
    private $username = '';
    private $password = '';
    private $accessToken = null;
    private $tokenExpiry = 0;
	
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
   
    private function encodeCredentials()
    {
        return base64_encode("$this->username:$this->password");
    }
  
    public function auth()
    {
        $headers = [
            "Authorization: Basic " . $this->encodeCredentials()
        ];
        $validateAuth = $this->curl('auth/login', 'POST', $headers);
        if ($validateAuth && $validateAuth->responseMessage === "success") {
            $this->accessToken = $validateAuth->responseBody->accessToken;
            $this->tokenExpiry = $validateAuth->responseBody->expiresIn;
            return $this;
        }
        return false;
    }
   
    public function refreshToken()
    {
        if ($this->isTokenValid()) {
            $token = $this->accessToken;
        } else {
            $token = $this->auth()->responseBody->accessToken;
        }
        return $token;
    }
   
    public function getAccessToken()
    {
        return $this->accessToken;
    }
   
    public function getTokenExpiry()
    {
        return $this->tokenExpiry;
    }
  
    public function isTokenValid()
    {
        return ($this->accessToken && time() < $this->tokenExpiry) ? true : false;
    }
 
    private function curl($url, $requestType = 'POST',$headers = [], $body = [])
    {
		$soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $this->apiEndpoint . '/' . $url);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 30000000);
        curl_setopt($soap_do, CURLOPT_TIMEOUT, 30000000);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, $requestType);
        curl_setopt($soap_do, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($soap_do, CURLOPT_VERBOSE, TRUE);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($soap_do);
		$err = curl_error($soap_do);
		curl_close($soap_do);
		if($err){
			return json_encode($err);
		}
        return json_decode($result);
    }
    
    public function reserveAccount(array $body)
    {
		//set request headers
		$header = array(
            "Content-Type: application/json; charset=utf-8",
			"Authorization:Bearer $this->refreshToken()"
			);
		//send request
        $response = $this->curl('bank-transfer/reserved-accounts', 'POST', $header, $body);
        if ($response->requestSuccessful) {
            return $response->responseBody;
        }
        return json_encode(["error" => "Request was unsuccessful."]);
    }
  
    public function getTransStatus(string $reference)
    {
		//set request headers
        $header = array(
            "Content-Type: application/json; charset=utf-8",
			"Authorization:Bearer $this->refreshToken()"
			);
        $param = [
            "paymentReference" => $reference
        ];
		//send request
        $response = $this->curl('merchant/transactions/query', 'GET', $header, $param);
        if ($response->requestSuccessful) {
            return $response->responseBody;
        }
        return json_encode(["error" => "Request was unsuccessful."]);
    }
  
    public function DeleteAReservedAccount($accountNumber)
    {
		//set request headers
        $header = array(
            "Content-Type: application/json; charset=utf-8",
			"Authorization:Bearer $this->refreshToken()"
			);
		//send request
        $response = $this->curl("bank-transfer/reserved-accounts/$accountNumber", 'DELETE', $header);
        if ($response->requestSuccessful) {
            return $response->responseBody;
        }
        return json_encode(["error" => "Request was unsuccessful."]);
    }
}