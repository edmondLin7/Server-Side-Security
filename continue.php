<?php
  require_once 'login.php';
  $conn = new mysqli($hn, $un, $pw, $db);
  if ($conn->connect_error) die(mysql_fatal_error());
  session_start();

   if ($_SESSION['check'] != hash('sha256', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT']))
   {
      different_user();
   }

   if (!isset($_SESSION['initiated'])) {
     session_regenerate_id();
     $_SESSION['initiated'] = 1;
   }

   if(isset($_SESSION['username']))
   {
     echo <<<_END
       <h1>Translator</h1>
       <p1>Input a english word in the dictionary to output</p1><br><br>
       </head><body><form method='post' action='continue.php' enctype='multipart/form-data'>
       Please enter a english word <input type="text" name="eng"><br>
       <input type="submit" value="Access files / Submit file"> <br><br>
       </pre></form>
       _END;
       if (isset($_POST['eng']))
       {
         $eng_trans = sanitizeString($_POST['eng']);
         $san_eng = sanitizeMySQL($conn, $eng_trans);
         $username = $_SESSION['username'];
         $query = "SELECT * FROM usertable WHERE email = '$username'";
         $result = $conn->query($query);
         $rows = $result->num_rows;
         $eng = "";
         $other = "";
         $count = 0;
         if(!$result) die ("Error has occured try again");
         for ($j = 0 ; $j < $rows; ++$j)
         {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            if($count == 0)
            {
              echo "list of eng words to translate, separated by space " . $row[1];
              $eng = $eng . $row[1];
              $other =  $other . $row[2];
              $count++;
            }
          }
          $engvalues = explode(" ", $eng);
          $other = explode(" ", $other);
          $found = false;
          $locate_trans = 0;
          for($i = 0; $i < count($engvalues); $i++)
          {
            $engvalues[$i] = trim($engvalues[$i]);
          }
          for($i = 0; $i < count($engvalues); $i++)
          {
            if(strcmp($engvalues[$i], $san_eng) == 0)
            {
              $locate_trans = $i;
              $found = true;
            }
          }
          if($found == true)
          {
             echo "<br> Translation is " . $other[$locate_trans];
          }
          else
          {
            echo "<br> translate cannot be found " . $san_eng;
          }
          $result->close();
        }
     }
     else {
        echo "Pleas authethicate";
        die("<p><a href = translator.php> Click here to continue </a></p>");
     }
     $conn->close();
     
     echo <<<_END
       </head><body><form method='post' action='continue.php' enctype='multipart/form-data'>
       <input type="submit" name="logout" value="Log Out Button"> <br><br>
       </pre></form>
       _END;
     if(isset($_POST['logout']))
     {
        destroy_session_and_data();
        die("<p><a href = lame.php> Click here to continue log in again</a></p>");
     }

     function destroy_session_and_data(){
         $_SESSION = array();
         setcookie(session_name(), '', time() - 2592000, '/');
         session_destroy();
       }

       function sanitizeMySQL($connection, $var) {
         $var = $connection->real_escape_string($var);
         $var = sanitizeString($var);
         return $var;
       }

      function sanitizeString($var) {
         $var = stripslashes($var);
         $var = strip_tags($var);
         $var = htmlentities($var);
         return $var;
       }

       function different_user()
       {
        destroy_session_and_data();
        echo "Please log in again";
        die("<p><a href = lame.php> Click here to continue </a></p>");
       }

     function mysql_fatal_error()
     {
       echo <<< _END
         We are sorry, but it was not possible to complete
         the requested task. Please click the back button on your browser
         and try again.
       _END;
     }
?>
