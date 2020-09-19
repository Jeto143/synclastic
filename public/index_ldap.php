<?php

$ldap = ldap_connect('openldap', 1389);
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);

$ldapbind = ldap_bind($ldap, 'cn=admin,dc=example,dc=org', 'adminpassword');

$result = ldap_search($ldap, 'dc=example,dc=org', '(sn=10002)');

$data = ldap_get_entries($ldap, $result);

echo '<pre>'; print_r($data); echo '</pre>';
echo '<pre>'; var_dump($data[0]['telephonenumber'][0]);
