<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models\Traits;

use Callcocam\LaraGatekeeper\Models\Address;

trait HasAddresses
{
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function defaultAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('is_default', true);
    }

    public function addAddress(array $data): Address
    {
        if (!empty($data['is_default'])) {
            $this->addresses()->update(['is_default' => false]);
        }

        return $this->addresses()->create($data);
    }

    public function updateAddress(Address $address, array $data): bool
    {
        if (!empty($data['is_default'])) {
            $this->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        return $address->update($data);
    }
} 