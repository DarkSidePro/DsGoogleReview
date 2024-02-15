<?php

namespace DarkSide\DsGoogleReview\Service;

use Configuration;
use DarkSide\DsGoogleReview\Form\DsGoogleReviewsConfiguration;
use DateTime;
use Db;
use DbQuery;
use PrestaShopModuleException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Accepted options
 * $options = array(
 * 'google_reviews_sorting' => 'most_relevant',       // reviews are sorted by relevance (default), or in chronological order (most_relevant/newest)
 * 'cache_data_xdays_local' => 30,       // every x day the reviews are loaded from google (save API traffic)
 * 'your_language_for_tran' => 'pl',     // give you language for auto translate reviews
 * 'show_not_more_than_max' => 5,        // (0-5) only show first x reviews
 * 'show_only_if_with_text' => false,    // true = show only reviews that have text
 * 'show_only_if_greater_x' => 0,        // (0-4) only show reviews with more than x stars
 * 'sort_reviews_by_a_data' => 'rating', // sort by 'time' or by 'rating' (newest/best first)
 * 'show_cname_as_headline' => true,     // true = show customer name as headline
 * 'show_stars_in_headline' => true,     // true = show customer stars after name in headline
 * 'show_author_avatar_img' => true,     // true = show the author avatar image (rounded)
 * 'show_blank_star_till_5' => true,     // false = don't show always 5 stars e.g. ⭐⭐⭐☆☆
 * 'show_txt_of_the_review' => true,     // true = show the text of each review
 * 'show_author_of_reviews' => true,     // true = show the author of each review
 * 'show_age_of_the_review' => true,     // true = show the age of each review
 * 'dateformat_for_the_age' => 'Y.m.d',  // see https://www.php.net/manual/en/datetime.format.php
 * 'show_rule_after_review' => true,     // false = don't show <hr> Tag after each review (and before first)
 * 'add_schemaorg_metadata' => true,     // add schemo.org data to loop back your rating to SERP
 * );
 */
class DsGoogleReviewService
{
    private string $dsGooglePlaceId;
    private string $dsGoogleApiKey;
    private string $dsReviewCacheKey;
    private array $option;
    private CONST URL = 'https://maps.googleapis.com/maps/api/place/details/json';


    public function __construct(array $option)
    {
        $this->dsGooglePlaceId = DsGoogleReviewsConfiguration::DS_GOOGLE_PLACE_ID;
        $this->dsGoogleApiKey = DsGoogleReviewsConfiguration::DS_GOOGLE_API_KEY;
        $this->dsReviewCacheKey = DsGoogleReviewsConfiguration::DS_REVIEW_CACHE;
        $this->option = array(
            'google_reviews_sorting' => 'most_relevant',  // reviews are sorted by relevance (default), or in chronological order (most_relevant/newest)
            'cache_data_xdays_local' => 7,       // every x day the reviews are loaded from google (save API traffic)
            'your_language_for_tran' => 'pl',     // give you language for auto translate reviews
            'show_not_more_than_max' => 5,        // (0-5) only show first x reviews
            'show_only_if_with_text' => false,    // true = show only reviews that have text
            'show_only_if_greater_x' => 0,        // (0-4) only show reviews with more than x stars
            'sort_reviews_by_a_data' => 'rating', // sort by 'time' or by 'rating' (newest/best first)
            'show_cname_as_headline' => true,     // true = show customer name as headline
            'show_stars_in_headline' => true,     // true = show customer stars after name in headline
            'show_author_avatar_img' => true,     // true = show the author avatar image (rounded)
            'show_blank_star_till_5' => true,     // false = don't show always 5 stars e.g. ⭐⭐⭐☆☆
            'show_txt_of_the_review' => true,     // true = show the text of each review
            'show_author_of_reviews' => true,     // true = show the author of each review
            'show_age_of_the_review' => true,     // true = show the age of each review
            'dateformat_for_the_age' => 'Y.m.d',  // see https://www.php.net/manual/en/datetime.format.php
            'show_rule_after_review' => true,     // false = don't show <hr> Tag after each review (and before first)
            'add_schemaorg_metadata' => true,     // add schemo.org data to loop back your rating to SERP
          );
    }

    public function fetchAndCacheReviews()
    {
        $reviewsJson = $this->getReviewsConnection();
        $reviewsArray = $this->decodeJSON($reviewsJson);

        dd($reviewsArray);

        if (isset($this->option['show_only_if_with_text']) && $this->option['show_only_if_with_text'] === true) {

        }

        return $this->saveReviewCache($reviewsArray);
    }

    public function saveReviewCache(array $data): void
    {
        $jsonEncoder = new JsonEncoder();
        $jsonReviews = $jsonEncoder->encode($data, 'json');

        $reviewCache = $this->getReviewCache();
        $dateUpd = new DateTime($reviewCache['date_upd']);

        if (isset($this->option['cache_data_xdays_local'])) {
            $dateUpd->modify('+' . $this->option['cache_data_xdays_local'] . ' days');
        }

        if (isset($this->option['your_language_for_tran']))


        if (new DateTime() > $dateUpd) {
            Configuration::updateValue($this->dsReviewCacheKey, $jsonReviews);
        }
    }

    public function getReviewCache(): array
    {
        $query = new DbQuery();
        $query->select('value, date_upd');
        $query->from('configuration');
        $query->where("name = '" . pSQL($this->dsReviewCacheKey) . "'");

        try {
            $result = Db::getInstance()->executeS($query);
            $value = $result[0]['value'];
            $date_upd = $result[0]['date_upd'];

            return ['date_upd' => $date_upd, 'value' => $value];
        } catch (PrestaShopModuleException $e) {
            throw new PrestaShopModuleException('Missing data for ' . $this->dsReviewCacheKey);
        }
    }

    private function decodeJSON(string $json): array
    {
        $jsonEncoder = new JsonEncoder();
        
        $array = $jsonEncoder->decode($json, 'json');

        return $array;
    }

    private function getReviewsConnection()
    {
        $client = HttpClient::create();

        $queryParams = [
            'place_id' => $this->dsGooglePlaceId,
            'reviews_sort' => $this->option['google_reviews_sorting'],
            'key' => $this->dsGoogleApiKey
        ];

        try {
            $response = $client->request(Request::METHOD_GET, self::URL, [
                'query' => $queryParams,
                'headers' => [
                    'Accept-Language' => $this->option['your_language_for_tran'],
                ],
            ]);
            $content = $response->getContent();

            return $content;
        } catch (PrestaShopModuleException $e) {
            throw $e;
        }
    }
}
