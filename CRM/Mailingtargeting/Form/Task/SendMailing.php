<?php

/**
 * This class provides a search action to send a mailing,
 * either a new draft, an existing one, or a cloned mailing
 */
class CRM_Mailingtargeting_Form_Task_SendMailing extends CRM_Contact_Form_Task {

	public function preProcess() {
		parent::preProcess();
    // For AB mailings, the recipients are associated to mailing A while it is a draft
    // So here we merge standalone mailings and experiments A
		$drafts = civicrm_api3('Mailing', 'get', array(
      'sequential' => 1,
      'return' => array("id", "name"),
      'mailing_type' => "standalone",
      'scheduled_date' => array('IS NULL' => 1),
      'options' => array('limit' => 250, 'sort' => "id desc"),
    ));
		$abDrafts = civicrm_api3('MailingAB', 'get', array(
      'sequential' => 1,
      'return' => array("mailing_id_a", "name"),
      'status' => "Draft",
      'options' => array('limit' => 50, 'sort' => "mailing_id_a desc"),
    ));
    foreach ($abDrafts['values'] as $ab) {
      $drafts['values'][] = array('id' => $ab['mailing_id_a'], 'name' => $ab['name']);
    }

		$completed = civicrm_api3('Mailing', 'get', array(
      'sequential' => 1,
      'return' => array("id", "name"),
      'scheduled_date' => array('IS NOT NULL' => 1),
      'options' => array('limit' => 250, 'sort' => "id desc"),
    ));

		CRM_Core_Resources::singleton()->addVars('Mailingtargeting', array(
			'drafts' => $drafts['values'],
			'completed' => $completed['values'],
		));
	}

  public function buildQuickForm() {
    $options = array(
      'new' => 'New draft mailing',
      'existing' => 'Existing draft mailing',
      'clone' => 'Clone of past mailing'
    );
    $this->addRadio('target', ts('Add recipients to'), $options);

    $select2style = array(
      'style' => 'width: 100%; max-width: 60em;',
      'class' => 'crm-select2',
      'placeholder' => ts('- select -'),
			'disabled' => TRUE,
    );
    $this->add('select', 'targetmid',
      ts('Existing mailing'),
      NULL,
      FALSE,
      $select2style
    );

    $this->addDefaultButtons(ts('Add recipients'));
  }

	public function postProcess() {
    $params = $this->controller->exportValues();
    list ($groupId, $ssId) = $this->createHiddenGroup();
    if ($params['target'] == 'new') {
      $templateTypes = CRM_Mailing_BAO_Mailing::getTemplateTypes();
			$mailing = civicrm_api3('Mailing', 'create', array(
				'name' => "",
				'campaign_id' => NULL,
				'replyto_email' => "",
				'template_type' => $templateTypes[0]['name'],
				'template_options' => array('nonce' => 1),
				'subject' => "",
				'body_html' => "",
				'body_text' => "",
				'groups' => array(
					'include' => array($groupId),
					'exclude' => array(),
					'base' => array(),
				),
				'mailings' => array(
					'include' => array(),
					'exclude' => array(),
				),
			));

      static::openMailing($mailing['id']);
    }
    else if ($params['target'] == 'existing') {
      $mid = $params['targetmid'];
			$newParams = array('id' => $mid);
			$newParams['groups']['include'] = array();
			$dao = new CRM_Mailing_DAO_MailingGroup();
			$dao->mailing_id = $mid;
      $dao->entity_table = 'civicrm_group';
      $dao->group_type = 'include';
			$dao->find();
			while ($dao->fetch()) {
				$newParams['groups']['include'][] = $dao->entity_id;
			}
      $newParams['groups']['include'][] = $groupId;

      $mailing = civicrm_api3('Mailing', 'create', $newParams);
      static::openMailing($mailing['id']);
    }
    else if ($params['target'] == 'clone') {
      $mid = $params['targetmid'];
      $clone = civicrm_api3('Mailing', 'clone', array('id' => $mid));
      $clone = civicrm_api3('Mailing', 'create', array(
        'id' => $clone['id'],
        'groups' => array('include' => array($groupId)),
      ));
      static::openMailing($clone['id']);
    }
	}

  public static function openMailing($mid) {
    $query = array(
      'mid' => $mid,
      'continue' => 'true',
      'reset' => '1',
    );
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/mailing/send', $query, TRUE));
  }

}
