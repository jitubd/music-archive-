<?php

namespace App\Services;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveService
{
    protected Guzzle $http;
    protected ?array $token = null;
    protected bool $tokenLoaded = false;

    public function __construct()
    {
        $this->http = new Guzzle(['base_uri' => 'https://www.googleapis.com']);
    }

    protected function loadToken(): void
    {
        if ($this->tokenLoaded) {
            return;
        }
        $this->tokenLoaded = true;

        $row = DB::table('google_tokens')->where('key', 'drive_token')->first();
        if ($row) {
            $this->token = json_decode($row->value, true);
        }
    }

    protected function saveToken(): void
    {
        DB::table('google_tokens')->updateOrInsert(
            ['key' => 'drive_token'],
            ['value' => json_encode($this->token), 'updated_at' => now()]
        );
    }

    protected function getAccessToken(): string
    {
        $this->loadToken();

        if ($this->token && isset($this->token['access_token'])) {
            if (empty($this->token['expires_at']) || $this->token['expires_at'] > time()) {
                return $this->token['access_token'];
            }
            if (!empty($this->token['refresh_token'])) {
                $this->refreshToken();
                return $this->token['access_token'];
            }
        }
        abort(401, 'Not authorized with Google Drive. Visit /auth/google first.');
    }

    protected function refreshToken(): void
    {
        $response = $this->http->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $this->token['refresh_token'],
                'grant_type'    => 'refresh_token',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $this->token['access_token'] = $data['access_token'];
        $this->token['expires_at'] = time() + ($data['expires_in'] ?? 3600);
        $this->saveToken();
    }

    public function isAuthorized(): bool
    {
        $this->loadToken();
        return !empty($this->token['access_token']);
    }

    public function getAuthUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => config('services.google.client_id'),
            'redirect_uri'  => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/drive.readonly',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = $this->http->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri'  => config('services.google.redirect_uri'),
                'code'          => $code,
                'grant_type'    => 'authorization_code',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $this->token = $data;
        $this->token['expires_at'] = time() + ($data['expires_in'] ?? 3600);
        $this->saveToken();

        return $data;
    }

    public function listChildren(string $folderId): array
    {
        $files = [];
        $pageToken = null;

        do {
            $params = [
                'q'       => "'{$folderId}' in parents and trashed = false",
                'fields'  => 'nextPageToken, files(id, name, mimeType, size)',
                'pageSize' => 200,
            ];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = $this->http->get('/drive/v3/files', [
                'headers' => ['Authorization' => 'Bearer ' . $this->getAccessToken()],
                'query'   => $params,
            ]);

            $data = json_decode($response->getBody(), true);

            foreach ($data['files'] ?? [] as $file) {
                $files[] = [
                    'id'       => $file['id'],
                    'name'     => $file['name'],
                    'mimeType' => $file['mimeType'],
                    'size'     => $file['size'] ?? null,
                ];
            }

            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        return $files;
    }

    public function isFolder(array $file): bool
    {
        return $file['mimeType'] === 'application/vnd.google-apps.folder';
    }

    public function isAudio(array $file): bool
    {
        return str_starts_with($file['mimeType'] ?? '', 'audio/')
            || preg_match('/\.(mp3|m4a|flac|wav|ogg)$/i', $file['name']);
    }

    public function getDirectUrl(string $fileId): string
    {
        return "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&access_token=" . $this->getAccessToken();
    }

    public function streamFile(string $fileId, ?string $rangeHeader, ?string $mimeType = 'audio/mpeg'): StreamedResponse
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];
        if ($rangeHeader) {
            $headers['Range'] = $rangeHeader;
        }

        $response = $this->http->get("https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media", [
            'headers' => $headers,
            'stream'  => true,
        ]);

        $status = $rangeHeader ? 206 : 200;
        $respHeaders = [
            'Content-Type'  => $mimeType,
            'Accept-Ranges' => 'bytes',
        ];

        if ($response->hasHeader('Content-Range')) {
            $respHeaders['Content-Range'] = $response->getHeaderLine('Content-Range');
        }
        if ($response->hasHeader('Content-Length')) {
            $respHeaders['Content-Length'] = $response->getHeaderLine('Content-Length');
        }

        $body = $response->getBody();

        return new StreamedResponse(function () use ($body) {
            while (!$body->eof()) {
                echo $body->read(1024 * 1024);
                flush();
            }
        }, $status, $respHeaders);
    }

    public function downloadFile(string $fileId, string $filename, string $mimeType = 'audio/mpeg'): StreamedResponse
    {
        $response = $this->http->get("https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media", [
            'headers' => ['Authorization' => 'Bearer ' . $this->getAccessToken()],
            'stream'  => true,
        ]);

        $respHeaders = [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        if ($response->hasHeader('Content-Length')) {
            $respHeaders['Content-Length'] = $response->getHeaderLine('Content-Length');
        }

        $body = $response->getBody();

        return new StreamedResponse(function () use ($body) {
            while (!$body->eof()) {
                echo $body->read(1024 * 1024);
                flush();
            }
        }, 200, $respHeaders);
    }
}
