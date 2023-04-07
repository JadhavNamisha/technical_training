<?php

namespace Drupal\twitter_card\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "metatag_extractor",
 *   label = @Translation("Meta tag Extractor"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */

class MetaDataExtractor extends TextDefaultFormatter {

  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $elements = parent::viewElements($items, $langcode);

    $elements[] = [
      '#theme' => 'twitter_cards',
      '#twitter_data' => \Drupal::service('twitter_card.meta_tag_extractor')->getMetaDataFromText($items[0]->value),
    ];
    return $elements;
  }
}
