<?php

/**
 * A custom contact search
 */
class CRM_Mailingtargeting_Form_Search_MailingTarget extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {

    $this->setTitle(ts('Mailing targeting'));

    $form->assign('groups', CRM_Contact_BAO_Group::getGroups());
    $form->assign('mailings', CRM_Mailing_PseudoConstant::completed());
    $form->assign('campaigns', CRM_Campaign_BAO_Campaign::getCampaigns());
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    $columns = array(
      ts('Contact ID') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      contact_a.id            as contact_id,
      contact_a.contact_type  as contact_type,
      contact_a.sort_name     as sort_name
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "
      FROM civicrm_contact contact_a
      JOIN civicrm_group_contact gc ON gc.contact_id=contact_a.id
    ";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    return "gc.group_id=886";
  }

  /**
   * Keep only the commonly used actions
   */
  public function buildTaskList(CRM_Core_Form_Search $form) {
    $taskList = parent::buildTaskList($form);
    $toKeep = array(
      CRM_Contact_Task::GROUP_CONTACTS,
      CRM_Contact_Task::REMOVE_CONTACTS,
      CRM_Contact_Task::CREATE_MAILING,
    );
    foreach ($taskList as $key => $task) {
      if (!in_array($key, $toKeep)) {
        unset($taskList[$key]);
      }
    }

    return $taskList;
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Mailingtargeting/Form/Search/MailingTarget.tpl';
  }
}