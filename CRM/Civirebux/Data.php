<?php
/**
 * Provide static methods to retrieve and format Contribution data.
 */
class CRM_Civirebux_Data {
  protected static $fields = array();
  protected static $emptyRow = array();
  protected static $multiValues = array();

  /**
   * Return an array containing formatted Contributed data.
   * @function getContributionData 
   * @return array
   */
  public static function getContributionData() {
    self::$fields = self::getContributionFields();
    self::$emptyRow = self::getEmptyRow(true);
    self::$multiValues = array();
    $contributions = civicrm_api3('Contribution', 'get', array(
          'sequential' => 1,
          'api.Contribution.get' => array(),
          'return' => implode(',',array_keys(self::$fields)),
          'options' => array('sort' => 'id ASC', 'limit' => 0)
          ));

    return self::splitMultiValues(self::formatContributionResult($contributions['values']));
  }

  /**
   * Return an array containing formatted Membership data.
   * @function getMembershipData
   * @return array
   */
  public static function getMembershipData() {
    self::$fields = self::getMembershipFields();
    self::$emptyRow = self::getEmptyRow(false);
    self::$multiValues = array();
    $membershipinfo = civicrm_api3('Membership', 'get', array(
          'sequential' => 1,
          'api.Membership.get' => array(),
          'return' => implode(',',array_keys(self::$fields)),
          'options' => array('sort' => 'id ASC', 'limit' => 0)
          ));
    return self::splitMultiValues(self::formatMembershipResult($membershipinfo['values']));
  }

  /**
   * Return an array containing $data rows and each row containing multiple values of at least one field is populated into separate row for each field's
   * multiple value.
   * @function splitMultiValues 
   * @param array   $data   array containing a set of Contributions/Membership data
   * @return array
   */
  protected static function splitMultiValues(array $data) {
    $result = array();
    foreach ($data as $key => $row) {
      if (!empty(self::$multiValues[$key])) {
        $multiValuesFields = array_combine(self::$multiValues[$key], array_fill(0, count(self::$multiValues[$key]), 0));
        $result = array_merge($result, self::populateMultiValuesRow($row, $multiValuesFields));
      } else {
        $result[] = $row;
      }
    }
    return $result;
  }

  /**
   * Return an array containing set of rows which are built basing on given $row  and $fields array with indexes of multi values of the $row.
   * @function populateMultiValuesRow 
   * @param array   $row        a single record
   * @param array   $fields     array containing contribution/membership multi value fields as keys and integer indexes as values
   * @return array
   */
  protected static function populateMultiValuesRow(array $row, array $fields) {
    $result = array();
    $found = true;
    while ($found) {
      $rowResult = array();
      foreach ($fields as $key => $index) {
        $rowResult[$key] = $row[$key][$index];
      }
      $result[] = array_merge($row, $rowResult);
      foreach ($fields as $key => $index) {
        $found = false;
        if ($index + 1 === count($row[$key])) {
          $fields[$key] = 0;
          continue;
        }
        $fields[$key]++;
        $found = true;
        break;
      }
    }
    return $result;
  }

  /**
   * Return a result of recursively parsed and formatted $data.
   * @function formatContributionResult 
   * @param mixed   $data       data element
   * @param string  $dataKey    key of current $data item
   * @param int     $level      how deep we are relative to the root of our data
   * @return array  $result
   */
  protected static function formatContributionResult($data, $dataKey = null, $level = 0) {
    $result = array();
    if ($level < 2) {
      if ($level === 1) {
        $result = self::$emptyRow;
      }
      $baseKey = $dataKey;
      foreach ($data as $key => $value) {
        if (empty(self::$fields[$key]) && $level) {
          continue;
        }
        if ($level === 0 && empty($value['api.Contribution.get']['values'])) {
          continue;
        }
        $dataKey = $key;
        if (!empty(self::$fields[$key]['title'])) {
          $key = self::$fields[$key]['title'];
        }
        $result[$key] = self::formatContributionResult($value, $dataKey, $level + 1);
        if ($level === 1 && is_array($result[$key])) {
          self::$multiValues[$baseKey][] = $key;
        }
      }
    } else {
      return self::formatValue($dataKey, $data);
    }
    return $result;
  }	

  /**
   * Return a result of recursively parsed and formatted $data.
   * @function formatMembershipResult 
   * @param mixed   $data       data element
   * @param string  $dataKey    key of current $data item
   * @param int     $level      how deep we are relative to the root of our data
   * @return array  $result
   */
  protected static function formatMembershipResult($data, $dataKey = null, $level = 0) {
    $result = array();
    if ($level < 2) {
      if ($level === 1) {
        $result = self::$emptyRow;
      }
      $baseKey = $dataKey;
      foreach ($data as $key => $value) {
        if (empty(self::$fields[$key]) && $level) {
          continue;
        }
        if ($level === 0 && empty($value['api.Membership.get']['values'])) {
          continue;
        }
        $dataKey = $key;
        if (!empty(self::$fields[$key]['title'])) {
          $key = self::$fields[$key]['title'];
        }
        $result[$key] = self::formatMembershipResult($value, $dataKey, $level + 1);
        if ($level === 1 && is_array($result[$key])) {
          self::$multiValues[$baseKey][] = $key;
        }
      }
    } else {
      return self::formatValue($dataKey, $data);
    }
    return $result;
  }	

  /**
   * Return $value formatted by available Option Values for the $key Field. 
   * If there is no Option Values for the field, then return $value itself with HTML tags stripped.
   * If $value contains an array of values then the method works recursively returning an array of formatted values.
   * @function formatValue
   * @param string $key     field name
   * @param string $value   field value
   * @param int $level      recursion level
   * @return string
   */
  protected static function formatValue($key, $value, $level = 0) {
    if (empty($value) || $level > 1) {
      return '';
    }
    $dataType = !empty(self::$fields[$key]['customField']['data_type']) ? self::$fields[$key]['customField']['data_type'] : null;
    if (is_array($value) && $dataType !== 'File') {
      $valueArray = array();
      foreach ($value as $valueKey => $valueItem) {
        $valueArray[] = self::formatValue($key, $valueKey, $level + 1);
      }
      return $valueArray;
    }
    if (!empty(self::$fields[$key]['customField'])) {
      switch (self::$fields[$key]['customField']['data_type']) {
        case 'File':
          return CRM_Utils_System::formatWikiURL($value['fileURL'] . ' ' . $value['fileName']);
          break;
        case 'Date':
        case 'Boolean':
        case 'Link':
        case 'StateProvince':
        case 'Country':
          $data = array('data' => $value);
          CRM_Utils_System::url();
          return CRM_Core_BAO_CustomGroup::formatCustomValues($data, self::$fields[$key]['customField']);
          break;
      }
    }
    if (!empty(self::$fields[$key]['optionValues'])) {
      return self::$fields[$key]['optionValues'][$value];
    }
    return strip_tags(self::customizeValue($key, $value));
  }


  /**
   * Additional function for customizing Membership value by its key
   * (if it's needed). For example: we want to return Campaign's title
   * instead of ID.
   * @function customizeValue 
   * @param string $key
   * @param string $value
   * @return string $result
   */
  protected static function customizeValue($key, $value) {
    $result = $value;
    switch ($key) {
      case 'campaign_id':
        if (!empty($value)) {
          $campaign = civicrm_api3('Campaign', 'getsingle', array(
                'sequential' => 1,
                'return' => "title",
                'id' => $value,
                ));
          if ($campaign['is_error']) {
            $result = '';
          } else {
            $result = $campaign['title'];
          }
        }
        break;
      case 'contribution_status_id':
        if(!empty($value)){
          $result = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')[$value];					
        }
        break;
      case 'contribution_page_id':
        if(!empty($value)){
          $result = CRM_Contribute_PseudoConstant::contributionPage($value, true);
        }	
        break;
      case 'financial_type_id':
        if(!empty($value)){
          $result = CRM_Contribute_PseudoConstant::financialType($value);
        }
        break;
      case 'membership_type_id':
        if(!empty($value)){
          $result = CRM_Member_PseudoConstant::membershipType($value,FALSE);
        }
        break;		
      case 'status_id':
        if(!empty($value)){
          $result = CRM_Member_PseudoConstant::membershipStatus($value,NULL,'name',FALSE,FALSE);
        }
        break;
    }
    return $result;
  }	

  /**
   * Resolved the duplicate issue. This method was returning names rather than titles in the output array, which got added to the list of attributes
   * Now is keyed by Titles. So Voila! No need of filtering now!!
   * @function getEmptyRow
   * @param bool $isContribution  1 for Contribution, 0 otherwise
   * @return array $result
   */
  protected static function getEmptyRow($isContribution) {
    $result = array();
    if($isContribution){
      foreach (self::$fields as $key => $value) {
        if (!empty($value['title'])) {
          $key = $value['title'];
        }
        $result[$key] = '';
      }
    }
    else{
      foreach (self::$fields as $key => $value) {
        if (!empty($value['title'])) {
          $key = $value['title'];
        }
        $result[$key] = '';
      }
    }
    return $result;
  }

  /**
   * Return an array containing all Fields and Custom Fields of Contribution entity, keyed by their API keys and extended with available fields Option Values.
   * @function getContributionFields
   * @return array $result
   */
  protected static function getContributionFields() {
    $fields = CRM_Contribute_DAO_Contribution::fields();
    if (!empty($fields['contribution_id'])) {
      $fields['contribution_id']['title'] = 'Contribution ID';
    }
    if (!empty($fields['contribution_page_id'])) {
      $fields['contribution_page_id']['title'] = 'Contribution Page ID';
    }
    $keys = CRM_Contribute_DAO_Contribution::fieldKeys();
    $result = array();

    $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends = \'Contribution\' AND g.is_active = 1 AND f.is_active = 1'
        );
    while ($customFieldsResult->fetch()) {
      $customField = new CRM_Core_BAO_CustomField();
      $customField->id = $customFieldsResult->id;
      $customField->find(true);
      $fields['custom_' . $customFieldsResult->id] = array(
          'name' => 'custom_' . $customFieldsResult->id,
          'title' => $customFieldsResult->label,
          'pseudoconstant' => array(
            'optionGroupName' => $customFieldsResult->option_group_name,
            ),
          'customField' => (array)$customField
          );
    }

    //Adding contact info attributes separately
    $fields['display_name'] = array('name' => 'display_name', 'title' => 'Display Name');
    $fields['sort_name'] = array('name' => 'sort_name', 'title' => 'Sort Name');
    $fields['contact_type'] = array('name' => 'contact_type', 'title' => 'Contact Type');
    $fields['id'] = array('name' => 'id', 'title' => 'ID');		

    foreach ($fields as $key => $value) {
      $key = $value['name'];
      $result[$key] = $value;
    }
    return $result;
  }

  /**
   * Return an array containing all Fields and Custom Fields of Membership entity, keyed by their API keys and extended with available fields Option Values.
   * @function getMembershipFields
   * @return array $result
   */
  protected static function getMembershipFields() {
    $fields = CRM_Member_DAO_Membership::fields();
    $keys = CRM_Member_DAO_Membership::fieldKeys();
    $result = array();

    $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends = \'Membership\' AND g.is_active = 1 AND f.is_active = 1'
        );
    while ($customFieldsResult->fetch()) {
      $customField = new CRM_Core_BAO_CustomField();
      $customField->id = $customFieldsResult->id;
      $customField->find(true);
      $fields['custom_' . $customFieldsResult->id] = array(
          'name' => 'custom_' . $customFieldsResult->id,
          'title' => $customFieldsResult->label,
          'pseudoconstant' => array(
            'optionGroupName' => $customFieldsResult->option_group_name,
            ),
          'customField' => (array)$customField
          );
    }

    $fields['membership_name'] = array('name' => 'membership_name', 'title' => 'Membership Name');
    $fields['relationship_name'] = array('name' => 'relationship_name', 'title' => 'Relationship Name');
    $fields['id'] = array('name' => 'id', 'title' => 'ID');
    $fields['status_id'] = array('name' => 'status_id', 'title' => 'Status ID');        	
    $fields['is_pay_later'] = array('name' => 'is_pay_later', 'title' => 'Is Pay Later');        
    foreach ($fields as $key => $value) {
      $key = $value['name'];
      $result[$key] = $value;
    }
    return $result;
  }
}
