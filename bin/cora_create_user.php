<?php
$CORA_DIR = dirname(__FILE__) . "/../";
require_once( $CORA_DIR . "lib/globals.php" );
require_once( $CORA_DIR . "lib/connect.php" );
$dbi = new DBInterface(DB_SERVER, DB_USER, DB_PASSWORD, MAIN_DB);

$notwin = (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN');

$options = getopt("u:p:ah");
if (array_key_exists("h", $options)) {
?>

Create a new CorA user.

    Usage:
    <?php echo $argv[0]; ?> -u <username> -p <password> [-a]

    If -a is given, user will be given administrator rights.

<?php
    exit;
}

if (array_key_exists("u", $options)) {
  $user = $options["u"];
}
else {
  echo "Username: ";
  $user = rtrim(fgets(STDIN), PHP_EOL);
}
if ($dbi->getUserByName($user)) {
  echo "User '${user}' already exists.", PHP_EOL;
  exit(1);
}
if (!$user) {
  echo "Username mustn't be empty.", PHP_EOL;
  exit(1);
}

if (array_key_exists("p", $options)) {
  $pw = $options["p"];
}
else {
  echo "Password: ";
  if ($notwin) { system('stty -echo'); }
  $pw = rtrim(fgets(STDIN), PHP_EOL);
  if ($notwin) { system('stty echo'); echo PHP_EOL; }
}
if (!$pw) {
  echo "Password mustn't be empty.", PHP_EOL;
  exit(1);
}

if (array_key_exists("a", $options)) {
  $admin = true;
}
else if (array_key_exists("u", $options) && array_key_exists("p", $options)) {
  $admin = false;
}
else {
  $char = "";
  while ($char !== 'y' && $char !== 'n') {
    echo "Give administrator rights? (y/n) ";
    $char = strtolower(fgetc(STDIN));
    echo "", PHP_EOL;
  }
  $admin = ($char === 'y');
}

$status = $dbi->createUser($user, $pw, $admin);

if ($status == 1) {
  $wwo = ($admin ? "with" : "without");
  echo "Successfully created user '${user}' ${wwo} administrator rights.", PHP_EOL;
}
else {
  echo "Error creating user '${user}'.", PHP_EOL;
  exit(1);
}

?>
