<?php
/*
 * ajaxv21.php
 * 
 * Copyright 2021 Jim Richardson <jim@noideersoftware.co.uk>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 *  unlike previous versions of ajax v21 will exec modules rather than the code being written into the file
 *  reasons are:-
 *  1. increased securtiy early versions have a security hole !
 *  2. code will be easier to maintain as you can edit the exec and NOT break any other ajax calls
 *  3. will work fully with the command line as well as HTTP requests
 *  4. better logging 
 */
require_once 'includes/master.inc.php';
include 'functions.php';
require DOC_ROOT. '/xpaw/SourceQuery/bootstrap.php'; // load xpaw
	use xPaw\SourceQuery\SourceQuery;
	define( 'SQ_TIMEOUT',     $settings['SQ_TIMEOUT'] );
	define( 'SQ_ENGINE',      SourceQuery::SOURCE );
	define( 'LOG',	'logs/ajax.log');
	define ('CR',PHP_EOL);
	define ('borders',array('horizontal' => '─', 'vertical' => '│', 'intersection' => '┼','left' =>'├','right' => '┤','left_top' => '┌','right_top'=>'┐','left_bottom'=>'└','right_bottom'=>'┘','top_intersection'=>'┬'));
	$build = "9102-3606648806";
	$version = 2.101;
	$cmds = startup();
	//print_r($argv);
	//echo 'returned $cmds '.cr,printr($cmds).cr;
	if ($cmds['valid'] === false) {
		//die( 'invalid API entry point');
	}
	if (!isset($cmds['action']) || empty($cmds['action'])){
		die('I don\'t know what you mean'.cr);
	}
	switch ($cmds['action']) {
        case 'version' :
           echo 'Ajax v'.$version.' '.$build.' Copyright Noideer Software '.$settings['start_year'].' - '.date('Y').cr;
            break;
        case 'help' :
			if(empty($cmds['topic'])) {$cmds['topic'] = null;} 
          die(help($cmds['topic']));
          break;
        case 'scanlog':
             //printr($cmds);
             $exe = './scanlog.php -s'.$cmds['server'];
             if(isset($cmds['silent'])) { $exe.=' '.$cmds['silent'];}
			exec($exe,$content,$ret_val);
			//printr($content);
			switch ($cmds['output']) {
				case 'json':
					echo json_encode($content);
					break;
				case 'xml':
						echo  arrayToXML($content, new SimpleXMLElement('<scanlog/>'), 'output');
						break;
				case 'text':	
					foreach ($content as $line) {echo $line.cr;}
					break;
				 default:
                     echo "i is not equal to 0, 1 or 2";	
				}
			break; 
          }

	function startup() {
		// get supplied options
                
                $output ='';
                $cmds = array();
               
		if (is_cli()) {
			global $argv; 
			//$method = $argv";
                        define ('cr',PHP_EOL);
			 //echo 'raw argv '.print_r($argv,true);
                $argv = array_map('strtolower',$argv);
                //echo 'lowered argv '.printr($argv,true);
                
                      //  echo 'in cli'.CR;
			$shortopts ="a:A:s:S:d::D::v::V::h::H::t:T:o:O:";
			$longopts[] = "debug::";
			$longopts[] = "DEBUG::";
			$longopts[] = "action:";
			$longopts[] = "server:";
			$longopts[] = "version";
			$longopts[] = "VERSION";
			$longopts[] = "help::";
			$longopts[] = "HELP::";
			$longopts[] = "topic:";
			$longopts[] = "TOPIC:";
			$longopts[] = "silent::";
			$options = getopt($shortopts,$longopts);
			//echo 'options as is  '.printr($options,true).cr;
			
			$options = array_change_key_case($options,CASE_LOWER);
			$options = array_map('strtolower',$options);
			//echo 'case changed '.printr($options);
			// running from the command line
			//echo 'options '.printr($options,true).CR;
			$method = 'cli';
			if (!isset($argv['action'])) { 
				$cmds = convert_to_argv($argv,"",true);
			}
			if(isset($options['debug'])) {
				$cmds['debug']= true;
				//define('debug',true); // maybe not define cmds later ?
			}
            if(isset($options['v']) or isset($options['version'])) {$cmds['action'] ='version';}
            if(isset($options['silent'])) {$cmds['silent'] ='--silent';}
            if(isset($options['o'])) {
				$cmds['output'] = $options['o'];
				} 
				else {
					$cmds['output'] ='text';
					}
				
            if(isset($options['s'])) {
				$cmds['server'] = $options['s'];
			}
			else {
				$cmds['server'] = 'all';
			}
            if(isset($options['help'])||isset($options['h'])){
				$cmds['action'] ='help';
			}
				if(!empty($options['topic'])) {
					$cmds['topic'] = $options['topic'];
				}
				elseif (!empty($options['t'])) {
					$cmds['topic'] = $options['t'];
				}
				else {
					//$cmds['helpopt'] = null;
				}
			//}
            if(isset($options['a'])) {$cmds['action'] = $options['a'];} 
            //switch ($options) {
            $cmds['valid'] = true; // we trust the console
            //return $cmds;
            
		}

		else {
			// run via url
                        //echo 'run by url<br>';
                        define ('cr','<br>');
			if(!empty($_POST)) {
				// this is the norm
                               //echo 'in post<br>';
                               // echo print_r($_POST,true).'<br>';
				$cmds =convert_to_argv($_POST,"",true);
				$method = '$_POST';
				//define ('cr','<br>');
			}
			if(!empty($_GET)) {
				// not the best but added
                                 //echo 'in get<br>';
                                 //echo print_r($_GET,true).'<br>';
				if (isset($cmds)) {
					// we have details from $_POST
                                        //echo 'merge get<br>';
					$cmds = array_merge($cmds,convert_to_argv($_GET,"",true));
					$method .='/$_GET';
				}
				else {
					// no $_POST backwards compat
                    $cmds = convert_to_argv($_GET,"",true);
					$method = '$_GET';
					
				}
			}
			
			 if (isset($_SERVER['HTTP_PHPGSM_AUTH']) and $_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {
                        $cmds['valid'] = true;
                        //echo 'auth on'.cr;
                        }
                else {
					$cmds['valid'] = false;
				}        
		} 
		
		//$output .= "method = $method".cr;
          //       foreach ($cmds as $k => $v) {
            //         $output .= "[$k]=>$v".cr;
              //   }
                // $output .= "$method finished".cr;
		        //echo printr($cmds,true);
                //if(isset($_SERVER)) {
                 //$output .= printr($_SERVER,true);
                //}
                //echo print_r($_SERVER,true);
                
              if(isset($cmds['debug']) and $cmds['valid'] ==1) {
                  echo $output;
                 
			}
			 //die('x1');
			 //printr($cmds);
            return $cmds; 
	}
	
	function help($option=null) {
		// display help
		if (is_cli()) {
			//echo "option = $option".cr;
			global $version,$build,$settings;
			$table = new Table(CONSOLE_TABLE_ALIGN_LEFT, borders, 2, null,true,CONSOLE_TABLE_ALIGN_CENTER);
			$year = $settings['start_year'];
			$date = date('Y');
			$cc = new Color();
			$option1 = $cc->convert("%cOption%n");
			$use = $cc->convert("%cUse%n");
			$notes = $cc->convert("%cNotes%n");
			echo $cc->convert("%MAjax v$version $build Copyright Noideer Software $year - $date%n").cr;
			if (is_null($option) ){ 
			//echo cr;
			$table->setHeaders( array ($option1,$use,$notes));
			$table->addRow(array(' --help' ,'get help','display help on a subject e.g \'--help --topic action\''));
			$table->addRow(array('-a, --action' ,'send action','major option must be set'));
		}
		else {
			switch ($option) {
				case 'action':
				case 'a':
				echo "help for '-a' & '--action'".cr;
				$table->setHeaders( array ($option1,$use,$notes));
				$table->addRow(array(' game_detail' ,'returns JSON array of currently running servers','-s'));
				$table->addRow(array(' game_detail#1' ,'returns xml array of currently running servers','loads of options here, where do I begin ?'));
				$table->addRow(array(' scanlog' ,'scans server logs','-x'));
				break;
				default:
				echo "no help for $option".cr;
				exit;
			}
			
		}
		echo $table->getTable().cr;
	}
	else {
		echo 'no help available'.cr;
	}
	}
	
	function arrayToXML($array, SimpleXMLElement $xml, $child_name)
{
    foreach ($array as $k => $v) {
        if(is_array($v)) {
            (is_int($k)) ? $this->arrayToXML($v, $xml->addChild($child_name), $v) : $this->arrayToXML($v, $xml->addChild(strtolower($k)), $child_name);
        } else {
            (is_int($k)) ? $xml->addChild($child_name, $v) : $xml->addChild(strtolower($k), $v);
        }
    }

    return $xml->asXML();
}
?>
