<?php

namespace App\Http\Controllers;

use App\Contracts\ClamScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UploadController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        '.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png', '.gif', '.webp', '.xlsx', '.xls', '.csv', '.txt',
    ];

    private const MIME_SIGNATURES = [
        '.pdf' => ['25504446'],
        '.jpg' => ['FFD8FF'],
        '.jpeg' => ['FFD8FF'],
        '.png' => ['89504E47'],
        '.gif' => ['47494638'],
    ];

    public function upload(Request $request, ClamScanner $scanner)
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
            'filename' => ['required', 'string'],
        ]);

        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        $ext = '.'.ltrim($ext, '.');

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => 'File type not allowed']);
        }

        $buffer = base64_decode($data['file'], true);
        if ($buffer === false || strlen($buffer) < 4) {
            throw ValidationException::withMessages(['file' => 'File is empty or invalid base64']);
        }

        if (strlen($buffer) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'File too large (max 10MB)']);
        }

        if (isset(self::MIME_SIGNATURES[$ext])) {
            $magic = strtoupper(bin2hex(substr($buffer, 0, 4)));
            $matches = false;
            foreach (self::MIME_SIGNATURES[$ext] as $signature) {
                if (str_starts_with($magic, $signature)) {
                    $matches = true;
                    break;
                }
            }
            if (! $matches) {
                throw ValidationException::withMessages(['file' => 'File content does not match extension']);
            }
        }

        if ($scanner->enabled()) {
            try {
                $scanner->scanOrFail($buffer);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages([
                    'file' => $e->getMessage() === 'File failed the antivirus scan'
                        ? 'File failed the antivirus scan'
                        : 'Antivirus scanner unavailable; uploads are temporarily blocked',
                ]);
            }
        }

        $dir = public_path('uploads');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $safeName = now()->timestamp.'_'.(string) Str::uuid().$ext;
        file_put_contents($dir.'/'.$safeName, $buffer);

        return response()->json(['url' => '/uploads/'.$safeName, 'filename' => $data['filename']], 201);
    }
}
