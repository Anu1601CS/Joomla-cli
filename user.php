<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

if (php_sapi_name() != 'cli')
{
	exit(1);
}

// We are a valid entry point.
const _JEXEC = 1;

/**
 * Set initial value for debug to improve "undefined notice" in fof.
 * Do not change this value at this point, because fof causes a fatal error
 * calling to a member function logAddLogger() on null.
 */
const JDEBUG = 0;

// Define core extension id
const CORE_EXTENSION_ID = 700;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework. 
require_once JPATH_LIBRARIES . '/import.legacy.php'; 

// Bootstrap the CMS libraries. 
require_once JPATH_LIBRARIES . '/cms.php'; 

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

// Load the JApplicationCli class
JLoader::import('joomla.application.cli');
JLoader::import('joomla.application.component.helper');
JLoader::import('joomla.filesystem.folder');
JLoader::import('joomla.filesystem.file');

/**
 * Manage Joomla extensions and core on the commandline
 *
 * @since  3.5.1
 */
class JoomlaCliUpdate extends JApplicationCli
{
	public function __construct() 
	{
        	parent::__construct();
 		JFactory::$application = $this;
    	}

	public function doExecute()
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->app = JFactory::getApplication('site');
    		$this->out($this->getSiteInfo());
	}

	public function getSiteInfo()
	{	
		
		$args = (array) $GLOBALS['argv'];
		
    		if(empty($args[2]))
    		{
    			$this->out('Command Not Found'."\n".
            			'1 : --dummy Name Only'."\n".
            			'2 : --user Full User INfo');
    			exit(1);
    		}

    	switch ($args[1]) 
      	{
		case  '--dummy':         	   
        		$result=$this->addusers($args[2]);
                     	break;
        	 
        	case  '--user':    
        	 	@$username = $args[2];		
		        @$name = $args[4];
		        @$email = $args[6];		
		        @$groups = $args[8];   	   
        	   	$result=$this->addusers($username,$name,$email,$groups);
        	   	break;  
   		
		default:   
          		$this->out('Command Not Found'."\n".
            		'1 : --dummy Name Only Dummy user'."\n".
            		'2 : --user  Full User Info needed'."\n");
             		break;   
    	}
    		
        $this->out();         
  }

   public function addusers($username='user',$name='user',$email='@xyz.com',$groups=2)
    {
    
    	require_once(JPATH_ADMINISTRATOR.'/components/com_users/models/user.php');
    
    	$num=1;
    	$user = new JUser();
	$array = array();
	$data = array();
	$array['username'] = $username;
	$array['name'] = $name;
	$array['email'] = $email;
	$array['password'] = '12132421';
	$array['groups']=array($groups);
	$array['activation']='';
	$array['block']=0;         
	$array['result']=array();

	$app = JFactory::getApplication('site');
	$app->initialise();   

    for ($u = 1; $u <= $num; $u++) {  
       	$user = JModelLegacy::getInstance('UsersModelUser');
       	$data['block'] = 0;
        $data['username']=$array['username'].$u;       
        $data['name']=$array['name'].$u;       
        $data['email']=$data['name'].$array['email'];       
        $data['groups']=$array['groups'];
        $data['password'] = '12132421';
        $data['password2']=$data['password'];

       	$result=$user->save($data);

       	if(!$result) 
        {
        	$this->out('User not created:'.$u)."\n";        	
        }
        else  
        {        	
		$this->out('User created:'.$u);        	
        }

       	$array['result']=array($u,$result)
    }	
      return $array['result']; 
  }

}

JApplicationCli::getInstance('JoomlaCliUpdate')->execute();
