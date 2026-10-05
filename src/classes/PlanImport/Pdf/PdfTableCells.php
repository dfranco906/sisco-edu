<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport\Pdf;

use SiscoEdu\PlanImport\PlanImportException;
use SiscoEdu\PlanImport\ProcessRunner;

/**
 * Reads ruled, unrotated PDF tables. Text is still decoded by the configured Xpdf.
 * Only ordinary page/content objects and unfiltered or Flate streams are supported.
 * Unsupported/ambiguous grids fail closed instead of guessing academic associations.
 */
final class PdfTableCells
{
    public function __construct(private readonly string $binary, private readonly ProcessRunner $runner) {}

    public function extract(string $path, int $pages, array $limits): array
    {
        $started = microtime(true);
        $pdf = (string) file_get_contents($path);
        if (preg_match('/\/ObjStm\b/', $pdf) || !preg_match_all('/(?:^|[\r\n])(\d+)\s+(\d+)\s+obj\b(.*?)\bendobj\b/s', $pdf, $matches, PREG_SET_ORDER)) $this->unsupported();
        $objects = [];
        foreach ($matches as $m) $objects[(int)$m[1]] = ['generation'=>(int)$m[2], 'body'=>trim($m[3])];
        if (!preg_match('/\/Root\s+(\d+)\s+(\d+)\s+R/', $pdf, $root) || !preg_match_all('/startxref\s+(\d+)/', $pdf, $xrefs)) $this->unsupported();
        $rootBody = $objects[(int)$root[1]]['body'] ?? '';
        if (!preg_match('/\/Pages\s+(\d+)\s+\d+\s+R/', $rootBody, $tree)) $this->unsupported();
        $pageIds = $this->pageIds($objects, (int)$tree[1]);
        if (count($pageIds) !== $pages) $this->unsupported();
        $temp = tempnam(sys_get_temp_dir(), 'sisco-cells-');
        if ($temp === false) $this->unsupported();
        $outputBytes = 0;
        $result = [];
        try {
            foreach ($pageIds as $index=>$id) {
                $body = $objects[$id]['body'];
                if (preg_match('/\/Rotate\s+(?!0\b)\d+/', $body)) $this->unsupported();
                if (!preg_match('/\/Contents\s+(\d+)\s+\d+\s+R/', $body, $content)) $this->unsupported();
                $stream = $this->stream($objects[(int)$content[1]]['body'] ?? '', (int)$limits['max_output_bytes']);
                $regions = $this->regions($stream);
                $pageRegions = [];
                foreach ($regions as $segments) {
                    $grid = $this->grid($segments);
                    if ($grid === null) continue;
                    $cells = [];
                    foreach ($grid['columns'] as $column=>$bounds) {
                        foreach ($bounds as $box) {
                            if (microtime(true)-$started >= (float)$limits['timeout_seconds']) throw new PlanImportException('La lectura de celdas excedió el tiempo máximo.', 'PARSER_TIMEOUT', 8, 408);
                            // Incremental update of a private copy: original streams/resources remain intact.
                            $pageBody = preg_replace('/\/(?:MediaBox|CropBox)\s*\[[^\]]*\]/', '', $body);
                            $rectangle = implode(' ', [$box[0]+0.15, $box[1]+0.15, $box[2]-0.15, $box[3]-0.15]);
                            $pageBody = substr(rtrim((string)$pageBody), 0, -2).' /MediaBox ['.$rectangle.'] /CropBox ['.$rectangle.'] >>';
                            $prefix = $pdf."\n";
                            $offset = strlen($prefix);
                            $update = $id.' '.$objects[$id]['generation']." obj\n".$pageBody."\nendobj\n";
                            $xrefOffset = $offset + strlen($update);
                            $update .= "xref\n".$id." 1\n".sprintf('%010d %05d n ', $offset, $objects[$id]['generation'])."\n";
                            $update .= 'trailer << /Size '.(max(array_keys($objects))+1).' /Root '.$root[1].' '.$root[2].' R /Prev '.end($xrefs[1])." >>\nstartxref\n".$xrefOffset."\n%%EOF\n";
                            if (file_put_contents($temp, $prefix.$update) === false) $this->unsupported();
                            $remaining = max(0.01, (float)$limits['timeout_seconds']-(microtime(true)-$started));
                            $run = $this->runner->run([$this->binary, '-enc','UTF-8','-table','-f',(string)($index+1),'-l',(string)($index+1),'--',$temp,'-'], $remaining, (int)$limits['max_output_bytes']);
                            if ($run['exit_code'] !== 0) $this->unsupported();
                            $outputBytes += strlen($run['stdout']);
                            if ($outputBytes > (int)$limits['max_output_bytes']) throw new PlanImportException('La salida de celdas supera el límite permitido.', 'PARSER_OUTPUT_LIMIT', 7, 422);
                            $cells[$column][] = ['box'=>$box, 'text'=>trim(str_replace("\f", '', $run['stdout']))];
                        }
                    }
                    $pageRegions[] = ['page'=>$index+1,'cells'=>$cells];
                }
                if (!$pageRegions) $this->unsupported();
                $result = array_merge($result, $pageRegions);
            }
        } finally { @unlink($temp); }
        return $result;
    }

    private function pageIds(array $objects, int $id, array $visited = []): array
    {
        if (isset($visited[$id]) || count($visited)>100) $this->unsupported();
        $visited[$id]=true;
        $body=$objects[$id]['body']??'';
        if (preg_match('/\/Type\s*\/Page\b/', $body)) return [$id];
        if (!preg_match('/\/Kids\s*\[([^\]]+)\]/s', $body, $kids)) $this->unsupported();
        preg_match_all('/(\d+)\s+\d+\s+R/', $kids[1], $refs);
        $result=[];
        foreach ($refs[1] as $ref) $result=array_merge($result,$this->pageIds($objects,(int)$ref,$visited));
        return $result;
    }

    private function stream(string $body, int $limit): string
    {
        if (!preg_match('/\bstream\r?\n(.*?)\r?\nendstream\b/s', $body, $m)) $this->unsupported();
        $stream=$m[1];
        if (preg_match('/\/Filter\b/', substr($body,0,strpos($body,'stream')))) {
            if (!preg_match('/\/Filter\s*\/FlateDecode\b/', $body)) $this->unsupported();
            $stream=@gzuncompress($stream, $limit);
            if ($stream===false) $this->unsupported();
        }
        if (strlen($stream)>$limit) $this->unsupported();
        return $stream;
    }

    private function regions(string $stream): array
    {
        // Text strings are excluded: operators inside strings must never become grid edges.
        $stream=preg_replace('/BT\b.*?\bET/s','',$stream);
        preg_match_all('/-?(?:\d*\.\d+|\d+)|(?<![A-Za-z])[A-Za-z]+\*?/', (string)$stream, $tokens);
        $matrix=[1.,0.,0.,1.,0.,0.]; $clip=null; $stack=[]; $args=[]; $point=null; $rectangle=null; $path=[]; $regions=[];
        foreach ($tokens[0] as $token) {
            if (is_numeric($token)) { $args[]=(float)$token; continue; }
            if ($token==='q') $stack[]=[$matrix,$clip];
            elseif ($token==='Q') { if (!$stack) $this->unsupported(); [$matrix,$clip]=array_pop($stack); }
            elseif ($token==='cm' && count($args)>=6) {
                [$a,$b,$c,$d,$e,$f]=array_slice($args,-6);
                [$A,$B,$C,$D,$E,$F]=$matrix;
                $matrix=[$A*$a+$C*$b,$B*$a+$D*$b,$A*$c+$C*$d,$B*$c+$D*$d,$A*$e+$C*$f+$E,$B*$e+$D*$f+$F];
            } elseif (($token==='m'||$token==='l') && count($args)>=2) {
                [$x,$y]=array_slice($args,-2);
                $next=[$matrix[0]*$x+$matrix[2]*$y+$matrix[4],$matrix[1]*$x+$matrix[3]*$y+$matrix[5]];
                if ($token==='l' && $point!==null) $path[]=[$point,$next];
                $point=$next;
            } elseif ($token==='re' && count($args)>=4) {
                [$x,$y,$w,$h]=array_slice($args,-4);
                if (abs($matrix[1])+abs($matrix[2])>0.001) $this->unsupported();
                $x1=$matrix[0]*$x+$matrix[4]; $y1=$matrix[3]*$y+$matrix[5];
                $x2=$matrix[0]*($x+$w)+$matrix[4]; $y2=$matrix[3]*($y+$h)+$matrix[5];
                $rectangle=[min($x1,$x2),min($y1,$y2),max($x1,$x2),max($y1,$y2)];
            } elseif (($token==='W'||$token==='W*') && $rectangle!==null) {
                $clip=$clip===null?$rectangle:[max($clip[0],$rectangle[0]),max($clip[1],$rectangle[1]),min($clip[2],$rectangle[2]),min($clip[3],$rectangle[3])];
            } elseif ($token==='S'||$token==='s') {
                $segments=[];
                foreach ($path as [$p,$q]) {
                    if (abs($p[0]-$q[0])<0.01) {
                        $x=$p[0]; $lo=min($p[1],$q[1]);$hi=max($p[1],$q[1]);
                        if ($clip!==null) { if ($x<$clip[0]||$x>$clip[2]) continue; $lo=max($lo,$clip[1]);$hi=min($hi,$clip[3]); }
                        if ($hi>$lo) $segments[]=['axis'=>'v','at'=>$x,'lo'=>$lo,'hi'=>$hi];
                    } elseif (abs($p[1]-$q[1])<0.01) {
                        $y=$p[1];$lo=min($p[0],$q[0]);$hi=max($p[0],$q[0]);
                        if ($clip!==null) { if ($y<$clip[1]||$y>$clip[3]) continue; $lo=max($lo,$clip[0]);$hi=min($hi,$clip[2]); }
                        if ($hi>$lo) $segments[]=['axis'=>'h','at'=>$y,'lo'=>$lo,'hi'=>$hi];
                    }
                }
                if ($segments) $regions[]=$segments;
                $path=[];$point=null;$rectangle=null;
            } elseif (in_array($token,['n','f','f*'],true)) { $path=[];$point=null;$rectangle=null; }
            $args=[];
        }
        return $regions;
    }

    private function grid(array $segments): ?array
    {
        $vertical=[];
        foreach ($segments as $s) if ($s['axis']==='v') $vertical[(string)round($s['at'],2)]=$s;
        if (count($vertical)!==9) return null; // Eight columns in the supported competence table.
        uasort($vertical,static fn(array $a,array $b):int=>$a['at']<=>$b['at']);
        $vertical=array_values($vertical);
        $top=min(array_column($vertical,'hi'));$bottom=max(array_column($vertical,'lo'));
        if ($top-$bottom<2) return null;
        $columns=[];
        for ($i=0;$i<8;$i++) {
            $left=$vertical[$i]['at'];$right=$vertical[$i+1]['at'];$middle=($left+$right)/2;
            $ys=[(string)round($top,2)=>$top,(string)round($bottom,2)=>$bottom];
            foreach ($segments as $s) if ($s['axis']==='h'&&$s['lo']<=$middle&&$s['hi']>=$middle&&$s['at']<$top-0.5&&$s['at']>$bottom+0.5) $ys[(string)round($s['at'],2)]=$s['at'];
            rsort($ys,SORT_NUMERIC);
            if (count($ys)>200) $this->unsupported();
            for ($j=0;$j<count($ys)-1;$j++) if ($ys[$j]-$ys[$j+1]>1) $columns[$i][]=[$left,$ys[$j+1],$right,$ys[$j]];
        }
        return ['columns'=>$columns];
    }

    private function unsupported(): never
    {
        throw new PlanImportException('No se pudieron verificar los límites de las celdas de este PDF. Revise el documento antes de importar; no se inferirán asociaciones.', 'PDF_TABLE_BOUNDARIES_UNSUPPORTED', 5, 422);
    }
}
