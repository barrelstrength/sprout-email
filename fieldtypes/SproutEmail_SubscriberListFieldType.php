<?php
namespace Craft;

class SproutEmail_SubscriberListFieldType extends BaseFieldType
{
  /**
   * Field Type name
   *
   * @return string
   */
  public function getName()
  {
    return Craft::t('Subscriber List');
  }

  /**
   * Define database column
   *
   * @return false
   */
  public function defineContentAttribute()
  {
    // field type doesn’t need its own column
    // in the content table, return false
    return false;
  }

}
