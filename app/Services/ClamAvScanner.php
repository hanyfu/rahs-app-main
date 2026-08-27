<?php

namespace App\Services;

use App\Contracts\ClamScanner;
use RuntimeException;

class ClamAvScanner implements ClamScanner
{
    public function __construct(
        private string $host,
        private int $port,
        private int $timeout,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('upload.clamav');

        return new self(
            host: (string) ($config['host'] ?? '/run/clamav/clamd.ctl'),
            port: (int) ($config['port'] ?? 3310),
            timeout: (int) ($config['timeout'] ?? 30),
        );
    }

    public function enabled(): bool
    {
        return (bool) config('upload.clamav.enabled', false);
    }

    /**
     * Stream the payload to clamd and return whether it is clean.
     *
     * Throws a RuntimeException when clamd cannot be reached so callers can
     * fail closed.
     */
    public function isClean(string $buffer): bool
    {
        if (str_starts_with($this->host, '/')) {
            $stream = @stream_socket_client('unix://'.$this->host, $errno, $errstr, $this->timeout);
        } else {
            $stream = @stream_socket_client('tcp://'.$this->host.':'.$this->port, $errno, $errstr, $this->timeout);
        }

        if ($stream === false) {
            throw new RuntimeException('ClamAV daemon unreachable: '.($errstr ?: 'connection failed'));
        }

        try {
            stream_set_timeout($stream, $this->timeout);
            fwrite($stream, "zINSTREAM\0");

            foreach (str_split($buffer, 8192) as $chunk) {
                fwrite($stream, pack('N', strlen($chunk)).$chunk);
            }
            fwrite($stream, pack('N', 0));

            $response = '';
            while (! feof($stream)) {
                $response .= fread($stream, 8192);
                if (str_contains($response, "\n")) {
                    break;
                }
            }

            $clean = str_contains($response, 'OK');
            $infected = preg_match('/FOUND$/m', trim($response)) === 1;

            if ($infected) {
                return false;
            }

            return $clean;
        } finally {
            fclose($stream);
        }
    }

    public function scanOrFail(string $buffer): void
    {
        if (! $this->isClean($buffer)) {
            throw new RuntimeException('File failed the antivirus scan');
        }
    }
}
