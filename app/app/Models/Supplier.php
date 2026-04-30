<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    public const LOCATION_CATEGORY_ID = 2;

    public const LOCATION_STRUCTURE_TYPES = [
        'villa' => 'Villa',
        'farmhouse' => 'Farmhouse',
        'hamlet' => 'Hamlet',
        'castle' => 'Castle',
        'hotel' => 'Hotel',
        'estate' => 'Estate',
        'resort' => 'Resort',
        'beach_club' => 'Beach club',
        'historic_home' => 'Historic home',
        'other' => 'Other',
    ];

    public const LOCATION_STYLE_TYPES = [
        'rustic' => 'Rustic',
        'historic' => 'Historic',
        'elegant' => 'Elegant',
        'modern' => 'Modern',
        'luxury' => 'Luxury',
        'boho' => 'Boho',
        'classic' => 'Classic',
        'minimal' => 'Minimal',
        'other' => 'Other',
    ];

    public const LOCATION_CATERING_TYPES = [
        'inhouse_only' => 'In-house only',
        'external_only' => 'External only',
        'inhouse_or_external' => 'In-house or external',
        'approved_external_only' => 'Approved external only',
        'not_applicable' => 'Not applicable',
    ];

    public const LOCATION_CEREMONY_TYPES = [
        'civil' => 'Civil',
        'symbolic' => 'Symbolic',
        'religious' => 'Religious',
    ];

    public const LOCATION_RENTAL_MODES = [
        'daily' => 'Daily',
        'exclusive_use' => 'Exclusive use',
        'multi_day' => 'Multi-day',
        'custom' => 'Custom',
    ];

    protected $fillable = [
        'name',
        'category_id',
        'service_area',
        'location',
        'email',
        'phone',
        'contact_person',
        'style_description',
        'price_range',
        'internal_notes',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'province',
        'region',
        'country',
        'vat_number',
        'tax_code',
        'sdi_code',
        'loc_locality',
        'loc_geo_area',
        'loc_latitude',
        'loc_longitude',
        'loc_airport_distance_km',
        'loc_overview',
        'loc_structure_type',
        'loc_style',
        'loc_website',
        'loc_guest_max',
        'loc_guest_indoor_max',
        'loc_guest_outdoor_max',
        'loc_guest_min',
        'loc_event_spaces',
        'loc_has_garden',
        'loc_has_indoor_hall',
        'loc_has_ceremony_space',
        'loc_other_event_areas',
        'loc_has_rooms',
        'loc_room_count',
        'loc_stay_guest_max',
        'loc_room_setup',
        'loc_exclusive_use',
        'loc_min_nights',
        'loc_stay_notes',
        'loc_catering_type',
        'loc_has_inhouse_catering',
        'loc_allows_external_catering',
        'loc_exclusive_caterers',
        'loc_external_catering_rules',
        'loc_catering_notes',
        'loc_ceremony_types',
        'loc_allows_ceremony_on_site',
        'loc_ceremony_spaces',
        'loc_ceremony_rules',
        'loc_music_end_time',
        'loc_music_extension',
        'loc_sound_limits',
        'loc_music_rules',
        'loc_music_notes',
        'loc_allows_fireworks',
        'loc_fireworks_rules',
        'loc_fireworks_area',
        'loc_fireworks_permits',
        'loc_supplier_access',
        'loc_has_parking',
        'loc_accessible',
        'loc_protected_areas',
        'loc_setup_limits',
        'loc_setup_time_limits',
        'loc_other_limits',
        'loc_rental_fee',
        'loc_rental_mode',
        'loc_extra_costs',
        'loc_booking_deposit',
        'loc_payment_terms',
    ];

    protected $casts = [
        'loc_latitude' => 'decimal:7',
        'loc_longitude' => 'decimal:7',
        'loc_airport_distance_km' => 'decimal:2',
        'loc_guest_max' => 'integer',
        'loc_guest_indoor_max' => 'integer',
        'loc_guest_outdoor_max' => 'integer',
        'loc_guest_min' => 'integer',
        'loc_has_garden' => 'boolean',
        'loc_has_indoor_hall' => 'boolean',
        'loc_has_ceremony_space' => 'boolean',
        'loc_has_rooms' => 'boolean',
        'loc_room_count' => 'integer',
        'loc_stay_guest_max' => 'integer',
        'loc_exclusive_use' => 'boolean',
        'loc_min_nights' => 'integer',
        'loc_has_inhouse_catering' => 'boolean',
        'loc_allows_external_catering' => 'boolean',
        'loc_ceremony_types' => 'array',
        'loc_allows_ceremony_on_site' => 'boolean',
        'loc_music_extension' => 'boolean',
        'loc_allows_fireworks' => 'boolean',
        'loc_has_parking' => 'boolean',
        'loc_accessible' => 'boolean',
        'loc_rental_fee' => 'decimal:2',
        'loc_booking_deposit' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function categoryBudgetSuppliers(): HasMany
    {
        return $this->hasMany(CategoryBudgetSupplier::class);
    }

    public function projectDocuments(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function projectImages(): HasMany
    {
        return $this->hasMany(ProjectImage::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supplierCommunications(): HasMany
    {
        return $this->hasMany(ProjectSupplierCommunication::class);
    }
}
