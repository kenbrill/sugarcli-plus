<?php

    /**
     * SugarCLI
     *
     * PHP Version 5.3 -> 5.4
     * SugarCRM Versions 6.5 - 7.6
     *
     * @author Rémi Sauvat
     * @author Emmanuel Dyan
     * @author Kenneth Brill
     *
     * @copyright 2005-2016 iNet Process
     *
     * @package inetprocess/sugarcrm
     *
     * @license Apache License 2.0
     *
     * @link http://www.inetprocess.com
     */
    namespace SugarCli\Console\Command\Install;

    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Console\Helper\ProgressIndicator;
    use SugarCli\Console\Command\AbstractConfigOptionCommand;

    use Inet\SugarCRM\System as SugarSystem;

    class SystemRestoreCommand extends AbstractConfigOptionCommand
    {
        protected $messages = array();
        private $delete_destination=0;
        private $delete_cache=0;
        private $delete_upload=0;
        private $wwwPathName;
        private $instanceName;
        private $filesName;
        private $SQLName='';

        protected function configure()
        {
            $this->setName('install:restore')
                 ->setDescription("Restore a copy of SugarCRM from compressed files")
                 ->enableStandardOption('path')
                 ->addOption(
                     'files',
                     '',
                     InputOption::VALUE_REQUIRED,
                     'Instance Files',
                     ''
                 )
                 ->addOption(
                     'sql',
                     '',
                     InputOption::VALUE_OPTIONAL,
                     'SQL for the instance',
                     ''
                 )->addOption(
                    'delete_destination',
                    'd',
                    InputOption::VALUE_NONE,
                    'Delete the destination directory.'
                )->addOption(
                    'delete_cache',
                    'c',
                    InputOption::VALUE_NONE,
                    'Delete the cache directory.'
                )->addOption(
                    'delete_upload',
                    'u',
                    InputOption::VALUE_NONE,
                    'Delete the upload directory.'
                );
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $this->instanceName       = basename($input->getOption('path'));
            $this->filesName          = trim($input->getOption('files'));
            $this->SQLName            = trim($input->getOption('sql'));
            $this->delete_destination = $input->getOption('delete_destination');
            $this->delete_cache = $input->getOption('delete_cache');
            $this->delete_upload = $input->getOption('delete_upload');

            $this->wwwPathName=str_ireplace('/sugarcli/bin/sugarcli','',$_SERVER['SCRIPT_NAME']);

            if (!file_exists($this->filesName)) {
                $output->writeln(PHP_EOL . "<error>'{$this->filesName}' does not exist...</error>");
                return;
            }
            if (!empty($this->SQLName) && !file_exists($this->SQLName)) {
                $output->writeln(PHP_EOL . "<error>'{$this->SQLName}' does not exist...</error>");
                return;
            }
            if (file_exists("{$this->wwwPathName}/" . $this->instanceName)) {
                if (!$this->delete_destination) {
                    $output->writeln(PHP_EOL . "<error>{$this->instanceName} already exists in the web directory...</error>");
                    exit(1);
                } else {
                    exec("sudo rm -Rf {$this->wwwPathName}/{$this->instanceName}");
                }
            }

            $output->writeln(PHP_EOL . "-->Starting installation of " . $this->instanceName . '...');

            $output->writeln("<info> ---> Creating directory {$this->wwwPathName}/{$this->instanceName}</info>");

            //Make the instance directory
            mkdir("{$this->wwwPathName}/{$this->instanceName}", 0777, true);

            $filesInfo           = pathinfo($this->filesName);
            $fileCompressionType = $filesInfo['extension'];

            switch ($fileCompressionType) {
                case 'zip':
                    $this->decompressFiles($input, $output, false);
                    break;
                case 'gz':
                case 'tgz':
                case 'bz2':
                    $this->decompressFiles($input, $output);
                    break;
                default:
                    $output->writeln(PHP_EOL . "<error>Extension error on {$this->filesName}. Could not process type: {$fileCompressionType}</error>");
                    exit(1);
                    break;
            }

            if(!empty($this->SQLName)) {
                $filesInfo           = pathinfo($this->SQLName);
                $fileCompressionType = $filesInfo['extension'];
                $output->writeln(PHP_EOL . "<info>Processing SQL</info>");
                switch ($fileCompressionType) {
                    case 'zip':
                        $this->decompressSQL($input, $output, false);
                        exit(1);
                        break;
                    case 'gz':
                    case 'tgz':
                    case 'bz2':
                        $this->decompressSQL($input, $output);
                        break;
                    default:
                        $output->writeln(PHP_EOL . "<error>Extension error on {$this->filesName}. Could not process type: {$fileCompressionType}</error>");
                        exit(1);
                        break;
                }
            }

            $output->writeln("<info> ---> Updating config_override.php...</info>");
            $this->addToConfigOverride();
            $output->writeln("<info> ---> Creating .htaccess file...</info>");
            $this->writeHTAccess();
            $output->writeln("<info> ---> Resetting permissions...</info>");
            if(!file_exists("{$this->wwwPathName}/{$this->instanceName}/cache")) {
                mkdir("{$this->wwwPathName}/{$this->instanceName}/cache",0777);
            }
            if(!file_exists("{$this->wwwPathName}/{$this->instanceName}/upload")) {
                mkdir("{$this->wwwPathName}/{$this->instanceName}/upload",0777);
            }

            exec("sudo chmod -Rf 777 {$this->wwwPathName}/{$this->instanceName}", $commandOutput);
            exec("sudo chown -Rf apache:apache {$this->wwwPathName}/{$this->instanceName}", $commandOutput);

            $output->writeln('<info>Finished.</info>');

            return;
        }

        private function decompressFiles(InputInterface $input, OutputInterface $output, $gzip=true)
        {
            $tempDir = '/tmp/temp' . date('U');
            mkdir($tempDir, 777);
            $output->writeln(PHP_EOL . "<comment> --> Decompressing files...</comment>");
            if($gzip) {
                passthru("pv {$this->filesName} | tar xzf - -C {$tempDir}");
            } else {
                $cwd=getcwd();
                chdir($tempDir);
                passthru("unzip -q {$this->filesName}");
                chdir($cwd);
            }
            $rootPath = $this->findInstance($tempDir);
            if(!empty($rootPath)) {
                $output->writeln("<info> ---> Found root instance at {$rootPath}</info>");
                if ($this->delete_cache && file_exists("{$rootPath}/cache")) {
                    $output->writeln("<info> ---> Deleting {$rootPath}/cache/*</info>");
                    passthru("find {$rootPath}/cache -type f | pv -s $(find {$rootPath}/cache -type f | wc -c) | xargs rm");
                    //exec("sudo rm -Rfv {$rootPath}/cache/*");
                }
                if ($this->delete_upload && file_exists("{$rootPath}/upload")) {
                    $output->writeln("<info> ---> Deleting {$rootPath}/upload/*</info>");
                    passthru("find {$rootPath}/upload -type f | pv -s $(find {$rootPath}/upload -type f | wc -c) | xargs rm");
                    //exec("sudo rm -Rfv {$rootPath}/upload/*");
                }
                $output->writeln("<info> ---> Copying files into place...</info>");
                $cwd=getcwd();
                chdir($rootPath);
                passthru("find . -type f | pv -s $(find . -type f | wc -c) | xargs -i cp {} --parents {$this->wwwPathName}/{$this->instanceName}/$(dirname {})");
                chdir($cwd);
                if (empty($this->SQLName)) {
                    $sqlFile = $this->findInstance($tempDir,'SQL');
                    if(!empty($sqlFile)) {
                        if ($this->delete_destination) {
                            $output->writeln("<info> ---> Dropping old MySQL database '{$this->instanceName}'</info>");
                            exec("mysql -uroot -pqwerty -e 'DROP DATABASE {$this->instanceName};'", $commandOutput);
                        }
                        $output->writeln(PHP_EOL . "<comment> --> Creating database '{$this->instanceName}'...</comment>");
                        exec("mysql -uroot -pqwerty -e 'CREATE DATABASE {$this->instanceName};'", $commandOutput);
                        $output->writeln(PHP_EOL . "<comment> --> Importing SQL...</comment>");
                        foreach($sqlFile as $SQLFileName) {
                            $output->writeln("<info> ---> Found SQL at {$SQLFileName}</info>");
                            $output->writeln("<info> ---> Importing into MySQL database '{$this->instanceName}'</info>");
                            //exec("mysql -uroot -pqwerty {$this->instanceName} < $SQLFileName", $commandOutput);
                            passthru("pv {$SQLFileName} | mysql -uroot -pqwerty {$this->instanceName}");
                        }
                    } else {
                        $output->writeln("<error> ---> Could not find SQL file</error>");
                    }
                }
            } else {
                $output->writeln("<error> ---> Could not find files</error>");
            }
            $output->writeln(PHP_EOL . "<comment> --> Cleanup...</comment>");
            $output->writeln("<info> ---> Removing /tmp directory...</info>");
            exec("sudo rm -Rfv {$tempDir}", $commandOutput);
        }

        /**
         * @param InputInterface $input
         * @param OutputInterface $output
         * @param bool $gzip
         */
        private function decompressSQL(InputInterface $input, OutputInterface $output, $gzip=true)
        {
            $tempDir     = '/tmp/temp' . date('u');
            mkdir($tempDir, 777);
            $output->writeln(PHP_EOL . "<comment> ---> Decompressing SQL file '{$this->SQLName}'...</comment>");
            if($gzip) {
                passthru("pv {$this->SQLName} | tar xzf - -C {$tempDir}");
            } else {
                $cwd=getcwd();
                chdir($tempDir);
                passthru("unzip {$this->SQLName}");
                chdir($cwd);
            }

            $sqlFile = glob($tempDir . "/*.sql");
            if (empty($sqlFile)) {
                $output->writeln(PHP_EOL . "<error>SQL file not in root of compressed file.</error>");
                exit(1);
            }
            if ($this->delete_destination) {
                $output->writeln("<info> ---> Dropping old MySQL database '{$this->instanceName}'</info>");
                exec("mysql -uroot -pqwerty -e 'DROP DATABASE {$this->instanceName};'", $commandOutput);
            }
            $output->writeln(PHP_EOL . "<comment> --> Creating database '{$this->instanceName}'...</comment>");
            exec("mysql -uroot -pqwerty -e 'CREATE DATABASE {$this->instanceName};'", $commandOutput);
            $output->writeln(PHP_EOL . "<comment> --> Importing SQL...</comment>");
            foreach($sqlFile as $SQLFileName) {
                $output->writeln("<info> ---> Found SQL at {$SQLFileName}</info>");
                $output->writeln("<info> ---> Importing into MySQL database '{$this->instanceName}'</info>");
                //exec("mysql -uroot -pqwerty {$this->instanceName} < $SQLFileName", $commandOutput);
                passthru("pv {$SQLFileName} | mysql -uroot -pqwerty {$this->instanceName}");
            }
            $output->writeln(PHP_EOL . "<comment> --> Cleanup...</comment>");
            $output->writeln("<info> ---> Removing /tmp directory...</info>");
            exec("sudo rm -Rfv {$tempDir}", $commandOutput);
        }

        /**
         * @param $directoryToCheck
         * @return String
         */
        private function findInstance($directoryToCheck, $whatToFind='SugarInstance')
        {
            if($whatToFind=='SugarInstance') {
                $key='sugar_version.json';
                $it = new \RecursiveDirectoryIterator($directoryToCheck);
                foreach (new \RecursiveIteratorIterator($it) as $file) {
                    $fileinfo = pathinfo($file);
                    if (stristr($fileinfo['basename'],$key) !== false) {
                        return $fileinfo['dirname'];
                    }
                }
            } else {
                $iterator = new \RecursiveDirectoryIterator($directoryToCheck);
                foreach ($iterator as $fileinfo) {
                    if ($fileinfo->isDir()) {
                        if($fileinfo->getFilename()!='.' && $fileinfo->getFilename()!='..') {
                            $SQLFile =
                                glob($directoryToCheck . DIRECTORY_SEPARATOR . $fileinfo->getFilename() . DIRECTORY_SEPARATOR . '*.sql');
                            if (!empty($SQLFile)) {
                                return $SQLFile;
                            }
                        }
                    }
                }
            }

            return false;
        }

        private function addToConfigOverride()
        {
            $fileName = "{$this->wwwPathName}/{$this->instanceName}/config_override.php";
            if (file_exists($fileName)) {
                $extraConfig = "<?php\n";
            } else {
                $extraConfig = "\n";
            }
            $extraConfig .= "
\$sugar_config['dbconfig'] =
  array (
      'db_host_name' => 'localhost',
      'db_host_instance' => 'SQLEXPRESS',
      'db_user_name' => 'root',
      'db_password' => 'qwerty',
      'db_name' => '{$this->instanceName}',
      'db_type' => 'mysql',
      'db_port' => '',
      'db_manager' => 'MysqliManager',
  );
\$sugar_config['full_text_engine']['Elastic']['host'] = 'localhost';
\$sugar_config['full_text_engine']['Elastic']['port'] = 9200;
\$sugar_config['full_text_engine']['Elastic']['valid'] = true;
\$sugar_config['host_name'] = 'localhost:8888';
\$sugar_config['site_url'] = 'http://localhost:8888/{$this->instanceName}';";


            $fh = fopen($fileName, "a+");
            fwrite($fh, $extraConfig);
            fclose($fh);
        }

        private function writeHTAccess()
        {
            $htaccess = "# BEGIN SUGARCRM RESTRICTIONS
RedirectMatch 403 (?i).*\.log$
RedirectMatch 403 (?i)/+not_imported_.*\.txt
RedirectMatch 403 (?i)/+(soap|cache|xtemplate|data|examples|include|log4php|metadata|modules)/+.*\.(php|tpl)
RedirectMatch 403 (?i)/+emailmandelivery\.php
RedirectMatch 403 (?i)/+upload/
RedirectMatch 403 (?i)/+custom/+blowfish
RedirectMatch 403 (?i)/+cache/+diagnostic
RedirectMatch 403 (?i)/+files\.md5$
RedirectMatch 403 (?i)/+composer\.(json|lock)
RedirectMatch 403 (?i)/+vendor/composer/
RedirectMatch 403 (?i).*/\.git

# Fix mimetype for logo.svg (SP-1395)
AddType     image/svg+xml     .svg
AddType     application/json  .json
AddType     application/javascript  .js

<IfModule mod_rewrite.c>
    Options +FollowSymLinks
    RewriteEngine On
    RewriteBase /{$this->instanceName}
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^rest/(.*)$ api/rest.php?__sugar_url=$1 [L,QSA]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^cache/api/metadata/lang_(.._..)_(.*)_public(_ordered)?\.json$ rest/v10/lang/public/$1?platform=$2&ordered=$3 [N,QSA,DPI]

    RewriteRule ^cache/api/metadata/lang_(.._..)_([^_]*)(_ordered)?\.json$ rest/v10/lang/$1?platform=$2&ordered=$3 [N,QSA,DPI]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^cache/Expressions/functions_cache(_debug)?.js$ rest/v10/ExpressionEngine/functions?debug=$1 [N,QSA,DPI]
    RewriteRule ^cache/jsLanguage/(.._..).js$ index.php?entryPoint=jslang&module=app_strings&lang=$1 [L,QSA,DPI]
    RewriteRule ^cache/jsLanguage/(\w*)/(.._..).js$ index.php?entryPoint=jslang&module=$1&lang=$2 [L,QSA,DPI]
    RewriteRule ^portal/(.*)$ portal2/$1 [L,QSA]
    RewriteRule ^portal$ portal/? [R=301,L]
</IfModule>

<IfModule mod_mime.c>
    AddType application/x-font-woff .woff
</IfModule>
<FilesMatch \"\.(jpg|png|gif|js|css|ico|woff|svg)$\">
        <IfModule mod_headers.c>
                Header set ETag \"\"
                Header set Cache-Control \"max-age=2592000\"
                Header set Expires \"01 Jan 2112 00:00:00 GMT\"
        </IfModule>
</FilesMatch>
<IfModule mod_expires.c>
        ExpiresByType text/css \"access plus 1 month\"
        ExpiresByType text/javascript \"access plus 1 month\"
        ExpiresByType application/x-javascript \"access plus 1 month\"
        ExpiresByType image/gif \"access plus 1 month\"
        ExpiresByType image/jpg \"access plus 1 month\"
        ExpiresByType image/png \"access plus 1 month\"
        ExpiresByType application/x-font-woff \"access plus 1 month\"
        ExpiresByType image/svg \"access plus 1 month\"
</IfModule>
# END SUGARCRM RESTRICTIONS";
            $fileName = "{$this->wwwPathName}/{$this->instanceName}/.htaccess";
            $fh       = fopen($fileName, "w");
            fwrite($fh, $htaccess);
            fclose($fh);
        }
    }
