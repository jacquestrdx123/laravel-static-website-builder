<?php

namespace App\Support;

use App\Models\User;

class DomainContactBuilder
{
    /**
     * Build a single contact array from the authenticated user.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, string>
     */
    public static function fromUser(User $user, array $overrides = []): array
    {
        $nameParts = preg_split('/\s+/', trim($user->name), 2) ?: [$user->name];
        $firstName = $nameParts[0] ?? $user->name;
        $lastName = $nameParts[1] ?? '-';

        return array_merge([
            'firstname' => $firstName,
            'lastname' => $lastName,
            'fullname' => $user->name,
            'companyname' => $user->name,
            'email' => $user->email,
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'postcode' => '',
            'country' => 'ZA',
            'phonenumber' => '',
        ], $overrides);
    }

    /**
     * Build WHMCS-style contacts payload for registration/transfer.
     *
     * @param  array<string, mixed>  $contact
     * @return array<string, array<string, string>>
     */
    public static function contactsPayload(array $contact): array
    {
        return [
            'registrant' => $contact,
            'admin' => $contact,
            'billing' => $contact,
            'tech' => $contact,
        ];
    }

    /**
     * Build contactdetails payload for saveContacts API.
     *
     * @param  array<string, mixed>  $contact
     * @return array<string, array<string, string>>
     */
    public static function contactDetailsPayload(array $contact): array
    {
        return [
            'Registrant' => $contact,
            'Admin' => $contact,
            'Billing' => $contact,
            'Technical' => $contact,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultNameservers(): array
    {
        $defaults = config('services.hostafrica.default_nameservers', []);

        return array_filter([
            'ns1' => $defaults['ns1'] ?? null,
            'ns2' => $defaults['ns2'] ?? null,
            'ns3' => $defaults['ns3'] ?? null,
            'ns4' => $defaults['ns4'] ?? null,
            'ns5' => $defaults['ns5'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string>
     */
    public static function contactFromValidated(array $validated): array
    {
        return [
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'fullname' => trim($validated['firstname'].' '.$validated['lastname']),
            'companyname' => $validated['companyname'] ?? trim($validated['firstname'].' '.$validated['lastname']),
            'email' => $validated['email'],
            'address1' => $validated['address1'],
            'address2' => $validated['address2'] ?? '',
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postcode' => $validated['postcode'],
            'country' => $validated['country'],
            'phonenumber' => $validated['phonenumber'],
        ];
    }
}
