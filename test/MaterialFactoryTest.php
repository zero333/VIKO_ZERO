<?php

require_once 'MaterialFactory.php';
require_once 'TableAbstractionDummy.php';
require_once 'PHPUnit.php';

class MaterialFactoryTest extends PHPUnit_TestCase
{
    // constructor of the test suite
    function MaterialFactoryTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Materials (
                material_id,
                user_id,
                course_id,
                parent_id,
                material_type,
                mime_type,
                material_name,
                material_description,
                material_uri,
                material_addtime,
                material_size
                )
            VALUES
                (1, 1, 1, NULL, 'FOLDER', '',           'Folder',     'Little folder',            '',                  '2006-05-04 14:00:00', 0),
                (2, 1, 1, NULL, 'FILE',   'image/jpeg', 'my_dog.jpg', 'Picture of my pretty dog', '',                  '2006-05-04 15:00:00', 123456),
                (3, 1, 1,    1, 'LINK',   '',           'google',     'A search engine',          'http://google.com', '2006-05-04 16:00:00', 0),
                (4, 1, 1,    1, 'FILE',   'text/plain', 'Lesson 1',   '',                         '',                  '2006-05-04 18:00:00', 1024)
            "
        );
        $db->query(
            "
            INSERT INTO Material_Contents
                (material_id, material_content)
            VALUES
                (2, 'JPEG_9876543210'),
                (4, 'Do some stuff at home.')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        $db = DBInstance::get();
        $db->query("DELETE FROM Materials");
        $db->query("DELETE FROM Material_Contents");
    }

    function testMaterial()
    {
        $material = MaterialFactory::material( "FILE" );
        $this->assertEquals( "FILE", $material->getType() );

        $material = MaterialFactory::material( "LINK" );
        $this->assertEquals( "LINK", $material->getType() );

        $material = MaterialFactory::material( "FOLDER" );
        $this->assertEquals( "FOLDER", $material->getType() );
    }

    function testMaterialByID()
    {
        $material = MaterialFactory::materialByID( 1 );
        $this->assertEquals( "FOLDER", $material->getType() );

        $material = MaterialFactory::materialByID( 2 );
        $this->assertEquals( "FILE", $material->getType() );

        $material = MaterialFactory::materialByID( 3 );
        $this->assertEquals( "LINK", $material->getType() );
    }

    function testFile()
    {
        $file = MaterialFactory::file();
        $this->assertEquals( "FILE", $file->getType() );
    }

    function testFolder()
    {
        $folder = MaterialFactory::folder();
        $this->assertEquals( "FOLDER", $folder->getType() );
    }

    function testLink()
    {
        $link = MaterialFactory::link();
        $this->assertEquals( "LINK", $link->getType() );
    }

    function testSetGetAttributes_Folder() {
        $id = 123;
        $material = MaterialFactory::file( $id );

        $owner = new TableAbstractionDummy( 123 );
        $material->setOwner( $owner );

        $course = new TableAbstractionDummy( 256 );
        $material->setCourse( $course );

        $parent = new TableAbstractionDummy( 112 );
        $material->setParent( $parent );

        $name = 'One big folder';
        $material->setName( $name );

        $description = 'A good folder for files';
        $material->setDescription( $description );

        $date = new Date('2001-02-03 01:02:03');
        $material->setAddTime( $date );


        // check that getting these values gives the correct results
        $this->assertEquals( $id, $material->getID() );
        $this->assertEquals( $owner, $material->getOwner() );
        $this->assertEquals( $course, $material->getCourse() );
        $this->assertEquals( $parent, $material->getParent() );
        $this->assertEquals( $name, $material->getName() );
        $this->assertEquals( $description, $material->getDescription() );
        $this->assertEquals( $date, $material->getAddTime() );
    }

    function testSetGetAttributes_File() {
        $id = 123;
        $material = MaterialFactory::file( $id );

        $mime_type = 'image/jpeg';
        $material->setMimeType( $mime_type );

        $size = 1024;
        $material->setSize( $size );


        // check that getting these values gives the correct results
        $this->assertEquals( $id, $material->getID() );
        $this->assertEquals( $mime_type, $material->getMimeType() );
        $this->assertEquals( $size, $material->getSize() );
    }

    function testSetGetAttributes_Link() {
        $id = 123;
        $material = MaterialFactory::link( $id );

        $uri = 'http://www.example.com';
        $material->setURI( $uri );


        // check that getting these values gives the correct results
        $this->assertEquals( $id, $material->getID() );
        $this->assertEquals( $uri, $material->getURI() );
    }


    function testGetAttributes_Folder() {
        // set ID to be equal to the one file ID in database
        $material = MaterialFactory::folder( 1 );


        $owner = $material->getOwner();
        $this->assertEquals( 1, $owner->getID() );

        $course = $material->getCourse();
        $this->assertEquals( 1, $course->getID() );

        $parent = $material->getParent();
        $this->assertEquals( null, $parent );

        $this->assertEquals( 'FOLDER', $material->getType() );

        $this->assertEquals( 'Folder', $material->getName() );

        $this->assertEquals( 'Little folder', $material->getDescription() );

        $date = $material->getAddTime();
        $this->assertEquals( '2006-05-04 14:00:00', $date->getDate() );
    }

    function testGetAttributes_File() {
        // set ID to be equal to the one file ID in database
        $material = MaterialFactory::file( 4 );


        $owner = $material->getOwner();
        $this->assertEquals( 1, $owner->getID() );

        $course = $material->getCourse();
        $this->assertEquals( 1, $course->getID() );

        $parent = $material->getParent();
        $this->assertEquals( 1, $parent->getID() );

        $this->assertEquals( 'FILE', $material->getType() );

        $this->assertEquals( 'Lesson 1', $material->getName() );

        $this->assertEquals( '', $material->getDescription() );

        $date = $material->getAddTime();
        $this->assertEquals( '2006-05-04 18:00:00', $date->getDate() );

        $this->assertEquals( 'text/plain', $material->getMimeType() );

        $this->assertEquals( 1024, $material->getSize() );
    }

    function testGetAttributes_Link() {
        // set ID to be equal to the one file ID in database
        $material = MaterialFactory::link( 3 );


        $owner = $material->getOwner();
        $this->assertEquals( 1, $owner->getID() );

        $course = $material->getCourse();
        $this->assertEquals( 1, $course->getID() );

        $parent = $material->getParent();
        $this->assertEquals( 1, $parent->getID() );

        $this->assertEquals( 'LINK', $material->getType() );

        $this->assertEquals( 'google', $material->getName() );

        $this->assertEquals( 'A search engine', $material->getDescription() );

        $date = $material->getAddTime();
        $this->assertEquals( '2006-05-04 16:00:00', $date->getDate() );

        $this->assertEquals( 'http://google.com', $material->getURI() );
    }


    function testGetHTMLName() {
        $material = MaterialFactory::folder();
        $material->setName( "Micro & Soft" );
        $this->assertEquals( 'Micro &amp; Soft', $material->getHTMLName() );
    }

    function testGetHTMLDescription() {
        $material = MaterialFactory::folder();
        $material->setDescription( "Micro <&> Soft" );
        $this->assertRegExp( '/<p>Micro &lt;&amp;&gt; Soft<\/p>/', $material->getHTMLDescription() );
    }

    function testGetHTMLURI() {
        $material = MaterialFactory::link();
        $material->setURI( "http://example.com/?q=bar&page=8" );
        $this->assertEquals( 'http://example.com/?q=bar&amp;page=8', $material->getHTMLURI() );
    }

    function testGetTypeName() {
        $material = MaterialFactory::link();
        $this->assertEquals( 'Link', $material->getTypeName() );

        $material = MaterialFactory::folder();
        $this->assertEquals( 'Folder', $material->getTypeName() );

        $material = MaterialFactory::file();
        $material->setMimeType( 'application/pdf' );
        $this->assertEquals( 'PDF document', $material->getTypeName() );

        $material->setMimeType( 'image/jpeg' );
        $this->assertEquals( 'Image file', $material->getTypeName() );
    }

    function testGetHumanReadableSize() {
        $file = MaterialFactory::file();

        // zero bytes
        $file->setSize( 0 );
        $this->assertEquals( '0 B', $file->getHumanReadableSize() );

        // simple bytes
        $file->setSize( 128 );
        $this->assertEquals( '128 B', $file->getHumanReadableSize() );

        // exactly one kilobyte
        $file->setSize( 1024 );
        $this->assertEquals( '1 KB', $file->getHumanReadableSize() );

        // one and a half kilobytes
        $file->setSize( 1024 + 512 );
        $this->assertEquals( '1.5 KB', $file->getHumanReadableSize() );

        // a little bit over one kilobyte
        $file->setSize( 1024 + 1 );
        $this->assertEquals( '1 KB', $file->getHumanReadableSize() );

        // a megabyte
        $file->setSize( 1024*1024 );
        $this->assertEquals( '1 MB', $file->getHumanReadableSize() );

        // 7 megabytes
        $file->setSize( 1024*1024*7 );
        $this->assertEquals( '7 MB', $file->getHumanReadableSize() );

        // 100.8 megabytes
        $file->setSize( 1024*1024*100.8 );
        $this->assertEquals( '100.8 MB', $file->getHumanReadableSize() );

        // 1 gigabyte
        $file->setSize( 1024*1024*1024 );
        $this->assertEquals( '1 GB', $file->getHumanReadableSize() );

        // 2.5 gigabytes
        $file->setSize( 1024*1024*1024*2.5 );
        $this->assertEquals( '2.5 GB', $file->getHumanReadableSize() );

        // call statically
        $this->assertEquals( '3 MB', File::getHumanReadableSize( 1024*1024*3 ) );
    }


    function testGetLink_Folder() {
        $material = MaterialFactory::folder( 2005 );

        $material->setCourse( new Course(128) );
        $material->setName( "Kaust" );

        $this->assertEquals(
            '<a href="/128/materials/view/2005" class="folder">Kaust</a>',
            $material->getLink()
        );
    }

    function testGetLink_File() {
        $material = MaterialFactory::file( 2005 );

        $material->setCourse( new Course(128) );
        $material->setName( "flower.jpg" );
        $material->setMimeType( "image/jpeg" );

        $this->assertEquals(
            '<a href="/128/materials/download/2005" class="image-file">flower.jpg</a>',
            $material->getLink()
        );
    }

    function testGetLink_Link() {
        $material = MaterialFactory::link( 2005 );

        $material->setCourse( new Course(128) );
        $material->setName( "example" );
        $material->setURI( "http://www.example.com/" );

        $this->assertEquals(
            '<a href="http://www.example.com/" class="uri">example</a>',
            $material->getLink()
        );
    }

    function testSave_Folder() {
        // set file ID to be equal to the one file ID in database
        $material = MaterialFactory::folder();

        // set other attributes to arbitrary values
        $owner = new TableAbstractionDummy( 123 );
        $material->setOwner( $owner );

        $course = new TableAbstractionDummy( 256 );
        $material->setCourse( $course );

        $parent = new TableAbstractionDummy( 1 );
        $material->setParent( $parent );

        $name = 'viko';
        $material->setName( $name );

        $description = 'Simple stuff';
        $material->setDescription( $description );

        $date = new Date('2001-02-03 00:00:00');
        $material->setAddTime( $date );

        // save
        $material->save();
        $id = $material->getID();

        // check that new entry was added
        $db = DBInstance::get();
        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Materials
            WHERE
                material_id=? AND
                user_id=123 AND
                course_id=256 AND
                parent_id=1 AND
                material_type='FOLDER' AND
                mime_type='' AND
                material_name='viko' AND
                material_description='Simple stuff' AND
                material_uri='' AND
                material_addtime='2001-02-03 00:00:00' AND
                material_size=0
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );


        // set ID and test updating
        $id = 1;
        $material->setID( $id );
        $material->save();

        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Materials
            WHERE
                material_id=? AND
                user_id=123 AND
                course_id=256 AND
                parent_id=1 AND
                material_type='FOLDER' AND
                mime_type='' AND
                material_name='viko' AND
                material_description='Simple stuff' AND
                material_uri='' AND
                material_addtime='2001-02-03 00:00:00' AND
                material_size=0
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );
    }

    function testSave_File() {
        // set file ID to be equal to the one file ID in database
        $material = MaterialFactory::file();

        // set other attributes to arbitrary values
        $owner = new TableAbstractionDummy( 123 );
        $material->setOwner( $owner );

        $course = new TableAbstractionDummy( 256 );
        $material->setCourse( $course );

        $parent = new TableAbstractionDummy( 1 );
        $material->setParent( $parent );

        $name = 'viko.svg';
        $material->setName( $name );

        $description = 'Simple stuff';
        $material->setDescription( $description );

        $date = new Date('2001-02-03 00:00:00');
        $material->setAddTime( $date );

        $mime_type = "image/svg+xml";
        $material->setMimeType( $mime_type );

        $size = 256;
        $material->setSize( $size );

        // save
        $material->save();
        $id = $material->getID();

        // check that new entry was added
        $db = DBInstance::get();
        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Materials
            WHERE
                material_id=? AND
                user_id=123 AND
                course_id=256 AND
                parent_id=1 AND
                material_type='FILE' AND
                mime_type='image/svg+xml' AND
                material_name='viko.svg' AND
                material_description='Simple stuff' AND
                material_uri='' AND
                material_addtime='2001-02-03 00:00:00' AND
                material_size=256
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );

        // set ID and test updating
        $id = 2;
        $material->setID( $id );
        $material->save();

        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Materials
            WHERE
                material_id=? AND
                user_id=123 AND
                course_id=256 AND
                parent_id=1 AND
                material_type='FILE' AND
                mime_type='image/svg+xml' AND
                material_name='viko.svg' AND
                material_description='Simple stuff' AND
                material_uri='' AND
                material_addtime='2001-02-03 00:00:00' AND
                material_size=256
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );
    }

    function testSave_Link() {
        // set file ID to be equal to the one file ID in database
        $material = MaterialFactory::link();

        // set other attributes to arbitrary values
        $owner = new TableAbstractionDummy( 123 );
        $material->setOwner( $owner );

        $course = new TableAbstractionDummy( 256 );
        $material->setCourse( $course );

        $parent = new TableAbstractionDummy( 1 );
        $material->setParent( $parent );

        $name = 'viko.svg';
        $material->setName( $name );

        $description = 'Simple stuff';
        $material->setDescription( $description );

        $date = new Date('2001-02-03 00:00:00');
        $material->setAddTime( $date );

        $uri = 'http://example.com/viko.svg';
        $material->setURI( $uri );

        // save
        $material->save();
        $id = $material->getID();

        // check that new record was added
        $db = DBInstance::get();
        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Materials
            WHERE
                material_id=? AND
                user_id=123 AND
                course_id=256 AND
                parent_id=1 AND
                material_type='LINK' AND
                mime_type='' AND
                material_name='viko.svg' AND
                material_description='Simple stuff' AND
                material_uri='http://example.com/viko.svg' AND
                material_addtime='2001-02-03 00:00:00' AND
                material_size=0
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );

        // set ID and test updating
        $id = 3;
        $material->setID( $id );
        $material->save();

        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Materials
            WHERE
                material_id=? AND
                user_id=123 AND
                course_id=256 AND
                parent_id=1 AND
                material_type='LINK' AND
                mime_type='' AND
                material_name='viko.svg' AND
                material_description='Simple stuff' AND
                material_uri='http://example.com/viko.svg' AND
                material_addtime='2001-02-03 00:00:00' AND
                material_size=0
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );
    }


    function testDeleteExisting() {
        // set file ID to be equal to the one ID in database
        $id = 3;
        $material = MaterialFactory::link( $id );

        $success = $material->delete();
        $this->assertTrue( $success );

        if ( $success ) {
            // check that database entry was deleted
            $db = DBInstance::get();
            $count = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Materials
                WHERE
                    material_id = ?
                ",
                array( $id )
            );
            $this->assertEquals( 0, $count );
        }
    }

    function testDeleteUnexisting() {
        // set file ID to be unexisting in database
        $id = 9999999;
        $material = MaterialFactory::file( $id );

        $success = $material->delete();
        $this->assertFalse( $success );
    }


    function testDeleteRecursively() {
        // set ID to be equal to the folder ID in database
        // the folder contains other files
        $id = 1;
        $folder = MaterialFactory::folder( $id );

        $success = $folder->delete();
        $this->assertTrue( $success );

        if ( $success ) {
            // check that the folder and two files inside that were deleted
            $db = DBInstance::get();
            $count = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Materials
                WHERE
                    material_id = 1 OR
                    material_id = 3 OR
                    material_id = 4
                "
            );
            $this->assertEquals( 0, $count );

            // check that the file contents also gets removed
            $count = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Material_Contents
                WHERE
                    material_id = 4
                "
            );
            $this->assertEquals( 0, $count );
        }
    }



}

?>
