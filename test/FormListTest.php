<?php

require_once 'FormList.php';
require_once 'PHPUnit.php';

class FormListTest extends PHPUnit_TestCase
{
    // constructor of the test suite
    function FormListTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Forms
                (form_id, school_id, form_name)
            VALUES
                (9999991, 7777771, 'First form'),
                (9999992, 7777771, 'Second form'),
                (9999993, 7777771, 'Third form'),
                (9999994, 7777771, 'Fourth form'),
                (9999995, 7777772, 'Fifth form')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        $db = DBInstance::get();
        $db->query("DELETE FROM Forms WHERE school_id = 7777771 OR school_id = 7777772");
    }


    function testGetFromSchool() {
        // assign this ID to the school
        $school = new School();
        $school->setID(7777771);

        // get the forms
        $forms = FormList::getFromSchool( $school );

        // check, that we have retrieved the correct amount of forms
        $this->AssertEquals( 4, count($forms) );
        // and the names of the forms are correct
        if ( count($forms) == 4 ) {
            $this->AssertEquals( 'First form', $forms[0]->getName() );
            $this->AssertEquals( 'Fourth form', $forms[1]->getName() );
            $this->AssertEquals( 'Second form', $forms[2]->getName() );
            $this->AssertEquals( 'Third form', $forms[3]->getName() );
        }
    }


    function testGetFormsSelectBox() {
        // assign this ID to the school
        $school = new School();
        $school->setID(7777771);

        // get select box options
        $options = FormList::getFormsSelectBox( $school );

        // check, that we have retrieved the correct amount
        $this->AssertEquals( 4, count($options) );

        // check all the entries
        if ( count($options) == 4 ) {
            $this->AssertEquals( 'First form', $options[9999991] );
            $this->AssertEquals( 'Second form', $options[9999992] );
            $this->AssertEquals( 'Third form', $options[9999993] );
            $this->AssertEquals( 'Fourth form', $options[9999994] );
        }
    }


    function testGetFormsSelectBox_EmptyFirstItem() {
        // assign this ID to the school
        $school = new School();
        $school->setID(7777771);

        // get select box options
        $options = FormList::getFormsSelectBox( $school, true );

        // check, that we have retrieved the correct amount
        $this->AssertEquals( 5, count($options) );

        // check all the entries
        if ( count($options) == 5 ) {
            $this->AssertEquals( '', $options[0] );
            $this->AssertEquals( 'First form', $options[9999991] );
            $this->AssertEquals( 'Second form', $options[9999992] );
            $this->AssertEquals( 'Third form', $options[9999993] );
            $this->AssertEquals( 'Fourth form', $options[9999994] );
        }
    }


    function testGetFormsSelectBox_PseudoLastItems() {
        // assign this ID to the school
        $school = new School();
        $school->setID(7777771);

        // get select box options
        $options = FormList::getFormsSelectBox( $school, false, true );

        // check, that we have retrieved the correct amount
        $this->AssertEquals( 6, count($options) );

        // check all the entries
        if ( count($options) == 6 ) {
            $this->AssertEquals( 'First form', $options[9999991] );
            $this->AssertEquals( 'Second form', $options[9999992] );
            $this->AssertEquals( 'Third form', $options[9999993] );
            $this->AssertEquals( 'Fourth form', $options[9999994] );
            $this->AssertTrue( isset($options["teacher"]) );
            $this->AssertTrue( isset($options["schooladmin"]) );
        }
    }


    function testGetFormsSelectBox_AllPossibleItems() {
        // assign this ID to the school
        $school = new School();
        $school->setID(7777771);

        // get select box options
        $options = FormList::getFormsSelectBox( $school, true, true );

        // check, that we have retrieved the correct amount
        $this->AssertEquals( 7, count($options) );

        // check all the entries
        if ( count($options) == 7 ) {
            $this->AssertEquals( '', $options[0] );
            $this->AssertEquals( 'First form', $options[9999991] );
            $this->AssertEquals( 'Second form', $options[9999992] );
            $this->AssertEquals( 'Third form', $options[9999993] );
            $this->AssertEquals( 'Fourth form', $options[9999994] );
            $this->AssertTrue( isset($options["teacher"]) );
            $this->AssertTrue( isset($options["schooladmin"]) );
        }
    }


}

?>
