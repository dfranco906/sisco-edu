<?php
declare(strict_types=1);
function planTestHttp(PlanImportTestEnvironment $env,string $cookie,string $path,string $method='GET',mixed $body=null,array $headers=[]): array
{
    $curl=curl_init($env->url.$path);
    if(is_string($body))$headers[]='Content-Type: application/json';
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>45]);
    if($body!==null)curl_setopt($curl,CURLOPT_POSTFIELDS,$body);
    $raw=curl_exec($curl);if($raw===false)throw new RuntimeException('HTTP temporal falló: '.curl_error($curl));
    $status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);
    $json=json_decode($raw,true,128);
    return ['status'=>$status,'raw'=>$raw,'json'=>$json];
}
