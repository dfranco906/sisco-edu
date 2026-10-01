<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__,2).'/src/config/db.php';
require_once dirname(__DIR__,2).'/src/classes/ZeroUserIdentityRepair.php';
$apply=in_array('--apply',$argv,true);
if (array_diff(array_slice($argv,1),['--apply','--dry-run']) || ($apply && in_array('--dry-run',$argv,true))) {
    fwrite(STDERR,"Usage: php repair_zero_admin_identity.php [--dry-run|--apply]\n"); exit(2);
}
try {
    // This is the specific identity repair approved for this installation.
    $result=(new ZeroUserIdentityRepair((new Database())->getConnection()))->run('admin1',29,$apply);
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR,"Identity repair failed; transaction rolled back. ".$error->getMessage().PHP_EOL); exit(1);
}
