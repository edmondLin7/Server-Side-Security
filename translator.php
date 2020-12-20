<?php
  require_once 'login.php';
  $conn = new mysqli($hn, $un, $pw, $db);
  if ($conn->connect_error) die(mysql_fatal_error());
  echo <<<_END
  <h1>ACCOUNT CREATION</h1>
  <p1>Enter a valid email as username, and password for account creation </p1><br><br>
  </head><body><form method='post' action='translator.php' enctype='multipart/form-data'>
  Email <input type="text" name="email"> <br><br>
  Pass <input type="password" name="pass"> <br><br>
  <input type="submit" value="SUBMIT"> <br><br>
  </pre></form>
  _END;
    if (isset($_POST['email']) && isset($_POST['pass']))
    {
        if(strpos($_POST['email'], "@") == false)
        {
          die ("Account not created, enter a valid email to become the username @....com reload page");
        }
        $user = sanitizeString($_POST['email']);
        $pass = sanitizeString($_POST['pass']);
        $username = sanitizeMySQL($conn, $user);
        $password = sanitizeMySQL($conn, $pass);
        $salting = explode("@", $username, 2);
        $salt = $salting[0];
        $saltedPass = $salt . $password;
        $hashedPass = hash('ripemd128', $saltedPass);
        $query = "SELECT * FROM cred";
        $result = $conn->query($query);
        if(!$result) die(mysql_fatal_error());
        $rows = $result->num_rows;
        $foundUser = false;
        for ($j = 0 ; $j < $rows ; ++$j)
        {
          $result->data_seek($j);
          $row = $result->fetch_array(MYSQLI_NUM);
          if($row[0] === $username)
          {
            $foundUser = true;
          }
        }
        if($foundUser == true)
        {
          die ("Email username is already taken, try with another unique email");
        }
        $placeholder = $conn->prepare('INSERT INTO cred VALUES(?,?,?)');
        $placeholder->bind_param("sss", $username, $hashedPass, $salt);
        $placeholder->execute();
        if($placeholder->affected_rows == 0)
        {
          echo mysql_fatal_error();
        }
        echo "hello";
        $placeholder->close();
      }
      //FileName (Optional) <input type="text" name="filename"><br>

      echo <<<_END
        <h1>ACCOUNT LOGIN</h1>
        <p1>Log in to translate. (Mandatory) txt file slot for dictionary </p1><br><br>
        </head><body><form method='post' action='translator.php' enctype='multipart/form-data'>
        Please enter a valid email <input type="email" name="emailinfo"><br>
        Password <input type="password" name="password"><br>
        Select .txt File: (Mandatory)
        <input type ='file' name='fileupload' size='30'> <br>
        <input type="submit" value="Access files / Submit file"> <br><br>
        </pre></form>
        _END;
        if (isset($_POST['emailinfo']) && isset($_POST['password']))
        {
          $user = sanitizeString($_POST['emailinfo']);
          $pass = sanitizeString($_POST['password']);
          $username = sanitizeMySQL($conn, $user);
          $password = sanitizeMySQL($conn, $pass);
          $salting = explode("@", $username, 2);
          $salt = $salting[0];
          $saltedPass = $salt . $password;
          $hashedPass = hash('ripemd128', $saltedPass);
          $query = "SELECT * FROM cred";
          $result = $conn->query($query);
          if(!$result) die(mysql_fatal_error());
          $rows = $result->num_rows;
          $foundUser = false;
          // finds if username / password exists, if yes display, and/or upload data
          for ($j = 0 ; $j < $rows ; ++$j)
          {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            if($row[0] === $username && $row[1] === $hashedPass)
            {
              $foundUser = true;
            }
          }
          if($foundUser == true)
          {
            if (is_uploaded_file($_FILES['fileupload']['tmp_name']))
            {
                $file_name = sanitizeString($_FILES['fileupload']['tmp_name']);
                $content = file_get_contents($file_name);
                $content = sanitizeString($content);
                $content = sanitizeMySQL($conn, $content);
                if($_FILES['fileupload']['type'] == 'text/plain')
                {
                  if(strlen($content) == null)
                  {
                    die ("Empty text file");
                  }
                  $fh = fopen("$file_name", 'r') or die ("Can not to open file");
                  $i = 0;
                  $english = "";
                  $trans = "";
                  if (flock($fh, LOCK_EX))
                  {
                    while(!feof($fh))
                    {
                      $i++;
                      $line = fgets($fh);
                      $line = rtrim($line);
                      $line = ltrim($line);
                      if($i % 2 == 1)
                      {
                        $english = $english . " " . $line;
                      }
                      else {
                        $trans = $trans = $trans . " ". $line;
                      }
                    }
                  flock($fh, LOCK_UN);
                  $placeholder = $conn->prepare('INSERT INTO usertable VALUES(?,?,?)');
                  $placeholder->bind_param("sss", $username, $english, $trans);
                  $placeholder->execute();
                  if($placeholder->affected_rows == 0)
                  {
                    echo mysql_fatal_error();
                  }
                  $placeholder->close();
                }
              }
              else {
                die("Please enter a text file");
              }
            }
            session_start();
            $_SESSION['username'] = $username;
            $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
            echo "You are now logged in";
            die("<p><a href = continue.php> Click here to continue </a></p>");
          }
          else {
            die ("Not valid credentials. Please try again, or make an account");
          }
          $result->close();
        }
        $conn->close();

  function destroy_session_and_data(){
      session_start();$_SESSION = array();
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

  function mysql_fatal_error()
  {
    echo <<< _END
      We are sorry, but it was not possible to complete
      the requested task. Please click the back button on your browser
      and try again.
    _END;
  }
?>
