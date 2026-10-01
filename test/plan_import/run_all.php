<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__,2).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\ProcessRunner;
$groups=['unit','migration','cli','http'];$tests=[];foreach($groups as $group)$tests=array_merge($tests,glob(__DIR__.'/'.$group.'/*Test.php')?:[]);sort($tests,SORT_STRING);$tests[]=dirname(__DIR__).'/plan_parser_poc/run.php';$runner=new ProcessRunner();$failed=0;
foreach($tests as $test){try{$result=$runner->run([PHP_BINARY,$test],90,5*1024*1024);echo $result['stdout'];if($result['exit_code']!==0){$failed++;fwrite(STDERR,basename($test).': '.$result['stderr']);}}catch(Throwable $e){$failed++;fwrite(STDERR,basename($test).': '.$e->getMessage().PHP_EOL);}}
echo 'PLAN IMPORT RUN ALL: '.(count($tests)-$failed).'/'.count($tests).' PASS; '.$failed.' FAIL.'.PHP_EOL;exit($failed?1:0);
