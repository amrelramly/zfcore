<?php

/**
 * Core_Controller_Action_Crud
 *
 * @uses       Zend_Controller_Action
 * @category   Core
 * @package    Core_Controller
 * @subpackage Core_Controller_Action
 */
abstract class Core_Controller_Action_Crud extends Core_Controller_Action
{
    /**
     * init controller
     *
     * @return void
     */
    public function init()
    {
        $this->_isDashboard();

        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        $this->_viewRenderer = $this->_helper->getHelper('viewRenderer');
        $this->_redirector = $this->_helper->getHelper('redirector');

        /** load model before editing and deleting */
        $this->_before('_loadModel', array('only' => array('edit', 'delete')));

        /** change view script path specification */
        $this->_after('_changeViewScriptPathSpec', array('only' => array('index', 'grid', 'create', 'edit')));

        /** load grid */
        $this->_beforeGridFilter('_loadGrid');
    }

    /**
     * index
     *
     * @return void
     */
    public function indexAction()
    {
        /** init paginator before rendering, catch all exception in action */
        $this->grid->getPaginator();
        $this->view->grid = $this->grid;
    }

    /**
     * grid
     *
     * @return void
     */
    public function gridAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout->disableLayout();
        }

        /** init paginator before rendering, catch all exception in action */
        $this->grid->getPaginator();
        $this->view->grid = $this->grid;
    }

    /**
     * create
     *
     * @return void
     */
    public function createAction()
    {
        $table = $this->_getTable();
        $form = $this->_getCreateForm()
            ->setAction($this->view->url());

        if ($this->_request->isPost() &&
            $form->isValid($this->_getAllParams())
        ) {
            $table->createRow($form->getValues())
                ->save();

            $this->_flashMessenger->addMessage('Successfully');
            $this->_redirector->direct('index');
        }

        $this->view->form = $form;
    }

    /**
     * edit
     *
     * @return void
     */
    public function editAction()
    {
        $form = $this->_getEditForm()
            ->setAction($this->view->url())
            ->setDefaults($this->model->toArray(true));

        if ($this->_request->isPost() &&
            $form->isValid($this->_getAllParams())
        ) {
            $this->model
                ->setFromArray($form->getValues())
                ->save();

            $this->_flashMessenger->addMessage('Successfully');
            $this->_redirector->direct('index');
        }

        $this->view->form = $form;
    }

    /**
     * load model
     *
     * @return void
     */
    protected function _loadModel()
    {
        if (!$id = $this->_getParam('id')) {
            $this->_forwardNotFound();
        }

        $table = $this->_getTable();

        if (!$model = $table->getById($id)) {
            $this->_forwardNotFound();
        }

        $this->model = $model;
    }

    /**
     * change view script path specification
     *
     * @return void
     */
    protected function _changeViewScriptPathSpec()
    {
        $this->_viewRenderer->setViewScriptPathSpec('crud/:action.:suffix');
    }

    /**
     * delete
     *
     * @return void
     */
    public function deleteAction()
    {
        $this->_helper->json($this->model->delete());
    }

    /**
     * get create form
     *
     * @abstract
     * @return Zend_Form
     */
    abstract protected function _getCreateForm();

    /**
     * get edit form
     *
     * @abstract
     * @return Zend_Form
     */
    abstract protected function _getEditForm();

    /**
     * load grid
     *
     * @return void
     */
    protected function _loadGrid()
    {
        $grid = new Core_Grid();
        $grid->setSelect($this->_getSource())
            ->setCurrentPageNumber($this->_getParam('page', 1))
            ->setItemCountPerPage(10);

        if ($this->_getParam('orderColumn')) {
            $grid->setOrder($this->_getParam('orderColumn'), $this->_getParam('orderDirection', 'asc'));
        }

        if ($this->_getParam('filterColumn')) {
            $grid->setFilter($this->_getParam('filterColumn'), $this->_getParam('filterValue'));
        }

        $this->grid = $grid;
    }

    /**
     * add all table columns to grid
     *
     * @return void
     */
    public function _addAllTableColumns()
    {
        foreach ($this->_getTable()->info(Zend_Db_Table::COLS) as $col) {
            $this->grid->addColumn($col, array(
                'name' => ucfirst($col),
                'type' => Core_Grid::TYPE_DATA,
                'index' => $col
            ));
        }
    }

    /**
     * add edit column to grid
     *
     * @return void
     */
    public function _addEditColumn()
    {
        $this->grid->addColumn('edit', array(
            'name' => 'Edit',
            'formatter' => array($this, 'editLinkFormatter')
        ));
    }

    /**
     * add delete column to grid
     *
     * @return void
     */
    public function _addDeleteColumn()
    {
        $this->grid->addColumn('delete', array(
            'name' => 'Delete',
            'formatter' => array($this, 'deleteLinkFormatter')
        ));
    }

    /**
     * edit link formatter
     *
     * @param $value
     * @param $row
     * @return string
     */
    public function editLinkFormatter($value, $row)
    {
        $link = '<a href="%s" class="edit">Edit</a>';
        $url = $this->getHelper('url')->url(array(
            'action' => 'edit',
            'id' => $row['id']
        ), 'default');

        return sprintf($link, $url);
    }

    /**
     * delete link formatter
     *
     * @param $value
     * @param $row
     * @return string
     */
    public function deleteLinkFormatter($value, $row)
    {
        $link = '<a href="%s" class="delete">Delete</a>';
        $url = $this->getHelper('url')->url(array(
            'action' => 'delete',
            'id' => $row['id']
        ), 'default');

        return sprintf($link, $url);
    }

    /**
     * get table
     *
     * @abstract
     * @return Core_Db_Table_Abstract
     */
    abstract protected function _getTable();

    /**
     * get source
     *
     * @return Zend_Db_Select
     */
    protected function _getSource()
    {
        return $this->_getTable()->select();
    }

    /**
     * set default script path
     *
     * @return Core_Controller_Action_Crud
     */
    protected function _setDefaultScriptPath()
    {
        $this->_viewRenderer->setViewScriptPathSpec(':controller/:action.:suffix');
        return $this;
    }

    /**
     * before grid filter
     *
     * @param $function
     * @return void
     */
    protected function _beforeGridFilter($function)
    {
        $this->_before($function, array('only' => array('index', 'grid')));
    }
}