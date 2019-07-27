<?php

namespace UVDesk\CommunityPackages\UVDesk\ECommerce\Utils\Api\Admin\Oauth;

/**
 * Read More: https://help.shopify.com/en/api/reference/access/accessscope
 */
abstract class AccessScope
{
    public static function get($shopDomain, $accessToken)
    {
        $curlHandler = curl_init('https://' . $shopDomain . '/admin/oauth/access_scopes.json');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ["X-Shopify-Access-Token: " . $accessToken]);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        if (empty($jsonResponse['access_scopes'])) {
            throw new \Exception('Unable to retrieve store details');
        }

        return $jsonResponse['access_scopes'];
    }
}
