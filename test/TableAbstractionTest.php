<?php

require_once 'TableAbstractionDummy.php';
require_once 'PHPUnit.php';

class TableAbstractionTest extends PHPUnit_TestCase
{
    var $dummy;

    // constructor of the test suite
    function TableAbstractionTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->dummy = new TableAbstractionDummy();

        $db = DBInstance::get();
        $db->query("
            CREATE TABLE DummyTable (
                dummy_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                field VARCHAR(255) NOT NULL UNIQUE
            )
        ");
        $db->query("
            INSERT INTO DummyTable VALUES
            (1, 'foo'),
            (2, 'bar'),
            (3, 'baz')
        ");
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->dummy );

        $db = DBInstance::get();
        $db->query("DROP TABLE DummyTable");
    }

    // test that we can retrieve the value set with setID() using getID() method
    function testSettingIDWithConstructor() {
        $id = 256;
        $dummy = new TableAbstractionDummy( $id );
        $this->assertEquals( $id, $dummy->getID() );
    }

    // test that we can retrieve the value set with setID() using getID() method
    function testGetSetID() {
        $expected = 256;
        $this->dummy->setID( $expected );
        $this->assertEquals( $expected, $this->dummy->getID() );
    }

    // test that we can retrieve the value set with setField() using getField() method
    function testGetSetField() {
        $expected = 'FooBar';
        $this->dummy->setField( $expected );
        $this->assertEquals( $expected, $this->dummy->getField() );
    }

    // test that we can retrieve the value from database
    // by setting ID with setID() and asking for the value with getField()
    function testGetField() {
        $this->dummy->setID( 2 );
        $this->assertEquals( 'bar', $this->dummy->getField() );
    }

    function testSaveInsert() {
        $this->dummy->setField('Big Ben');

        // check successfull save
        $this->assertTrue( $this->dummy->save() );

        // check normal ID
        $id = $this->dummy->getID();
        $this->assertTrue( $id > 0 );

        if ( $id > 0 ) {
            // check that the record was added
            $db = DBInstance::get();
            $count = $db->getOne("SELECT COUNT(*) FROM DummyTable WHERE field='Big Ben'");
            $this->assertEquals( 1, $count );

            // check that we now have 4 records in table
            $count = $db->getOne("SELECT COUNT(*) FROM DummyTable");
            $this->assertEquals( 4, $count );
        }
    }

    function testSaveInsertDuplicate() {
        $this->dummy->setField('foo');

        // check that the save returns false because of duplicate
        $this->assertFalse( $this->dummy->save() );
    }

    function testSaveUpdate() {
        $this->dummy->setID(2);
        $this->dummy->setField('Big Ben');

        // check successfull save
        $this->assertTrue( $this->dummy->save() );

        // check normal ID
        $id = $this->dummy->getID();
        $this->assertTrue( $id > 0 );

        if ( $id > 0 ) {
            // check that the record was changed
            $db = DBInstance::get();
            $count = $db->getOne("SELECT COUNT(*) FROM DummyTable WHERE field='Big Ben'");
            $this->assertEquals( 1, $count );

            // check that we still have 3 records in table
            $count = $db->getOne("SELECT COUNT(*) FROM DummyTable");
            $this->assertEquals( 3, $count );
        }
    }

    function testSaveUpdateDuplicate() {
        $this->dummy->setID(2);
        $this->dummy->setField('baz');

        // check that the save returns false because of duplicate
        $this->assertFalse( $this->dummy->save() );
    }

    function testSaveUpdateWithLoadingOtherFieldsFromDB() {
        $this->dummy->setID(2);
        $this->dummy->loadFromDatabaseByID();

        // check successfull save
        $this->assertTrue( $this->dummy->save() );

        // check that the record remained unchanged
        $db = DBInstance::get();
        $count = $db->getOne("SELECT COUNT(*) FROM DummyTable WHERE dummy_id=2 AND field='bar'");
        $this->assertEquals( 1, $count );
    }

    function testDeleteExisting() {
        $this->dummy->setID(2);

        // check successfull delete
        $this->assertTrue( $this->dummy->delete() );

        // check that record 2 is missing
        $db = DBInstance::get();
        $count = $db->getOne("SELECT COUNT(*) FROM DummyTable WHERE dummy_id=2");
        $this->assertEquals( 0, $count );

        // check that we now have only 2 records in table
        $count = $db->getOne("SELECT COUNT(*) FROM DummyTable");
        $this->assertEquals( 2, $count );
    }

    function testDeleteUnExisting() {
        $this->dummy->setID(4);

        // check that delete fails because of unexisting ID value
        $this->assertFalse( $this->dummy->delete() );

        // check that we still have 3 records in table
        $db = DBInstance::get();
        $count = $db->getOne("SELECT COUNT(*) FROM DummyTable");
        $this->assertEquals( 3, $count );
    }

}

?>
