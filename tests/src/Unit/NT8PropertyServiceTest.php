<?php

namespace Drupal\Tests\nt8tabsio\Unit;

/**
 * @file
 * Defines a unit test for the nt8tabsrestservice.
 */
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\views\wizard\TaxonomyTerm;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Kernel\Handler\FieldEntityLinkTest;
use Psr\Log\InvalidArgumentException;

/**
 * Class TestNT8PropertyService (Test overrides).
 * @package Drupal\Tests\nt8tabsio\Unit
 */
class TestNT8PropertyService extends NT8PropertyService {

  protected static $mockedTerm;

  protected static $mockedFieldItem;

  public function __construct(
    $entityQuery,
    $entityTypeManager,
    \Drupal\nt8tabsio\Service\NT8TabsRestService $nt8RestService,
    $mockedTerm,
    $mockedFieldItem
  ) {
    parent::__construct($entityQuery, $entityTypeManager, $nt8RestService);
    static::$mockedTerm = $mockedTerm;
    static::$mockedFieldItem = $mockedFieldItem;
  }

  /**
   * @codeCoverageIgnore
   */
  protected static function loadMultipleTaxonomyTermsByName($term_name, $vocab_name) {
    if(isset($term_name) && !empty($term_name)) {
      return [$term_name => static::$mockedTerm];
    }
    return NULL;
  }

  /**
   * @codeCoverageIgnore
   */
  protected static function getTermParents($tid) {
    if(isset($tid) && !empty($tid)) {
      return [static::$mockedTerm];
    }
    return NULL;
  }


  /**
   * @codeCoverageIgnore
   */
  public static function getNodeFieldValue(
    EntityInterface $node,
    string $fieldName,
    int $index = -1,
    string $keyname = 'value'
  ) {

    if($index > -1) {
      return 'testindex';
    }

    return [
      ['value' => 'Test Value'],
    ];
  }

  public static function getNodeField(EntityInterface $node, string $fieldName) {
    return static::$mockedFieldItem;
  }
}

/**
 * Description of NT8PropertyServiceTest.
 *
 * @author tobias
 * @coversDefaultClass Drupal\Tests\nt8tabsio\Unit\TestNT8PropertyService
 * @group nt8property
 */
class NT8PropertyServiceTest extends UnitTestCase {

  /**
   * @var NT8PropertyService
   */
  protected $serviceInstance;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyTermStorage;

  /**
   * @var TermInterface
   */
  protected $mockedTerm;

  /**
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $mockedField;

  /**
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $mockedTermDefinition;

  /**
   * @var \Drupal\Core\Entity\Query\Sql\Query
   */
  protected $sqlQuery;

  /**
   * @var \Drupal\Core\Entity\Entity
   */
  protected $mockedEntity;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $mockedNode;

  public function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->entityQuery = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $this->entityTypeManager = $this->getMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->taxonomyTermStorage = $this->getMock('Drupal\taxonomy\TermStorageInterface');
    $this->mockedTermDefinition = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');

    $this->nodeStorage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $this->sqlQuery = $this->getMockBuilder('\Drupal\Core\Entity\Query\Sql\Query')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityQueryFactory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->mockedTerm = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();
    $this->mockedEntity = $this->getMockBuilder('Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $this->mockedNode = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $this->mockedField = $this->getMockBuilder('\Drupal\Core\Field\FieldItemList')
      ->disableOriginalConstructor()
      ->getMock();

    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity.manager', $this->entityManager);
    $container->set('logger.factory', new \Drupal\Core\Logger\LoggerChannelFactory());
    $container->set('config.factory', $this->getConfigFactoryStub(
      [
        'nt8tabsio.settings' => [
          'id' => 'ZZ',
          'uri' => 'http://zz.api.carltonsoftware.co.uk/',
          'key' => 'mouse',
          'secret' => 'cottage',
        ],
        'nt8property.config' => [
          'property-teaser.placeholder-image.url' => 'http://g.co'
        ],
      ]
    ));

    $this->serviceInstance = new TestNT8PropertyService(
      $this->entityQueryFactory,
      $this->entityTypeManager,
      new NT8TabsRestService(),
      $this->mockedTerm,
      $this->mockedField
    );
  }

  /**
   * @covers ::getAttributeDataFromTabs
   * @dataProvider getAttributeDataFromTabsDataProvider
   */
  public function testGetAttributeDataFromTabs($limit, $expectedCount) {
    $attr_data = $this->serviceInstance->getAttributeDataFromTabs($limit);

    $this->assertTrue(
      is_array($attr_data),
      'Check if an array was returned.'
    );

    $this->assertContainsOnlyInstancesOf(
      \stdClass::class,
        $attr_data,
        'Assert that the JSON was parsed to expectations.'
    );

    if($expectedCount > -1) {
      $this->assertCount(
        $expectedCount,
        $attr_data,
        'Assert that we received the expected amount of data.'
      );
    }
  }

  public function getAttributeDataFromTabsDataProvider() {
    return [
      [[], -1],
      [['ATTR01'], 1],
      [['ATTR01', 'ATTR02'], 2],
    ];
  }

  /**
   * @covers ::loadTermsByNames
   * @dataProvider loadTermsByNamesDataProvider
   */
  public function testLoadTermsByNames($vocabName, $termNames) {
    $termNamesLookup = $termNames;
    if(count($termNames) === 0) {
      $termNamesLookup = ['test_entry_1'];
    }

    $this->taxonomyTermStorage
      ->method('loadTree')
      ->willReturn(['test_entry_1' => $this->mockedTerm]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->taxonomyTermStorage);

    $loadedTerms = $this->serviceInstance->loadTermsByNames($vocabName, $termNames, function($termEntity, $tid) use ($termNamesLookup) {
      // Assert that an object of type entity is returned.
      $this->assertInstanceOf(\Drupal\Core\Entity\Entity::class, $termEntity);
      // Assert that a valid tid was returned.
      $this->assertContains($tid, $termNamesLookup);
    });

    if(is_array($loadedTerms)) {
      $this->assertContainsOnlyInstancesOf(\Drupal\Core\Entity\Entity::class, $loadedTerms);
    }
  }

  public function loadTermsByNamesDataProvider() {
    return [
      ['', []],
      ['', ['testTerm_0'], 1],
      ['', ['testTerm_0', 'testTerm_1'], 2],
    ];
  }

  /**
   * @covers ::createAttributesFromTabs
   * @dataProvider createAttributesFromTabsDataProvider
   */
  public function testCreateAttributesFromTabs($attribute_data, $expectedCount) {
    $this->mockedTermDefinition->expects($this->any())
      ->method('create')
      ->willReturn($this->mockedField);

    $this->mockedField->expects($this->any())
      ->method('setValue')
      ->willReturn(1);

    $this->mockedTerm->expects($this->any())
      ->method('get')
      ->willReturn($this->mockedField);

    $this->mockedTerm->expects($this->any())
      ->method('save')
      ->willReturn(TRUE);

    $this->entityManager->method('getStorage')->willReturn($this->mockedTermDefinition);
    $this->entityManager->method('getEntityTypeFromClass')->willReturn($this->mockedTermDefinition);

    $updated_attributes = $this->serviceInstance->createAttributesFromTabs($attribute_data);

    $this->assertTrue(isset($updated_attributes), 'A value was returned.');

    $updated_attributes = array_map('trim', explode(',', $updated_attributes));

    $this->assertCount($expectedCount, $updated_attributes, 'The expected number of attributes were updated.');
  }

  public function createAttributesFromTabsDataProvider() {
    return [
      [
        [
          (object) [
            'code' => 'ATTR01',
            'label' => '< Coast',
            'type' => 'Number',
            'group' => 'Core',
            'brand' => 'ALL',
          ],
        ],
        2
      ],
      [
        [
          (object) [
            'code' => 'ATTR01',
            'label' => '< Coast',
            'type' => 'Number',
            'group' => 'Core',
            'brand' => 'ALL',
          ],
          (object) [
            'code' => 'ATTR02',
            'label' => '< Coast',
            'type' => 'Number',
            'group' => 'Core',
            'brand' => 'ALL',
          ],
        ],
        3
      ],
      [[], 1]
    ];
  }

  /**
   * @covers ::loadNodesFromPropref
   * @dataProvider loadNodesFromProprefDataProvider
   */
  public function testLoadNodesFromPropref($propref, $load = TRUE) {
    $this->sqlQuery->expects($this->any())
      ->method('condition')
      ->willReturn($this->sqlQuery);
    $this->sqlQuery->expects($this->any())
      ->method('execute')
      ->willReturn([$propref => $propref]);

    $this->entityQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($this->sqlQuery);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->nodeStorage);

    $nodeStorageReturn = [];
    if($propref !== "") {
      $nodeStorageReturn = [$propref => $this->mockedEntity];
    }

    $this->nodeStorage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($nodeStorageReturn);

    $loadedNodes = $this->serviceInstance->loadNodesFromPropref($propref, $load);

    if(count($nodeStorageReturn) === 0) {
      $this->assertNull($loadedNodes);
      return;
    }

    $this->assertArrayHasKey($propref, $loadedNodes);
    if($load) {
      $this->assertContainsOnlyInstancesOf(Entity::class, $loadedNodes);
    } else {
      $this->assertContains($propref, $loadedNodes);
    }

  }

  public function loadNodesFromProprefDataProvider() {
    return [
      ['ABCXYZ_ZZ'],
      ['ABCXYZ_ZZ', FALSE],
      [''],
    ];
  }

  /**
   * @covers ::loadNodesFromProprefs
   * @dataProvider loadNodesFromProprefsDataProvider
   */
  public function testLoadNodesFromProprefs($proprefs) {
    $this->sqlQuery->expects($this->any())
      ->method('condition')
      ->willReturn($this->sqlQuery);
    $this->sqlQuery->expects($this->any())
      ->method('execute')
      ->willReturn(['ABC123_ZZ' => 'ABC123_ZZ']);

    $this->entityQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($this->sqlQuery);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->nodeStorage);

    $nodeStorageReturn = [];
    if(count($proprefs) > 0) {
      $nodeStorageReturn = ['ABC123_ZZ' => $this->mockedEntity];
    }

    $this->nodeStorage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($nodeStorageReturn);

    $loadedNodes = $this->serviceInstance->loadNodesFromProprefs($proprefs);

    $this->assertTrue(is_array($loadedNodes));
  }

  public function loadNodesFromProprefsDataProvider() {
    return [
      [['ABC123_ZZ']],
      [['ABC123_ZZ', 'ABC123_ZZ']],
      [[]],
    ];
  }

  /**
   * @covers ::updateNodeInstanceFromData
   * @dataProvider updateNodeInstanceFromDataDataProvider
   */
  public function testUpdateNodeInstanceFromData($updatedValues) {
    $this->mockedField->expects($this->any())
      ->method('setValue')
      ->willReturn(NULL);

    $updateResult = $this->serviceInstance->updateNodeInstanceFromData($updatedValues, $this->mockedNode);
    $is_bool = is_bool($updateResult);

    $this->assertTrue($is_bool, 'Assert tht return = boolean value.');
  }

  public function updateNodeInstanceFromDataDataProvider() {
    return [
      [
        array (
          'body' =>
            array (
            ),
          'title' =>
            array (
              'value' => 'Cottage 401',
            ),
          'field_cottage_name' =>
            array (
              'value' => 'Cottage 401',
            ),
          'field_cottage_brandcode' =>
            array (
              'value' => 'ZZ',
            ),
          'field_cottage_slug' =>
            array (
              'value' => 'waw298-zz',
            ),
          'field_cottage_ownercode' =>
            array (
              'value' => 'W298P',
            ),
          'field_cottage_url' =>
            array (
              'uri' => 'http://zz.api.carltonsoftware.co.uk/property/WAW298_ZZ',
              'title' => 'Cottage 401',
              'options' =>
                array (
                ),
            ),
          'field_cottage_teaser_description' =>
            array (
              'value' => '',
              'format' => NULL,
            ),
          'field_cottage_reference_code' =>
            array (
              'value' => 'WAW298',
            ),
          'field_cottage_booking' =>
            array (
              'uri' => 'http://zz.api.carltonsoftware.co.uk/booking',
              'title' => 'Booking',
              'options' =>
                array (
                ),
            ),
          'field_cottage_accommodates' =>
            array (
              'value' => '8',
            ),
          'field_cottage_pets' =>
            array (
              'value' => '0',
            ),
          'field_cottage_bedrooms' =>
            array (
              'value' => '4',
            ),
          'field_cottage_promote' =>
            array (
              'value' => '0',
            ),
          'field_cottage_rating' =>
            array (
              'value' => '4',
            ),
          'field_cottage_changeover_day' =>
            array (
              'value' => 'Saturday',
            ),
          'field_cottage_pricing' =>
            array (
              'value' => '{"bookingBrand":"ZZ","ranges":{"2017":{"high":0,"low":999999,"allowBookingOnWeb":false},"2018":{"high":0,"low":999999,"allowBookingOnWeb":false}},"searchPrice":null}',
            ),
          'field_cottage_coordinates' =>
            array (
              0 =>
                array (
                  'value' => '51.692',
                ),
              1 =>
                array (
                  'value' => '-4.2743',
                ),
            ),
          'field_cottage_address' =>
            array (
              'address_line1' => 'Hillside',
              'address_line2' => 'Prestwich',
              'locality' => 'Barnsley',
              'administrative_area' => 'Worcestershire',
              'postal_code' => 'LU5 6BJ',
              'country_code' => 'GB',
              'langcode' => NULL,
              'dependent_locality' => NULL,
              'sorting_code' => NULL,
              'organization' => NULL,
              'given_name' => NULL,
              'additional_name' => NULL,
              'family_name' => NULL,
            ),
          'field_cottage_image_info' =>
            array (
              0 =>
                array (
                  'value' => '{"filename":"odwxyo--w298-1.jpg","alt":"","title":"w298-1","width":1000,"height":751,"url":"http:\\/\\/zz.api.carltonsoftware.co.uk\\/image\\/normal\\/1000x751\\/odwxyo--w298-1.jpg"}',
                ),
              1 =>
                array (
                  'value' => '{"filename":"odwxyo--w298-2.jpg","alt":"","title":"w298-2","width":1000,"height":751,"url":"http:\\/\\/zz.api.carltonsoftware.co.uk\\/image\\/normal\\/1000x751\\/odwxyo--w298-2.jpg"}',
                ),
              2 =>
                array (
                  'value' => '{"filename":"odwxyo--w298-3.jpg","alt":"","title":"w298-3","width":1000,"height":751,"url":"http:\\/\\/zz.api.carltonsoftware.co.uk\\/image\\/normal\\/1000x751\\/odwxyo--w298-3.jpg"}',
                ),
              3 =>
                array (
                  'value' => '{"filename":"odwxyo--w298-4.jpg","alt":"","title":"w298-4","width":1000,"height":751,"url":"http:\\/\\/zz.api.carltonsoftware.co.uk\\/image\\/normal\\/1000x751\\/odwxyo--w298-4.jpg"}',
                ),
              4 =>
                array (
                  'value' => '{"filename":"odwxyo--w298-5.jpg","alt":"","title":"w298-5","width":1000,"height":751,"url":"http:\\/\\/zz.api.carltonsoftware.co.uk\\/image\\/normal\\/1000x751\\/odwxyo--w298-5.jpg"}',
                ),
              5 =>
                array (
                  'value' => '{"filename":"odwxyo--w298-6.jpg","alt":"","title":"w298-6","width":1000,"height":751,"url":"http:\\/\\/zz.api.carltonsoftware.co.uk\\/image\\/normal\\/1000x751\\/odwxyo--w298-6.jpg"}',
                ),
            ),
          'field_cottage_featured_image' =>
            array (
              'uri' => 'http://zz.api.carltonsoftware.co.uk/image/normal/1000x751/odwxyo--w298-1.jpg',
              'title' => '',
              'options' =>
                array (
                ),
            ),
          'field_cottage_images' =>
            array (
              0 =>
                array (
                  'uri' => 'http://zz.api.carltonsoftware.co.uk/image/normal/1000x751/odwxyo--w298-1.jpg',
                  'title' => '',
                  'options' =>
                    array (
                    ),
                ),
              1 =>
                array (
                  'uri' => 'http://zz.api.carltonsoftware.co.uk/image/normal/1000x751/odwxyo--w298-2.jpg',
                  'title' => '',
                  'options' =>
                    array (
                    ),
                ),
              2 =>
                array (
                  'uri' => 'http://zz.api.carltonsoftware.co.uk/image/normal/1000x751/odwxyo--w298-3.jpg',
                  'title' => '',
                  'options' =>
                    array (
                    ),
                ),
              3 =>
                array (
                  'uri' => 'http://zz.api.carltonsoftware.co.uk/image/normal/1000x751/odwxyo--w298-4.jpg',
                  'title' => '',
                  'options' =>
                    array (
                    ),
                ),
              4 =>
                array (
                  'uri' => 'http://zz.api.carltonsoftware.co.uk/image/normal/1000x751/odwxyo--w298-5.jpg',
                  'title' => '',
                  'options' =>
                    array (
                    ),
                ),
              5 =>
                array (
                  'uri' => 'http://zz.api.carltonsoftware.co.uk/image/normal/1000x751/odwxyo--w298-6.jpg',
                  'title' => '',
                  'options' =>
                    array (
                    ),
                ),
            ),
          'field_cottage_attributes' =>
            array (
              0 =>
                array (
                  'target_id' => '1',
                  'value' => '2',
                ),
              1 =>
                array (
                  'target_id' => '2',
                  'value' => 'false',
                ),
              2 =>
                array (
                  'target_id' => '3',
                  'value' => 'false',
                ),
              3 =>
                array (
                  'target_id' => '4',
                  'value' => '0.5',
                ),
              4 =>
                array (
                  'target_id' => '5',
                  'value' => '0.5',
                ),
              5 =>
                array (
                  'target_id' => '6',
                  'value' => 'true',
                ),
              6 =>
                array (
                  'target_id' => '7',
                  'value' => 'false',
                ),
              7 =>
                array (
                  'target_id' => '8',
                  'value' => '0',
                ),
              8 =>
                array (
                  'target_id' => '9',
                  'value' => 'false',
                ),
              9 =>
                array (
                  'target_id' => '10',
                  'value' => 'false',
                ),
              10 =>
                array (
                  'target_id' => '11',
                  'value' => '4',
                ),
              11 =>
                array (
                  'target_id' => '12',
                  'value' => '3',
                ),
              12 =>
                array (
                  'target_id' => '13',
                  'value' => '0',
                ),
              13 =>
                array (
                  'target_id' => '14',
                  'value' => 'false',
                ),
              14 =>
                array (
                  'target_id' => '15',
                  'value' => 'false',
                ),
              15 =>
                array (
                  'target_id' => '16',
                  'value' => 'false',
                ),
              16 =>
                array (
                  'target_id' => '17',
                  'value' => 'false',
                ),
              17 =>
                array (
                  'target_id' => '18',
                  'value' => 'false',
                ),
              18 =>
                array (
                  'target_id' => '19',
                  'value' => 'true',
                ),
              19 =>
                array (
                  'target_id' => '20',
                  'value' => 'false',
                ),
              20 =>
                array (
                  'target_id' => '21',
                  'value' => '0',
                ),
              21 =>
                array (
                  'target_id' => '22',
                  'value' => 'false',
                ),
              22 =>
                array (
                  'target_id' => '23',
                  'value' => 'false',
                ),
              23 =>
                array (
                  'target_id' => '24',
                  'value' => 'false',
                ),
              24 =>
                array (
                  'target_id' => '25',
                  'value' => 'false',
                ),
              25 =>
                array (
                  'target_id' => '26',
                  'value' => 'false',
                ),
              26 =>
                array (
                  'target_id' => '28',
                  'value' => 'false',
                ),
              27 =>
                array (
                  'target_id' => '29',
                  'value' => '"Travel cot"',
                ),
              28 =>
                array (
                  'target_id' => '30',
                  'value' => '""',
                ),
              29 =>
                array (
                  'target_id' => '31',
                  'value' => 'true',
                ),
              30 =>
                array (
                  'target_id' => '32',
                  'value' => '"\\"#PROPREF#\\",\\"2014\\",\\"#ATTR169#\\",\\"4 C\\",\\"#ACCOMDESC#\\",\\"#ACCOM#\\",\\"+ cot\\",\\"Provided\\",\\"No\\",\\"#PRICE_A#\\",\\"#PRICE_B#\\",\\"#PRICE_C#\\",\\"#PRICE_D#\\",\\"\\",\\"Electricity & heating included\\",\\"#ATTR163#\\",\\"#ATTR164#\\",\\"SN429017\\",\\"#LAT#\\",\\"#LONG#\\",\\"\\""',
                ),
              31 =>
                array (
                  'target_id' => '33',
                  'value' => '0',
                ),
              32 =>
                array (
                  'target_id' => '34',
                  'value' => '"House at Pembrey"',
                ),
              33 =>
                array (
                  'target_id' => '35',
                  'value' => 'true',
                ),
              34 =>
                array (
                  'target_id' => '36',
                  'value' => '""',
                ),
              35 =>
                array (
                  'target_id' => '37',
                  'value' => '""',
                ),
              36 =>
                array (
                  'target_id' => '38',
                  'value' => '""',
                ),
              37 =>
                array (
                  'target_id' => '39',
                  'value' => '""',
                ),
              38 =>
                array (
                  'target_id' => '40',
                  'value' => '""',
                ),
              39 =>
                array (
                  'target_id' => '41',
                  'value' => '""',
                ),
              40 =>
                array (
                  'target_id' => '42',
                  'value' => '0',
                ),
              41 =>
                array (
                  'target_id' => '43',
                  'value' => '""',
                ),
              42 =>
                array (
                  'target_id' => '44',
                  'value' => '""',
                ),
              43 =>
                array (
                  'target_id' => '45',
                  'value' => '""',
                ),
              44 =>
                array (
                  'target_id' => '46',
                  'value' => 'false',
                ),
              45 =>
                array (
                  'target_id' => '47',
                  'value' => 'false',
                ),
              46 =>
                array (
                  'target_id' => '48',
                  'value' => 'false',
                ),
              47 =>
                array (
                  'target_id' => '49',
                  'value' => 'false',
                ),
              48 =>
                array (
                  'target_id' => '50',
                  'value' => 'false',
                ),
              49 =>
                array (
                  'target_id' => '51',
                  'value' => 'false',
                ),
              50 =>
                array (
                  'target_id' => '52',
                  'value' => 'false',
                ),
              51 =>
                array (
                  'target_id' => '53',
                  'value' => 'true',
                ),
              52 =>
                array (
                  'target_id' => '54',
                  'value' => 'true',
                ),
              53 =>
                array (
                  'target_id' => '55',
                  'value' => 'false',
                ),
              54 =>
                array (
                  'target_id' => '56',
                  'value' => '3',
                ),
              55 =>
                array (
                  'target_id' => '57',
                  'value' => 'true',
                ),
              56 =>
                array (
                  'target_id' => '58',
                  'value' => 'true',
                ),
              57 =>
                array (
                  'target_id' => '59',
                  'value' => 'true',
                ),
              58 =>
                array (
                  'target_id' => '60',
                  'value' => 'false',
                ),
              59 =>
                array (
                  'target_id' => '61',
                  'value' => 'true',
                ),
              60 =>
                array (
                  'target_id' => '62',
                  'value' => 'true',
                ),
              61 =>
                array (
                  'target_id' => '63',
                  'value' => 'false',
                ),
              62 =>
                array (
                  'target_id' => '64',
                  'value' => 'true',
                ),
              63 =>
                array (
                  'target_id' => '65',
                  'value' => 'true',
                ),
              64 =>
                array (
                  'target_id' => '66',
                  'value' => 'false',
                ),
              65 =>
                array (
                  'target_id' => '67',
                  'value' => 'false',
                ),
              66 =>
                array (
                  'target_id' => '68',
                  'value' => 'true',
                ),
              67 =>
                array (
                  'target_id' => '69',
                  'value' => 'false',
                ),
              68 =>
                array (
                  'target_id' => '70',
                  'value' => 'false',
                ),
              69 =>
                array (
                  'target_id' => '71',
                  'value' => 'false',
                ),
              70 =>
                array (
                  'target_id' => '72',
                  'value' => 'false',
                ),
              71 =>
                array (
                  'target_id' => '73',
                  'value' => '2',
                ),
              72 =>
                array (
                  'target_id' => '74',
                  'value' => 'false',
                ),
              73 =>
                array (
                  'target_id' => '75',
                  'value' => 'true',
                ),
              74 =>
                array (
                  'target_id' => '76',
                  'value' => 'true',
                ),
              75 =>
                array (
                  'target_id' => '77',
                  'value' => 'true',
                ),
              76 =>
                array (
                  'target_id' => '78',
                  'value' => 'true',
                ),
              77 =>
                array (
                  'target_id' => '79',
                  'value' => 'false',
                ),
              78 =>
                array (
                  'target_id' => '80',
                  'value' => 'false',
                ),
              79 =>
                array (
                  'target_id' => '81',
                  'value' => 'false',
                ),
            ),
        ), // $updatedValues.
      ]
    ];
  }

  /**
   * @covers ::updateNodeInstancesFromData
   * @dataProvider fixturePropertyDataProvider
   */
  public function testUpdateNodeInstancesFromData($data) {

    $this->sqlQuery->expects($this->any())
      ->method('condition')
      ->willReturn($this->sqlQuery);
    $this->sqlQuery->expects($this->any())
      ->method('execute')
      ->willReturn([$data->propertyRef => $data->propertyRef]);

    $this->entityQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($this->sqlQuery);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->taxonomyTermStorage);

    $nodeStorageReturn = [];
    if($data->propertyRef !== "") {
      $nodeStorageReturn = [$data->propertyRef => $this->mockedEntity];
    }

    $this->taxonomyTermStorage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($nodeStorageReturn);

    $updatedProperties = $this->serviceInstance->updateNodeInstancesFromData($data);

    $is_array = is_array($updatedProperties);
    $this->assertTrue($is_array);
    $this->assertContains($data->propertyRef, $updatedProperties);
  }

  /**
   * @covers ::generateUpdateArray
   * @dataProvider generateUpdateArrayDataProvider
   */
  public function testGenerateUpdateArray($data, $is_node = TRUE) {
    $updateArray = $this->invokeMethod($this->serviceInstance, 'generateUpdateArray', [$data, $is_node]);

    $this->assertTrue(is_array($updateArray));

    $data_keys = array_values((array) $data);
    $update_keys = array_values($updateArray);
    if(!$is_node) {
      $this->assertArrayNotHasKey('type', $updateArray);
      $this->assertArrayNotHasKey('promote', $updateArray);
      // We subtract two here because we expect two values to be unset.
      $this->assertNotSameSize($data_keys, $update_keys);
    } else {
      $this->assertCount(26, $update_keys);
    }

  }

  public function generateUpdateArrayDataProvider() {
    $path =  "nt8/nt8property/src/Fixtures/H610_ZZ.json";
    $fixtureData = json_decode(file_get_contents($path));

    $unsetImages = clone $fixtureData;
    $unsetImages->images = [];

    return [
      [$fixtureData, true],
      [$fixtureData, false],
      [$unsetImages, true]
    ];
  }


  /**
   * @covers ::createNodeInstanceFromData
   * @dataProvider createNodeInstanceFromDataDataProvider
   */
  public function testCreateNodeInstanceFromData($data, $deleteExisting) {
    $this->entityQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($this->sqlQuery);

    $this->sqlQuery->expects($this->any())
      ->method('condition')
      ->willReturn($this->sqlQuery);
    $this->sqlQuery->expects($this->any())
      ->method('execute')
      ->willReturn([0,1]);

    $this->nodeStorage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn([]);
    $this->nodeStorage->expects($this->any())
      ->method('delete')
      ->willReturn(NULL);

    $this->nodeStorage->expects($this->any())
      ->method('create')
      ->willReturn($this->mockedNode);
    $this->entityTypeManager->expects($this->any())->method('getStorage')->willReturn($this->nodeStorage);

    if(isset($data->errorCode)) {
      $this->setExpectedException(\Exception::class);
    }

    $node = $this->serviceInstance->createNodeInstanceFromData($data, $deleteExisting);
    $this->assertTrue($node instanceof Node);
  }

  public function createNodeInstanceFromDataDataProvider() {
    $path =  "nt8/nt8property/src/Fixtures/H610_ZZ.json";
    $fixtureData = json_decode(file_get_contents($path));

    return [
      [$fixtureData, true],
      [$fixtureData, false],
      [
        (object) [
          'errorCode' => 100000000,
        ], true
      ]
    ];
  }

  public function fixturePropertyDataProvider() {
    $path =  "nt8/nt8property/src/Fixtures/H610_ZZ.json";
    $fixtureData = json_decode(file_get_contents($path));

    return [
      [$fixtureData, true],
      [$fixtureData, false]
    ];
  }

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object    Instantiated object that we will run method on.
   * @param string $methodName Method name to call
   * @param array  $parameters Array of parameters to pass into method.
   *
   * @return mixed Method return.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = array())
  {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
  }

  /**
   * Sets a protected property on a given object via reflection
   *
   * @param $object - instance in which protected value is being modified
   * @param $property - property on instance being modified
   * @param $value - new value of the property being modified
   *
   * @return void
   */
  public function setProtectedProperty($object, $property, $value)
  {
    $reflection = new \ReflectionClass($object);
    $reflection_property = $reflection->getProperty($property);
    $reflection_property->setAccessible(true);
    $reflection_property->setValue($object, $value);
  }

}
