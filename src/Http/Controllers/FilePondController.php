<?php

namespace Callcocam\LaraGatekeeper\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Para validação
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;

class FilePondController extends Controller
{
    public function load(Request $request)
    {
        // 1. Validar o parâmetro 'path'
        $relativePath = $request->query('path');

        if (!$relativePath) {
            abort(400, 'Parâmetro "path" ausente.');
        }

        // 2. Validação de Segurança (MUITO IMPORTANTE)
        //    - Impedir LFI (../)
        //    - Garantir que o path começa com um prefixo esperado (ex: 'avatars/')
        //    - Limpar o path
        
        // Use o disco configurado para os avatares (ex: 'public', 's3', etc.)
        $disk = config('filesystems.default', 'public'); // Pega do config ou default 'public'
        $safePath = Storage::disk($disk)->url($relativePath); 

        // 3. Verificar se o arquivo existe no disco
        if (!Storage::disk($disk)->exists($relativePath)) {
            abort(404, 'Arquivo não encontrado.');
        }

        // 4. Retornar a resposta do arquivo
        //    Storage::response lida com os cabeçalhos corretos (Content-Type, etc.)
        try {
            return Storage::disk($disk)->response($relativePath);
        } catch (\Exception $e) {
            Log::error('Erro ao servir arquivo via FilePond load:', ['path' => $safePath, 'error' => $e->getMessage()]);
            abort(500, 'Erro ao ler o arquivo.');
        }
    }

    // Adicione métodos para process, revert, etc. aqui depois
}
