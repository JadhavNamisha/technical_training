<?php

namespace Drupal\twitter_card\Helper;

class MetaTagExtractor {
  public function getMetaDataFromText(string $text): array {
    $regex_URL = '/(http|https)\:\/\/[a-zA-Z0-9\-\.]+web\.ahdev\.cloud+(\/\S*)?/';
    preg_match($regex_URL, $text, $urls);
    $first_link = substr($urls[0], 0, strpos($urls[0], '"'));
    $cache = \Drupal::cache('my_cache_bin')->get($first_link);
    $meta_tags = [];
    if($cache->data) {
      $meta_tags = $cache->data;
    } else {
      $meta_tags = get_meta_tags($first_link);
      \Drupal::cache('my_cache_bin')->set($first_link, $meta_tags);
    }

    return [
      'desc' => $meta_tags['acquia:description'],
      'title' => $meta_tags['acquia:acquia'],
      'image' => $meta_tags['acquia:image'],
    ];
  }
}
