@if (! auth()->user()?->isCustomer())
    <div class="wm-credential-panel">
        <p class="wm-credential-title">Customer portal credentials</p>
        <div class="wm-credential-actions">
            <x-filament::button
                type="button"
                size="sm"
                wire:click="mountAction('sendCustomerCredentials', { field: 'email' })"
            >
                Send Partner 1 Credential
            </x-filament::button>
            <x-filament::button
                type="button"
                size="sm"
                color="gray"
                wire:click="mountAction('sendCustomerCredentials', { field: 'secondary_email' })"
            >
                Send Partner 2 Credential
            </x-filament::button>
        </div>
    </div>
@endif
