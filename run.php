<?php
#!/usr/bin/env php
# app/console

require_once "vendor/autoload.php";

use Ingress\Command\GreetCommand;
use Symfony\Component\Console\Application;
use Ingress\Crawl;

//$application = new Application();
//$application->add(new GreetCommand);
//$application->run();

$crawler = new Crawl();
$crawler->login();