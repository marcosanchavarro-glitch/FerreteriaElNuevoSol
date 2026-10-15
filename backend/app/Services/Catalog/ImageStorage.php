<?php

namespace App\Services\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageStorage
{
    public function store(UploadedFile $file, string $folder): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw ValidationException::withMessages(['image' => 'El servidor necesita GD con soporte WebP para procesar imágenes.']);
        }
        $bytes = file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($bytes);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] > 3000 || $info[1] > 3000 || strlen($bytes) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'Imagen inválida. Máximo 5 MB y 3000 × 3000 píxeles.']);
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw ValidationException::withMessages(['image' => 'No se pudo leer el contenido de la imagen.']);
        }
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        try {
            $success = imagewebp($image, null, 85);
            $webp = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        if (! $success || $webp === '' || strlen($webp) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'No se pudo convertir la imagen dentro del límite de 5 MB.']);
        }
        $path = $folder.'/'.Str::uuid().'.webp';
        $this->assertPath($path, $folder);
        $this->write($path, $webp);

        return $path;
    }

    public function assertPath(string $path, string $folder): void
    {
        if (! preg_match('/^(products|categories|sections|banners)\/[1-9][0-9]*$/D', $folder) || ! preg_match('/^'.preg_quote($folder, '/').'\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\.webp$/D', $path)) {
            throw ValidationException::withMessages(['image' => 'La ruta de imagen no pertenece a este registro.']);
        }
    }

    public function backup(string $path, string $folder): ?string
    {
        $this->assertPath($path, $folder);

        return config('catalog.storage') === 'cloudinary'
            ? app(CloudinaryImages::class)->get($path)
            : (Storage::disk('catalog')->exists($path) ? Storage::disk('catalog')->get($path) : null);
    }

    public function delete(string $path, string $folder): void
    {
        $this->assertPath($path, $folder);
        if (config('catalog.storage') === 'cloudinary') {
            app(CloudinaryImages::class)->delete($path);

            return;
        }
        if (! Storage::disk('catalog')->delete($path)) {
            throw new \RuntimeException('No se pudo eliminar el archivo.');
        }
    }

    public function restore(string $path, string $folder, ?string $bytes): void
    {
        $this->assertPath($path, $folder);
        if ($bytes !== null) {
            $this->write($path, $bytes);
        }
    }

    public function response(string $path, string $folder): StreamedResponse
    {
        $this->assertPath($path, $folder);
        if (config('catalog.storage') === 'cloudinary') {
            $bytes = app(CloudinaryImages::class)->get($path);
            abort_if($bytes === null, 404);

            return response()->stream(function () use ($bytes): void {
                echo $bytes;
            }, 200, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
        }
        abort_unless(Storage::disk('catalog')->exists($path), 404);

        return Storage::disk('catalog')->response($path, null, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    private function write(string $path, string $bytes): void
    {
        if (config('catalog.storage') === 'cloudinary') {
            app(CloudinaryImages::class)->put($path, $bytes);
        } else {
            Storage::disk('catalog')->put($path, $bytes);
        }
    }
}
