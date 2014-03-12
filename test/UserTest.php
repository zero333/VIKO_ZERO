<?php

require_once 'User.php';
require_once 'PHPUnit.php';

class UserTest extends PHPUnit_TestCase
{
    var $user;

    // constructor of the test suite
    function UserTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->user = new User();
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->user );
    }

    function testSetGetID() {
        $expected = 128;
        $this->user->setID( $expected );
        $result = $this->user->getID();
        $this->assertTrue( $result == $expected );
    }

    function testSetGetSchool() {
        $expected = new School();
        $this->user->setSchool( $expected );
        $result = $this->user->getSchool();
        $this->assertTrue( $result === $expected );
    }

    function testGetSchool() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $school_id = 7777777;
        $db->query(
            "INSERT INTO Users (user_id, username, school_id) VALUES (?,?,?)",
            array($id, $username, $school_id)
        );
        $this->user->setID( $id );

        $result = $this->user->getSchool();
        $this->assertEquals( $school_id, $result->getID() );
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testSetGetUsername() {
        $expected = "john";
        $this->user->setUsername( $expected );
        $result = $this->user->getUsername();
        $this->assertTrue( $result == $expected );
    }

    function testGetUsername() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $db->query(
            "INSERT INTO Users (user_id, username) VALUES (?,?)",
            array($id, $username)
        );
        $this->user->setID( $id );

        $result = $this->user->getUsername();
        $this->assertEquals( $username, $result );
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testSetGetFirstName() {
        $expected = "John";
        $this->user->setFirstName( $expected );
        $result = $this->user->getFirstName();
        $this->assertTrue( $result == $expected );
    }

    function testGetFirstName() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $firstname = 'John';
        $db->query(
            "INSERT INTO Users (user_id, username, firstname) VALUES (?,?,?)",
            array($id, $username, $firstname)
        );
        $this->user->setID( $id );

        $result = $this->user->getFirstName();
        $this->assertEquals( $firstname, $result );
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testSetGetLastName() {
        $expected = "Doe";
        $this->user->setLastName( $expected );
        $result = $this->user->getLastName();
        $this->assertTrue( $result == $expected );
    }

    function testGetLastName() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $lastname = 'Smith';
        $db->query(
            "INSERT INTO Users (user_id, username, lastname) VALUES (?,?,?)",
            array($id, $username, $lastname)
        );
        $this->user->setID( $id );

        $result = $this->user->getLastName();
        $this->assertEquals( $lastname, $result );
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testSetGetEmail() {
        $expected = "mallory@example.com";
        $this->user->setEmail( $expected );
        $result = $this->user->getEmail();
        $this->assertTrue( $result == $expected );
    }

    function testGetEmail() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $email = 'john@example.com';
        $db->query(
            "INSERT INTO Users (user_id, username, email) VALUES (?,?,?)",
            array($id, $username, $email)
        );
        $this->user->setID( $id );

        $result = $this->user->getEmail();
        $this->assertEquals( $email, $result );
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testSetGetGroup() {
        $expected = "STUDENT";
        $this->user->setGroup( $expected );
        $result = $this->user->getGroup();
        $this->assertTrue( $result == $expected );
    }

    function testGetGroup() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $group = 'SCHOOLADMIN';
        $db->query(
            "INSERT INTO Users (user_id, username, user_group) VALUES (?,?,?)",
            array($id, $username, $group)
        );
        $this->user->setID( $id );

        $result = $this->user->getGroup();
        $this->assertEquals( $group, $result );
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testBelongsToGroup() {
        $expected = "TEACHER";
        $this->user->setGroup( $expected );
        $this->assertTrue( $this->user->belongsToGroup( $expected ) );
    }

    function testSetGetForm() {
        $expected = new Form();
        $this->user->setForm( $expected );
        $result = $this->user->getForm();
        $this->assertTrue( $result === $expected );
    }

    function testGetForm() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $form_id = 7777777;
        $db->query(
            "INSERT INTO Users (user_id, username, form_id) VALUES (?,?,?)",
            array($id, $username, $form_id)
        );
        $this->user->setID( $id );

        $result = $this->user->getForm();
        $this->assertEquals( $form_id, $result->getID() );
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testGetFullName() {
        // test that names are returned in correct order, and quotes are escaped
        $this->user->setFirstName( "John'" );
        $this->user->setLastName( '"Maddog" Hall' );
        $this->assertEquals( 'John&#039; &quot;Maddog&quot; Hall', $this->user->getFullName() );

        // test the reverse ordering and escaping of <, > and &
        $this->user->setFirstName( "Larry" );
        $this->user->setLastName( "& The <i>cow</i>" );
        $this->assertEquals( "&amp; The &lt;i&gt;cow&lt;/i&gt; Larry", $this->user->getFullName( LASTNAME_FIRSTNAME ) );
    }

    function testGetUserLink() {
        // check with teacher
        $this->user->setID( 256 );
        $this->user->setFirstName( "Mickey <'&'>" );
        $this->user->setLastName( '"Mouse"' );
        $this->user->setEmail( "foo@bar.com" );

        $_SESSION['user'] = new User();
        $_SESSION['user']->setGroup( "ADMIN" );

        $this->assertEquals( '<a href="/user/edit/256">Mickey &lt;&#039;&amp;&#039;&gt; &quot;Mouse&quot;</a>', $this->user->getUserLink() );

        $_SESSION['user']->setGroup( "SCHOOLADMIN" );

        $this->assertEquals( '<a href="/user/edit/256">Mickey &lt;&#039;&amp;&#039;&gt; &quot;Mouse&quot;</a>', $this->user->getUserLink() );

        $_SESSION['user']->setGroup( "TEACHER" );

        $this->assertEquals( '<a href="/user/view/256">Mickey &lt;&#039;&amp;&#039;&gt; &quot;Mouse&quot;</a>', $this->user->getUserLink() );

        // test the reverse case with student
        $_SESSION['user']->setGroup( "STUDENT" );
        $this->assertEquals( '<a href="mailto:foo@bar.com">&quot;Mouse&quot; Mickey &lt;&#039;&amp;&#039;&gt;</a>', $this->user->getUserLink(LASTNAME_FIRSTNAME) );

        // test student without e-mail
        $this->user->setEmail( "" );
        $this->assertEquals( '&quot;Mouse&quot; Mickey &lt;&#039;&amp;&#039;&gt;', $this->user->getUserLink(LASTNAME_FIRSTNAME) );
    }

    function testGetEmailLink() {
        // check with teacher
        $this->user->setID( 256 );
        $this->user->setEmail( "rms@mit.edu" );
        $this->assertEquals( '<a href="mailto:rms@mit.edu">rms@mit.edu</a>', $this->user->getEmailLink() );

        // if e-mail is missing - empty string should be returned
        $this->user->setEmail( "" );
        $this->assertEquals( "", $this->user->getEmailLink() );
    }

    function testSave() {
        $db = DBInstance::get();

        // new data for user
        $id = 9999999;
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        $firstname = 'Larry';
        $lastname = 'Wall';
        $email = 'larry@perl.com';
        $group = 'STUDENT';
        $school = new School( 8484848 );
        $form = new Form( 9999991 );

        // create old record into database
        $db->query(
            "
            INSERT INTO Users (
                user_id,
                username,
                password,
                firstname,
                lastname,
                email,
                user_group,
                school_id,
                form_id
            )
            VALUES (
                '$id',
                SHA1('ken8888888'),
                'simplepass',
                'Ken',
                'Thompson',
                'ken@mit.edu',
                'SCHOOLADMIN',
                '8888888',
                '7777777'
            )
            "
        );

        // apply data to user and save it
        $this->user->setID( $id );
        $this->user->setUsername( $username );
        $this->user->changePassword( $password );
        $this->user->setFirstName( $firstname );
        $this->user->setLastName( $lastname );
        $this->user->setEmail( $email );
        $this->user->setGroup( $group );
        $this->user->setSchool( $school );
        $this->user->setForm( $form );
        $this->user->save();

        // query the database
        $row = $db->getRow( "SELECT * FROM Users WHERE user_id = ?", array($id) );

        $this->assertEquals( $username, $row['username'] );
        $this->assertEquals( sha1($password), $row['password'] );
        $this->assertEquals( $firstname, $row['firstname'] );
        $this->assertEquals( $lastname, $row['lastname'] );
        $this->assertEquals( $email, $row['email'] );
        $this->assertEquals( $group, $row['user_group'] );
        $this->assertEquals( $school->getID(), $row['school_id'] );
        $this->assertEquals( $form->getID(), $row['form_id'] );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testSave_New() {
        $db = DBInstance::get();

        // new data for user
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        $firstname = 'Larry';
        $lastname = 'Wall';
        $email = 'larry@perl.com';
        $group = 'STUDENT';
        $school = new School( 8484848 );
        $form = new Form( 9999991 );

        // apply data to user and save it
        $this->user->setUsername( $username );
        $this->user->changePassword( $password );
        $this->user->setFirstName( $firstname );
        $this->user->setLastName( $lastname );
        $this->user->setEmail( $email );
        $this->user->setGroup( $group );
        $this->user->setSchool( $school );
        $this->user->setForm( $form );
        $this->user->save();
        $id = $this->user->getID();

        // query the database
        $row = $db->getRow( "SELECT * FROM Users WHERE user_id = ?", array($id) );

        $this->assertEquals( $username, $row['username'] );
        $this->assertEquals( sha1($password), $row['password'] );
        $this->assertEquals( $firstname, $row['firstname'] );
        $this->assertEquals( $lastname, $row['lastname'] );
        $this->assertEquals( $email, $row['email'] );
        $this->assertEquals( $group, $row['user_group'] );
        $this->assertEquals( $school->getID(), $row['school_id'] );
        $this->assertEquals( $form->getID(), $row['form_id'] );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testChangePassword_OldIsCorrect() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $old_password = 'simplepass';
        $new_password = 'unBR32k2bl3';
        $firstname = 'Larry';
        $lastname = 'Wall';
        $email = 'larry@perl.com';
        $group = 'STUDENT';
        $school = new School( 8484848 );
        // insert user record with old password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, SHA1(?))",
            array($id, $username, $old_password)
        );

        // change pass of this user
        $this->user->setID( $id );
        $this->user->setUsername( $username );
        $this->user->changePassword( $old_password );
        $this->user->setFirstName( $firstname );
        $this->user->setLastName( $lastname );
        $this->user->setEmail( $email );
        $this->user->setGroup( $group );
        $this->user->setSchool( $school );
        $this->assertTrue( $this->user->changePassword( $new_password, $old_password ) );
        $this->user->save();

        // check if the change was successful
        $result = $db->getOne(
            "SELECT user_id FROM Users WHERE username = ? AND password = SHA1(?)",
            array($username, $new_password)
        );
        $this->assertTrue( isset($result) );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testChangePassword_OldIsWrong() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $old_password = 'simplepass';
        $new_password = 'unBR32k2bl3';
        $firstname = 'Larry';
        $lastname = 'Wall';
        $email = 'larry@perl.com';
        $group = 'STUDENT';
        $school = new School( 8484848 );
        // insert user record with old password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, SHA1(?))",
            array($id, $username, $old_password)
        );

        // change pass of this user
        $this->user->setID( $id );
        $this->user->setUsername( $username );
        $this->user->changePassword( $old_password );
        $this->user->setFirstName( $firstname );
        $this->user->setLastName( $lastname );
        $this->user->setEmail( $email );
        $this->user->setGroup( $group );
        $this->user->setSchool( $school );
        $this->assertFalse( $this->user->changePassword( $new_password, "wrongpass" ) );
        $this->user->save();

        // check that the old password is still the same
        $result = $db->getOne(
            "SELECT user_id FROM Users WHERE username = ? AND password = SHA1(?)",
            array($username, $old_password)
        );
        $this->assertTrue( isset($result) );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testVerifyPassword_CorrectPass() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        // insert user record with password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, SHA1(?))",
            array($id, $username, $password)
        );

        // verify the pass
        $this->user->setID( $id );
        $this->assertTrue( $this->user->verifyPassword( $password ) === true );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testVerifyPassword_WrongPass() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        // insert user record with password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, SHA1(?))",
            array($id, $username, $password)
        );

        // verify the pass
        $this->user->setID( $id );
        $this->assertTrue( $this->user->verifyPassword( "somethingelse" ) === false );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testAuthenticate_CorrectPass() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        // insert user record with password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, SHA1(?))",
            array($id, $username, $password)
        );

        // verify the pass
        $this->assertTrue( User::authenticate( $username, $password ) === true );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testAuthenticate_WrongPass() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        // insert user record with password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, SHA1(?))",
            array($id, $username, $password)
        );

        // verify the pass
        $this->assertTrue( User::authenticate( $username, "somethingelse" ) === false );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testAuthenticate_PASSWORD() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        // insert user record with password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, PASSWORD(?))",
            array($id, $username, $password)
        );

        // verify the pass
        $this->assertTrue( User::authenticate( $username, $password ) === true );

        // check if the password was changed to SHA1 hash
        $result = $db->getOne(
            "SELECT user_id FROM Users WHERE username = ? AND password = SHA1(?)",
            array($username, $password)
        );
        echo $result == null;
        $this->assertTrue( isset($result) );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testAuthenticate_OLD_PASSWORD() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'user9999999';
        $password = 'unBR32k2bl3';
        // insert user record with password
        $db->query(
            "INSERT INTO Users (user_id, username, password) VALUES (?,?, OLD_PASSWORD(?))",
            array($id, $username, $password)
        );

        // verify the pass
        $this->assertTrue( User::authenticate( $username, $password ) === true );

        // check if the password was changed to SHA1 hash
        $result = $db->getOne(
            "SELECT user_id FROM Users WHERE username = ? AND password = SHA1(?)",
            array($username, $password)
        );
        $this->assertTrue( isset($result) );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id=? ", $id);
    }

    function testSimpleDelete() {
        $db = DBInstance::get();
        $id = 9999999;
        $username = 'ken9999999';
        $db->query("INSERT INTO Users (user_id, username) VALUES (?, ?)",
            array( $id, $username )
        );

        $this->user->setID( $id );
        $this->user->delete();

        $res = $db->query("SELECT username FROM Users WHERE user_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );

        if ( $res->numRows() != 0 ) {
            $db->query("DELETE FROM Users WHERE user_id = ? ", $id);
        }

    }

    function testUnexistingDelete() {
        $db = DBInstance::get();
        $id = 9999999;

        $this->user->setID( $id );
        $result = $this->user->delete();

        $this->assertTrue( $result === false );
    }

    function testRecursiveDelete() {
        $db = DBInstance::get();
        $id = 9999999;
        $db->query("INSERT INTO Users (user_id, username) VALUES (?, ?)",
            array( $id, 'ken9999999' )
        );
        $db->query("INSERT INTO Courses (course_id, course_name, teacher_id) VALUES (?, ?, ?)",
            array( 9999991, 'Math', $id )
        );
        $db->query("INSERT INTO Lessons (lesson_id, course_id) VALUES (?, ?)",
            array( 7777771, 9999991 )
        );
        $db->query("INSERT INTO Marks (lesson_id, user_id) VALUES (?, ?)",
            array( 7777771, $id )
        );
        $db->query("INSERT INTO Materials (course_id, user_id) VALUES (?, ?)",
            array( 8888881, $id )
        );
        $db->query("INSERT INTO Posts (topic_id, user_id) VALUES (?, ?)",
            array( 6666661, $id )
        );

        $this->user->setID( $id );
        $this->user->delete();

        $res = $db->query("SELECT * FROM Users WHERE user_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );
        $res = $db->query("SELECT * FROM Courses WHERE teacher_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );
        $res = $db->query("SELECT * FROM Lessons WHERE course_id = ? ", 9999991);
        $this->assertEquals( 0, $res->numRows() );
        $res = $db->query("SELECT * FROM Marks WHERE user_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );
        $res = $db->query("SELECT * FROM Materials WHERE user_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );
        $res = $db->query("SELECT * FROM Posts WHERE user_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );

        // clean up
        $db->query("DELETE FROM Users WHERE user_id = ? ", $id);
        $db->query("DELETE FROM Courses WHERE teacher_id = ? ", $id);
        $db->query("DELETE FROM Lessons WHERE course_id = ? ", 9999991);
        $db->query("DELETE FROM Marks WHERE user_id = ? ", $id);
        $db->query("DELETE FROM Materials WHERE user_id = ? ", $id);
        $db->query("DELETE FROM Posts WHERE user_id = ? ", $id);
    }

}

?>
