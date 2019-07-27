<?php

namespace UVDesk\CommunityPackages\UVDesk\ECommerce\Utils\Api\Admin\Oauth;

/**
 * Read More: https://help.shopify.com/en/api/getting-started/authentication/oauth#verification
 */
abstract class AccessToken
{
    public static function post($shop_domain, $auth_code, $client_id, $client_secret)
    {
        $curlHandler = curl_init("https://" . $shop_domain . "/admin/oauth/access_token");
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query(['code' => $auth_code, 'client_id' => $client_id, 'client_secret' => $client_secret]));
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        if (empty($jsonResponse['access_token']) || empty($jsonResponse['scope'])) {
            throw new \Exception('Unable to generate access token from code');
        }

        return ['token' => $jsonResponse['access_token'], 'scopes' => explode(',', $jsonResponse['scope'])];
    }
}
