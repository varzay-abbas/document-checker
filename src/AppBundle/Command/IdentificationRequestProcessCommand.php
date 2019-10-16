<?php
/*
 * This file is part of the AppBundle Command package.
 *
 * Created By Abbas Uddin Sheikh
 *
 * The Purpose of the file is to identify document csv data
 * if it works properly it returns valid other wise respective error message
 */


namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;

class IdentificationRequestProcessCommand extends ContainerAwareCommand
{
    /** @var Pin Array (Personal Identifcation Number)*/
    private $pins = [];
    /** @var Pin wise request dates (Basic Rules)*/
    private $dn_request_dates = [];
    /** @var Rules (Basic Rules)*/
    private $rules = [];

    protected function configure()
    {
        $this
            ->setName('identification-request:process')
            ->setDescription('...')
            ->addArgument('argument', InputArgument::REQUIRED, 'Provide CSV File Name:')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('argument');
        if (!empty($argument)) {
            $this->checkDocument($argument);
        }
    }

    /**
     * Check document
     *
     * @param @var argument input.csv
     *
     * @return document_type_is_invalid if type is not defined as per country rules
     */

    public function checkDocument($argument)
    {
        $this->setIdentificationRules();
        $reader = Reader::createFromPath('%kernel.root_dir%/../'.$argument);
        $results = $reader->fetchAll();
        $messages = "";
        $message = "";

        foreach ($results as  $row) {

            /** @var message */
            $message = "";
            if (!empty($row)) {

                /** @var data */
                $data = array_values($row);
                if (count($data) == 6) {
                    $identification_request_date = $data[0];
                    $identity_document_country_code = $data[1];
                    $identity_document_type = $data[2];
                    $identity_document_number = $dn = $data[3];
                    $identity_document_issue_date = $data[4];
                    $personal_identification_number = $pin = $data[5];
                    /** pins: Personal identification number */
                    $this->pins[] = $pin;
                    $this->dn_request_dates[$dn] = $pin."#".$identification_request_date;
                    $message .= $this->isDocumentTypeValid($identity_document_type, $identity_document_country_code);
                    $message .= $this->isDocumentExpired($identity_document_issue_date, $identification_request_date, $identity_document_country_code);
                    $message .= $this->isDocumentIssueDateValid($identity_document_issue_date, $identity_document_country_code);
                    $message .= $this->isDocumentNumberLengthValid($identity_document_number, $identity_document_issue_date, $identity_document_type, $identity_document_country_code);
                    $message .= $this->isDocumentNumberValid($identity_document_number, $identity_document_type, $identity_document_country_code);
                    /**pn: personal identification number */
                    $message .= $this->isRequestLimitExceeded($pin);
                }
            }

            if (empty($message)) {
                $message = "valid";
            }
        
            $messages .= $message."\n";
        }
   
        file_put_contents("php://output", $messages);
    }

    /**
     * Check document type
     *
     * @param @var type, @var country
     *
     * @return document_type_is_invalid if type is not defined as per country rules
     */

    public function isDocumentTypeValid($type, $country)
    {
        if (in_array($country, $this->rules["specific_countries"])) {
            if (in_array($type, $this->rules[$country]["document_types"])) {
                return ;
            } else {
                return "document_type_is_invalid";
            }
        }

        if (in_array($type, $this->rules["supported_documents"])) {
            return ;
        }

        return "document_type_is_invalid";
    }
    
    /**
     * differentiate two given date in defined format
     *
     * @param @var issue_date, request_date, format
     *
     * @return number in given format
     */
    public function date_interval($date1, $date2, $format = "%y")
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
        $interval_years = $this->date_interval($issue_date, $request_date, "%y");
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
     * Check document number length as per defined rules
     *
     * @param @var document number, issue_date, document type, country
     *
     * @return document_number_length_invalid if length is found more than defined one
     */

    public function isDocumentNumberLengthValid($document_number, $issue_date, $document_type, $country)
    {
        $issue_date = date("Y-m-d", strtotime($issue_date));
        $new_date = date("Y-m-d", strtotime("2018-09-01"));
       
        if (in_array($country, $this->rules["specific_countries"]) && $country == "pl") {
            if ($document_type == "identity_card" && $issue_date>= $new_date && strlen($document_number) == 10) {
                return ;
            } elseif (($document_type == "residence_permit" || $document_type == "passport") && strlen($document_number) == 8) {
                return ;
            } else {
                return "document_number_length_invalid";
            }
        }

        if (strlen($document_number) == 8) {
            return ;
        }

        //
        return "document_number_length_invalid";
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
        if ($country == "es" && $document_type == "passport" && $document_number>= 50001111 && $document_number <= 50009999) {
            return "document_number_invalid";
        } else {
            return;
        }
    }

    /**
     * Check number for request for each  5 working days
     *
     * @param @var issue_date, country
     *
     * @return document_issue_date_invalid if it's issue day is not within defined days
     */
    public function isDocumentIssueDateValid($issue_date, $country)
    {
        //
        $day = date("D", strtotime($issue_date));
        $issue_date = date("Y-m-d", strtotime($issue_date));
        //
        $start_date = date("Y-m-d", strtotime("2019-01-01"));
        $end_date = date("Y-m-d", strtotime("2019-01-31"));

        if (in_array($country, $this->rules["specific_countries"]) && $country == "it") {
            if (in_array($day, $this->rules[$country]["permitted_issue_dates"])
                && ($issue_date>=$start_date && $issue_date <= $end_date)
             ) {
                return ;
            } else {
                return "document_issue_date_invalid";
            }
        }

        if (in_array($day, $this->rules["permitted_issue_dates"])) {
            return ;
        }

        return "document_issue_date_invalid";
    }

    /**
     * Check number for request for each  5 working days
     *
     * @params pin :personal identification number, dn:document number
     *
     * @return request_limit_exceeded if attempts is found more than twice
     */
    public function isRequestLimitExceeded($pin)
    {
        //
        $pids = array_count_values($this->pins);
        //print_r($this->pin_request_dates);

        
        //die();
        if ($pids[$pin]>2) {
            $request_number = 0;
            /** 2nd time request date*/
            $request_date_2nd = "";
            /** 3rd time request date */
            $request_date_3rd = "";
            foreach ($this->dn_request_dates as $id => $rd) {
                if (stristr($rd, $pin)) {
                    $request_number++;
                    /** assign 2nd request date for this pin */
                    if ($request_number == 2) {
                        $request_date_2nd = explode("#", $rd)[1];
                    }
                    /** verify date diff if req attemp is 3 */
                    if ($request_number == 3) {
                        $request_date_3rd = explode("#", $rd)[1];
                        $day_diff = $this->date_interval($request_date_2nd, $request_date_3rd, "%d");
                        if ($day_diff <= 5) {
                            return "request_limit_exceeded";
                        }
                    }
                }
            }
        }
        return ;
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
