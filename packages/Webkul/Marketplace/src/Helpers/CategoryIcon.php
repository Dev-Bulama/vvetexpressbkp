<?php

namespace Webkul\Marketplace\Helpers;

class CategoryIcon
{
    /**
     * Keyword => icon key. First matching keyword wins. Public so both the
     * server-rendered partial and the header's Vue category component can
     * share the exact same mapping (exposed to JS via @json()).
     */
    public static array $keywords = [
        'dog' => 'paw',
        'cat' => 'paw',
        'puppy' => 'paw',
        'kitten' => 'paw',
        'farm' => 'feed',
        'poultry' => 'feed',
        'vaccine' => 'syringe',
        'medication' => 'syringe',
        'deworm' => 'shield',
        'flea' => 'shield',
        'vitamin' => 'pill',
        'supplement' => 'pill',
        'groom' => 'droplet',
        'shampoo' => 'droplet',
        'leash' => 'leash',
        'collar' => 'leash',
        'harness' => 'leash',
        'carrier' => 'crate',
        'crate' => 'crate',
        'bed' => 'bed',
        'housing' => 'bed',
        'toy' => 'toy',
        'aquarium' => 'fish',
        'fish' => 'fish',
        'bird' => 'bird',
        'reptile' => 'reptile',
        'equipment' => 'stethoscope',
        'veterinary' => 'stethoscope',
        'first aid' => 'bandage',
        'wound' => 'bandage',
        'litter' => 'litter',
        'waste' => 'litter',
        'training' => 'whistle',
        'behavior' => 'whistle',
    ];

    /**
     * icon key => SVG path `d` attribute (viewBox 0 0 24 24). Public for the
     * same reason as $keywords above.
     */
    public static array $paths = [
        'paw' => 'M4.5 12.5a2 2 0 100-4 2 2 0 000 4zM9.5 8.5a2 2 0 100-4 2 2 0 000 4zM14.5 8.5a2 2 0 100-4 2 2 0 000 4zM19.5 12.5a2 2 0 100-4 2 2 0 000 4zM12 21c-3 0-5.5-1.8-5.5-4.2 0-2 2-3.3 5.5-3.3s5.5 1.3 5.5 3.3C17.5 19.2 15 21 12 21z',
        'feed' => 'M4 20V10a8 8 0 0116 0v10M4 20h16M8 20v-4a4 4 0 018 0v4',
        'syringe' => 'M18 6l1-1m-4 4L20.5 3.5M14 8l2 2-8 8-3 1 1-3 8-8zM6 18l-2 2',
        'shield' => 'M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6l7-3z',
        'pill' => 'M7 14l7-7a3.5 3.5 0 015 5l-7 7a3.5 3.5 0 01-5-5z M9.5 11.5l3 3',
        'droplet' => 'M12 3s6 6.5 6 11a6 6 0 01-12 0c0-4.5 6-11 6-11z',
        'leash' => 'M4 6a2 2 0 114 0 2 2 0 01-4 0zm4 0h8a4 4 0 014 4v2a4 4 0 01-4 4h-1',
        'crate' => 'M4 8h16v11a1 1 0 01-1 1H5a1 1 0 01-1-1V8zM2 8h20L19 4H5L2 8zM10 12h4M10 16h4',
        'bed' => 'M3 18v-6a2 2 0 012-2h14a2 2 0 012 2v6M3 18h18M3 18v2M21 18v2M6 10V7a2 2 0 012-2h2a2 2 0 012 2v3',
        'toy' => 'M12 4a2.5 2.5 0 00-2.45 3H9a3 3 0 100 6h.55A2.5 2.5 0 1012 16.5 2.5 2.5 0 0014.45 13H15a3 3 0 100-6h-.55A2.5 2.5 0 0012 4z',
        'fish' => 'M3 12s3-5 9-5 9 5 9 5-3 5-9 5-9-5-9-5zM19 12l3-3v6l-3-3zM8 11.5h.01',
        'bird' => 'M4 14c0-4 3-8 9-8 3 0 5 2 5 4 0 3-3 4-3 4l3 4h-4l-2-2c-4 0-8-1-8-2z M13 10h.01',
        'reptile' => 'M3 15c2-4 5-9 9-9s5 3 5 5-2 3-4 3 1 2 3 2 3-2 5-1-1 5-5 5c-5 0-11-2-13-5z',
        'stethoscope' => 'M6 3v5a4 4 0 008 0V3M10 12v3a5 5 0 0010 0v-2M20 17a2 2 0 11-4 0 2 2 0 014 0z',
        'bandage' => 'M4.5 14.5l9-9a3.5 3.5 0 015 5l-9 9a3.5 3.5 0 01-5-5zM9 10l4 4',
        'litter' => 'M5 8h14l-1.5 11a2 2 0 01-2 1.8H8.5a2 2 0 01-2-1.8L5 8zM3 8h18M9 8V5h6v3',
        'whistle' => 'M8 12a4 4 0 118 0 4 4 0 01-8 0zM12 8V5h4v2M20 12h2',
        'box' => 'M20 7L12 3 4 7m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    ];

    /**
     * Resolve an icon key for a category name.
     */
    public static function keyFor(string $categoryName): string
    {
        $name = strtolower($categoryName);

        foreach (static::$keywords as $keyword => $icon) {
            if (str_contains($name, $keyword)) {
                return $icon;
            }
        }

        return 'box';
    }
}
