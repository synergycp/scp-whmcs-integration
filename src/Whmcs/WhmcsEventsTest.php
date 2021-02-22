<?php

namespace Scp\Whmcs\Whmcs;

class WhmcsEventsTest extends \TestCase
{
  /**
   * @dataProvider dataCreate
   */
  public function testWipeProductDetails(string $input, string $expected) {
    $this->assertEquals($expected, WhmcsEvents::domainForTerminatedServer($input));
  }

  public function dataCreate()
  {
    return [
      'with srv ID' => [
        'Test test <srv123>',
        'Test test',
      ],
      'with escaped lt & gt' => [
        'Test test &lt;srv123&gt;',
        'Test test',
      ],
      'with escaped lt & gt' => [
        htmlentities('Test test <srv123>'),
        'Test test',
      ],
      'without srv ID' => [
        'Test test',
        'Test test',
      ],
      'with srv ID with spaces' => [
        'Test test < srv123 >',
        'Test test',
      ],
      'with srv ID not at end' => [
        'Test < srv123 > test',
        'Test < srv123 > test',
      ],
    ];
  }
}
