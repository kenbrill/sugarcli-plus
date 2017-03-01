<?php
    /**
     * SugarCLI
     *
     * PHP Version 5.3 -> 5.4
     * SugarCRM Versions 6.5 - 7.6
     *
     * @author Rémi Sauvat
     * @author Emmanuel Dyan
     * @copyright 2005-2015 iNet Process
     *
     * @package inetprocess/sugarcrm
     *
     * @license Apache License 2.0
     *
     * @link http://www.inetprocess.com
     */

    namespace SugarCli\Console\Command\Code;

    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use SugarCli\Console\ExitCode;
    use SugarCli\Console\Command\AbstractConfigOptionCommand;

    /**
     * Make a package out of custom/ directory
     */
    class MakePackageCommand extends AbstractConfigOptionCommand
    {
        protected $ignore_dirs = array("custom\/application\/Ext\/",
                                       "custom\/index.html",
                                       "custom\/blowfish\/",
                                       "custom\/install\/",
                                       "custom\/working\/",
                                       "custom\/history\/",
                                       "modules\/PMSE_",
                                       "modules\/ops_Backups\/",
                                       "custom\/modules\/.*\/Ext\/",
                                       "\/zips\/.*\.zip",
                                       "\.suback\.",
                                       ".*\.ext\.php"
        );
        protected $tmpDir;
        public $installDefs = array();
        private $path;
        private $output;
        private $pname;
        private $pversion;
        private $search;

        private $empty = false;
        private $modifiedTime = 0;
        private $customFields = false;
        private $modulebuilder = false;
        private $reports = false;
        private $acl = false;
        private $teams = false;
        private $data = false;
        private $onlyenglish = false;
        private $newTable;
        private $pdo;

        protected function configure()
        {
            $this->setName('code:makePackage')
                 ->setDescription('Makes an installable package out of the custom directory.')
                 ->enableStandardOption('path')
                 ->addOption(
                     'acl',
                     'a',
                     InputOption::VALUE_NONE,
                     'include the ACL related tables'
                 )->addOption(
                    'modulebuilder',
                    'm',
                    InputOption::VALUE_NONE,
                    'include the modulebuilder/ directory'
                )->addOption(
                    'onlyenglish',
                    'o',
                    InputOption::VALUE_NONE,
                    'include only english language files'
                )->addOption(
                    'customFields',
                    'c',
                    InputOption::VALUE_NONE,
                    'include Custom Fields from fields_meta_data'
                )->addOption(
                    'reports',
                    'r',
                    InputOption::VALUE_NONE,
                    'include the saved_reports table'
                )->addOption(
                    'teams',
                    't',
                    InputOption::VALUE_NONE,
                    'include the Teams related tables'
                )->addOption(
                    'empty',
                    'e',
                    InputOption::VALUE_NONE,
                    'Truncate tables before INSERTING data'
                )->addOption(
                    'data',
                    'd',
                    InputOption::VALUE_NONE,
                    'include Data from custom modules'
                )->addOption(
                    'packageName',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'The Name for the package'
                )->addOption(
                    'packageVersion',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'The Version for the package'
                )->addOption(
                    'search',
                    's',
                    InputOption::VALUE_REQUIRED,
                    'Include Only files with this search string in them'
                )->addOption(
                    'updated',
                    'u',
                    InputOption::VALUE_REQUIRED,
                    'Collect files modified in the last number of minutes',
                    '0'
                );
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $this->path          = $input->getOption('path');
            $this->data          = $input->getOption('data');
            $this->customFields  = $input->getOption('customFields');
            $this->acl           = $input->getOption('acl');
            $this->teams         = $input->getOption('teams');
            $this->reports       = $input->getOption('reports');
            $this->modulebuilder = $input->getOption('modulebuilder');
            $this->empty         = $input->getOption('empty');
            $this->onlyenglish   = $input->getOption('onlyenglish');
            $this->modifiedTime  = $input->getOption('updated');
            $this->pname         = $input->getOption('packageName');
            if ($this->pname == 'CustomFiles') {
                $this->pname = 'custom_files_' . basename($this->path);
            }
            $this->pversion = $input->getOption('packageVersion');
            $this->search   = $input->getOption('search');
            $this->output   = $output;
            $this->pdo      = $this->getService('sugarcrm.pdo');

            $this->installDefs = array(
                'id'          => 'CUSTOM' . date("U"),
                'copy'        => array(),
                'pre_execute' => array()
            );

            $this->makeTempDirectory();

            if (!$this->modulebuilder) {
                $this->ignore_dirs[] = "custom\/modulebuilder\/";
            }

            if ($this->onlyenglish) {
                $this->ignore_dirs[] = "\/language\/(?:(?!en_us).)*\..*\.php";
            }

            $name = "~/custom_files_" . basename($this->path) . ".zip";
            if (file_exists($name)) {
                $output->writeln(PHP_EOL . "<error>'{$name}' already exists...</error>");
                return;
            }
            if(!empty($this->search)) {
                $this->output->writeln("Including only files that contain the string '{$this->search}'.");
            }
            if($this->modifiedTime>0) {
                $this->output->writeln("Including only files that were modified in the last {$this->modifiedTime} minutes.");
            }

            $this->output->writeln("Copying custom/ directory.");
            $objects =
                new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path . DIRECTORY_SEPARATOR . 'custom'));
            $data    = array();
            foreach ($objects as $file) {
                $mTime       = filemtime($file->getPathname());
                $fTime       = \DateTime::createFromFormat('U', $mTime);
                $nTime       = \DateTime::createFromFormat('U', date("U"));
                $diff        = date_diff($nTime, $fTime);
                $secondsDiff = $diff->s + ($diff->i * 60) + ($diff->h * 3600) + ($diff->d * 86400);
                $minutesDiff = intval($secondsDiff / 60);
                if ($file->isFile()) {
                    if ($this->modifiedTime == 0 || $this->modifiedTime > $minutesDiff) {
                        if (empty($this->search)) {
                            $data[] = array('filename' => $file->getPathname(), 'time' => $fTime->getTimestamp(),
                                            'minutes'  => $minutesDiff);
                        } else {
                            if ($this->searchFile($this->search, $file->getPathname())) {
                                $data[] = array('filename' => $file->getPathname(), 'time' => $fTime->getTimestamp(),
                                                'minutes'  => $minutesDiff);
                            }
                        }
                    } elseif (!empty($this->search) && $this->modifiedTime == 0) {
                        if ($this->searchFile($this->search, $file->getPathname())) {
                            $data[] = array('filename' => $file->getPathname(), 'time' => $fTime->getTimestamp(),
                                            'minutes'  => $minutesDiff);
                        }
                    }
                }
            }
            usort($data, function ($a, $b) { // sort by time latest
                return $b['time'] - $a['time'];
            });

            foreach ($data as $key => $object) {

                if ($this->checkPath($object['filename'])) {
                    if ($this->modifiedTime != 0 || !empty($this->search)) {
                        $this->output->writeln($object['filename']."\n".$object['minutes']);
                    }
                    $fileName                    = $this->copyFile($object['filename']);
                    $this->installDefs['copy'][] =
                        array('from'      => '<basepath>/files' . DIRECTORY_SEPARATOR . $fileName, 'to' => $fileName,
                              'timestamp' => date('Y-m-d H:i:s', $object['time']));
                }

            }

            //Add files from /modules/CUSTOM_MODULES

            if (is_file($this->path . '/custom/application/Ext/Include/modules.ext.php')) {
                $this->output->writeln("Copying custom modules.");
                $beanFiles = array();
                include($this->path . '/custom/application/Ext/Include/modules.ext.php');
                foreach ($beanFiles as $index => $fileName) {
                    $directory = $this->path . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $index;
                    if (is_dir($directory)) {
                        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
                        foreach ($objects as $name => $object) {
                            if ($object->isFile()) {
                                if ($this->checkPath($name)) {
                                    $mTime       = filemtime($object->getPathname());
                                    $fTime       = \DateTime::createFromFormat('U', $mTime);
                                    $nTime       = \DateTime::createFromFormat('U', date("U"));
                                    $diff        = date_diff($nTime, $fTime);
                                    $secondsDiff = $diff->s + ($diff->i * 60) + ($diff->h * 3600) + ($diff->d * 86400);
                                    $minutesDiff = intval($secondsDiff / 60);
                                    if($this->modifiedTime==0 || $this->modifiedTime > $minutesDiff) {
                                        if(empty($this->search)) {
                                            $fileName                    = $this->copyFile($name);
                                            $this->installDefs['copy'][] =
                                                array('from' => '<basepath>/files' . DIRECTORY_SEPARATOR . $fileName,
                                                      'to'   => $fileName);
                                        } else {
                                            if ($this->searchFile($this->search, $object->getPathname())) {
                                                $fileName                    = $this->copyFile($name);
                                                $this->installDefs['copy'][] =
                                                    array('from' => '<basepath>/files' . DIRECTORY_SEPARATOR . $fileName,
                                                          'to'   => $fileName);
                                            }
                                        }
                                    } elseif (!empty($this->search) && $this->modifiedTime == 0) {
                                        if ($this->searchFile($this->search, $object->getPathname())) {
                                            $fileName                    = $this->copyFile($name);
                                            $this->installDefs['copy'][] =
                                                array('from' => '<basepath>/files' . DIRECTORY_SEPARATOR . $fileName,
                                                      'to'   => $fileName);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $output->writeln(PHP_EOL . "<error>'{$directory}' is in the module list but does not exist in the modules directory!</error>");
                    }
                }
            }
            if($this->data || $this->customFields || $this->acl || $this->teams || $this->reports) {
                $this->makePreExecute();
            }
            $this->makeManifest();
            $this->add_post_install_scripts();
            $this->makeZipFile();
            $output->writeln("Done...");
        }

        private function checkPath($fileName)
        {
            foreach ($this->ignore_dirs as $ignore_dir) {
                if (preg_match("/" . $ignore_dir . "/i", $fileName)) {
                    return false;
                }
            }
            return true;
        }

        private function makeTempDirectory()
        {
            $this->tmpDir = "/tmp/temp" . date("U");
            $this->output->writeln("Creating TMP directory: " . $this->tmpDir);
            if (!mkdir($this->tmpDir . DIRECTORY_SEPARATOR . 'files', 0777, true)) {
                die("Could not create {$this->tmpDir}");
            }
            if (!mkdir($this->tmpDir . DIRECTORY_SEPARATOR . 'sql', 0777, true)) {
                die("Could not create {$this->tmpDir}");
            }
        }

        private function makeZipFile()
        {
            $name = "~/" . $this->pname . ".zip";
            $this->output->writeln("Creating ZIP file: " . $name);
            exec("cd {$this->tmpDir};zip -r {$name} .");
        }

        private function copyFile($from)
        {
            $newFileName = substr($from, strlen($this->path) + 1);

            $to = $this->tmpDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $newFileName;

            //$this->output->writeln($this->tmpDir . DIRECTORY_SEPARATOR . $newFileName);
            if (!is_dir(dirname($to))) {
                mkdir(dirname($to), 0777, true);
            }
            if (!copy($from, $to)) {
                die("Could not copy file '{$from}' to '{$to}'");
            }
            return $newFileName;
        }

        private function makePreExecute()
        {
            $this->newTable = false;
            $this->output->writeln("Creating pre_execute scripts.");
            if ($this->customFields) {
                $this->includeTable('fields_meta_data');
            }
            if ($this->teams) {
                $this->includeTable('teams');
                $this->includeTable('team_sets');
                $this->includeTable('team_sets_teams');
            }
            if ($this->acl) {
                $this->includeTable('acl_actions');
                $this->includeTable('acl_fields');
                $this->includeTable('acl_roles_actions');
                $this->includeTable('acl_role_sets');
                $this->includeTable('acl_role_sets_acl_roles');
            }
            if ($this->reports) {
                $this->includeTable('saved_reports');
            }

            if ($this->data) {
                if (is_file($this->path . '/custom/application/Ext/Include/modules.ext.php')) {
                    $this->newTable = true;
                    $moduleList     = array();
                    $tableNames     = array();
                    include($this->path . '/custom/application/Ext/Include/modules.ext.php');
                    $result = $this->pdo->query("SHOW TABLES");
                    foreach ($result as $row) {
                        foreach ($row as $index => $tableName) {
                            $tableNames[] = $tableName;
                        }
                    }
                    foreach ($moduleList as $className) {
                        foreach ($tableNames as $tableName) {
                            if (stristr($tableName, $className) !== false) {
                                $command = $this->createTable($tableName);
                                if ($command !== false) {
                                    $header = "<?php\n";
                                    $header .= "\$GLOBALS['db']->query(\"DROP TABLE IF EXISTS {$tableName}\");\n";
                                    $header .= "\$GLOBALS['db']->query(\"{$command}\");\n";
                                    $this->includeTable($tableName, '', $header);
                                }
                            }
                        }
                    }
                }
            }
        }

        private function searchFile($searchForThis, $fileName)
        {
            $match  = false;
            $handle = @fopen($fileName, "r");
            if ($handle) {
                while (!feof($handle)) {
                    $buffer = fgets($handle);
                    if (strpos($buffer, $searchForThis) !== false) {
                        $match = true;
                    }
                }
                fclose($handle);
            }
            return $match;
        }

        private function createTable($tableName)
        {
            if ($tableName == 'ops_backups') {
                return false;
            }
            $data = $this->pdo->query("SHOW CREATE TABLE {$tableName}");
            foreach ($data as $row) {
                $command = $row[1];
                return $command;
            }
            return false;
        }

        private function includeTable($tableName, $fileName = '', $header = '<?php')
        {
            $fields       = array();
            $preExecute   = array();
            $preExecute[] = $header;
            $insertHeader = true;

            if (empty($fileName)) {
                $fileName = $tableName;
            }
            if (isset($this->installDefs['pre_execute'][$fileName])) {
                return null;
            }
            if ($this->empty && $this->newTable == false) {
                $preExecute[] = "\$GLOBALS['db']->query(\"TRUNCATE {$tableName}\");";
            }
            $query = "SELECT * FROM {$tableName}";
            $data  = $this->pdo->query($query);

            if ($data === false) {
                throw new \PDOException("Query Failed: " . PHP_EOL . $query);
            }
            if (!$this->empty && $this->newTable == false) {
                $preExecute[] = "\$GLOBALS['db']->query(\"DELETE FROM {$tableName} WHERE id='{$fields['id']}'\");";
            }

            $values        = array();
            $extendedCount = 0;
            foreach ($data as $row) {
                foreach ($row as $index => $value) {
                    if (!is_numeric($index)) {
                        $fields[$index] = $this->pdo->quote($value);
                    }
                }
                if (($this->newTable && $this->data) || !$this->newTable) {
                    $fieldList = implode(",", array_keys($fields));
                    $valueList = implode(",", array_values($fields));
                    if ($insertHeader) {
                        $preExecute[] = "\$GLOBALS['db']->query(\"INSERT INTO {$tableName} ({$fieldList}) VALUES";
                        $insertHeader = false;
                    }
                    $values[] = "({$valueList})";
                    $extendedCount++;
                    if ($extendedCount == 100) {
                        $preExecute[]  = implode(",\n", $values) . "\");";
                        $preExecute[]  = "\$GLOBALS['db']->query(\"INSERT INTO {$tableName} ({$fieldList}) VALUES";
                        $extendedCount = 0;
                        $values        = array();
                    }
                }
            }
            if (!empty($values)) {
                $preExecute[] = implode(",", $values) . "\");";
            } else {
                $lastOne = array_pop($preExecute);
                if ($lastOne != "\$GLOBALS['db']->query(\"INSERT INTO {$tableName} ({$fieldList}) VALUES") {
                    $preExecute[] = $lastOne;
                }
            }
            $fh                                          =
                fopen($this->tmpDir . DIRECTORY_SEPARATOR . "sql" . DIRECTORY_SEPARATOR . "{$fileName}.php", "w");
            $this->installDefs['pre_execute'][$fileName] = "<basepath>/sql/{$fileName}.php";
            fwrite($fh, implode("\n", $preExecute));
            fclose($fh);
            $this->output->writeln("->Created a pre_execute script for {$tableName}.");
        }

        private function add_post_install_scripts() {
            if(file_exists($this->path . DIRECTORY_SEPARATOR . 'post_install.php')) {
                $this->output->writeln("Adding POST_INSTSALL script.");
                mkdir($this->tmpDir . DIRECTORY_SEPARATOR . 'scripts',0777,true);
                copy($this->path . DIRECTORY_SEPARATOR . 'post_install.php',$this->tmpDir . DIRECTORY_SEPARATOR . 'scripts/post_install.php');
            }
        }

        private function makeManifest()
        {
            $this->output->writeln("Creating Manifest.");
            $dop      = date("Y-m-d H:i:s");
            $version  = $this->pversion;
            $manifest = "<?php
\$manifest = array(
    'acceptable_sugar_flavors' => array('CE','PRO','CORP','ENT','ULT'),
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array('(.*?)\\.(.*?)\\.(.*?)$'),
    ),
    'author' => 'Plus Consulting',
    'description' => 'Custom/ directory files',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => '{$this->pname}',
    'published_date' => '{$dop}',
    'type' => 'module',
    'version' => '{$version}'
);\n\n\$installdefs =";
            $manifest .= var_export($this->installDefs, true);
            $manifest .= ";";
            $fh = fopen($this->tmpDir . DIRECTORY_SEPARATOR . "manifest.php", "w");
            fwrite($fh, $manifest);
            fclose($fh);
        }
    }

