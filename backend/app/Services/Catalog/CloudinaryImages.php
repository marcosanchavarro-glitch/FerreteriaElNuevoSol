<?php

namespace App\Services\Catalog;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudinaryImages
{
    private function publicId(string $path): string
    {
        app(ImageStorage::class)->assertPath($path, dirname($path));
        $prefix = (string) config('catalog.cloudinary.prefix');
        if (! preg_match('/^[a-zA-Z0-9_-]{1,80}$/D', $prefix)) {
            throw new RuntimeException('El prefijo de imágenes no es válido.');
        }

        return $prefix.'/'.substr($path, 0, -5);
    }

    private function request(string $operation, array $parameters, ?string $bytes = null): Response
    {
        $cloud = (string) config('catalog.cloudinary.cloud');
        $key = (string) config('catalog.cloudinary.key');
        $secret = (string) config('catalog.cloudinary.secret');
        if (! preg_match('/^[a-zA-Z0-9_-]+$/D', $cloud) || $key === '' || $secret === '') {
            throw new RuntimeException('El almacenamiento de imágenes no está configurado.');
        }
        $parameters['timestamp'] = time();
        ksort($parameters);
        $signature = hash('sha256', implode('&', array_map(fn ($name, $value) => $name.'='.$value, array_keys($parameters), $parameters)).$secret);
        $parameters += ['api_key' => $key, 'signature' => $signature];
        $url = 'https://api.cloudinary.com/v1_1/'.$cloud.'/image/'.$operation;
        try {
            $client = Http::connectTimeout(5)->timeout(25)->withoutRedirecting();
            $response = $operation === 'download'
                ? $client->get($url, $parameters)
                : ($bytes === null ? $client->asForm()->post($url, $parameters) : $client->attach('file', $bytes, 'image.webp')->post($url, $parameters));
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo contactar al almacenamiento de imágenes.');
        }
        if (! $response->successful() && ! ($operation === 'download' && $response->status() === 404)) {
            throw new RuntimeException('El almacenamiento de imágenes rechazó la operación.');
        }

        return $response;
    }

    public function put(string $path, string $bytes): void
    {
        $id = $this->publicId($path);
        $response = $this->request('upload', ['public_id' => $id, 'type' => 'authenticated', 'overwrite' => 'true'], $bytes);
        if ($response->json('public_id') !== $id || $response->json('type') !== 'authenticated' || $response->json('format') !== 'webp') {
            throw new RuntimeException('El almacenamiento no confirmó una imagen protegida.');
        }
    }

    public function get(string $path): ?string
    {
        $response = $this->request('download', [
            'public_id' => $this->publicId($path), 'type' => 'authenticated',
            'format' => 'webp', 'expires_at' => time() + 60, 'attachment' => 'false',
        ]);
        if ($response->status() === 404) {
            return null;
        }
        $bytes = $response->body();
        if (strlen($bytes) > 5 * 1024 * 1024 || substr($bytes, 0, 4) !== 'RIFF' || substr($bytes, 8, 4) !== 'WEBP') {
            throw new RuntimeException('El almacenamiento devolvió una imagen inválida.');
        }

        return $bytes;
    }

    public function delete(string $path): void
    {
        $response = $this->request('destroy', ['public_id' => $this->publicId($path), 'type' => 'authenticated', 'invalidate' => 'true']);
        if (! in_array($response->json('result'), ['ok', 'not found'], true)) {
            throw new RuntimeException('No se pudo confirmar la eliminación de la imagen.');
        }
    }
}
