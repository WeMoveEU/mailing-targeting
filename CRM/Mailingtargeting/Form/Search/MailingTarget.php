<?php

/**
 * A custom contact search
 */
class CRM_Mailingtargeting_Form_Search_MailingTarget extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  static function options($values, $key, $display) {
    $result = array();
    foreach ($values as $i => $val) {
      $result[$val[$key]] = $val[$display];
    }
    return $result;
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {

    $this->setTitle(ts('Mailing targeting'));

		$mailings = civicrm_api3('Mailing', 'get', array(
      'sequential' => 1,
      'return' => array("id", "name"),
      'is_completed' => 1,
      'is_archived' => 0,
      'name' => array('NOT LIKE' => "%--CAMP-ID-%"),
      'options' => array('limit' => 1000),
    ));
    $form->assign('mailings', static::options($mailings['values'], 'id', 'name'));

		$groups = civicrm_api3('Group', 'get', array(
      'sequential' => 1,
      'return' => array("id", "title"),
      'is_active' => 1,
      'title' => array('NOT LIKE' => "%--CAMP-ID-%"),
      'options' => array('limit' => 500),
    ));
    $form->assign('groups', static::options($groups['values'], 'id', 'title'));

    $form->assign('campaigns', CRM_Campaign_BAO_Campaign::getCampaigns());

    $form->add('select', 'include', ts('Include contacts'), NULL, FALSE, array('multiple' => TRUE));
    $form->add('select', 'exclude', ts('Exclude contacts'), NULL, FALSE, array('multiple' => TRUE));

    $commonIncludes = explode(',', Civi::settings()->get('mailingtargeting_commonIncludes'));
    $groups = CRM_Contact_BAO_Group::getGroups(array('id' => $commonIncludes));
    $form->assign('commonIncludes', $groups);

    $commonExcludes = explode(',', Civi::settings()->get('mailingtargeting_commonExcludes'));
    $groups = CRM_Contact_BAO_Group::getGroups(array('id' => $commonExcludes));
    $form->assign('commonExcludes', $groups);
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
    $from = "FROM civicrm_contact contact_a"
        . $this->includeJoins()
        . $this->excludeJoins();

    return $from;
  }

  /**
   * Construct the JOIN queries to match the include conditions
   */
  public function includeJoins() {
    $from = "";
    $includeIds = static::splitIds($this->_formValues['include']);

    $smartGroups = static::smartGroups($includeIds['groups']);
    foreach ($includeIds['groups'] as $gid) {
      $tbl = "iGC$gid";
      if (in_array($gid, $smartGroups)) {
        $tblJoin = "civicrm_group_contact_cache $tbl ON";
      } else {
        $tblJoin = "civicrm_group_contact $tbl ON $tbl.status='Added' AND";
      }
      $from .= " JOIN $tblJoin $tbl.group_id=$gid AND $tbl.contact_id=contact_a.id";
    }

    foreach ($includeIds['mailings'] as $mid) {
      $tbl = "iMR$mid";
      $from .= " JOIN civicrm_mailing_recipients $tbl ON $tbl.mailing_id=$mid AND $tbl.contact_id=contact_a.id";
    }

    foreach ($includeIds['signs'] as $cid) {
      $tbl = "iACT$cid";
      $from .= " JOIN civicrm_activity $tbl ON $tbl.campaign_id=$cid AND $tbl.activity_type_id=32";
      $from .= " JOIN civicrm_activity_contact ct$tbl ON ct$tbl.activity_id=$tbl.id AND ct$tbl.contact_id=contact_a.id";
    }

    return $from;
  }

  /**
   * Construct the LEFT JOIN queries to match the exclude conditions
   */
  public function excludeJoins() {
    $from = "";
    $excludeIds = static::splitIds($this->_formValues['exclude']);
    $this->_excludeTables = array();

    if (!empty($excludeIds['groups'])) {
      $smartGroups = static::smartGroups($excludeIds['groups']);
      $plainGroups = array_diff($excludeIds['groups'], $smartGroups);
      if (!empty($plainGroups)) {
        $tbl = "xG";
        $this->_excludeTables[] = $tbl;
        $gids = implode(',', $plainGroups);
        $from .= " LEFT JOIN civicrm_group_contact $tbl ON $tbl.group_id IN ($gids) AND $tbl.contact_id=contact_a.id AND $tbl.status='Added'";
      }
      if (!empty($smartGroups)) {
        $tbl = "xSG";
        $this->_excludeTables[] = $tbl;
        $gids = implode(',', $smartGroups);
        $from .= " LEFT JOIN civicrm_group_contact_cache $tbl ON $tbl.group_id IN ($gids) AND $tbl.contact_id=contact_a.id";
      }
    }

    if (!empty($excludeIds['mailings'])) {
      $tbl = "xMR";
      $this->_excludeTables[] = $tbl;
      $mids = implode(',', $excludeIds['mailings']);
      $from .= " LEFT JOIN civicrm_mailing_recipients $tbl ON $tbl.mailing_id IN ($mids) AND $tbl.contact_id=contact_a.id";
    }

    if (!empty($excludeIds['signs'])) {
      $tbl = "xACT";
      $this->_excludeTables[] = $tbl;
      $cids = implode(',', $excludeIds['signs']);
      $from .= " LEFT JOIN ("
            .  "   SELECT ct$tbl.id, ct$tbl.contact_id "
            .  "   FROM civicrm_activity_contact ct$tbl "
            .  "   JOIN civicrm_activity a$tbl ON a$tbl.campaign_id IN ($cids) AND a$tbl.id=ct$tbl.activity_id"
            .  " ) $tbl ON $tbl.contact_id=contact_a.id";
    }

    return $from;
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    foreach ($this->_excludeTables as $tbl) {
      $where[] = "$tbl.id IS NULL";
    }
    if (empty($where)) {
      return "1=1";
    } else {
      return implode(" OR ", $where);
    }
  }

  protected static function splitIds($ids) {
    $result = array(
      'groups' => array(),
      'mailings' => array(),
      'signs' => array(),
    );
    
    foreach ($ids as $id) {
      if (strpos($id, 'gid-') === 0) { //Group
        $result['groups'][] = intval(substr($id, 4));
      } else if (strpos($id, 'mid-') === 0) { //Mailing
        $result['mailings'][] = intval(substr($id, 4));
      } else if (strpos($id, 'sign-cid-') === 0) { //Signature
        $result['signs'][] = intval(substr($id, 9));
      }
    }

    return $result;
  }

  /**
   * From a list of group ids, return the list of ids of those that are smart groups
   */
  public static function smartGroups($groups) {
    $smartGroups = array();
    if (!empty($groups)) {
      $groups = CRM_Contact_BAO_Group::getGroups(array('id' => $groups));
      foreach ($groups as $group) {
        if ($group->saved_search_id) {
          $smartGroups[] = $group->id;
        }
      }
    }
    return $smartGroups;
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
