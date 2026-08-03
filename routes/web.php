<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssetCdnController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CaddyController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DomainContactController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\DomainDnsController;
use App\Http\Controllers\DomainNameserverController;
use App\Http\Controllers\DomainSearchController;
use App\Http\Controllers\DomainSettingsController;
use App\Http\Controllers\DomainTransferController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NewsletterSubscriberController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\PublicNewsletterController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\WebsiteSubscriptionController;
use App\Http\Controllers\WebsiteController;
use App\Livewire\Auth\Login as AuthLogin;
use App\Livewire\Auth\Register as AuthRegister;
use App\Livewire\Billing;
use App\Livewire\Domains\Contacts as DomainContacts;
use App\Livewire\Domains\Dns as DomainDns;
use App\Livewire\Domains\Index as DomainIndex;
use App\Livewire\Domains\Nameservers as DomainNameservers;
use App\Livewire\Domains\Register as DomainRegister;
use App\Livewire\Domains\Search as DomainSearch;
use App\Livewire\Domains\Settings as DomainSettings;
use App\Livewire\Domains\Show as DomainShow;
use App\Livewire\Domains\Transfer as DomainTransfer;
use App\Livewire\Pricing;
use App\Livewire\Websites\Create as CreateWebsite;
use App\Livewire\Websites\EditContent as EditWebsiteContent;
use App\Livewire\Websites\Index as WebsiteIndex;
use App\Livewire\Websites\Newsletters\Create as NewsletterCreate;
use App\Livewire\Websites\Newsletters\Index as NewsletterIndex;
use App\Livewire\Websites\Newsletters\Show as NewsletterShow;
use App\Livewire\Websites\Posters\Create as PosterCreate;
use App\Livewire\Websites\Posters\Index as PosterIndex;
use App\Livewire\Websites\Posters\Show as PosterShow;
use App\Livewire\Websites\Show as ShowWebsite;
use App\Livewire\Websites\Subscribers as WebsiteSubscribers;
use App\Livewire\Websites\Subscription as WebsiteSubscription;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Stable, cacheable URLs for customer website assets (photos referenced by static sites).
Route::get('/cdn/{website}/{assetKey}', [AssetCdnController::class, 'show'])
    ->where('assetKey', '[0-9a-fA-F-]{36}')
    ->name('cdn.asset');

// Caddy on_demand_tls "ask" endpoint (called server-to-server, no session).
Route::get('/caddy/allowed', [CaddyController::class, 'allowed'])->name('caddy.allowed');

Route::post('/sites/{slug}/newsletter/subscribe', [PublicNewsletterController::class, 'subscribe'])
    ->name('public.newsletter.subscribe');
Route::get('/sites/{slug}/newsletter/unsubscribe/{token}', [PublicNewsletterController::class, 'unsubscribe'])
    ->name('public.newsletter.unsubscribe');

Route::middleware('guest')->group(function () {
    Route::get('/register', AuthRegister::class)->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', AuthLogin::class)->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Public: the homepage funnel links logged-out visitors here.
Route::get('/pricing', Pricing::class)->name('pricing');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', WebsiteIndex::class)->name('dashboard');
    Route::get('/websites', WebsiteIndex::class)->name('websites.index');

    Route::get('/websites/create', CreateWebsite::class)->name('websites.create');
    Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
    Route::get('/websites/{website}', ShowWebsite::class)->name('websites.show');
    Route::get('/websites/{website}/status', [WebsiteController::class, 'status'])->name('websites.status');
    Route::post('/websites/{website}/regenerate', [WebsiteController::class, 'regenerate'])->name('websites.regenerate');
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy'])->name('websites.destroy');

    Route::get('/websites/{website}/content', EditWebsiteContent::class)->name('websites.content.edit');
    Route::post('/websites/{website}/content', [ContentController::class, 'update'])->name('websites.content.update');
    Route::get('/websites/{website}/images/{image}', [ContentController::class, 'image'])->name('websites.images.show');

    Route::get('/websites/{website}/subscription', WebsiteSubscription::class)->name('websites.subscription.show');
    Route::post('/websites/{website}/subscription', [WebsiteSubscriptionController::class, 'purchase'])->name('websites.subscription.purchase');

    Route::get('/websites/{website}/subscribers', WebsiteSubscribers::class)->name('websites.subscribers.index');
    Route::post('/websites/{website}/subscribers', [NewsletterSubscriberController::class, 'store'])->name('websites.subscribers.store');
    Route::delete('/websites/{website}/subscribers/{subscriber}', [NewsletterSubscriberController::class, 'destroy'])->name('websites.subscribers.destroy');

    Route::get('/websites/{website}/newsletters', NewsletterIndex::class)->name('websites.newsletters.index');
    Route::get('/websites/{website}/newsletters/create', NewsletterCreate::class)->name('websites.newsletters.create');
    Route::post('/websites/{website}/newsletters', [NewsletterController::class, 'store'])->name('websites.newsletters.store');
    Route::get('/websites/{website}/newsletters/{uuid}', NewsletterShow::class)->name('websites.newsletters.show');
    Route::post('/websites/{website}/newsletters/{uuid}/send', [NewsletterController::class, 'send'])->name('websites.newsletters.send');

    Route::get('/websites/{website}/posters', PosterIndex::class)->name('websites.posters.index');
    Route::get('/websites/{website}/posters/create', PosterCreate::class)->name('websites.posters.create');
    Route::post('/websites/{website}/posters', [PosterController::class, 'store'])->name('websites.posters.store');
    Route::get('/websites/{website}/posters/{uuid}', PosterShow::class)->name('websites.posters.show');
    Route::get('/websites/{website}/posters/{uuid}/download', [PosterController::class, 'download'])->name('websites.posters.download');

    Route::post('/websites/{website}/publish', [PublishController::class, 'store'])->name('websites.publish');
    Route::delete('/websites/{website}/publish', [PublishController::class, 'destroy'])->name('websites.unpublish');

    Route::get('/billing', Billing::class)->name('billing.index');
    Route::post('/billing/purchase', [BillingController::class, 'purchase'])->name('billing.purchase');

    Route::prefix('domains')->name('domains.')->group(function () {
        Route::get('/', DomainIndex::class)->name('index');
        Route::get('/search', DomainSearch::class)->name('search');
        Route::post('/search', [DomainSearchController::class, 'search'])->name('search.perform');
        Route::post('/suggest', [DomainSearchController::class, 'suggest'])->name('search.suggest');
        Route::get('/register', DomainRegister::class)->name('register');
        Route::post('/register', [DomainController::class, 'store'])->name('register.store');
        Route::get('/transfer', DomainTransfer::class)->name('transfer');
        Route::post('/transfer', [DomainTransferController::class, 'store'])->name('transfer.store');

        Route::get('/{domain}', DomainShow::class)->name('show');
        Route::post('/{domain}/renew', [DomainController::class, 'renew'])->name('renew');
        Route::post('/{domain}/link', [DomainController::class, 'link'])->name('link');
        Route::delete('/{domain}/link', [DomainController::class, 'unlink'])->name('unlink');
        Route::post('/{domain}/sync', [DomainController::class, 'sync'])->name('sync');

        Route::get('/{domain}/dns', DomainDns::class)->name('dns.edit');
        Route::post('/{domain}/dns', [DomainDnsController::class, 'update'])->name('dns.update');

        Route::get('/{domain}/nameservers', DomainNameservers::class)->name('nameservers.edit');
        Route::post('/{domain}/nameservers', [DomainNameserverController::class, 'update'])->name('nameservers.update');
        Route::post('/{domain}/nameservers/register', [DomainNameserverController::class, 'registerChild'])->name('nameservers.register-child');
        Route::post('/{domain}/nameservers/modify', [DomainNameserverController::class, 'modifyChild'])->name('nameservers.modify-child');
        Route::post('/{domain}/nameservers/delete', [DomainNameserverController::class, 'deleteChild'])->name('nameservers.delete-child');

        Route::get('/{domain}/contacts', DomainContacts::class)->name('contacts.edit');
        Route::post('/{domain}/contacts', [DomainContactController::class, 'update'])->name('contacts.update');

        Route::get('/{domain}/settings', DomainSettings::class)->name('settings.edit');
        Route::post('/{domain}/settings/lock', [DomainSettingsController::class, 'updateLock'])->name('settings.lock');
        Route::post('/{domain}/settings/id-protection', [DomainSettingsController::class, 'updateIdProtection'])->name('settings.id-protection');
        Route::post('/{domain}/settings/email', [DomainSettingsController::class, 'updateEmailForwarding'])->name('settings.email');
        Route::post('/{domain}/settings/epp', [DomainSettingsController::class, 'eppCode'])->name('settings.epp');
        Route::post('/{domain}/settings/release', [DomainSettingsController::class, 'release'])->name('settings.release');
        Route::post('/{domain}/settings/delete', [DomainSettingsController::class, 'requestDeletion'])->name('settings.delete');
        Route::post('/{domain}/settings/transfer-sync', [DomainSettingsController::class, 'transferSync'])->name('settings.transfer-sync');
    });
});
