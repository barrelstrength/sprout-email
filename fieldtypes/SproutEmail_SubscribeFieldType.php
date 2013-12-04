<?php
namespace Craft;

class SproutEmail_SubscribeFieldType extends BaseFieldType
{
  /**
   * Blocktype name
   *
   * @return string
   */
  public function getName()
  {
    return Craft::t('List');
  }

  /**
   * Define database column
   *
   * @return false
   */
  public function defineContentAttribute()
  {
    // block type doesn’t need its own column
    // in the content table, return false
    return false;
  }

  /**
   * Define blocktype settings
   *
   * @return array List of our available styles
   */
  protected function defineSettings()
  {

      // $groups = craft()->colonelCategory->getAllGroups();

      // $groupsArray = array();
      // foreach ($groups as $key => $value) {
      //   $groupsArray[$value->id] = $value->name;
      // }

      // return array(
      //     'categoryGroups' => array(
      //         AttributeType::Mixed,
      //         'default' => $groupsArray
      //     )
      // );
  }

  /**
   * Display our settings
   *
   * @return string Returns the template which displays our settings
   */
  public function getSettingsHtml()
  {
      // $label          = Craft::t('Select Category Group...');
      // $name           = Craft::t('categoryGroup');
      // $instructions   = Craft::t('Select the category group you want your field to show.');

      // $selectedSetting = '';

      // return craft()->templates->render('colonelcategory/_fields/settings', array(
      //     'label'         => $label,
      //     'name'          => $name,
      //     'instructions'  => $instructions,
      //     'settings'      => $this->getSettings(),
      //     'value'         => $selectedSetting
      // ));
  }

  public function onAfterElementSave()
  {
    // // Determine our entryId
    // $entryId = (isset($_POST['entryId']))
    //     ? $_POST['entryId']
    //     : $this->element->id;

    // // Grab all the Classification fields.
    // // @TODO - may have to rework how the field is named or how we retrieve it.  Currently this
    // // only works if no duplicate categories are added as fields.  I guess that needs to work
    // // as all categories must be global.  Perhaps we need to block a new category field from
    // // being added to a section if it already exists?
    // $selectedClassifications  = (isset($_POST['classification_fields'])) ? $_POST['classification_fields'] : array();

    // $selectedClassificationsModel = array();
    // foreach ($selectedClassifications as $key => $value) {
    //   array_push($selectedClassificationsModel, array(
    //     'elementId'         => $entryId,
    //     'classificationId'  => $value
    //   ));
    // }

    // $selectedClassifications = ColonelCategory_RelationshipsModel::populateModels($selectedClassificationsModel);
    // $existingClassifications = craft()->colonelCategory->getClassificationsByElementId($entryId);

    // // If we have selected items, let's sort out their database logic
    // // if we don't have any selected items, we can clean up and remove whatever used to exist
    // if (count($selectedClassifications) > 0)
    // {

    //   // @ TODO - consider how to make this more efficient
    //   // Currently, if we have any category relationships, we delete ALL previous relationships
    //   // and insert all of the selected relationships anew.

    //   // Remove ALL existing relationships
    //   foreach ($existingClassifications as $key => $value) {
    //     craft()->db->createCommand()
    //              ->delete('colonelcategory_relationships', 'id=:id', array(':id'=> $value->id));
    //   }

    //   // Add ALL selected relationships
    //   foreach ($selectedClassifications as $key => $value) {

    //     $attributes['elementId']         = $entryId;
    //     $attributes['classificationId']  = $value->classificationId;

    //     craft()->db->createCommand()
    //              ->insert('colonelcategory_relationships', $attributes);
    //   }

    // }
    // else
    // {

    //   // Remove ALL existing relationships
    //   foreach ($existingClassifications as $key => $value) {
    //     craft()->db->createCommand()
    //              ->delete('colonelcategory_relationships', 'id=:id', array(':id'=> $value->id));
    //   }
    // }

  }

  /**
   * Display our blocktype
   *
   * @param string $name  Our blocktype handle
   * @param string $value Always returns blank, our block
   *                       only styles the Instructions field
   * @return string Return our blocks input template
   */
  public function getInputHtml($name, $values)
  {
    // $entryId = craft()->request->getSegment(3);

    // $selectedGroupId = $this->getFieldSetting('categoryGroup');

    // $classifications = craft()->colonelCategory->getAllClassifications('category', $selectedGroupId);

    // $relationships = craft()->colonelCategory->getAllRelationships($entryId);

    // // Create our array of categories that will be passed to the CheckboxGroup macro
    // $classificationsArray = array();
    // foreach ($classifications as $key => $value) {
    //   array_push($classificationsArray, array(
    //     'label' => $value->name,
    //     'value' => $value->id
    //   ));
    // }

    // // Create our array of values which will be matched to the
    // // categoriesArray value in the CheckboxGroup macro
    // $relationshipsArray = array();
    // foreach ($relationships as $key => $value) {
    //   array_push($relationshipsArray, $value->classificationId);
    // }

    // return craft()->templates->render('colonelcategory/_fields/input', array(
    //   'name'        => $name,
    //   'categories'  => $classificationsArray,
    //   'values'      => $relationshipsArray
    // ));
  }

  /**
     * Get the blocktype settings for a particular block
     * @TODO  - Seems a bit roundabout, but this is how we roll for now.
     *
     * @param  string $blockSetting The setting name
     * @return string The value of the selected setting
     */
    public function getFieldSetting($fieldSetting)
    {
        $selectedSetting = "";

        // USE THIS WAY OF ACCESSING SETTINGS / getSettings() is contextual.
        // $fieldSettings = $this->getSettings()->getAttributes();

        // echo "<pre>";
        // print_r($fieldSettings['styleOptions']);
        // echo "</pre>";
        // die('fin');

        if (is_object($this->model)) {
            $attributes = $this->model->getAttributes();
            if (isset($attributes['settings'][$fieldSetting])) {
                $selectedSetting = $attributes['settings'][$fieldSetting];
            }
        }

        return $selectedSetting;
    }

}
