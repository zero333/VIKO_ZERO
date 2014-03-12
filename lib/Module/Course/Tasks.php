<?php

/**
 * Contains the Material class
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
 * @author     Karl Almet
 * @license    http://www.gnu.org/licenses/gpl.html  GPL 2.0
 */

require_once 'lib/Module/Course.php';
require_once 'lib/task.php';
require_once 'lib/tasklist.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';
require_once 'lib/HTML.php';
require_once 'Pear/Text/Wiki/BBCode.php';
/**
 * Tasks class
 *
 * This is abstract parent class of all different course Tasks.
 */
class Module_Course_Tasks extends Module_Course 
{

    /**
     * Selected task
     *
     * @access private
     * @var Lesson
     */
    var $_task = null;

    /**
     * Action to be performed with the task
     *
     * @access private
     * @var string
     */
    var $_action = null;


    /**
     * Constructs new Course Tasks Module
     */
    function Module_Course_Tasks( $task, $parameters )
    {
        $actions = array( "add", "edit", "delete", "check", "select", "choose", "answer" );
        if ( isset($parameters[0]) && in_array( $parameters[0], $actions ) ) {
            $this->_action = $parameters[0];
               
            if ( isset($parameters[1]) && (int)$parameters[1] > 0 ) {
                      $this->_task = new Task( (int)$parameters[1] );
            }
            elseif ( $this->_action != "add" ) {
                trigger_error( "No task ID specified to edit/delete.", E_USER_ERROR );
            }
        }
        elseif ( isset($parameters[0]) ) {
            trigger_error( "Unknown task '$parameters[0]' specified.", E_USER_ERROR );
        }

        parent::Module_Course( $task, $parameters );
    }


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "tasks";
    }


    /**
     * Returns the short name of the module, this is used in navigation menu.
     *
     * @access public
     * @static
     * @return string short name
     */
    function getName()
    {
        return _("Ülesanded");
    }


    /**
     * Returns short description of module
     *
     * @access public
     * @static
     * @return string module description
     */
    function getDescription()
    {
        return _("description");
    }

    
    function getTaskContent() {
        return _("task_content");
    }

    /**
     * Returns Lessons page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        if ( isset($this->_action) ) {
            // only the teacher can add/delete/edit tasks
            if ( !$this->_userCanEditThisCourse() ) {
                return $this->accessDeniedPage(
                    _("Only teacher of the course can add or remove tasks.")
                );
            }

            // if task is to be edited or deleted, it has to exist and belong to this course
            if ( isset( $this->_Task ) ) {

                // does it exist?
                if ( !$this->_Task->loadFromDatabaseByID() ) {
                    return $this->errorPage(
                        _("Selected task does not exist.")
                    );
                }

                // does it belong to this course?
                $task_course = $this->_task->getTask();
                if ( $task_course->getID() != $this->_course->getID() ) {
                    return $this->errorPage(
                        _("Selected task does not belong to this course.")
                    );
                }

            }

            switch ( $this->_action ) {
                case "add":
                    return $this->_createTasksTemplateListPage( null );
                    break;
                case "edit":
                    return $this->_createEditTask( $this->_task );
                    break;
                case "delete":
                    return $this->_deleteTask( $this->_task );
                    break;
                case "check":
                    return $this->_createCheckTask( $this->_task );
                    break;
                case "select":
                    return $this->_createSelectTask( $this->_task );
                    break;
                case "choose":
                    return $this->_createChooseTask( $this->_task );
                    break;
                case "answer":
                    return $this->_createAnswerTask( $this->_task );
                    break;                
                default:
                    trigger_error( "Unknown action '{$this->_action}' specified.", E_USER_ERROR );
            }
        }

        return $this->_createTaskListPage();
    }


    /**
     * Creates HTML fragment containing table of tasks
     *
     * @return string HTML
     */
    function _createTaskListPage()
    {
        $html_title = $this->title();

        $table = new VikoTable();
        // Create table headers
        $header_list = array(
            _("Kuupäev"),
            _("Ülesanne")
        );
        // if page is editable, add the "X" to the end of headers
        if ( $this->_userCanEditThisCourse() ) {
            $header_list[] = _("Edit");
            $header_list[] = HTML::abbr( "X", _("Remove task") );
        }
        else {
            $header_list[] = _("Mark");
        }
        $table->addHeader( $header_list );


        // get list of tasks
        $task_list = TaskList::getFromCourse( $this->_course, $order_by = array("task_id"), false, NULL );

        // Fill table body with list of tasks
        foreach ( $task_list as $task ) {
            $table->addRow( $this->_createTasksTableRow( $task ) );
        }

        if ( $this->_userCanEditThisCourse() ) {
            // the last column is for deletion
            $table->setColumnToDelete( 3 );

            // create link to new task creation
            $add_task_html = HTML::newLink(
                $this->relativeURI( "add" ),
                _("Add task"),
                STANDALONE
            );
            
           $note = "";

        }
        else {
            $add_task_html = "";
            $note = HTML::p( _("Assessment marks are in bold face.") );
        }

        // If there are tasks, show them in table; or show message telling that there are no tasks
        if ( count($task_list) > 0 ) {
            return $html_title . $table->toHTML() . $add_task_html . $note;
        }
        else {
            return HTML::notice( _("There are no tasks on this course.") ) . $add_task_html;
        }
    }

    
    
    /**
     * Tasks templates
     */    
    
    function _createTasksTemplateListPage()
    {
        $html_title = $this->title();

        $table = new VikoTable();
        // Create table headers
        $header_list = array(
            _("Ülesanne")
        );
        // if page is editable, add the "X" to the end of headers
        if ( $this->_userCanEditThisCourse() ) {
            $header_list[] = _("Choose");
            
        }
        else {
            $header_list[] = _("Mark");
        }
        $table->addHeader( $header_list );

            

        // get list of tasks templates
        $task_list = TaskList::getFromCourse( $this->_course, 
                     $order_by = array("task_id"), 
                     true, null );

        // Fill table body with list of tasks
        foreach ( $task_list as $task ) {
            $table->addRow( $this->_createTasksTableRow( $task, true ) );
        }

        if ( $this->_userCanEditThisCourse() ) {
            // the last column is for deletion
            $table->setColumnToDelete( 2 );

            // create link to new task creation
            $add_task_html = HTML::newLink(
                $this->relativeURI( "add" ),
                _("Add task"),
                STANDALONE
            );
            
           $note = "";

        }
        else {
            $add_task_html = "";
            $note = HTML::p( _("Assessment marks are in bold face.") );
        }

        // If there are tasks, show them in table; or show message telling that there are no tasks
        if ( count($task_list) > 0 ) {
            return $html_title . $table->toHTML() . $add_task_html . $note;
        }
        else {
            return HTML::notice( _("There are no task templates.") );
        }
    }    
    

    /**
     * Returns table row contents as an array
     *
     * @param Lesson $task
     * @return array table row
     */
    function _createTasksTableRow( $task, $template = false )
    {
        // Task date
        $task_date = NULL;
        if ($this->_action !== 'add') {
            $date = $task->getDate();
            $date_short = $date->format( Configuration::getShortDate() );
            $date_long = $date->format( Configuration::getLongDateWithWeekday() );
            $task_date = HTML::p( HTML::abbr( $date_short, $date_long ) );
        }
        if ($this->_userCanEditThisCourse()) {
            // Task topic
            $task_title_uri = $this->relativeURI("select", $task->getID());
            $task_link = HTML::p( $task->getHTMLTopic() );
            
        } else {
            $task_link = HTML::p( $task->getHTMLTopic() );
        }
        
        //echo '<pre>', print_r($_SESSION), '</pre>';
        //die();
        if ($template) {
            // Choose link
            $task_uri = $this->relativeURI( "choose", $task->getID() );
            $edit_link = HTML::p( HTML::a( $task_uri, _("Choose") ) );
        } elseif(!$this->_userCanEditThisCourse()) {        
            // Edit link
            $task_uri = $this->relativeURI( "answer", $task->getID() );
            $edit_link = HTML::p( HTML::a( $task_uri, _("Vasta") ) );        
        } else {        
            // Edit link
            $task_uri = $this->relativeURI( "edit", $task->getID() );
            $edit_link = HTML::p( HTML::a( $task_uri, _("Edit") ) );        
        }
        // Check link
        
        $task_check_uri = $this->relativeURI( "check", $task->getID() );
        $check_link = HTML::p( HTML::a( $task_check_uri, _("Check") ) );        
        
                // Task Description
        $task_desc = $task->getHTMLDesc();
        
        // Task content
        $task_content = $task->getHTMLTaskContent();
        
        // Delete link
        $delete_link = NULL;
        if ($template == false) {
            $delete_uri = $this->relativeURI( "delete", $task->getID() );
            $delete_label = "X";
            $delete_title = _("Delete task");
            $delete_link = HTML::p( HTML::a( $delete_uri, $delete_label, $delete_title ) );
            
        }        
        
        // Mark
        if ( !$this->_userCanEditThisCourse() ) {
            $mark = new Mark();
            $mark->setStudent( $_SESSION['user'] );
            $mark->setLesson( $task );
            $mark_value = $mark->getHTMLMark();
            // do not display zero or empty marks
            if (trim($mark_value) > "" && trim($mark_value) != "0") {
                if ( $task->getMarkType() == 'PRELIM' ) {
                    $mark_value = HTML::strong( $mark_value );
                }
            }
            else {
                $mark_value="";
            }
            $mark_value = HTML::p( $mark_value );
        }

        // combine all this data into table row
        if ( $this->_userCanEditThisCourse() ) {
            $row = NULL;
            if ($task_date != NULL) {
                $row = Array(
                    $task_date,
                    $task_link,
                    $edit_link,
                    $delete_link
                );    
            } else {
            
                $row = Array(
                    $task_link,
                    $edit_link,
                    $delete_link
                );
            }
        }
        else {
            $row = Array(
                $task_link,
                $edit_link
            );
        }

        return $row;
    }


    /**
     * Creates HTML fragment containing form to edit task
     *
     * @param Lesson $task  null, when adding new task
     * @return string HTML
     */
    function _createEditTask( $task=null )
    {
                // get list of tasks templates
        //echo '</pre>', print_r($this->_course), '</pre>';
        $task = TaskList::getFromCourse( 'tasks_users', 
                     $order_by = array("task_id"), 
                     false, 1 );
        
        //echo $task->getTaskContent();
        
        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';
        // create HTML form
        $form = $this->_createForm( $task );
        if ( !isset($task) && $this->_action != 'edit') {
            $this->createTemplateTable();
        }
        
        // check form for user input
        if ( $form->validate() ) {
            // if data is entered correctly, save it
            // and redirect to the tasks list page
            $this->_saveTask( $form, $task );
            $this->redirectInsideCourseModule();
            return false;
        }

        $html = $this->title( $this->_editPageTitle('change') );

        // convert form to HTML
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

        $html .= HTML::backLink(
            $this->relativeURI(),
            _("Return to the list of tasks"),
            STANDALONE
        );

        // return HTML containing page title and form
        return $html;
    }


    /**
     * Returnes appropriate title, depending weather the task is defined or not
     *
     * @access private
     * @param Task $task
     * @return string
     */
    function _editPageTitle( $task=null )
    {
        if ( isset($task) && $task == 'change' ) {
            return _("Change task");
        }
        elseif($task == 'submit') {
            return _("Submit task");
        } else {
             
             return _("Add task");
        }
    }

    /**
     * Creates an HTML form for editing task info
     *
     * @access private
     * @param Lesson $task  null, when adding new task
     * @return Form new form object
     */
    function _createForm( $task = null, $template = array() )
    {
        $form = new HTML_QuickForm();
        //echo '<pre>', print_r($template), '</pre>';
        // get the language code from locale (first two letters)
        $lang = substr( Configuration::getLocale(), 0, 2 );
        if (!empty($template)) {
            if ($template['date'] > 0) {
                $form->addElement(
                    'date',
                    'task_date',
                    _('Date') . ':',
                    array( 'language'=>$lang, 'format'=>'dFY' ),
                    array( 'id'=>'date' )
                );
            }
            if ($template['title'] > 0) {
                $form->addElement(
                    'text',
                    'task_title',
                    _('Task') . ':',
                    array( 'rows'=>'12', 'cols'=>'55', 'id'=>'task_title' )
                );
            }
            if ($template['date'] > 0) {            
                $form->addElement(
                    'date',
                    'task_exp_date',
                    _('Date') . ':',
                    array( 'language'=>$lang, 'format'=>'dFY' ),
                    array( 'id'=>'date' )
                );
            }
            if ($template['textarea']) {
                $form->addElement(
                    'textarea',
                    'hometask',
                    _('Hometask') . ':',
                    array( 'rows'=>'12', 'cols'=>'45', 'id'=>'hometask' )
                );
            }
            if ($template['input'] > 0) {
                for ($i = 1; $i <= $template['input']; $i++) {
                    $form->addElement(
                        'text',
                        'task_content' . $i,
                        _('Kysimus') . ':',
                        array( 'rows'=>'12', 'cols'=>'45', 'id'=>'task_content')
                    );
                }
            }    
            $form->addElement(
                'submit',
                'submit',
                $this->_editPageTitle($task)
            );

            $form->addRule(
                'task_title',
                _("Task topic field must not be left blank."),
                'required'
            );


            
        } else {
            $form->addElement(
                'date',
                'task_date',
                _('Date') . ':',
                array( 'language'=>$lang, 'format'=>'dFY' ),
                array( 'id'=>'date' )
            );
            $form->addElement(
                'submit',
                'submit',
                $this->_editPageTitle($task)
            );            
        }
        
        return $form;
    }


    function _createSelectForm( $task = null )
    {
        $form = new HTML_QuickForm();

        // get the language code from locale (first two letters)
        $lang = substr( Configuration::getLocale(), 0, 2 );
        
        for ($i = 1; $i <= 10; $i++) {
        $i = ($i == 10) ? $i : "0" . $i;
            $form->addElement(
                'text',
                'task_topic' . $i,
                _('Kysimus ' . $i) . ':',
                array( 'rows'=>'12', 'cols'=>'45', 'id'=>'task_topic' )
            );
        }

        $form->addElement(
            'submit',
            'submit',
            $this->_editPageTitle('submit')
        );

        $form->addRule(
            'task_topic',
            _("Lesson topic field must not be left blank."),
            'required'
        );


        return $form;
    }    
    
    
    /**
     * Saves the task info in submitted form
     *
     * If the task is specified, updates that task record,
     * otherwise creates new task.
     *
     * @access private
     * @param HTML_QuickForm $form    submitted HTML form
     * @param Lesson $task          null, when adding new task
     */
    function _saveTask( $form, $task=null )
    {
        if ( !isset($task) ) {
            $task = new Task();

        }

            $date_arr = $form->exportValue('task_date');
            $date = new Date();
            //echo '<pre>', print_r($date_arr), '</pre>';
            $date->setDay( $date_arr['d'] );
            $date->setMonth( $date_arr['F'] );
            $date->setYear( $date_arr['Y'] );
        $task->setDate( $date );
        $task->setTitle( $form->exportValue('task_title') );
        $task->setTaskContent( $form->exportValue('task_content') );
            $task_exp_date_arr = $form->exportValue('task_exp_date');
            $exp_date = new Date();
            $exp_date->setDay( $task_exp_date_arr['d'] );
            $exp_date->setMonth( $task_exp_date_arr['F'] );
            $exp_date->setYear( $task_exp_date_arr['Y'] );
        $task->setTaskExpDate( $exp_date );

        $task->setCourse( $this->_course );

        $task->saveTemplate();
    }


    /**
     * Deletes the task and send user back to the tasks main page
     *
     * @access private
     * @param Lesson $task  the task to be deleted
     * @return mixed  false on success; error page on failure
     */
    function _deleteTask( $task )
    {
        
        if ( $task->delete() ) {
            $this->redirectInsideCourseModule();
            return false;
        }
        else {
            return $this->errorPage(
                _("Deleting task failed.")
            );
        }
    }
    
    function _createSelectTask( $task ) {
        // change the menu item to be clickable link
        $this->_selected_menu_item_status = 'HIGHLIGHTED';

        // create HTML form
        $form = $this->_createSelectForm( $task );
        // check form for user input
        if ( $form->validate() ) {
            // if data is entered correctly, save it
            // and redirect to the tasks list page
            $this->_saveTask( $form, $task );
            $this->redirectInsideCourseModule();
            return false;
        }

        $html = $this->title( $this->_editPageTitle($task) );

        // convert form to HTML
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

        $html .= HTML::backLink(
            $this->relativeURI(),
            _("Return to the list of tasks"),
            STANDALONE
        );

        // return HTML containing page title and form
        return $html;
    }
    
    function _createCheckTask( $task ) {
         $this->_title = $this->getName();
         HTML::a('Link', $this->getName());
    }
    
    function _createChooseTask($task) {
        
        //$form = $this->_createForm( $task );
        
        // create HTML form
        $form = $this->_chooseController( $task );
        
        // check form for user input
        if ( $form->validate() ) {
            // if data is entered correctly, save it
            // and redirect to the tasks list page
            $this->_saveTask( $form, $task );
            $this->redirectInsideCourseModule();
            return false;
        }

        $html = $this->title( $this->_editPageTitle('change') );

        // convert form to HTML
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

        $html .= HTML::backLink(
            $this->relativeURI(),
            _("Return to the list of tasks"),
            STANDALONE
        );
        return $html;
        
    }
    
    /**
     * Tasks template controller
     * @param type $task
     * @return type
     */
    
    protected function _chooseController( $task ) {
        $template = NULL;
        switch ( $task->getID() ) {
            case 1:
                $template = array('type'        => $task->getID(),
                                  'title'       => 1,
                                  'input'       => 3, 
                                  'textarea'    => 2,
                                  'date'        => 1,); 
                
                break;
            case 2:
                $template = array('type'        => $task->getID(),
                                  'title'       => 1,
                                  'input'       => 2, 
                                  'textarea'    => 0,
                                  'date'        => 1,); 
                
                break;
            default:
                trigger_error( "Unknown task '" . $task->getID() 
                             . "' specified.", E_USER_ERROR );
        }
        return $this->_createForm( $task, $template );
        
        
    }
    
    function _createAnswerTask() {
        echo '_createAnswerTask';
    }
    
    
    
}
