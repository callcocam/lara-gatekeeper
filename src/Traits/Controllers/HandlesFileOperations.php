<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait HandlesFileOperations
{
    /**
     * Move um arquivo temporário (identificado pelo seu path no disco local)
     * para o disco de destino padrão (geralmente 'public').
     *
     * @param string $temporaryPath Caminho do arquivo no disco 'local' (ex: "tmp/uploads/xyz.jpg")
     * @param string $targetDirectory Diretório de destino no disco padrão (ex: "avatars")
     * @return string|null O caminho relativo ao disco de destino se sucesso, null caso contrário.
     */
    protected function moveTemporaryFile(string $temporaryPath, string $targetDirectory): ?string
    {
        $destinationDisk = config('filesystems.default'); // Ex: 'public' 

        // 1. Verificar se o arquivo temporário existe no disco de destino
        if (!Storage::disk($destinationDisk)->exists($temporaryPath)) {
            Log::warning("Arquivo temporário não encontrado em [{$destinationDisk}]: " . $temporaryPath);
            return null;
        }

        // 2. Definir o caminho de destino no disco padrão
        $filename = basename($temporaryPath);
        $targetPath = rtrim($targetDirectory, '/') . '/' . $filename;

        // 3. Mover o arquivo
        try {
            if (Storage::disk($destinationDisk)->move($temporaryPath, $targetPath)) {
                // Deletar o arquivo temporário após mover
                Storage::disk($destinationDisk)->delete($temporaryPath);
                Log::info("Arquivo movido de [{$destinationDisk}] {$temporaryPath} para [{$destinationDisk}] {$targetPath}");
                return $targetPath;
            } else {
                Log::error("Falha ao mover arquivo de [{$destinationDisk}] {$temporaryPath} para [{$destinationDisk}] {$targetPath}");
                Storage::disk($destinationDisk)->delete($temporaryPath);
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Erro ao mover arquivo {$temporaryPath}: " . $e->getMessage());
            report($e);
            return null;
        }
    }

    /**
     * Deleta um arquivo do disco especificado
     */
    protected function deleteFile(string $filePath, string $disk = null): bool
    {
        $disk = $disk ?? config('filesystems.default');

        try {
            if (Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
                Log::info("Arquivo deletado: [{$disk}] {$filePath}");
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error("Erro ao deletar arquivo {$filePath}: " . $e->getMessage());
            report($e);
            return false;
        }
    }

    /**
     * Valida se um arquivo é uma imagem válida
     */
    protected function isValidImage(string $filePath, string $disk = null): bool
    {
        $disk = $disk ?? config('filesystems.default');

        if (!Storage::disk($disk)->exists($filePath)) {
            return false;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = Storage::disk($disk)->mimeType($filePath);

        return in_array($mimeType, $allowedMimes);
    }
}
