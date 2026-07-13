<nav class="actions" style="margin-bottom:1rem">
    <a class="btn secondary" href="{{ route('domains.show', $domain) }}">Overview</a>
    <a class="btn secondary" href="{{ route('domains.dns.edit', $domain) }}">DNS</a>
    <a class="btn secondary" href="{{ route('domains.nameservers.edit', $domain) }}">Nameservers</a>
    <a class="btn secondary" href="{{ route('domains.contacts.edit', $domain) }}">Contacts</a>
    <a class="btn secondary" href="{{ route('domains.settings.edit', $domain) }}">Settings</a>
</nav>
