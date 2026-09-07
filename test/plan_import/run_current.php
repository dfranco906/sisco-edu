<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__,2).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\ProcessRunner;
$tests=array_merge(glob(__DIR__.'/unit/*Test.php')?:[],glob(__DIR__.'/migration/*Test.php')?:[],glob(__DIR__.'/cli/*Test.php')?:[],glob(__DIR__.'/http/*Test.php')?:[]);
$runner=new ProcessRunner();$failed=0;
foreach($tests as $test){
    try{$result=$runner->run([PHP_BINARY,$test],60,2*1024*1024);echo$result['stdout'];if($result['exit_code']!==0){$failed++;fwrite(STDERR,basename($test).': '.$result['stderr']);}}
    catch(Throwable $e){$failed++;fwrite(STDERR,basename($test).': '.$e->getMessage().PHP_EOL);}
}
echo 'RESUMEN: '.(count($tests)-$failed).'/'.count($tests).' PASS; '.$failed.' FAIL. Alcance T01-T15; no incluye confirmación ni E2E.'.PHP_EOL;
exit($failed?1:0);
