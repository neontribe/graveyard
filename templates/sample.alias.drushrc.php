<?php
$aliases["{{ cottage_alias_name }}"] = array (
  'root' => '/var/www/sites/latest/{{ cottage_brand_code }}',
  'uri' => 'http://default',
  '#name' => '{{ cottage_alias_name }}',
  'remote-host' => '{{ remote_host }}',
  'remote-user' => '{{ remote_user }}',
  'databases' =>
  array (
    'default' =>
    array (
      'default' =>
      array (
        'database' => '{{ drupal.dbname }}',
        'username' => '{{ drupal.dbname }}',
        'password' => '{{ drupal_dbpass }}',
        'host' => 'localhost',
        'port' => '',
        'driver' => 'mysql',
        'prefix' => '',
      ),
    ),
  ),
);

