<?php
/**
 * UsersController for admin module
 *
 * @category   Application
 * @package    Users
 * @subpackage Controller
 * 
 * @version  $Id: ManagementController.php 48 2010-02-12 13:23:39Z AntonShevchuk $
 */
class Forum_ManagementController extends Core_Controller_Action_Crud
{
    /**
     * init invironment
     *
     * @return void
     */
    public function init()
    {
        /* Initialize */
        parent::init();


        $this->_beforeGridFilter(array(
             '_addAllTableColumns',
             '_prepareGrid',
             '_addCheckBoxColumn',
             '_addEditColumn',
             '_addDeleteColumn'
        ));
    }

    /**
     * indexAction
     *
     */
//    public function indexAction()
//    {
//
//    }

    /**
     * createAction
     *
     * @return void
     */
//    public function createAction()
//    {
//        parent::createAction();
//        $this->_setDefaultScriptPath();
//    }

    /**
     * editAction
     *
     * @return void
     */
//    public function editAction()
//    {
//        parent::editAction();
//        $this->_setDefaultScriptPath();
//    }

    /**
     * _getCreateForm
     *
     * return create form for scaffolding
     *
     * @return  Zend_Dojo_Form
     */
    protected function _getCreateForm()
    {
        return new Forum_Model_Post_Form_Admin_Create();
    }
    
    /**
     * _getEditForm
     *
     * return edit form for scaffolding
     *
     * @return  Zend_Dojo_Form
     */
    protected function _getEditForm()
    {
        $form = new Forum_Model_Post_Form_Admin_Create();
        $form->addElement(new Zend_Form_Element_Hidden('id'));
        return $form;
    }

    /**
     * _getTable
     *
     * return manager for scaffolding
     *
     * @return  Core_Model_Abstract
     */
    protected function _getTable()
    {
        return new Forum_Model_Post_Table();
    }

    protected function _prepareGrid()
    {
        $this->grid
             ->removeColumn('body')
             ->addColumn('body', array(
                'name' => ucfirst('body'),
                'type' => Core_Grid::TYPE_DATA,
                'index' => 'body',
                'formatter' => array($this, 'shorterFormatter')
             ))
             ->removeColumn('categoryId')
             ->removeColumn('userId')
             ->removeColumn('views')
             ->removeColumn('comments');
    }

    public function shorterFormatter($value, $row)
    {
        if (strlen($row['body']) >= 200) {
            if (false !== ($breakpoint = strpos($row['body'], ' ', 200))) {
                if ($breakpoint < strlen($row['body']) - 1) {
                    $row['body'] = substr($row['body'], 0, $breakpoint) . ' ...';
                }
            }
        }
        return $row['body'];
    }

}

