<?php

namespace UVDesk\CommunityPackages\UVDesk\ECommerce\Utils\Api\Admin\Webhook;

/**
 * Read More: https://help.shopify.com/en/api/reference/events/webhook
 */
abstract class Webhook
{
    public static function get($shop_domain, $access_token)
    {
        $curlHandler = curl_init("https://$shop_domain/admin/webhooks.json");

        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ["X-Shopify-Access-Token: " . $access_token]);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        return !empty($jsonResponse['webhooks']) ? $jsonResponse['webhooks'] : [];
    }

    public static function post($shop_domain, $access_token, $event_topic, $webhook_url, $format_type = 'json')
    {
        $curlHandler = curl_init("https://$shop_domain/admin/webhooks.json");
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ["X-Shopify-Access-Token: " . $access_token]);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query([
            'webhook' => [
                'topic' => $event_topic,
                'format' => $format_type,
                'address' => $webhook_url,
            ]
        ]));

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);
    }

    public static function delete($shop_domain, $access_token, $webhook_id)
    {
        $curlHandler = curl_init("https://$shop_domain/admin/webhooks/$webhook_id.json");
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ["X-Shopify-Access-Token: " . $access_token]);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        return $jsonResponse;
    }
}
