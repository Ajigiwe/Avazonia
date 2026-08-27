<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
Session::start();
$secret=$_GET['secret']??'';
if($secret!=='avazonia_purge_2026' && Session::get('user_role')!=='admin'){header('Content-Type:text/plain');http_response_code(403);echo "Forbidden\n";exit;}
header('Content-Type:text/plain');
$db=db(); $driver=$db->getAttribute(PDO::ATTR_DRIVER_NAME);
$isMysql=($driver!=='sqlite');
$whereRandom = $isMysql ? "full_name REGEXP '^[a-z]{8,15}$'" : "full_name GLOB '[a-z]*' AND length(full_name) BETWEEN 8 AND 15 AND instr(full_name,' ')=0";
$confirm=isset($_GET['confirm'])&&$_GET['confirm']=='1';
$sqlCount="SELECT COUNT(*) FROM users WHERE role='customer' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL)";
try{$count=(int)$db->query($sqlCount)->fetchColumn();echo "Matched spam (all dates, random name, 0 orders, not seller): $count\n";}catch(Throwable $e){echo "Count error: ".$e->getMessage()."\n";$count=0;}
if(!$confirm){
 echo "Dry run. Sample 10:\n";
 try{foreach($db->query("SELECT id, full_name, email, created_at FROM users WHERE role='customer' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL) LIMIT 10")->fetchAll() as $r){echo " - #{$r['id']} {$r['full_name']} {$r['email']} {$r['created_at']}\n";}}catch(Throwable $e){echo $e->getMessage()."\n";}
 echo "Add &confirm=1 to delete with backup.\n";exit;
}
$backupDir=__DIR__.'/../backups/spam_purge'; if(!is_dir($backupDir)) mkdir($backupDir,0755,true);
$backupFile=$backupDir.'/spam_all_'.date('Ymd_His').'.json';
try{$rows=$db->query("SELECT * FROM users WHERE role='customer' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL)")->fetchAll(); file_put_contents($backupFile, json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); echo "Backup $backupFile (".count($rows).")\n";}catch(Throwable $e){echo "Backup fail: ".$e->getMessage()."\n";}
try{
 $ids=$db->query("SELECT id FROM users WHERE role='customer' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL)")->fetchAll(PDO::FETCH_COLUMN);
 if(empty($ids)){echo "No ids\n";exit;}
 $ph=implode(',',array_fill(0,count($ids),'?'));
 // Clean related
 foreach(['wishlist','reviews','system_logs'] as $tbl){
  try{$exists=$db->query("SELECT 1 FROM $tbl LIMIT 1");$exists->fetch();}catch(Throwable $e){continue;}
  try{$stmt=$db->prepare("DELETE FROM $tbl WHERE user_id IN ($ph)");$stmt->execute($ids);echo "Cleaned $tbl: ".$stmt->rowCount()."\n";}catch(Throwable $e){echo "Clean $tbl error: ".$e->getMessage()."\n";}
 }
 // password_resets by email
 try{$emails=$db->query("SELECT email FROM users WHERE id IN (".implode(',',array_fill(0,count($ids),'?')).")")->fetchAll(PDO::FETCH_COLUMN); // re-use ids
  // need to re-prepare with ids
  $emails2=$db->prepare("SELECT email FROM users WHERE id IN ($ph)"); $emails2->execute($ids); $emails=$emails2->fetchAll(PDO::FETCH_COLUMN);
  if(!empty($emails)){ $ph2=implode(',',array_fill(0,count($emails),'?')); $stmt=$db->prepare("DELETE FROM password_resets WHERE email IN ($ph2)"); $stmt->execute($emails); echo "Cleaned password_resets: ".$stmt->rowCount()."\n"; }
 }catch(Throwable $e){echo "password_resets error: ".$e->getMessage()."\n";}
 $stmt=$db->prepare("DELETE FROM users WHERE id IN ($ph)"); $stmt->execute($ids); echo "Deleted users: ".$stmt->rowCount()."\n";
}catch(Throwable $e){echo "Delete error: ".$e->getMessage()."\n";}
foreach(['users','sellers','stores','products','orders'] as $t){try{$c=$db->query("SELECT COUNT(*) FROM $t")->fetchColumn();echo "$t: $c\n";}catch(Throwable $e){echo "$t: ERROR\n";}}
echo "Purge complete. Delete this file after.\n";
