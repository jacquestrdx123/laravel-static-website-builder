<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class WebsiteContentVault
{
    private PDO $pdo;

    private string $root;

    private function __construct(private Website $website)
    {
        $this->root = rtrim((string) config('sites.website_data_path'), '/').'/'.$website->id;
        File::ensureDirectoryExists($this->root);
        $this->pdo = new PDO('sqlite:'.$this->vaultPath('vault.sqlite'));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->migrate();
    }

    public static function forWebsite(Website $website): self
    {
        return new self($website);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function recordProductSnapshot(string $source, array $before, array $after): string
    {
        $uuid = (string) Str::uuid();
        $dir = 'products/'.$uuid;
        $this->writeJson($dir.'/before.json', $before);
        $this->writeJson($dir.'/after.json', $after);
        $this->writeXml($dir.'/snapshot.xml', 'product_snapshot', [
            'uuid' => $uuid,
            'source' => $source,
            'created_at' => now()->toIso8601String(),
            'before' => $before,
            'after' => $after,
        ]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO product_snapshots (uuid, source, created_at, before_path, after_path, xml_path)
             VALUES (:uuid, :source, :created_at, :before_path, :after_path, :xml_path)'
        );
        $stmt->execute([
            'uuid' => $uuid,
            'source' => $source,
            'created_at' => now()->toIso8601String(),
            'before_path' => $dir.'/before.json',
            'after_path' => $dir.'/after.json',
            'xml_path' => $dir.'/snapshot.xml',
        ]);

        $this->regenerateManifest();

        return $uuid;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $afterMeta
     */
    public function recordNewsletter(string $topic, array $before, array $afterMeta, string $html, string $text): string
    {
        $uuid = (string) Str::uuid();
        $dir = 'newsletters/'.$uuid;
        File::ensureDirectoryExists($this->vaultPath($dir));

        $meta = [
            'uuid' => $uuid,
            'topic' => $topic,
            'subject' => $afterMeta['subject'] ?? '',
            'created_at' => now()->toIso8601String(),
            'sent_at' => null,
            'recipient_count' => 0,
            'status' => 'ready',
        ];

        $this->writeJson($dir.'/meta.json', $meta);
        $this->writeJson($dir.'/before.json', $before);
        $this->writeJson($dir.'/after.json', $afterMeta);
        File::put($this->vaultPath($dir.'/content.html'), $html);
        File::put($this->vaultPath($dir.'/content.txt'), $text);
        $this->writeXml($dir.'/newsletter.xml', 'newsletter', array_merge($meta, [
            'html' => $html,
            'text' => $text,
        ]));

        $stmt = $this->pdo->prepare(
            'INSERT INTO newsletters (uuid, topic, subject, status, created_at, sent_at, recipient_count, dir_path)
             VALUES (:uuid, :topic, :subject, :status, :created_at, NULL, 0, :dir_path)'
        );
        $stmt->execute([
            'uuid' => $uuid,
            'topic' => $topic,
            'subject' => $meta['subject'],
            'status' => 'ready',
            'created_at' => $meta['created_at'],
            'dir_path' => $dir,
        ]);

        $this->regenerateManifest();

        return $uuid;
    }

    public function updatePosterPng(string $uuid, string $pngRelativePath): void
    {
        $poster = $this->findPoster($uuid);
        if ($poster === null) {
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE posters SET png_path = :png_path, status = :status WHERE uuid = :uuid');
        $stmt->execute([
            'png_path' => $pngRelativePath,
            'status' => 'ready',
            'uuid' => $uuid,
        ]);

        $metaPath = $poster['dir_path'].'/meta.json';
        if (File::exists($this->vaultPath($metaPath))) {
            $meta = json_decode(File::get($this->vaultPath($metaPath)), true);
            $meta['status'] = 'ready';
            $meta['png_path'] = $pngRelativePath;
            $this->writeJson($metaPath, $meta);
        }

        $this->regenerateManifest();
    }

    public function markNewsletterSent(string $uuid, int $recipientCount): void
    {
        $newsletter = $this->findNewsletter($uuid);
        if ($newsletter === null) {
            return;
        }

        $meta = json_decode(File::get($this->vaultPath($newsletter['dir_path'].'/meta.json')), true);
        $meta['sent_at'] = now()->toIso8601String();
        $meta['recipient_count'] = $recipientCount;
        $meta['status'] = 'sent';
        $this->writeJson($newsletter['dir_path'].'/meta.json', $meta);

        $stmt = $this->pdo->prepare(
            'UPDATE newsletters SET status = :status, sent_at = :sent_at, recipient_count = :recipient_count WHERE uuid = :uuid'
        );
        $stmt->execute([
            'status' => 'sent',
            'sent_at' => $meta['sent_at'],
            'recipient_count' => $recipientCount,
            'uuid' => $uuid,
        ]);

        $this->regenerateManifest();
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function recordPoster(array $before, string $format, string $html, ?string $pngRelativePath): string
    {
        $uuid = (string) Str::uuid();
        $dir = 'posters/'.$uuid;
        File::ensureDirectoryExists($this->vaultPath($dir));

        $meta = [
            'uuid' => $uuid,
            'format' => $format,
            'created_at' => now()->toIso8601String(),
            'status' => $pngRelativePath ? 'ready' : 'html_only',
        ];

        $this->writeJson($dir.'/meta.json', $meta);
        $this->writeJson($dir.'/before.json', $before);
        File::put($this->vaultPath($dir.'/after.html'), $html);
        $this->writeXml($dir.'/poster.xml', 'poster', array_merge($meta, [
            'html_path' => $dir.'/after.html',
            'png_path' => $pngRelativePath,
        ]));

        $stmt = $this->pdo->prepare(
            'INSERT INTO posters (uuid, format, status, created_at, dir_path, png_path)
             VALUES (:uuid, :format, :status, :created_at, :dir_path, :png_path)'
        );
        $stmt->execute([
            'uuid' => $uuid,
            'format' => $format,
            'status' => $meta['status'],
            'created_at' => $meta['created_at'],
            'dir_path' => $dir,
            'png_path' => $pngRelativePath,
        ]);

        $this->regenerateManifest();

        return $uuid;
    }

    /** @return list<array<string, mixed>> */
    public function listProductSnapshots(): array
    {
        $rows = $this->pdo->query('SELECT * FROM product_snapshots ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /** @return list<array<string, mixed>> */
    public function listNewsletters(): array
    {
        $rows = $this->pdo->query('SELECT * FROM newsletters ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /** @return list<array<string, mixed>> */
    public function listPosters(): array
    {
        $rows = $this->pdo->query('SELECT * FROM posters ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, mixed>|null */
    public function findNewsletter(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM newsletters WHERE uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findPoster(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM posters WHERE uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function newsletterHtml(string $uuid): ?string
    {
        $newsletter = $this->findNewsletter($uuid);
        if ($newsletter === null) {
            return null;
        }

        $path = $this->vaultPath($newsletter['dir_path'].'/content.html');

        return File::exists($path) ? File::get($path) : null;
    }

    public function posterHtml(string $uuid): ?string
    {
        $poster = $this->findPoster($uuid);
        if ($poster === null) {
            return null;
        }

        $path = $this->vaultPath($poster['dir_path'].'/after.html');

        return File::exists($path) ? File::get($path) : null;
    }

    public function posterPngPath(string $uuid): ?string
    {
        $poster = $this->findPoster($uuid);
        if ($poster === null || blank($poster['png_path'])) {
            return null;
        }

        $path = $this->vaultPath($poster['png_path']);

        return File::exists($path) ? $path : null;
    }

    public function counts(): array
    {
        return [
            'products' => (int) $this->pdo->query('SELECT COUNT(*) FROM product_snapshots')->fetchColumn(),
            'newsletters' => (int) $this->pdo->query('SELECT COUNT(*) FROM newsletters')->fetchColumn(),
            'posters' => (int) $this->pdo->query('SELECT COUNT(*) FROM posters')->fetchColumn(),
        ];
    }

    private function migrate(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS product_snapshots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                source TEXT NOT NULL,
                created_at TEXT NOT NULL,
                before_path TEXT NOT NULL,
                after_path TEXT NOT NULL,
                xml_path TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS newsletters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                topic TEXT NOT NULL,
                subject TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at TEXT NOT NULL,
                sent_at TEXT,
                recipient_count INTEGER NOT NULL DEFAULT 0,
                dir_path TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS posters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                format TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at TEXT NOT NULL,
                dir_path TEXT NOT NULL,
                png_path TEXT
            );
        SQL);
    }

    private function vaultPath(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if (str_contains($relative, '..')) {
            throw new RuntimeException('Invalid vault path.');
        }

        return $this->root.'/'.$relative;
    }

    /** @param  array<string, mixed>  $data */
    private function writeJson(string $relative, array $data): void
    {
        File::ensureDirectoryExists(dirname($this->vaultPath($relative)));
        File::put($this->vaultPath($relative), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /** @param  array<string, mixed>  $data */
    private function writeXml(string $relative, string $rootElement, array $data): void
    {
        $xml = new \SimpleXMLElement('<'.$rootElement.'/>');
        $this->arrayToXml($data, $xml);
        File::ensureDirectoryExists(dirname($this->vaultPath($relative)));
        File::put($this->vaultPath($relative), $xml->asXML());
    }

    /** @param  array<string, mixed>  $data */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild(is_numeric($key) ? 'item' : (string) $key);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild((string) $key, htmlspecialchars((string) $value));
            }
        }
    }

    private function regenerateManifest(): void
    {
        $this->writeJson('manifest.json', [
            'website_id' => $this->website->id,
            'updated_at' => now()->toIso8601String(),
            'counts' => $this->counts(),
        ]);
    }
}
