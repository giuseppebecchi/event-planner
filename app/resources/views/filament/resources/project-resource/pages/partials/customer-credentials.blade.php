@if (! auth()->user()?->isCustomer())
    <div class="wm-credential-panel">
        <p class="wm-credential-title">Customer portal credentials</p>
        <div class="wm-credential-actions">
            <x-filament::button
                type="button"
                size="sm"
                wire:click="sendCustomerCredentials('email')"
                wire:confirm="Create/link a Customer user and send credentials to Partner 1 email?"
            >
                Send Partner 1 Credential
            </x-filament::button>
            <x-filament::button
                type="button"
                size="sm"
                color="gray"
                wire:click="sendCustomerCredentials('secondary_email')"
                wire:confirm="Create/link a Customer user and send credentials to Partner 2 email?"
            >
                Send Partner 2 Credential
            </x-filament::button>
        </div>
    </div>
@endif
