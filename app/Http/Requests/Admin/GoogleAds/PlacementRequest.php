<?php

namespace App\Http\Requests\Admin\GoogleAds;

use App\Enums\AdDevice;
use App\Enums\AdLocation;
use App\Enums\AdNetwork;
use App\Enums\AdPlacementKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One ad placement, for both creating and editing. Fields that do not apply to the chosen kind or network are
 * cleared, so a stored placement never carries leftovers.
 */
class PlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route group already requires an administrator.
        return $this->user()?->isAdministrator() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $display = $this->input('kind') === AdPlacementKind::Display->value;
        $adManager = $this->input('network') === AdNetwork::AdManager->value;

        return [
            'key' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('ad_placements', 'key')->ignore($this->route('placement'))],
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::enum(AdPlacementKind::class)],
            // Google only serves rewarded web ads through Ad Manager.
            'network' => ['required', $display ? Rule::enum(AdNetwork::class) : Rule::in([AdNetwork::AdManager->value])],
            'location' => $display ? ['required', Rule::enum(AdLocation::class)] : ['nullable'],
            'ad_unit_path' => $adManager ? ['required', 'string', 'max:255', 'regex:#^/\d+(,\d+)?(/[^\s<>"\'/]+)+$#'] : ['nullable'],
            'adsense_slot_id' => $adManager ? ['nullable'] : ['required', 'string', 'regex:/^\d{5,20}$/'],
            'sizes' => ['nullable', 'string', 'max:200', 'regex:/^\s*\d{1,4}x\d{1,4}(\s*,\s*\d{1,4}x\d{1,4})*\s*$/'],
            'devices' => ['required', Rule::enum(AdDevice::class)],
            'min_height' => ['required', 'integer', 'min:0', 'max:1000'],
            'every_n' => ['nullable', 'integer', 'min:1', 'max:50'],
            'load_timeout_seconds' => ['required', 'integer', 'min:3', 'max:60'],
            'min_watch_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'is_enabled' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'network.in' => 'Rewarded ads are only available through Ad Manager.',
            'ad_unit_path.regex' => 'An Ad Manager unit path looks like /1234567/rewarded.',
            'adsense_slot_id.regex' => 'The AdSense slot ID is digits only.',
            'sizes.regex' => 'Write sizes like 300x250, 728x90.',
        ];
    }

    /**
     * The values to store.
     *
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $data = $this->validated();
        $display = $data['kind'] === AdPlacementKind::Display->value;
        $adManager = $data['network'] === AdNetwork::AdManager->value;
        $sizes = $data['sizes'] ?? null;

        return [
            ...$data,
            'location' => $display ? $data['location'] : null,
            'ad_unit_path' => $adManager ? $data['ad_unit_path'] : null,
            'adsense_slot_id' => $adManager ? null : $data['adsense_slot_id'],
            'sizes' => $display && $adManager && is_string($sizes) ? array_map('trim', explode(',', $sizes)) : null,
            'every_n' => $display ? ($data['every_n'] ?? null) : null,
            'is_enabled' => $this->boolean('is_enabled'),
        ];
    }
}
