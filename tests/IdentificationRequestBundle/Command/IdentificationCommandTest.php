<?php
/*
 * This file is part of the IdentificationRequestBundle Command package.
 *
 * Created By Abbas Uddin Sheikh
 *
 * The Purpose of the file is to test the document csv data
 *  if it works properly
 */

namespace Tests\IdentificationRequestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use League\Csv\Reader;
use IdentificationRequestBundle\Util\Common;

class IdentificationCommandTest extends WebTestCase
{

    /** @var CustomerRepository|PHPUnit_Framework_MockObject_MockObject */
    private $customerRepositoryMock;
    /** @var CommandTester */
    private $commandTester;
    /** @var $this->results from Reader Object */
    private $results = [];
    /** @var $common */
    private $common;
    
    
    protected function setUp()
    {
        $reader = Reader::createFromPath('%kernel.root_dir%/../input.csv');
        $this->results = $reader->fetchAll();
        $this->common = new Common();
    }

    /** @test Document Type */
    public function testIsDocumentTypeValid()
    {
        //For uk only passport but it has identy_card
        $this->assertNotEquals("passport", $this->results[8][2]);
    }

    /**
     * @test Document Number
     *
     * @dataProvider documentNumberProvider
     */
    public function testIsDocumentNumberValid($document_number, $document_type, $country, $expected)
    {
        $result = $this->common->isDocumentNumberValid($document_number, $document_type, $country);
        $this->assertEquals("document_number_invalid", $result);
    }
    /**
     * Test to check if document is expired
     * for given issue_date, request_date & country.
     *
     * @param string $issue_date
     * @param string $request_date
     * @param string $country
     * @param string $expected
     *
     * @dataProvider documentExpiredProvider
     */
    public function testIsDocumentExpired($issue_date, $request_date, $country, $expected)
    {
        $result = $this->common->isDocumentExpired($issue_date, $request_date, $country);
        $this->assertEquals(
            $expected,
            $result
        );
    }

    /**
     * Data provider for TestIsDocumentExpired
     * variables are in the order of
     * $issue_date, $request_date, $country, $expected.
     *
     * @return type
     */
    public function documentExpiredProvider()
    {
         return [
            ['2019-01-03', '2009-01-01', 'de', 'document_is_expired'],
            ['2019-01-01', '2019-03-01', 'lt', null]
         ];
    }

    /**
     * Data provider for TestIsDocumentNumberValid
     * variables are in the order of
     * $issue_date, $request_date, $country, $expected.
     *
     * @return type
     */
    public function documentNumberProvider()
    {
         return [
            ['50008532','passport', 'es', 'document_number_invalid']
            
         ];
    }

    /** @test */
    public function testCSV()
    {
        $request_date = $this->results[0][0];
        $country_code = $this->results[0][1];

        $this->assertEquals("2019-01-01", $request_date);
        $this->assertContains('lt', $country_code);
        $this->assertEquals(14, count($this->results));
    }
}
