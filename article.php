<?php

// CLI not a web interface
if (php_sapi_name() !== 'cli')
{
    die('This is a command line only application.');
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

require_once JPATH_LIBRARIES . '/bootstrap.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

// ----------------------
class ArticleCli extends JApplicationCli
{
    public function __construct() {
        parent::__construct();

        $this->config = new JConfig();
        $this->db = JFactory::getDbo();
        
        JFactory::$application = $this;
    }

    public function doExecute() {

        $category_titles = array("Red", "Blue", "Green");

        // add categories to database
        for ($i = 0; $i < count($category_titles); $i++)
        {
            $this->save($category_titles[$i]);
        }
    }

    private function save($title) {
        
        $table = JTable::getInstance("Category");

        // get existing aliases from categories table
        $tab = $this->db->quoteName($this->config->dbprefix . "categories");
        $conditions = array(
            $this->db->quoteName("extension") . " LIKE 'com_content'"
        );

        $query = $this->db->getQuery(true);
        $query
            ->select($this->db->quoteName("alias"))
            ->from($tab)
            ->where($conditions);

        $this->db->setQuery($query);
        $cat_from_db = $this->db->loadObjectList();

        $category_existing = False;
        $new_alias = JFilterOutput::stringURLSafe($title);

        foreach ($cat_from_db as $cdb) 
        {
            if ($cdb->alias == $new_alias) 
            {
                $category_existing = True;
                $this->out("category already existing: " . $new_alias . "\n");
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
                JLog::add($row->getError(), JLog::ERROR, 'jerror');
                return FALSE;
                
            }

            if (!$table->check()) 
            {
                JLog::add($row->getError(), JLog::ERROR, 'jerror');
                return FALSE;
            }

            if (!$table->store(TRUE)) 
            {
                JLog::add($row->getError(), JLog::ERROR, 'jerror');
                return FALSE;
            } 

            $table->rebuildPath($table->id); 
            $this->out("category inserted: " . $table->id . " - " . $new_alias . "\n");
        }
    }
}
JApplicationCli::getInstance('ArticleCli')->execute();

?>
