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

        $options = array(
                'driver' => $this->config->dbtype,
                'host' => $this->config->host,
                'user' => $this->config->user,
                'password' => $this->config->password,
                'database' => $this->config->db,
                'prefix' => $this->config->dbprefix,
            );

        $this->dbo = JDatabaseDriver::getInstance($options);
        
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

    private function save($cat_title) {
        
        $table = JTable::getInstance("Category");

        // get existing aliases from categories table
        $tab = $this->dbo->quoteName($this->config->dbprefix . "categories");
        $conditions = array(
            $this->dbo->quoteName("extension") . " LIKE 'com_content'"
        );


        $query = $this->dbo->getQuery(true);
        $query
            ->select($this->dbo->quoteName("alias"))
            ->from($tab)
            ->where($conditions);

        $this->dbo->setQuery($query);
        $cat_from_db = $this->dbo->loadObjectList();

        $category_existing = False;
        $new_alias = JFilterOutput::stringURLSafe($cat_title);

        foreach ($cat_from_db as $cdb) {
            if ($cdb->alias == $new_alias) {
                $category_existing = True;
                $this->out("category already existing: " . $new_alias . "\n");
            }
        }
            
        if (!$category_existing) {

            $values = [
                "id"	=> null,
                "title" => $cat_title,
                "path" 	=> $new_alias,
                "access" => 1,
                "extension" => "com_content",
                "published" => 1,
                "language" 	=> "*",
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

            if (!$table->bind($values)) {
                JLog::add($row->getError(), JLog::ERROR, 'jerror');
                return FALSE;
                
            }

            if (!$table->check()) {
                JLog::add($row->getError(), JLog::ERROR, 'jerror');
                return FALSE;
            }

            if (!$table->store(TRUE)) {
                JLog::add($row->getError(), JLog::ERROR, 'jerror');
                return FALSE;
            } 

            $table->rebuildPath($table->id); 
            $this->out("category inserted: " . $table->id . " - " . $new_alias . "\n");
        }
    }
}

try {
    JApplicationCli::getInstance('ArticleCli')->execute();
} 
catch (Exception $e) {
   
    fwrite(STDOUT, $e->getMessage() . "\n");
    exit($e->getCode());
}

?>
