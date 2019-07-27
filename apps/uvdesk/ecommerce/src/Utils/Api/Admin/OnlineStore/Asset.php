<?php

namespace UVDesk\CommunityPackages\UVDesk\ECommerce\Utils\Api\Admin\OnlineStore;

/**
 * Read More: https://help.shopify.com/en/api/reference/online-store/asset#show-2019-07
 */
abstract class Asset
{
    public static function put($shop_domain, $access_token, $theme_id, $asset_key, $asset_value)
    {
        $curlHandler = curl_init('https://' . $shop_domain . '/admin/themes/' . $theme_id . '/assets.json');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token:' . $access_token]);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query(['asset' => ['key' => $asset_key, 'value' => $asset_value]]));

        $curlResponse = curl_exec($curlHandler);
        $assestsResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        return !empty($jsonResponse['asset']) ? $jsonResponse['asset'] : [];
    }

    public static function all($shop_domain, $access_token)
    {
        $curlHandler = curl_init('https://' . $shop_domain . '/admin/themes.json');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token:' . $access_token]);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        return !empty($jsonResponse['themes']) ? $jsonResponse['themes'] : [];
    }

    public static function get($shop_domain, $access_token, $theme_id, $asset_key, $isExist, $old_asset_value, $new_asset_value, $isInject)
    {
        $curlHandler = curl_init('https://' . $shop_domain . '/admin/themes/' . $theme_id . '/assets.json?asset[key]=' . $asset_key . '&theme_id=' . $theme_id);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token:' . $access_token]);

        $curlResponse = curl_exec($curlHandler);
        $assestsResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        $asset = $assestsResponse['asset'];
        $asset_value = $asset['value'];

        $existing_position = strpos($asset_value, $isExist);
        
        $updated_asset_value = "";
        if(!$existing_position) {
            $last_position = strrpos($asset_value, $old_asset_value);
            $updated_asset_value = substr_replace($asset_value, $new_asset_value, $last_position, strlen($old_asset_value));
        } elseif (!$isInject) {
            $updated_asset_value = str_replace($old_asset_value, $new_asset_value, $asset_value);
        }

        return ($updated_asset_value == '') ? false : $updated_asset_value;
    }

    public static function delete($shop_domain, $access_token, $theme_id, $asset_key)
    {
        $curlHandler = curl_init('https://' . $shop_domain . '/admin/themes/' . $theme_id . '/assets.json?asset[key]=' . $asset_key);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token:' . $access_token]);

        $curlResponse = curl_exec($curlHandler);
        curl_close($curlHandler);
    }
}
