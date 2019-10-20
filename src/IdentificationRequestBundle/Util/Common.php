<?php
/*
 * This file is part of the IdentificationRequestBundle Command package.
 *
 * Created By Abbas Uddin Sheikh
 *
 * The Purpose of the file is to support common functionalties
 * if it works properly it returns valid other wise respective error message
 */

namespace IdentificationRequestBundle\Util;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Common extends Bundle
{
 
    /** @var Rules (Basic Rules)*/
    public $rules = [];

    /**
     * Constructor that initializes the requirements.
     */
    public function __construct()
    {
        $this->setIdentificationRules();
    }

    /**
     * differentiate two given date in defined format
     *
     * @param @var issue_date, request_date, format
     *
     * @return number in given format
     */
    public function dateInterval($date1, $date2, $format = "%y")
    {
        $date1  = date_create($date1);
        $date2 = date_create($date2);
        $interval = date_diff($date1, $date2);
        return $interval->format($format);
    }

    /**
     * Check if document is expired as per issue date
     *
     * @param @var issue_date, request_date, country
     *
     * @return document_is_expired if issue date exceeds as per defined time
     */

    public function isDocumentExpired($issue_date, $request_date, $country)
    {
        $interval_years = $this->dateInterval($issue_date, $request_date, "%y");
        if (in_array($country, $this->rules["specific_countries"])) {
            if ($interval_years < $this->rules[$country]["expired_years_after_issue"]) {
                return ;
            } else {
                return "document_is_expired";
            }
        }

        if ($interval_years < 5) {
            return ;
        }
        //*/
        return "document_is_expired";
    }

        /**
     * Check document number as per deinfed rules
     *
     * @param document number, document type, country
     *
     * @return document_number_invalid if number is found within stolen range
     */

    public function isDocumentNumberValid($document_number, $document_type, $country)
    {
        //
        if ($country == "es" && $document_type == "passport"
        && $document_number>= 50001111
        && $document_number <= 50009999) {
            return "document_number_invalid";
        } else {
            return;
        }
    }


    /**
     * Set Basic Rules into @var Rules
     *
     * @param
     *
     * @return @Rules Object
     */

    public function setIdentificationRules()
    {
        /** Basic Rules */
        $this->rules["supported_documents"] = ["passport", "identity_card", "residence_permit"];
        $this->rules["expired_years_after_issue"] = 5;
        $this->rules["document_number_length"] = 8;
        $this->rules["permitted_issue_dates"] = ["Mon", "Tue", "Wed", "Thu", "Fri"];
        $this->rules["specific_countries"] = ["de", "es", "fr", "pl", "it", "uk"];
        /** Specific Rules for de */
        $this->rules["de"]["code"] = "de";
        $this->rules["de"]["document_types"] = ["identity_card"];
        $this->rules["de"]["expired_years_after_issue"] = 10;
        /** Specific Rules for es */
        $this->rules["es"]["code"] = "es";
        $this->rules["es"]["document_types"] = ["passport"];
        $this->rules["es"]["expired_years_after_issue"] = 15;
        /** Specific Rules for fr */
        $this->rules["fr"]["code"] = "fr";
        $this->rules["fr"]["document_types"] = ["passport", "identity_card", "residence_permit", "drivers_license"];
        $this->rules["fr"]["expired_years_after_issue"] = 5;
        /** Specific Rules for pl */
        $this->rules["pl"]["code"] = "pl";
        $this->rules["pl"]["document_types"] = ["passport", "identity_card", "residence_permit"];
        $this->rules["pl"]["expired_years_after_issue"] = 5;
        /** Specific Rules for it */
        $this->rules["it"]["code"] = "it";
        $this->rules["it"]["permitted_issue_dates"] = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        $this->rules["it"]["document_types"] = ["passport", "identity_card", "residence_permit"];
        $this->rules["it"]["expired_years_after_issue"] = 5;
        /** Specific Rules for uk*/
        $this->rules["uk"]["code"] = "uk";
        $this->rules["uk"]["document_types"] = ["passport"];
        $this->rules["uk"]["expired_years_after_issue"] = 5;
    }
}
