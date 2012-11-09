<?php
/**
 * FacebookComponent class
 */
class FacebookComponent extends CApplicationComponent
{
    public $appId;
    public $secret;
    
    public $sdkLocation = 'application.vendors.facebook.src.facebook';
    
    public $messages = array();

    /**
     * @var Facebook
     */
    public $fb;
    
    private $_userData = null;
    
    public function init()
    {
        // Attach SDK
        Yii::import($this->sdkLocation, true);
        
        if(is_null($this->fb))
        {
            $this->fb = new Facebook(array(
                'appId'  => $this->appId,
                'secret' => $this->secret,
            ));
        }
        
        //$this->fb->setCookieSupport(true);
    }

    
    public function __call($name, $attrs)
    {
        return call_user_func_array(array($this->getInstance(), $name), $attrs);
    }
    
    
    /**
	 * Checks that facebook granted selected permissions.
	 * @param string $permissions comma separeted list with given permissions
	 * @return boolean
     */
    public function checkPermissions($permissions)
    {
        try {
            $user = $this->fb->getUser();
            
            if ( !empty($user) ) {
                // FB session is active
                
                // Retrieve given permissions
                $givenPermissions = $this->getPermissons();
                
                // Check that all required permissions are granted
                $valid = true;
                $permissionsArray = explode(',', $permissions);
                foreach ($permissionsArray as $permission) {
                    if ( !isset($givenPermissions[$permission]) || !$givenPermissions[$permission] ) {
                        $valid = false;
                        break;
                    }
                }
                
                if ($valid)
                    return true; // All required permissions are granted
            }
            
        } catch (FacebookApiException $e) {
            // Facebook can throw an exception if session is too old.
            
        }
        
        // Not all required permissions are granted
        return false;
    }
    
    # Getters #
    
    /**
     * Returns facebook user data
     * @return array
     */
    public function getUserData()
    {
        if ( !isset($this->_userData) ) {
            $this->_userData = $this->fb->api('/me');
        }
        
        return $this->_userData;
    }
    
    /**
     * Returns current user friends array
     * @return array
     */
    public function getFriends()
    {
        $friends = $this->fb->api('/me/friends');
        return $friends['data'];
    }
    
    /**
     * Returns array of permissions user granted to application
     * @return array
     */
    public function getPermissons()
    {
        // Retrieve given permissions
        $permissions = $this->fb->api('/me/permissions');
        return $permissions['data'][0];
    }
    
    # Redirect actions #
    
    /**
	 * Redirects user to facebook to grant selected permissions.
	 * Does nothing if all required permissions are granted
	 * @param string $permissions comma separeted list with given permissions
	 * @param boolean $force redirects user to facebook without checking permissions
	 */
    public function grantPermissions($permissions, $force = false)
    {
        if ( !$force && $this->checkPermissions($permissions) )
            return; // All required permissions are granted, we can return
        
        // Not all required permissions are granted
        // User will beredirected to facebook page and return here
        
        $url = $this->fb->getLoginUrl(array(
            'scope' => $permissions,
        ));
        
        Yii::app()->controller->redirect($url);
    }
    
    /**
     * Tryes to login user
     * @param string $permissions comma separeted list with given permissions
     * @return array|boolean array with user data or false if user denied login
     */
    public function login($permissions = 'email')
    {
        if (Yii::app()->request->getQuery('error_reason', null) == 'user_denied')
            return false;
        
        $this->grantPermissions($permissions);
        
        return $this->getUserData();
    }

    # Facebook actions #
    
    /**
     * @param mixed $message array - message parameters, string - predefined message name
     * @param array $ids friends facebook ids
     */
    public function postLink($message, $ids = null)
    {
        // Get predefined message params
        if ( is_string($message) ) {
            $message = $this->messages[$message];
        }
        
        // Add default values
        $message = array_merge(array(
            'message' => '',
            'picture' => '',
            'link'    => '',
            'name'    => Yii::app()->name,
            'caption' => '',
        ), $message);
        
        // Make picture link absolute
        if ( strncmp($message['picture'], '/', 1) == 0 ) {
            $message['picture'] = Yii::app()->request->hostInfo . $message['picture'];
        }
        
        // Create url from route
        if ( is_array($message['link']) ) {
            $url = $message['link'];
            $route = isset($url[0]) ? $url[0] : '';
            
            $params = array_splice($url, 1);
            
            // Setting user id if it's needed
            $key = array_search('[userId]', $params);
            if ($key !== false)
                $params[$key] = Yii::app()->user->id;
            
            $url = Yii::app()->createAbsoluteUrl($route, $params);
            $message['link'] = $url;
        }
        
        // If recipient ids are null - posting to user's wall
        if ($ids === null)
            $ids = array('me');
        
        
        foreach ($ids as $id) {
            $this->fb->api("/$id/feed", 'POST', $message);
        }
    }
    
}
