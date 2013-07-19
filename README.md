# PHP library to manage the linux crontab

*currently this lib can only handle the crontab of the user the script is executed by*

## Install using composer
Simply add the following to your *composer.json* and run *composer update*

```javascript
{
	"require": {
		"zolex/phpcron": "dev-master"
    }
}
```

## Example usage
Use-Case: When deploying an application update the crontab depending on the release version (this can for exmaple be called from within a capistrano deploy script)

```shell
php update-crontab.php $FROM_RELEASE $TO_RELEASE $APP_NAME $APP_ENV $APP_PATH
```

update-crontab.php:
```php
define('DS', DIRECTORY_SEPARATOR);

if ($argc != 6) {
        
    die("wrong call");
}

$fromRelease = (integer)$argv[1];
$toRelease = (integer)$argv[2];
$projectName = $argv[3];
$stage = $argv[4];
$projectPath = $argv[5];

require($projectPath . DS .'vendor'. DS . 'autoload.php');

use PHPCron\Cron;

$fromSignature = 'app='. $projectName .'; env='. $stage .'; rel='. $fromRelease;
$toSignature = 'app='. $projectName .'; env='. $stage .'; rel='. $toRelease;

$crontab = new Cron\Tab;
$jobs = $crontab->get();
foreach ($jobs as $index => $job) {
            
    $jobSignature = $job->getComment();
    if ($jobSignature == $fromSignature || $jobSignaature == $toSignature) {
        
        $crontab->remove($index);
    }
}

if (0 !== $toRelease) {

    $joblist = (@include $projectPath . DS .'config'. DS .'cronjobs.php');
    if (is_array($joblist)) {
                
        $signature = 'app='. $projectName .'; env='. $stage .'; rel='. $toRelease;
        foreach ($joblist as $job) {
                          
            $originalCommand = $job->getCommand();
            $finalCommand = 'cd '. $projectPath .' && export APPLICATION_ENV="'. $stage .'" && '. $originalCommand;
            $job->setCommand($finalCommand);                       
            $job->setComment($signature);
            $crontab->add($job);
        }
    }
}

$crontab->write();
```