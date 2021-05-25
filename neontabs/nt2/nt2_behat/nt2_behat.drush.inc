<?php

/**
 * Implements hook_drush_command().
 */
function nt2_behat_drush_command() {
  $items['nt2-behat-write-behat-yaml'] = array(
    'description' => 'Create a behat config XML file in the root of this drupal.',
    'arguments' => array(
      'url' => 'Behat needs to know where to find the install in this instance.',
    ),
    'aliases' => array('ntwby'),
    'examples' => array(
      'drush ntwby http://localhost/foo',
    ),
  );

  return $items;
}

/**
 * Create the nehat YAML file.
 *
 * Echos the response to the std out.
 *
 * @param string $base_url
 *   The URI that be used by behat to hit the site.
 */
function drush_nt2_behat_write_behat_yaml($base_url) {
  if (empty($base_url)) {
    drush_set_error(
      dt('drush_nt2_behat_write_behat_yaml requires the site URL as the first argument.')
    );
    return;
  }
  // Get current list of modules.
  $files = system_rebuild_module_data();
  $features = [];

  // Iterate through each of the modules.
  foreach ($files as $filename => $module) {
    $info = $module->info;
    if (isset($info['behat'])) {
      $path = dirname($module->filename);
      $behats = $info['behat'];
      foreach ($behats as $behat) {
        $features[] = DRUPAL_ROOT . '/' . $path . '/' . $behat;
      }
    }
    $changes[] = str_replace("\t", '    ', $line);
  }

  // This could be better.
  $brandcode = strtolower(variable_get('tabs_api_default_brandcode'));
  $bootstrap = DRUPAL_ROOT . '/' . drupal_get_path('module', 'nt' . $brandcode . '_behat_tests') . '/features/bootstrap';

  if (!file_exists($bootstrap)) {
    // Fail over to the default bootstrap in this module.
    $bootstrap = DRUPAL_ROOT . '/' . drupal_get_path('module', 'nt2_behat') . '/features/bootstrap';
  }

  $array = array(
    'default' => array(
      'autoload' => array(
        '' => $bootstrap,
      ),
      'suites' => array(
        'default' => array(
          'paths' => $features,
          'contexts' => array(
            'FeatureContext',
            strtoupper($brandcode) . 'Context',
            'Drupal\DrupalExtension\Context\DrupalContext',
            'Drupal\DrupalExtension\Context\MinkContext',
            'Drupal\DrupalExtension\Context\MessageContext',
            'Drupal\DrupalExtension\Context\DrushContext',
          ),
        ),
      ),
      'extensions' => array(
        'Behat\MinkExtension' => array(
          'goutte' => array(),
          'selenium2' => array(),
          'base_url' => $base_url,
        ),
        'Drupal\DrupalExtension' => array(
          'blackbox' => array(),
          'api_driver' => 'drupal',
          'drupal' => array(
            'drupal_root' => DRUPAL_ROOT,
          ),
        ),
      ),
    ),
  );

  if (!class_exists('\Symfony\Component\Yaml\Dumper')) {
    require_once __DIR__ . '/../vendor/autoload.php';
  }
  // @codingStandardsIgnoreStart
  $yaml = \Symfony\Component\Yaml\Yaml::dump($array, 99);
  // @codingStandardsIgnoreEnd
  echo $yaml;
}
