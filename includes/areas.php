<?php
// Dhaka areas (approximate latitude, longitude) used for restaurant and delivery locations.
const DHAKA_AREAS = [
    'Banani'                 => [23.7937, 90.4066],
    'Baridhara'              => [23.8000, 90.4225],
    'Gulshan 1'              => [23.7806, 90.4166],
    'Gulshan 2'              => [23.7925, 90.4147],
    'Mohakhali'              => [23.7781, 90.4056],
    'Badda'                  => [23.7806, 90.4262],
    'Bashundhara R/A'        => [23.8134, 90.4247],
    'Uttara'                 => [23.8759, 90.3795],
    'Mirpur 1'               => [23.7956, 90.3537],
    'Mirpur 10'              => [23.8069, 90.3687],
    'Mohammadpur'            => [23.7662, 90.3586],
    'Tejgaon'                => [23.7639, 90.3925],
    'Farmgate'               => [23.7561, 90.3872],
    'Dhanmondi'              => [23.7461, 90.3742],
    'Jigatola'               => [23.7399, 90.3753],
    'New Market'             => [23.7340, 90.3850],
    'Shahbagh'               => [23.7389, 90.3958],
    'Malibagh'               => [23.7497, 90.4130],
    'Rampura'                => [23.7612, 90.4261],
    'Khilgaon'               => [23.7510, 90.4290],
    'Motijheel'              => [23.7330, 90.4172],
    'Wari'                   => [23.7186, 90.4182],
    'Lalbagh'                => [23.7188, 90.3880],
    'Old Dhaka (Chawkbazar)' => [23.7194, 90.3961],
];

function valid_area($area) {
    return is_string($area) && isset(DHAKA_AREAS[$area]);
}

// Road distance estimate in km between two areas (straight line x 1.35 for road curves)
function area_distance($a, $b) {
    if (!valid_area($a) || !valid_area($b)) return null;
    [$lat1, $lon1] = DHAKA_AREAS[$a];
    [$lat2, $lon2] = DHAKA_AREAS[$b];
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $h = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $km = 2 * $r * asin(sqrt($h)) * 1.35;
    return max(0.5, round($km, 1));
}

function area_distance_km($a, $b) { return area_distance($a, $b); }

// Delivery fee (BDT) by distance
function delivery_fee($km) {
    if ($km === null) return 60;
    if ($km <= 2)  return 40;
    if ($km <= 4)  return 60;
    if ($km <= 7)  return 80;
    if ($km <= 10) return 100;
    if ($km <= 14) return 120;
    return 150;
}

// Total delivery charge for a cart: each restaurant sends its own rider,
// so the fee is the sum of (restaurant area -> customer area) fees.
// Returns ['total' => int, 'parts' => [restaurant name => ['km' => x, 'fee' => y]]]
function delivery_quote(array $items, $area) {
    $parts = [];
    foreach ($items as $it) {
        $rid = $it['restaurant_id'];
        if (isset($parts[$rid])) continue;
        $km = area_distance($it['restaurant_area'], $area);
        $parts[$rid] = ['name' => $it['restaurant_name'], 'km' => $km, 'fee' => delivery_fee($km)];
    }
    return ['total' => array_sum(array_column($parts, 'fee')), 'parts' => $parts];
}

function area_options($selected = '', $placeholder = '-- Select area --') {
    $html = '<option value="">' . htmlspecialchars($placeholder) . '</option>';
    foreach (array_keys(DHAKA_AREAS) as $a) {
        $html .= '<option value="' . htmlspecialchars($a, ENT_QUOTES) . '"' . ($a === $selected ? ' selected' : '') . '>' . htmlspecialchars($a) . '</option>';
    }
    return $html;
}
