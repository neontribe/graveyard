<?php

namespace Drupal\Tests\nt8tabsio\Unit;

/**
 * @file
 * Defines a unit test for the nt8tabsrestservice.
 */
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\Tests\BrowserTestBase;

/**
 * Description of NT8TabsRestServiceTest.
 *
 * @author tobias
 * @group nt8tabsio
 */
class NT8TabsRestServiceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public function testFilterParams() {
    $params = [
      'pageSize' => '99',
      'page' => '1',
      'searchId' => 'abcdef',
      'fields' => 'id:name',
      'filter' => 'ATTR01=true',
      'searchparam1' => 'lorem',
      'searchparam2' => 'ipsum',
    ];

    $rest = new NT8TabsRestService();
    $filtered_params = $rest->filterParams($params);

    $this->assertNotTrue(isset($params['pageSize']));
    $this->assertNotTrue(isset($params['page']));
    $this->assertNotTrue(isset($params['searchId']));
    $this->assertNotTrue(isset($params['fields']));

    $this->assertTrue(isset($params['searchparam1']));
    $this->assertTrue(isset($params['searchparam2']));

    $this->assertTrue(isset($filtered_params['pageSize']));
    $this->assertTrue(isset($filtered_params['page']));
    $this->assertTrue(isset($filtered_params['searchId']));
    $this->assertTrue(isset($filtered_params['fields']));
    $this->assertTrue(isset($filtered_params['filter']));
  }

}
