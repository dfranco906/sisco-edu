<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

final class PlanImportStaging
{
    private string $root;
    public function __construct(private readonly array $config)
    {
        $path = $config['storage_path'];
        $this->rejectLinks($path);
        if (!is_dir($path) && !@mkdir($path, 0700, true)) $this->fail('No se pudo preparar el almacenamiento privado.', 'STORAGE_ERROR', 503);
        $root = realpath($path);
        $web = realpath($config['document_root']);
        if (!$root || !$web || str_starts_with($this->normalized($root), $this->normalized($web))) $this->fail('El almacenamiento debe estar fuera del directorio público.', 'STORAGE_ERROR', 503);
        $this->root = $root;
    }

    public function create(string $source, int $owner, int $assignment, int $year, string $name, bool $upload = false): string
    {
        if ($owner < 1 || $assignment < 1 || $year < 2000 || $year > 2100) $this->fail('Contexto de importación inválido.', 'INVALID_CONTEXT');
        return $this->locked(function () use ($source, $owner, $assignment, $year, $name, $upload): string {
            $token = bin2hex(random_bytes(32));
            $dir = $this->root.DIRECTORY_SEPARATOR.$token;
            if (!mkdir($dir, 0700)) $this->fail('No se pudo preparar la importación.', 'STORAGE_ERROR', 503);
            try {
                $target = $dir.'/source.pdf';
                if (is_link($source) || !is_file($source) || ($upload ? !move_uploaded_file($source, $target) : !copy($source, $target))) $this->fail('No se pudo guardar el PDF.', 'STORAGE_ERROR');
                @chmod($target, 0600);
                $this->write($dir, [
                    'owner'=>$owner, 'assignment'=>$assignment, 'year'=>$year,
                    'sha256'=>hash_file('sha256', $target), 'filename'=>basename(str_replace('\\', '/', $name)),
                    'expires'=>time()+(int)$this->config['limits']['staging_ttl_seconds'],
                    'state'=>'preview', 'plan'=>null, 'decisions'=>[], 'programming'=>[],
                ]);
                return $token;
            } catch (\Throwable $e) { $this->remove($dir); throw $e; }
        });
    }

    public function read(string $token, int $owner): array
    {
        return $this->withToken($token, $owner, static fn(array &$state, string $pdf): array => $state);
    }

    // Mantiene exclusión mutua durante leer/actualizar/confirmar. El callback no
    // debe llamar otro método de staging mientras conserva el bloqueo.
    public function withToken(string $token, int $owner, callable $callback, bool $write = false): mixed
    {
        return $this->locked(function () use ($token, $owner, $callback, $write): mixed {
            $dir = $this->directory($token);
            $state = $this->state($dir);
            if ($state['owner'] !== $owner) $this->fail('Importación no disponible para este usuario.', 'TOKEN_FORBIDDEN', 403);
            if ($state['expires'] <= time()) $this->fail('La importación venció. Vuelva a cargar el PDF.', 'TOKEN_EXPIRED', 410);
            if ($state['state'] !== 'preview') $this->fail('La importación ya fue consumida.', 'TOKEN_CONSUMED', 409);
            $result = $callback($state, $dir.'/source.pdf');
            if ($write) $this->write($dir, $state);
            return $result;
        });
    }

    public function delete(string $token, int $owner): void
    {
        $this->locked(function () use ($token, $owner): void {
            $dir = $this->directory($token);
            $state = $this->state($dir);
            if ($state['owner'] !== $owner) $this->fail('Importación no disponible para este usuario.', 'TOKEN_FORBIDDEN', 403);
            $this->remove($dir);
        });
    }

    public function cleanup(bool $dryRun = true): array
    {
        return $this->locked(function () use ($dryRun): array {
            $expired = [];
            foreach (scandir($this->root) ?: [] as $token) {
                if (!preg_match('/^[a-f0-9]{64}$/D', $token)) continue;
                try { $dir = $this->directory($token); $state = $this->state($dir); }
                catch (PlanImportException) { continue; }
                if ($state['expires'] > time()) continue;
                $expired[] = $token;
                if (!$dryRun) $this->remove($dir);
            }
            return $expired;
        });
    }

    private function state(string $dir): array
    {
        $path = $dir.'/state.json';
        $this->rejectLinks($path);
        if (!is_file($path) || filesize($path) > 2*(int)$this->config['limits']['max_preview_json_bytes']) $this->fail('Estado temporal inválido.', 'INVALID_STAGING_JSON');
        try { $state = json_decode((string)file_get_contents($path), true, 128, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { $this->fail('Estado temporal inválido.', 'INVALID_STAGING_JSON'); }
        if (!is_array($state) || !is_int($state['owner'] ?? null) || !is_int($state['expires'] ?? null) || !isset($state['state'])) $this->fail('Estado temporal inválido.', 'INVALID_STAGING_JSON');
        return $state;
    }

    private function write(string $dir, array $state): void
    {
        $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($json) > 2*(int)$this->config['limits']['max_preview_json_bytes']) $this->fail('El preview supera el tamaño permitido.', 'PREVIEW_TOO_LARGE', 413);
        $tmp = $dir.'/state-'.bin2hex(random_bytes(8)).'.tmp';
        $this->rejectLinks($dir.'/state.json');
        try {
            if (file_put_contents($tmp, $json, LOCK_EX) !== strlen($json)) $this->fail('No se pudo guardar el preview.', 'STORAGE_ERROR', 503);
            @chmod($tmp, 0600);
            if (!rename($tmp, $dir.'/state.json')) $this->fail('No se pudo guardar el preview.', 'STORAGE_ERROR', 503);
        } finally { if (is_file($tmp) && !is_link($tmp)) unlink($tmp); }
    }

    private function directory(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) $this->fail('Token de importación inválido.', 'INVALID_TOKEN');
        $path = $this->root.DIRECTORY_SEPARATOR.$token;
        $this->rejectLinks($path);
        if (!is_dir($path) || realpath($path) !== $path) $this->fail('Importación no encontrada.', 'TOKEN_NOT_FOUND', 404);
        return $path;
    }

    private function remove(string $dir): void
    {
        // Sólo archivos conocidos en una carpeta token resuelta; nunca recursivo.
        if (dirname($dir) !== $this->root || !preg_match('/^[a-f0-9]{64}$/D', basename($dir))) $this->fail('Ruta temporal inválida.', 'INVALID_TOKEN');
        $files = array_values(array_diff(scandir($dir) ?: [], ['.', '..']));
        foreach ($files as $file) {
            if (!in_array($file, ['state.json','source.pdf'], true) && !preg_match('/^state-[a-f0-9]{16}\.tmp$/D', $file)) $this->fail('Archivo temporal inesperado.', 'STORAGE_ERROR');
            $this->rejectLinks($dir.'/'.$file);
            if (!is_file($dir.'/'.$file)) $this->fail('Archivo temporal inválido.', 'STORAGE_ERROR');
        }
        foreach ($files as $file) if (!unlink($dir.'/'.$file)) $this->fail('No se pudo limpiar el temporal.', 'STORAGE_ERROR');
        if (!rmdir($dir)) $this->fail('No se pudo limpiar el temporal.', 'STORAGE_ERROR');
    }

    private function locked(callable $callback): mixed
    {
        $path = $this->root.'/.lock';
        $this->rejectLinks($path);
        $handle = fopen($path, 'c+b');
        if (!$handle) $this->fail('No se pudo bloquear el almacenamiento.', 'STORAGE_ERROR', 503);
        try {
            if (!flock($handle, LOCK_EX)) $this->fail('No se pudo bloquear el almacenamiento.', 'STORAGE_ERROR', 503);
            return $callback();
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    private function rejectLinks(string $path): void
    {
        for ($current = $path; $current !== dirname($current); $current = dirname($current)) {
            if (is_link($current)) $this->fail('No se permiten enlaces en el almacenamiento.', 'STORAGE_SYMLINK');
        }
    }
    private function normalized(string $path): string { return strtolower(rtrim(str_replace('\\', '/', $path), '/')).'/'; }
    private function fail(string $message, string $type, int $http = 422): never { throw new PlanImportException($message, $type, 6, $http); }
}
