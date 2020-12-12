<?php

class com_joineryhq_licolreport extends CRM_Report_Form {
  var $_debug = FALSE;

  function __construct() {
    $this->_columns = array(
      'civicrm_participant' => array(
        'fields' => array(
          'participant_id' => array(
            'title' => E::ts('Participant ID'),
            'name' => 'id',
          ),
          'status_id' => array(
            'title' => E::ts('Status'),
          ),
          'role_id' => array(
            'title' => E::ts('Role'),
          ),
          'source' => array(
            'title' => E::ts('Source'),
          ),
          'register_date' => array(
            'title' => E::ts('Registration Date')
          ),
        ),
        'grouping' => 'event-fields',
        'filters' => array(
          'event_id' => array(
            'name' => 'event_id',
            'title' => E::ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => array(
              'entity' => 'event',
              'select' => array('minimumInputLength' => 0),
            ),
          ),
          'sid' => array(
            'name' => 'status_id',
            'title' => E::ts('Participant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ),
          'rid' => array(
            'name' => 'role_id',
            'title' => E::ts('Participant Role'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ),
          'participant_register_date' => array(
            'title' => E::ts('Registration Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'fee_currency' => array(
            'title' => E::ts('Fee Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'registered_by_id' => array(
            'title' => E::ts('Registered by Participant ID'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ),
          'source' => array(
            'title' => E::ts('Source'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ),
        ),
        'order_bys' => array(
          'participant_register_date' => array(
            'title' => E::ts('Registration Date'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ),
          'event_id' => array(
            'title' => E::ts('Event'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ),
        ),
      ),
      'civicrm_contact' => array(
        'grouping' => 'contact-fields',
        'fields' => array(
          'first_name' => array(
            'title' => E::ts('First Name'),
          ),
          'last_name' => array(
            'title' => E::ts('Last Name'),
          ),
          'display_name' => array(
            'title' => E::ts('Display Name'),
            'default' => TRUE,
          ),
          'sort_name' => array(
            'title' => E::ts('Sort Name'),
          ),
          'contact_type' => array(
            'title' => E::ts('Contact Type'),
          ),
          'prefix_id' => array(
            'title' => E::ts('Prefix'),
          ),
          'suffix_id' => array(
            'title' => E::ts('Individual Suffix'),
          ),
          'external_identifier' => array(
            'title' => E::ts('External ID'),
          ),
          'is_deceased' => array(
            'title' => E::ts('Deceased'),
            'dbAlias' => "if(is_deceased, 'Yes', 'No')",
          ),
          'source' => array(
            'title' => E::ts('Source'),
          ),
          'id' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
        'order_bys' => array(
          'sort_name' => array(
            'title' => E::ts('Sort Name'),
//            'default_weight' => '1',
//            'default_order' => 'ASC',
          ),
        ),
      ),
      'civicrm_email' => array(
        'grouping' => 'contact-fields',
        'fields' => array(
          'email' => array(
            'title' => E::ts('Email'),
            'no_repeat' => TRUE,
          ),
        ),
      ),
    );
    $this->_columns += $this->getAddressColumns();

    $this->_customGroupExtends = array( 'Contact', 'Individual',  'Participant');
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    parent::__construct();
    
    $this->_columns += $this->_getPriceFieldColumns();
  }

  function _getPriceFieldColumns() {
    
    $price_field_columns = array();
    $price_field_columns['civicrm_line_item'] = array(
      'alias' => 'li',
      'grouping' => 'price-fields',
      'group_title' => 'Price Fields (All)',
      'fields' => array(
      ),
    );

    $sql = "
      SELECT
        pfv.id as price_field_value_id,
        ps.title as price_set_label,
        pf.label as price_field_label,
        pfv.label as price_field_value_label,
        pf.html_type
      FROM
        civicrm_price_set ps
        INNER JOIN civicrm_price_set_entity pse ON pse.price_set_id = ps.id
        INNER JOIN civicrm_event e ON e.id = pse.entity_id AND pse.entity_table = 'civicrm_event'
        INNER JOIN civicrm_price_field pf ON pf.price_set_id = ps.id
        INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
      WHERE
        e.is_active
        AND pfv.is_active
        AND ps.is_active
        AND pf.is_active
      ORDER BY
        ps.title, pf.label, pfv.weight
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $title_parts = array(
        $dao->price_set_label,
        $dao->price_field_label
      );
      if ($dao->html_type != 'Text') {
        $title_parts[] = $dao->price_field_value_label;
      }
      $price_field_columns['civicrm_line_item']['fields']['pfv_'. $dao->price_field_value_id] = array(
        'title' => implode(': ', $title_parts),
        'dbAlias' => "floor(sum(if(li_civireport.price_field_value_id = {$dao->price_field_value_id}, li_civireport.qty, 0)))",
      );
    }

    $price_field_columns['civicrm_line_item_merged'] = array(
      'alias' => 'limerged',
      'grouping' => 'price-fields-merged',
      'group_title' => E::ts('Price Fields (Merged by Label)'),
      'fields' => array(
      ),
    );
//    return $price_field_columns;


    $sql = "
      SELECT
        group_concat(DISTINCT pfv.id ORDER BY pfv.id)  as price_field_value_ids,
        ps.title as price_set_label,
        pf.label as price_field_label,
        pfv.label as price_field_value_label,
        pf.html_type,
        count(*) as cnt
      FROM
        civicrm_price_set ps
        INNER JOIN civicrm_price_set_entity pse ON pse.price_set_id = ps.id
        INNER JOIN civicrm_event e ON e.id = pse.entity_id AND pse.entity_table = 'civicrm_event'
        INNER JOIN civicrm_price_field pf ON pf.price_set_id = ps.id
        INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
      WHERE
        e.is_active
        AND pfv.is_active
        AND ps.is_active
        AND pf.is_active
      GROUP BY
        pf.label, pfv.label
      HAVING
        cnt > 1
      ORDER BY
        ps.title, pf.label, pfv.label
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $title_parts = array(
        E::ts('Merged'),
        $dao->price_field_label
      );
      if ($dao->html_type != 'Text') {
        $title_parts[] = $dao->price_field_value_label;
      }
      $field_alias = 'pfvs_'. str_replace(',', '_', $dao->price_field_value_ids);
      $price_field_columns['civicrm_line_item_merged']['fields'][$field_alias] = array(
        'title' => implode(': ', $title_parts),
        'dbAlias' => "floor(sum(if(li_civireport.price_field_value_id IN ({$dao->price_field_value_ids}), li_civireport.qty, 0)))",
      );
    }

    return $price_field_columns;
  }

  /**
   * Overrides parent::from().
   */
  function from() {
    $this->_from = "
      FROM
        civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
    ";

    if ($this->isTableSelected('civicrm_participant')) {
      $this->_from .= "
        INNER JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
          ON {$this->_aliases['civicrm_participant']}.contact_id = {$this->_aliases['civicrm_contact']}.id
      ";
    }

    if ($this->isTableSelected('civicrm_address')) {
      $this->_from .= "
        LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
          ON {$this->_aliases['civicrm_address']}.contact_id = {$this->_aliases['civicrm_contact']}.id
            AND {$this->_aliases['civicrm_address']}.is_primary
      ";
    }
    
    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
        LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
          ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id
            AND {$this->_aliases['civicrm_email']}.is_primary = 1)
      ";
    }

    if ($this->isTableSelected('civicrm_line_item') || $this->isTableSelected('civicrm_line_item_merged')) {
      $this->_from .= "
        LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
          ON {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_participant'
            AND {$this->_aliases['civicrm_line_item']}.entity_id = {$this->_aliases['civicrm_participant']}.id
      ";
    }
  }

  /**
   * @param $rows
   * @param $entryFound
   * @param $row
   * @param int $rowId
   * @param $rowNum
   * @param $types
   *
   * @return bool
   */
  private function _initBasicRow(&$rows, &$entryFound, $row, $rowId, $rowNum, $types) {
    if (!array_key_exists($rowId, $row)) {
      return FALSE;
    }

    $value = $row[$rowId];
    if ($value) {
      $rows[$rowNum][$rowId] = CRM_Utils_Array::value($value, $types);
    }
    $entryFound = TRUE;
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $participant_status = CRM_Event_PseudoConstant::participantStatus(NULL, FALSE, 'label');
    $participant_role = CRM_Event_PseudoConstant::participantRole();
    $suffix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');
    $prefix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');

    foreach ($rows as $rowNum => $row) {

      // handle participant status id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_participant_status_id', $rowNum, $participant_status);

      // handle participant role id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_participant_role_id', $rowNum, $participant_role);

      // handle suffix_id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contact_suffix_id', $rowNum, $suffix);

      // handle prefix_id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contact_prefix_id', $rowNum, $prefix);

      // Convert display name to link
      if ($this->_outputMode !== 'csv') {
        $displayName = CRM_Utils_Array::value('civicrm_contact_display_name', $row);
        $cid = CRM_Utils_Array::value('civicrm_contact_id', $row);
        $id = CRM_Utils_Array::value('civicrm_participant_participant_id', $row);

        if ($displayName && $cid && $id) {
          $url = CRM_Utils_System::url('civicrm/contact/view/',
            "reset=1&cid=$cid"
          );

          $viewUrl = CRM_Utils_System::url("civicrm/contact/view/participant",
            "reset=1&id=$id&cid=$cid&action=view&context=participant"
          );

          $contactTitle = E::ts('View Contact Details');
          $participantTitle = E::ts('View Participant Record');

          $rows[$rowNum]['civicrm_contact_display_name'] = "<a title='$contactTitle' href=$url>$displayName</a>";
          $rows[$rowNum]['civicrm_contact_display_name'] .=
            "<span style='float: right;'><a title='$participantTitle' href=$viewUrl>" .
            E::ts('View') . "</a></span>";
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * Overrides parent::groupBy().
   */
  function groupBy() {
    parent::groupBy();

    if (empty($groupBys)) {
      $this->_groupBy = "GROUP BY ";
    }
    else {
      $this->_groupBy .= ', ';
    }
    $this->_groupBy .= " {$this->_aliases['civicrm_contact']}.id";
  }


  /**
   * Debug logger. If $this->_debug is TRUE, send $var to dsm() with label $label.
   */
  function _debugDsm($var, $label = NULL) {
    if ($this->_debug && function_exists('dsm')) {
      dsm($var, $label);
    }
  }

}
