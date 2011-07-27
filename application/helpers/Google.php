<?php
/**
 * Helper_Google
 *
 * @version $Id$
 */
class Helper_Google extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var Zend_Oauth_Consumer
     */
    protected $_client;

    /**
     * @var Zend_Oauth_Token
     */
    protected $_token;

    /**
     * Init Zend_Oauth_Consumer
     */
    public function direct()
    {
        return $this->getClient();
    }

    /**
     * Get fb client
     *
     * @return Zend_Oauth_Consumer
     */
    public function getClient()
    {
        if (!$this->_client) {
            $this->setClient(new Zend_Oauth_Consumer($this->getConfig()));
        }

        return $this->_client;
    }

    /**
     * Get config
     *
     * @throws Zend_Controller_Action_Exception
     * @return array
     */
    public function getConfig()
    {
        if (!Zend_Registry::isRegistered('googleConfig')) {
            throw new Zend_Controller_Action_Exception(
                'Google Config not found'
            );
        }
        $config = Zend_Registry::get('googleConfig');

        if (strpos($config['callbackUrl'], 'http') !== 0) {
            $view = new Zend_View();
            $config['callbackUrl'] = $view->serverUrl(
                $config['callbackUrl']
            );
        }
        return $config;
    }

    /**
     * Set client
     *
     * @param Zend_Oauth_Consumer $client
     * @return Helper_Twitter
     */
    public function setClient(Zend_Oauth_Consumer $client)
    {
        $this->_client = $client;

        return $this;
    }

    /**
     * Get Access Token
     *
     * @return Zend_Oauth_Token_Access|null
     */
    public function getToken()
    {
        if (!$this->_token) {

            $query = $this->getRequest()->getQuery();

            if (!empty($query) && isset($_SESSION['GOOGLE_REQUEST_TOKEN'])) {
                $token = $this->getClient()->getAccessToken(
                    $query,
                    unserialize($_SESSION['GOOGLE_REQUEST_TOKEN'])
                );
                $_SESSION['GOOGLE_ACCESS_TOKEN'] = serialize($token);

                // Now that we have an Access Token, we can discard the Request Token
                $_SESSION['GOOGLE_REQUEST_TOKEN'] = null;

                $this->_token = $token;
            }
        }
        return $this->_token;
    }

    /**
     * Login
     *
     */
    public function login()
    {
        if ($token = $this->getToken()) {
            $client = $token->getHttpClient($this->getConfig());
            $client->setUri('https://www-opensocial.googleusercontent.com/api/people/@me/@self');
            $client->setMethod(Zend_Http_Client::GET);
            $response = $client->request();

            $info = Zend_Json::decode($response->getBody());

            $client->setUri('https://www.googleapis.com/userinfo/email');
            $response = $client->request();
            $emailData = explode('&',$response->getBody());
            $email = substr($emailData['0'], 6);

            $users = new Users_Model_Users_Table();
            if (!$row = $users->getByEmail($email)) {
                $row = $users->createRow();
                $row->email = $email;
                $row->login = $info['entry']['displayName'];
                $row->firstname = $info['entry']['name']['givenName'];
                $row->lastname = $info['entry']['name']['familyName'];
                $row->role = Users_Model_User::ROLE_USER;
                $row->status = Users_Model_User::STATUS_ACTIVE;
            }
            $row->gId = $info['entry']['id'];
            $row->logined = date('Y-m-d H:i:s');
            $row->ip = $this->getRequest()->getClientIp();
            $row->count++;
            $row->save();

            Zend_Auth::getInstance()->getStorage()->write($row);
        } else {
            $consumer = $this->getClient();
            // fetch a request token
            $token = $consumer->getRequestToken(
                array('scope' => 'http://www-opensocial.googleusercontent.com/api/people/ https://www.googleapis.com/auth/userinfo#email')
            );

            // persist the token to storage
            $_SESSION['GOOGLE_REQUEST_TOKEN'] = serialize($token);

            // redirect the user
            $consumer->redirect();
        }
    }
}