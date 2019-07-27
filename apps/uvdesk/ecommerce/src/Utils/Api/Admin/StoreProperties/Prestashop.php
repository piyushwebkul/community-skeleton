<?php

namespace UVDesk\CommunityPackages\UVDesk\ECommerce\Utils\Api\Admin\StoreProperties;

/**
 * Read More: https://help.shopify.com/en/api/getting-started/authentication/oauth#verification
 */
abstract class Prestashop
{
    public static function get($shop_domain, $api_key)
    {   
        $url = "http://" . $api_key . "@$shop_domain/api/";
        $finalResponse = [];

        try {
            $curlHandler = curl_init();
            
            curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandler, CURLOPT_URL, $url . "shops/1?output_format=JSON");

            $jsonResponse = json_decode(curl_exec($curlHandler), true);
            $finalResponse = array_merge($finalResponse, $jsonResponse['shop']);
            
            // Currency start
            curl_setopt($curlHandler, CURLOPT_URL, $url . "configurations?filter[name]=PS_CURRENCY_DEFAULT&output_format=JSON");
            $jsonResponse = json_decode(curl_exec($curlHandler), true);
            
            curl_setopt($curlHandler, CURLOPT_URL, $url . "configurations/{$jsonResponse['configurations'][0]['id']}?output_format=JSON");
            $jsonResponse = json_decode(curl_exec($curlHandler), true);
            
            curl_setopt($curlHandler, CURLOPT_URL, $url . "currencies/{$jsonResponse['configuration']['value']}?output_format=JSON");
            $jsonResponse = json_decode(curl_exec($curlHandler), true);
            
            $finalResponse = array_merge($finalResponse, $jsonResponse);
            //Currency end
            
            // Timezone start
            curl_setopt($curlHandler, CURLOPT_URL, $url . "configurations?filter[name]=PS_TIMEZONE&output_format=JSON");
            $jsonResponse = json_decode(curl_exec($curlHandler), true);
            
            curl_setopt($curlHandler, CURLOPT_URL, $url . "configurations/{$jsonResponse['configurations'][0]['id']}?output_format=JSON");
            $jsonResponse = json_decode(curl_exec($curlHandler), true);
            

            $finalResponse['timezone'] = $jsonResponse['configuration'];
            //timezone end

        } catch (\Exception $e) {
            throw new \Exception('Unable to retrieve store details');
        }

        return $finalResponse;
    }
}
