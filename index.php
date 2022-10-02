<?PHP

/**
 * Copyright 2021, 2024 5 Mode
 *
 * This file is part of Bugzilla PHP Wrapper.
 *
 * Bugzilla PHP Wrapper is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Bugzilla PHP Wrapper is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.  
 * 
 * You should have received a copy of the GNU General Public License
 * along with Bugzilla PHP Wrapper. If not, see <https://www.gnu.org/licenses/>.
 *
 * index.php
 * 
 * Bugzilla PHP Wrapper description of the file.
 *
 * @author Daniele Bonini <my25mb@aol.com>
 * @copyrights (c) 2016, 2024, 5 Mode
 */
 
 // SCRIPT_NAME 
 $f = filter_input(INPUT_GET, 'perl_script');
 if ($f === "" || $f === "/") {
   $f = "/index.cgi";
 }
 if (substr($f,0,1)!=="/") {
   $f = "/".$f;
 }
 
 // QUERY_STRING
 $s = filter_input(INPUT_SERVER, 'QUERY_STRING');
 $s = explode("perl_script=$f", $s)[1];
 // Rebuilding the $_POST parameters..
 $formData = $_POST;
 foreach($formData as $key=>$val) {
   if ($f!=="/index.cgi" && ($key==="Bugzilla_login" || $key==="Bugzilla_password" || $key ==="Bugzilla_login_token" || $key==="GoAheadAndLogIn")) {
   } else {
     $s .= "&$key=".urlencode($val); 
   }
 }
 if (substr($s,0,1) === "&") {
   $s = substr($s,1);
 }

 // SERVER VARIABLES 
 putenv("QUERY_STRING=$s");
 putenv("local_timezone=Europe/Rome");
 putenv("SERVER_SOFTWARE=nginx");
 putenv("SERVER_NAME=" . $_SERVER['SERVER_NAME']);
 putenv("REQUEST_METHOD=" . $_SERVER['REQUEST_METHOD']);
 //putenv("REMOTE_ADDR=".$_SERVER['REMOTE_ADDR']);
 // Rebuilding the rest of $_SERVER variables environment.. 
 $serverEnvs = $_SERVER;
 foreach($serverEnvs as $key=>$val) {
   if (($key !== "QUERY_STRING") && ($key !== "SERVER_SOFTWARE") && ($key !== "SERVER_NAME") && ($key !== "REQUEST_METHOD")) { 
     putenv("$key=".$val);
   }
 }

 $output = [];
 $r = exec("perl -T " . __DIR__ . $f, $output);

 if ($f==="/buglist.cgi" && array_count_values($output)['</html>']>1) {
   foreach($output as &$row) {
     if ($row === "</html>") {
       $row = "#### BLANK ####";
       break;
     } else {
       $row = "#### BLANK ####";
     }
   }
 }

 if ($f==="/for_debugging.cgi") {
   header("Content-Type: plain/html");
   echo("<html><body>");
   print_r($output);
   echo("</body></html>");
   exit(0);
   
 } else {
   $docParsing = false;
   foreach($output as $row) {
     if ($row!=="#### BLANK ####") {
       if ($row === "<!DOCTYPE html>") {
         $docParsing = true;
       }
       if ($docParsing) {
         echo($row."\n");
       } else {
//       echo("header=$row<br>");
         if (mb_strpos(strtolower($row), "set-cookie: bugzilla_login=") !== false) {
           // Parsing for the UserID on the Cookie request
           $userID = explode("=", explode(";", $row)[0])[1];
           // Creating the login cookie..
           setcookie("Bugzilla_login", $userID, time() + 999999999, "/", "bugs.5mode.com", true, true);
         } else {
           if ((mb_strpos($row, "--------- =") === false) && (mb_strpos($row, "WARNING:") === false)) {
             header($row);
           }
         }        
       }
     }
   }
 }

