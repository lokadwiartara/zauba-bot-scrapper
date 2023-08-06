<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/configuration.php');
require_once(__DIR__ . '/db-connection.php');
require_once(__DIR__ . '/hQuerylib/hquery.php');

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverAction;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverMouse;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Chrome\ChromeOptions;

$options = new ChromeOptions();
$options->addArguments(array(
  '--start-maximized',
));

$caps = DesiredCapabilities::chrome();
$caps->setCapability(ChromeOptions::CAPABILITY, $options);

$host = 'http://localhost:4444/wd/hub'; // this is the default
$driver = RemoteWebDriver::create($host, $caps);

$driver->get("https://www.zauba.com/user/login");

$elemname = $driver->findElement(WebDriverBy::id("edit-name"));

if($elemname){
	$elemname->sendKeys($username);
}

$elempass = $driver->findElement(WebDriverBy::id("edit-pass"));

if($elempass){
	$elempass->sendKeys($password);
}

sleep(180);

for($x=$start_page;$x<=$end_page;$x++){
	$driver->get("https://www.zauba.com/import-".$search_keyword."/p-".$x."-hs-code.html");
	echo "==============================================================="."\n";
	echo "Grab Keyword ".$search_keyword." Halaman ".$x."\n";
	echo "==============================================================="."\n";

	$pageHTML = hQuery::fromHTML($driver->getPageSource());

	$patternA = 'table > tbody > tr > td > a';
	$anchor = $pageHTML->find($patternA);
	if(count($anchor) > 0){
		$c = 0;
		$array_anchor = array();
		foreach($anchor as $pos => $a) {	
			$array_anchor[$c] = $a->text();
			$c++;
		}		
	}
	else{
		exit();
	}

	$patternDesc = 'table > tbody > tr > td.desc';
	$description = $pageHTML->find($patternDesc);
	if(count($description) > 0){
		$c = 0;
		$sql = ""; 
		foreach($description as $d => $desc){
			echo $array_anchor[$c]." : ".$desc->text();

			/* SQL query build */
			$hscode = $array_anchor[$c];
			$descript = $desc->text();
			$sql = "INSERT INTO `tbl_content` (`id_content`, `hs_code`, `description`, `search_keyword`, `page_position`) VALUES (NULL, '$hscode', '$descript', '$search_keyword', '$x');";

			if ($conn->query($sql) === TRUE) {
				echo " \t...INSERTED\n";				
			}
			else{
				echo "Error: " . $sql . "<br>" . $conn->error;
			}

			$c++;
		}	
	}
	else{
		echo "grab halaman ".$x." gagal!!!! Coba lagi, mulai dari start page halaman ini ... ";
		exit();
	}

	echo "\n";
	$rand_second = 	rand(1,4);
	echo "grab halaman ".$x." selesai, halaman selanjutnya akan digrab dalam ".$rand_second." detik .... "."\n\n";
	sleep($rand_second);
}

$driver->quit();

// $file = fopen('syn-id-y.txt','a');
// fwrite($file,$driver->getPageSource());