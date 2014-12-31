<?php
require_once 'Db.php';
$GMAIL_HOST="imap.gmail.com";
$GMAIL_PORT=993;
$SSL=true;
//$GMAIL_USERNAME="your-username";
//$GMAIL_PASSWORD="your-password";
$GMAIL_USERNAME="maulik@savitriya.com";
$GMAIL_PASSWORD="lnovocorei3";
Db::config( 'driver',   'mysql' );
Db::config( 'host',     'localhost' );
Db::config( 'database', 'scanmails' );
Db::config( 'user',     'root' );
Db::config( 'password', 'mysqlpim' );
