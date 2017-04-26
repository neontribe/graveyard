<?php

namespace Drupal\Tests\nt8tabsio\Unit;

/**
 * @file
 * Defines a unit test for the nt8tabsrestservice.
 */
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\Tests\UnitTestCase;

/**
 * Description of NT8TabsRestServiceTest.
 *
 * @author tobias
 * @coversDefaultClass \Drupal\nt8tabsio\Service\NT8TabsRestService
 * @group nt8tabsio
 */
class NT8TabsRestServiceTest extends UnitTestCase {

  protected $serviceInstance;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    // @TODO: Read this in from a yml file.
    $container->set('config.factory', $this->getConfigFactoryStub(
        [
          'nt8tabsio.settings' => [
            'id' => 'ZZ',
            'uri' => 'http://zz.api.carltonsoftware.co.uk/',
            'key' => 'mouse',
            'secret' => 'cottage',
          ],
        ]
    ));

    $container->set('logger.factory', new LoggerChannelFactory());

    $this->serviceInstance = new NT8TabsRestService();
  }

  /**
   * @covers ::rest
   * @dataProvider restDataProvider
   */
  public function testRest($args) {
    $api_response = call_user_func_array([$this->serviceInstance, 'rest'], $args);

    $this->assertTrue(isset($api_response));
    $this->assertNotFalse($api_response);

    // The response might be markup or json so we just test to see if char >= 1.
    $this->assertStringMatchesFormat("%a", $api_response);
  }

  /**
   * Data provider for rest paramaters.
   *
   * @return array
   *   Sample rest parameters.
   */
  public function restDataProvider() {
    return [
      [
        ['GET', '/', []],
      ],
      [
        ['OPTIONS', '/', []],
      ],
      [
        ['POST', '/', []],
      ],
      [
        ['PUT', '/', []],
      ],
      [
        ['DELETE', '/', []],
      ],
    ];
  }

  /**
   * @covers ::__call
   * @dataProvider callDataProvider
   */
  public function testCall($method, $args) {
    $api_response = $this->serviceInstance->__call($method, $args);

    $this->assertTrue(isset($api_response));
    $this->assertNotFalse($api_response);

    // The response might be markup or json so we just test to see if char >= 1.
    $this->assertStringMatchesFormat("%a", $api_response);
  }

  /**
   * Data provider for call paramaters.
   *
   * @return array
   *   Sample call parameters.
   */
  public function callDataProvider() {
    return [
      [
        'get',
        [
          '/',
          [],
        ],
      ],
      [
        'post',
        [
          '/',
          [],
        ],
      ],
      [
        'put',
        [
          '/',
          [],
        ],
      ],
      [
        'delete',
        [
          '/',
          [],
        ],
      ],
      [
        'del',
        [
          '/',
          [],
        ],
      ],
      [
        'options',
        [
          '/',
          [],
        ],
      ],
    ];
  }

  /**
   * @covers ::strToDate
   * @dataProvider strToDateDataProvider
   */
  public function testStrToDate($date, $format, $expectedDate) {
    if (isset($format)) {
      $date_string = $this->serviceInstance->strToDate($date, $format);
    }
    else {
      $date_string = $this->serviceInstance->strToDate($date);
    }

    $this->assertEquals($expectedDate, $date_string);
  }

  /**
   * Data provider for date paramaters.
   *
   * @return array
   *   Sample date parameters.
   */
  public function strToDateDataProvider() {
    return [
      // Test that slashes are replaced.
      ['10/10/2010', NULL, '2010-10-10'],
      // Test that the default is 'Y-m-d'.
      ['10-10-2010', NULL, '2010-10-10'],
      // Test a custom format string.
      ['10-10-2010', 'd-m-Y', '10-10-2010'],
    ];
  }

  /**
   * @covers ::hmacHash
   * @dataProvider splitProprefDataProvider
   */
  public function testSplitPropref(string $raw_propref = '', $expectedSplit = FALSE, $expectedBrand = FALSE) {
    $splitted = $this->serviceInstance->splitPropref($raw_propref);

    $this->assertTrue(isset($splitted[0]));
    $this->assertTrue(isset($splitted[1]));

    // Assert that the splitted part is what we expect.
    $this->assertEquals($expectedSplit, $splitted[0]);

    // Assert that the brandcode returned matches the one provided.
    $this->assertEquals($expectedBrand, $splitted[1]);
  }

  /**
   * Data provider for propref paramaters.
   *
   * @return array
   *   Sample propref parameters.
   */
  public function splitProprefDataProvider() {
    $id = 'ZZ';

    return [
      ['', '', $id],
      ["XXX", 'XXX', $id],
      ["XXX_YYY_${id}", 'XXX_YYY', $id],
      ["_XXX_ZZ_", '_XXX_ZZ_', $id],
      ["X_BRXX_${id}", 'X_BRXX', $id],
      ["_X_BRXX_${id}", '_X_BRXX', $id],
      ["_BRX_BRXX_${id}", '_BRX_BRXX', $id],
    ];
  }

  /**
   * @covers ::hmacHash
   * @dataProvider hmacHashDataProvider
   */
  public function testHmacHash(string $data = '') {
    $hash = $this->serviceInstance->hmacHash($data);

    $test_hash = hash('SHA256', $data, FALSE);

    // Check that something was returned.
    $this->assertTrue(isset($hash));

    // Check that the hash matches an sha256 regex.
    $this->assertRegexp('#[A-Fa-f0-9]{64}#si', $hash);

    // Check that the returned hash matches the expected hash.
    $this->assertEquals($test_hash, $hash);
  }

  /**
   * Data provider for testHmacEncode().
   */
  public function hmacHashDataProvider() {
    return [
      [''],
      ['&foo=bar'],
    ];
  }

  /**
   * @covers ::hmacEncode
   * @dataProvider hmacEncodeDataProvider
   */
  public function testHmacEncode($params = []) {
    $encodedParams = $this->serviceInstance->hmacEncode($params);

    $this->assertArrayHasKey('APIKEY', $encodedParams);
    $this->assertArrayNotHasKey('APISECRET', $encodedParams);
    $this->assertArrayHasKey('hash', $encodedParams);
  }

  /**
   * Data provider for testHmacEncode().
   */
  public function hmacEncodeDataProvider() {
    return [
      [
        ['foo' => 'bar'],
      ],
      [
        [],
      ],
    ];
  }

  /**
   * @covers ::buildRequestUrl
   * @dataProvider requestUrlDataProvider
   */
  public function testBuildRequestUrl($method, $path, $data) {
    $requestUrl = $this->serviceInstance->buildRequestUrl($method, $path, $data);

    // Assert that the returned value is not null.
    $this->assertTrue(isset($requestUrl));

    // Check that the returned value matches a url checking regex.
    $this->assertRegexp('#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si', $requestUrl);
  }

  /**
   * Data provider for testBuildRequestUrl().
   */
  public function requestUrlDataProvider() {
    return [
      ['GET', 'properties', ['foo' => 'bar']],
      ['POST', 'properties', ['foo' => 'bar']],
      ['GET', 'properties', []],
      ['POST', 'properties', []],
    ];
  }

  /**
   * @covers ::filterParams
   * @dataProvider filterParamsDataProvider
   */
  public function testFilterParams($params) {
    $filtered_params = $this->invokeMethod($this->serviceInstance, 'filterParams', [$params]);

    $this->assertArrayHasKey('searchparam1', $params);
    $this->assertArrayHasKey('searchparam2', $params);

    $this->assertArrayHasKey('pageSize', $filtered_params);
    $this->assertArrayHasKey('page', $filtered_params);
    $this->assertArrayHasKey('searchId', $filtered_params);
    $this->assertArrayHasKey('fields', $filtered_params);
    $this->assertArrayHasKey('filter', $filtered_params);
  }

  /**
   * Data provider for filter paramaters.
   *
   * @return array
   *   Sample filter parameters.
   */
  public function filterParamsDataProvider() {
    return [
      [
        [
          'pageSize' => '99',
          'page' => '1',
          'searchId' => 'abcdef',
          'fields' => 'id:name',
          'filter' => 'ATTR01=true',
          'searchparam1' => 'lorem',
          'searchparam2' => 'ipsum',
        ],
      ],
    ];
  }

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object
   *   Instantiated object that we will run method on.
   * @param string $methodName
   *   Method name to call.
   * @param array $parameters
   *   Array of parameters to pass into method.
   *
   * @return mixed
   *   Method return.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($object, $parameters);
  }

}
