<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Jwt {
    private $secretKey = 'secret';
    private $algorithm = 'HS256';
    private $expiration = 604800;

    public function __construct() {
        $this->secretKey = config_item('jwt_secret_key') ?: $this->secretKey;
        $this->expiration = config_item('jwt_expiration') ?: $this->expiration;
    }

    public function create($payload = array()){
        $header = array(
            'typ' => 'JWT',
            'alg' => $this->algorithm
        );

        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiration;

        $headerEncoded = $this->base64_url_encode(json_encode($header));
        $payloadEncoded = $this->base64_url_encode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $this->secretKey,
            true 
        );

        $signatureEncoded = $this->base64_url_encode($signature);
        return $headerEncoded .  '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public function verify($token = ""){
        if(empty($token)){
            return false;
        }

        $parts = explode('.', $token);
        if(count($parts) !== 3){
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $this->secretKey,
            true
        );
        $signatureComputed = $this->base64_url_encode($signature);
        if($signatureComputed !== $signatureEncoded){
            return false;
        }

        $payload = json_decode($this->base64_url_decode($payloadEncoded));

        if(isset($payload->exp) && $payload->exp < time()){
            return false;
        }

        return $payload;
    }

    public function get_token_from_request()
    {
        $auth_header = null;

        // Method 1: Apache/nginx
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : null;
        }

        // Method 2: $_SERVER
        if (!$auth_header && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Method 3: Alternative header name
        if (!$auth_header && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!$auth_header) {
            return false;
        }

        // Extract token from "Bearer <token>"
        if (preg_match('/Bearer\s+(\S+)/', $auth_header, $matches)) {
            return $matches[1];
        }

        return false;
    }

    private function base64_url_encode($input)
    {
        return str_replace(
            array('+', '/', '='),
            array('-', '_', ''),
            base64_encode($input)
        );
    }

    private function base64_url_decode($input)
    {
        // Add padding if needed
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(
            str_replace(
                array('-', '_'),
                array('+', '/'),
                $input
            )
        );
    }
}