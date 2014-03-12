<?php

/**
 * Contains the Module_Login class
 *
 * PHP versions 4 and 5
 *
 * LICENSE: VIKO is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * VIKO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * VIKO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307  USA
 *
 * @package    VIKO
 * @author     Rene Saarsoo <nene@triin.net>
 * @copyright  2001-2007 VIKO team and contributors
 * @license    http://www.gnu.org/licenses/gpl.html  GPL 2.0
 */

require_once 'lib/Module.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';
require_once 'lib/UserFactory.php';
require_once 'lib/I18n.php';

/**
 * Manages the login page of VIKO
 *
 */
class Module_Login extends Module {


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "login";
    }


    /**
     * Returns special stylesheet for login page
     *
     * @access public
     * @return array list of stylesheets
     */
    function getStylesheets()
    {
       return array( array( 'uri' => 'login.css' ) );    
    }


    /**
     * Returns login page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        $this->title("VIKO learning environment");

        $form = $this->_createLoginForm();

        if ( $form->validate() ) {
            return $this->_login(
                $form->exportValue('username'),
                $form->exportValue('password')
            );
        }
        else {
            return $this->_createLoginPage();
        }
    }


    /**
     * Returns HTML of the login-page
     *
     * @access private
     * @return string HTML fragment
     */
    function _createLoginPage()
    {
        $login_title = HTML::h3( _("Log in") );
        $form_html = $this->_createLoginFormHTML();
        $register_link = HTML::a( $this->URI("register"), _("Register as a new user.") );
        $forgot_link = HTML::a( $this->URI("forgot-password"), _("Forgot your password?") );

        // Only show registration link if registration is enabled in configuration
        if ( Configuration::getAllowRegistration() ) {
            $links = HTML::ul( $register_link, $forgot_link );
        }
        else {
            $links = HTML::ul( $forgot_link );
        }

        // get the localized text for the main content area.
        $main_content = I18n::getFileContents( "login.html" );

        return
            HTML::element("div", $login_title . $form_html . $links, array("class"=>"col-1") ).
            HTML::element("div", $main_content, array("class"=>"col-2") );
    }


    /**
     * Returns HTML of the login-form
     *
     * @access private
     * @return string HTML fragment
     */
    function _createLoginFormHTML()
    {
        $username_label = _("Username") . ":";
        $password_label = _("Password") . ":";
        $submit_label = _("Get in");
        return <<<EOHTML
<form action="" method="post">
<dl>
  <dt><label for="username">$username_label</label></dt>
    <dd><input type="text" name="username" id="username" /></dd>
  <dt><label for="password">$password_label</label></dt>
    <dd><input type="password" name="password" id="password" /></dd>
</dl>

<p><input type="submit" name="submit" value="$submit_label" /></p>
</form>
EOHTML;
    }


    /**
     * Performes the login
     *
     * If login succeeds, stores user object into session and
     * sends user to my-viko page,
     * otherwise returns HTML, that says "Login failed."
     *
     * @access private
     * @param string $username
     * @param string $password
     * @return string HTML fragment
     */
    function _login( $username, $password )
    {
        $user = UserFactory::createUserByLogin( $username, $password );

        if ( $user ) {
            // save user info to session
            $_SESSION['user'] = $user;

            // Redirect to the main page of VIKO
            $this->redirect("my-viko");
            return false;
        }
        else {
            $html = $this->title("Login Failed!");
            $html.= HTML::p("Either the username or password you entered was wrong.");
            $html.= HTML::p( HTML::a( $this->URI(), "Try again." ) );
            return $html;
        }
    }


    /**
     * Returns the login form object
     *
     * @access private
     * @return HTML_QuickForm form object
     */
    function _createLoginForm()
    {
        $form = new HTML_QuickForm();

        $form->addElement('text', 'username', 'Username' . ':' );
        $form->addElement('password', 'password', 'Password' . ':' );
        $form->addElement('submit', 'submit', 'Get in' );

        return $form;
    }

}


?>