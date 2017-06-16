<?php

return array(
  'mailingtargeting_commonIncludes' => array(
    'group_name' => 'Mailing targeting settings',
    'group' => 'mailing_targeting',
    'name' => 'mailing_targeting_commonIncludes',
    'type' => 'String',
    'default' => NULL,
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => 'Common include conditions',
    'description' => "Comma-separated list of group IDs commonly included in mailings",
    'help_text' => "Common includes conditions",
  ),
  'mailingtargeting_commonExcludes' => array(
    'group_name' => 'Mailing targeting settings',
    'group' => 'mailing_targeting',
    'name' => 'mailing_targeting_commonExcludes',
    'type' => 'Array',
    'default' => NULL,
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => 'Common exclude conditions',
    'description' => "Comma-separated list of group IDs commonly excluded in mailings",
    'help_text' => "Common excludes conditions",
  ),
);
