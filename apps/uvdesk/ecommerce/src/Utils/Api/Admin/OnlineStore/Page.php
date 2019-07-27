<?php

namespace UVDesk\CommunityPackages\UVDesk\ECommerce\Utils\Api\Admin\OnlineStore;

/**
 * Read More: https://help.shopify.com/en/api/reference/online-store/page
 */
abstract class Page
{
    public static function get($shop_domain, $access_token)
    {
        $curlHandler = curl_init('https://' . $shop_domain . '/admin/pages.json');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token:' . $access_token]);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        return !empty($jsonResponse['pages']) ? $jsonResponse['pages'] : [];
    }

    public static function post($shop_domain, $access_token, $page_title, $page_content, $is_published = true)
    {
        $curlHandler = curl_init('https://' . $shop_domain . '/admin/pages.json');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token:' . $access_token]);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query([
            'page' => [
                'title' => $page_title,
                'body_html' => $page_content,
                'published' => $is_published,
            ],
        ]));

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);
        curl_close($curlHandler);

        return !empty($jsonResponse['page']) ? $jsonResponse['page'] : [];
    }

    public static function delete($shop_domain, $access_token, $page_id)
    {
        $curlHandler = curl_init('https://' . $shop_domain . '/admin/pages/' . $page_id . '.json');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token:' . $access_token]);

        $curlResponse = curl_exec($curlHandler);
        curl_close($curlHandler);

        return null;
    }
}
