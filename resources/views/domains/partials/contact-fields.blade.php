@php
    $field = fn (string $name) => filled($prefix ?? null) ? $prefix.'['.$name.']' : $name;
    $oldKey = fn (string $name) => filled($prefix ?? null) ? $prefix.'.'.$name : $name;
@endphp
<div class="grid-2">
    <div>
        <label for="{{ ($prefix ?? 'contact').'_firstname' }}">First name</label>
        <input type="text" id="{{ ($prefix ?? 'contact').'_firstname' }}" name="{{ $field('firstname') }}" value="{{ old($oldKey('firstname'), $contact['firstname'] ?? '') }}" required>
        @error($oldKey('firstname'))<div class="error">{{ $message }}</div>@enderror
    </div>
    <div>
        <label for="{{ ($prefix ?? 'contact').'_lastname' }}">Last name</label>
        <input type="text" id="{{ ($prefix ?? 'contact').'_lastname' }}" name="{{ $field('lastname') }}" value="{{ old($oldKey('lastname'), $contact['lastname'] ?? '') }}" required>
        @error($oldKey('lastname'))<div class="error">{{ $message }}</div>@enderror
    </div>
</div>
<label for="{{ ($prefix ?? 'contact').'_companyname' }}">Company</label>
<input type="text" id="{{ ($prefix ?? 'contact').'_companyname' }}" name="{{ $field('companyname') }}" value="{{ old($oldKey('companyname'), $contact['companyname'] ?? '') }}">
<label for="{{ ($prefix ?? 'contact').'_email' }}">Email</label>
<input type="email" id="{{ ($prefix ?? 'contact').'_email' }}" name="{{ $field('email') }}" value="{{ old($oldKey('email'), $contact['email'] ?? '') }}" required>
<label for="{{ ($prefix ?? 'contact').'_address1' }}">Address line 1</label>
<input type="text" id="{{ ($prefix ?? 'contact').'_address1' }}" name="{{ $field('address1') }}" value="{{ old($oldKey('address1'), $contact['address1'] ?? '') }}" required>
<label for="{{ ($prefix ?? 'contact').'_address2' }}">Address line 2</label>
<input type="text" id="{{ ($prefix ?? 'contact').'_address2' }}" name="{{ $field('address2') }}" value="{{ old($oldKey('address2'), $contact['address2'] ?? '') }}">
<div class="grid-2">
    <div>
        <label for="{{ ($prefix ?? 'contact').'_city' }}">City</label>
        <input type="text" id="{{ ($prefix ?? 'contact').'_city' }}" name="{{ $field('city') }}" value="{{ old($oldKey('city'), $contact['city'] ?? '') }}" required>
    </div>
    <div>
        <label for="{{ ($prefix ?? 'contact').'_state' }}">State / province</label>
        <input type="text" id="{{ ($prefix ?? 'contact').'_state' }}" name="{{ $field('state') }}" value="{{ old($oldKey('state'), $contact['state'] ?? '') }}" required>
    </div>
</div>
<div class="grid-2">
    <div>
        <label for="{{ ($prefix ?? 'contact').'_postcode' }}">Postcode</label>
        <input type="text" id="{{ ($prefix ?? 'contact').'_postcode' }}" name="{{ $field('postcode') }}" value="{{ old($oldKey('postcode'), $contact['postcode'] ?? '') }}" required>
    </div>
    <div>
        <label for="{{ ($prefix ?? 'contact').'_country' }}">Country (ISO)</label>
        <input type="text" id="{{ ($prefix ?? 'contact').'_country' }}" name="{{ $field('country') }}" maxlength="2" value="{{ old($oldKey('country'), $contact['country'] ?? 'ZA') }}" required>
    </div>
</div>
<label for="{{ ($prefix ?? 'contact').'_phonenumber' }}">Phone number</label>
<input type="text" id="{{ ($prefix ?? 'contact').'_phonenumber' }}" name="{{ $field('phonenumber') }}" value="{{ old($oldKey('phonenumber'), $contact['phonenumber'] ?? '') }}" required>
