<?php

// CLI not a web interface
if (php_sapi_name() !== 'cli')
{
    die('This is a Command Line Application Only.');
}

// We are a valid entry point.
const _JEXEC = 1;

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

// ----------------------
class ArticleCli extends JApplicationCli
{
    public function __construct() 
    {
        parent::__construct();
        
        $this->config = new JConfig();
        $this->db = JFactory::getDbo();
        
        JFactory::$application = $this;
    }

    public function doExecute() 
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->app = JFactory::getApplication('site');

        $category_titles = (array) $GLOBALS['argv'];
        
        if($this->input->get('a',False))
        {    
            // add categories to database
            for ($i = 2; $i <= count($category_titles); $i++)
            {   
                if(!empty($category_titles[2]))
                {
                    $this->save($category_titles[$i]);
                }
                else
                {   $this->out();
                    $this->out('Title Empty. Abroting.....');
                    $this->out();
                }   
            }
        }
        else
        {   
            $this->out();
            $this->out('Command Not Found =====>   '.$category_titles[1]);
            $this->out();
            $this->out('Use   ======>    php article.php --a <Your titles> ');
            $this->out();
        } 

    }

    private function save($title) 
    {   
        $table = JTable::getInstance("Category");
        
        // get existing aliases from categories table
        $tab = $this->db->quoteName($this->config->dbprefix . "categories");
        $conditions = array(
            $this->db->quoteName("extension") . " LIKE 'com_content'"
        );

        $query = $this->db->getQuery(true);
        $query->select($this->db->quoteName("alias"))
              ->from($tab)
              ->where($conditions);

        $this->db->setQuery($query);
        $ext = $this->db->loadObjectList();

        $category_existing = False;
        $new_alias = JFilterOutput::stringURLSafe($title);

        foreach ($ext as $pks) 
        {
            if ($pks->alias == $new_alias) 
            {
                $category_existing = True;
                $this->out("Category already existing: " . $new_alias . "\n");
            }
        }

        if (!$category_existing)
        {
            $values = [
                "id"    => null,
                "title" => $title,
                "path"  => $new_alias,
                "access" => 1,
                "extension" => "com_content",
                "published" => 1,
                "language"  => "*",
                "created_user_id" => 0,
                "params" => array (
                    "category_layout" => "",
                    "image" => "",
                ),
                "metadata" => array (
                    "author" => "Anurag",
                    "robots" => "",
                ),
            ];

            $table->setLocation(1, "last-child");

            if (!$table->bind($values)) 
            {
                $this->out('Save Failed'."\n");
                return FALSE;
            }

            if (!$table->check()) 
            {
                $this->out('Save Failed'."\n");
                return FALSE;
            }

            if (!$table->store(TRUE)) 
            {
                $this->out('Save Failed'."\n");
                return FALSE;
            } 

            $table->rebuildPath($table->id); 
            $this->out("Category inserted: " . $table->id . " - " . $new_alias . "\n");

            $this->out('Save Done!'."\n");
        }
    }
}

JApplicationCli::getInstance('ArticleCli')->execute();

