<?php
/*
 * This file is part of the AppBundle Command package.
 *
 * Created By Abbas Uddin Sheikh
 *
 * The Purpose of the file is to test the document csv data
 *  if it works properly
 */

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use League\Csv\Reader;

class IdentificationControllerTest extends WebTestCase
{
    /** @test */
    public function testCSV()
    {
        $reader = Reader::createFromPath('%kernel.root_dir%/../input.csv');
        $results = $reader->fetchAll();
        $request_date = $results[0][0];
        $country_code = $results[0][1];

        $this->assertEquals("2019-01-01", $request_date);
        $this->assertContains('lt', $country_code);
        $this->assertEquals(14, count($results));
    }

    /** @test Document Type */
    public function testIsDocumentTypeValid()
    {
        $reader = Reader::createFromPath('%kernel.root_dir%/../input.csv');
        $results = $reader->fetchAll();        
        //For uk only passport but it has identy_card
        $this->assertNotEquals("passport", $results[8][2]);
    }

    /** @test Document Number */
    public function testIDocumentNumberValid()
    {
        $reader = Reader::createFromPath('%kernel.root_dir%/../input.csv');
        $results = $reader->fetchAll();
        $country = $results[11][1];
        $document_type = $results[11][2];
        $document_number = $results[11][3];
        $message = "";
        if ($country == "es" && $document_type == "passport" && $document_number>= 50001111 && $document_number <= 50009999) {
            $message = "document_number_invalid";
        }
        $this->assertEquals("document_number_invalid", $message);
    }
}
