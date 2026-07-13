<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\File;

class PublishedSiteHost
{
    public function publishPath(Website $website): string
    {
        return rtrim((string) config('sites.publish_path'), '/').'/'.$website->slug;
    }

    public function customDomainPath(string $domain): string
    {
        return rtrim((string) config('sites.publish_path'), '/').'/domains/'.$domain;
    }

    public function domainsDirectory(): string
    {
        return rtrim((string) config('sites.publish_path'), '/').'/domains';
    }

    /** Copy preview site into the live web root and sync custom-domain symlink. */
    public function publish(Website $website): void
    {
        $target = $this->publishPath($website);

        File::deleteDirectory($target);
        File::ensureDirectoryExists(dirname($target));
        File::copyDirectory($website->sitePath(), $target);

        $this->syncCustomDomainSymlink($website);
    }

    /** Remove live copy and custom-domain symlink. */
    public function unpublish(Website $website): void
    {
        File::deleteDirectory($this->publishPath($website));
        $this->removeCustomDomainSymlink($website->custom_domain);
    }

    /** Point custom domain at the published slug directory. */
    public function syncCustomDomainSymlink(Website $website): void
    {
        if (blank($website->custom_domain)) {
            return;
        }

        if ($website->status !== Website::STATUS_PUBLISHED) {
            return;
        }

        $slugPath = $this->publishPath($website);

        if (! File::isDirectory($slugPath)) {
            return;
        }

        File::ensureDirectoryExists($this->domainsDirectory());

        $linkPath = $this->customDomainPath(strtolower($website->custom_domain));
        $this->removeSymlinkIfExists($linkPath);

        if (! symlink($slugPath, $linkPath)) {
            // Fallback when symlinks are unavailable (e.g. some Windows dev setups).
            if (! File::isDirectory($linkPath)) {
                File::copyDirectory($slugPath, $linkPath);
            }
        }
    }

    public function removeCustomDomainSymlink(?string $domain): void
    {
        if (blank($domain)) {
            return;
        }

        $this->removeSymlinkIfExists($this->customDomainPath(strtolower($domain)));
    }

    private function removeSymlinkIfExists(string $path): void
    {
        if (is_link($path)) {
            unlink($path);

            return;
        }

        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }
    }
}
